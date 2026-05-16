@extends('admin.layout')
@section('title', 'Pagine')

@push('styles')

    <style>
        .page-toolbar { position: sticky; top: 0; z-index: 1010; background: var(--bs-body-bg); padding: .75rem 0; }
        .rounded-2xl { border-radius: 1rem; }
        .shadow-soft { box-shadow: 0 6px 14px rgba(0,0,0,.06); }
        .table-hover tbody tr:hover { background: rgba(0,0,0,.02); }
        .status-tab .nav-link { border-radius: 999px; }
        .search-pill { border-radius: 999px; padding-left: 2.5rem; }
        .search-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); opacity:.6; }
        .slug-chip { background: var(--bs-light); border:1px dashed var(--bs-border-color); padding:.15rem .5rem; border-radius: .5rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .action-gap .btn { margin-right:.4rem; }
        /* Card list (mobile) */
        @media (max-width: 767.98px){
            .table-responsive{ display:none; }
        }
        @media (min-width: 768px){
            .card-list-mobile{ display:none; }
        }
    </style>
@endpush

@section('content')

    {{-- Toolbar --}}
    <div class="page-toolbar mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Pagine</h1>
                <div class="small text-muted">Gestisci, filtra e ordina le pagine del sito</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.pages.create') }}" class="btn btn-primary rounded-pill shadow-soft">
                    <i class="bi bi-plus-lg me-1"></i> Nuova Pagina
                </a>
            </div>
        </div>
    </div>

    {{-- Tabs stato veloci --}}
    @php
        $st = request('status');
        $qs = request()->except('page', 'status');
    @endphp
    <div class="status-tab mb-3">
        <ul class="nav nav-pills gap-2">
            <li class="nav-item">
                <a class="nav-link {{ !$st ? 'active' : '' }}"
                   href="{{ route('admin.pages.index', $qs) }}">
                    Tutte
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $st==='published' ? 'active' : '' }}"
                   href="{{ route('admin.pages.index', array_merge($qs, ['status' => 'published'])) }}">
                    Pubblicate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $st==='draft' ? 'active' : '' }}"
                   href="{{ route('admin.pages.index', array_merge($qs, ['status' => 'draft'])) }}">
                    Bozze
                </a>
            </li>
        </ul>
    </div>

    {{-- Filtri --}}
    <div class="card mb-3 shadow-soft rounded-2xl">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5 position-relative">
                    <span class="search-icon"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control search-pill"
                           placeholder="Cerca titolo o slug..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select rounded-pill">
                        <option value="">Tutti gli stati</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Bozza</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Pubblicata</option>
                        {{-- Se non usi “archiviata”, elimina la riga seguente --}}
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archiviata</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select rounded-pill">
                        @php $sort = request('sort'); @endphp
                        <option value="">Ordina per…</option>
                        <option value="published_at_desc" {{ $sort==='published_at_desc'?'selected':'' }}>Più recenti</option>
                        <option value="published_at_asc"  {{ $sort==='published_at_asc'?'selected':'' }}>Meno recenti</option>
                        <option value="title_asc"         {{ $sort==='title_asc'?'selected':'' }}>Titolo A→Z</option>
                        <option value="title_desc"        {{ $sort==='title_desc'?'selected':'' }}>Titolo Z→A</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-secondary rounded-pill">Filtra</button>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Lista Pagine (desktop/tablet) --}}
    <div class="card shadow-soft rounded-2xl mb-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:36px;">
                        <input class="form-check-input" type="checkbox" id="checkAll">
                    </th>
                    <th>Titolo</th>
                    <th>Slug</th>
                    <th>Stato</th>
                    <th>Pubblicazione</th>
                    <th>Autore</th>
                    <th class="text-end" style="width:210px;">Azioni</th>
                </tr>
                </thead>
                <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td>
                            <input class="form-check-input row-check" type="checkbox" value="{{ $page->id }}">
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $page->title }}</div>
                            @if($page->excerpt)
                                <div class="text-muted small">{{ Str::limit($page->excerpt, 80) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="slug-chip me-1">{{ $page->slug }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill copy-btn"
                                    data-copy="{{ $page->slug }}" data-bs-toggle="tooltip" title="Copia slug">
                                copia
                            </button>
                        </td>
                        <td>
                            @if($page->status === 'published')
                                <span class="badge bg-success rounded-pill">Pubblicata</span>
                            @elseif($page->status === 'draft')
                                <span class="badge bg-warning text-dark rounded-pill">Bozza</span>
                            @else
                                <span class="badge bg-secondary rounded-pill">Archiviata</span>
                            @endif
                        </td>
                        <td>
                            @if($page->published_at)
                                <div class="small">
                                    <span class="fw-medium">{{ $page->published_at->format('d/m/Y H:i') }}</span>
                                    <div class="text-muted">{{ $page->published_at->diffForHumans() }}</div>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted small">{{ $page->creator?->name ?? '—' }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex action-gap">
                                <a href="{{ route('admin.pages.edit_v5', $page) }}"
                                   class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editor visuale">
                                    <i class="bi bi-magic me-1"></i> Editor
                                </a>

                                <a href="{{ $page->getUrl() }}"
                                   class="btn btn-sm btn-outline-secondary" target="_blank" data-bs-toggle="tooltip" title="Visualizza">
                                    <i class="bi bi-eye me-1"></i> Visualizza
                                </a>

                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        Altro
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.pages.edit', $page) }}">
                                                <i class="bi bi-pencil-square me-2"></i> Editor classico
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.pages.edit_v2', $page) }}">
                                                <i class="bi bi-layout-text-window-reverse me-2"></i> Editor V2
                                            </a>
                                        </li>

                                        @if(\Illuminate\Support\Facades\Route::has('admin.pages.edit_v3'))
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.pages.edit_v3', $page) }}">
                                                    <i class="bi bi-window-stack me-2"></i> Editor V3
                                                </a>
                                            </li>
                                        @endif

                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">
                                                @csrf
                                                <button class="dropdown-item" type="submit">
                                                    <i class="bi bi-files me-2"></i> Duplica
                                                </button>
                                            </form>
                                        </li>

                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                                  onsubmit="return confirm('Eliminare questa pagina?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit">
                                                    <i class="bi bi-trash me-2"></i> Elimina
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            Nessuna pagina trovata.
                            <a href="{{ route('admin.pages.create') }}" class="ms-1">Crea la prima!</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Card list (mobile) --}}
    <div class="card-list-mobile">
        @forelse($pages as $page)
            <div class="card mb-2 shadow-soft rounded-2xl">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <div class="fw-semibold">{{ $page->title }}</div>
                            @if($page->excerpt)
                                <div class="text-muted small">{{ Str::limit($page->excerpt, 90) }}</div>
                            @endif
                        </div>
                        <div>
                            @if($page->status === 'published')
                                <span class="badge bg-success rounded-pill">Pubblicata</span>
                            @elseif($page->status === 'draft')
                                <span class="badge bg-warning text-dark rounded-pill">Bozza</span>
                            @else
                                <span class="badge bg-secondary rounded-pill">Archiviata</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="slug-chip">{{ $page->slug }}</span>
                        <span class="small text-muted">
                            @if($page->published_at)
                                {{ $page->published_at->format('d/m/Y H:i') }} • {{ $page->published_at->diffForHumans() }}
                            @else — @endif
                        </span>
                        <span class="small text-muted">{{ $page->creator?->name ?? '—' }}</span>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('admin.pages.edit_v5', $page) }}" class="btn btn-sm btn-primary flex-fill">
                            <i class="bi bi-magic me-1"></i> Editor
                        </a>

                        <a href="{{ $page->getUrl() }}" class="btn btn-sm btn-outline-secondary flex-fill" target="_blank">
                            <i class="bi bi-eye me-1"></i> Apri
                        </a>

                        <div class="dropup flex-fill">
                            <button class="btn btn-sm btn-outline-dark w-100 dropdown-toggle" data-bs-toggle="dropdown">Altro</button>
                            <ul class="dropdown-menu dropdown-menu-end">

                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.pages.edit', $page) }}">
                                        <i class="bi bi-pencil-square me-2"></i> Editor classico
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.pages.edit_v2', $page) }}">
                                        <i class="bi bi-layout-text-window-reverse me-2"></i> Editor V2
                                    </a>
                                </li>

                                @if(\Illuminate\Support\Facades\Route::has('admin.pages.edit_v3'))
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.pages.edit_v3', $page) }}">
                                            <i class="bi bi-window-stack me-2"></i> Editor V3
                                        </a>
                                    </li>
                                @endif

                                <li><hr class="dropdown-divider"></li>

                                <li>
                                    <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">
                                            <i class="bi bi-files me-2"></i> Duplica
                                        </button>
                                    </form>
                                </li>

                                <li><hr class="dropdown-divider"></li>

                                <li>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                          onsubmit="return confirm('Eliminare questa pagina?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash me-2"></i> Elimina
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                Nessuna pagina trovata. <a href="{{ route('admin.pages.create') }}">Crea la prima!</a>
            </div>
        @endforelse
    </div>

    {{-- Paginazione --}}
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Mostrate {{ $pages->firstItem() }}–{{ $pages->lastItem() }} di {{ $pages->total() }}
        </div>
        <div>
            {{ $pages->withQueryString()->links() }}
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Bootstrap tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el)
        });

        // Seleziona tutti
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', () => {
                document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
            });
        }

        // Copia slug
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(btn.dataset.copy);
                    btn.innerText = 'copiato ✓';
                    setTimeout(() => btn.innerText = 'copia', 1000);
                } catch(e) {
                    alert('Impossibile copiare lo slug');
                }
            });
        });
    </script>
@endpush
