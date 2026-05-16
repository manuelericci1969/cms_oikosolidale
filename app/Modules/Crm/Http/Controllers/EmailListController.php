<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\EmailList;
use App\Modules\Crm\Models\EmailListCategory;
use App\Modules\Crm\Models\EmailListContact;
use App\Modules\Crm\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmailListController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function ensureSameClient(EmailList $list, int $clientId): void
    {
        if ((int) $list->client_id !== (int) $clientId) {
            abort(404);
        }
    }

    protected function cleanNullableString(mixed $value, bool $uppercase = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $uppercase ? mb_strtoupper($value) : $value;
    }

    protected function contactData(array $data, bool $defaultMarketingConsense = true): array
    {
        return [
            'email'                => trim((string) $data['email']),
            'name'                 => $this->cleanNullableString($data['name'] ?? null),
            'segment'              => $this->cleanNullableString($data['segment'] ?? null),
            'city'                 => $this->cleanNullableString($data['city'] ?? null),
            'province'             => $this->cleanNullableString($data['province'] ?? null, true),
            'region'               => $this->cleanNullableString($data['region'] ?? null),
            'country'              => $this->cleanNullableString($data['country'] ?? null),
            'postal_code'          => $this->cleanNullableString($data['postal_code'] ?? null),
            'phone'                => $this->cleanNullableString($data['phone'] ?? null),
            'whatsapp'             => $this->cleanNullableString($data['whatsapp'] ?? null),
            'website_url'          => $this->cleanNullableString($data['website_url'] ?? null),
            'contact_page_url'     => $this->cleanNullableString($data['contact_page_url'] ?? null),
            'address'              => $this->cleanNullableString($data['address'] ?? null),
            'business_type'        => $this->cleanNullableString($data['business_type'] ?? null),
            'stars'                => isset($data['stars']) && $data['stars'] !== '' ? (int) $data['stars'] : null,
            'vat_number'           => $this->cleanNullableString($data['vat_number'] ?? null),
            'cin_code'             => $this->cleanNullableString($data['cin_code'] ?? null),
            'contact_role'         => $this->cleanNullableString($data['contact_role'] ?? null),
            'email_status'         => $this->cleanNullableString($data['email_status'] ?? null),
            'source_type'          => $this->cleanNullableString($data['source_type'] ?? null),
            'source_url'           => $this->cleanNullableString($data['source_url'] ?? null),
            'site_rating'          => $this->cleanNullableString($data['site_rating'] ?? null),
            'commercial_potential' => $this->cleanNullableString($data['commercial_potential'] ?? null),
            'seo_score'            => isset($data['seo_score']) && $data['seo_score'] !== '' ? (float) $data['seo_score'] : null,
            'last_verified_at'     => $this->cleanNullableString($data['last_verified_at'] ?? null),
            'notes'                => $this->cleanNullableString($data['notes'] ?? null),
            'marketing_consense'   => array_key_exists('marketing_consense', $data)
                ? (bool) $data['marketing_consense']
                : $defaultMarketingConsense,
        ];
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $lists = EmailList::where('client_id', $clientId)
            ->withCount('contacts')
            ->orderBy('name')
            ->paginate(20);

        return view('crm::email_lists.index', compact('lists'));
    }

    public function create(Request $request)
    {
        $clientId          = $this->clientId($request);
        $list              = new EmailList();
        $list->client_id   = $clientId;
        $contacts          = collect();
        $leadStatusOptions = Lead::STATUS_OPTIONS;
        $owners            = User::orderBy('name')->get(['id', 'name']);

        $categories = EmailListCategory::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('crm::email_lists.edit', compact(
            'list',
            'contacts',
            'leadStatusOptions',
            'owners',
            'categories'
        ));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'name'        => 'required|string|max:190',
            'description' => 'nullable|string',
        ]);

        $list = EmailList::create([
            'client_id'   => $clientId,
            'name'        => trim((string) $data['name']),
            'description' => $this->cleanNullableString($data['description'] ?? null),
        ]);

        return redirect()
            ->route('admin.crm.email-lists.edit', $list)
            ->with('success', 'Lista creata.');
    }

    public function edit(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $contacts = $list->contacts()
            ->with('categories:id,name')
            ->orderBy('email')
            ->limit(100)
            ->get();

        $leadStatusOptions = Lead::STATUS_OPTIONS;
        $owners            = User::orderBy('name')->get(['id', 'name']);

        $categories = EmailListCategory::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('crm::email_lists.edit', compact(
            'list',
            'contacts',
            'leadStatusOptions',
            'owners',
            'categories'
        ));
    }

    public function update(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'name'        => 'required|string|max:190',
            'description' => 'nullable|string',
        ]);

        $list->update([
            'name'        => trim((string) $data['name']),
            'description' => $this->cleanNullableString($data['description'] ?? null),
        ]);

        return redirect()
            ->route('admin.crm.email-lists.edit', $list)
            ->with('success', 'Lista aggiornata.');
    }

    public function destroy(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $list->delete();

        return redirect()
            ->route('admin.crm.email-lists.index')
            ->with('success', 'Lista eliminata.');
    }

    public function importCsv(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

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

        $normalized = array_map(fn ($h) => strtoupper(trim((string) $h)), $header);

        $map = [
            'email'                => array_search('EMAIL', $normalized, true),
            'nome'                 => array_search('NOME', $normalized, true),
            'cognome'              => array_search('COGNOME', $normalized, true),
            'city'                 => array_search('CITY', $normalized, true),
            'province'             => array_search('PROVINCE', $normalized, true),
            'region'               => array_search('REGION', $normalized, true),
            'country'              => array_search('COUNTRY', $normalized, true),
            'postal_code'          => array_search('POSTAL_CODE', $normalized, true),
            'phone'                => array_search('PHONE', $normalized, true),
            'whatsapp'             => array_search('WHATSAPP', $normalized, true),
            'website_url'          => array_search('WEBSITE_URL', $normalized, true),
            'contact_page_url'     => array_search('CONTACT_PAGE_URL', $normalized, true),
            'address'              => array_search('ADDRESS', $normalized, true),
            'business_type'        => array_search('BUSINESS_TYPE', $normalized, true),
            'stars'                => array_search('STARS', $normalized, true),
            'vat_number'           => array_search('VAT_NUMBER', $normalized, true),
            'cin_code'             => array_search('CIN_CODE', $normalized, true),
            'contact_role'         => array_search('CONTACT_ROLE', $normalized, true),
            'email_status'         => array_search('EMAIL_STATUS', $normalized, true),
            'source_type'          => array_search('SOURCE_TYPE', $normalized, true),
            'source_url'           => array_search('SOURCE_URL', $normalized, true),
            'site_rating'          => array_search('SITE_RATING', $normalized, true),
            'commercial_potential' => array_search('COMMERCIAL_POTENTIAL', $normalized, true),
            'seo_score'            => array_search('SEO_SCORE', $normalized, true),
            'last_verified_at'     => array_search('LAST_VERIFIED_AT', $normalized, true),
            'notes'                => array_search('NOTES', $normalized, true),
        ];

        if ($map['email'] === false) {
            fclose($handle);
            return back()->with('error', 'Colonna EMAIL non trovata nel CSV. Header: ' . implode(', ', $header));
        }

        $countImported = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }

                $email = trim((string) ($row[$map['email']] ?? ''));
                if ($email === '') {
                    continue;
                }

                $firstName = $map['nome'] !== false ? trim((string) ($row[$map['nome']] ?? '')) : '';
                $lastName  = $map['cognome'] !== false ? trim((string) ($row[$map['cognome']] ?? '')) : '';
                $fullName  = trim($firstName . ' ' . $lastName);

                $exists = $list->contacts()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $list->contacts()->create([
                    'contact_type'       => 'csv',
                    'contact_id'         => null,
                    'email'              => $email,
                    'name'               => $fullName !== '' ? Str::limit($fullName, 190) : null,
                    'segment'            => $segment,
                    'city'               => $map['city'] !== false ? $this->cleanNullableString($row[$map['city']] ?? null) : null,
                    'province'           => $map['province'] !== false ? $this->cleanNullableString($row[$map['province']] ?? null, true) : null,
                    'region'             => $map['region'] !== false ? $this->cleanNullableString($row[$map['region']] ?? null) : null,
                    'country'            => $map['country'] !== false ? $this->cleanNullableString($row[$map['country']] ?? null) : null,
                    'postal_code'        => $map['postal_code'] !== false ? $this->cleanNullableString($row[$map['postal_code']] ?? null) : null,
                    'phone'              => $map['phone'] !== false ? $this->cleanNullableString($row[$map['phone']] ?? null) : null,
                    'whatsapp'           => $map['whatsapp'] !== false ? $this->cleanNullableString($row[$map['whatsapp']] ?? null) : null,
                    'website_url'        => $map['website_url'] !== false ? $this->cleanNullableString($row[$map['website_url']] ?? null) : null,
                    'contact_page_url'   => $map['contact_page_url'] !== false ? $this->cleanNullableString($row[$map['contact_page_url']] ?? null) : null,
                    'address'            => $map['address'] !== false ? $this->cleanNullableString($row[$map['address']] ?? null) : null,
                    'business_type'      => $map['business_type'] !== false ? $this->cleanNullableString($row[$map['business_type']] ?? null) : null,
                    'stars'              => $map['stars'] !== false && ($row[$map['stars']] ?? '') !== '' ? (int) $row[$map['stars']] : null,
                    'vat_number'         => $map['vat_number'] !== false ? $this->cleanNullableString($row[$map['vat_number']] ?? null) : null,
                    'cin_code'           => $map['cin_code'] !== false ? $this->cleanNullableString($row[$map['cin_code']] ?? null) : null,
                    'contact_role'       => $map['contact_role'] !== false ? $this->cleanNullableString($row[$map['contact_role']] ?? null) : null,
                    'email_status'       => $map['email_status'] !== false ? $this->cleanNullableString($row[$map['email_status']] ?? null) : null,
                    'source_type'        => $map['source_type'] !== false ? $this->cleanNullableString($row[$map['source_type']] ?? null) : null,
                    'source_url'         => $map['source_url'] !== false ? $this->cleanNullableString($row[$map['source_url']] ?? null) : null,
                    'site_rating'        => $map['site_rating'] !== false ? $this->cleanNullableString($row[$map['site_rating']] ?? null) : null,
                    'commercial_potential' => $map['commercial_potential'] !== false ? $this->cleanNullableString($row[$map['commercial_potential']] ?? null) : null,
                    'seo_score'          => $map['seo_score'] !== false && ($row[$map['seo_score']] ?? '') !== '' ? (float) $row[$map['seo_score']] : null,
                    'last_verified_at'   => $map['last_verified_at'] !== false ? $this->cleanNullableString($row[$map['last_verified_at']] ?? null) : null,
                    'notes'              => $map['notes'] !== false ? $this->cleanNullableString($row[$map['notes']] ?? null) : null,
                    'marketing_consense' => true,
                ]);

                $countImported++;
            }

            fclose($handle);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            report($e);

            return back()->with('error', 'Errore durante l\'import CSV: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.crm.email-lists.edit', $list)
            ->with('success', "Import CSV completato. Contatti aggiunti: {$countImported}.");
    }

    public function syncFromCrm(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'include_leads'     => 'nullable|boolean',
            'include_customers' => 'nullable|boolean',
            'lead_status'       => 'nullable|array',
            'lead_status.*'     => 'string',
            'owner_id'          => 'nullable|integer|exists:users,id',
        ]);

        $includeLeads     = $request->boolean('include_leads');
        $includeCustomers = $request->boolean('include_customers');
        $ownerId          = $data['owner_id'] ?? null;

        $collection = collect();

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

            $leads = $leadQuery->get();

            $collection = $collection->merge(
                $leads->map(function (Lead $lead) {
                    return [
                        'contact_type'         => 'lead',
                        'contact_id'           => $lead->id,
                        'email'                => trim((string) $lead->email),
                        'name'                 => Str::limit((string) ($lead->name ?? ''), 190),
                        'segment'              => 'lead',
                        'city'                 => $this->cleanNullableString(data_get($lead, 'city')),
                        'province'             => $this->cleanNullableString(data_get($lead, 'province'), true),
                        'region'               => $this->cleanNullableString(data_get($lead, 'region')),
                        'country'              => $this->cleanNullableString(data_get($lead, 'country')),
                        'postal_code'          => $this->cleanNullableString(data_get($lead, 'postal_code')),
                        'phone'                => $this->cleanNullableString(data_get($lead, 'phone')),
                        'whatsapp'             => $this->cleanNullableString(data_get($lead, 'whatsapp')),
                        'website_url'          => $this->cleanNullableString(data_get($lead, 'website_url')),
                        'contact_page_url'     => $this->cleanNullableString(data_get($lead, 'contact_page_url')),
                        'address'              => $this->cleanNullableString(data_get($lead, 'address')),
                        'business_type'        => $this->cleanNullableString(data_get($lead, 'business_type')),
                        'stars'                => data_get($lead, 'stars'),
                        'vat_number'           => $this->cleanNullableString(data_get($lead, 'vat_number')),
                        'cin_code'             => $this->cleanNullableString(data_get($lead, 'cin_code')),
                        'contact_role'         => $this->cleanNullableString(data_get($lead, 'contact_role')),
                        'email_status'         => $this->cleanNullableString(data_get($lead, 'email_status')),
                        'source_type'          => $this->cleanNullableString(data_get($lead, 'source_type')),
                        'source_url'           => $this->cleanNullableString(data_get($lead, 'source_url')),
                        'site_rating'          => $this->cleanNullableString(data_get($lead, 'site_rating')),
                        'commercial_potential' => $this->cleanNullableString(data_get($lead, 'commercial_potential')),
                        'seo_score'            => data_get($lead, 'seo_score'),
                        'last_verified_at'     => data_get($lead, 'last_verified_at'),
                        'notes'                => $this->cleanNullableString(data_get($lead, 'notes')),
                        'marketing_consense'   => true,
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

            $customers = $customerQuery->get();

            $collection = $collection->merge(
                $customers->map(function (Customer $customer) {
                    return [
                        'contact_type'         => 'customer',
                        'contact_id'           => $customer->id,
                        'email'                => trim((string) $customer->email),
                        'name'                 => Str::limit((string) ($customer->name ?? ''), 190),
                        'segment'              => 'customer',
                        'city'                 => $this->cleanNullableString(data_get($customer, 'city')),
                        'province'             => $this->cleanNullableString(data_get($customer, 'province'), true),
                        'region'               => $this->cleanNullableString(data_get($customer, 'region')),
                        'country'              => $this->cleanNullableString(data_get($customer, 'country')),
                        'postal_code'          => $this->cleanNullableString(data_get($customer, 'postal_code')),
                        'phone'                => $this->cleanNullableString(data_get($customer, 'phone')),
                        'whatsapp'             => $this->cleanNullableString(data_get($customer, 'whatsapp')),
                        'website_url'          => $this->cleanNullableString(data_get($customer, 'website_url')),
                        'contact_page_url'     => $this->cleanNullableString(data_get($customer, 'contact_page_url')),
                        'address'              => $this->cleanNullableString(data_get($customer, 'address')),
                        'business_type'        => $this->cleanNullableString(data_get($customer, 'business_type')),
                        'stars'                => data_get($customer, 'stars'),
                        'vat_number'           => $this->cleanNullableString(data_get($customer, 'vat_number')),
                        'cin_code'             => $this->cleanNullableString(data_get($customer, 'cin_code')),
                        'contact_role'         => $this->cleanNullableString(data_get($customer, 'contact_role')),
                        'email_status'         => $this->cleanNullableString(data_get($customer, 'email_status')),
                        'source_type'          => $this->cleanNullableString(data_get($customer, 'source_type')),
                        'source_url'           => $this->cleanNullableString(data_get($customer, 'source_url')),
                        'site_rating'          => $this->cleanNullableString(data_get($customer, 'site_rating')),
                        'commercial_potential' => $this->cleanNullableString(data_get($customer, 'commercial_potential')),
                        'seo_score'            => data_get($customer, 'seo_score'),
                        'last_verified_at'     => data_get($customer, 'last_verified_at'),
                        'notes'                => $this->cleanNullableString(data_get($customer, 'notes')),
                        'marketing_consense'   => true,
                    ];
                })
            );
        }

        $unique = $collection->unique(fn ($item) => mb_strtolower(trim((string) $item['email'])));

        $existingCategoriesByEmail = $list->contacts()
            ->whereIn('contact_type', ['lead', 'customer'])
            ->with('categories:id')
            ->get()
            ->mapWithKeys(fn ($c) => [mb_strtolower(trim((string) $c->email)) => $c->categories->pluck('id')->all()]);

        DB::transaction(function () use ($list, $unique, $existingCategoriesByEmail) {
            $list->contacts()
                ->whereIn('contact_type', ['lead', 'customer'])
                ->delete();

            foreach ($unique as $row) {
                $new = $list->contacts()->create($row);

                $ids = $existingCategoriesByEmail[mb_strtolower(trim((string) $row['email']))] ?? [];
                if (!empty($ids)) {
                    $new->categories()->sync($ids);
                }
            }
        });

        return redirect()
            ->route('admin.crm.email-lists.edit', $list)
            ->with('success', 'Lista aggiornata da lead/clienti.');
    }

    public function storeContact(Request $request, EmailList $list)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'email'                => 'required|email|max:190',
            'name'                 => 'nullable|string|max:255',
            'segment'              => 'nullable|string|max:190',
            'city'                 => 'nullable|string|max:190',
            'province'             => 'nullable|string|max:10',
            'region'               => 'nullable|string|max:190',
            'country'              => 'nullable|string|max:190',
            'postal_code'          => 'nullable|string|max:20',
            'phone'                => 'nullable|string|max:50',
            'whatsapp'             => 'nullable|string|max:50',
            'website_url'          => 'nullable|url|max:255',
            'contact_page_url'     => 'nullable|url|max:255',
            'address'              => 'nullable|string|max:255',
            'business_type'        => 'nullable|string|max:100',
            'stars'                => 'nullable|integer|min:1|max:5',
            'vat_number'           => 'nullable|string|max:50',
            'cin_code'             => 'nullable|string|max:100',
            'contact_role'         => 'nullable|string|max:50',
            'email_status'         => 'nullable|string|max:30',
            'source_type'          => 'nullable|string|max:50',
            'source_url'           => 'nullable|url|max:500',
            'site_rating'          => 'nullable|string|max:50',
            'commercial_potential' => 'nullable|string|max:30',
            'seo_score'            => 'nullable|numeric|min:0|max:100',
            'last_verified_at'     => 'nullable|date',
            'notes'                => 'nullable|string',
            'category_ids'         => 'nullable|array',
            'category_ids.*'       => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
        ]);

        $email = trim((string) $data['email']);

        $exists = $list->contacts()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esiste già un contatto con questa email in questa lista.');
        }

        $contact = $list->contacts()->create(array_merge([
            'contact_type' => 'manual',
            'contact_id'   => null,
        ], $this->contactData($data, true)));

        $contact->categories()->sync($data['category_ids'] ?? []);

        return back()->with('success', 'Contatto aggiunto alla lista.');
    }

    public function destroyContact(Request $request, EmailList $list, EmailListContact $contact)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        if ((int) $contact->list_id !== (int) $list->id) {
            abort(404);
        }

        $contact->delete();

        return back()->with('success', 'Contatto rimosso dalla lista.');
    }

    public function editContact(Request $request, EmailList $list, EmailListContact $contact)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        if ((int) $contact->list_id !== (int) $list->id) {
            abort(404);
        }

        $categories = EmailListCategory::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedCategoryIds = $contact->categories()
            ->pluck('crm_email_list_categories.id')
            ->all();

        return view('crm::email_lists.contact_edit', compact(
            'list',
            'contact',
            'categories',
            'selectedCategoryIds'
        ));
    }

    public function updateContact(Request $request, EmailList $list, EmailListContact $contact)
    {
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        if ((int) $contact->list_id !== (int) $list->id) {
            abort(404);
        }

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('crm_email_list_contacts', 'email')
                    ->where(fn ($q) => $q->where('list_id', $list->id))
                    ->ignore($contact->id),
            ],
            'name'                 => 'nullable|string|max:255',
            'segment'              => 'nullable|string|max:190',
            'city'                 => 'nullable|string|max:190',
            'province'             => 'nullable|string|max:10',
            'region'               => 'nullable|string|max:190',
            'country'              => 'nullable|string|max:190',
            'postal_code'          => 'nullable|string|max:20',
            'phone'                => 'nullable|string|max:50',
            'whatsapp'             => 'nullable|string|max:50',
            'website_url'          => 'nullable|url|max:255',
            'contact_page_url'     => 'nullable|url|max:255',
            'address'              => 'nullable|string|max:255',
            'business_type'        => 'nullable|string|max:100',
            'stars'                => 'nullable|integer|min:1|max:5',
            'vat_number'           => 'nullable|string|max:50',
            'cin_code'             => 'nullable|string|max:100',
            'contact_role'         => 'nullable|string|max:50',
            'email_status'         => 'nullable|string|max:30',
            'source_type'          => 'nullable|string|max:50',
            'source_url'           => 'nullable|url|max:500',
            'site_rating'          => 'nullable|string|max:50',
            'commercial_potential' => 'nullable|string|max:30',
            'seo_score'            => 'nullable|numeric|min:0|max:100',
            'last_verified_at'     => 'nullable|date',
            'notes'                => 'nullable|string',
            'marketing_consense'   => 'nullable|boolean',
            'category_ids'         => 'nullable|array',
            'category_ids.*'       => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
        ]);

        $contact->update($this->contactData($data, $request->boolean('marketing_consense')));

        $contact->categories()->sync($data['category_ids'] ?? []);

        return redirect()
            ->route('admin.crm.email-lists.edit', $list)
            ->with('success', 'Contatto aggiornato.');
    }

    public function storeCategory(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('crm_email_list_categories', 'name')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
        ]);

        EmailListCategory::create([
            'client_id' => $clientId,
            'name'      => trim((string) $data['name']),
        ]);

        return back()->with('success', 'Categoria creata.');
    }

    public function destroyCategory(Request $request, EmailListCategory $category)
    {
        $clientId = $this->clientId($request);

        if ((int) $category->client_id !== (int) $clientId) {
            abort(404);
        }

        $category->delete();

        return back()->with('success', 'Categoria eliminata.');
    }
}
