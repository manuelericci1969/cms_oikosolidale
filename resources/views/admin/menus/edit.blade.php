@extends('admin.layout')
@section('title', 'Navigation Menu Builder')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/navigation-menu-builder-pro/menu-builder.css') }}?v={{ @filemtime(public_path('assets/admin/navigation-menu-builder-pro/menu-builder.css')) ?: time() }}">
@endpush

@section('content')
    @php
        $allParents = $menu->allItems()->select('id','title','parent_id','type','url','target','order','is_active')->orderBy('order')->get();
        $topLevel = $allParents->whereNull('parent_id');
        $cfg = $menu->settings ?? [];
        $previewBg = $cfg['bg_color'] ?? '#ffffff';
        $previewText = $cfg['link_color'] ?? '#111827';
        $previewHover = $cfg['link_color_hover'] ?? '#0d6efd';
        $previewSize = (int)($cfg['font_size'] ?? 16);
        $previewWeight = $cfg['font_weight'] ?? '600';
    @endphp

    <div class="r4-nav-builder-pro">
        <div class="r4-nav-builder-hero">
            <div class="r4-nav-builder-hero__inner">
                <div>
                    <span class="r4-nav-builder-kicker"><i class="bi bi-menu-button-wide-fill"></i> Navigation Builder</span>
                    <h1 class="r4-nav-builder-title">{{ $menu->name }}</h1>
                    <p class="r4-nav-builder-subtitle">Gestisci struttura, voci, sottomenu, comportamento e stile del menu di navigazione del sito.</p>
                </div>
                <div class="r4-nav-builder-status">
                    <span>Slug <strong>{{ $menu->slug }}</strong></span>
                    <span>Location <strong>{{ $menu->location ?: '—' }}</strong></span>
                    <span>Stato <strong>{{ $menu->is_active ? 'Attivo' : 'Bozza' }}</strong></span>
                    <span>Voci <strong>{{ $allParents->count() }}</strong></span>
                </div>
            </div>
        </div>

        @if(session('ok'))
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check2-circle me-2"></i>
                <div>{{ session('ok') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Si sono verificati degli errori:</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="r4-nav-builder-quick-actions">
            <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Torna ai menu</a>
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Preview sito</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-action="ask-delete" data-form="delMenuForm" data-name="menu «{{ $menu->name }}»"><i class="bi bi-trash me-1"></i> Elimina menu</button>
            <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" id="delMenuForm" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>

        <div class="r4-nav-builder-grid">
            <div class="d-grid gap-3">
                <div class="r4-nav-builder-panel">
                    <div class="r4-nav-builder-panel__head">
                        <div>
                            <h2 class="r4-nav-builder-panel__title"><i class="bi bi-diagram-3"></i> Struttura menu</h2>
                            <p class="r4-nav-builder-panel__hint">Aggiungi, modifica e organizza le voci. Per ora l’ordine si gestisce con il campo Ordine.</p>
                        </div>
                        <span class="badge text-bg-light border">{{ $menu->items->count() }} principali</span>
                    </div>

                    <div class="r4-nav-builder-panel__body">
                        <div class="r4-nav-create-card">
                            <div class="r4-nav-create-card__head">
                                <div>
                                    <h3><i class="bi bi-plus-square"></i> Aggiungi voce</h3>
                                    <p>Crea una voce menu e definisci collegamento, gerarchia e comportamento.</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}" class="r4-nav-create-form">
                                @csrf

                                <div class="r4-nav-create-grid r4-nav-create-grid--top">
                                    <div class="r4-field-card">
                                        <label class="form-label">Titolo *</label>
                                        <input type="text" name="title" class="form-control" placeholder="Titolo voce" value="{{ old('title') }}" required>
                                        <div class="form-text">Nome visibile nel menu.</div>
                                    </div>
                                    <div class="r4-field-card">
                                        <label class="form-label">URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                            <input type="text" name="url" class="form-control" placeholder="/percorso | https:// | #" value="{{ old('url') }}" list="urlHints">
                                        </div>
                                        <div class="form-text">Percorso interno, link esterno, ancora, mailto o telefono.</div>
                                    </div>
                                </div>

                                <div class="r4-nav-create-grid r4-nav-create-grid--middle">
                                    <div class="r4-field-card">
                                        <label class="form-label">Parent</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">— Root —</option>
                                            @foreach($topLevel as $p)
                                                <option value="{{ $p->id }}" @selected(old('parent_id') == $p->id)>{{ $p->title }}</option>
                                                @foreach($allParents->where('parent_id', $p->id) as $child)
                                                    <option value="{{ $child->id }}" @selected(old('parent_id') == $child->id)>— {{ $child->title }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="r4-field-card">
                                        <label class="form-label">Ordine</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}" placeholder="0">
                                    </div>
                                    <div class="r4-field-card">
                                        <label class="form-label">Tipo</label>
                                        <select name="type" class="form-select">
                                            <option value="link">Link</option>
                                            <option value="separator">Separatore</option>
                                        </select>
                                    </div>
                                    <div class="r4-field-card">
                                        <label class="form-label">Target</label>
                                        <select name="target" class="form-select">
                                            <option value="">Default</option>
                                            <option value="_self" @selected(old('target') == '_self')>Stessa scheda</option>
                                            <option value="_blank" @selected(old('target') == '_blank')>Nuova scheda</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="r4-nav-create-actions">
                                    <div class="r4-toggle-card r4-toggle-card--compact">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="item_active" checked>
                                            <label class="form-check-label" for="item_active">Voce attiva</label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-lg"><i class="bi bi-plus-lg me-1"></i> Aggiungi voce</button>
                                </div>
                            </form>
                        </div>

                        <datalist id="urlHints">
                            <option value="/"></option>
                            <option value="#"></option>
                            <option value="https://"></option>
                            <option value="http://"></option>
                            <option value="mailto:"></option>
                            <option value="tel:"></option>
                        </datalist>

                        <div class="r4-nav-entries">
                            <div class="r4-nav-entries__head">
                                <h3><i class="bi bi-list-ul"></i> Voci menu</h3>
                                <span>{{ $allParents->count() }} elementi totali</span>
                            </div>

                            @if($menu->items->count())
                                @foreach($menu->items as $item)
                                    @php
                                        $blocked = collect([$item->id])->merge($item->children->pluck('id'));
                                        $itemType = $item->type ?? 'link';
                                    @endphp

                                    <div class="r4-nav-entry-card">
                                        <div class="r4-nav-entry-card__main">
                                            <div class="r4-nav-entry-card__left">
                                                <div class="r4-nav-entry-card__title">
                                                    <i class="bi bi-grip-vertical"></i>
                                                    <strong>{{ $item->title }}</strong>
                                                    @unless($item->is_active)
                                                        <span class="r4-chip r4-chip--muted">Disattiva</span>
                                                    @endunless
                                                    @if($itemType === 'separator')
                                                        <span class="r4-chip r4-chip--warning">Separatore</span>
                                                    @endif
                                                </div>

                                                <div class="r4-nav-entry-card__meta">
                                                    @if($itemType !== 'separator')
                                                        <span class="r4-chip"><i class="bi bi-link-45deg"></i> {{ $item->url ?: ($item->page?->getUrl() ?? '#') }}</span>
                                                    @endif
                                                    <span class="r4-chip"><i class="bi bi-sort-numeric-down"></i> Ordine {{ $item->order ?? 0 }}</span>
                                                    @if($item->target === '_blank')
                                                        <span class="r4-chip r4-chip--info">Nuova scheda</span>
                                                    @endif
                                                    @if($item->children->count())
                                                        <span class="r4-chip r4-chip--info"><i class="bi bi-diagram-2"></i> {{ $item->children->count() }} sotto-voci</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="r4-nav-entry-card__actions">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}"><i class="bi bi-pencil-square"></i></button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-action="ask-delete" data-form="delItemForm_{{ $item->id }}" data-name="voce «{{ $item->title }}»"><i class="bi bi-trash"></i></button>
                                                <form method="POST" action="{{ route('admin.menus.items.destroy', $item) }}" id="delItemForm_{{ $item->id }}" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </div>

                                        @if($item->children->count())
                                            <div class="r4-nav-entry-card__children">
                                                @foreach($item->children as $child)
                                                    @php($childType = $child->type ?? 'link')
                                                    <div class="r4-nav-child-entry">
                                                        <div class="r4-nav-child-entry__left">
                                                            <strong><i class="bi bi-arrow-return-right"></i> {{ $child->title }}</strong>
                                                            <div class="r4-nav-entry-card__meta">
                                                                @if($childType !== 'separator')
                                                                    <span class="r4-chip"><i class="bi bi-link-45deg"></i> {{ $child->url ?: ($child->page?->getUrl() ?? '#') }}</span>
                                                                @endif
                                                                <span class="r4-chip"><i class="bi bi-sort-numeric-down"></i> Ordine {{ $child->order ?? 0 }}</span>
                                                                @unless($child->is_active)
                                                                    <span class="r4-chip r4-chip--muted">Disattiva</span>
                                                                @endunless
                                                            </div>
                                                        </div>
                                                        <div class="r4-nav-entry-card__actions">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $child->id }}"><i class="bi bi-pencil-square"></i></button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm" data-action="ask-delete" data-form="delItemForm_{{ $child->id }}" data-name="voce «{{ $child->title }}»"><i class="bi bi-trash"></i></button>
                                                            <form method="POST" action="{{ route('admin.menus.items.destroy', $child) }}" id="delItemForm_{{ $child->id }}" class="d-none">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @include('admin.menus.partials._item-modal', ['menu' => $menu, 'item' => $item, 'allParents' => $allParents, 'topLevel' => $topLevel, 'blocked' => $blocked])
                                    @foreach($item->children as $child)
                                        @include('admin.menus.partials._item-modal', ['menu' => $menu, 'item' => $child, 'allParents' => $allParents, 'topLevel' => $topLevel, 'blocked' => collect([$child->id])])
                                    @endforeach
                                @endforeach
                            @else
                                <div class="alert alert-secondary d-flex align-items-center mb-0">
                                    <i class="bi bi-inboxes me-2"></i> Nessuna voce presente nel menu.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="r4-nav-builder-preview">
                <div class="r4-nav-builder-panel">
                    <div class="r4-nav-builder-panel__head">
                        <div>
                            <h2 class="r4-nav-builder-panel__title"><i class="bi bi-eye"></i> Preview menu</h2>
                            <p class="r4-nav-builder-panel__hint">Anteprima live basata sui controlli stile correnti.</p>
                        </div>
                    </div>
                    <div class="r4-nav-preview-frame">
                        <div class="r4-nav-preview-header" style="--preview-bg:{{ $previewBg }};--preview-text:{{ $previewText }};--preview-hover:{{ $previewHover }};--preview-size:{{ $previewSize }}px;--preview-weight:{{ $previewWeight }};">
                            <div class="r4-nav-preview-logo"><span class="r4-nav-preview-logo__mark">R4</span><span>R4Software</span></div>
                            <ul class="r4-nav-preview-menu">
                                @forelse($menu->items as $previewItem)
                                    @if($previewItem->is_active)
                                        <li><a href="#">{{ $previewItem->title }}</a></li>
                                    @endif
                                @empty
                                    <li><span>Home</span></li>
                                    <li><span>Servizi</span></li>
                                    <li><span>Contatti</span></li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="r4-nav-preview-device">
                            <p class="r4-nav-preview-note">Preview live attiva: le modifiche a colori, font e allineamento si riflettono subito qui.</p>
                        </div>
                    </div>
                </div>

                <div class="r4-nav-builder-panel">
                    <div class="r4-nav-builder-panel__head">
                        <div>
                            <h2 class="r4-nav-builder-panel__title"><i class="bi bi-sliders"></i> Impostazioni & stile</h2>
                            <p class="r4-nav-builder-panel__hint">Dati menu, posizione, tipografia, colori e comportamento.</p>
                        </div>
                    </div>
                    <div class="r4-nav-builder-panel__body">
                        <form method="POST" action="{{ route('admin.menus.update', $menu) }}">
                            @csrf
                            @method('PATCH')
                            <div class="r4-nav-style-tabs" role="tablist">
                                <button type="button" class="r4-nav-style-tab is-active" data-r4-nav-style-tab="general">Generale</button>
                                <button type="button" class="r4-nav-style-tab" data-r4-nav-style-tab="style">Stile</button>
                                <button type="button" class="r4-nav-style-tab" data-r4-nav-style-tab="behavior">Comport.</button>
                                <button type="button" class="r4-nav-style-tab" data-r4-nav-style-tab="mobile">Mobile</button>
                            </div>

                            @include('admin.menus.partials._builder-settings-panels', ['menu' => $menu])

                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva impostazioni</button>
                                <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">Annulla</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Conferma eliminazione</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">Vuoi davvero eliminare <span class="fw-semibold" id="whatToDelete">questo elemento</span>?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="bi bi-trash3 me-1"></i> Elimina</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/admin/navigation-menu-builder-pro/menu-builder.js') }}?v={{ @filemtime(public_path('assets/admin/navigation-menu-builder-pro/menu-builder.js')) ?: time() }}"></script>
@endpush
