(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function cfg() { return window.R4EditorV5Config || {}; }
  function box() { return document.getElementById(cfg().controlsId || 'r4v5Controls'); }
  function byId(id) { return document.getElementById(id); }

  function esc(v) {
    return String(v || '').replace(/[&<>'"]/g, function (c) {
      return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' })[c];
    });
  }

  function attrs(c) { try { return c && c.getAttributes ? c.getAttributes() || {} : {}; } catch (e) { return {}; } }
  function style(c) { try { return c && c.getStyle ? c.getStyle() || {} : {}; } catch (e) { return {}; } }
  function attr(c, k, f) { var a = attrs(c); return a[k] || f || ''; }
  function css(c, k, f) { var s = style(c); return s[k] || f || ''; }
  function isFooter(c) { return !!(c && attrs(c)['data-r4v5-footer-builder']); }

  function footerOf(c) {
    var cur = c;
    while (cur) {
      if (isFooter(cur)) return cur;
      cur = cur.parent && cur.parent() ? cur.parent() : null;
    }
    return null;
  }

  function setAttrs(c, next) {
    if (!c || !c.setAttributes) return;
    c.setAttributes(Object.assign({}, attrs(c), next));
  }

  function syncFields() {
    var ed = editor();
    var c = cfg();
    if (!ed) return;
    var html = c.htmlFieldId ? document.getElementById(c.htmlFieldId) : null;
    var css = c.cssFieldId ? document.getElementById(c.cssFieldId) : null;
    var json = c.jsonFieldId ? document.getElementById(c.jsonFieldId) : null;
    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) {
      try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {}
    }
  }

  function changed() {
    var ed = editor();
    if (ed && ed.trigger) ed.trigger('update');
    syncFields();
  }

  function field(label, html) { return '<label>' + esc(label) + html + '</label>'; }
  function text(id, value, ph) { return '<input type="text" id="' + id + '" value="' + esc(value || '') + '" placeholder="' + esc(ph || '') + '">'; }
  function textarea(id, value, ph) { return '<textarea id="' + id + '" placeholder="' + esc(ph || '') + '">' + esc(value || '') + '</textarea>'; }
  function num(id, value, min, max, step) { return '<input type="number" id="' + id + '" min="' + min + '" max="' + max + '" step="' + (step || 1) + '" value="' + esc(value || '') + '">'; }
  function color(id, value) { return '<input type="color" id="' + id + '" value="' + esc(value || '#ffffff') + '">'; }

  function removePanel() {
    var old = document.getElementById('r4v5FooterBuilderPanelWrap');
    if (old) old.remove();
  }

  function first(c, selector) { return c && c.find ? (c.find(selector) || [])[0] || null : null; }
  function all(c, selector) { return c && c.find ? c.find(selector) || [] : []; }
  function findInner(c) { return first(c, '[data-r4v5-footer-inner]'); }
  function findGrid(c) { return first(c, '[data-r4v5-footer-grid]'); }
  function findBottom(c) { return first(c, '[data-r4v5-footer-bottom]'); }
  function findCols(c) { return all(c, '[data-r4v5-footer-col]'); }
  function findBrand(c) { return first(c, '[data-r4v5-footer-brand]') || first(c, 'h3'); }
  function findDescription(c) { return first(c, '[data-r4v5-footer-description]'); }
  function findCta(c) { return first(c, '[data-r4v5-footer-cta]'); }
  function findContactsCol(c) { return first(c, '[data-r4v5-footer-col="contacts"]') || first(c, '[data-r4v5-footer-col=contacts]'); }
  function findContactsText(c) { var col = findContactsCol(c); return col ? first(col, '[data-r4v5-footer-contacts-text]') || first(col, 'p') : null; }

  function getOwnContent(c) {
    try { return c && c.get ? c.get('content') || '' : ''; } catch (e) { return ''; }
  }

  function textRecursive(c) {
    if (!c) return '';
    var own = getOwnContent(c);
    var out = own ? String(own) : '';
    try {
      if (c.components) {
        c.components().forEach(function (child) {
          var childText = textRecursive(child);
          if (childText) out += (out ? ' ' : '') + childText;
        });
      }
    } catch (e) {}
    return out;
  }

  function componentText(c, fallback) {
    var txt = textRecursive(c);
    txt = String(txt || '').replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '').replace(/\s+\n/g, '\n').replace(/\n\s+/g, '\n').replace(/[ \t]{2,}/g, ' ').trim();
    return txt || fallback || '';
  }

  function setComponentHtml(c, value) {
    if (!c) return;
    if (c.components) c.components(value || '');
    else if (c.set) c.set('content', value || '');
  }

  function nl2br(v) { return esc(v || '').replace(/\n/g, '<br>'); }

  function findBottomSpans(c) {
    var bottom = findBottom(c);
    return bottom ? all(bottom, 'span') : [];
  }

  function findCopyright(c) { return first(c, '[data-r4v5-footer-copyright]') || findBottomSpans(c)[0] || null; }
  function findLegalWrap(c) { return first(c, '[data-r4v5-footer-legal]') || findBottomSpans(c)[1] || null; }
  function findLegalLinks(c) { var wrap = findLegalWrap(c); return wrap ? all(wrap, 'a') : []; }

  function ensureFooterStructure(c) {
    var copyright = findCopyright(c);
    if (copyright) setAttrs(copyright, { 'data-r4v5-footer-copyright': '1' });
    var legal = findLegalWrap(c);
    if (legal) setAttrs(legal, { 'data-r4v5-footer-legal': '1' });
    var contacts = findContactsText(c);
    if (contacts) setAttrs(contacts, { 'data-r4v5-footer-contacts-text': '1' });
    var brand = findBrand(c);
    if (brand) setAttrs(brand, { 'data-r4v5-footer-brand': '1' });
  }

  function setInput(id, value) {
    var el = byId(id);
    if (el) el.value = value || '';
  }

  function applyResponsiveCss(c, colsD, colsT, colsM, gapX, gapY, mutedColor) {
    var id = attr(c, 'id', '');
    if (!id) {
      id = 'r4v5-footer-' + Math.random().toString(36).slice(2, 9);
      setAttrs(c, { id: id });
    }

    var cssText = '' +
      '#' + id + ' [data-r4v5-footer-grid]{display:grid;grid-template-columns:1.4fr repeat(' + Math.max(1, colsD - 1) + ',minmax(0,1fr));gap:' + gapY + 'px ' + gapX + 'px;}\n' +
      '#' + id + ' [data-r4v5-footer-grid] a{transition:color .18s ease,opacity .18s ease;}\n' +
      '#' + id + ' [data-r4v5-footer-grid] a:hover,#' + id + ' [data-r4v5-footer-legal] a:hover{color:#ffffff!important;opacity:1;}\n' +
      '#' + id + ' [data-r4v5-footer-legal] a{color:' + (mutedColor || '#94a3b8') + ';text-decoration:none;}\n' +
      '@media (max-width: 991px){#' + id + ' [data-r4v5-footer-grid]{grid-template-columns:repeat(' + colsT + ',minmax(0,1fr));}}\n' +
      '@media (max-width: 640px){#' + id + ' [data-r4v5-footer-grid]{grid-template-columns:repeat(' + colsM + ',minmax(0,1fr));}#' + id + ' [data-r4v5-footer-bottom]{display:block;}#' + id + ' [data-r4v5-footer-bottom] span{display:block;margin-bottom:10px;}}';

    var ed = editor();
    if (ed && ed.setStyle) {
      try {
        var allCss = ed.getCss ? ed.getCss() : '';
        var re = new RegExp('/\\* R4V5_FOOTER_' + id + '_START \\*/[\\s\\S]*?/\\* R4V5_FOOTER_' + id + '_END \\*/', 'g');
        var nextCss = (allCss || '').replace(re, '').trim();
        nextCss += '\n/* R4V5_FOOTER_' + id + '_START */\n' + cssText + '\n/* R4V5_FOOTER_' + id + '_END */\n';
        ed.setStyle(nextCss);
      } catch (e) {}
    }
  }

  function apply(c) {
    c = footerOf(c) || c;
    if (!isFooter(c)) return;
    ensureFooterStructure(c);

    var colsD = Math.max(1, Math.min(6, parseInt(byId('r4v5FooterColsD').value || '4', 10)));
    var colsT = Math.max(1, Math.min(4, parseInt(byId('r4v5FooterColsT').value || '2', 10)));
    var colsM = Math.max(1, Math.min(2, parseInt(byId('r4v5FooterColsM').value || '1', 10)));
    var gapX = Math.max(0, Math.min(120, parseInt(byId('r4v5FooterGapX').value || '32', 10)));
    var gapY = Math.max(0, Math.min(120, parseInt(byId('r4v5FooterGapY').value || '28', 10)));
    var maxW = byId('r4v5FooterMaxW').value || '1180px';
    var padding = byId('r4v5FooterPadding').value || '72px 24px 28px';
    var margin = byId('r4v5FooterMargin').value || '0';
    var bgColor = byId('r4v5FooterBg').value || '#0f172a';
    var textColor = byId('r4v5FooterText').value || '#e5e7eb';
    var mutedColor = byId('r4v5FooterMuted').value || '#cbd5e1';
    var accentColor = byId('r4v5FooterAccent').value || '#0d6efd';
    var brandText = byId('r4v5FooterBrandText').value || 'R4Software';
    var descText = byId('r4v5FooterDescriptionText').value || '';
    var contactsText = byId('r4v5FooterContactsText').value || '';
    var ctaText = byId('r4v5FooterCtaText').value || 'Richiedi consulenza';
    var ctaHref = byId('r4v5FooterCtaHref').value || '/contatti';
    var copyrightText = byId('r4v5FooterCopyrightText').value || '';
    var privacyLabel = byId('r4v5FooterPrivacyLabel').value || 'Privacy';
    var privacyHref = byId('r4v5FooterPrivacyHref').value || '/privacy-policy';
    var cookieLabel = byId('r4v5FooterCookieLabel').value || 'Cookie';
    var cookieHref = byId('r4v5FooterCookieHref').value || '/cookie-policy';

    setAttrs(c, {
      'data-r4v5-footer-builder': '1',
      'data-r4v5-footer-cols-desktop': String(colsD),
      'data-r4v5-footer-cols-tablet': String(colsT),
      'data-r4v5-footer-cols-mobile': String(colsM),
      'data-r4v5-footer-gap-x': String(gapX),
      'data-r4v5-footer-gap-y': String(gapY)
    });

    c.addStyle({ padding: padding, margin: margin, background: bgColor, 'background-color': bgColor, color: textColor });

    var inner = findInner(c);
    if (inner) inner.addStyle({ 'max-width': maxW, margin: '0 auto' });

    var grid = findGrid(c);
    if (grid) grid.addStyle({ display: 'grid', 'grid-template-columns': '1.4fr repeat(' + Math.max(1, colsD - 1) + ',minmax(0,1fr))', gap: gapY + 'px ' + gapX + 'px' });

    var brand = findBrand(c);
    if (brand) { setComponentHtml(brand, esc(brandText)); brand.addStyle({ color: '#ffffff' }); }

    var desc = findDescription(c);
    if (desc) { setComponentHtml(desc, nl2br(descText)); desc.addStyle({ color: mutedColor }); }

    var contacts = findContactsText(c);
    if (contacts) { setComponentHtml(contacts, nl2br(contactsText)); contacts.addStyle({ color: mutedColor }); }

    var cta = findCta(c);
    if (cta) {
      setAttrs(cta, { href: ctaHref });
      setComponentHtml(cta, esc(ctaText));
      cta.addStyle({ background: accentColor, color: '#ffffff' });
    }

    findCols(c).forEach(function (col) {
      col.addStyle({ 'min-width': '0' });
      all(col, 'h4').forEach(function (h) { h.addStyle({ color: '#ffffff' }); });
      all(col, 'a').forEach(function (a) { a.addStyle({ color: mutedColor, 'text-decoration': 'none' }); });
      all(col, 'p').forEach(function (p) { p.addStyle({ color: mutedColor }); });
    });

    var bottom = findBottom(c);
    if (bottom) bottom.addStyle({ color: '#94a3b8', 'border-top': '1px solid rgba(255,255,255,.12)' });

    var copyright = findCopyright(c);
    if (copyright) { setAttrs(copyright, { 'data-r4v5-footer-copyright': '1' }); setComponentHtml(copyright, esc(copyrightText)); }

    var legal = findLegalWrap(c);
    if (legal) {
      setAttrs(legal, { 'data-r4v5-footer-legal': '1' });
      setComponentHtml(legal, '<a href="' + esc(privacyHref) + '" style="color:#94a3b8;text-decoration:none;">' + esc(privacyLabel) + '</a> · <a href="' + esc(cookieHref) + '" style="color:#94a3b8;text-decoration:none;">' + esc(cookieLabel) + '</a>');
    }

    applyResponsiveCss(c, colsD, colsT, colsM, gapX, gapY, mutedColor);
    changed();
    window.setTimeout(function () { render(c); }, 90);
  }

  function applyPreset(c, preset) {
    var presets = {
      dark: { bg:'#0f172a', text:'#e5e7eb', muted:'#cbd5e1', accent:'#0d6efd' },
      light: { bg:'#f8fafc', text:'#111827', muted:'#475569', accent:'#0d6efd' },
      blue: { bg:'#071b3a', text:'#eaf3ff', muted:'#bfdbfe', accent:'#38bdf8' },
      r4: { bg:'#0b1220', text:'#e5e7eb', muted:'#cbd5e1', accent:'#0d6efd' }
    };
    var p = presets[preset] || presets.dark;
    setInput('r4v5FooterBg', p.bg);
    setInput('r4v5FooterText', p.text);
    setInput('r4v5FooterMuted', p.muted);
    setInput('r4v5FooterAccent', p.accent);

    if (preset === 'r4') {
      setInput('r4v5FooterBrandText', byId('r4v5FooterBrandText').value || 'R4Software');
      setInput('r4v5FooterDescriptionText', byId('r4v5FooterDescriptionText').value || 'Software house specializzata in CRM, siti web professionali, automazioni e soluzioni digitali su misura per aziende e professionisti.');
      setInput('r4v5FooterContactsText', byId('r4v5FooterContactsText').value || 'Olbia, Sardegna\nEmail: info@r4software.it\nWeb: www.r4software.it');
      setInput('r4v5FooterCtaText', byId('r4v5FooterCtaText').value || 'Richiedi consulenza');
      setInput('r4v5FooterCtaHref', byId('r4v5FooterCtaHref').value || '/contatti');
      setInput('r4v5FooterCopyrightText', byId('r4v5FooterCopyrightText').value || '© 2026 R4Software s.r.l. Tutti i diritti riservati.');
      setInput('r4v5FooterPrivacyLabel', byId('r4v5FooterPrivacyLabel').value || 'Privacy');
      setInput('r4v5FooterPrivacyHref', byId('r4v5FooterPrivacyHref').value || '/privacy-policy');
      setInput('r4v5FooterCookieLabel', byId('r4v5FooterCookieLabel').value || 'Cookie');
      setInput('r4v5FooterCookieHref', byId('r4v5FooterCookieHref').value || '/cookie-policy');
    }
  }

  function addLinkColumn(c) {
    c = footerOf(c) || c;
    var grid = findGrid(c);
    if (!grid || !grid.append) return;
    grid.append('<nav data-r4v5-footer-col="custom" aria-label="Footer custom" style="min-width:0;"><h4 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;margin:0 0 14px;color:#ffffff;">Nuova colonna</h4><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Link uno</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Link due</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Link tre</a></nav>');
    changed();
    render(c);
  }

  function removeLastColumn(c) {
    c = footerOf(c) || c;
    var cols = findCols(c);
    if (cols.length <= 1) {
      alert('Il footer deve avere almeno una colonna.');
      return;
    }
    cols[cols.length - 1].remove();
    changed();
    render(c);
  }

  function normalizeLinks(c) {
    c = footerOf(c) || c;
    findCols(c).forEach(function (col) {
      col.addStyle({ 'min-width': '0' });
      all(col, 'a').forEach(function (a) {
        a.addStyle({ display: 'block', color: '#cbd5e1', 'text-decoration': 'none', margin: '0 0 10px', 'font-size': '15px' });
      });
    });
    changed();
  }

  function render(selected) {
    var c = footerOf(selected) || selected;
    var root = box();
    if (!root) return;
    removePanel();
    if (!isFooter(c)) return;
    ensureFooterStructure(c);

    var inner = findInner(c);
    var cols = findCols(c);
    var brand = findBrand(c);
    var desc = findDescription(c);
    var contacts = findContactsText(c);
    var cta = findCta(c);
    var copyright = findCopyright(c);
    var links = findLegalLinks(c);
    var bg = css(c, 'background-color', '#0f172a') || '#0f172a';
    if (bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)') bg = '#0f172a';

    var html = '' +
      '<div id="r4v5FooterBuilderPanelWrap">' +
      '<div class="r4v5-panel-title">Footer builder</div>' +
      '<div class="r4v5-page-box" id="r4v5FooterBuilderPanel">' +
        '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">I dati vengono letti dal footer selezionato. I preset compilano i campi senza applicare automaticamente.</div>' +
        '<div class="r4v5-field-row">' +
          field('Desktop cols', num('r4v5FooterColsD', attr(c, 'data-r4v5-footer-cols-desktop', '4'), 1, 6, 1)) +
          field('Tablet cols', num('r4v5FooterColsT', attr(c, 'data-r4v5-footer-cols-tablet', '2'), 1, 4, 1)) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Mobile cols', num('r4v5FooterColsM', attr(c, 'data-r4v5-footer-cols-mobile', '1'), 1, 2, 1)) +
          field('Colonne attuali', '<input type="text" readonly value="' + esc(cols.length) + '">') +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Gap colonne px', num('r4v5FooterGapX', attr(c, 'data-r4v5-footer-gap-x', '32'), 0, 120, 1)) +
          field('Gap righe px', num('r4v5FooterGapY', attr(c, 'data-r4v5-footer-gap-y', '28'), 0, 120, 1)) +
        '</div>' +
        field('Max width interno', text('r4v5FooterMaxW', inner ? css(inner, 'max-width', '1180px') : '1180px', '1180px / 1280px / 100%')) +
        field('Padding footer', text('r4v5FooterPadding', css(c, 'padding', '72px 24px 28px'), '72px 24px 28px')) +
        field('Margin footer', text('r4v5FooterMargin', css(c, 'margin', '0'), '0 / 32px 0')) +
        '<div class="r4v5-panel-title">Contenuto aziendale</div>' +
        field('Nome brand', text('r4v5FooterBrandText', componentText(brand, 'R4Software'), 'R4Software')) +
        field('Descrizione', textarea('r4v5FooterDescriptionText', componentText(desc, ''), 'Descrizione aziendale')) +
        field('Contatti', textarea('r4v5FooterContactsText', componentText(contacts, ''), 'Una riga per contatto')) +
        '<div class="r4v5-panel-title">CTA</div>' +
        '<div class="r4v5-field-row">' +
          field('Testo CTA', text('r4v5FooterCtaText', componentText(cta, 'Richiedi consulenza'), 'Richiedi consulenza')) +
          field('Link CTA', text('r4v5FooterCtaHref', attr(cta, 'href', '/contatti'), '/contatti')) +
        '</div>' +
        '<div class="r4v5-panel-title">Copyright e link legali</div>' +
        field('Copyright', text('r4v5FooterCopyrightText', componentText(copyright, ''), '© 2026 Azienda')) +
        '<div class="r4v5-field-row">' +
          field('Privacy label', text('r4v5FooterPrivacyLabel', componentText(links[0], 'Privacy'), 'Privacy')) +
          field('Privacy link', text('r4v5FooterPrivacyHref', attr(links[0], 'href', '/privacy-policy'), '/privacy-policy')) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Cookie label', text('r4v5FooterCookieLabel', componentText(links[1], 'Cookie'), 'Cookie')) +
          field('Cookie link', text('r4v5FooterCookieHref', attr(links[1], 'href', '/cookie-policy'), '/cookie-policy')) +
        '</div>' +
        '<div class="r4v5-panel-title">Colori</div>' +
        '<div class="r4v5-field-row">' + field('Sfondo', color('r4v5FooterBg', bg)) + field('Testo', color('r4v5FooterText', css(c, 'color', '#e5e7eb') || '#e5e7eb')) + '</div>' +
        '<div class="r4v5-field-row">' + field('Testo soft', color('r4v5FooterMuted', '#cbd5e1')) + field('Accent / CTA', color('r4v5FooterAccent', '#0d6efd')) + '</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5FooterApply">Applica footer</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterPresetR4">Preset R4Software</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterPresetDark">Preset dark</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterPresetLight">Preset light</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterPresetBlue">Preset blu</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterAddCol">Aggiungi colonna link</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5FooterNormalize">Normalizza link</button>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5FooterRemoveCol">Rimuovi ultima colonna</button>' +
      '</div></div>';

    root.insertAdjacentHTML('afterbegin', html);
    byId('r4v5FooterApply').addEventListener('click', function () { apply(c); });
    byId('r4v5FooterPresetR4').addEventListener('click', function () { applyPreset(c, 'r4'); });
    byId('r4v5FooterPresetDark').addEventListener('click', function () { applyPreset(c, 'dark'); });
    byId('r4v5FooterPresetLight').addEventListener('click', function () { applyPreset(c, 'light'); });
    byId('r4v5FooterPresetBlue').addEventListener('click', function () { applyPreset(c, 'blue'); });
    byId('r4v5FooterAddCol').addEventListener('click', function () { addLinkColumn(c); });
    byId('r4v5FooterRemoveCol').addEventListener('click', function () { removeLastColumn(c); });
    byId('r4v5FooterNormalize').addEventListener('click', function () { normalizeLinks(c); });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (c) { window.setTimeout(function () { render(c); }, 70); });
        ed.on('component:deselected', function (c) { if (!footerOf(c)) removePanel(); });
        ed.on('component:remove', removePanel);
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
