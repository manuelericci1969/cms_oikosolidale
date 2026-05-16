@extends('admin.layout')
@section('title', 'Gestisci Permessi - ' . $user->name)

@section('content')
    @php
        use Illuminate\Support\Str;

        // Prepara dati lato Blade
        $userPermissions   = $user->permissions->pluck('name')->toArray();
        $groupedPermissions = $allPermissions->groupBy(function($p) {
            return explode('.', $p->name)[0];
        });

        // Ricava permessi del ruolo (se già calcolati in controller, puoi usare direttamente la variabile)
        $roleValue = is_string($user->role) ? $user->role : $user->role->value;
        $rolePermissionIds = \Illuminate\Support\Facades\DB::table('role_permissions')
            ->where('role', $roleValue)
            ->pluck('permission_id');
        $rolePermissions = \App\Models\Permission::whereIn('id', $rolePermissionIds)->get();
    @endphp

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
        .chip{
            display:inline-flex; align-items:center; gap:.35rem;
            padding:.25rem .55rem; border-radius:999px; font-size:.75rem;
            border:1px solid var(--pb-border); background:#fff;
        }
        .accordion-button .group-badge{ margin-left:auto; }
        .perm-item[hidden]{ display:none !important; }
        .fade-muted{ color: var(--pb-muted); }
        .toolbar-hint{ font-size:.875rem; color:#6b7280; }
        .list-compact li{ padding:.15rem 0; }
    </style>

    {{-- Topbar --}}
    <div class="page-topbar mb-3">
        <div class="container-fluid py-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
                        <h1 class="h4 mb-0">Gestisci Permessi</h1>
                    </div>
                    <span class="chip"><i class="bi bi-person-fill"></i> {{ $user->name }}</span>
                    <span class="chip"><i class="bi bi-envelope"></i> {{ $user->email }}</span>
                    <span class="badge text-bg-info"><i class="bi bi-award me-1"></i> Ruolo: {{ $roleValue }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Torna agli utenti
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lock-fill text-primary"></i>
                        <span class="fw-semibold">Permessi Disponibili</span>
                    </div>
                    <span class="badge text-bg-light border">
                        <i class="bi bi-check2-square me-1"></i>
                        Selezionati: <span id="selTotal">0</span>
                    </span>
                </div>

                <div class="card-body">
                    {{-- Info --}}
                    <div class="alert alert-info d-flex align-items-start" role="alert">
                        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                        <div>
                            <strong>Come funziona</strong>
                            <ul class="mb-0">
                                <li>I permessi del <strong>ruolo</strong> sono automatici.</li>
                                <li>Qui assegni <strong>permessi extra</strong> specifici per l’utente.</li>
                                <li>I permessi selezionati si sommano a quelli del ruolo.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- TOOLBAR: ricerca + azioni --}}
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fade-muted">Ricerca permessi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="permSearch" class="form-control" placeholder="Cerca per nome o descrizione…">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex gap-2 justify-content-md-end">
                            <button type="button" class="btn btn-outline-primary" id="btnSelectFiltered">
                                <i class="bi bi-check2-all me-1"></i> Seleziona filtrati
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnClearFiltered">
                                <i class="bi bi-x-circle me-1"></i> Deseleziona filtrati
                            </button>
                            <button type="button" class="btn btn-soft" id="btnExpandAll">
                                <i class="bi bi-arrows-expand me-1"></i> Espandi
                            </button>
                            <button type="button" class="btn btn-soft" id="btnCollapseAll">
                                <i class="bi bi-arrows-collapse me-1"></i> Comprimi
                            </button>
                        </div>
                        <div class="col-12">
                            <div class="toolbar-hint">
                                <i class="bi bi-funnel me-1"></i> Le azioni agiscono solo sugli elementi <strong>visibili</strong> (filtrati).
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.users.permissions.sync', $user) }}" id="permForm">
                        @csrf

                        <div class="accordion" id="permAcc">
                            @foreach($groupedPermissions as $group => $perms)
                                @php
                                    $groupSlug = Str::slug($group);
                                    $selInGroup = $perms->filter(fn($p) => in_array($p->name, $userPermissions))->count();
                                @endphp

                                <div class="accordion-item" data-group="{{ $groupSlug }}">
                                    <h2 class="accordion-header" id="heading-{{ $groupSlug }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse-{{ $groupSlug }}"
                                                aria-expanded="false"
                                                aria-controls="collapse-{{ $groupSlug }}">
                                            <span class="text-uppercase small fw-semibold">{{ $group }}</span>
                                            <span class="group-badge badge text-bg-light border ms-2">
                                                <i class="bi bi-check2-square me-1"></i>
                                                <span id="count-{{ $groupSlug }}">{{ $selInGroup }}</span>/<span>{{ $perms->count() }}</span>
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $groupSlug }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $groupSlug }}" data-bs-parent="#permAcc">
                                        <div class="accordion-body pt-3">
                                            <div class="d-flex gap-2 mb-3">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-action="group-select" data-group="{{ $groupSlug }}">
                                                    <i class="bi bi-check2-all me-1"></i> Seleziona gruppo
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        data-action="group-clear" data-group="{{ $groupSlug }}">
                                                    <i class="bi bi-x-circle me-1"></i> Pulisci gruppo
                                                </button>
                                            </div>

                                            <div class="row">
                                                @foreach($perms as $permission)
                                                    @php
                                                        $checked = in_array($permission->name, $userPermissions);
                                                    @endphp
                                                    <div class="col-md-6 mb-2 perm-item"
                                                         data-group="{{ $groupSlug }}"
                                                         data-name="{{ Str::lower($permission->name . ' ' . ($permission->description ?? '')) }}">
                                                        <div class="form-check">
                                                            <input class="form-check-input perm-check"
                                                                   type="checkbox"
                                                                   name="permissions[]"
                                                                   value="{{ $permission->name }}"
                                                                   id="perm_{{ $permission->id }}"
                                                                   data-group="{{ $groupSlug }}"
                                                                @checked($checked)>
                                                            <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                                <strong>{{ $permission->name }}</strong>
                                                                @if($permission->description)
                                                                    <br><small class="text-muted">{{ $permission->description }}</small>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <i class="bi bi-hash me-1"></i> Gruppo: {{ $group }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-3">

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save2 me-1"></i> Salva Permessi
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i> Annulla
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar destra --}}
        <div class="col-lg-4">
            <div class="card card-soft mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-data text-primary"></i>
                    <span class="fw-semibold">Riepilogo Permessi</span>
                </div>
                <div class="card-body">
                    <h6 class="mb-2"><i class="bi bi-award-fill me-1 text-warning"></i> Dal ruolo “{{ $roleValue }}”</h6>
                    @if($rolePermissions->count() > 0)
                        <ul class="small mb-0 list-compact">
                            @foreach($rolePermissions as $rp)
                                <li><i class="bi bi-dot text-muted"></i> {{ $rp->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">Nessun permesso dal ruolo.</p>
                    @endif

                    <hr>

                    <h6 class="mb-2"><i class="bi bi-plus-circle-fill me-1 text-success"></i> Permessi extra utente</h6>
                    @if($user->permissions->count() > 0)
                        <ul class="small mb-0 list-compact">
                            @foreach($user->permissions as $up)
                                <li class="text-primary"><i class="bi bi-check2"></i> {{ $up->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">Nessun permesso extra.</p>
                    @endif
                </div>
            </div>

            <div class="card border-danger card-soft">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Azioni Rapide
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.permissions.clear', $user) }}"
                          onsubmit="return confirm('Rimuovere TUTTI i permessi extra di questo utente?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash3 me-1"></i> Rimuovi tutti i permessi extra
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        (function(){
            'use strict';

            const $ = (s,ctx=document)=>ctx.querySelector(s);
            const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));

            const searchInput = $('#permSearch');
            const permItems   = $$('.perm-item');
            const permChecks  = $$('.perm-check');
            const selTotalEl  = $('#selTotal');

            function updateCounts(){
                // totale
                const totalSelected = permChecks.filter(c => c.checked).length;
                selTotalEl.textContent = totalSelected;

                // per gruppo
                const groups = new Set(permChecks.map(c => c.dataset.group));
                groups.forEach(g => {
                    const checks = permChecks.filter(c => c.dataset.group === g);
                    const count  = checks.filter(c => c.checked).length;
                    const badge  = document.getElementById('count-' + g);
                    if (badge) badge.textContent = count;
                });
            }

            function applyFilter(){
                const q = (searchInput.value || '').trim().toLowerCase();
                const groups = $$('.accordion-item');

                // filtra singole card permesso
                permItems.forEach(item => {
                    const name = (item.dataset.name || '');
                    const match = !q || name.includes(q);
                    item.hidden = !match;
                });

                // nascondi gruppi senza risultati
                groups.forEach(gr => {
                    const anyVisible = $$('.perm-item:not([hidden])', gr).length > 0;
                    gr.style.display = anyVisible ? '' : 'none';
                });
            }

            // Azioni filtri
            $('#btnSelectFiltered')?.addEventListener('click', () => {
                $$('.perm-item:not([hidden]) .perm-check').forEach(c => c.checked = true);
                updateCounts();
            });
            $('#btnClearFiltered')?.addEventListener('click', () => {
                $$('.perm-item:not([hidden]) .perm-check').forEach(c => c.checked = false);
                updateCounts();
            });

            // Azioni gruppo (select/clear)
            document.addEventListener('click', (e)=>{
                const btn = e.target.closest('[data-action^="group-"]');
                if(!btn) return;
                const group = btn.dataset.group;
                const checks = $$('.perm-check').filter(c => c.dataset.group === group);
                if (btn.dataset.action === 'group-select') checks.forEach(c => c.checked = true);
                if (btn.dataset.action === 'group-clear')  checks.forEach(c => c.checked = false);
                updateCounts();
            });

            // Expand/Collapse all
            $('#btnExpandAll')?.addEventListener('click', () => {
                $$('.accordion-collapse').forEach(el => new bootstrap.Collapse(el, {show:true}));
            });
            $('#btnCollapseAll')?.addEventListener('click', () => {
                $$('.accordion-collapse.show').forEach(el => new bootstrap.Collapse(el, {toggle:true}));
            });

            // Live search
            searchInput?.addEventListener('input', applyFilter);

            // Recount on individual change
            permChecks.forEach(c => c.addEventListener('change', updateCounts));

            // Tooltips (se presenti)
            $$('.[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

            // Init
            applyFilter();
            updateCounts();
        })();
    </script>
@endsection
