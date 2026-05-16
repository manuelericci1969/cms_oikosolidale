(function () {
    'use strict';

    var cfg = window.R4EditorV5Config || {};
    var ROOT_ID = 'r4v5QuickLinkUrlInspector';

    function byId(id) { return id ? document.getElementById(id) : null; }
    function editor() { return window.R4EditorV5 || null; }
    function selected() { var ed = editor(); return ed && ed.getSelected ? ed.getSelected() : null; }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function tagName(cmp) {
        if (!cmp || !cmp.get) return '';
        return String(cmp.get('tagName') || '').toLowerCase();
    }

    function children(cmp) {
        if (!cmp || !cmp.components) return [];
        var collection = cmp.components();
        return collection && collection.models ? collection.models : [];
    }

    function findByTag(cmp, tag) {
        if (!cmp) return null;
        if (tagName(cmp) === tag) return cmp;
        var list = children(cmp);
        for (var i = 0; i < list.length; i += 1) {
            var found = findByTag(list[i], tag);
            if (found) return found;
        }
        return null;
    }

    function selectedLink() { return findByTag(selected(), 'a'); }
    function attrs(cmp) { return cmp && cmp.getAttributes ? (cmp.getAttributes() || {}) : {}; }
    function attr(cmp, key) { var a = attrs(cmp); return a[key] == null ? '' : a[key]; }

    function syncFields() {
        var ed = editor();
        if (!ed) return;
        var html = byId(cfg.htmlFieldId);
        var css = byId(cfg.cssFieldId);
        var json = byId(cfg.jsonFieldId);
        if (html) html.value = ed.getHtml();
        if (css) css.value = ed.getCss();
        if (json) {
            try { json.value = JSON.stringify(ed.getProjectData()); }
            catch (error) { console.warn('[R4 Editor V5] Sync link rapido non riuscito', error); }
        }
    }

    function setAttrs(cmp, changes) {
        if (!cmp || !cmp.setAttributes) return;
        var next = Object.assign({}, attrs(cmp));
        Object.keys(changes).forEach(function (key) {
            var value = changes[key];
            if (value === null || value === undefined || value === '') delete next[key];
            else next[key] = value;
        });
        cmp.setAttributes(next);
        syncFields();
        var ed = editor();
        if (ed) {
            ed.trigger('component:update', cmp);
            ed.trigger('update');
        }
    }

    function ensureStyle() {
        if (document.getElementById('r4v5-quick-link-style')) return;
        var style = document.createElement('style');
        style.id = 'r4v5-quick-link-style';
        style.textContent = [
            '.r4v5-quick-link-box{border:1px solid #0d6efd;background:rgba(13,110,253,.08);border-radius:14px;padding:12px;margin:0 0 12px}',
            '.r4v5-quick-link-title{font-size:13px;font-weight:950;color:#e5e7eb;margin:0 0 4px}',
            '.r4v5-quick-link-sub{font-size:12px;line-height:1.5;color:#94a3b8;margin:0 0 10px}',
            '.r4v5-quick-link-box input{width:100%;border:1px solid #334155;background:#020617;color:#e5e7eb;border-radius:10px;padding:9px 10px;font-size:12px}',
            '.r4v5-quick-link-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}',
            '.r4v5-quick-link-check{display:flex;gap:7px;align-items:center;color:#cbd5e1;font-size:12px;margin-top:8px}',
            '.r4v5-quick-link-check input{width:auto}',
            '.r4v5-quick-link-current{font-size:11px;color:#94a3b8;margin:6px 0 0;word-break:break-all}'
        ].join('');
        document.head.appendChild(style);
    }

    function ensureRoot() {
        var controls = byId(cfg.controlsId || 'r4v5Controls');
        if (!controls) return null;
        var root = byId(ROOT_ID);
        if (root) return root;
        root = document.createElement('div');
        root.id = ROOT_ID;
        root.className = 'r4v5-quick-link-box';
        controls.insertBefore(root, controls.firstChild);
        return root;
    }

    function selectedName() {
        var cmp = selected();
        if (!cmp) return 'Nessun elemento selezionato';
        var tag = tagName(cmp) || 'elemento';
        var link = selectedLink();
        if (link && link !== cmp) return '<' + tag + '> contiene link <a>';
        return '<' + tag + '>';
    }

    function render() {
        ensureStyle();
        var root = ensureRoot();
        if (!root) return false;

        var link = selectedLink();
        var href = attr(link, 'href');
        var text = '';
        if (link && link.components) {
            text = link.components().map(function (child) {
                return child && child.get ? (child.get('content') || '') : '';
            }).join('').trim();
        }

        root.innerHTML = [
            '<div class="r4v5-quick-link-title">Link pulsante</div>',
            '<div class="r4v5-quick-link-sub">Campo unico per salvare il percorso del pulsante/link selezionato.</div>',
            '<input id="r4v5QuickHref" type="text" value="' + escapeHtml(href) + '" placeholder="/contatti oppure https://dominio.it/pagina">',
            '<input id="r4v5QuickText" type="text" value="' + escapeHtml(text) + '" placeholder="Testo pulsante opzionale" style="margin-top:7px">',
            '<label class="r4v5-quick-link-check"><input id="r4v5QuickBlank" type="checkbox" ' + (attr(link, 'target') === '_blank' ? 'checked' : '') + '> Apri in nuova scheda</label>',
            '<div class="r4v5-quick-link-row">',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5QuickApplyLink">Applica link</button>',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5QuickRemoveLink">Rimuovi link</button>',
            '</div>',
            '<div class="r4v5-quick-link-current">Selezione: ' + escapeHtml(selectedName()) + (link ? ' — href attuale: ' + escapeHtml(href || '#') : ' — nessun link trovato') + '</div>'
        ].join('');

        bind();
        return true;
    }

    function bind() {
        var apply = byId('r4v5QuickApplyLink');
        var remove = byId('r4v5QuickRemoveLink');
        if (apply) {
            apply.addEventListener('click', function () {
                var link = selectedLink();
                if (!link) {
                    alert('Seleziona il bottone/link oppure una sezione che contiene un bottone.');
                    return;
                }
                var href = (byId('r4v5QuickHref').value || '').trim();
                var text = (byId('r4v5QuickText').value || '').trim();
                var blank = !!byId('r4v5QuickBlank').checked;
                setAttrs(link, {
                    href: href || '#',
                    target: blank ? '_blank' : null,
                    rel: blank ? 'noopener noreferrer' : null
                });
                if (text && link.components) link.components(text);
                syncFields();
                render();
            });
        }
        if (remove) {
            remove.addEventListener('click', function () {
                var link = selectedLink();
                if (!link) {
                    alert('Seleziona il bottone/link oppure una sezione che contiene un bottone.');
                    return;
                }
                setAttrs(link, { href: null, target: null, rel: null });
                render();
            });
        }
    }

    function boot() {
        if (!render()) return false;
        var ed = editor();
        if (ed && !ed.__r4v5QuickLinkBound) {
            ed.__r4v5QuickLinkBound = true;
            ed.on('component:selected component:update component:add component:remove load canvas:frame:load', function () {
                window.setTimeout(render, 30);
            });
        }
        return true;
    }

    if (!boot()) {
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            if (boot() || attempts > 100) window.clearInterval(timer);
        }, 100);
    }
})();
