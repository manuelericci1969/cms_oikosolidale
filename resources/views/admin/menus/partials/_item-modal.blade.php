@php
    /** @var \App\Models\MenuItem $item */
    /** @var \Illuminate\Support\Collection $allParents */
    /** @var \Illuminate\Support\Collection $topLevel */
    /** @var \Illuminate\Support\Collection $blocked */
    /** @var \Illuminate\Support\Collection|null $pages */
    $availablePages = isset($pages)
        ? $pages
        : \App\Models\Page::query()->orderBy('title')->get(['id', 'title', 'slug', 'status']);
@endphp

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/admin/navigation-menu-builder-pro/menu-modal-fix.css') }}?v={{ @filemtime(public_path('assets/admin/navigation-menu-builder-pro/menu-modal-fix.css')) ?: time() }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/admin/navigation-menu-builder-pro/menu-modal-fix.js') }}?v={{ @filemtime(public_path('assets/admin/navigation-menu-builder-pro/menu-modal-fix.js')) ?: time() }}"></script>
    @endpush
@endonce

<div class="modal fade modal-wide r4-nav-item-modal" id="editItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true" aria-labelledby="editItemLabel{{ $item->id }}">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="editItemLabel{{ $item->id }}">
                    <i class="bi bi-pencil-square me-1 text-primary"></i>
                    Modifica voce: {{ $item->title }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <form method="POST" action="{{ route('admin.menus.items.update', $item) }}" class="r4-nav-item-form">
                @csrf
                @method('PATCH')

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="modal-section-title">Dati base</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Titolo *</label>
                                    <input type="text" name="title" class="form-control" required value="{{ $item->title }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Pagina CMS</label>
                                    <select name="page_id" class="form-select" data-r4-nav-destination="page">
                                        <option value="">— Nessuna pagina —</option>
                                        @foreach($availablePages as $page)
                                            <option value="{{ $page->id }}" @selected((int) $item->page_id === (int) $page->id)>
                                                {{ $page->title }} /{{ $page->slug }} @if(($page->status ?? null) !== 'published') ({{ $page->status }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="help-hint mt-1">Usa questo campo per collegare una pagina del CMS senza scrivere manualmente l'URL.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">URL manuale</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                        <input type="text" name="url" class="form-control" value="{{ $item->getRawOriginal('url') }}" list="urlHints" placeholder="http(s):// | mailto: | tel: | /percorso | #ancora" data-r4-nav-destination="url">
                                    </div>
                                    <div class="help-hint mt-1">Scegli Pagina CMS oppure URL manuale, non entrambi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Icona CSS opzionale</label>
                                    <input type="text" name="icon" class="form-control" value="{{ $item->icon }}" placeholder="es. bi bi-house">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="modal-section-title">Posizionamento & comportamento</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Parent</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">— Nessun parent —</option>
                                        @foreach($topLevel as $p)
                                            @if(!$blocked->contains($p->id))
                                                <option value="{{ $p->id }}" @selected($item->parent_id == $p->id)>{{ $p->title }}</option>
                                            @endif
                                            @foreach($allParents->where('parent_id', $p->id) as $child)
                                                @if(!$blocked->contains($child->id))
                                                    <option value="{{ $child->id }}" @selected($item->parent_id == $child->id)>— {{ $child->title }}</option>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <div class="help-hint">Non puoi scegliere la voce stessa come parent.</div>
                                </div>

                                <div class="col-6">
                                    <label class="form-label">Ordine</label>
                                    <input type="number" name="order" class="form-control" value="{{ $item->order }}" inputmode="numeric">
                                </div>

                                <div class="col-6">
                                    <label class="form-label">Target</label>
                                    <select name="target" class="form-select">
                                        <option value="">Predef.</option>
                                        <option value="_self" @selected($item->target === '_self')>Self</option>
                                        <option value="_blank" @selected($item->target === '_blank')>Blank</option>
                                    </select>
                                </div>

                                <div class="col-6">
                                    <label class="form-label">Tipo</label>
                                    <select name="type" class="form-select">
                                        <option value="link" @selected(($item->type ?? 'link') === 'link')>Link</option>
                                        <option value="separator" @selected(($item->type ?? 'link') === 'separator')>Separatore</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $item->id }}" @checked($item->is_active)>
                                        <label class="form-check-label" for="active_{{ $item->id }}">Voce attiva</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Annulla
                    </button>
                    <button class="btn btn-primary">
                        <i class="bi bi-save2 me-1"></i> Salva modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
