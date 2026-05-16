@php
    /** @var \App\Models\Menu|null $menu */
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nome *</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-card-text"></i></span>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $menu->name ?? '') }}" required autofocus placeholder="es. Menu principale">
        </div>
        <div class="form-text">Il nome amministrativo del menu.</div>
        @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Slug *</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $menu->slug ?? '') }}" required data-autofill="{{ ($menu ?? null)?->exists ? '0' : '1' }}" placeholder="es. header, footer, menu-principale">
        </div>
        <div class="d-flex align-items-center gap-2 mt-1">
            <small class="text-muted">Solo lettere, numeri e trattini.</small>
            <span class="badge rounded-pill text-bg-light border" id="slugPreview">slug: <code class="ms-1">{{ old('slug', $menu->slug ?? '') }}</code></span>
        </div>
        @error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Location <span class="text-muted">(opzionale)</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $menu->location ?? '') }}" placeholder="es. header, footer, sidebar" list="menuLocations">
        </div>
        <datalist id="menuLocations">
            <option value="header"></option>
            <option value="footer"></option>
            <option value="sidebar"></option>
            <option value="primary"></option>
            <option value="secondary"></option>
            <option value="mobile"></option>
        </datalist>
        <div class="form-text">Usata per caricare il menu in quella posizione.</div>
        @error('location') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label">Stato</label>
            <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', ($menu->is_active ?? false)))>
                    <label class="form-check-label" for="is_active">Attivo</label>
                </div>
                <span class="badge rounded-pill" id="isActiveBadge">
                    @if(old('is_active', ($menu->is_active ?? false)))
                        <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> attivo</span>
                    @else
                        <span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i> disattivo</span>
                    @endif
                </span>
            </div>
            <div class="form-text">Se disattivo, il menu non verrà esposto nel frontend.</div>
        </div>
    </div>
</div>

