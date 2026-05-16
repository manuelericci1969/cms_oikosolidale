(function () {
    'use strict';

    let booted = false;

    function getCfg() {
        return window.R4VisualEditorV4 || {};
    }

    function csrfToken() {
        const cfg = getCfg();
        const meta = document.querySelector('meta[name="csrf-token"]');
        return cfg.csrfToken || (meta ? meta.getAttribute('content') : '');
    }

    function mediaDeleteUrl(id) {
        const cfg = getCfg();

        if (cfg.mediaDeleteUrlTemplate) {
            return String(cfg.mediaDeleteUrlTemplate).replace('__ID__', id);
        }

        if (cfg.mediaUploadUrl) {
            return String(cfg.mediaUploadUrl).replace(/\/$/, '') + '/' + id;
        }

        return '/admin/media/' + id;
    }

    function ensureStyle() {
        if (document.getElementById('r4v4-media-delete-style')) return;

        const style = document.createElement('style');
        style.id = 'r4v4-media-delete-style';
        style.textContent = `
            .r4v4-media-card {
                position: relative;
            }

            .r4v4-media-card .r4v4-media-item {
                width: 100%;
            }

            .r4v4-media-delete {
                position: absolute;
                top: 8px;
                right: 8px;
                z-index: 30;
                border: 0;
                border-radius: 999px;
                padding: 7px 11px;
                background: #dc2626;
                color: #fff;
                font-size: 11px;
                font-weight: 900;
                line-height: 1;
                cursor: pointer;
                box-shadow: 0 8px 20px rgba(15, 23, 42, .22);
                opacity: 1;
                transform: none;
                transition: background .16s ease, transform .16s ease;
            }

            .r4v4-media-delete:hover,
            .r4v4-media-delete:focus {
                background: #b91c1c;
                transform: translateY(-1px);
            }

            .r4v4-media-delete.is-loading {
                pointer-events: none;
                background: #64748b;
            }
        `;
        document.head.appendChild(style);
    }

    function wrapMediaItem(button) {
        if (!button || button.closest('.r4v4-media-card')) return;

        const id = Number(button.getAttribute('data-media-id'));
        if (!id || !button.parentNode) return;

        const card = document.createElement('div');
        card.className = 'r4v4-media-card';
        card.setAttribute('data-media-id', String(id));

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'r4v4-media-delete';
        deleteButton.setAttribute('data-media-delete', String(id));
        deleteButton.setAttribute('title', 'Elimina media');
        deleteButton.textContent = 'Elimina';

        button.parentNode.insertBefore(card, button);
        card.appendChild(button);
        card.appendChild(deleteButton);
    }

    function enhanceGrid() {
        const grid = document.getElementById('r4v4MediaGrid');
        if (!grid) return;

        ensureStyle();
        grid.querySelectorAll('.r4v4-media-item[data-media-id]').forEach(wrapMediaItem);
    }

    async function deleteMedia(button) {
        const id = Number(button.getAttribute('data-media-delete'));
        if (!id) return;

        const confirmed = window.confirm('Eliminare definitivamente questo media e tutte le sue varianti?');
        if (!confirmed) return;

        button.classList.add('is-loading');
        button.textContent = 'Elimino...';

        const response = await fetch(mediaDeleteUrl(id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'X-HTTP-Method-Override': 'DELETE'
            },
            body: new URLSearchParams({
                _method: 'DELETE',
                ajax: '1'
            })
        });

        if (!response.ok) {
            button.classList.remove('is-loading');
            button.textContent = 'Elimina';
            window.alert('Eliminazione non riuscita. Verifica permessi o utilizzo del file.');
            return;
        }

        const card = button.closest('.r4v4-media-card');
        if (card) card.remove();
    }

    function boot() {
        if (booted) {
            enhanceGrid();
            return;
        }

        ensureStyle();

        const grid = document.getElementById('r4v4MediaGrid');
        if (!grid) {
            window.setTimeout(boot, 150);
            return;
        }

        booted = true;
        enhanceGrid();

        const observer = new MutationObserver(enhanceGrid);
        observer.observe(grid, { childList: true, subtree: true });

        grid.addEventListener('click', function (event) {
            const button = event.target.closest('[data-media-delete]');
            if (!button) return;

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            deleteMedia(button).catch(function (error) {
                console.error('[R4 Editor V4] Errore eliminazione media', error);
                button.classList.remove('is-loading');
                button.textContent = 'Elimina';
                window.alert('Errore durante eliminazione media.');
            });
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
