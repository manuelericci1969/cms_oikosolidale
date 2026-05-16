<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Product;
use App\Modules\Crm\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Elenco servizi, con filtri base + TAB + (opzionale) prossimi alla scadenza.
     */
    public function index(Request $request)
    {
        $filters = [
            'customer_id' => $request->input('customer_id'),
            'product_id'  => $request->input('product_id'),
            'status'      => $request->input('status'),
        ];

        // TAB: active | suspended | non_active
        $allowedTabs = ['active', 'suspended', 'non_active'];
        $tab = $request->input('tab', 'active');
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'active';
        }

        // Toggle: mostra solo scadenze vicine (DISATTIVATO di default)
        $onlyExpiring = $request->boolean('only_expiring');

        // Giorni finestra scadenza (±N giorni) usati SOLO se only_expiring=1
        $expiringDays = (int) $request->input('expiring_days', 15);
        if ($expiringDays < 0) {
            $expiringDays = 15;
        }

        $rangeStart = now()->startOfDay()->subDays($expiringDays);
        $rangeEnd   = now()->endOfDay()->addDays($expiringDays);

        // Funzione per riapplicare i filtri comuni (customer/prodotto + opzionale scadenza)
        $applyCommonFilters = function ($q) use ($filters, $onlyExpiring, $rangeStart, $rangeEnd) {
            if ($filters['customer_id']) {
                $q->where('customer_id', $filters['customer_id']);
            }
            if ($filters['product_id']) {
                $q->where('product_id', $filters['product_id']);
            }

            // SOLO se attivo: mostro solo quelli con expires_at nella finestra
            if ($onlyExpiring) {
                $q->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [$rangeStart, $rangeEnd]);
            }
        };

        // Base query per conteggi tab (senza status dropdown, così i tab restano utili)
        $countBase = Service::query();
        $applyCommonFilters($countBase);

        // Conteggi per tab: numero CLIENTI distinti
        $tabCounts = [];
        foreach ($allowedTabs as $t) {
            $q = (clone $countBase);

            if ($t === 'active') {
                $q->where('status', 'active');
            } elseif ($t === 'suspended') {
                $q->where('status', 'suspended');
            } else { // non_active
                $q->whereNotIn('status', ['active', 'suspended']);
            }

            $tabCounts[$t] = $q->distinct()->count('customer_id');
        }

        // Query finale elenco
        $query = Service::with(['customer', 'product']);
        $applyCommonFilters($query);

        // Se l’utente seleziona lo status dal dropdown, quello ha precedenza sul tab
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        } else {
            if ($tab === 'active') {
                $query->where('status', 'active');
            } elseif ($tab === 'suspended') {
                $query->where('status', 'suspended');
            } else { // non_active
                $query->whereNotIn('status', ['active', 'suspended']);
            }
        }

        // Ordinamento:
        // - se mostro tutto: prima quelli con scadenza (ordinati), poi null
        // - se onlyExpiring: tanto hanno expires_at, quindi ordina per data
        if ($onlyExpiring) {
            $query->orderBy('expires_at', 'asc');
        } else {
            $query->orderByRaw('ISNULL(expires_at), expires_at ASC');
        }

        $services      = $query->paginate(25)->withQueryString();
        $customers     = Customer::orderBy('name')->get(['id', 'name']);
        $products      = Product::orderBy('name')->get(['id', 'name']);
        $statusOptions = Service::STATUS_OPTIONS;

        return view('crm::services.index', compact(
            'services',
            'customers',
            'products',
            'filters',
            'statusOptions',
            'tab',
            'tabCounts',
            'expiringDays',
            'onlyExpiring'
        ));
    }

    public function create(Request $request)
    {
        $service           = new Service();
        $customer          = null;
        $customerServices  = collect();
        $reminderLogs      = collect(); // nessun log su nuovo servizio

        if ($request->filled('customer_id')) {
            $customer = Customer::with(['services.product'])->findOrFail($request->customer_id);
            $customerServices = $customer->services;
            $service->customer_id = $customer->id; // pre-compila
        }

        $products  = Product::orderBy('name')->get();
        $customers = $customer
            ? collect()     // cliente fisso: niente select
            : Customer::orderBy('name')->get();

        return view('crm::services.form', compact(
            'service',
            'customer',
            'customerServices',
            'products',
            'customers',
            'reminderLogs'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Service::create($data);

        return redirect()
            ->route('admin.crm.services.index')
            ->with('success', 'Servizio creato correttamente.');
    }

    public function edit(Service $service)
    {
        $service->load(['customer', 'product', 'reminderLogs']);

        $customer = $service->customer;
        $customerServices = $customer
            ? $customer->services()->with('product')->get()
            : collect();

        $reminderLogs = $service->reminderLogs
            ->sortByDesc('created_at');

        $products  = Product::orderBy('name')->get();
        $customers = $customer
            ? collect()
            : Customer::orderBy('name')->get();

        return view('crm::services.form', compact(
            'service',
            'customer',
            'customerServices',
            'products',
            'customers',
            'reminderLogs'
        ));
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validateData($request, $service);

        $service->update($data);

        return redirect()
            ->route('admin.crm.services.index')
            ->with('success', 'Servizio aggiornato correttamente.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()
            ->route('admin.crm.services.index')
            ->with('success', 'Servizio eliminato.');
    }

    protected function validateData(Request $request, ?Service $service = null): array
    {
        $service ??= new Service();

        $rules = [
            'customer_id'       => ['required', 'integer', 'exists:crm_customers,id'],
            'product_id'        => ['nullable', 'integer', 'exists:crm_products,id'],

            'name'              => ['required', 'string', 'max:190'],
            'type'              => ['nullable', 'string', 'max:50'],

            'status'            => ['required', 'string', 'in:' . implode(',', array_keys(Service::STATUS_OPTIONS))],

            'provider_name'     => ['nullable', 'string', 'max:190'],
            'provider_website'  => ['nullable', 'url', 'max:255'],
            'panel_url'         => ['nullable', 'url', 'max:255'],
            'panel_username'    => ['nullable', 'string', 'max:190'],
            'panel_password'    => ['nullable', 'string', 'max:190'],

            'activated_at'      => ['nullable', 'date'],
            'expires_at'        => ['nullable', 'date'],

            'auto_renew'               => ['sometimes', 'boolean'],
            'renew_price_vat_included' => ['sometimes', 'boolean'],

            'notes'             => ['nullable', 'string'],

            'renewal_price'      => ['nullable', 'numeric', 'min:0'],

            'renew_price'              => ['nullable', 'numeric', 'min:0'],
            'renew_price_vat_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],

            'renewal_vat_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'renewal_vat_mode'  => ['nullable', 'string', 'in:week,month,year'],

            'send_reminder'         => ['sometimes', 'boolean'],
            'reminder_days_before'  => ['nullable', 'integer', 'min:0'],
            'reminder_custom_text'  => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        $data['auto_renew']               = $request->boolean('auto_renew');
        $data['renew_price_vat_included'] = $request->boolean('renew_price_vat_included');
        $data['send_reminder']            = $request->boolean('send_reminder');

        $reminderDays = $data['reminder_days_before']
            ?? $service->reminder_days_before
            ?? 15;

        $data['reminder_days_before'] = (int) $reminderDays;

        if (!array_key_exists('renew_price_vat_rate', $data) || $data['renew_price_vat_rate'] === null) {
            $data['renew_price_vat_rate'] = 22;
        }

        if ($service->exists && (($data['panel_password'] ?? '') === '')) {
            unset($data['panel_password']);
        }

        return $data;
    }
}
