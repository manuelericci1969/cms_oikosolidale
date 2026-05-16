@extends('admin.layout')

@section('title', 'Servizi clienti')

@section('content')
    @php
        use App\Models\Setting;

        $activeTab    = $tab ?? request('tab', 'active');
        $expiringDays = $expiringDays ?? (int) request('expiring_days', 15);
        $onlyExpiring = $onlyExpiring ?? request()->boolean('only_expiring');

        $tabBaseQuery = request()->except('page', 'tab', 'status');

        $companyName     = Setting::get('company.name');
        $companyVat      = Setting::get('company.vat');
        $companyAddress  = Setting::get('company.address');
        $companyCity     = Setting::get('company.city');
        $companyZip      = Setting::get('company.zip');
        $companyProvince = Setting::get('company.province');
        $companyEmail    = Setting::get('company.email');
        $companyPhone    = Setting::get('company.phone');

        $servicesCollection = $services instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $services->getCollection()
            : collect($services);

        $groupedByCustomer = $servicesCollection->groupBy(function ($serviceItem) {
            return optional($serviceItem->customer)->id ?: 0;
        });

        $periodLabelMap = [
            'week'  => 'settimana',
            'month' => 'mese',
            'year'  => 'anno',
        ];

        $today = now()->startOfDay();

        $pageTotals = [
            'all'      => 0,
            'active'   => 0,
            'expiring' => 0,
            'expired'  => 0,
        ];

        foreach ($servicesCollection as $s) {
            $price = (float) ($s->renew_price_gross ?? 0);
            $pageTotals['all'] += $price;

            $days = null;
            if ($s->expires_at) {
                $days = $today->diffInDays($s->expires_at->copy()->startOfDay(), false);
            }

            if (($s->status ?? '') === 'expired' || ($days !== null && $days < 0)) {
                $pageTotals['expired'] += $price;
            } elseif ($days !== null && $days >= 0 && $days <= $expiringDays) {
                $pageTotals['expiring'] += $price;
            } else {
                $pageTotals['active'] += $price;
            }
        }
    @endphp

    <style>
        .cust-pill { font-weight: 600; }
        .service-card { transition: transform .08s ease, box-shadow .08s ease; }
        .service-card:hover { transform: translateY(-1px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.06); }
        .muted-mini { font-size: .82rem; color: #6c757d; }
        .section-title { letter-spacing: .06em; font-size: .78rem; text-transform: uppercase; color: #6c757d; }
        .chip { border: 1px solid rgba(0,0,0,.08); background: rgba(255,255,255,.7); }
        .stat-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: .75rem; }
        .stat-value { font-weight: 700; font-size: 1.1rem; }

        .reminder-expiry-box {
            border-radius: .75rem;
            border: 1px solid rgba(0,0,0,.08);
            padding: .9rem 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        .reminder-expiry-box.is-success {
            background: #ecfdf3;
            border-color: rgba(25, 135, 84, .2);
        }
        .reminder-expiry-box.is-warning {
            background: #fff8e1;
            border-color: rgba(255, 193, 7, .35);
        }
        .reminder-expiry-box.is-danger {
            background: #fff1f2;
            border-color: rgba(220, 53, 69, .25);
        }
        .reminder-expiry-kpi {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .reminder-expiry-label {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6c757d;
        }
        .reminder-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }
        @media (max-width: 767px) {
            .reminder-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Servizi clienti</h1>

        <a href="{{ route('admin.crm.services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuovo servizio
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- FILTRI --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.crm.services.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <div class="col-md-3">
                    <label class="form-label mb-1">Cliente</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Tutti i clienti</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected(($filters['customer_id'] ?? null) == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Prodotto</label>
                    <select name="product_id" class="form-select">
                        <option value="">Tutti i prodotti</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                @selected(($filters['product_id'] ?? null) == $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Stato servizio</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti (usa i tab)</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}"
                                @selected(($filters['status'] ?? null) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Scadenza vicina (± giorni)</label>
                    <input type="number"
                           name="expiring_days"
                           class="form-control"
                           min="0"
                           value="{{ request('expiring_days', $expiringDays) }}">
                </div>

                <div class="col-md-1">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input"
                               type="checkbox"
                               id="only_expiring"
                               name="only_expiring"
                               value="1"
                            @checked($onlyExpiring)>
                        <label class="form-check-label" for="only_expiring">
                            Solo
                        </label>
                    </div>
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2 mt-2">
                    <button class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> Applica
                    </button>

                    <a href="{{ route('admin.crm.services.index', ['tab' => $activeTab]) }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </form>

            @if($onlyExpiring)
                <div class="alert alert-info mt-3 mb-0">
                    Stai vedendo <strong>solo</strong> i servizi con scadenza entro <strong>± {{ $expiringDays }} giorni</strong>.
                </div>
            @endif
        </div>
    </div>

    {{-- TABS --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            @php
                $tabs = [
                    'active' => [
                        'label' => 'Attivi',
                        'icon'  => 'bi-check-circle',
                        'badge' => 'bg-success',
                    ],
                    'suspended' => [
                        'label' => 'Sospesi',
                        'icon'  => 'bi-pause-circle',
                        'badge' => 'bg-warning text-dark',
                    ],
                    'non_active' => [
                        'label' => 'Non attivi',
                        'icon'  => 'bi-slash-circle',
                        'badge' => 'bg-secondary',
                    ],
                ];
            @endphp

            <ul class="nav nav-tabs">
                @foreach($tabs as $key => $tabItem)
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === $key) active @endif"
                           href="{{ route('admin.crm.services.index', array_merge($tabBaseQuery, ['tab' => $key])) }}">
                            <i class="bi {{ $tabItem['icon'] }}"></i>
                            {{ $tabItem['label'] }}
                            <span class="badge {{ $tabItem['badge'] }} ms-2">
                                {{ $tabCounts[$key] ?? 0 }}
                            </span>
                        </a>
                    </li>
                @endforeach

                <li class="ms-auto align-self-center small text-muted px-2">
                    @if($onlyExpiring)
                        Scadenze vicine: ± {{ $expiringDays }} giorni
                    @else
                        Elenco completo
                    @endif
                </li>
            </ul>
        </div>
    </div>

    {{-- RIEPILOGO TOTALI --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="stat-card p-3">
                <div class="muted-mini"><i class="bi bi-cash-coin me-1"></i> Totale rinnovi (pagina)</div>
                <div class="stat-value">{{ number_format($pageTotals['all'], 2, ',', '.') }} €</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3">
                <div class="muted-mini"><i class="bi bi-check-circle me-1"></i> Attivi</div>
                <div class="stat-value">{{ number_format($pageTotals['active'], 2, ',', '.') }} €</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3">
                <div class="muted-mini"><i class="bi bi-exclamation-triangle me-1"></i> In scadenza</div>
                <div class="stat-value">{{ number_format($pageTotals['expiring'], 2, ',', '.') }} €</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3">
                <div class="muted-mini"><i class="bi bi-x-circle me-1"></i> Scaduti</div>
                <div class="stat-value">{{ number_format($pageTotals['expired'], 2, ',', '.') }} €</div>
            </div>
        </div>
    </div>

    @if($groupedByCustomer->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Nessun servizio trovato con i filtri selezionati.
            </div>
        </div>
    @else
        <div class="accordion" id="customersAccordion">
            @foreach($groupedByCustomer as $customerId => $customerServices)
                @php
                    $firstService = $customerServices->first();
                    $customer = optional($firstService->customer);
                    $accId = 'cust-'.$customerId.'-'.$loop->index;

                    $byState = [
                        'expiring' => collect(),
                        'expired'  => collect(),
                        'active'   => collect(),
                    ];

                    foreach ($customerServices as $serviceItem) {
                        $expiresAt = $serviceItem->expires_at;
                        $days = null;

                        if ($expiresAt) {
                            $days = $today->diffInDays($expiresAt->copy()->startOfDay(), false);
                        }

                        if (($serviceItem->status ?? '') === 'expired' || ($days !== null && $days < 0)) {
                            $byState['expired']->push($serviceItem);
                        } elseif ($days !== null && $days >= 0 && $days <= $expiringDays) {
                            $byState['expiring']->push($serviceItem);
                        } else {
                            $byState['active']->push($serviceItem);
                        }
                    }

                    $byState['expiring'] = $byState['expiring']->sortBy('expires_at');
                    $byState['expired']  = $byState['expired']->sortByDesc('expires_at');
                    $byState['active']   = $byState['active']->sortBy(function ($item) {
                        return $item->expires_at ? $item->expires_at->timestamp : PHP_INT_MAX;
                    });

                    $countActive   = $byState['active']->count();
                    $countExpiring = $byState['expiring']->count();
                    $countExpired  = $byState['expired']->count();

                    $sumActive   = (float) $byState['active']->sum(fn($item) => $item->renew_price_gross ?? 0);
                    $sumExpiring = (float) $byState['expiring']->sum(fn($item) => $item->renew_price_gross ?? 0);
                    $sumExpired  = (float) $byState['expired']->sum(fn($item) => $item->renew_price_gross ?? 0);
                    $customerTotal = $sumActive + $sumExpiring + $sumExpired;

                    $nextExpiry = $customerServices
                        ->filter(fn($item) => $item->expires_at)
                        ->sortBy('expires_at')
                        ->first();

                    $nextExpiryText = null;
                    if ($nextExpiry && $nextExpiry->expires_at) {
                        $d = $today->diffInDays($nextExpiry->expires_at->copy()->startOfDay(), false);
                        $nextExpiryText = $nextExpiry->expires_at->format('d/m/Y') . ($d >= 0 ? " (tra $d gg)" : " (da " . abs($d) . " gg)");
                    }

                    $sections = [
                        'expiring' => [
                            'title' => 'In scadenza',
                            'icon'  => 'bi-exclamation-triangle',
                            'badge' => 'bg-warning text-dark',
                            'sum'   => $sumExpiring,
                        ],
                        'expired' => [
                            'title' => 'Scaduti',
                            'icon'  => 'bi-x-circle',
                            'badge' => 'bg-danger',
                            'sum'   => $sumExpired,
                        ],
                        'active' => [
                            'title' => 'Attivi',
                            'icon'  => 'bi-check-circle',
                            'badge' => 'bg-success',
                            'sum'   => $sumActive,
                        ],
                    ];
                @endphp

                <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                    <h2 class="accordion-header" id="heading-{{ $accId }}">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $accId }}"
                                aria-expanded="false"
                                aria-controls="collapse-{{ $accId }}">
                            <div class="w-100 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">
                                        {{ $customer->name ?: 'Senza cliente' }}
                                    </div>
                                    <div class="muted-mini">
                                        @if($customer && $customer->vat_number)
                                            P.IVA {{ $customer->vat_number }}
                                        @else
                                            &nbsp;
                                        @endif

                                        @if($nextExpiryText)
                                            <span class="ms-2">• Prossima scadenza: <span class="fw-semibold">{{ $nextExpiryText }}</span></span>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 ms-auto">
                                    <span class="badge bg-success cust-pill">
                                        <i class="bi bi-check-circle me-1"></i> Attivi: {{ $countActive }}
                                    </span>
                                    <span class="badge bg-warning text-dark cust-pill">
                                        <i class="bi bi-exclamation-triangle me-1"></i> In scadenza: {{ $countExpiring }}
                                    </span>
                                    <span class="badge bg-danger cust-pill">
                                        <i class="bi bi-x-circle me-1"></i> Scaduti: {{ $countExpired }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="collapse-{{ $accId }}" class="accordion-collapse collapse"
                         aria-labelledby="heading-{{ $accId }}"
                         data-bs-parent="#customersAccordion">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-light border text-dark cust-pill">
                                        <i class="bi bi-cash-coin me-1"></i> Totale: {{ number_format($customerTotal, 2, ',', '.') }} €
                                    </span>
                                    <span class="badge bg-success cust-pill">
                                        Attivi: {{ number_format($sumActive, 2, ',', '.') }} €
                                    </span>
                                    <span class="badge bg-warning text-dark cust-pill">
                                        In scadenza: {{ number_format($sumExpiring, 2, ',', '.') }} €
                                    </span>
                                    <span class="badge bg-danger cust-pill">
                                        Scaduti: {{ number_format($sumExpired, 2, ',', '.') }} €
                                    </span>
                                </div>

                                @if($customer && $customer->id)
                                    <a href="{{ route('admin.crm.services.create', ['customer_id' => $customer->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plus-lg"></i> Nuovo servizio
                                    </a>
                                @endif
                            </div>

                            @foreach($sections as $sectionKey => $section)
                                @if($byState[$sectionKey]->count() > 0)
                                    <div class="mb-4">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <div class="section-title">
                                                <i class="bi {{ $section['icon'] }} me-1"></i>
                                                {{ $section['title'] }}
                                            </div>

                                            <div class="d-flex gap-2">
                                                <span class="badge bg-light border text-dark">
                                                    Totale: {{ number_format((float) $section['sum'], 2, ',', '.') }} €
                                                </span>
                                                <span class="badge {{ $section['badge'] }}">
                                                    {{ $byState[$sectionKey]->count() }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="vstack gap-2">
                                            @foreach($byState[$sectionKey] as $service)
                                                @php
                                                    $expiresAt = $service->expires_at;
                                                    $days = null;

                                                    $expiryBadgeClass = 'bg-light text-muted border';
                                                    $expiryIcon = 'bi-calendar';

                                                    if ($expiresAt) {
                                                        $days = now()->startOfDay()->diffInDays(
                                                            $expiresAt->copy()->startOfDay(),
                                                            false
                                                        );

                                                        if ($days < 0) {
                                                            $expiryBadgeClass = 'bg-danger';
                                                            $expiryIcon = 'bi-calendar-x';
                                                        } elseif ($days === 0) {
                                                            $expiryBadgeClass = 'bg-warning text-dark';
                                                            $expiryIcon = 'bi-calendar2-event';
                                                        } elseif ($days <= $expiringDays) {
                                                            $expiryBadgeClass = 'bg-warning text-dark';
                                                            $expiryIcon = 'bi-calendar2-exclamation';
                                                        } else {
                                                            $expiryBadgeClass = 'bg-success';
                                                            $expiryIcon = 'bi-calendar2-check';
                                                        }
                                                    }

                                                    $status = $service->status ?? 'active';
                                                    $statusLabelMap = [
                                                        'active'    => 'Attivo',
                                                        'suspended' => 'Sospeso',
                                                        'expired'   => 'Scaduto',
                                                    ];
                                                    $statusClassMap = [
                                                        'active'    => 'bg-success',
                                                        'suspended' => 'bg-warning text-dark',
                                                        'expired'   => 'bg-secondary',
                                                    ];
                                                    $statusLabel = $statusLabelMap[$status] ?? ucfirst($status);
                                                    $statusClass = $statusClassMap[$status] ?? 'bg-light text-muted';

                                                    $priceGross = $service->renew_price_gross;
                                                    $period     = $service->renewal_vat_mode;
                                                    $priceLabel = null;

                                                    if (!is_null($priceGross)) {
                                                        $priceLabel = number_format($priceGross, 2, ',', '.') . ' €';
                                                        if ($period && isset($periodLabelMap[$period])) {
                                                            $priceLabel .= ' / ' . $periodLabelMap[$period];
                                                        }
                                                    }

                                                    $customerPhone = optional($service->customer)->whatsapp
                                                        ?? optional($service->customer)->mobile
                                                        ?? optional($service->customer)->phone
                                                        ?? null;
                                                @endphp

                                                <div class="service-card border rounded-3 p-3">
                                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">
                                                                {{ $service->name ?: optional($service->product)->name ?: '—' }}
                                                            </div>

                                                            <div class="muted-mini">
                                                                @if($service->product)
                                                                    <span class="me-2">
                                                                        <i class="bi bi-box-seam me-1"></i>
                                                                        {{ $service->product->name }}
                                                                    </span>
                                                                @endif

                                                                @if($service->provider_name)
                                                                    <span class="me-2">
                                                                        <i class="bi bi-building me-1"></i>
                                                                        {{ $service->provider_name }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            @if($service->panel_url)
                                                                <div class="mt-1">
                                                                    <i class="bi bi-link-45deg"></i>
                                                                    <a href="{{ $service->panel_url }}" target="_blank" rel="noopener">
                                                                        Pannello di controllo
                                                                    </a>
                                                                </div>
                                                            @endif

                                                            @if($service->notes)
                                                                <div class="mt-2 muted-mini">
                                                                    <i class="bi bi-sticky me-1"></i>
                                                                    {{ \Illuminate\Support\Str::limit($service->notes, 120) }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="text-end">
                                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                                @if($priceLabel)
                                                                    <span class="badge chip text-dark">
                                                                        <i class="bi bi-cash-coin me-1"></i> {{ $priceLabel }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-light text-muted border">
                                                                        Prezzo rinnovo N/D
                                                                    </span>
                                                                @endif

                                                                @if($expiresAt)
                                                                    <span class="badge {{ $expiryBadgeClass }}">
                                                                        <i class="bi {{ $expiryIcon }} me-1"></i>
                                                                        {{ $expiresAt->format('d/m/Y') }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-light text-muted border">
                                                                        <i class="bi bi-calendar-minus me-1"></i> Nessuna scadenza
                                                                    </span>
                                                                @endif

                                                                <span class="badge {{ $statusClass }}">
                                                                    <i class="bi bi-flag me-1"></i> {{ $statusLabel }}
                                                                </span>
                                                            </div>

                                                            @if($expiresAt)
                                                                <div class="muted-mini mt-1">
                                                                    @if($days > 0)
                                                                        Tra {{ $days }} giorni
                                                                    @elseif($days === 0)
                                                                        Scade oggi
                                                                    @else
                                                                        Scaduto da {{ abs($days) }} giorni
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            <div class="btn-group btn-group-sm mt-2">
                                                                <a href="{{ route('admin.crm.services.edit', $service) }}"
                                                                   class="btn btn-outline-primary" title="Modifica">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>

                                                                @if($customerPhone)
                                                                    <button type="button"
                                                                            class="btn btn-outline-success"
                                                                            title="Invia promemoria WhatsApp"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#serviceReminderModal"
                                                                            data-channel="whatsapp"
                                                                            data-service-id="{{ $service->id }}"
                                                                            data-service-name="{{ $service->name ?: optional($service->product)->name ?? '-' }}"
                                                                            data-product-name="{{ optional($service->product)->name ?? '' }}"
                                                                            data-ref-name="{{ $service->name }}"
                                                                            data-domain="{{ $service->provider_website }}"
                                                                            data-customer-name="{{ optional($service->customer)->name ?? '' }}"
                                                                            data-renew-price="{{ $service->renew_price_gross }}"
                                                                            data-renew-period="{{ $service->renewal_vat_mode }}"
                                                                            data-expires-at="{{ optional($service->expires_at)->format('Y-m-d') }}"
                                                                            data-send-url="{{ route('admin.crm.services.send-whatsapp-reminder', $service) }}">
                                                                        <i class="bi bi-whatsapp"></i>
                                                                    </button>
                                                                @endif

                                                                <button type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        title="Invia promemoria email"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#serviceReminderModal"
                                                                        data-channel="email"
                                                                        data-service-id="{{ $service->id }}"
                                                                        data-service-name="{{ $service->name ?: optional($service->product)->name ?? '-' }}"
                                                                        data-product-name="{{ optional($service->product)->name ?? '' }}"
                                                                        data-ref-name="{{ $service->name }}"
                                                                        data-domain="{{ $service->provider_website }}"
                                                                        data-customer-name="{{ optional($service->customer)->name ?? '' }}"
                                                                        data-renew-price="{{ $service->renew_price_gross }}"
                                                                        data-renew-period="{{ $service->renewal_vat_mode }}"
                                                                        data-expires-at="{{ optional($service->expires_at)->format('Y-m-d') }}"
                                                                        data-send-url="{{ route('admin.crm.services.send-reminder', $service) }}">
                                                                    <i class="bi bi-envelope"></i>
                                                                </button>

                                                                <form action="{{ route('admin.crm.services.destroy', $service) }}"
                                                                      method="POST"
                                                                      class="d-inline-block"
                                                                      onsubmit="return confirm('Eliminare questo servizio?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-outline-danger" title="Elimina">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($services instanceof \Illuminate\Pagination\LengthAwarePaginator && $services->hasPages())
            <div class="mt-3">
                {{ $services->links() }}
            </div>
        @endif
    @endif

    {{-- MODAL --}}
    <div class="modal fade" id="serviceReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="serviceReminderForm">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="serviceReminderModalTitle">Invia promemoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="reminder_service_id">

                        <p class="small text-muted mb-2">
                            Cliente: <span id="reminder_customer_name"></span><br>
                            Prodotto/servizio: <span id="reminder_product_name"></span><br>
                            Riferimento: <span id="reminder_ref_name"></span><br>
                            Dominio: <span id="reminder_domain"></span><br>
                            Prezzo rinnovo: <strong id="reminder_renew_price">Non disponibile</strong>
                        </p>

                        <div id="reminder_expiry_box" class="reminder-expiry-box d-none">
                            <div class="reminder-meta-grid">
                                <div>
                                    <div class="reminder-expiry-label">Data scadenza</div>
                                    <div class="fw-bold" id="reminder_expiry_date">—</div>
                                </div>
                                <div>
                                    <div class="reminder-expiry-label">Stato</div>
                                    <div class="fw-bold" id="reminder_expiry_status">—</div>
                                </div>
                                <div>
                                    <div class="reminder-expiry-label">Tempistica</div>
                                    <div class="reminder-expiry-kpi" id="reminder_expiry_days">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Testo messaggio</label>
                            <textarea name="message"
                                      id="reminder_message"
                                      rows="8"
                                      class="form-control"
                                      required></textarea>
                            <div class="form-text">
                                Puoi modificare liberamente il testo prima dell'invio.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Annulla
                        </button>

                        <button type="submit" class="btn btn-primary" id="serviceReminderSubmitBtn">
                            <i class="bi bi-send"></i> Invia promemoria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('serviceReminderModal');
            if (!modalEl) return;

            var reminderFooter = "Cordiali saluti,\n\n";
            reminderFooter += @json($companyName ?: 'Azienda') + "\n";

            @if($companyAddress || $companyCity || $companyZip || $companyProvince)
                reminderFooter += @json($companyAddress ?? '');
            @if($companyZip || $companyCity)
                reminderFooter += " – " + @json(trim(($companyZip ?? '') . ' ' . ($companyCity ?? '')));
            @endif
                @if($companyProvince)
                reminderFooter += " (" + @json($companyProvince) + ")";
            @endif
                reminderFooter += "\n";
            @endif

                @if($companyVat)
                reminderFooter += "P.IVA {{ $companyVat }}\n";
            @endif

                @if($companyPhone || $companyEmail)
                reminderFooter += "Tel: {{ $companyPhone ?? '' }}";
            @if($companyEmail)
                reminderFooter += " – Email: {{ $companyEmail }}";
            @endif
                reminderFooter += "\n";
            @endif

            function formatPeriodLabel(period) {
                if (period === 'week') return 'settimana';
                if (period === 'month') return 'mese';
                if (period === 'year') return 'anno';
                return '';
            }

            function formatMoney(value, period) {
                if (value === null || value === undefined || value === '') return '';

                var parsed = parseFloat(value);
                if (isNaN(parsed)) return '';

                var text = parsed.toLocaleString('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' €';

                var periodLabel = formatPeriodLabel(period);
                if (periodLabel) {
                    text += ' / ' + periodLabel;
                }

                return text;
            }

            function buildExpiryMeta(expiresAt) {
                if (!expiresAt) {
                    return {
                        visible: false,
                        dateText: 'Nessuna scadenza',
                        statusText: 'Non definita',
                        daysText: '—',
                        lineText: '',
                        boxClass: 'is-success'
                    };
                }

                var expiryDate = new Date(expiresAt + 'T00:00:00');
                if (isNaN(expiryDate.getTime())) {
                    return {
                        visible: false,
                        dateText: 'N/D',
                        statusText: 'N/D',
                        daysText: '—',
                        lineText: '',
                        boxClass: 'is-success'
                    };
                }

                var today = new Date();
                today.setHours(0, 0, 0, 0);

                var diffMs = expiryDate.getTime() - today.getTime();
                var days = Math.round(diffMs / 86400000);

                var dateText = expiryDate.toLocaleDateString('it-IT');
                var statusText = '';
                var daysText = '';
                var lineText = '';
                var boxClass = 'is-success';

                if (days < 0) {
                    statusText = 'Scaduto';
                    daysText = abs(days) + ' giorni';
                    lineText = 'Il servizio risulta scaduto in data ' + dateText + ', da ' + abs(days) + ' giorni.';
                    boxClass = 'is-danger';
                } else if (days === 0) {
                    statusText = 'Scade oggi';
                    daysText = 'Oggi';
                    lineText = 'Il servizio scade oggi (' + dateText + ').';
                    boxClass = 'is-warning';
                } else {
                    statusText = 'In scadenza';
                    daysText = 'Tra ' + days + ' giorni';
                    lineText = 'Il servizio scadrà il ' + dateText + ', tra ' + days + ' giorni.';
                    boxClass = days <= 15 ? 'is-warning' : 'is-success';
                }

                return {
                    visible: true,
                    dateText: dateText,
                    statusText: statusText,
                    daysText: daysText,
                    lineText: lineText,
                    boxClass: boxClass
                };

                function abs(n) {
                    return Math.abs(n);
                }
            }

            function buildReminderText(channel, customerName, descr, renewPriceText, expiryMeta) {
                var intro = 'Gentile ' + (customerName || '') + ',\n\n';
                var serviceLine = 'Le ricordiamo che il servizio' + descr + '.\n';

                var expiryLine = '';
                if (expiryMeta && expiryMeta.visible && expiryMeta.lineText) {
                    expiryLine = expiryMeta.lineText + '\n';
                }

                var renewLine = renewPriceText
                    ? 'Il costo di rinnovo previsto è di ' + renewPriceText + '.\n'
                    : '';

                if (channel === 'whatsapp') {
                    return intro +
                        serviceLine +
                        expiryLine +
                        renewLine +
                        '\n' +
                        'Per evitare interruzioni del servizio, La invitiamo a confermare il rinnovo quanto prima.\n\n' +
                        'Può rispondere direttamente a questo messaggio oppure contattarci ai nostri recapiti.\n\n' +
                        reminderFooter;
                }

                return intro +
                    serviceLine + '\n' +
                    expiryLine +
                    (renewLine ? renewLine + '\n' : '') +
                    'Se ha ricevuto la fattura di rinnovo come previsto dal nostro contratto, è perché non ha comunicato disdetta entro il termine previsto di 30 giorni prima della scadenza.\n' +
                    'La invitiamo pertanto a rispettare la scadenza di rinnovo per evitare qualsiasi disservizio.\n\n' +
                    'Se desidera mantenere attivo il servizio, La invitiamo a confermarci il rinnovo rispondendo a questa email oppure contattandoci ai nostri recapiti.\n\n' +
                    reminderFooter;
            }

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;

                var channel      = button.getAttribute('data-channel') || 'email';
                var serviceId    = button.getAttribute('data-service-id') || '';
                var customerName = button.getAttribute('data-customer-name') || '';
                var productName  = button.getAttribute('data-product-name') || '';
                var refName      = button.getAttribute('data-ref-name') || '';
                var domain       = button.getAttribute('data-domain') || '';
                var sendUrl      = button.getAttribute('data-send-url') || '';
                var renewPrice   = button.getAttribute('data-renew-price') || '';
                var renewPeriod  = button.getAttribute('data-renew-period') || '';
                var expiresAt    = button.getAttribute('data-expires-at') || '';

                var renewPriceText = formatMoney(renewPrice, renewPeriod);

                document.getElementById('reminder_service_id').value = serviceId;
                document.getElementById('reminder_customer_name').textContent = customerName || '—';
                document.getElementById('reminder_product_name').textContent  = productName || '—';
                document.getElementById('reminder_ref_name').textContent      = refName || '—';
                document.getElementById('reminder_domain').textContent        = domain || '—';
                document.getElementById('reminder_renew_price').textContent   = renewPriceText || 'Non disponibile';

                var expiryMeta = buildExpiryMeta(expiresAt);
                var expiryBox = document.getElementById('reminder_expiry_box');

                if (expiryMeta.visible) {
                    expiryBox.classList.remove('d-none', 'is-success', 'is-warning', 'is-danger');
                    expiryBox.classList.add(expiryMeta.boxClass);
                    document.getElementById('reminder_expiry_date').textContent = expiryMeta.dateText;
                    document.getElementById('reminder_expiry_status').textContent = expiryMeta.statusText;
                    document.getElementById('reminder_expiry_days').textContent = expiryMeta.daysText;
                } else {
                    expiryBox.classList.add('d-none');
                    expiryBox.classList.remove('is-success', 'is-warning', 'is-danger');
                    document.getElementById('reminder_expiry_date').textContent = '—';
                    document.getElementById('reminder_expiry_status').textContent = '—';
                    document.getElementById('reminder_expiry_days').textContent = '—';
                }

                var titleEl = document.getElementById('serviceReminderModalTitle');
                var submitBtn = document.getElementById('serviceReminderSubmitBtn');

                if (channel === 'whatsapp') {
                    titleEl.textContent = 'Invia promemoria WhatsApp';
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-success');
                    submitBtn.innerHTML = '<i class="bi bi-whatsapp"></i> Invia WhatsApp';
                } else {
                    titleEl.textContent = 'Invia promemoria email';
                    submitBtn.classList.remove('btn-success');
                    submitBtn.classList.add('btn-primary');
                    submitBtn.innerHTML = '<i class="bi bi-send"></i> Invia email';
                }

                var descr = '';
                if (refName) {
                    descr += ' "' + refName + '"';
                } else if (productName) {
                    descr += ' "' + productName + '"';
                }

                if (domain) {
                    descr += ' relativo al dominio ' + domain;
                }

                var textarea = document.getElementById('reminder_message');
                if (textarea) {
                    textarea.value = buildReminderText(
                        channel,
                        customerName,
                        descr,
                        renewPriceText,
                        expiryMeta
                    );
                }

                var form = document.getElementById('serviceReminderForm');
                if (form) {
                    form.action = sendUrl;
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                var textarea = document.getElementById('reminder_message');
                if (textarea) {
                    textarea.value = '';
                }

                var expiryBox = document.getElementById('reminder_expiry_box');
                if (expiryBox) {
                    expiryBox.classList.add('d-none');
                    expiryBox.classList.remove('is-success', 'is-warning', 'is-danger');
                }
            });
        });
    </script>
@endsection
