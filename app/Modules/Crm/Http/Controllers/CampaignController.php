<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignEmailJob;
use App\Modules\Crm\Models\Campaign;
use App\Modules\Crm\Models\CampaignLinkClick;
use App\Modules\Crm\Models\CampaignRecipient;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\EmailList;
use App\Modules\Crm\Models\EmailListCategory;
use App\Modules\Crm\Models\EmailListContact;
use App\Modules\Crm\Models\Lead;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    /**
     * Subquery: email disiscritte (status=unsubscribed) su qualunque campagna del client.
     * Restituisce LOWER(email) as email
     */
    protected function unsubscribedEmailsQuery(int $clientId)
    {
        return DB::table('crm_campaign_recipients')
            ->join('crm_campaigns', 'crm_campaigns.id', '=', 'crm_campaign_recipients.campaign_id')
            ->where('crm_campaigns.client_id', $clientId)
            ->where('crm_campaign_recipients.status', 'unsubscribed')
            ->whereNotNull('crm_campaign_recipients.email')
            ->selectRaw('LOWER(crm_campaign_recipients.email) as email');
    }

    /**
     * Subquery: email già inviate oggi per il client.
     * Restituisce LOWER(email) as email
     */
    protected function sentTodayEmailsQuery(int $clientId, ?int $excludeCampaignId = null)
    {
        $q = DB::table('crm_campaign_recipients')
            ->join('crm_campaigns', 'crm_campaigns.id', '=', 'crm_campaign_recipients.campaign_id')
            ->where('crm_campaigns.client_id', $clientId)
            ->whereNotNull('crm_campaign_recipients.email')
            ->whereNotNull('crm_campaign_recipients.sent_at')
            ->whereDate('crm_campaign_recipients.sent_at', now()->toDateString())
            ->selectRaw('LOWER(crm_campaign_recipients.email) as email');

        if ($excludeCampaignId) {
            $q->where('crm_campaigns.id', '!=', $excludeCampaignId);
        }

        return $q;
    }

    /**
     * Verifica se una email ha già ricevuto una campagna oggi.
     */
    protected function wasEmailSentToday(string $email, int $clientId, ?int $excludeCampaignId = null): bool
    {
        $email = trim(mb_strtolower($email));

        $q = CampaignRecipient::query()
            ->join('crm_campaigns', 'crm_campaigns.id', '=', 'crm_campaign_recipients.campaign_id')
            ->where('crm_campaigns.client_id', $clientId)
            ->whereRaw('LOWER(crm_campaign_recipients.email) = ?', [$email])
            ->whereNotNull('crm_campaign_recipients.sent_at')
            ->whereDate('crm_campaign_recipients.sent_at', now()->toDateString());

        if ($excludeCampaignId) {
            $q->where('crm_campaigns.id', '!=', $excludeCampaignId);
        }

        return $q->exists();
    }

    /**
     * Marca come soppressi i destinatari che hanno già ricevuto una email oggi.
     * Ritorna il numero di destinatari bloccati.
     */
    protected function suppressRecipientsSentToday(Campaign $campaign, int $clientId): int
    {
        $sentToday = $this->sentTodayEmailsQuery($clientId, $campaign->id);

        $query = $campaign->recipients()
            ->whereIn('status', ['pending', 'queued'])
            ->whereRaw('LOWER(email) IN ('.$sentToday->toSql().')', $sentToday->getBindings());

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $update = [
            'status' => 'suppressed',
        ];

        if (Schema::hasColumn('crm_campaign_recipients', 'last_error')) {
            $update['last_error'] = 'Invio bloccato: email già contattata oggi da un’altra campagna.';
        }

        if (Schema::hasColumn('crm_campaign_recipients', 'updated_at')) {
            $update['updated_at'] = now();
        }

        CampaignRecipient::whereIn('id', $ids)->update($update);

        return $ids->count();
    }

    /**
     * Subquery: emails (LOWER) dei contatti appartenenti alle categorie selezionate (e opzionalmente liste).
     * Usata per: match lead->categorie (intersezione via email).
     */
    protected function categoryContactEmailsSubquery(int $clientId, array $listIds, array $categoryIds)
    {
        $q = DB::table('crm_email_list_contacts')
            ->join('crm_email_lists', 'crm_email_lists.id', '=', 'crm_email_list_contacts.list_id')
            ->join('crm_email_list_contact_category', 'crm_email_list_contact_category.contact_id', '=', 'crm_email_list_contacts.id')
            ->where('crm_email_lists.client_id', $clientId)
            ->where('crm_email_list_contacts.marketing_consense', true)
            ->whereNotNull('crm_email_list_contacts.email')
            ->whereIn('crm_email_list_contact_category.category_id', $categoryIds)
            ->selectRaw('LOWER(crm_email_list_contacts.email) as email');

        if (!empty($listIds)) {
            $q->whereIn('crm_email_list_contacts.list_id', $listIds);
        }

        $unsub = $this->unsubscribedEmailsQuery($clientId);
        $q->whereRaw('LOWER(crm_email_list_contacts.email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());

        return $q;
    }

    protected function isGloballyUnsubscribed(string $email, int $clientId): bool
    {
        $email = trim(mb_strtolower($email));

        $existsRecipientUnsub = CampaignRecipient::whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'unsubscribed')
            ->whereHas('campaign', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })
            ->exists();

        if ($existsRecipientUnsub) {
            return true;
        }

        $existsLead = Lead::where('client_id', $clientId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('marketing_consense', false)
            ->exists();

        if ($existsLead) {
            return true;
        }

        if (Schema::hasColumn('crm_customers', 'marketing_consense')) {
            $existsCustomer = Customer::where('client_id', $clientId)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('marketing_consense', false)
                ->exists();

            if ($existsCustomer) {
                return true;
            }
        }

        $existsListContact = EmailListContact::whereHas('list', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('marketing_consense', false)
            ->exists();

        return $existsListContact;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $statusOptions = Campaign::STATUS_OPTIONS + ['sent' => 'Inviata'];

        $filters = [
            'q'         => trim((string) $request->get('q', '')),
            'status'    => trim((string) $request->get('status', '')),
            'date_from' => trim((string) $request->get('date_from', '')),
            'date_to'   => trim((string) $request->get('date_to', '')),
            'sort'      => trim((string) $request->get('sort', 'created_desc')),
        ];

        $query = Campaign::query()
            ->where('client_id', $clientId);

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('preheader', 'like', "%{$q}%");
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        switch ($filters['sort']) {
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'sent_desc':
                $query->orderByDesc('sent_count')->orderByDesc('created_at');
                break;

            case 'open_desc':
                $query->orderByDesc('open_count')->orderByDesc('created_at');
                break;

            case 'click_desc':
                $query->orderByDesc('click_count')->orderByDesc('created_at');
                break;

            case 'recipients_desc':
                $query->orderByDesc('total_recipients')->orderByDesc('created_at');
                break;

            case 'created_desc':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $campaigns = $query
            ->paginate(20)
            ->appends($request->query());

        return view('crm::campaigns.index', compact('campaigns', 'filters', 'statusOptions'));
    }

    public function create(Request $request)
    {
        $defaultFromName  = config('mail.from.name');
        $defaultFromEmail = config('mail.from.address');

        return view('crm::campaigns.create', compact('defaultFromName', 'defaultFromEmail'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'name'           => 'required|string|max:190',
            'subject'        => 'required|string|max:190',
            'from_name'      => 'nullable|string|max:190',
            'from_email'     => 'nullable|email|max:190',
            'reply_to_email' => 'nullable|email|max:190',
            'preheader'      => 'nullable|string|max:255',
            'html_body'      => 'required|string',
            'text_body'      => 'nullable|string',
        ]);

        $campaign = Campaign::create($data + [
                'client_id' => $clientId,
                'status'    => 'draft',
            ]);

        return redirect()
            ->route('admin.crm.campaigns.edit', $campaign)
            ->with('success', 'Campagna creata.');
    }

    public function edit_OLD(Request $request, Campaign $campaign)
    {
        $clientId          = $this->clientId($request);
        $leadStatusOptions = Lead::STATUS_OPTIONS;
        $owners            = User::orderBy('name')->get(['id', 'name']);

        $emailLists = EmailList::where('client_id', $clientId)
            ->withCount('contacts')
            ->orderBy('name')
            ->get();

        $categories = EmailListCategory::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $linkStats = CampaignLinkClick::select(
            'url',
            DB::raw('SUM(click_count) as clicks'),
            DB::raw('COUNT(DISTINCT recipient_id) as unique_recipients')
        )
            ->where('campaign_id', $campaign->id)
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        return view('crm::campaigns.edit', [
            'campaign'          => $campaign,
            'leadStatusOptions' => $leadStatusOptions,
            'owners'            => $owners,
            'emailLists'        => $emailLists,
            'linkStats'         => $linkStats,
            'categories'        => $categories,
        ]);
    }

    public function edit(Request $request, Campaign $campaign)
    {
        $clientId          = $this->clientId($request);
        $leadStatusOptions = Lead::STATUS_OPTIONS;
        $owners            = User::orderBy('name')->get(['id', 'name']);

        $emailLists = EmailList::where('client_id', $clientId)
            ->withCount('contacts')
            ->orderBy('name')
            ->get();

        $categories = EmailListCategory::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $linkStats = CampaignLinkClick::select(
            'url',
            DB::raw('SUM(click_count) as clicks'),
            DB::raw('COUNT(DISTINCT recipient_id) as unique_recipients')
        )
            ->where('campaign_id', $campaign->id)
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        $clickRows = CampaignLinkClick::query()
            ->with(['recipient' => function ($q) {
                $q->select('id', 'email', 'name');
            }])
            ->where('campaign_id', $campaign->id)
            ->orderByDesc('last_clicked_at')
            ->limit(200)
            ->get();

        $topRecipients = DB::table('crm_campaign_link_clicks as c')
            ->join('crm_campaign_recipients as r', 'r.id', '=', 'c.recipient_id')
            ->where('c.campaign_id', $campaign->id)
            ->select(
                'r.email',
                'r.name',
                DB::raw('SUM(c.click_count) as clicks'),
                DB::raw('MAX(c.last_clicked_at) as last_clicked_at')
            )
            ->groupBy('r.email', 'r.name')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        return view('crm::campaigns.edit', [
            'campaign'          => $campaign,
            'leadStatusOptions' => $leadStatusOptions,
            'owners'            => $owners,
            'emailLists'        => $emailLists,
            'categories'        => $categories,
            'linkStats'         => $linkStats,
            'clickRows'         => $clickRows,
            'topRecipients'     => $topRecipients,
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:190',
            'subject'        => 'required|string|max:190',
            'from_name'      => 'nullable|string|max:190',
            'from_email'     => 'nullable|email|max:190',
            'reply_to_email' => 'nullable|email|max:190',
            'preheader'      => 'nullable|string|max:255',
            'html_body'      => 'required|string',
            'text_body'      => 'nullable|string',
        ]);

        $campaign->update($data);

        return redirect()
            ->route('admin.crm.campaigns.edit', $campaign)
            ->with('success', 'Campagna aggiornata.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()
            ->route('admin.crm.campaigns.index')
            ->with('success', 'Campagna eliminata.');
    }

    public function estimateRecipients(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['mixed', 'category_only'])],
            'include_leads'     => 'nullable|boolean',
            'include_customers' => 'nullable|boolean',
            'lead_status'       => 'nullable|array',
            'lead_status.*'     => 'string',
            'owner_id'          => 'nullable|integer|exists:users,id',
            'list_ids'          => 'nullable|array',
            'list_ids.*'        => [
                'integer',
                Rule::exists('crm_email_lists', 'id')->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
            'category_ids'      => 'nullable|array',
            'category_ids.*'    => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
            'match_leads_to_categories' => 'nullable|boolean',
        ]);

        $mode = $data['mode'] ?? 'mixed';
        $listIds = $data['list_ids'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        $matchLeadsToCategories = $request->boolean('match_leads_to_categories');

        $includeLeads     = $request->boolean('include_leads');
        $includeCustomers = $request->boolean('include_customers');
        $ownerId          = $data['owner_id'] ?? null;

        $unsub = $this->unsubscribedEmailsQuery($clientId);
        $sentToday = $this->sentTodayEmailsQuery($clientId, $campaign->id);

        $countSub = function ($q) {
            return DB::query()->fromSub($q, 't')->count();
        };

        $leadEmails = null;
        $customerEmails = null;
        $listEmails = null;

        $shouldIncludeListContacts = ($mode === 'category_only') || (!empty($listIds) || !empty($categoryIds));

        if ($shouldIncludeListContacts) {
            $base = DB::table('crm_email_list_contacts')
                ->join('crm_email_lists', 'crm_email_lists.id', '=', 'crm_email_list_contacts.list_id')
                ->where('crm_email_lists.client_id', $clientId)
                ->where('crm_email_list_contacts.marketing_consense', true)
                ->whereNotNull('crm_email_list_contacts.email');

            if (!empty($listIds)) {
                $base->whereIn('crm_email_list_contacts.list_id', $listIds);
            }

            if (!empty($categoryIds)) {
                $base->whereExists(function ($q) use ($categoryIds) {
                    $q->select(DB::raw(1))
                        ->from('crm_email_list_contact_category as piv')
                        ->whereColumn('piv.contact_id', 'crm_email_list_contacts.id')
                        ->whereIn('piv.category_id', $categoryIds);
                });
            }

            $base->whereRaw(
                'LOWER(crm_email_list_contacts.email) NOT IN ('.$unsub->toSql().')',
                $unsub->getBindings()
            );

            $base->whereRaw(
                'LOWER(crm_email_list_contacts.email) NOT IN ('.$sentToday->toSql().')',
                $sentToday->getBindings()
            );

            $listEmails = (clone $base)
                ->selectRaw('LOWER(crm_email_list_contacts.email) as email')
                ->distinct();
        }

        if ($mode !== 'category_only') {
            if ($includeLeads) {
                $leadQ = DB::table('crm_leads')
                    ->where('client_id', $clientId)
                    ->where('marketing_consense', true)
                    ->whereNotNull('email')
                    ->selectRaw('LOWER(email) as email')
                    ->distinct();

                if (!empty($data['lead_status'])) {
                    $leadQ->whereIn('status', $data['lead_status']);
                }

                if ($ownerId) {
                    $leadQ->where('owner_id', $ownerId);
                }

                $leadQ->whereRaw('LOWER(email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());
                $leadQ->whereRaw('LOWER(email) NOT IN ('.$sentToday->toSql().')', $sentToday->getBindings());

                if ($matchLeadsToCategories) {
                    if (empty($categoryIds)) {
                        return response()->json([
                            'ok' => false,
                            'message' => 'Per “Categoria + Stato lead” devi selezionare almeno una categoria.',
                        ], 422);
                    }

                    $catEmails = $this->categoryContactEmailsSubquery($clientId, $listIds, $categoryIds);
                    $leadQ->whereRaw('LOWER(email) IN ('.$catEmails->toSql().')', $catEmails->getBindings());
                }

                $leadEmails = $leadQ;
            }

            if ($includeCustomers) {
                $custQ = DB::table('crm_customers')
                    ->where('client_id', $clientId)
                    ->whereNotNull('email')
                    ->selectRaw('LOWER(email) as email')
                    ->distinct();

                if (Schema::hasColumn('crm_customers', 'marketing_consense')) {
                    $custQ->where('marketing_consense', true);
                }

                $custQ->whereRaw('LOWER(email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());
                $custQ->whereRaw('LOWER(email) NOT IN ('.$sentToday->toSql().')', $sentToday->getBindings());

                $customerEmails = $custQ;
            }
        }

        $leadCount     = $leadEmails ? $countSub($leadEmails) : 0;
        $customerCount = $customerEmails ? $countSub($customerEmails) : 0;
        $listCount     = $listEmails ? $countSub($listEmails) : 0;

        $parts = array_values(array_filter([$leadEmails, $customerEmails, $listEmails]));

        if (empty($parts)) {
            $total = 0;
        } else {
            $u = array_shift($parts);
            foreach ($parts as $p) {
                $u->unionAll($p);
            }

            $total = DB::query()
                ->fromSub($u, 'u')
                ->distinct()
                ->count('email');
        }

        return response()->json([
            'ok' => true,
            'mode' => $mode,
            'total_unique' => $total,
            'breakdown' => [
                'leads_unique' => $leadCount,
                'customers_unique' => $customerCount,
                'list_contacts_unique' => $listCount,
            ],
        ]);
    }

    public function updateRecipients(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['mixed', 'category_only'])],
            'include_leads'     => 'nullable|boolean',
            'include_customers' => 'nullable|boolean',
            'lead_status'       => 'nullable|array',
            'lead_status.*'     => 'string',
            'owner_id'          => 'nullable|integer|exists:users,id',
            'list_ids'          => 'nullable|array',
            'list_ids.*'        => [
                'integer',
                Rule::exists('crm_email_lists', 'id')->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
            'category_ids'      => 'nullable|array',
            'category_ids.*'    => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
            'match_leads_to_categories' => 'nullable|boolean',
        ]);

        $mode = $data['mode'] ?? 'mixed';
        $listIds = $data['list_ids'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        $matchLeadsToCategories = $request->boolean('match_leads_to_categories');

        $includeLeads     = $request->boolean('include_leads');
        $includeCustomers = $request->boolean('include_customers');
        $ownerId          = $data['owner_id'] ?? null;

        if ($mode === 'category_only' && empty($categoryIds)) {
            return back()->with('error', 'Per “Campagna solo per categoria” devi selezionare almeno una categoria.');
        }

        if ($mode !== 'category_only' && $matchLeadsToCategories && empty($categoryIds)) {
            return back()->with('error', 'Per “Categoria + Stato lead” devi selezionare almeno una categoria.');
        }

        $unsub = $this->unsubscribedEmailsQuery($clientId);
        $sentToday = $this->sentTodayEmailsQuery($clientId, $campaign->id);

        $collection = collect();

        $shouldIncludeListContacts = ($mode === 'category_only') || (!empty($listIds) || !empty($categoryIds));

        if ($shouldIncludeListContacts) {
            $listContactsQuery = EmailListContact::query()
                ->where('marketing_consense', true)
                ->whereNotNull('email')
                ->whereHas('list', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                });

            if (!empty($listIds)) {
                $listContactsQuery->whereIn('list_id', $listIds);
            }

            if (!empty($categoryIds)) {
                $listContactsQuery->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('crm_email_list_categories.id', $categoryIds);
                });
            }

            $listContactsQuery->whereRaw('LOWER(email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());
            $listContactsQuery->whereRaw('LOWER(email) NOT IN ('.$sentToday->toSql().')', $sentToday->getBindings());

            $listContacts = $listContactsQuery->get();

            $collection = $collection->merge(
                $listContacts->map(function (EmailListContact $c) {
                    return [
                        'contact_type' => $c->contact_type ?: 'csv',
                        'contact_id'   => $c->contact_id,
                        'email'        => $c->email,
                        'name'         => $c->name,
                        'segment'      => $c->segment ?: 'lista',
                    ];
                })
            );
        }

        if ($mode !== 'category_only') {
            if ($includeLeads) {
                $leadQuery = Lead::where('client_id', $clientId)
                    ->where('marketing_consense', true)
                    ->whereNotNull('email');

                if (!empty($data['lead_status'])) {
                    $leadQuery->whereIn('status', $data['lead_status']);
                }

                if ($ownerId) {
                    $leadQuery->where('owner_id', $ownerId);
                }

                $leadQuery->whereRaw('LOWER(email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());
                $leadQuery->whereRaw('LOWER(email) NOT IN ('.$sentToday->toSql().')', $sentToday->getBindings());

                if ($matchLeadsToCategories) {
                    $catEmails = $this->categoryContactEmailsSubquery($clientId, $listIds, $categoryIds);
                    $leadQuery->whereRaw('LOWER(email) IN ('.$catEmails->toSql().')', $catEmails->getBindings());
                }

                $leads = $leadQuery->get();

                $collection = $collection->merge(
                    $leads->map(function (Lead $lead) {
                        return [
                            'contact_type' => 'lead',
                            'contact_id'   => $lead->id,
                            'email'        => $lead->email,
                            'name'         => $lead->name,
                            'segment'      => 'lead',
                        ];
                    })
                );
            }

            if ($includeCustomers) {
                $customerQuery = Customer::where('client_id', $clientId)
                    ->whereNotNull('email');

                if (Schema::hasColumn('crm_customers', 'marketing_consense')) {
                    $customerQuery->where('marketing_consense', true);
                }

                $customerQuery->whereRaw('LOWER(email) NOT IN ('.$unsub->toSql().')', $unsub->getBindings());
                $customerQuery->whereRaw('LOWER(email) NOT IN ('.$sentToday->toSql().')', $sentToday->getBindings());

                $customers = $customerQuery->get();

                $collection = $collection->merge(
                    $customers->map(function (Customer $customer) {
                        return [
                            'contact_type' => 'customer',
                            'contact_id'   => $customer->id,
                            'email'        => $customer->email,
                            'name'         => $customer->name,
                            'segment'      => 'customer',
                        ];
                    })
                );
            }
        }

        $unique = $collection->unique(function ($row) {
            return mb_strtolower(trim($row['email'] ?? ''));
        });

        DB::transaction(function () use ($campaign, $unique) {
            $campaign->recipients()->delete();

            foreach ($unique as $row) {
                $campaign->recipients()->create($row + [
                        'status' => 'pending',
                        'hash'   => Str::random(40),
                    ]);
            }

            $campaign->update([
                'total_recipients'   => $campaign->recipients()->count(),
                'sent_count'         => 0,
                'open_count'         => 0,
                'click_count'        => 0,
                'bounce_count'       => 0,
                'unsubscribe_count'  => 0,
                'status'             => 'draft',
                'scheduled_at'       => null,
                'sent_at'            => null,
            ]);
        });

        return redirect()
            ->route('admin.crm.campaigns.edit', $campaign)
            ->with(
                'success',
                $mode === 'category_only'
                    ? 'Destinatari rigenerati SOLO per categoria.'
                    : 'Destinatari rigenerati (mix: lead/clienti/liste, con eventuale match lead->categorie).'
            );
    }

    public function importCsv(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'file'    => 'required|file|mimes:csv,txt',
            'segment' => 'nullable|string|max:190',
        ]);

        $file    = $data['file'];
        $segment = $data['segment'] ?: 'Import ' . $file->getClientOriginalName();
        $path    = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Impossibile leggere il file CSV.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return back()->with('error', 'File CSV vuoto.');
        }

        $delimiter = ';';
        if (strpos($firstLine, ';') === false && strpos($firstLine, ',') !== false) {
            $delimiter = ',';
        }

        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter);

        if (!$header || !is_array($header)) {
            fclose($handle);
            return back()->with('error', 'Header CSV non valido.');
        }

        $normalized = array_map(function ($h) {
            return strtoupper(trim((string) $h));
        }, $header);

        $colEmail   = array_search('EMAIL', $normalized, true);
        $colNome    = array_search('NOME', $normalized, true);
        $colCognome = array_search('COGNOME', $normalized, true);

        if ($colEmail === false) {
            fclose($handle);
            return back()->with('error', 'Colonna EMAIL non trovata nel CSV. Header trovato: ' . implode(', ', $header));
        }

        $countImported = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                $email = trim($row[$colEmail] ?? '');
                if (!$email) {
                    continue;
                }

                if ($this->isGloballyUnsubscribed($email, $clientId)) {
                    continue;
                }

                if ($this->wasEmailSentToday($email, $clientId, $campaign->id)) {
                    continue;
                }

                $firstName = $colNome !== false ? trim($row[$colNome] ?? '') : '';
                $lastName  = $colCognome !== false ? trim($row[$colCognome] ?? '') : '';
                $name      = trim($firstName . ' ' . $lastName);

                $exists = $campaign->recipients()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $campaign->recipients()->create([
                    'contact_type' => 'csv',
                    'contact_id'   => null,
                    'email'        => $email,
                    'name'         => $name ?: null,
                    'segment'      => $segment,
                    'status'       => 'pending',
                    'hash'         => Str::random(40),
                ]);

                $countImported++;
            }

            fclose($handle);

            $campaign->update([
                'total_recipients' => $campaign->recipients()->count(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            report($e);

            return back()->with('error', 'Errore durante l\'import CSV: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.crm.campaigns.edit', $campaign)
            ->with('success', "Import CSV completato. Contatti aggiunti: {$countImported}.");
    }

    public function clearRecipients(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);
        if ((int) $campaign->client_id !== (int) $clientId) {
            abort(403);
        }

        $count = $campaign->recipients()->count();

        $campaign->recipients()->delete();

        $campaign->update([
            'total_recipients'   => 0,
            'sent_count'         => 0,
            'open_count'         => 0,
            'click_count'        => 0,
            'bounce_count'       => 0,
            'unsubscribe_count'  => 0,
            'status'             => 'draft',
            'scheduled_at'       => null,
            'sent_at'            => null,
        ]);

        return back()->with(
            'success',
            "Sono stati cancellati {$count} destinatari e azzerate le statistiche della campagna."
        );
    }

    public function queue(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);
        if ((int) $campaign->client_id !== (int) $clientId) {
            abort(403);
        }

        $suppressedCount = $this->suppressRecipientsSentToday($campaign, $clientId);

        $pending = $campaign->recipients()
            ->where('status', 'pending')
            ->get();

        foreach ($pending as $recipient) {
            $recipient->update([
                'status'    => 'queued',
                'queued_at' => now(),
            ]);

            SendCampaignEmailJob::dispatch($campaign->id, $recipient->id);
        }

        $campaign->update([
            'status'       => $pending->isEmpty() ? 'draft' : 'sending',
            'scheduled_at' => $pending->isEmpty() ? null : now(),
        ]);

        $message = 'Campagna messa in coda per l\'invio.';
        if ($suppressedCount > 0) {
            $message .= " {$suppressedCount} destinatari bloccati perché già contattati oggi.";
        }

        return redirect()
            ->route('admin.crm.campaigns.edit', $campaign)
            ->with('success', $message);
    }

    public function sendNow(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);
        if ((int) $campaign->client_id !== (int) $clientId) {
            abort(403);
        }

        $suppressedCount = $this->suppressRecipientsSentToday($campaign, $clientId);

        $batchSize = 20;

        $pendingQuery = $campaign->recipients()
            ->whereIn('status', ['pending', 'queued']);

        $total = $campaign->total_recipients ?: $campaign->recipients()->count();

        $recipients = $pendingQuery->limit($batchSize)->get();

        if ($recipients->isEmpty()) {
            $sent = $campaign->recipients()
                ->whereNotNull('sent_at')
                ->count();

            $campaign->sent_count = $sent;
            if ($campaign->status !== 'completed') {
                $campaign->status  = 'completed';
                $campaign->sent_at = now();
            }

            try {
                $campaign->save();
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'error'   => true,
                    'message' => 'Errore aggiornando lo stato campagna: '.$e->getMessage(),
                ]);
            }

            return response()->json([
                'done'             => true,
                'total'            => $total,
                'sent'             => $sent,
                'suppressed_today' => $suppressedCount,
            ]);
        }

        foreach ($recipients as $recipient) {
            $job = new SendCampaignEmailJob($campaign->id, $recipient->id);
            $job->handle();
        }

        $sent = $campaign->recipients()
            ->whereNotNull('sent_at')
            ->count();

        $campaign->sent_count = $sent;
        if ($campaign->status !== 'completed') {
            $campaign->status = 'sending';
        }
        $campaign->save();

        return response()->json([
            'done'             => false,
            'batch'            => $recipients->count(),
            'total'            => $total,
            'sent'             => $sent,
            'suppressed_today' => $suppressedCount,
        ]);
    }

    public function retryErrors(Request $request, Campaign $campaign)
    {
        $clientId = $this->clientId($request);
        if ((int) $campaign->client_id !== (int) $clientId) {
            abort(403);
        }

        $errorQuery = $campaign->recipients()->where('status', 'failed');
        $errorCount = $errorQuery->count();

        if ($errorCount === 0) {
            return back()->with('info', 'Non ci sono destinatari in errore da riprovare.');
        }

        $data = ['status' => 'pending'];
        if (Schema::hasColumn('crm_campaign_recipients', 'last_error')) {
            $data['last_error'] = null;
        }

        $errorQuery->update($data);

        $campaign->sent_count = $campaign->recipients()
            ->whereNotNull('sent_at')
            ->count();

        if (in_array($campaign->status, ['completed', 'sent'], true)) {
            $campaign->status  = 'draft';
            $campaign->sent_at = null;
        }

        $campaign->save();

        return back()->with(
            'success',
            "Sono stati rimessi in coda {$errorCount} destinatari in errore. Ora puoi rilanciare l'invio."
        );
    }
}
