@extends('admin.layout')
@section('title','Utenti')

@section('content')
    <style>
        :root{
            --pb-bg:#f6f8fb;
            --pb-card:#ffffff;
            --pb-muted:#6c757d;
            --pb-primary:#0d6efd;
            --pb-soft:#e9f1ff;
            --pb-border:#e5e7eb;
        }
        body{ background: var(--pb-bg); }
        .page-topbar{
            position: sticky; top: -1px; z-index: 20;
            background: linear-gradient(180deg,#ffffff 0%, #ffffffef 70%, #ffffff00 100%);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid var(--pb-border);
        }
        .card-soft{ background:var(--pb-card); border:1px solid var(--pb-border); border-radius:14px; box-shadow: 0 1px 2px rgba(16,24,40,.06); }
        .btn-soft{ background:var(--pb-soft); border-color:#cfe1ff; color:#0b5ed7; }
        .btn-soft:hover{ background:#dfeaff; border-color:#bdd3ff; color:#0846a6; }
        .table thead th{ white-space:nowrap; font-weight:600; color:#4b5563; }
        .table tbody tr.selected{ background:#eef5ff; }
        .table td .badge{ font-weight:500; }
        .chip{
            display:inline-flex; align-items:center; gap:.35rem;
            padding:.25rem .5rem; border-radius:999px; font-size:.75rem;
            border:1px solid var(--pb-border); background:#fff;
        }
        .form-select.form-select-sm, .form-control.form-control-sm{ border-radius:10px; }
    </style>

    <div class="page-topbar mb-3">
        <div class="container-fluid py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill fs-5 text-primary"></i>
                    <h1 class="h4 mb-0">Utenti</h1>
                    @isset($users)
                        <span class="badge text-bg-light border"><i class="bi bi-person-check me-1"></i> {{ $users->total() }} totali</span>
                    @endisset
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>{{ session('ok') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="d-flex">
                <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                <div>
                    <strong>Si sono verificati degli errori:</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-soft mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Ricerca</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Cerca nome, email o telefono…">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Ruolo</label>
                    <select class="form-select" name="role">
                        <option value="">Tutti i ruoli</option>
                        @foreach(\App\Enums\Role::cases() as $r)
                            <option value="{{ $r->value }}" @selected(($role ?? '') === $r->value)>{{ $r->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Stato</label>
                    <select class="form-select" name="state">
                        <option value="">Tutti</option>
                        <option value="1" @selected(($state ?? '')==='1')>Attivi</option>
                        <option value="0" @selected(($state ?? '')==='0')>Disattivi</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Filtra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body border-bottom">
            <form id="bulkForm" method="POST" action="{{ route('admin.users.bulk') }}" class="row g-2 align-items-center">
                @csrf
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <select name="action" class="form-select form-select-sm" required>
                        <option value="" disabled selected>Azioni massive…</option>
                        <option value="activate">Attiva selezionati</option>
                        <option value="deactivate">Disattiva selezionati</option>
                        <option value="set_admin">Imposta ruolo: admin</option>
                        <option value="set_user">Imposta ruolo: user</option>
                        <option value="set_superadmin">Imposta ruolo: superadmin</option>
                        <option value="set_agent">Imposta ruolo: agent</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <button type="submit" id="bulkApplyBtn" class="btn btn-sm btn-primary w-100" disabled>
                        <i class="bi bi-magic me-1"></i> Applica
                    </button>
                </div>
                <div class="col-12 col-lg text-lg-end">
                    <span class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Seleziona uno o più utenti per abilitare le azioni.
                    </span>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                <tr>
                    <th style="width:38px">
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" id="pickAll">
                        </div>
                    </th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Ruolo</th>
                    <th>Stato</th>
                    <th>WhatsApp</th>
                    <th>Ultimo accesso</th>
                    <th style="width:260px">Azioni</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="form-check m-0">
                                <input class="form-check-input pick" type="checkbox" name="ids[]" value="{{ $user->id }}" form="bulkForm">
                            </div>
                        </td>
                        <td class="fw-medium">
                            <i class="bi bi-person-circle me-2 text-secondary"></i>{{ $user->name }}
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td class="text-muted">{{ $user->phone ?: '—' }}</td>
                        <td>
                            <span class="chip">
                                <i class="bi bi-award"></i> {{ $user->role->value }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i> attivo</span>
                            @else
                                <span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i> off</span>
                            @endif
                        </td>
                        <td>
                            @if($user->whatsapp_opt_in)
                                <span class="badge text-bg-success">consenso</span>
                            @else
                                <span class="badge text-bg-light border text-dark">no</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Azioni riga">
                                <button class="btn btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal{{ $user->id }}"
                                        title="Modifica">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <a href="{{ route('admin.users.permissions.edit', $user) }}"
                                   class="btn btn-outline-info"
                                   title="Gestisci permessi"
                                   data-bs-toggle="tooltip">
                                    <i class="bi bi-shield-lock"></i>
                                </a>

                                @if($user->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Eliminare questo utente?')"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline-danger"
                                                title="Elimina"
                                                data-bs-toggle="tooltip">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="card-footer bg-white">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    @foreach($users as $user)
        @include('admin.users._edit_modal', ['user' => $user])
    @endforeach

    <script>
        (function(){
            'use strict';

            const pickAll = document.getElementById('pickAll');
            const picks = Array.from(document.querySelectorAll('.pick'));
            const bulkBtn = document.getElementById('bulkApplyBtn');

            function refreshBulkUI(){
                const checked = picks.filter(c => c.checked);
                bulkBtn.disabled = checked.length === 0;

                picks.forEach(c => {
                    const tr = c.closest('tr');
                    tr?.classList.toggle('selected', c.checked);
                });

                if (pickAll){
                    pickAll.indeterminate = checked.length > 0 && checked.length < picks.length;
                    pickAll.checked = checked.length === picks.length && picks.length > 0;
                }
            }

            pickAll?.addEventListener('change', function(){
                picks.forEach(c => c.checked = this.checked);
                refreshBulkUI();
            });

            picks.forEach(c => c.addEventListener('change', refreshBulkUI));
            refreshBulkUI();

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        })();
    </script>
@endsection
