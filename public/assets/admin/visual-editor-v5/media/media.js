(function () {
    'use strict';

    const cfg = window.R4EditorV5Config || {};
    let selectedItem = null;
    let selectedItems = [];
    let cachedItems = [];
    let allItems = [];
    let pickerMode = 'default';
    let backgroundTarget = null;
    let pageBackgroundInput = null;

    function byId(id) { return id ? document.getElementById(id) : null; }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char];
        });
    }

    function absoluteUrl(value) {
        const raw = String(value || '').trim();
        if (!raw) return '';
        try { return new URL(raw, window.location.origin).toString(); }
        catch (error) { return raw; }
    }

    function mediaUrl(item) {
        const raw = item && (item.q75 || item.full || item.src || item.url || item.path || '') || '';
        return absoluteUrl(raw);
    }

    function mediaTitle(item) {
        return item && (item.alt || item.title || item.original_name || item.name || 'Immagine') || 'Immagine';
    }

    function mediaId(item) {
        return item && item.id ? String(item.id) : '';
    }

    function getEditor() { return window.R4EditorV5 || null; }
    function bgManager() { return window.R4V5BackgroundManager || null; }

    function itemKey(item) {
        return String(item && (item.id || item.url || item.src || item.full || item.q75 || mediaTitle(item)) || '');
    }

    function isSelected(item) {
        const key = itemKey(item);
        return selectedItems.some(function (selected) { return itemKey(selected) === key; });
    }

    function modeLabel() {
        if (pickerMode === 'background') return ' — modalità sfondo elemento';
        if (pickerMode === 'background-slider') return ' — modalità slider sfondo';
        if (pickerMode === 'page-background') return ' — modalità sfondo pagina';
        return '';
    }

    function updateSelectionInfo() {
        const info = byId('r4v5MediaSelectionInfo');
        if (!info) return;
        const count = selectedItems.length;
        info.textContent = (count === 1 ? '1 immagine selezionata' : count + ' immagini selezionate') + modeLabel();
        const deleteButton = byId('r4v5MediaDeleteSelected');
        if (deleteButton) deleteButton.disabled = count !== 1 || pickerMode !== 'default';
    }

    function syncFields(editor) {
        const manager = bgManager();
        if (manager && typeof manager.sync === 'function') { manager.sync(); return; }
        if (!editor) return;
        const htmlField = byId(cfg.htmlFieldId);
        const cssField = byId(cfg.cssFieldId);
        const jsonField = byId(cfg.jsonFieldId);
        if (htmlField && typeof editor.getHtml === 'function') htmlField.value = editor.getHtml();
        if (cssField && typeof editor.getCss === 'function') cssField.value = editor.getCss();
        if (jsonField && typeof editor.getProjectData === 'function') {
            try { jsonField.value = JSON.stringify(editor.getProjectData()); }
            catch (error) { console.warn('[R4 Editor V5] Sync media JSON non riuscito', error); }
        }
    }

    function modal() { return byId('r4v5MediaModal'); }

    function setButtonsMode() {
        const imageButton = byId('r4v5MediaInsertImage');
        const galleryButton = byId('r4v5MediaInsertGallery');
        const sliderButton = byId('r4v5MediaInsertSlider');
        const logoButton = byId('r4v5MediaInsertLogoGrid');
        const deleteButton = byId('r4v5MediaDeleteSelected');
        const isSpecial = pickerMode === 'background' || pickerMode === 'background-slider' || pickerMode === 'page-background';

        if (imageButton) {
            imageButton.textContent = pickerMode === 'background-slider' ? 'Applica slider sfondo' : (isSpecial ? 'Applica sfondo' : 'Immagine');
        }
        [galleryButton, sliderButton, logoButton].forEach(function (button) {
            if (button) button.hidden = isSpecial;
        });
        if (deleteButton) deleteButton.hidden = isSpecial;
        updateSelectionInfo();
    }

    function openModal(mode, target) {
        pickerMode = mode || 'default';
        backgroundTarget = (mode === 'background' || mode === 'background-slider') ? target || null : null;
        pageBackgroundInput = mode === 'page-background' ? target || null : null;
        selectedItem = null;
        selectedItems = [];
        const el = modal();
        if (!el) return;
        el.hidden = false;
        setButtonsMode();
        loadMedia();
    }

    function closeModal() {
        const el = modal();
        if (el) el.hidden = true;
        pickerMode = 'default';
        backgroundTarget = null;
        pageBackgroundInput = null;
        setButtonsMode();
    }

    function setLoading() {
        const grid = byId('r4v5MediaGrid');
        if (grid) grid.innerHTML = '<div class="r4v5-media-empty">Caricamento media...</div>';
    }

    function renderItems(items, preserveAll) {
        const grid = byId('r4v5MediaGrid');
        if (!grid) return;
        cachedItems = items || [];
        if (!preserveAll) allItems = cachedItems.slice();
        selectedItem = selectedItems[0] || null;

        if (!cachedItems.length) {
            grid.innerHTML = '<div class="r4v5-media-empty">Nessuna immagine trovata nella libreria media.</div>';
            updateSelectionInfo();
            return;
        }

        grid.innerHTML = cachedItems.map(function (item, index) {
            const src = mediaUrl(item);
            const title = mediaTitle(item);
            const selectedClass = isSelected(item) ? ' is-selected' : '';
            return '<button type="button" class="r4v5-media-item' + selectedClass + '" data-r4v5-media-index="' + index + '">' +
                '<img src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '">' +
                '<span>' + escapeHtml(title) + '</span>' +
                '</button>';
        }).join('');
        updateSelectionInfo();
    }

    async function loadMedia() {
        if (!cfg.mediaPickerUrl) { renderItems([]); return; }
        setLoading();
        try {
            const url = new URL(cfg.mediaPickerUrl, window.location.origin);
            url.searchParams.set('pb_mode', 'image');
            url.searchParams.set('per', '80');
            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            renderItems(data.items || []);
        } catch (error) {
            console.error('[R4 Editor V5] Errore caricamento media', error);
            const grid = byId('r4v5MediaGrid');
            if (grid) grid.innerHTML = '<div class="r4v5-media-empty">Errore durante il caricamento dei media.</div>';
        }
    }

    async function uploadMedia(event) {
        event.preventDefault();
        const fileInput = byId('r4v5MediaUploadFile');
        if (!fileInput || !fileInput.files || !fileInput.files.length) { alert('Seleziona prima un file immagine.'); return; }
        if (!cfg.mediaUploadUrl) { alert('Endpoint upload media non configurato.'); return; }
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        try {
            const response = await fetch(cfg.mediaUploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrfToken || '', 'Accept': 'application/json' },
                body: formData
            });
            if (!response.ok) throw new Error('Upload non riuscito');
            fileInput.value = '';
            await loadMedia();
        } catch (error) {
            console.error('[R4 Editor V5] Upload media fallito', error);
            alert('Upload non riuscito. Verifica il file e riprova.');
        }
    }

    async function deleteSelectedMedia() {
        const item = selectedItems[0] || null;
        const id = mediaId(item);
        if (!item || !id) { alert('Seleziona un solo media valido da eliminare.'); return; }
        if (!cfg.mediaDeleteBaseUrl) { alert('Endpoint cancellazione media non configurato.'); return; }

        const title = mediaTitle(item);
        const ok = window.confirm('Vuoi eliminare definitivamente questo media e le sue varianti?\n\n' + title + '\n\nAttenzione: se è già usato in una pagina, il riferimento resterà nella pagina ma il file non sarà più disponibile.');
        if (!ok) return;

        const deleteButton = byId('r4v5MediaDeleteSelected');
        if (deleteButton) {
            deleteButton.disabled = true;
            deleteButton.textContent = 'Elimino...';
        }

        try {
            const response = await fetch(String(cfg.mediaDeleteBaseUrl).replace(/\/$/, '') + '/' + encodeURIComponent(id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': cfg.csrfToken || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Cancellazione non riuscita');
            selectedItem = null;
            selectedItems = [];
            await loadMedia();
        } catch (error) {
            console.error('[R4 Editor V5] Cancellazione media fallita', error);
            alert('Cancellazione non riuscita. Verifica permessi e riprova.');
        } finally {
            if (deleteButton) {
                deleteButton.textContent = 'Elimina selezionato';
                updateSelectionInfo();
            }
        }
    }

    function selectedCanvasImage(editor) {
        const selected = editor && editor.getSelected ? editor.getSelected() : null;
        if (!selected) return null;
        const tag = String(selected.get && selected.get('tagName') || '').toLowerCase();
        const type = String(selected.get && selected.get('type') || '').toLowerCase();
        return tag === 'img' || type === 'image' ? selected : null;
    }

    function replaceImage(editor, item) {
        const image = selectedCanvasImage(editor);
        if (!image) return false;
        const attrs = Object.assign({}, image.getAttributes ? image.getAttributes() || {} : {});
        attrs.src = mediaUrl(item);
        attrs.alt = mediaTitle(item);
        attrs.title = mediaTitle(item);
        image.setAttributes(attrs);
        editor.trigger('update');
        syncFields(editor);
        return true;
    }

    function fallbackBackgroundImage(ed, target, src) {
        target.addStyle({
            'background-image': 'url("' + src.replace(/"/g, '%22') + '")',
            'background-size': 'cover',
            'background-position': 'center center',
            'background-repeat': 'no-repeat'
        });
        const attrs = Object.assign({}, target.getAttributes ? target.getAttributes() || {} : {});
        attrs['data-r4v5-bg-mode'] = 'image';
        attrs['data-r4v5-bg-image'] = src;
        target.setAttributes(attrs);
        ed.trigger('update');
        syncFields(ed);
        return true;
    }

    function applyBackgroundImage(item) {
        const ed = getEditor();
        const target = backgroundTarget || (ed && ed.getSelected ? ed.getSelected() : null);
        const src = mediaUrl(item);
        if (!ed || !target || !src) return false;

        const manager = bgManager();
        if (manager && typeof manager.apply === 'function') {
            manager.apply(target, {
                mode: 'image',
                image: src,
                size: 'cover',
                position: 'center center',
                repeat: 'no-repeat',
                attachment: 'scroll',
                overlayColor: '#000000',
                overlayOpacity: 0
            });
            return true;
        }

        return fallbackBackgroundImage(ed, target, src);
    }

    function fallbackBackgroundSlider(ed, target, images) {
        const attrs = Object.assign({}, target.getAttributes ? target.getAttributes() || {} : {});
        attrs['data-r4v5-bg-mode'] = 'slider';
        attrs['data-r4v5-bg-slider'] = '1';
        attrs['data-r4v5-bg-slider-images'] = JSON.stringify(images);
        attrs['data-r4v5-bg-slider-autoplay'] = attrs['data-r4v5-bg-slider-autoplay'] || 'true';
        attrs['data-r4v5-bg-slider-interval'] = attrs['data-r4v5-bg-slider-interval'] || '4500';
        attrs['data-r4v5-bg-slider-duration'] = attrs['data-r4v5-bg-slider-duration'] || '700';
        attrs['data-r4v5-bg-slider-fit'] = attrs['data-r4v5-bg-slider-fit'] || 'cover';
        attrs['data-r4v5-bg-slider-position'] = attrs['data-r4v5-bg-slider-position'] || 'center center';
        attrs['data-r4v5-bg-overlay-color'] = attrs['data-r4v5-bg-overlay-color'] || '#000000';
        attrs['data-r4v5-bg-overlay-opacity'] = attrs['data-r4v5-bg-overlay-opacity'] || '0.35';
        target.setAttributes(attrs);
        target.addStyle({ position: 'relative', overflow: 'hidden', 'min-height': target.getStyle()['min-height'] || '420px', background: '', 'background-image': '', 'background-color': '' });
        ed.trigger('update');
        syncFields(ed);
        if (window.R4V5BackgroundSliderBridge && typeof window.R4V5BackgroundSliderBridge.inject === 'function') window.setTimeout(window.R4V5BackgroundSliderBridge.inject, 120);
        return true;
    }

    function applyBackgroundSlider() {
        const ed = getEditor();
        const target = backgroundTarget || (ed && ed.getSelected ? ed.getSelected() : null);
        const images = selectedItems.map(mediaUrl).filter(Boolean);
        if (!ed || !target || !images.length) return false;

        const manager = bgManager();
        if (manager && typeof manager.apply === 'function') {
            const current = typeof manager.read === 'function' ? manager.read(target) : {};
            manager.apply(target, {
                mode: 'slider',
                images: images,
                autoplay: current.autoplay || true,
                interval: current.interval || 4500,
                duration: current.duration || 700,
                fit: current.size || 'cover',
                position: current.position || 'center center',
                overlayColor: current.overlayColor || '#000000',
                overlayOpacity: current.overlayOpacity || 0.35,
                minHeight: current.minHeight || '420px'
            });
            return true;
        }

        return fallbackBackgroundSlider(ed, target, images);
    }

    function applyPageBackgroundImage(item) {
        const input = pageBackgroundInput || byId('r4v5PageBgImageSrc');
        const type = byId('r4v5PageBgType');
        const src = mediaUrl(item);
        if (!input || !src) return false;
        input.value = src;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        if (type) {
            type.value = 'image';
            type.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return true;
    }

    function insertImage(editor, item) {
        const src = mediaUrl(item);
        const title = mediaTitle(item);
        if (!src) return;
        editor.addComponents('<img src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '" title="' + escapeHtml(title) + '" style="width:100%;height:auto;border-radius:22px;display:block;">');
        editor.trigger('update');
        syncFields(editor);
    }

    function galleryHtml(items) {
        const cards = items.map(function (item) {
            const src = mediaUrl(item);
            const title = mediaTitle(item);
            return '<figure style="margin:0;border-radius:22px;overflow:hidden;background:#f8fafc;border:1px solid #e5e7eb;"><img src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '" title="' + escapeHtml(title) + '" style="width:100%;height:260px;object-fit:cover;display:block;"><figcaption style="padding:12px 14px;font-size:13px;line-height:1.5;color:#64748b;">' + escapeHtml(title) + '</figcaption></figure>';
        }).join('');
        return '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;"><div style="margin-bottom:28px;text-align:center;"><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Gallery</span><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 12px;color:#111827;">Galleria immagini</h2><p style="font-size:17px;line-height:1.7;color:#64748b;margin:0;">Immagini selezionate dalla libreria media interna.</p></div><div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;">' + cards + '</div></div></section>';
    }

    function sliderHtml(items) {
        const slides = items.map(function (item, index) {
            const src = mediaUrl(item);
            const title = mediaTitle(item);
            return '<article style="min-width:100%;scroll-snap-align:start;background:#ffffff;"><img src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '" title="' + escapeHtml(title) + '" style="width:100%;height:520px;object-fit:cover;display:block;border-radius:26px;"><div style="padding:18px 4px 0;color:#64748b;font-size:14px;line-height:1.6;">Slide ' + (index + 1) + ' — ' + escapeHtml(title) + '</div></article>';
        }).join('');
        return '<section style="padding:76px 24px;background:#f8fafc;"><div style="max-width:1120px;margin:0 auto;"><div style="margin-bottom:28px;text-align:center;"><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Slider</span><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 12px;color:#111827;">Slider immagini</h2><p style="font-size:17px;line-height:1.7;color:#64748b;margin:0;">Scorri orizzontalmente le immagini selezionate.</p></div><div style="display:flex;gap:18px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding-bottom:14px;">' + slides + '</div></div></section>';
    }

    function logoGridHtml(items) {
        const cards = items.map(function (item) {
            const src = mediaUrl(item);
            const title = mediaTitle(item);
            return '<a href="#" style="display:block;text-decoration:none;color:inherit;padding:24px;border-radius:22px;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 12px 28px rgba(15,23,42,.06);"><img src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '" title="' + escapeHtml(title) + '" style="width:100%;height:120px;object-fit:contain;display:block;margin-bottom:14px;"><strong style="display:block;font-size:17px;color:#111827;line-height:1.3;">' + escapeHtml(title) + '</strong><span style="display:block;margin-top:6px;font-size:14px;line-height:1.5;color:#64748b;">Cliente, partner o lavoro realizzato.</span></a>';
        }).join('');
        return '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;"><div style="margin-bottom:28px;text-align:center;"><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Loghi / lavori</span><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 12px;color:#111827;">Loghi e lavori realizzati</h2><p style="font-size:17px;line-height:1.7;color:#64748b;margin:0;">Elementi selezionati dalla libreria media interna.</p></div><div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;">' + cards + '</div></div></section>';
    }

    function requireMultipleSelection(actionName) {
        if (!selectedItems.length) { alert('Seleziona almeno una immagine per creare ' + actionName + '.'); return false; }
        return true;
    }

    function addComponentFromSelection(builder, actionName) {
        if (!requireMultipleSelection(actionName)) return;
        const editor = getEditor();
        if (!editor) return;
        editor.addComponents(builder(selectedItems));
        editor.trigger('update');
        syncFields(editor);
        closeModal();
    }

    function applySelectedImage() {
        const item = selectedItem || selectedItems[0];
        if (pickerMode === 'background-slider') {
            if (!selectedItems.length) { alert('Seleziona almeno una immagine per lo slider di sfondo.'); return; }
            if (!applyBackgroundSlider()) alert('Seleziona un elemento valido per applicare lo slider di sfondo.');
            closeModal();
            return;
        }
        if (!item) { alert('Seleziona un’immagine dalla libreria media.'); return; }
        if (pickerMode === 'background') {
            if (!applyBackgroundImage(item)) alert('Seleziona un elemento valido per applicare lo sfondo.');
            closeModal();
            return;
        }
        if (pickerMode === 'page-background') {
            if (!applyPageBackgroundImage(item)) alert('Campo sfondo pagina non disponibile.');
            closeModal();
            return;
        }
        const editor = getEditor();
        if (!editor) return;
        if (!replaceImage(editor, item)) insertImage(editor, item);
        closeModal();
    }

    function clearSelection() {
        selectedItem = null;
        selectedItems = [];
        renderItems(cachedItems, true);
    }

    function toggleItemSelection(item) {
        const key = itemKey(item);
        const exists = selectedItems.some(function (selected) { return itemKey(selected) === key; });
        if (exists) selectedItems = selectedItems.filter(function (selected) { return itemKey(selected) !== key; });
        else selectedItems.push(item);
        selectedItem = selectedItems[0] || null;
    }

    function bindEvents() {
        document.querySelectorAll('[data-r4v5-media-close]').forEach(function (button) { button.addEventListener('click', closeModal); });
        document.querySelectorAll('[data-r4v5-command="media"]').forEach(function (button) { button.addEventListener('click', function () { openModal('default'); }); });
        document.querySelectorAll('[data-r4v5-page-bg-media]').forEach(function (button) {
            button.addEventListener('click', function () { openModal('page-background', byId('r4v5PageBgImageSrc')); });
        });

        const search = byId('r4v5MediaSearch');
        if (search) search.addEventListener('input', function () {
            const q = String(search.value || '').trim().toLowerCase();
            if (!q) { renderItems(allItems, true); return; }
            renderItems(allItems.filter(function (item) { return mediaTitle(item).toLowerCase().includes(q); }), true);
        });

        const grid = byId('r4v5MediaGrid');
        if (grid) grid.addEventListener('click', function (event) {
            const button = event.target.closest('[data-r4v5-media-index]');
            if (!button) return;
            const item = cachedItems[Number(button.dataset.r4v5MediaIndex)] || null;
            if (!item) return;
            toggleItemSelection(item);
            renderItems(cachedItems, true);
        });

        const uploadForm = byId('r4v5MediaUploadForm');
        if (uploadForm) uploadForm.addEventListener('submit', uploadMedia);
        const insertButton = byId('r4v5MediaInsertImage');
        if (insertButton) insertButton.addEventListener('click', applySelectedImage);
        const galleryButton = byId('r4v5MediaInsertGallery');
        if (galleryButton) galleryButton.addEventListener('click', function () { addComponentFromSelection(galleryHtml, 'la gallery'); });
        const sliderButton = byId('r4v5MediaInsertSlider');
        if (sliderButton) sliderButton.addEventListener('click', function () { addComponentFromSelection(sliderHtml, 'lo slider'); });
        const logoGridButton = byId('r4v5MediaInsertLogoGrid');
        if (logoGridButton) logoGridButton.addEventListener('click', function () { addComponentFromSelection(logoGridHtml, 'il blocco loghi/lavori'); });
        const clearButton = byId('r4v5MediaClearSelection');
        if (clearButton) clearButton.addEventListener('click', clearSelection);
        const deleteButton = byId('r4v5MediaDeleteSelected');
        if (deleteButton) deleteButton.addEventListener('click', deleteSelectedMedia);
    }

    window.R4V5Media = {
        open: function () { openModal('default'); },
        openForBackground: function (target) { openModal('background', target); },
        openForBackgroundSlider: function (target) { openModal('background-slider', target); },
        openForPageBackground: function (input) { openModal('page-background', input || byId('r4v5PageBgImageSrc')); }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindEvents);
    else bindEvents();
})();
