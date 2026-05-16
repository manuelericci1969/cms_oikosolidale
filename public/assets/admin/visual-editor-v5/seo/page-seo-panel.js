(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"]/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char] || char;
        });
    }

    function storageKey() {
        var form = document.getElementById('r4v5PageForm');
        var action = form ? String(form.getAttribute('action') || '') : window.location.pathname;
        return 'r4v5:page-seo-panel:' + action;
    }

    function readStorage() {
        try {
            var raw = window.localStorage ? window.localStorage.getItem(storageKey()) : '';
            var data = raw ? JSON.parse(raw) : {};
            return data && typeof data === 'object' ? data : {};
        } catch (error) {
            return {};
        }
    }

    function writeStorage() {
        try {
            if (!window.localStorage) return;
            var data = {};
            document.querySelectorAll('[data-r4v5-seo-field]').forEach(function (field) {
                if (!field.name) return;
                if (field.type === 'checkbox') {
                    data[field.name] = field.checked ? '1' : '0';
                } else {
                    data[field.name] = field.value || '';
                }
            });
            window.localStorage.setItem(storageKey(), JSON.stringify(data));
        } catch (error) {
            // storage non disponibile: ignora senza bloccare l'editor
        }
    }

    function stored(name, fallback) {
        var data = readStorage();
        return Object.prototype.hasOwnProperty.call(data, name) ? data[name] : (fallback || '');
    }

    function ensureStyle() {
        if (document.getElementById('r4v5-page-seo-panel-style')) return;
        var style = document.createElement('style');
        style.id = 'r4v5-page-seo-panel-style';
        style.textContent = [
            '.r4v5-seo-panel{display:flex;flex-direction:column;gap:14px}',
            '.r4v5-seo-section{border:1px solid rgba(148,163,184,.22);background:#0f172a;border-radius:14px;padding:12px}',
            '.r4v5-seo-section-title{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#93c5fd;font-weight:900;margin:0 0 10px}',
            '.r4v5-seo-grid{display:grid;grid-template-columns:1fr;gap:10px}',
            '.r4v5-seo-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}',
            '.r4v5-seo-panel label{display:grid;gap:5px;font-size:12px;color:#cbd5e1;font-weight:800}',
            '.r4v5-seo-panel input,.r4v5-seo-panel textarea,.r4v5-seo-panel select{width:100%;border:1px solid rgba(148,163,184,.25);border-radius:10px;background:#020617;color:#e5e7eb;padding:9px 10px;font-size:12px;outline:none}',
            '.r4v5-seo-panel textarea{min-height:76px;resize:vertical}',
            '.r4v5-seo-check{display:flex!important;grid-template-columns:none!important;align-items:center;gap:8px;font-size:12px;color:#cbd5e1}',
            '.r4v5-seo-check input{width:auto}',
            '.r4v5-seo-count{font-size:11px;color:#94a3b8;text-align:right}',
            '.r4v5-seo-preview-google{background:#fff;color:#202124;border-radius:12px;padding:12px;font-family:Arial,sans-serif}',
            '.r4v5-seo-preview-url{color:#202124;font-size:12px;margin-bottom:3px;word-break:break-all}',
            '.r4v5-seo-preview-title{color:#1a0dab;font-size:18px;line-height:1.25;margin-bottom:4px}',
            '.r4v5-seo-preview-desc{color:#4d5156;font-size:13px;line-height:1.4}',
            '.r4v5-seo-preview-card{border:1px solid rgba(148,163,184,.24);border-radius:12px;overflow:hidden;background:#020617}',
            '.r4v5-seo-preview-img{height:120px;background:#111827;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;background-size:cover;background-position:center}',
            '.r4v5-seo-preview-body{padding:10px}.r4v5-seo-preview-body strong{display:block;color:#f8fafc;font-size:13px}.r4v5-seo-preview-body span{display:block;color:#94a3b8;font-size:12px;margin-top:4px}',
            '.r4v5-seo-warnings{display:grid;gap:6px;margin:0;padding:0;list-style:none}',
            '.r4v5-seo-warnings li{border-radius:9px;padding:8px 9px;font-size:11px;font-weight:800}',
            '.r4v5-seo-ok{background:rgba(22,163,74,.14);color:#bbf7d0}.r4v5-seo-warn{background:rgba(245,158,11,.14);color:#fde68a}.r4v5-seo-bad{background:rgba(239,68,68,.14);color:#fecaca}',
            '.r4v5-seo-prompt{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:11px;line-height:1.5;background:#020617;border:1px solid rgba(148,163,184,.2);border-radius:10px;color:#dbeafe;padding:10px;white-space:pre-wrap}',
            '.r4v5-seo-copy{border:1px solid rgba(56,189,248,.35);background:#082f49;color:#e0f2fe;border-radius:999px;padding:8px 10px;font-size:11px;font-weight:900;cursor:pointer}',
            '.r4v5-seo-copy:disabled{opacity:.55;cursor:not-allowed}',
            '.r4v5-seo-og-status{border-radius:10px;background:rgba(15,23,42,.75);border:1px solid rgba(148,163,184,.18);padding:8px 10px;font-size:11px;color:#cbd5e1;line-height:1.45}',
            '.r4v5-seo-og-tools{display:flex;gap:8px;flex-wrap:wrap}',
            '@media(max-width:1200px){.r4v5-seo-row{grid-template-columns:1fr}}'
        ].join('');
        document.head.appendChild(style);
    }

    function value(name, fallback) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? el.value || '' : (fallback || '');
    }

    function setValue(name, newValue) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) return;
        el.value = newValue || '';
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function pageTitle() {
        return value('meta[page_title]', value('title', document.querySelector('.r4v5-subtitle') ? document.querySelector('.r4v5-subtitle').textContent.trim() : ''));
    }

    function pageExcerpt() {
        return value('meta[page_excerpt]', value('excerpt', ''));
    }

    function pageSlug() {
        return value('slug', '');
    }

    function isHomepage() {
        var home = document.querySelector('[name="is_homepage"][type="checkbox"]');
        if (home) return !!home.checked;

        var field = document.querySelector('[name="is_homepage"]');
        return field ? String(field.value || '') === '1' : false;
    }

    function normalizedSlug() {
        return String(pageSlug() || '').replace(/^\/+|\/+$/g, '');
    }

    function pagePublicUrl() {
        if (isHomepage()) return window.location.origin;
        var slug = normalizedSlug();
        return slug ? window.location.origin + '/' + slug : window.location.origin;
    }

    function resolveGenerateOgUrl() {
        var form = document.getElementById('r4v5PageForm');
        if (!form) return '';

        var explicit = form.getAttribute('data-r4v5-og-generate-url');
        if (explicit) return explicit;

        var action = String(form.getAttribute('action') || '');
        if (!action) return '';

        if (action.indexOf('/update-v5') !== -1) {
            return action.replace(/\/update-v5(?:\?.*)?$/, '/generate-og-image-v5');
        }

        return action.replace(/\/$/, '') + '/generate-og-image-v5';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var form = document.getElementById('r4v5PageForm');
        var input = form ? form.querySelector('input[name="_token"]') : null;
        return meta ? meta.getAttribute('content') : (input ? input.value : '');
    }

    function selectOptions(options, current) {
        return options.map(function (item) {
            return '<option value="' + escapeHtml(item) + '"' + (item === current ? ' selected' : '') + '>' + escapeHtml(item) + '</option>';
        }).join('');
    }

    function buildPanel(panel) {
        if (!panel || panel.dataset.r4v5SeoReady === '1') return;
        panel.dataset.r4v5SeoReady = '1';

        var baseTitle = value('meta[seo_title]', value('meta[title]', pageTitle()));
        var baseDesc = value('meta[seo_description]', value('meta[description]', pageExcerpt()));
        var baseKeywords = value('meta[seo_keywords]', value('meta[keywords]', ''));
        var slug = pageSlug();
        var canonical = pagePublicUrl();

        var title = stored('meta[seo][title]', baseTitle);
        var desc = stored('meta[seo][description]', baseDesc);
        var keywords = stored('meta[seo][focus_keyword]', baseKeywords);
        var robotsIndex = stored('meta[seo][robots][index]', 'index');
        var robotsFollow = stored('meta[seo][robots][follow]', 'follow');
        var ogTitle = stored('meta[seo][og][title]', title);
        var ogDesc = stored('meta[seo][og][description]', desc);
        var ogImage = stored('meta[seo][og][image]', '');
        var ogType = stored('meta[seo][og][type]', 'website');
        var ogUrl = stored('meta[seo][og][url]', canonical);
        var twTitle = stored('meta[seo][twitter][title]', title);
        var twDesc = stored('meta[seo][twitter][description]', desc);
        var twImage = stored('meta[seo][twitter][image]', ogImage);
        var twCard = stored('meta[seo][twitter][card]', 'summary_large_image');
        var schemaType = stored('meta[seo][schema][type]', 'WebPage');
        var schemaEnabled = stored('meta[seo][schema][enabled]', '1') !== '0';
        var customJson = stored('meta[seo][schema][custom_json]', '');

        panel.innerHTML = [
            '<div class="r4v5-panel-title">SEO pagina</div>',
            '<div class="r4v5-seo-panel">',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">SEO base</div><div class="r4v5-seo-grid">',
                    '<label>Meta title<input data-r4v5-seo-field id="r4v5SeoTitle" type="text" name="meta[seo][title]" value="' + escapeHtml(title) + '"></label><div class="r4v5-seo-count" data-r4v5-count="title"></div>',
                    '<label>Meta description<textarea data-r4v5-seo-field id="r4v5SeoDescription" name="meta[seo][description]">' + escapeHtml(desc) + '</textarea></label><div class="r4v5-seo-count" data-r4v5-count="description"></div>',
                    '<label>Focus keyword<input data-r4v5-seo-field id="r4v5SeoFocusKeyword" type="text" name="meta[seo][focus_keyword]" value="' + escapeHtml(keywords) + '"></label>',
                    '<label>Slug / URL SEO<input id="r4v5SeoSlugMirror" type="text" value="' + escapeHtml(slug) + '" readonly></label>',
                    '<label>Canonical URL<input data-r4v5-seo-field id="r4v5SeoCanonical" type="url" name="meta[seo][canonical_url]" value="' + escapeHtml(stored('meta[seo][canonical_url]', canonical)) + '"></label>',
                    '<input type="hidden" name="meta[seo_title]" id="r4v5SeoLegacyTitle" value="' + escapeHtml(title) + '">',
                    '<input type="hidden" name="meta[seo_description]" id="r4v5SeoLegacyDescription" value="' + escapeHtml(desc) + '">',
                    '<input type="hidden" name="meta[seo_keywords]" id="r4v5SeoLegacyKeywords" value="' + escapeHtml(keywords) + '">',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Robots</div><div class="r4v5-seo-row">',
                    '<label>Index<select data-r4v5-seo-field id="r4v5SeoRobotsIndex" name="meta[seo][robots][index]">' + selectOptions(['index','noindex'], robotsIndex) + '</select></label>',
                    '<label>Follow<select data-r4v5-seo-field id="r4v5SeoRobotsFollow" name="meta[seo][robots][follow]">' + selectOptions(['follow','nofollow'], robotsFollow) + '</select></label>',
                '</div><div class="r4v5-seo-grid">',
                    '<label class="r4v5-seo-check"><input data-r4v5-seo-field type="checkbox" name="meta[seo][robots][advanced][noarchive]" value="1"' + (stored('meta[seo][robots][advanced][noarchive]', '0') === '1' ? ' checked' : '') + '> noarchive</label>',
                    '<label class="r4v5-seo-check"><input data-r4v5-seo-field type="checkbox" name="meta[seo][robots][advanced][nosnippet]" value="1"' + (stored('meta[seo][robots][advanced][nosnippet]', '0') === '1' ? ' checked' : '') + '> nosnippet</label>',
                    '<label class="r4v5-seo-check"><input data-r4v5-seo-field type="checkbox" name="meta[seo][robots][advanced][noimageindex]" value="1"' + (stored('meta[seo][robots][advanced][noimageindex]', '0') === '1' ? ' checked' : '') + '> noimageindex</label>',
                    '<div class="r4v5-seo-row"><label>max-snippet<input data-r4v5-seo-field type="number" name="meta[seo][robots][advanced][max_snippet]" value="' + escapeHtml(stored('meta[seo][robots][advanced][max_snippet]', '')) + '" placeholder="es. 160"></label><label>max-image-preview<select data-r4v5-seo-field name="meta[seo][robots][advanced][max_image_preview]">' + selectOptions(['large','standard','none'], stored('meta[seo][robots][advanced][max_image_preview]', 'large')) + '</select></label></div>',
                    '<label>max-video-preview<input data-r4v5-seo-field type="number" name="meta[seo][robots][advanced][max_video_preview]" value="' + escapeHtml(stored('meta[seo][robots][advanced][max_video_preview]', '')) + '" placeholder="es. -1"></label>',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Open Graph</div><div class="r4v5-seo-grid">',
                    '<label>og:title<input data-r4v5-seo-field id="r4v5SeoOgTitle" type="text" name="meta[seo][og][title]" value="' + escapeHtml(ogTitle) + '"></label>',
                    '<label>og:description<textarea data-r4v5-seo-field id="r4v5SeoOgDescription" name="meta[seo][og][description]">' + escapeHtml(ogDesc) + '</textarea></label>',
                    '<label>og:image<input data-r4v5-seo-field id="r4v5SeoOgImage" type="url" name="meta[seo][og][image]" value="' + escapeHtml(ogImage) + '" placeholder="https://.../immagine-1200x630.jpg"></label>',
                    '<div class="r4v5-seo-row"><label>og:type<select data-r4v5-seo-field name="meta[seo][og][type]">' + selectOptions(['website','article','product'], ogType) + '</select></label><label>og:url<input data-r4v5-seo-field id="r4v5SeoOgUrl" type="url" name="meta[seo][og][url]" value="' + escapeHtml(ogUrl) + '"></label></div>',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Generatore immagine OG 1200x630</div><div class="r4v5-seo-grid">',
                    '<label>Titolo immagine<input id="r4v5SeoOgGeneratorTitle" type="text" value="' + escapeHtml(ogTitle || title) + '"></label>',
                    '<label>Sottotitolo immagine<textarea id="r4v5SeoOgGeneratorSubtitle">' + escapeHtml(ogDesc || desc) + '</textarea></label>',
                    '<div class="r4v5-seo-og-tools"><button type="button" class="r4v5-seo-copy" id="r4v5SeoGenerateOgButton">Genera immagine OG</button><button type="button" class="r4v5-seo-copy" id="r4v5SeoOpenOgImage">Apri immagine</button></div>',
                    '<div class="r4v5-seo-og-status" id="r4v5SeoOgGeneratorStatus">Usa questo pulsante per creare una immagine social 1200x630 direttamente dal modulo SEO.</div>',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Twitter Card</div><div class="r4v5-seo-grid">',
                    '<label>twitter:title<input data-r4v5-seo-field id="r4v5SeoTwitterTitle" type="text" name="meta[seo][twitter][title]" value="' + escapeHtml(twTitle) + '"></label>',
                    '<label>twitter:description<textarea data-r4v5-seo-field id="r4v5SeoTwitterDescription" name="meta[seo][twitter][description]">' + escapeHtml(twDesc) + '</textarea></label>',
                    '<label>twitter:image<input data-r4v5-seo-field id="r4v5SeoTwitterImage" type="url" name="meta[seo][twitter][image]" value="' + escapeHtml(twImage) + '" placeholder="https://.../immagine.jpg"></label>',
                    '<label>twitter:card<select data-r4v5-seo-field name="meta[seo][twitter][card]">' + selectOptions(['summary_large_image','summary'], twCard) + '</select></label>',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Schema.org</div><div class="r4v5-seo-grid">',
                    '<label>Tipo schema<select data-r4v5-seo-field id="r4v5SeoSchemaType" name="meta[seo][schema][type]">' + selectOptions(['WebPage','Article','LocalBusiness','SoftwareApplication','Product','FAQPage'], schemaType) + '</select></label>',
                    '<label class="r4v5-seo-check"><input type="hidden" name="meta[seo][schema][enabled]" value="0"><input data-r4v5-seo-field type="checkbox" name="meta[seo][schema][enabled]" value="1"' + (schemaEnabled ? ' checked' : '') + '> Abilita JSON-LD</label>',
                    '<label>JSON-LD custom opzionale<textarea data-r4v5-seo-field name="meta[seo][schema][custom_json]" placeholder="Lascia vuoto per generazione dinamica">' + escapeHtml(customJson) + '</textarea></label>',
                '</div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Anteprima Google</div><div class="r4v5-seo-preview-google"><div class="r4v5-seo-preview-url" id="r4v5SeoPreviewUrl"></div><div class="r4v5-seo-preview-title" id="r4v5SeoPreviewTitle"></div><div class="r4v5-seo-preview-desc" id="r4v5SeoPreviewDesc"></div></div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Anteprima Social</div><div class="r4v5-seo-preview-card"><div class="r4v5-seo-preview-img" id="r4v5SeoPreviewImage">og:image 1200x630 consigliata</div><div class="r4v5-seo-preview-body"><strong id="r4v5SeoPreviewSocialTitle"></strong><span id="r4v5SeoPreviewSocialDesc"></span></div></div></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Controlli qualità</div><ul class="r4v5-seo-warnings" id="r4v5SeoWarnings"></ul></div>',
                '<div class="r4v5-seo-section"><div class="r4v5-seo-section-title">Prompt ChatGPT</div><div class="r4v5-seo-prompt" id="r4v5SeoPrompt"></div><button type="button" class="r4v5-seo-copy" id="r4v5SeoCopyPrompt">Copia prompt</button></div>',
            '</div>'
        ].join('');

        bind(panel);
        syncHomepageUrlFields(panel);
        update(panel);
    }

    function bind(panel) {
        panel.addEventListener('input', function () { writeStorage(); update(panel); });
        panel.addEventListener('change', function () { writeStorage(); update(panel); });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'is_homepage') {
                syncHomepageUrlFields(panel, true);
                writeStorage();
                update(panel);
            }
        });

        var form = document.getElementById('r4v5PageForm');
        if (form && !form.dataset.r4v5SeoStorageBound) {
            form.dataset.r4v5SeoStorageBound = '1';
            form.addEventListener('submit', function () {
                syncHomepageUrlFields(panel, true);
                writeStorage();
            });
        }

        var copy = panel.querySelector('#r4v5SeoCopyPrompt');
        if (copy) {
            copy.addEventListener('click', function () {
                var prompt = panel.querySelector('#r4v5SeoPrompt');
                if (!prompt) return;
                navigator.clipboard && navigator.clipboard.writeText(prompt.textContent || '');
                copy.textContent = 'Prompt copiato';
                setTimeout(function () { copy.textContent = 'Copia prompt'; }, 1600);
            });
        }

        var generate = panel.querySelector('#r4v5SeoGenerateOgButton');
        if (generate) {
            generate.addEventListener('click', function () {
                generateOgImage(panel, generate);
            });
        }

        var open = panel.querySelector('#r4v5SeoOpenOgImage');
        if (open) {
            open.addEventListener('click', function () {
                var url = value('meta[seo][og][image]', '') || value('meta[seo][twitter][image]', '');
                if (url) window.open(url, '_blank', 'noopener');
            });
        }
    }

    function syncHomepageUrlFields(panel, force) {
        var canonical = pagePublicUrl();
        var canonicalField = document.getElementById('r4v5SeoCanonical');
        var ogUrlField = document.getElementById('r4v5SeoOgUrl');

        if (canonicalField && (force || isHomepage() || !canonicalField.value.trim())) {
            canonicalField.value = canonical;
        }

        if (ogUrlField && (force || isHomepage() || !ogUrlField.value.trim())) {
            ogUrlField.value = canonical;
        }

        if (panel) update(panel);
    }

    function generateOgImage(panel, button) {
        var endpoint = resolveGenerateOgUrl();
        var status = document.getElementById('r4v5SeoOgGeneratorStatus');
        var titleField = document.getElementById('r4v5SeoOgGeneratorTitle');
        var subtitleField = document.getElementById('r4v5SeoOgGeneratorSubtitle');

        if (!endpoint) {
            if (status) status.textContent = 'Endpoint di generazione non disponibile.';
            return;
        }

        button.disabled = true;
        button.textContent = 'Generazione...';
        if (status) status.textContent = 'Generazione immagine OG 1200x630 in corso...';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                title: titleField ? titleField.value : value('meta[seo][og][title]', value('meta[seo][title]', pageTitle())),
                subtitle: subtitleField ? subtitleField.value : value('meta[seo][og][description]', value('meta[seo][description]', pageExcerpt()))
            })
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.ok || !data.url) throw new Error('Risposta non valida');

                setValue('meta[seo][og][image]', data.url);
                setValue('meta[seo][twitter][image]', data.url);
                setValue('meta[seo][twitter][card]', 'summary_large_image');
                writeStorage();
                update(panel);

                if (status) {
                    status.textContent = (data.message || 'Immagine OG generata correttamente.') + ' Dimensioni: ' + (data.width || 1200) + 'x' + (data.height || 630) + '.';
                }
            })
            .catch(function (error) {
                if (status) status.textContent = 'Errore generazione immagine OG: ' + (error && error.message ? error.message : 'operazione non riuscita') + '.';
            })
            .finally(function () {
                button.disabled = false;
                button.textContent = 'Genera immagine OG';
            });
    }

    function setText(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text || '';
    }

    function update(panel) {
        var title = value('meta[seo][title]', pageTitle());
        var desc = value('meta[seo][description]', pageExcerpt());
        var keyword = value('meta[seo][focus_keyword]', '');
        var canonical = value('meta[seo][canonical_url]', pagePublicUrl());
        var ogTitle = value('meta[seo][og][title]', title);
        var ogDesc = value('meta[seo][og][description]', desc);
        var ogImage = value('meta[seo][og][image]', '');
        var twitterTitle = value('meta[seo][twitter][title]', ogTitle || title);
        var twitterDesc = value('meta[seo][twitter][description]', ogDesc || desc);
        var twitterImage = value('meta[seo][twitter][image]', ogImage);
        var robotsIndex = value('meta[seo][robots][index]', 'index');
        var html = window.R4EditorV5 && window.R4EditorV5.getHtml ? window.R4EditorV5.getHtml() : value('visual_html', '');

        var legacyTitle = document.getElementById('r4v5SeoLegacyTitle');
        var legacyDesc = document.getElementById('r4v5SeoLegacyDescription');
        var legacyKeywords = document.getElementById('r4v5SeoLegacyKeywords');
        if (legacyTitle) legacyTitle.value = title;
        if (legacyDesc) legacyDesc.value = desc;
        if (legacyKeywords) legacyKeywords.value = keyword;

        setText('r4v5SeoPreviewUrl', canonical);
        setText('r4v5SeoPreviewTitle', title || pageTitle());
        setText('r4v5SeoPreviewDesc', desc || pageExcerpt());
        setText('r4v5SeoPreviewSocialTitle', twitterTitle || ogTitle || title);
        setText('r4v5SeoPreviewSocialDesc', twitterDesc || ogDesc || desc);

        var img = document.getElementById('r4v5SeoPreviewImage');
        if (img) {
            var previewImage = twitterImage || ogImage;
            img.textContent = previewImage ? '' : 'og:image 1200x630 consigliata';
            img.style.backgroundImage = previewImage ? 'url(' + previewImage + ')' : '';
        }

        var titleCount = panel.querySelector('[data-r4v5-count="title"]');
        var descCount = panel.querySelector('[data-r4v5-count="description"]');
        if (titleCount) titleCount.textContent = title.length + ' caratteri. Consigliato: 45-60.';
        if (descCount) descCount.textContent = desc.length + ' caratteri. Consigliato: 140-160.';

        var warnings = [];
        addCheck(warnings, title.length > 0, 'Meta title presente', 'Meta title mancante');
        addRange(warnings, title.length, 45, 60, 'Lunghezza title buona', 'Title fuori range consigliato');
        addCheck(warnings, desc.length > 0, 'Meta description presente', 'Meta description mancante');
        addRange(warnings, desc.length, 140, 160, 'Lunghezza description buona', 'Description fuori range consigliato');
        if (keyword) {
            addCheck(warnings, title.toLowerCase().indexOf(keyword.toLowerCase()) !== -1, 'Keyword nel title', 'Keyword non presente nel title');
            addCheck(warnings, desc.toLowerCase().indexOf(keyword.toLowerCase()) !== -1, 'Keyword nella description', 'Keyword non presente nella description');
        }
        addCheck(warnings, /<h1\b/i.test(html), 'H1 rilevato nella pagina', 'H1 non rilevato nel contenuto');
        addCheck(warnings, canonical.length > 0, 'Canonical presente', 'Canonical mancante');
        addCheck(warnings, ogImage.length > 0, 'og:image presente', 'og:image mancante o non configurata');
        if (robotsIndex === 'noindex') warnings.push({ type: 'bad', text: 'Attenzione: noindex attivo, la pagina non sarà indicizzabile.' });

        var list = document.getElementById('r4v5SeoWarnings');
        if (list) {
            list.innerHTML = warnings.map(function (item) {
                return '<li class="r4v5-seo-' + item.type + '">' + escapeHtml(item.text) + '</li>';
            }).join('');
        }

        setText('r4v5SeoPrompt', [
            'Agisci come SEO specialist senior e copywriter per R4Software.',
            'Pagina: ' + pageTitle(),
            'Slug: ' + pageSlug(),
            'URL pubblico corretto: ' + pagePublicUrl(),
            'Focus keyword: ' + (keyword || '[da definire]'),
            'Meta title attuale: ' + (title || '[vuoto]'),
            'Meta description attuale: ' + (desc || '[vuota]'),
            'Twitter description attuale: ' + (twitterDesc || '[vuota]'),
            '',
            'Genera 5 alternative di meta title massimo 60 caratteri, 5 meta description massimo 160 caratteri, suggerisci H1/H2, keyword correlate e una proposta di testo pagina orientata alla conversione.',
            '',
            'Poi genera anche una proposta per immagine Open Graph 1200x630 coerente con il titolo e con il brand R4Software.'
        ].join('\n'));
    }

    function addCheck(list, ok, okText, badText) {
        list.push({ type: ok ? 'ok' : 'bad', text: ok ? okText : badText });
    }

    function addRange(list, value, min, max, okText, warnText) {
        list.push({ type: value >= min && value <= max ? 'ok' : 'warn', text: value >= min && value <= max ? okText : warnText });
    }

    function boot() {
        ensureStyle();
        buildPanel(document.querySelector('[data-r4v5-left-panel="seo"]'));
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
