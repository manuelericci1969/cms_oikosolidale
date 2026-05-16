(function () {
    'use strict';

    var cfg = window.R4EditorV5Config || {};
    var TAB_KEY = 'linkurl';
    var ROOT_ID = 'r4v5LinkUrlInspector';

    function byId(id) {
        return id ? document.getElementById(id) : null;
    }

    function editor() {
        return window.R4EditorV5 || null;
    }

    function selected() {
        var ed = editor();
        return ed && ed.getSelected ? ed.getSelected() : null;
    }

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

    function getComponents(cmp) {
        if (!cmp || !cmp.components) return [];
        var collection = cmp.components();
        return collection && collection.models ? collection.models : [];
    }

    function findComponentByTag(cmp, tags) {
        if (!cmp) return null;
        tags = Array.isArray(tags) ? tags : [tags];
        if (tags.indexOf(tagName(cmp)) !== -1) return cmp;

        var children = getComponents(cmp);
        for (var i = 0; i < children.length; i += 1) {
            var found = findComponentByTag(children[i], tags);
            if (found) return found;
        }
        return null;
    }

    function getAttrs(cmp) {
        return cmp && cmp.getAttributes ? (cmp.getAttributes() || {}) : {};
    }

    function getAttr(cmp, name, fallback) {
        var attrs = getAttrs(cmp);
        return attrs[name] == null ? (fallback || '') : attrs[name];
    }

    function setAttrs(cmp, attrs) {
        if (!cmp || !cmp.setAttributes) return;
        var next = Object.assign({}, getAttrs(cmp));
        Object.keys(attrs).forEach(function (key) {
            var value = attrs[key];
            if (value === null || value === undefined || value === '') {
                delete next[key];
            } else {
                next[key] = value;
            }
        });
        cmp.setAttributes(next);
        syncFields();
        var ed = editor();
        if (ed) {
            ed.trigger('component:update', cmp);
            ed.trigger('update');
        }
    }

    function syncFields() {
        var ed = editor();
        if (!ed) return;

        var html = byId(cfg.htmlFieldId);
        var css = byId(cfg.cssFieldId);
        var json = byId(cfg.jsonFieldId);

        if (html) html.value = ed.getHtml();
        if (css) css.value = ed.getCss();
        if (json) {
            try {
                json.value = JSON.stringify(ed.getProjectData());
            } catch (error) {
                console.warn('[R4 Editor V5] Sync JSON link/url non riuscito', error);
            }
        }
    }

    function selectedLink() {
        return findComponentByTag(selected(), 'a');
    }

    function selectedImage() {
        return findComponentByTag(selected(), 'img');
    }

    function selectedLabel(cmp) {
        if (!cmp) return 'Nessun elemento selezionato';
        var tag = tagName(cmp) || 'elemento';
        var attrs = getAttrs(cmp);
        var cls = attrs.class ? '.' + String(attrs.class).trim().split(/\s+/).slice(0, 2).join('.') : '';
        return '<' + tag + '>' + cls;
    }

    function ensureStyle() {
        if (document.getElementById('r4v5-link-url-inspector-style')) return;
        var style = document.createElement('style');
        style.id = 'r4v5-link-url-inspector-style';
        style.textContent = [
            '.r4v5-link-url-status{font-size:12px;line-height:1.55;color:#94a3b8;background:#0f172a;border:1px solid #334155;border-radius:12px;padding:10px 12px;margin-bottom:10px}',
            '.r4v5-link-url-status strong{display:block;color:#e5e7eb;margin-bottom:3px}',
            '.r4v5-link-url-note{font-size:12px;line-height:1.55;color:#94a3b8;margin:6px 0 10px}',
            '.r4v5-link-url-sep{width:100%;border:0;border-top:1px solid #334155;margin:12px 0}',
            '.r4v5-link-url-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}'
        ].join('');
        document.head.appendChild(style);
    }

    function field(label, html) {
        return '<div class="r4v5-field"><label>' + label + '</label>' + html + '</div>';
    }

    function render() {
        ensureStyle();
        var root = byId(ROOT_ID);
        if (!root) return;

        var cmp = selected();
        var link = selectedLink();
        var img = selectedImage();

        var linkHref = getAttr(link, 'href', '');
        var linkTarget = getAttr(link, 'target', '');
        var linkRel = getAttr(link, 'rel', '');
        var linkTitle = getAttr(link, 'title', '');
        var linkText = link && link.components ? link.components().map(function (child) {
            return child && child.get ? (child.get('content') || '') : '';
        }).join('').trim() : '';

        var imgSrc = getAttr(img, 'src', '');
        var imgAlt = getAttr(img, 'alt', '');
        var imgTitle = getAttr(img, 'title', '');

        root.innerHTML = [
            '<div class="r4v5-link-url-status"><strong>Elemento selezionato</strong>' + escapeHtml(selectedLabel(cmp)) + '</div>',
            '<div class="r4v5-help">Gestisce i percorsi dei pulsanti, dei link e delle immagini. I valori vengono scritti negli attributi reali HTML e sincronizzati nel salvataggio V5.</div>',
            '<div class="r4v5-panel-title">Link / Pulsante</div>',
            link ? '' : '<div class="r4v5-link-url-note">Seleziona un bottone/link oppure una sezione che contiene un link. L’Inspector userà il primo link trovato dentro la selezione.</div>',
            field('URL / percorso href', '<input id="r4v5LinkHref" type="text" value="' + escapeHtml(linkHref) + '" placeholder="/contatti oppure https://dominio.it/pagina">'),
            field('Testo pulsante/link', '<input id="r4v5LinkText" type="text" value="' + escapeHtml(linkText) + '" placeholder="Richiedi consulenza">'),
            field('Titolo link', '<input id="r4v5LinkTitle" type="text" value="' + escapeHtml(linkTitle) + '" placeholder="Titolo opzionale">'),
            '<label class="r4v5-check"><input id="r4v5LinkBlank" type="checkbox" ' + (linkTarget === '_blank' ? 'checked' : '') + '> Apri in nuova scheda</label>',
            field('Rel', '<input id="r4v5LinkRel" type="text" value="' + escapeHtml(linkRel) + '" placeholder="noopener noreferrer">'),
            '<div class="r4v5-link-url-actions">',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5ApplyLinkUrl">Applica link</button>',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5RemoveLinkUrl">Rimuovi href</button>',
            '</div>',
            '<hr class="r4v5-link-url-sep">',
            '<div class="r4v5-panel-title">Immagine</div>',
            img ? '' : '<div class="r4v5-link-url-note">Seleziona un’immagine oppure una sezione che contiene un’immagine per modificare src e alt.</div>',
            field('URL immagine src', '<input id="r4v5ImageSrc" type="text" value="' + escapeHtml(imgSrc) + '" placeholder="/storage/uploads/immagine.jpg">'),
            field('Testo alternativo alt', '<input id="r4v5ImageAlt" type="text" value="' + escapeHtml(imgAlt) + '" placeholder="Descrizione immagine">'),
            field('Titolo immagine', '<input id="r4v5ImageTitle" type="text" value="' + escapeHtml(imgTitle) + '" placeholder="Titolo opzionale">'),
            '<div class="r4v5-link-url-actions">',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5ApplyImageUrl">Applica immagine</button>',
                '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5RemoveImageSrc">Rimuovi src</button>',
            '</div>'
        ].join('');

        bindActions(link, img);
    }

    function bindActions(link, img) {
        var applyLink = byId('r4v5ApplyLinkUrl');
        var removeLink = byId('r4v5RemoveLinkUrl');
        var applyImage = byId('r4v5ApplyImageUrl');
        var removeImage = byId('r4v5RemoveImageSrc');

        if (applyLink) {
            applyLink.addEventListener('click', function () {
                var target = selectedLink() || link;
                if (!target) {
                    alert('Seleziona un bottone/link o una sezione che contiene un link.');
                    return;
                }

                var href = (byId('r4v5LinkHref').value || '').trim();
                var text = (byId('r4v5LinkText').value || '').trim();
                var title = (byId('r4v5LinkTitle').value || '').trim();
                var blank = byId('r4v5LinkBlank').checked;
                var rel = (byId('r4v5LinkRel').value || '').trim();

                if (blank && !rel) rel = 'noopener noreferrer';

                setAttrs(target, {
                    href: href || '#',
                    target: blank ? '_blank' : null,
                    rel: rel || null,
                    title: title || null
                });

                if (text && target.components) {
                    target.components(text);
                }

                syncFields();
                render();
            });
        }

        if (removeLink) {
            removeLink.addEventListener('click', function () {
                var target = selectedLink() || link;
                if (!target) {
                    alert('Seleziona un bottone/link o una sezione che contiene un link.');
                    return;
                }
                setAttrs(target, { href: null, target: null, rel: null, title: null });
                render();
            });
        }

        if (applyImage) {
            applyImage.addEventListener('click', function () {
                var target = selectedImage() || img;
                if (!target) {
                    alert('Seleziona un’immagine o una sezione che contiene un’immagine.');
                    return;
                }
                setAttrs(target, {
                    src: (byId('r4v5ImageSrc').value || '').trim(),
                    alt: (byId('r4v5ImageAlt').value || '').trim(),
                    title: (byId('r4v5ImageTitle').value || '').trim() || null
                });
                render();
            });
        }

        if (removeImage) {
            removeImage.addEventListener('click', function () {
                var target = selectedImage() || img;
                if (!target) {
                    alert('Seleziona un’immagine o una sezione che contiene un’immagine.');
                    return;
                }
                setAttrs(target, { src: null, title: null });
                render();
            });
        }
    }

    function ensurePanel() {
        var tabs = document.querySelector('.r4v5-inspector-tabs');
        if (!tabs) return false;

        if (!document.querySelector('[data-r4v5-inspector-tab="' + TAB_KEY + '"]')) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'r4v5-inspector-tab';
            button.setAttribute('data-r4v5-inspector-tab', TAB_KEY);
            button.textContent = 'Link / URL';
            tabs.appendChild(button);
        }

        if (!document.querySelector('[data-r4v5-inspector-panel="' + TAB_KEY + '"]')) {
            var panel = document.createElement('div');
            panel.className = 'r4v5-inspector-panel';
            panel.setAttribute('data-r4v5-inspector-panel', TAB_KEY);
            panel.hidden = true;
            panel.innerHTML = '<div id="' + ROOT_ID + '"></div>';

            var props = document.querySelector('[data-r4v5-inspector-panel="props"]');
            if (props && props.parentNode) props.parentNode.insertBefore(panel, props.nextSibling);
            else tabs.parentNode.appendChild(panel);
        }

        render();
        return true;
    }

    function boot() {
        if (!ensurePanel()) return false;

        var ed = editor();
        if (ed && !ed.__r4v5LinkUrlInspectorBound) {
            ed.__r4v5LinkUrlInspectorBound = true;
            ed.on('component:selected component:update component:add component:remove load canvas:frame:load', function () {
                window.setTimeout(render, 20);
            });
        }

        document.addEventListener('click', function (event) {
            if (event.target && event.target.closest('[data-r4v5-inspector-tab="' + TAB_KEY + '"]')) {
                window.setTimeout(render, 20);
            }
        }, true);

        return true;
    }

    if (!boot()) {
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            if (boot() || attempts > 80) window.clearInterval(timer);
        }, 100);
    }
})();
