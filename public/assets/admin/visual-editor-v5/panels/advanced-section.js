(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function cfg() { return window.R4EditorV5Config || {}; }
  function box() { return document.getElementById(cfg().controlsId || 'r4v5Controls'); }

  function esc(v) {
    return String(v || '').replace(/[&<>'"]/g, function (c) {
      return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' })[c];
    });
  }

  function attrs(c) { return c && c.getAttributes ? c.getAttributes() || {} : {}; }
  function style(c) { return c && c.getStyle ? c.getStyle() || {} : {}; }
  function attr(c, k, f) { var a = attrs(c); return a[k] || f || ''; }
  function css(c, k, f) { var s = style(c); return s[k] || f || ''; }

  function isAdvancedSection(c) {
    return !!(c && attrs(c)['data-r4v5-advanced-section']);
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
  function num(id, value, min, max, step) { return '<input type="number" id="' + id + '" min="' + min + '" max="' + max + '" step="' + (step || 1) + '" value="' + esc(value || '') + '">'; }
  function color(id, value) { return '<input type="color" id="' + id + '" value="' + esc(value || '#ffffff') + '">'; }
  function select(id, options, value) {
    return '<select id="' + id + '">' + options.map(function (o) {
      return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(value || '') ? ' selected' : '') + '>' + esc(o.label) + '</option>';
    }).join('') + '</select>';
  }

  function removePanel() {
    var old = document.getElementById('r4v5AdvancedSectionPanelWrap');
    if (old) old.remove();
  }

  function findInner(c) {
    if (!c || !c.find) return null;
    return (c.find('[data-r4v5-advanced-inner]') || [])[0] || null;
  }

  function findGrid(c) {
    if (!c || !c.find) return null;
    return (c.find('[data-r4v5-advanced-grid]') || [])[0] || null;
  }

  function findCols(c) {
    if (!c || !c.find) return [];
    return c.find('[data-r4v5-advanced-col]') || [];
  }

  function applyResponsiveCss(c, colsD, colsT, colsM, gapX, gapY) {
    var id = attr(c, 'id', '');
    if (!id) {
      id = 'r4v5-adv-' + Math.random().toString(36).slice(2, 9);
      setAttrs(c, { id: id });
    }

    var css = '' +
      '#' + id + ' [data-r4v5-advanced-grid]{display:grid;grid-template-columns:repeat(' + colsD + ',minmax(0,1fr));gap:' + gapY + 'px ' + gapX + 'px;}\n' +
      '@media (max-width: 991px){#' + id + ' [data-r4v5-advanced-grid]{grid-template-columns:repeat(' + colsT + ',minmax(0,1fr));}}\n' +
      '@media (max-width: 640px){#' + id + ' [data-r4v5-advanced-grid]{grid-template-columns:repeat(' + colsM + ',minmax(0,1fr));}}';

    var ed = editor();
    if (ed && ed.CssComposer) {
      try {
        var allCss = ed.getCss ? ed.getCss() : '';
        var re = new RegExp('/\\* R4V5_ADV_' + id + '_START \\*/[\\s\\S]*?/\\* R4V5_ADV_' + id + '_END \\*/', 'g');
        var nextCss = (allCss || '').replace(re, '').trim();
        nextCss += '\n/* R4V5_ADV_' + id + '_START */\n' + css + '\n/* R4V5_ADV_' + id + '_END */\n';
        if (ed.setStyle) ed.setStyle(nextCss);
      } catch (e) {}
    }
  }

  function apply(c) {
    var colsD = Math.max(1, Math.min(6, parseInt(document.getElementById('r4v5AdvColsD').value || '3', 10)));
    var colsT = Math.max(1, Math.min(4, parseInt(document.getElementById('r4v5AdvColsT').value || '2', 10)));
    var colsM = Math.max(1, Math.min(2, parseInt(document.getElementById('r4v5AdvColsM').value || '1', 10)));
    var gapX = Math.max(0, Math.min(120, parseInt(document.getElementById('r4v5AdvGapX').value || '24', 10)));
    var gapY = Math.max(0, Math.min(120, parseInt(document.getElementById('r4v5AdvGapY').value || '24', 10)));
    var maxW = document.getElementById('r4v5AdvMaxW').value || '1120px';
    var padding = document.getElementById('r4v5AdvPadding').value || '84px 24px';
    var margin = document.getElementById('r4v5AdvMargin').value || '0';
    var minH = document.getElementById('r4v5AdvMinH').value || '0';
    var bgMode = document.getElementById('r4v5AdvBgMode').value || 'none';
    var bgColor = document.getElementById('r4v5AdvBgColor').value || '#ffffff';
    var textColor = document.getElementById('r4v5AdvTextColor').value || '#111827';

    setAttrs(c, {
      'data-r4v5-advanced-section': '1',
      'data-r4v5-cols-desktop': String(colsD),
      'data-r4v5-cols-tablet': String(colsT),
      'data-r4v5-cols-mobile': String(colsM),
      'data-r4v5-gap-x': String(gapX),
      'data-r4v5-gap-y': String(gapY)
    });

    var styleObj = { padding: padding, margin: margin, 'min-height': minH, color: textColor };
    if (bgMode === 'color') {
      styleObj.background = bgColor;
      styleObj['background-color'] = bgColor;
      styleObj['background-image'] = '';
    } else if (bgMode === 'none') {
      styleObj.background = '';
      styleObj['background-color'] = '';
      styleObj['background-image'] = '';
    }
    c.addStyle(styleObj);

    var inner = findInner(c);
    if (inner) inner.addStyle({ 'max-width': maxW, margin: '0 auto' });

    var grid = findGrid(c);
    if (grid) grid.addStyle({ display: 'grid', 'grid-template-columns': 'repeat(' + colsD + ',minmax(0,1fr))', gap: gapY + 'px ' + gapX + 'px' });

    applyResponsiveCss(c, colsD, colsT, colsM, gapX, gapY);
    changed();
  }

  function addColumn(c) {
    var grid = findGrid(c);
    if (!grid || !grid.append) return;
    grid.append('<article data-r4v5-advanced-col="1" style="padding:28px;border-radius:24px;background:#f8fafc;border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(15,23,42,.06);"><h3 style="font-size:24px;line-height:1.2;font-weight:900;margin:0 0 10px;color:#111827;">Nuova colonna</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Contenuto modificabile della nuova colonna.</p></article>');
    changed();
    render(c);
  }

  function removeLastColumn(c) {
    var cols = findCols(c);
    if (cols.length <= 1) {
      alert('La sezione deve avere almeno una colonna.');
      return;
    }
    cols[cols.length - 1].remove();
    changed();
    render(c);
  }

  function normalizeColumns(c) {
    findCols(c).forEach(function (col) {
      col.addStyle({ padding: '28px', 'border-radius': '24px', background: '#f8fafc', border: '1px solid #e5e7eb', 'box-shadow': '0 14px 34px rgba(15,23,42,.06)' });
    });
    changed();
  }

  function render(c) {
    var root = box();
    if (!root) return;
    removePanel();
    if (!isAdvancedSection(c)) return;

    var inner = findInner(c);
    var grid = findGrid(c);
    var cols = findCols(c);

    var bg = css(c, 'background-color', '#ffffff') || '#ffffff';
    if (bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)') bg = '#ffffff';

    var html = '' +
      '<div id="r4v5AdvancedSectionPanelWrap">' +
      '<div class="r4v5-panel-title">Sezione avanzata</div>' +
      '<div class="r4v5-page-box" id="r4v5AdvancedSectionPanel">' +
        '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">Gestisci griglia, colonne e layout della sezione selezionata.</div>' +
        '<div class="r4v5-field-row">' +
          field('Desktop cols', num('r4v5AdvColsD', attr(c, 'data-r4v5-cols-desktop', '3'), 1, 6, 1)) +
          field('Tablet cols', num('r4v5AdvColsT', attr(c, 'data-r4v5-cols-tablet', '2'), 1, 4, 1)) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Mobile cols', num('r4v5AdvColsM', attr(c, 'data-r4v5-cols-mobile', '1'), 1, 2, 1)) +
          field('Colonne attuali', '<input type="text" readonly value="' + esc(cols.length) + '">') +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Gap colonne px', num('r4v5AdvGapX', attr(c, 'data-r4v5-gap-x', '24'), 0, 120, 1)) +
          field('Gap righe px', num('r4v5AdvGapY', attr(c, 'data-r4v5-gap-y', '24'), 0, 120, 1)) +
        '</div>' +
        field('Max width interno', text('r4v5AdvMaxW', inner ? css(inner, 'max-width', '1120px') : '1120px', '1120px / 1280px / 100%')) +
        field('Padding sezione', text('r4v5AdvPadding', css(c, 'padding', '84px 24px'), '84px 24px')) +
        field('Margin sezione', text('r4v5AdvMargin', css(c, 'margin', '0'), '0 / 32px 0')) +
        field('Altezza minima', text('r4v5AdvMinH', css(c, 'min-height', '0'), '0 / 100vh / 640px')) +
        '<div class="r4v5-panel-title">Aspetto base</div>' +
        field('Sfondo rapido', select('r4v5AdvBgMode', [{value:'keep',label:'Mantieni'}, {value:'none',label:'Nessuno'}, {value:'color',label:'Colore'}], 'keep')) +
        '<div class="r4v5-field-row">' + field('Colore sfondo', color('r4v5AdvBgColor', bg)) + field('Colore testo', color('r4v5AdvTextColor', css(c, 'color', '#111827') || '#111827')) + '</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5AdvApply">Applica sezione</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5AdvAddCol">Aggiungi colonna</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5AdvNormalizeCols">Normalizza card</button>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5AdvRemoveCol">Rimuovi ultima colonna</button>' +
      '</div></div>';

    root.insertAdjacentHTML('afterbegin', html);

    document.getElementById('r4v5AdvApply').addEventListener('click', function () { apply(c); });
    document.getElementById('r4v5AdvAddCol').addEventListener('click', function () { addColumn(c); });
    document.getElementById('r4v5AdvRemoveCol').addEventListener('click', function () { removeLastColumn(c); });
    document.getElementById('r4v5AdvNormalizeCols').addEventListener('click', function () { normalizeColumns(c); });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (c) { window.setTimeout(function () { render(c); }, 70); });
        ed.on('component:deselected', removePanel);
        ed.on('component:remove', removePanel);
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
