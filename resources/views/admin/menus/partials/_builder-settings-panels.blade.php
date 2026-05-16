@php
    /** @var \App\Models\Menu $menu */
    $cfg = old('settings', $menu->settings ?? []);
    $locations = ['header','footer','sidebar','primary','secondary','mobile'];
    $fonts = ['system-ui','Inter','Roboto','Montserrat','Poppins','Georgia','serif','monospace'];
    $weights = ['400','500','600','700','800'];
    $alignments = ['left','center','right'];
    $isActive = old('is_active', $menu->is_active ?? false);
@endphp

<div class="r4-nav-style-panel is-active" data-r4-nav-style-panel="general">
    <div class="r4-settings-section">
        <div class="r4-settings-section__head">
            <span class="r4-settings-section__icon"><i class="bi bi-card-checklist"></i></span>
            <div><h3>Dati menu</h3><p>Identificazione, posizione e stato del menu.</p></div>
        </div>
        <div class="r4-settings-grid r4-settings-grid--2">
            <div class="r4-field-card"><label class="form-label">Nome *</label><div class="input-group"><span class="input-group-text"><i class="bi bi-card-text"></i></span><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $menu->name ?? '') }}" required autofocus placeholder="es. Menu principale"></div><div class="form-text">Nome amministrativo, visibile solo nel pannello.</div>@error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror</div>
            <div class="r4-field-card"><label class="form-label">Slug *</label><div class="input-group"><span class="input-group-text"><i class="bi bi-hash"></i></span><input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $menu->slug ?? '') }}" required data-autofill="{{ ($menu ?? null)?->exists ? '0' : '1' }}" placeholder="es. header"></div><div class="d-flex align-items-center gap-2 mt-1 flex-wrap"><small class="text-muted">Solo lettere, numeri e trattini.</small><span class="badge rounded-pill text-bg-light border" id="slugPreview">slug: <code class="ms-1">{{ old('slug', $menu->slug ?? '') }}</code></span></div>@error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror</div>
            <div class="r4-field-card"><label class="form-label">Location</label><div class="input-group"><span class="input-group-text"><i class="bi bi-geo-alt"></i></span><input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $menu->location ?? '') }}" placeholder="es. header" list="menuLocations"></div><datalist id="menuLocations">@foreach($locations as $location)<option value="{{ $location }}"></option>@endforeach</datalist><div class="form-text">Posizione frontend in cui caricare il menu.</div>@error('location') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror</div>
            <div class="r4-field-card r4-field-card--state"><label class="form-label">Stato</label><div class="r4-toggle-card r4-toggle-card--compact"><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked($isActive)><label class="form-check-label" for="is_active">Menu attivo</label></div><span class="badge rounded-pill" id="isActiveBadge">@if($isActive)<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> attivo</span>@else<span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i> disattivo</span>@endif</span></div><div class="form-text">Se disattivo, il menu non verrà esposto nel frontend.</div></div>
        </div>
    </div>
</div>

<div class="r4-nav-style-panel" data-r4-nav-style-panel="style" hidden>
    <div class="r4-settings-section">
        <div class="r4-settings-section__head"><span class="r4-settings-section__icon"><i class="bi bi-palette2"></i></span><div><h3>Stile visuale</h3><p>Dimensioni, logo, tipografia e colori del menu.</p></div></div>
        <div class="r4-settings-grid r4-settings-grid--4">
            <div class="r4-field-card"><label class="form-label">Altezza header</label><input type="number" min="40" max="220" class="form-control" name="settings[header_height]" value="{{ $cfg['header_height'] ?? 76 }}"><div class="form-text">Altezza minima in px.</div></div>
            <div class="r4-field-card"><label class="form-label">Altezza logo</label><input type="number" min="16" max="160" class="form-control" name="settings[logo_height]" value="{{ $cfg['logo_height'] ?? 28 }}"><div class="form-text">Dimensione visibile logo.</div></div>
            <div class="r4-field-card"><label class="form-label">Sfondo iniziale</label><input type="color" class="form-control form-control-color" name="settings[bg_color]" value="{{ $cfg['bg_color'] ?? '#ffffff' }}"><div class="form-text">Colore header normale.</div></div>
            <div class="r4-field-card"><label class="form-label">Colori link</label><div class="r4-color-duo"><span>Normale</span><input type="color" class="form-control form-control-color" name="settings[link_color]" value="{{ $cfg['link_color'] ?? '#111827' }}"><span>Hover</span><input type="color" class="form-control form-control-color" name="settings[link_color_hover]" value="{{ $cfg['link_color_hover'] ?? '#0d6efd' }}"></div></div>
        </div>
        <div class="r4-settings-grid r4-settings-grid--4 mt-3">
            <div class="r4-field-card"><label class="form-label">Font</label>@php($ff = $cfg['font_family'] ?? 'system-ui')<select class="form-select" name="settings[font_family]">@foreach($fonts as $f)<option value="{{ $f }}" @selected($ff===$f)>{{ $f }}</option>@endforeach</select></div>
            <div class="r4-field-card"><label class="form-label">Dimensione</label><input type="number" min="10" max="72" class="form-control" name="settings[font_size]" value="{{ $cfg['font_size'] ?? 16 }}"></div>
            <div class="r4-field-card"><label class="form-label">Peso</label>@php($fw = (string)($cfg['font_weight'] ?? '600'))<select class="form-select" name="settings[font_weight]">@foreach($weights as $w)<option value="{{ $w }}" @selected($fw===$w)>{{ $w }}</option>@endforeach</select></div>
            <div class="r4-field-card d-flex align-items-end"><div class="form-check form-switch m-0">@php($fs = $cfg['font_style'] ?? 'normal')<input class="form-check-input" type="checkbox" id="fsItalic" name="settings[font_style]" value="italic" {{ $fs==='italic'?'checked':'' }}><label class="form-check-label" for="fsItalic">Testo italic</label></div></div>
        </div>
        <div class="r4-settings-grid r4-settings-grid--2 mt-3">
            <div class="r4-field-card"><label class="form-label">Sfondo voce primaria</label>@php($ibm = $cfg['item_bg_mode'] ?? 'transparent')<div class="r4-radio-row"><label><input type="radio" name="settings[item_bg_mode]" value="transparent" {{ $ibm==='transparent'?'checked':'' }}> Trasparente</label><label><input type="radio" name="settings[item_bg_mode]" value="color" {{ $ibm==='color'?'checked':'' }}> Colore</label><input type="color" class="form-control form-control-color" name="settings[item_bg_color]" value="{{ $cfg['item_bg_color'] ?? '#ffffff' }}" id="itemBgColor"></div></div>
            <div class="r4-field-card"><label class="form-label">Sfondo sottomenu</label>@php($sbm = $cfg['sub_bg_mode'] ?? 'color')<div class="r4-radio-row"><label><input type="radio" name="settings[sub_bg_mode]" value="transparent" {{ $sbm==='transparent'?'checked':'' }}> Trasparente</label><label><input type="radio" name="settings[sub_bg_mode]" value="color" {{ $sbm==='color'?'checked':'' }}> Colore</label><input type="color" class="form-control form-control-color" name="settings[sub_bg_color]" value="{{ $cfg['sub_bg_color'] ?? '#ffffff' }}" id="subBgColor"></div></div>
        </div>
    </div>
