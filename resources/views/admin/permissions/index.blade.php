@extends('admin.layout')
@section('title','Permessi')

@section('content')
    @php use Illuminate\Support\Str; @endphp

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
        .form-hint{ font-size:.875rem; color:var(--pb-muted); }
        .table thead th{ white-space:nowrap; font-weight:600; color:#4b5563; }
        .badge-ambito{ background:#fff; border:1px solid var(--pb-border); color:#374151; }
        .code-chip{
            display:inline-flex; align-items:center; gap:.35rem;
            padding:.15rem .5rem; border-radius:6px; border:1px solid var(--pb-border);
            background:#fff; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
        .row-muted{ color:#6b7280; }
    </style>

    {{-- Topbar --}}
    <div class="page-topbar mb-3">
        <div class="container-fluid py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
                    <h1 class="h4 mb-0">Permessi</h1>
                    <span class="badge text-bg-light border"><i class="bi bi-check2-square me-1"></i> {{ $permissions->count() }} visti</span>
                </div>
                <button class="btn btn-outline-secondary btn-sm"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#helpPermessi"
                        aria-expanded="false"
                        aria-controls="helpPermessi">
                    <i class="bi bi-question-circle me-1"></i> Cos'è questa pagina?
                </button>
            </div>
        </div>
    </div>

    {{-- Help --}}
    <div class="collapse" id="helpPermessi">
        <div class="card card-soft mb-3">
            <div class="card-body">
                <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i> A cosa serve</div>
                <p class="mb-2">
                    Qui definisci i <strong>permessi</strong> del sistema (es. <code>manage.users</code>, <code>content.pages.edit</code>).
                    I permessi creati qui potranno essere:
                </p>
                <ul class="mb-2">
                    <li><strong>Assegnati ai ruoli</strong> dalla sezione <em>Ruoli</em> (tutti gli utenti con quel ruolo li ereditano).</li>
                    <li><strong>Assegnati a singoli utenti</strong> dalla pagina <em>Permessi</em> dell’utente (override).</li>
                </ul>
                <p class="mb-0 small text-muted">
                    Convenzione consigliata: <code>area.oggetto.azione</code> • Il <strong>SuperAdmin</strong> ha sempre tutti i permessi.
                </p>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('ok'))
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check2-circle me-2"></i><div>{{ session('ok') }}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
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

    {{-- Nuovo permesso --}}
    <div class="card card-soft mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-plus-square text-primary"></i>
                <span class="fw-semibold">Aggiungi permesso</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.permissions.store') }}" class="row g-2 align-items-end" id="createPermForm">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nome (es. manage.posts)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-braces-asterisk"></i></span>
                        <input name="name" id="permName" class="form-control" required placeholder="area.oggetto.azione" autocomplete="off">
                    </div>
                    <div class="form-hint mt-1">
                        <i class="bi bi-magic"></i> minuscole, punti come separatori •
                        <span id="namePreview" class="code-chip ms-1">area · oggetto · azione</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descrizione</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                        <input name="description" class="form-control" placeholder="Descrizione breve (facoltativa)">
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Aggiungi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toolbar elenco --}}
    <div class="card card-soft">
        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Ricerca</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchBox" class="form-control" placeholder="Cerca per nome o descrizione…">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Pulisci">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Ordina per</label>
                    <select id="sortSelect" class="form-select">
                        <option value="name_asc">Nome (A→Z)</option>
                        <option value="name_desc">Nome (Z→A)</option>
                        <option value="group_asc">Ambito (A→Z)</option>
                        <option value="group_desc">Ambito (Z→A)</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted small">
                        <i class="bi bi-list-check me-1"></i>
                        Mostrati: <span id="shownCount">{{ $permissions->count() }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Elenco --}}
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="permTable">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Ambito</th>
                    <th>Descrizione</th>
                    <th class="text-end" style="width:120px">Azioni</th>
                </tr>
                </thead>
                <tbody>
                @forelse($permissions as $perm)
                    @php $group = Str::before($perm->name, '.') ?: '—'; @endphp
                    <tr data-name="{{ Str::lower($perm->name.' '.($perm->description ?? '')) }}"
                        data-group="{{ Str::lower($group) }}">
                        <td>
                            <span class="code-chip"><code>{{ $perm->name }}</code></span>
                            <button type="button"
                                    class="btn btn-link btn-sm p-0 align-baseline copy-btn ms-1"
                                    data-copy="{{ $perm->name }}"
                                    data-bs-toggle="tooltip" title="Copia nome">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </td>
                        <td>
                            <span class="badge badge-ambito">
                                <i class="bi bi-folder2-open me-1"></i>{{ $group }}
                            </span>
                        </td>
                        <td class="row-muted">{{ $perm->description }}</td>
                        <td class="text-end">
                            <form method="POST"
                                  action="{{ route('admin.permissions.destroy', $perm) }}"
                                  id="delForm_{{ $perm->id }}"
                                  class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-action="ask-delete"
                                        data-form="delForm_{{ $perm->id }}"
                                        data-name="{{ $perm->name }}"
                                        data-bs-toggle="tooltip" title="Elimina">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-inboxes"></i> Nessun permesso presente.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($permissions, 'hasPages') && $permissions->hasPages())
            <div class="card-footer bg-white">
                {{ $permissions->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Modal conferma eliminazione --}}
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Conferma eliminazione</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    Vuoi davvero eliminare il permesso <span class="fw-semibold"><code id="delPermName"></code></span>?
                    <div class="form-hint mt-2"><i class="bi bi-info-circle"></i> L’operazione non è reversibile.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Annulla
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash3 me-1"></i> Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        (function(){
            'use strict';
            const $  = (s,ctx=document)=>ctx.querySelector(s);
            const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));

            // Tooltips
            $$('.[data-bs-toggle="tooltip"]').forEach(el=> new bootstrap.Tooltip(el));

            // Preview nome permesso (chips)
            const nameInput   = $('#permName');
            const namePreview = $('#namePreview');
            function updateNamePreview(){
                const raw = (nameInput?.value || '').trim().toLowerCase();
                if (!namePreview) return;
                const parts = raw.split('.').filter(Boolean);
                namePreview.textContent = parts.length ? parts.join(' · ') : 'area · oggetto · azione';
            }
            nameInput?.addEventListener('input', updateNamePreview); updateNamePreview();

            // Ricerca + conteggio
            const searchBox = $('#searchBox');
            const clearBtn  = $('#clearSearch');
            const shownEl   = $('#shownCount');
            const tbody     = $('#permTable tbody');
            function applyFilter(){
                const q = (searchBox.value || '').toLowerCase().trim();
                let shown = 0;
                $$('#permTable tbody tr').forEach(tr=>{
                    const hay = (tr.dataset.name || '');
                    const match = !q || hay.includes(q);
                    tr.style.display = match ? '' : 'none';
                    if (match) shown++;
                });
                if (shownEl) shownEl.textContent = shown;
            }
            searchBox?.addEventListener('input', applyFilter);
            clearBtn?.addEventListener('click', ()=>{ searchBox.value=''; applyFilter(); });
            applyFilter();

            // Ordinamento client-side
            const sortSel = $('#sortSelect');
            function sortRows(){
                if (!tbody || !sortSel) return;
                const val = sortSel.value;
                const rows = $$('#permTable tbody tr').slice(); // clone
                const by = (a,b, key, dir='asc')=>{
                    const av = (a.dataset[key]||'').toString();
                    const bv = (b.dataset[key]||'').toString();
                    if (av < bv) return dir==='asc' ? -1 : 1;
                    if (av > bv) return dir==='asc' ? 1 : -1;
                    return 0;
                };
                rows.sort((a,b)=>{
                    if (val==='name_asc')  return by(a,b,'name','asc');
                    if (val==='name_desc') return by(a,b,'name','desc');
                    if (val==='group_asc') return by(a,b,'group','asc') || by(a,b,'name','asc');
                    if (val==='group_desc')return by(a,b,'group','desc')|| by(a,b,'name','asc');
                    return 0;
                });
                rows.forEach(r => tbody.appendChild(r));
            }
            sortSel?.addEventListener('change', sortRows);
            sortRows();

            // Copia nome permesso
            document.addEventListener('click', async (e)=>{
                const btn = e.target.closest('.copy-btn');
                if(!btn) return;
                const txt = btn.dataset.copy || '';
                try{
                    await navigator.clipboard.writeText(txt);
                    const tip = bootstrap.Tooltip.getInstance(btn) || new bootstrap.Tooltip(btn, {title:'Copiato!', trigger:'manual'});
                    btn.setAttribute('data-bs-original-title', 'Copiato!');
                    tip.show(); setTimeout(()=> tip.hide(), 800);
                }catch(_){}
            });

            // Conferma eliminazione (modal riutilizzabile)
            let pendingFormId = null;
            const modalEl = $('#confirmDeleteModal');
            const nameEl  = $('#delPermName');
            const confirmBtn = $('#confirmDeleteBtn');

            document.addEventListener('click', (e)=>{
                const ask = e.target.closest('[data-action="ask-delete"]');
                if(!ask) return;
                pendingFormId = ask.dataset.form;
                const nm = ask.dataset.name || '';
                if (nameEl) nameEl.textContent = nm;
                const m = new bootstrap.Modal(modalEl);
                m.show();
            });
            confirmBtn?.addEventListener('click', ()=>{
                if (!pendingFormId) return;
                document.getElementById(pendingFormId)?.submit();
            });
        })();
    </script>
@endsection
