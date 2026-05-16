@extends('admin.layout')
@section('title','Automazioni chiamate CRM')

@section('content')
    <style>
        .r4v5-settings-shell{
            --r4v5-bg:#0f172a;
            --r4v5-panel:#ffffff;
            --r4v5-muted:#64748b;
            --r4v5-border:#e2e8f0;
            --r4v5-soft:#f8fafc;
            --r4v5-primary:#2563eb;
            --r4v5-primary-soft:#dbeafe;
            --r4v5-success:#16a34a;
            --r4v5-danger:#dc2626;
            --r4v5-warning:#f59e0b;
            background:
                radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 34rem),
                linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%);
            border-radius:1.5rem;
            padding:1rem;
        }
        .r4v5-topbar{
            background:linear-gradient(135deg,#020617 0%,#0f172a 55%,#1e3a8a 100%);
            color:#fff;
            border-radius:1.35rem;
            padding:1.15rem 1.25rem;
            box-shadow:0 24px 70px rgba(15,23,42,.22);
            border:1px solid rgba(148,163,184,.22);
        }
        .r4v5-topbar__badge{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.35rem .7rem;
            border-radius:999px;
            background:rgba(255,255,255,.10);
            border:1px solid rgba(255,255,255,.16);
            color:#bfdbfe;
            font-size:.78rem;
            font-weight:750;
            margin-bottom:.65rem;
        }
        .r4v5-topbar h1{font-weight:850;letter-spacing:-.035em;margin:0;font-size:1.45rem;}
        .r4v5-topbar p{color:#cbd5e1;margin:.4rem 0 0;font-size:.95rem;max-width:760px;}
        .r4v5-back-btn{
            border-color:rgba(255,255,255,.25)!important;
            color:#fff!important;
            background:rgba(255,255,255,.08)!important;
            border-radius:.9rem!important;
            font-weight:750;
        }
        .r4v5-back-btn:hover{background:rgba(255,255,255,.15)!important;}
        .r4v5-card{
            background:rgba(255,255,255,.96);
            border:1px solid rgba(148,163,184,.22);
            border-radius:1.25rem;
            box-shadow:0 18px 54px rgba(15,23,42,.075);
            overflow:hidden;
        }
        .r4v5-card__header{
            padding:1rem 1.1rem;
            background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
            border-bottom:1px solid var(--r4v5-border);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
        }
        .r4v5-card__title{font-weight:850;color:#0f172a;margin:0;display:flex;align-items:center;gap:.55rem;}
        .r4v5-card__title i{color:var(--r4v5-primary);}
        .r4v5-card__body{padding:1.1rem;}
        .r4v5-switch-row{
            display:grid;
            grid-template-columns:auto 1fr;
            gap:.85rem;
            align-items:flex-start;
            padding:1rem;
            border:1px solid var(--r4v5-border);
            border-radius:1rem;
            background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);
            margin-bottom:.85rem;
        }
        .r4v5-switch-row.is-main{
            border-color:rgba(37,99,235,.25);
            background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%);
        }
        .r4v5-switch-row .form-check-input{width:3rem;height:1.55rem;margin-top:.15rem;cursor:pointer;}
        .r4v5-switch-row .form-check-input:checked{background-color:var(--r4v5-primary);border-color:var(--r4v5-primary);}
        .r4v5-switch-title{font-weight:850;color:#0f172a;margin:0 0 .2rem;}
        .r4v5-switch-help{color:var(--r4v5-muted);font-size:.88rem;line-height:1.45;}
        .r4v5-pill{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.32rem .62rem;
            border-radius:999px;
            font-size:.75rem;
            font-weight:800;
            border:1px solid transparent;
        }
        .r4v5-pill.ok{background:#dcfce7;color:#166534;border-color:#bbf7d0;}
        .r4v5-pill.ko{background:#fee2e2;color:#991b1b;border-color:#fecaca;}
        .r4v5-pill.lock{background:#e2e8f0;color:#334155;border-color:#cbd5e1;}
        .r4v5-tech-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
            padding:.8rem 0;
            border-bottom:1px solid var(--r4v5-border);
        }
        .r4v5-tech-row:last-child{border-bottom:0;}
        .r4v5-tech-row code{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:.55rem;padding:.18rem .42rem;color:#1e293b;}
        .r4v5-status-box{
            border-radius:1rem;
            border:1px solid var(--r4v5-border);
            padding:1rem;
            margin-bottom:.8rem;
            background:#f8fafc;
        }
        .r4v5-status-box.ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
        .r4v5-status-box.lock{background:#f1f5f9;border-color:#cbd5e1;color:#334155;}
        .r4v5-alert{border-radius:1rem;border:1px solid #fde68a;background:#fffbeb;color:#92400e;padding:.9rem 1rem;}
        .r4v5-actions{background:#f8fafc;border-top:1px solid var(--r4v5-border);padding:1rem 1.1rem;display:flex;justify-content:flex-end;gap:.65rem;}
        .r4v5-actions .btn{border-radius:.85rem;font-weight:800;}
        .r4v5-actions .btn-primary{background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);border-color:#2563eb;box-shadow:0 12px 24px rgba(37,99,235,.20);}
    </style>

    <div class="r4v5-settings-shell">
        <div class="r4v5-topbar mb-3">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="r4v5-topbar__badge">
                        <i class="bi bi-shield-check"></i>
                        Scheduler Safe Controls
                    </div>
                    <h1>Automazioni chiamate CRM</h1>
                    <p>Gestione sicura dei job automatici per campagne chiamate, recovery e protezione dei log quando il modulo non è attivo o le tabelle non sono disponibili.</p>
                </div>
                <a href="{{ route('admin.settings.index', ['tab' => 'crm']) }}" class="btn r4v5-back-btn">
                    <i class="bi bi-arrow-left me-1"></i> Impostazioni CRM
                </a>
            </div>
        </div>

        @if(session('ok'))
            <div class="alert alert-success rounded-4 border-0 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i>{{ session('ok') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger rounded-4 border-0 shadow-sm">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.settings.crm-call-automation.update') }}" class="r4v5-card">
                    @csrf
                    @method('PUT')

                    <div class="r4v5-card__header">
                        <h2 class="h6 r4v5-card__title">
                            <i class="bi bi-toggles2"></i>
                            Interruttori automazioni
                        </h2>
                        <span class="r4v5-pill {{ $settings['enabled'] ? 'ok' : 'lock' }}">
                            <i class="bi {{ $settings['enabled'] ? 'bi-check-circle' : 'bi-lock' }}"></i>
                            {{ $settings['enabled'] ? 'Modulo attivo' : 'Modulo spento' }}
                        </span>
                    </div>

                    <div class="r4v5-card__body">
                        @if(! $settingsAvailable)
                            <div class="r4v5-alert mb-3">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                La tabella <code>settings</code> non è presente. Le automazioni restano disabilitate tramite fallback tecnico.
                            </div>
                        @endif

                        <label class="r4v5-switch-row is-main" for="enabled">
                            <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1" @checked($settings['enabled']) @disabled(! $settingsAvailable)>
                            <span>
                                <span class="r4v5-switch-title d-block">Abilita modulo chiamate CRM</span>
                                <span class="r4v5-switch-help d-block">Interruttore generale. Se spento, nessun job automatico viene registrato dallo scheduler Laravel.</span>
                            </span>
                        </label>

                        <label class="r4v5-switch-row" for="run_active_enabled">
                            <input class="form-check-input" type="checkbox" role="switch" id="run_active_enabled" name="run_active_enabled" value="1" @checked($settings['run_active_enabled']) @disabled(! $settingsAvailable)>
                            <span>
                                <span class="r4v5-switch-title d-block">Esegui automaticamente campagne attive</span>
                                <span class="r4v5-switch-help d-block">Richiede <code>crm_call_campaigns</code>. Se la tabella manca, il job resta bloccato in sicurezza.</span>
                            </span>
                        </label>

                        <label class="r4v5-switch-row mb-0" for="recover_stuck_enabled">
                            <input class="form-check-input" type="checkbox" role="switch" id="recover_stuck_enabled" name="recover_stuck_enabled" value="1" @checked($settings['recover_stuck_enabled']) @disabled(! $settingsAvailable)>
                            <span>
                                <span class="r4v5-switch-title d-block">Recupera automaticamente chiamate bloccate</span>
                                <span class="r4v5-switch-help d-block">Richiede <code>crm_call_queue</code> e <code>crm_call_logs</code>. Evita chiamate rimaste in stato <code>calling</code>.</span>
                            </span>
                        </label>
                    </div>

                    <div class="r4v5-actions">
                        <a href="{{ route('admin.settings.index', ['tab' => 'crm']) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Annulla
                        </a>
                        <button class="btn btn-primary" @disabled(! $settingsAvailable)>
                            <i class="bi bi-save2 me-1"></i> Salva automazioni
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-xl-5">
                <div class="r4v5-card mb-3">
                    <div class="r4v5-card__header">
                        <h2 class="h6 r4v5-card__title">
                            <i class="bi bi-database-check"></i>
                            Stato tecnico
                        </h2>
                    </div>
                    <div class="r4v5-card__body">
                        <div class="r4v5-tech-row">
                            <span>Tabella <code>settings</code></span>
                            <span class="r4v5-pill {{ $settingsAvailable ? 'ok' : 'ko' }}">
                                <i class="bi {{ $settingsAvailable ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                                {{ $settingsAvailable ? 'presente' : 'mancante' }}
                            </span>
                        </div>

                        @foreach($tables as $table => $exists)
                            <div class="r4v5-tech-row">
                                <span><code>{{ $table }}</code></span>
                                <span class="r4v5-pill {{ $exists ? 'ok' : 'ko' }}">
                                    <i class="bi {{ $exists ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                                    {{ $exists ? 'presente' : 'mancante' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="r4v5-card">
                    <div class="r4v5-card__header">
                        <h2 class="h6 r4v5-card__title">
                            <i class="bi bi-activity"></i>
                            Esito scheduler
                        </h2>
                    </div>
                    <div class="r4v5-card__body">
                        <div class="r4v5-status-box {{ $canRunActive ? 'ok' : 'lock' }}">
                            <div class="fw-bold mb-1">
                                <i class="bi {{ $canRunActive ? 'bi-play-circle' : 'bi-shield-lock' }} me-1"></i>
                                Campagne attive
                            </div>
                            {{ $canRunActive ? 'Scheduler abilitabile: requisiti soddisfatti.' : 'Scheduler bloccato in sicurezza.' }}
                        </div>

                        <div class="r4v5-status-box {{ $canRecoverStuck ? 'ok' : 'lock' }} mb-0">
                            <div class="fw-bold mb-1">
                                <i class="bi {{ $canRecoverStuck ? 'bi-arrow-repeat' : 'bi-shield-lock' }} me-1"></i>
                                Recovery chiamate
                            </div>
                            {{ $canRecoverStuck ? 'Scheduler abilitabile: requisiti soddisfatti.' : 'Scheduler bloccato in sicurezza.' }}
                        </div>

                        <div class="small text-muted mt-3">
                            Anche se Plesk esegue <code>php artisan schedule:run</code>, i job chiamate vengono registrati solo se modulo, singolo job e tabelle richieste sono disponibili.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