</div>

<div class="r4-nav-style-panel" data-r4-nav-style-panel="behavior" hidden>
    <div class="r4-settings-section">
        <div class="r4-settings-section__head"><span class="r4-settings-section__icon"><i class="bi bi-arrows-move"></i></span><div><h3>Comportamento & spaziatura</h3><p>Sticky, stato scroll e distanza dal primo blocco pagina.</p></div></div>
        <div class="r4-settings-grid r4-settings-grid--3">
            <div class="r4-toggle-card"><div><strong>Fisso in alto</strong><span>Blocca il menu in alto durante lo scroll.</span></div><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="settings[is_sticky]" value="1" {{ ($cfg['is_sticky'] ?? false) ? 'checked' : '' }}></div></div>
            <div class="r4-field-card"><label class="form-label">Quando scorro</label>@php($mode = $cfg['scrolled_mode'] ?? 'transparent')<div class="r4-radio-row"><label><input type="radio" name="settings[scrolled_mode]" value="transparent" {{ $mode==='transparent'?'checked':'' }}> Trasparente</label><label><input type="radio" name="settings[scrolled_mode]" value="color" {{ $mode==='color'?'checked':'' }}> Colore</label><input type="color" class="form-control form-control-color" name="settings[scrolled_bg_color]" value="{{ $cfg['scrolled_bg_color'] ?? '#ffffff' }}" id="scrolledColorWrap"></div></div>
            <div class="r4-toggle-card r4-toggle-card--featured"><div><strong>Elimina spazio</strong><span>Rimuove il distacco tra menu e primo blocco.</span></div><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="settings[remove_first_gap]" value="1" id="removeFirstGap" {{ ($cfg['remove_first_gap'] ?? false) ? 'checked' : '' }}></div></div>
        </div>
        <div class="r4-settings-grid r4-settings-grid--2 mt-3">
            <div class="r4-field-card"><label class="form-label">Spazio sotto menu</label><input type="number" min="0" max="240" class="form-control" name="settings[bottom_gap]" value="{{ $cfg['bottom_gap'] ?? 0 }}"><div class="form-text">Distanza positiva tra header e primo blocco.</div></div>
            <div class="r4-field-card"><label class="form-label">Offset primo blocco</label><input type="number" min="-240" max="240" class="form-control" name="settings[first_block_offset]" value="{{ $cfg['first_block_offset'] ?? 0 }}"><div class="form-text">Valori negativi avvicinano il contenuto.</div></div>
        </div>
    </div>
</div>

<div class="r4-nav-style-panel" data-r4-nav-style-panel="mobile" hidden>
    <div class="r4-settings-section">
        <div class="r4-settings-section__head"><span class="r4-settings-section__icon"><i class="bi bi-phone"></i></span><div><h3>Mobile menu</h3><p>Modalità mobile applicata al frontend pubblico.</p></div></div>
        <div class="r4-settings-grid r4-settings-grid--2">
            <div class="r4-field-card"><label class="form-label">Modalità mobile</label>@php($mobileMode = $cfg['mobile_mode'] ?? 'collapse')<select class="form-select" name="settings[mobile_mode]"><option value="collapse" @selected($mobileMode==='collapse')>Collapse Bootstrap</option><option value="offcanvas" @selected($mobileMode==='offcanvas')>Offcanvas laterale</option><option value="fullscreen" @selected($mobileMode==='fullscreen')>Fullscreen menu</option></select><div class="form-text">La scelta viene applicata al menu pubblico dopo il salvataggio.</div></div>
            <div class="r4-field-card"><label class="form-label">Modalità disponibili</label><div class="r4-mobile-note">Collapse mantiene il comportamento Bootstrap. Offcanvas apre un pannello laterale. Fullscreen apre il menu a schermo intero.</div></div>
        </div>
    </div>
</div>

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
