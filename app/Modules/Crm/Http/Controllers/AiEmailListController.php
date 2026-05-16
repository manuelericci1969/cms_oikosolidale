<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\EmailList;
use App\Modules\Crm\Models\EmailListCategory;
use App\Modules\Crm\Models\EmailListContact;
use App\Modules\Crm\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AiEmailListController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function ensureAiKey(Request $request): void
    {
        $expectedKey = (string) config('services.ai_gateway.key', '');
        $providedKey = (string) $request->header('X-AI-KEY', '');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401));
        }
    }

    protected function ensureSameClient(EmailList $list, int $clientId): void
    {
        if ((int) $list->client_id !== (int) $clientId) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Lista non trovata.',
            ], 404));
        }
    }

    protected function ensureSameClientCategory(EmailListCategory $category, int $clientId): void
    {
        if ((int) $category->client_id !== (int) $clientId) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Categoria non trovata.',
            ], 404));
        }
    }

    protected function ensureContactInList(EmailList $list, EmailListContact $contact): void
    {
        if ((int) $contact->list_id !== (int) $list->id) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Contatto non trovato nella lista.',
            ], 404));
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

    protected function contactPayload(array $data, bool $defaultMarketingConsense = true): array
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

    protected function contactPayloadPartial(array $data, ?bool $defaultMarketingConsense = null): array
    {
        $payload = [];

        if (array_key_exists('email', $data)) {
            $payload['email'] = trim((string) $data['email']);
        }

        foreach ([
                     'name',
                     'segment',
                     'city',
                     'region',
                     'country',
                     'postal_code',
                     'phone',
                     'whatsapp',
                     'website_url',
                     'contact_page_url',
                     'address',
                     'business_type',
                     'vat_number',
                     'cin_code',
                     'contact_role',
                     'email_status',
                     'source_type',
                     'source_url',
                     'site_rating',
                     'commercial_potential',
                     'last_verified_at',
                     'notes',
                 ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $this->cleanNullableString($data[$field]);
            }
        }

        if (array_key_exists('province', $data)) {
            $payload['province'] = $this->cleanNullableString($data['province'], true);
        }

        if (array_key_exists('stars', $data)) {
            $payload['stars'] = $data['stars'] !== '' ? (int) $data['stars'] : null;
        }

        if (array_key_exists('seo_score', $data)) {
            $payload['seo_score'] = $data['seo_score'] !== '' ? (float) $data['seo_score'] : null;
        }

        if (array_key_exists('marketing_consense', $data)) {
            $payload['marketing_consense'] = (bool) $data['marketing_consense'];
        } elseif ($defaultMarketingConsense !== null) {
            $payload['marketing_consense'] = $defaultMarketingConsense;
        }

        return $payload;
    }

    protected function formatContact(EmailListContact $contact): array
    {
        return [
            'id'                   => $contact->id,
            'list_id'              => $contact->list_id,
            'contact_type'         => $contact->contact_type,
            'contact_id'           => $contact->contact_id,
            'email'                => $contact->email,
            'name'                 => $contact->name,
            'segment'              => $contact->segment,
            'city'                 => $contact->city,
            'province'             => $contact->province,
            'region'               => $contact->region,
            'country'              => $contact->country,
            'postal_code'          => $contact->postal_code,
            'phone'                => $contact->phone,
            'whatsapp'             => $contact->whatsapp,
            'website_url'          => $contact->website_url,
            'contact_page_url'     => $contact->contact_page_url,
            'address'              => $contact->address,
            'business_type'        => $contact->business_type,
            'stars'                => $contact->stars,
            'vat_number'           => $contact->vat_number,
            'cin_code'             => $contact->cin_code,
            'contact_role'         => $contact->contact_role,
            'email_status'         => $contact->email_status,
            'source_type'          => $contact->source_type,
            'source_url'           => $contact->source_url,
            'site_rating'          => $contact->site_rating,
            'commercial_potential' => $contact->commercial_potential,
            'seo_score'            => $contact->seo_score,
            'last_verified_at'     => optional($contact->last_verified_at)?->format('Y-m-d H:i:s'),
            'notes'                => $contact->notes,
            'marketing_consense'   => (bool) $contact->marketing_consense,
            'unsubscribed_at'      => optional($contact->unsubscribed_at)?->format('Y-m-d H:i:s'),
            'categories'           => $contact->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ])->values(),
            'created_at'           => optional($contact->created_at)?->format('Y-m-d H:i:s'),
            'updated_at'           => optional($contact->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }

    protected function formatList(EmailList $list): array
    {
        return [
            'id'             => $list->id,
            'client_id'      => $list->client_id,
            'name'           => $list->name,
            'description'    => $list->description,
            'contacts_count' => $list->contacts_count ?? null,
            'created_at'     => optional($list->created_at)?->format('Y-m-d H:i:s'),
            'updated_at'     => optional($list->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'q'        => 'nullable|string|max:190',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = EmailList::query()
            ->where('client_id', $clientId)
            ->withCount('contacts');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $lists = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'ok' => true,
            'type' => 'email_lists',
            'items' => collect($lists->items())->map(fn ($list) => $this->formatList($list))->values(),
            'pagination' => [
                'current_page' => $lists->currentPage(),
                'last_page'    => $lists->lastPage(),
                'per_page'     => $lists->perPage(),
                'total'        => $lists->total(),
            ],
        ]);
    }

    public function show(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $list->loadCount('contacts');

        return response()->json([
            'ok'   => true,
            'type' => 'email_list',
            'item' => $this->formatList($list),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);
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

        $list->loadCount('contacts');

        return response()->json([
            'ok'      => true,
            'message' => 'Lista creata correttamente.',
            'item'    => $this->formatList($list),
        ], 201);
    }

    public function update(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
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

        $list->loadCount('contacts');

        return response()->json([
            'ok'      => true,
            'message' => 'Lista aggiornata correttamente.',
            'item'    => $this->formatList($list),
        ]);
    }

    public function destroy(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $list->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Lista eliminata correttamente.',
        ]);
    }

    public function contacts(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $validated = $request->validate([
            'q'                    => 'nullable|string|max:190',
            'segment'              => 'nullable|string|max:190',
            'city'                 => 'nullable|string|max:190',
            'province'             => 'nullable|string|max:10',
            'region'               => 'nullable|string|max:190',
            'country'              => 'nullable|string|max:190',
            'postal_code'          => 'nullable|string|max:20',
            'business_type'        => 'nullable|string|max:100',
            'contact_role'         => 'nullable|string|max:50',
            'email_status'         => 'nullable|string|max:30',
            'source_type'          => 'nullable|string|max:50',
            'site_rating'          => 'nullable|string|max:50',
            'commercial_potential' => 'nullable|string|max:30',
            'contact_type'         => 'nullable|string|in:manual,csv,lead,customer',
            'marketing_consense'   => 'nullable|boolean',
            'category_id'          => [
                'nullable',
                'integer',
                Rule::exists('crm_email_list_categories', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
            'unsubscribed' => 'nullable|boolean',
            'page'         => 'nullable|integer|min:1',
            'per_page'     => 'nullable|integer|min:1|max:200',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 50);

        $query = $list->contacts()
            ->with('categories:id,name')
            ->orderBy('email');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('segment', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('province', 'like', "%{$q}%")
                    ->orWhere('region', 'like', "%{$q}%")
                    ->orWhere('country', 'like', "%{$q}%")
                    ->orWhere('postal_code', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('whatsapp', 'like', "%{$q}%")
                    ->orWhere('website_url', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('business_type', 'like', "%{$q}%")
                    ->orWhere('vat_number', 'like', "%{$q}%")
                    ->orWhere('cin_code', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
        }

        foreach ([
                     'segment',
                     'city',
                     'region',
                     'country',
                     'postal_code',
                     'business_type',
                     'contact_role',
                     'email_status',
                     'source_type',
                     'site_rating',
                     'commercial_potential',
                     'contact_type',
                 ] as $field) {
            if (!empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        if (!empty($validated['province'])) {
            $query->where('province', mb_strtoupper(trim((string) $validated['province'])));
        }

        if ($request->has('marketing_consense')) {
            $query->where('marketing_consense', $request->boolean('marketing_consense'));
        }

        if ($request->has('unsubscribed')) {
            $request->boolean('unsubscribed')
                ? $query->whereNotNull('unsubscribed_at')
                : $query->whereNull('unsubscribed_at');
        }

        if (!empty($validated['category_id'])) {
            $query->whereHas('categories', function ($q) use ($validated) {
                $q->where('crm_email_list_categories.id', $validated['category_id']);
            });
        }

        $contacts = $query->paginate($perPage);

        return response()->json([
            'ok'   => true,
            'type' => 'email_list_contacts',
            'list' => $this->formatList($list->loadCount('contacts')),
            'items' => collect($contacts->items())->map(fn ($contact) => $this->formatContact($contact))->values(),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'last_page'    => $contacts->lastPage(),
                'per_page'     => $contacts->perPage(),
                'total'        => $contacts->total(),
            ],
        ]);
    }

    public function showContact(Request $request, EmailList $list, EmailListContact $contact): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);
        $this->ensureContactInList($list, $contact);

        $contact->load('categories:id,name');

        return response()->json([
            'ok'   => true,
            'type' => 'email_list_contact',
            'item' => $this->formatContact($contact),
        ]);
    }

    public function storeContact(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
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
            'marketing_consense'   => 'nullable|boolean',
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
            return response()->json([
                'ok'      => false,
                'message' => 'Esiste già un contatto con questa email in questa lista.',
            ], 422);
        }

        $contact = $list->contacts()->create(array_merge(
            ['contact_type' => 'manual', 'contact_id' => null],
            $this->contactPayload($data, true)
        ));

        $contact->categories()->sync($data['category_ids'] ?? []);
        $contact->load('categories:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Contatto creato correttamente.',
            'item'    => $this->formatContact($contact),
        ], 201);
    }

    public function updateContact(Request $request, EmailList $list, EmailListContact $contact): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);
        $this->ensureContactInList($list, $contact);

        $data = $request->validate([
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:190',
                Rule::unique('crm_email_list_contacts', 'email')
                    ->where(fn ($q) => $q->where('list_id', $list->id))
                    ->ignore($contact->id),
            ],
            'name'                 => 'sometimes|nullable|string|max:255',
            'segment'              => 'sometimes|nullable|string|max:190',
            'city'                 => 'sometimes|nullable|string|max:190',
            'province'             => 'sometimes|nullable|string|max:10',
            'region'               => 'sometimes|nullable|string|max:190',
            'country'              => 'sometimes|nullable|string|max:190',
            'postal_code'          => 'sometimes|nullable|string|max:20',
            'phone'                => 'sometimes|nullable|string|max:50',
            'whatsapp'             => 'sometimes|nullable|string|max:50',
            'website_url'          => 'sometimes|nullable|url|max:255',
            'contact_page_url'     => 'sometimes|nullable|url|max:255',
            'address'              => 'sometimes|nullable|string|max:255',
            'business_type'        => 'sometimes|nullable|string|max:100',
            'stars'                => 'sometimes|nullable|integer|min:1|max:5',
            'vat_number'           => 'sometimes|nullable|string|max:50',
            'cin_code'             => 'sometimes|nullable|string|max:100',
            'contact_role'         => 'sometimes|nullable|string|max:50',
            'email_status'         => 'sometimes|nullable|string|max:30',
            'source_type'          => 'sometimes|nullable|string|max:50',
            'source_url'           => 'sometimes|nullable|url|max:500',
            'site_rating'          => 'sometimes|nullable|string|max:50',
            'commercial_potential' => 'sometimes|nullable|string|max:30',
            'seo_score'            => 'sometimes|nullable|numeric|min:0|max:100',
            'last_verified_at'     => 'sometimes|nullable|date',
            'notes'                => 'sometimes|nullable|string',
            'marketing_consense'   => 'sometimes|nullable|boolean',
            'category_ids'         => 'sometimes|nullable|array',
            'category_ids.*'       => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
        ]);

        $payload = $this->contactPayloadPartial($data, (bool) $contact->marketing_consense);

        if (!empty($payload)) {
            $contact->update($payload);
        }

        if (array_key_exists('category_ids', $data)) {
            $contact->categories()->sync($data['category_ids'] ?? []);
        }

        $contact->load('categories:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Contatto aggiornato correttamente.',
            'item'    => $this->formatContact($contact),
        ]);
    }

    public function destroyContact(Request $request, EmailList $list, EmailListContact $contact): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);
        $this->ensureContactInList($list, $contact);

        $contact->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Contatto eliminato correttamente.',
        ]);
    }

    public function bulkDeleteContacts(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'contact_ids'   => 'required|array|min:1',
            'contact_ids.*' => 'integer',
        ]);

        $deleted = $list->contacts()
            ->whereIn('id', $data['contact_ids'])
            ->delete();

        return response()->json([
            'ok'            => true,
            'message'       => 'Eliminazione massiva completata.',
            'deleted_count' => $deleted,
        ]);
    }

    public function bulkUpsertContacts(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'items' => 'required|array|min:1|max:500',
            'items.*.email'                => 'required|email|max:190',
            'items.*.name'                 => 'nullable|string|max:255',
            'items.*.segment'              => 'nullable|string|max:190',
            'items.*.city'                 => 'nullable|string|max:190',
            'items.*.province'             => 'nullable|string|max:10',
            'items.*.region'               => 'nullable|string|max:190',
            'items.*.country'              => 'nullable|string|max:190',
            'items.*.postal_code'          => 'nullable|string|max:20',
            'items.*.phone'                => 'nullable|string|max:50',
            'items.*.whatsapp'             => 'nullable|string|max:50',
            'items.*.website_url'          => 'nullable|url|max:255',
            'items.*.contact_page_url'     => 'nullable|url|max:255',
            'items.*.address'              => 'nullable|string|max:255',
            'items.*.business_type'        => 'nullable|string|max:100',
            'items.*.stars'                => 'nullable|integer|min:1|max:5',
            'items.*.vat_number'           => 'nullable|string|max:50',
            'items.*.cin_code'             => 'nullable|string|max:100',
            'items.*.contact_role'         => 'nullable|string|max:50',
            'items.*.email_status'         => 'nullable|string|max:30',
            'items.*.source_type'          => 'nullable|string|max:50',
            'items.*.source_url'           => 'nullable|url|max:500',
            'items.*.site_rating'          => 'nullable|string|max:50',
            'items.*.commercial_potential' => 'nullable|string|max:30',
            'items.*.seo_score'            => 'nullable|numeric|min:0|max:100',
            'items.*.last_verified_at'     => 'nullable|date',
            'items.*.notes'                => 'nullable|string',
            'items.*.marketing_consense'   => 'nullable|boolean',
            'items.*.category_ids'         => 'nullable|array',
            'items.*.category_ids.*'       => [
                'integer',
                Rule::exists('crm_email_list_categories', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)),
            ],
        ]);

        $created = 0;
        $updated = 0;
        $results = [];

        DB::transaction(function () use ($data, $list, &$created, &$updated, &$results) {
            foreach ($data['items'] as $row) {
                $email = trim((string) $row['email']);

                $contact = $list->contacts()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->first();

                if ($contact) {
                    $contact->update($this->contactPayload($row, (bool) $contact->marketing_consense));
                    $updated++;
                } else {
                    $contact = $list->contacts()->create(array_merge(
                        ['contact_type' => 'manual', 'contact_id' => null],
                        $this->contactPayload($row, true)
                    ));
                    $created++;
                }

                if (array_key_exists('category_ids', $row)) {
                    $contact->categories()->sync($row['category_ids'] ?? []);
                }

                $contact->load('categories:id,name');
                $results[] = $this->formatContact($contact);
            }
        });

        return response()->json([
            'ok'            => true,
            'message'       => 'Bulk upsert completato.',
            'created_count' => $created,
            'updated_count' => $updated,
            'items'         => $results,
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'q' => 'nullable|string|max:190',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));

        $query = EmailListCategory::query()
            ->where('client_id', $clientId);

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->orderBy('name')->get()->map(fn ($cat) => [
            'id'   => $cat->id,
            'name' => $cat->name,
        ])->values();

        return response()->json([
            'ok'    => true,
            'type'  => 'email_categories',
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);
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

        $category = EmailListCategory::create([
            'client_id' => $clientId,
            'name'      => trim((string) $data['name']),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Categoria creata correttamente.',
            'item'    => [
                'id'   => $category->id,
                'name' => $category->name,
            ],
        ], 201);
    }

    public function updateCategory(Request $request, EmailListCategory $category): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClientCategory($category, $clientId);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('crm_email_list_categories', 'name')
                    ->where(fn ($q) => $q->where('client_id', $clientId))
                    ->ignore($category->id),
            ],
        ]);

        $category->update([
            'name' => trim((string) $data['name']),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Categoria aggiornata correttamente.',
            'item'    => [
                'id'   => $category->id,
                'name' => $category->name,
            ],
        ]);
    }

    public function destroyCategory(Request $request, EmailListCategory $category): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClientCategory($category, $clientId);

        $category->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Categoria eliminata correttamente.',
        ]);
    }

    public function unsubscribeContact(Request $request, EmailList $list, EmailListContact $contact): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);
        $this->ensureContactInList($list, $contact);

        $contact->update([
            'marketing_consense' => false,
            'unsubscribed_at'    => now(),
        ]);

        $contact->load('categories:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Contatto disiscritto correttamente.',
            'item'    => $this->formatContact($contact),
        ]);
    }

    public function resubscribeContact(Request $request, EmailList $list, EmailListContact $contact): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);
        $this->ensureContactInList($list, $contact);

        $contact->update([
            'marketing_consense' => true,
            'unsubscribed_at'    => null,
        ]);

        $contact->load('categories:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Contatto reiscritto correttamente.',
            'item'    => $this->formatContact($contact),
        ]);
    }

    public function syncFromCrm(Request $request, EmailList $list): JsonResponse
    {
        $this->ensureAiKey($request);
        $clientId = $this->clientId($request);
        $this->ensureSameClient($list, $clientId);

        $data = $request->validate([
            'include_leads'     => 'nullable|boolean',
            'include_customers' => 'nullable|boolean',
            'lead_status'       => 'nullable|array',
            'lead_status.*'     => 'string',
            'owner_id'          => 'nullable|integer|exists:users,id',
        ]);

        $includeLeads     = (bool) ($data['include_leads'] ?? false);
        $includeCustomers = (bool) ($data['include_customers'] ?? false);
        $ownerId          = $data['owner_id'] ?? null;

        $collection = collect();

        if ($includeLeads) {
            $leadQuery = Lead::query()
                ->where('client_id', $clientId)
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
            $customerQuery = Customer::query()
                ->where('client_id', $clientId)
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

        $unique = $collection->unique(fn ($item) => mb_strtolower(trim((string) ($item['email'] ?? ''))));

        $existingCategoriesByEmail = $list->contacts()
            ->whereIn('contact_type', ['lead', 'customer'])
            ->with('categories:id')
            ->get()
            ->mapWithKeys(function (EmailListContact $contact) {
                return [
                    mb_strtolower(trim((string) $contact->email)) => $contact->categories->pluck('id')->all(),
                ];
            });

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

        $list->loadCount('contacts');

        return response()->json([
            'ok'      => true,
            'message' => 'Sincronizzazione CRM completata.',
            'list'    => $this->formatList($list),
        ]);
    }
}