<div class="col-12">
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-sliders"></i>
            <strong>Aspetto &amp; comportamento</strong>
        </div>

        @php($cfg = old('settings', $menu->settings ?? []))

        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Fisso in alto</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="settings[is_sticky]" value="1" {{ ($cfg['is_sticky'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Sticky top</label>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Sfondo iniziale</label>
                <input type="color" class="form-control form-control-color" name="settings[bg_color]" value="{{ $cfg['bg_color'] ?? '#ffffff' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Quando scorro</label>
                @php($mode = $cfg['scrolled_mode'] ?? 'transparent')
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[scrolled_mode]" id="scm_tr" value="transparent" {{ $mode==='transparent'?'checked':'' }}>
                        <label class="form-check-label" for="scm_tr">Trasparente</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[scrolled_mode]" id="scm_col" value="color" {{ $mode==='color'?'checked':'' }}>
                        <label class="form-check-label" for="scm_col">Colore</label>
                    </div>
                    <div class="d-flex align-items-center gap-2" id="scrolledColorWrap">
                        <span class="text-muted small">Colore</span>
                        <input type="color" class="form-control form-control-color" name="settings[scrolled_bg_color]" value="{{ $cfg['scrolled_bg_color'] ?? '#ffffff' }}">
                    </div>
                </div>
            </div>

            <div class="col-12"><hr class="my-2"><div class="text-uppercase text-muted small fw-bold">Spaziatura header / primo blocco</div></div>

            <div class="col-md-3">
                <label class="form-label">Altezza header (px)</label>
                <input type="number" min="40" max="220" class="form-control" name="settings[header_height]" value="{{ $cfg['header_height'] ?? 76 }}">
                <div class="form-text">Altezza minima del menu/header.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Altezza logo (px)</label>
                <input type="number" min="16" max="160" class="form-control" name="settings[logo_height]" value="{{ $cfg['logo_height'] ?? 28 }}">
                <div class="form-text">Dimensione visibile del logo nel menu.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Spazio sotto menu (px)</label>
                <input type="number" min="0" max="240" class="form-control" name="settings[bottom_gap]" value="{{ $cfg['bottom_gap'] ?? 0 }}">
                <div class="form-text">Distanza tra menu e primo blocco.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Offset primo blocco (px)</label>
                <input type="number" min="-240" max="240" class="form-control" name="settings[first_block_offset]" value="{{ $cfg['first_block_offset'] ?? 0 }}">
                <div class="form-text">Valori negativi avvicinano il primo blocco.</div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch border rounded p-3 w-100">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="settings[remove_first_gap]" value="1" id="removeFirstGap" {{ ($cfg['remove_first_gap'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="removeFirstGap">Elimina spazio tra menu e primo blocco</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Font</label>
                <select class="form-select" name="settings[font_family]">
                    @php($ff = $cfg['font_family'] ?? 'system-ui')
                    @foreach(['system-ui','Inter','Roboto','Montserrat','Poppins','Georgia','serif','monospace'] as $f)
                        <option value="{{ $f }}" @selected($ff===$f)>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dimensione (px)</label>
                <input type="number" min="10" max="72" class="form-control" name="settings[font_size]" value="{{ $cfg['font_size'] ?? 16 }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Peso</label>
                @php($fw = (string)($cfg['font_weight'] ?? '600'))
                <select class="form-select" name="settings[font_weight]">
                    @foreach(['400','500','600','700','800'] as $w)
                        <option value="{{ $w }}" @selected($fw===$w)>{{ $w }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    @php($fs = $cfg['font_style'] ?? 'normal')
                    <input class="form-check-input" type="checkbox" id="fsItalic" name="settings[font_style]" value="italic" {{ $fs==='italic'?'checked':'' }}>
                    <label class="form-check-label" for="fsItalic">Italic</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Allineamento testo link</label>
                @php($ta = $cfg['text_align'] ?? 'left')
                <select class="form-select" name="settings[text_align]">
                    @foreach(['left','center','right'] as $a)
                        <option value="{{ $a }}" @selected($ta===$a)>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Posizione barra</label>
                @php($na = $cfg['nav_align'] ?? 'left')
                <select class="form-select" name="settings[nav_align]">
                    @foreach(['left','center','right'] as $a)
                        <option value="{{ $a }}" @selected($na===$a)>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Colore link (hover)</label>
                <div class="d-flex gap-2">
                    <input type="color" class="form-control form-control-color" name="settings[link_color]" value="{{ $cfg['link_color'] ?? '#111827' }}">
                    <input type="color" class="form-control form-control-color" name="settings[link_color_hover]" value="{{ $cfg['link_color_hover'] ?? '#0d6efd' }}">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Sfondo voce primaria</label>
                @php($ibm = $cfg['item_bg_mode'] ?? 'transparent')
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[item_bg_mode]" id="ibm_tr" value="transparent" {{ $ibm==='transparent'?'checked':'' }}>
                        <label class="form-check-label" for="ibm_tr">Trasparente</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[item_bg_mode]" id="ibm_col" value="color" {{ $ibm==='color'?'checked':'' }}>
                        <label class="form-check-label" for="ibm_col">Colore</label>
                    </div>
                    <input type="color" class="form-control form-control-color" name="settings[item_bg_color]" value="{{ $cfg['item_bg_color'] ?? '#ffffff' }}" id="itemBgColor">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Sfondo sottomenu</label>
                @php($sbm = $cfg['sub_bg_mode'] ?? 'color')
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[sub_bg_mode]" id="sbm_tr" value="transparent" {{ $sbm==='transparent'?'checked':'' }}>
                        <label class="form-check-label" for="sbm_tr">Trasparente</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="settings[sub_bg_mode]" id="sbm_col" value="color" {{ $sbm==='color'?'checked':'' }}>
                        <label class="form-check-label" for="sbm_col">Colore</label>
                    </div>
                    <input type="color" class="form-control form-control-color" name="settings[sub_bg_color]" value="{{ $cfg['sub_bg_color'] ?? '#ffffff' }}" id="subBgColor">
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>#slugPreview code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}</style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const nameInput=document.querySelector('input[name="name"]');
                const slugInput=document.querySelector('input[name="slug"]');
                const canAutofill=slugInput&&(slugInput.dataset.autofill==='1');
                const slugPrev=document.getElementById('slugPreview');
                const activeChk=document.getElementById('is_active');
                const activeBadge=document.getElementById('isActiveBadge');
                function slugify(str){return(str||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)+/g,'')}
                function updateSlugPreview(){if(!slugPrev||!slugInput)return;const val=slugInput.value||'';slugPrev.innerHTML='slug: <code class="ms-1">'+(val||'—')+'</code>'}
                function updateActiveBadge(){if(!activeBadge||!activeChk)return;activeBadge.innerHTML=activeChk.checked?'<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> attivo</span>':'<span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i> disattivo</span>'}
                if(canAutofill&&nameInput&&slugInput&&!slugInput.value){nameInput.addEventListener('input',()=>{if(!slugInput.value){slugInput.value=slugify(nameInput.value);updateSlugPreview()}})}
                slugInput?.addEventListener('input',()=>{const cleaned=slugify(slugInput.value);if(slugInput.value!==cleaned)slugInput.value=cleaned;updateSlugPreview()});
                activeChk?.addEventListener('change',updateActiveBadge);updateSlugPreview();updateActiveBadge();
            });
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        (function(){
            const byId=id=>document.getElementById(id);
            function toggleWrap(radios,wrapId){const show=Array.from(document.querySelectorAll(`[name="${radios}"]`)).some(r=>r.checked&&r.value==='color');const el=byId(wrapId);if(el){el.style.display=show?'':'none'}}
            document.addEventListener('change',e=>{
                if(e.target.name==='settings[scrolled_mode]')toggleWrap('settings[scrolled_mode]','scrolledColorWrap');
                if(e.target.name==='settings[item_bg_mode]')byId('itemBgColor')?.closest('div').style.setProperty('display',Array.from(document.querySelectorAll('[name="settings[item_bg_mode]"]')).some(r=>r.checked&&r.value==='color')?'':'none');
                if(e.target.name==='settings[sub_bg_mode]')byId('subBgColor')?.closest('div').style.setProperty('display',Array.from(document.querySelectorAll('[name="settings[sub_bg_mode]"]')).some(r=>r.checked&&r.value==='color')?'':'none');
            });
            toggleWrap('settings[scrolled_mode]','scrolledColorWrap');
        })();
    </script>
@endpush
