@extends('admin.layout')

@section('title', $service->exists ? 'Modifica servizio' : 'Nuovo servizio')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            {{ $service->exists ? 'Modifica servizio' : 'Nuovo servizio' }}
        </h1>

        <a href="{{ route('admin.crm.services.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Torna all’elenco
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione!</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <style>
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

    @if(isset($customer) && $customer)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Servizi di {{ $customer->name }}</strong>
                    <div class="small text-muted">
                        Cliente #{{ $customer->id }}
                        @if(!empty($customer->vat_number))
                            · P.IVA {{ $customer->vat_number }}
                        @endif
                    </div>
                </div>

                <a href="{{ route('admin.crm.services.create', ['customer_id' => $customer->id]) }}"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuovo servizio per questo cliente
                </a>
            </div>

            <div class="card-body p-0">
                @if(isset($customerServices) && $customerServices->count())
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Riferimento</th>
                                <th>Dominio</th>
                                <th>Inizio</th>
                                <th>Scadenza</th>
                                <th>Prezzo / Rinnovo</th>
                                <th class="text-center">Stato</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($customerServices as $row)
                                @php
                                    $gross = $row->renew_price_gross;
                                    $net   = $row->renew_price_net;
                                    $customerPhone = $customer->whatsapp ?? $customer->mobile ?? $customer->phone ?? null;
                                @endphp
                                <tr>
                                    <td>{{ optional($row->product)->name ?? '-' }}</td>

                                    <td>
                                        @if(!empty($row->name))
                                            <div>{{ $row->name }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        @if($row->provider_website)
                                            <a href="{{ $row->provider_website }}"
                                               target="_blank"
                                               rel="noopener">
                                                {{ $row->provider_website }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>{{ optional($row->activated_at)->format('d/m/Y') }}</td>
                                    <td>{{ optional($row->expires_at)->format('d/m/Y') }}</td>

                                    <td>
                                        @if(!is_null($gross))
                                            <div>
                                                <strong>{{ number_format($gross, 2, ',', '.') }} €</strong>
                                                <span class="small text-muted d-block">
                                                    Rinnovo IVA inclusa
                                                </span>
                                            </div>
                                            @if(!is_null($net))
                                                <div class="small text-muted">
                                                    Imponibile: {{ number_format($net, 2, ',', '.') }} €
                                                    @if($row->renew_price_vat_rate)
                                                        (IVA {{ $row->renew_price_vat_rate }}%)
                                                    @endif
                                                </div>
                                            @endif
                                        @elseif(!is_null($row->renewal_price))
                                            <div>
                                                <strong>{{ number_format($row->renewal_price, 2, ',', '.') }} €</strong>
                                                <span class="small text-muted d-block">
                                                    Prezzo contratto
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-{{ $row->status_color }}">
                                            {{ $row->status_label }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        {{-- EMAIL --}}
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Invia promemoria email"
                                                data-bs-toggle="modal"
                                                data-bs-target="#serviceReminderModal"
                                                data-channel="email"
                                                data-service-id="{{ $row->id }}"
                                                data-service-name="{{ $row->name ?: optional($row->product)->name ?? '-' }}"
                                                data-product-name="{{ optional($row->product)->name ?? '' }}"
                                                data-ref-name="{{ $row->name }}"
                                                data-domain="{{ $row->provider_website }}"
                                                data-customer-name="{{ $customer->name }}"
                                                data-renew-price="{{ $row->renew_price_gross }}"
                                                data-renew-period="{{ $row->renewal_vat_mode }}"
                                                data-expires-at="{{ optional($row->expires_at)->format('Y-m-d') }}"
                                                data-send-url="{{ route('admin.crm.services.send-reminder', $row) }}">
                                            <i class="bi bi-envelope"></i>
                                        </button>

                                        {{-- WHATSAPP --}}
                                        @if($customerPhone)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    title="Invia promemoria WhatsApp"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#serviceReminderModal"
                                                    data-channel="whatsapp"
                                                    data-service-id="{{ $row->id }}"
                                                    data-service-name="{{ $row->name ?: optional($row->product)->name ?? '-' }}"
                                                    data-product-name="{{ optional($row->product)->name ?? '' }}"
                                                    data-ref-name="{{ $row->name }}"
                                                    data-domain="{{ $row->provider_website }}"
                                                    data-customer-name="{{ $customer->name }}"
                                                    data-renew-price="{{ $row->renew_price_gross }}"
                                                    data-renew-period="{{ $row->renewal_vat_mode }}"
                                                    data-expires-at="{{ optional($row->expires_at)->format('Y-m-d') }}"
                                                    data-send-url="{{ route('admin.crm.services.send-whatsapp-reminder', $row) }}">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.crm.services.edit', $row) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Modifica">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                @else
                    <p class="text-muted m-3 mb-4">
                        Nessun servizio ancora associato a questo cliente.
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST"
                  action="{{ $service->exists
                        ? route('admin.crm.services.update', $service)
                        : route('admin.crm.services.store') }}">
                @csrf
                @if($service->exists)
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-lg-7">
                        <h5 class="mb-3">Dati generali</h5>

                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            @if(isset($customer) && $customer)
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <input type="text" class="form-control" value="{{ $customer->name }}" disabled>
                            @else
                                <select name="customer_id"
                                        class="form-select @error('customer_id') is-invalid @enderror">
                                    <option value="">Seleziona cliente…</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}"
                                            {{ old('customer_id', $service->customer_id) == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prodotto / Servizio</label>
                            <select name="product_id"
                                    class="form-select @error('product_id') is-invalid @enderror">
                                <option value="">Seleziona prodotto…</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ old('product_id', $service->product_id) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nome / riferimento</label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $service->name) }}">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dominio / URL</label>
                            <input type="text"
                                   name="provider_website"
                                   class="form-control @error('provider_website') is-invalid @enderror"
                                   value="{{ old('provider_website', $service->provider_website) }}">
                            @error('provider_website')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <h5 class="mb-3">Date e prezzi</h5>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data attivazione</label>
                                <input type="date"
                                       name="activated_at"
                                       class="form-control @error('activated_at') is-invalid @enderror"
                                       value="{{ old('activated_at', optional($service->activated_at)->format('Y-m-d')) }}">
                                @error('activated_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data scadenza</label>
                                <input type="date"
                                       name="expires_at"
                                       class="form-control @error('expires_at') is-invalid @enderror"
                                       value="{{ old('expires_at', optional($service->expires_at)->format('Y-m-d')) }}">
                                @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prezzo contratto</label>
                                <input type="number" step="0.01"
                                       name="renewal_price"
                                       class="form-control @error('renewal_price') is-invalid @enderror"
                                       value="{{ old('renewal_price', $service->renewal_price) }}">
                                @error('renewal_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prezzo rinnovo</label>
                                <input type="number" step="0.01"
                                       name="renew_price"
                                       class="form-control @error('renew_price') is-invalid @enderror"
                                       value="{{ old('renew_price', $service->renew_price) }}">
                                @error('renew_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">IVA rinnovo (%)</label>
                                <input type="number" step="0.01"
                                       name="renew_price_vat_rate"
                                       class="form-control @error('renew_price_vat_rate') is-invalid @enderror"
                                       value="{{ old('renew_price_vat_rate', $service->renew_price_vat_rate) }}">
                                @error('renew_price_vat_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Periodicità rinnovo</label>
                                @php
                                    $renewPeriod = old('renewal_vat_mode', $service->renewal_vat_mode);
                                    $periodOptions = [
                                        'week'  => 'Settimanale',
                                        'month' => 'Mensile',
                                        'year'  => 'Annuale',
                                    ];
                                @endphp
                                <select name="renewal_vat_mode"
                                        class="form-select @error('renewal_vat_mode') is-invalid @enderror">
                                    <option value="">-- Seleziona --</option>
                                    @foreach($periodOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $renewPeriod === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('renewal_vat_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="renew_price_vat_included" value="0">
                                    @php
                                        $vatIncludedOld = old('renew_price_vat_included', $service->renew_price_vat_included ? 1 : 0);
                                    @endphp
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="renew_price_vat_included"
                                           id="renew_price_vat_included"
                                           value="1"
                                        {{ (int)$vatIncludedOld === 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="renew_price_vat_included">
                                        Prezzo rinnovo IVA inclusa
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="auto_renew" value="0">
                                    @php
                                        $autoRenewOld = old('auto_renew', $service->auto_renew ? 1 : 0);
                                    @endphp
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="auto_renew"
                                           id="auto_renew"
                                           value="1"
                                        {{ (int)$autoRenewOld === 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_renew">
                                        Rinnovo automatico
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Stato</h5>

                        <div class="mb-3">
                            <label class="form-label">Stato servizio</label>
                            @php $statusValue = old('status', $service->status ?? 'active'); @endphp
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                @foreach(\App\Modules\Crm\Models\Service::STATUS_OPTIONS as $key => $label)
                                    <option value="{{ $key }}" {{ $statusValue === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="border rounded p-3 mb-4 bg-light-subtle">
                            <h5 class="mb-3">Promemoria scadenza</h5>

                            <div class="form-check mb-2">
                                <input type="hidden" name="send_reminder" value="0">
                                @php
                                    $sendReminderOld = old('send_reminder', $service->send_reminder ? 1 : 0);
                                @endphp
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="send_reminder"
                                       id="send_reminder"
                                       value="1"
                                    {{ (int)$sendReminderOld === 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_reminder">
                                    Abilita promemoria automatici
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Giorni prima della scadenza</label>
                                <input type="number"
                                       name="reminder_days_before"
                                       class="form-control @error('reminder_days_before') is-invalid @enderror"
                                       value="{{ old('reminder_days_before', $service->reminder_days_before ?? 15) }}">
                                @error('reminder_days_before')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Usato per eventuali invii automatici.
                                    Il bottone <strong>Invia promemoria</strong> funziona sempre in modo manuale.
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Testo predefinito promemoria (nota)</label>
                                <textarea name="reminder_custom_text"
                                          rows="3"
                                          class="form-control @error('reminder_custom_text') is-invalid @enderror">{{ old('reminder_custom_text', $service->reminder_custom_text) }}</textarea>
                                @error('reminder_custom_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Testo di riferimento per i promemoria.
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4 bg-light-subtle">
                            <h5 class="mb-3">Dati di accesso al pannello</h5>

                            <div class="mb-3">
                                <label class="form-label">URL pannello</label>
                                <input type="text"
                                       name="panel_url"
                                       class="form-control @error('panel_url') is-invalid @enderror"
                                       value="{{ old('panel_url', $service->panel_url) }}">
                                @error('panel_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username pannello</label>
                                <input type="text"
                                       name="panel_username"
                                       class="form-control @error('panel_username') is-invalid @enderror"
                                       value="{{ old('panel_username', $service->panel_username) }}">
                                @error('panel_username')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Password pannello</label>
                                <input type="text"
                                       name="panel_password"
                                       class="form-control @error('panel_password') is-invalid @enderror"
                                       value="{{ old('panel_password', $service->panel_password) }}">
                                @error('panel_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Se lasci vuoto in modifica, la password esistente non viene cambiata.
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-0 bg-light-subtle">
                            <h5 class="mb-3">Note interne</h5>

                            <textarea name="notes"
                                      rows="4"
                                      class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $service->notes) }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.crm.services.index') }}" class="btn btn-outline-secondary">
                        Annulla
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        {{ $service->exists ? 'Salva modifiche' : 'Crea servizio' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        use App\Models\Setting;

        $reminderLogs = $reminderLogs ?? collect();
        $statusLabelMap = [
            'pending' => 'In coda',
            'sent'    => 'Inviata',
            'opened'  => 'Aperta',
            'failed'  => 'Errore',
        ];
        $statusClassMap = [
            'pending' => 'secondary',
            'sent'    => 'primary',
            'opened'  => 'success',
            'failed'  => 'danger',
        ];

        $companyName     = Setting::get('company.name');
        $companyVat      = Setting::get('company.vat');
        $companyAddress  = Setting::get('company.address');
        $companyCity     = Setting::get('company.city');
        $companyZip      = Setting::get('company.zip');
        $companyProvince = Setting::get('company.province');
        $companyEmail    = Setting::get('company.email');
        $companyPhone    = Setting::get('company.phone');
    @endphp

    @if($service->exists)
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Cronologia invii promemoria</span>
                <span class="badge bg-light text-dark border">
                    Totale: {{ $reminderLogs->count() }}
                </span>
            </div>

            <div class="card-body p-0">
                @if($reminderLogs->isEmpty())
                    <p class="text-muted m-3">
                        Nessun promemoria inviato per questo servizio.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                            <tr>
                                <th style="width: 140px;">Data invio</th>
                                <th style="width: 140px;">Data lettura</th>
                                <th style="width: 110px;">Stato</th>
                                <th style="width: 110px;">Canale</th>
                                <th>Destinatario</th>
                                <th>Oggetto</th>
                                <th>Testo</th>
                                <th>Errore</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($reminderLogs as $log)
                                @php
                                    $statusLabel = $statusLabelMap[$log->status] ?? $log->status;
                                    $statusClass = $statusClassMap[$log->status] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>
                                        @if($log->sent_at)
                                            {{ $log->sent_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->opened_at)
                                            {{ $log->opened_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">Mai</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(($log->channel ?? 'email') === 'whatsapp')
                                            <span class="badge bg-success">WhatsApp</span>
                                        @else
                                            <span class="badge bg-primary">Email</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($log->channel ?? 'email') === 'whatsapp')
                                            {{ $log->to_phone ?: '—' }}
                                        @else
                                            {{ $log->to_email ?: '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $log->subject }}</td>
                                    <td style="max-width: 260px;">
                                        @if($log->body)
                                            <details>
                                                <summary class="small text-muted">Mostra testo</summary>
                                                <div class="small mb-0 text-wrap"
                                                     style="white-space: pre-wrap; word-break: break-word;">
                                                    {{ $log->body }}
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->error)
                                            <span class="small text-danger">{{ $log->error }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="modal fade" id="serviceReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="serviceReminderForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="serviceReminderModalTitle">
                            Invia promemoria
                        </h5>
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
                                      rows="6"
                                      class="form-control"
                                      required></textarea>
                            <div class="form-text">
                                Puoi modificare liberamente il testo prima dell'invio.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
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
                    daysText = Math.abs(days) + ' giorni';
                    lineText = 'Il servizio risulta scaduto in data ' + dateText + ', da ' + Math.abs(days) + ' giorni.';
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

                document.getElementById('reminder_service_id').value = serviceId;
                document.getElementById('reminder_customer_name').textContent = customerName || '—';
                document.getElementById('reminder_product_name').textContent  = productName || '—';
                document.getElementById('reminder_ref_name').textContent      = refName || '—';
                document.getElementById('reminder_domain').textContent        = domain || '—';

                var renewPriceText = formatMoney(renewPrice, renewPeriod);
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

                var textarea = document.getElementById('reminder_message');
                if (textarea) {
                    var descr = '';

                    if (refName) {
                        descr += ' "' + refName + '"';
                    } else if (productName) {
                        descr += ' "' + productName + '"';
                    }

                    if (domain) {
                        descr += ' relativo al dominio ' + domain;
                    }

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
