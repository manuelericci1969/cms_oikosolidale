// public/pb/pageBgPicker.js
import { openImagePicker, getMediaUrlByQuality } from './mediaPicker.js';

(function () {
    function $(id) { return document.getElementById(id); }

    function pickUrl(media) {
        if (!media) return '';

        // prova helper ufficiale
        try {
            const u = (typeof getMediaUrlByQuality === 'function')
                ? (getMediaUrlByQuality(media, 'full') || '')
                : '';
            if (u) return u;
        } catch (_) {}

        // fallback multi-formato
        return (
            media.full ||
            media.src ||
            media.url ||
            (media.urls && (media.urls.full || media.urls.original)) ||
            (media.variants && (media.variants.full || media.variants.original)) ||
            ''
        );
    }

    function dispatchChange(el) {
        if (!el) return;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function init() {
        const sel     = $('pageBgType');
        const panels  = Array.from(document.querySelectorAll('[data-bg-panel]'));

        const srcInput = $('pageBgImageSrc');
        const preview  = $('pageBgPreview');
        const btnPick  = $('btnPickPageBg');
        const btnClear = $('btnClearPageBg');

        const attSel    = $('pageBgAttachment');
        const parHidden = $('pageBgParallax');

        if (!sel) return;

        function updatePanels() {
            const v = sel.value || 'none';
            panels.forEach(p => {
                p.style.display = (p.getAttribute('data-bg-panel') === v) ? '' : 'none';
            });
        }

        function setPreview(url) {
            if (!preview) return;
            if (url) {
                preview.style.backgroundImage = `url("${url.replace(/"/g, '')}")`;
                preview.textContent = '';
            } else {
                preview.style.backgroundImage = 'none';
                preview.textContent = 'Nessuna immagine selezionata';
            }
        }

        function syncParallaxFromAttachment() {
            if (!attSel || !parHidden) return;
            parHidden.value = (attSel.value === 'fixed') ? '1' : '0';
            dispatchChange(parHidden);
        }

        // init UI
        updatePanels();
        setPreview(srcInput?.value || '');
        syncParallaxFromAttachment();

        sel.addEventListener('change', updatePanels);
        attSel && attSel.addEventListener('change', syncParallaxFromAttachment);

        async function pickOne() {
            const pickerUrl = window.PB_MEDIA_PICKER_URL || '/admin/media/picker';
            const res = openImagePicker({ pickerUrl });
            // supporta sia Promise che valore sync
            if (res && typeof res.then === 'function') return await res;
            return res;
        }

        btnPick && btnPick.addEventListener('click', async (e) => {
            e.preventDefault();

            const picked = await pickOne();
            if (!picked) return;

            const url = pickUrl(picked);
            if (!url) return;

            if (srcInput) {
                srcInput.value = url;
                dispatchChange(srcInput);
            }

            setPreview(url);

            // se non era "image", passa a image
            if (sel.value !== 'image') {
                sel.value = 'image';
                updatePanels();
                dispatchChange(sel);
            }
        });

        btnClear && btnClear.addEventListener('click', (e) => {
            e.preventDefault();
            if (srcInput) {
                srcInput.value = '';
                dispatchChange(srcInput);
            }
            setPreview('');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
