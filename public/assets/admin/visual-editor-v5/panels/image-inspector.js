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

  function tag(c) { return c && c.get ? String(c.get('tagName') || '').toLowerCase() : ''; }
  function type(c) { return c && c.get ? String(c.get('type') || '').toLowerCase() : ''; }
  function attrs(c) { return c && c.getAttributes ? c.getAttributes() || {} : {}; }
  function style(c) { return c && c.getStyle ? c.getStyle() || {} : {}; }
  function attr(c, k, f) { var a = attrs(c); return a[k] || f || ''; }
  function css(c, k, f) { var s = style(c); return s[k] || f || ''; }

  function isImage(c) {
    return !!c && (tag(c) === 'img' || type(c) === 'image');
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

  function setAttrs(c, next) {
    if (!c || !c.setAttributes) return;
    c.setAttributes(Object.assign({}, attrs(c), next));
  }

  function field(label, html) {
    return '<label>' + esc(label) + html + '</label>';
  }

  function text(id, value, ph) {
    return '<input type="text" id="' + id + '" value="' + esc(value || '') + '" placeholder="' + esc(ph || '') + '">';
  }

  function select(id, options, value) {
    return '<select id="' + id + '">' + options.map(function (o) {
      return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(value || '') ? ' selected' : '') + '>' + esc(o.label) + '</option>';
    }).join('') + '</select>';
  }

  function removePanel() {
    var old = document.getElementById('r4v5ImageInspectorPanelWrap');
    if (old) old.remove();
  }

  function currentLinkWrapper(c) {
    if (!c || !c.parent) return null;
    var parent = c.parent();
    if (!parent || !parent.get) return null;
    var pTag = String(parent.get('tagName') || '').toLowerCase();
    return pTag === 'a' ? parent : null;
  }

  function applyLink(c, url, target) {
    var wrapper = currentLinkWrapper(c);
    url = String(url || '').trim();
    if (!url) {
      if (wrapper && wrapper.components) {
        var parent = wrapper.parent();
        var index = parent && parent.components ? parent.components().indexOf(wrapper) : -1;
        wrapper.replaceWith(c.toHTML ? c.toHTML() : '<img>');
        if (parent && parent.components && index >= 0) {
          var next = parent.components().at(index);
          if (next && editor()) editor().select(next);
        }
      }
      return;
    }

    if (wrapper) {
      setAttrs(wrapper, { href: url, target: target === '_blank' ? '_blank' : '', rel: target === '_blank' ? 'noopener noreferrer' : '' });
      return;
    }

    var html = c.toHTML ? c.toHTML() : '';
    if (!html) return;
    var newHtml = '<a href="' + esc(url) + '"' + (target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '') + ' style="display:inline-block;text-decoration:none;color:inherit;">' + html + '</a>';
    var inserted = c.replaceWith(newHtml);
    if (inserted && inserted.length && editor()) editor().select(inserted[0]);
  }

  function apply(c) {
    var nextAttrs = {
      src: document.getElementById('r4v5ImgSrc').value || attr(c, 'src', ''),
      alt: document.getElementById('r4v5ImgAlt').value || '',
      title: document.getElementById('r4v5ImgTitle').value || '',
      loading: document.getElementById('r4v5ImgLoading').value || 'lazy'
    };

    if (!nextAttrs.title) delete nextAttrs.title;
    if (!nextAttrs.loading) delete nextAttrs.loading;
    setAttrs(c, nextAttrs);

    c.addStyle({
      width: document.getElementById('r4v5ImgWidth').value || '',
      height: document.getElementById('r4v5ImgHeight').value || '',
      'object-fit': document.getElementById('r4v5ImgFit').value || '',
      'object-position': document.getElementById('r4v5ImgPosition').value || '',
      'border-radius': document.getElementById('r4v5ImgRadius').value || '',
      'box-shadow': document.getElementById('r4v5ImgShadow').value || '',
      display: document.getElementById('r4v5ImgDisplay').value || 'block'
    });

    applyLink(c, document.getElementById('r4v5ImgLink').value, document.getElementById('r4v5ImgTarget').value);
    changed();
  }

  function reset(c) {
    c.addStyle({ width:'100%', height:'auto', 'object-fit':'', 'object-position':'', 'border-radius':'', 'box-shadow':'', display:'block' });
    setAttrs(c, { loading: 'lazy' });
    changed();
    render(c);
  }

  function openMedia() {
    if (!window.R4V5Media || typeof window.R4V5Media.open !== 'function') {
      alert('Media V5 non disponibile.');
      return;
    }
    window.R4V5Media.open();
  }

  function render(c) {
    var root = box();
    if (!root) return;
    removePanel();
    if (!isImage(c)) return;

    var wrapper = currentLinkWrapper(c);
    var linkAttrs = wrapper ? attrs(wrapper) : {};

    var html = '' +
      '<div id="r4v5ImageInspectorPanelWrap">' +
      '<div class="r4v5-panel-title">Immagine</div>' +
      '<div class="r4v5-page-box" id="r4v5ImageInspectorPanel">' +
        '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">Controlli dedicati per immagine selezionata.</div>' +
        field('URL immagine', text('r4v5ImgSrc', attr(c, 'src', ''), '/storage/media/immagine.jpg')) +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5ImgChooseMedia">Sostituisci da Media</button>' +
        field('Alt text', text('r4v5ImgAlt', attr(c, 'alt', ''), 'Descrizione immagine per SEO/accessibilità')) +
        field('Title', text('r4v5ImgTitle', attr(c, 'title', ''), 'Titolo opzionale')) +
        '<div class="r4v5-field-row">' +
          field('Width', text('r4v5ImgWidth', css(c, 'width', ''), 'es. 100%')) +
          field('Height', text('r4v5ImgHeight', css(c, 'height', ''), 'es. auto / 320px')) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Object fit', select('r4v5ImgFit', [{value:'',label:'Default'},{value:'cover',label:'Cover'},{value:'contain',label:'Contain'},{value:'fill',label:'Fill'},{value:'none',label:'None'}], css(c, 'object-fit', ''))) +
          field('Display', select('r4v5ImgDisplay', [{value:'block',label:'Block'},{value:'inline-block',label:'Inline block'},{value:'inline',label:'Inline'}], css(c, 'display', 'block'))) +
        '</div>' +
        field('Object position', select('r4v5ImgPosition', [
          {value:'',label:'Default'}, {value:'center center',label:'Centro'}, {value:'top center',label:'Alto centro'}, {value:'bottom center',label:'Basso centro'}, {value:'center left',label:'Centro sinistra'}, {value:'center right',label:'Centro destra'}
        ], css(c, 'object-position', ''))) +
        '<div class="r4v5-field-row">' +
          field('Radius', text('r4v5ImgRadius', css(c, 'border-radius', ''), 'es. 22px')) +
          field('Ombra', select('r4v5ImgShadow', [
            {value:'',label:'Nessuna'},
            {value:'0 10px 30px rgba(15,23,42,.08)',label:'Soft'},
            {value:'0 20px 50px rgba(15,23,42,.14)',label:'Strong'},
            {value:'0 24px 70px rgba(15,23,42,.22)',label:'Premium'}
          ], css(c, 'box-shadow', ''))) +
        '</div>' +
        '<div class="r4v5-panel-title">Link immagine</div>' +
        field('URL link', text('r4v5ImgLink', linkAttrs.href || '', 'https://...')) +
        field('Target', select('r4v5ImgTarget', [{value:'',label:'Stessa scheda'},{value:'_blank',label:'Nuova scheda'}], linkAttrs.target || '')) +
        field('Loading', select('r4v5ImgLoading', [{value:'lazy',label:'Lazy'},{value:'eager',label:'Eager'},{value:'',label:'Default'}], attr(c, 'loading', 'lazy'))) +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5ImgApply">Applica immagine</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5ImgPresetCover">Preset cover 100%</button>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5ImgReset">Reset stile</button>' +
      '</div></div>';

    root.insertAdjacentHTML('afterbegin', html);

    document.getElementById('r4v5ImgChooseMedia').addEventListener('click', openMedia);
    document.getElementById('r4v5ImgApply').addEventListener('click', function () { apply(c); });
    document.getElementById('r4v5ImgReset').addEventListener('click', function () { reset(c); });
    document.getElementById('r4v5ImgPresetCover').addEventListener('click', function () {
      document.getElementById('r4v5ImgWidth').value = '100%';
      document.getElementById('r4v5ImgHeight').value = '320px';
      document.getElementById('r4v5ImgFit').value = 'cover';
      document.getElementById('r4v5ImgPosition').value = 'center center';
      document.getElementById('r4v5ImgDisplay').value = 'block';
      apply(c);
    });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (c) { window.setTimeout(function () { render(c); }, 60); });
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
