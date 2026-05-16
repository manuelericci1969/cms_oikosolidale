(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function cfg() { return window.R4EditorV5Config || {}; }

  var state = { moving: false, menuOpen: false };

  function getWrapper(ed) { return ed && ed.getWrapper ? ed.getWrapper() : null; }
  function getCanvasDocument(ed) { return ed && ed.Canvas && ed.Canvas.getDocument ? ed.Canvas.getDocument() : null; }

  function collectionModels(component) {
    if (!component || !component.components) return [];
    try { return component.components().models || []; } catch (e) { return []; }
  }

  function rawTopLevelComponents(ed) {
    return collectionModels(getWrapper(ed));
  }

  function tagName(component) {
    try { return String(component.get('tagName') || (component.getName && component.getName()) || '').toLowerCase(); } catch (e) { return ''; }
  }

  function attrs(component) {
    try { return component.getAttributes ? component.getAttributes() || {} : {}; } catch (e) { return {}; }
  }

  function componentCid(component) {
    return component && (component.cid || (component.get && component.get('cid'))) || '';
  }

  function isVisualComponent(component) {
    if (!component) return false;
    var tag = tagName(component);
    var a = attrs(component);
    if (tag === 'script' || tag === 'style' || tag === 'meta' || tag === 'link') return false;
    if (a['data-r4v5-editor-helper'] || a['data-r4v5-hidden-in-editor']) return false;
    return true;
  }

  function visualTopLevelComponents(ed) {
    return rawTopLevelComponents(ed).filter(isVisualComponent);
  }

  function findComponentByCid(root, cid) {
    if (!root || !cid) return null;
    if (componentCid(root) === cid) return root;
    var children = collectionModels(root);
    for (var i = 0; i < children.length; i++) {
      var found = findComponentByCid(children[i], cid);
      if (found) return found;
    }
    return null;
  }

  function syncFields(ed) {
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

  function ensureCanvasStyle(ed) {
    var doc = getCanvasDocument(ed);
    if (!doc || doc.getElementById('r4v5-insert-slots-style')) return;

    var style = doc.createElement('style');
    style.id = 'r4v5-insert-slots-style';
    style.textContent = [
      'html,body{display:block!important;width:100%!important;min-width:100%!important;}',
      'body{float:none!important;position:relative!important;}',
      'body > section,body > header,body > footer,body > main,body > article,body > aside,body > nav,body > div:not([data-r4v5-insert-slot]):not(.gjs-selected){display:block;clear:both;}',
      'body > style,body > script,body > link,body > meta{display:none!important;}',
      '[data-r4v5-insert-slot]{box-sizing:border-box;width:min(1120px,calc(100% - 48px));min-height:82px;margin:16px auto;border:2px dashed #cbd5e1;border-radius:18px;background:rgba(248,250,252,.88);display:flex;align-items:center;justify-content:center;text-align:center;position:relative;z-index:9997;transition:border-color .18s ease,background .18s ease,box-shadow .18s ease;}',
      '[data-r4v5-insert-slot="between"]{min-height:44px;margin:8px auto;border-width:1px;opacity:.56;}',
      '[data-r4v5-insert-slot]:hover,[data-r4v5-insert-slot].is-active{opacity:1;border-color:#d946ef;background:rgba(253,244,255,.92);box-shadow:0 14px 34px rgba(217,70,239,.14);}',
      '.r4v5-insert-slot-inner{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#334155;}',
      '.r4v5-insert-slot-actions{display:inline-flex;align-items:center;gap:8px;}',
      '.r4v5-insert-slot-btn{width:38px;height:38px;border:0;border-radius:999px;background:#e5e7eb;color:#0f172a;font-size:22px;font-weight:900;line-height:1;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 14px rgba(15,23,42,.08);}',
      '.r4v5-insert-slot-btn:hover{background:#f0abfc;color:#581c87;}',
      '[data-r4v5-insert-slot="between"] .r4v5-insert-slot-btn{width:30px;height:30px;font-size:18px;}',
      '.r4v5-insert-slot-label{font-size:12px;font-weight:800;font-style:italic;color:#475569;}',
      '[data-r4v5-insert-slot="between"] .r4v5-insert-slot-label{display:none;}',
      '.r4v5-insert-menu{box-sizing:border-box;position:absolute;left:50%;top:calc(100% + 10px);transform:translateX(-50%);width:min(520px,calc(100vw - 32px));max-height:360px;overflow:hidden;z-index:10001;background:#ffffff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.24);text-align:left;color:#0f172a;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}',
      '[data-r4v5-insert-slot="final"] .r4v5-insert-menu{top:auto;bottom:calc(100% + 10px);}',
      '.r4v5-insert-menu-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#f8fafc;}',
      '.r4v5-insert-menu-title{font-size:13px;font-weight:950;color:#111827;}',
      '.r4v5-insert-menu-close{border:0;background:#e5e7eb;color:#0f172a;border-radius:999px;width:28px;height:28px;cursor:pointer;font-size:18px;line-height:1;}',
      '.r4v5-insert-menu-search{box-sizing:border-box;width:calc(100% - 28px);margin:12px 14px 8px;padding:9px 11px;border:1px solid #dbe3ee;border-radius:12px;font-size:13px;outline:0;}',
      '.r4v5-insert-menu-search:focus{border-color:#0d6efd;box-shadow:0 0 0 3px rgba(13,110,253,.12);}',
      '.r4v5-insert-menu-list{max-height:250px;overflow:auto;padding:4px 8px 10px;}',
      '.r4v5-insert-menu-group{padding:8px 6px 5px;font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:#64748b;}',
      '.r4v5-insert-menu-item{box-sizing:border-box;width:100%;display:flex;align-items:center;gap:10px;border:0;background:#ffffff;color:#111827;text-align:left;border-radius:12px;padding:9px 10px;cursor:pointer;font-size:13px;font-weight:850;}',
      '.r4v5-insert-menu-item:hover{background:#eef5ff;color:#0d6efd;}',
      '.r4v5-insert-menu-media{width:28px;height:28px;border-radius:9px;background:#f1f5f9;color:#0f172a;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;}',
      '.r4v5-insert-menu-media svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8;}',
      '.r4v5-insert-menu-empty{padding:18px 14px;color:#64748b;font-size:13px;text-align:center;}',
      '@media (max-width:640px){[data-r4v5-insert-slot]{width:calc(100% - 24px);min-height:72px;margin:12px auto;}.r4v5-insert-menu{width:calc(100vw - 24px);}}'
    ].join('\n');
    doc.head.appendChild(style);
  }

  function removeSlots(ed) {
    var doc = getCanvasDocument(ed);
    if (!doc) return;
    Array.prototype.slice.call(doc.querySelectorAll('[data-r4v5-insert-slot]')).forEach(function (node) { node.remove(); });
  }

  function forceCanvasRefresh(ed) {
    try { if (ed && ed.Canvas && ed.Canvas.refresh) ed.Canvas.refresh(); } catch (e) {}
    try { if (ed && ed.refresh) ed.refresh(); } catch (e) {}
    try {
      var doc = getCanvasDocument(ed);
      if (doc && doc.body) {
        doc.body.style.display = 'none';
        doc.body.offsetHeight;
        doc.body.style.display = '';
      }
    } catch (e) {}
  }

  function closeMenus(doc) {
    doc = doc || getCanvasDocument(editor());
    if (!doc) return;
    Array.prototype.slice.call(doc.querySelectorAll('.r4v5-insert-menu')).forEach(function (node) { node.remove(); });
    state.menuOpen = false;
  }

  function computeInsertTarget(slot) {
    var ed = editor();
    var wrapper = getWrapper(ed);
    var afterCid = slot ? slot.getAttribute('data-r4v5-after-cid') : '';
    var beforeCid = slot ? slot.getAttribute('data-r4v5-before-cid') : '';
    var after = findComponentByCid(wrapper, afterCid);
    var before = findComponentByCid(wrapper, beforeCid);
    var parent = null;
    var models = [];
    var index = 0;

    if (after) {
      parent = after.parent && after.parent() ? after.parent() : wrapper;
      models = collectionModels(parent);
      index = models.indexOf(after) + 1;
      if (index < 1) index = models.length;
      return { parent: parent, index: index, after: after, before: before };
    }

    if (before) {
      parent = before.parent && before.parent() ? before.parent() : wrapper;
      models = collectionModels(parent);
      index = models.indexOf(before);
      if (index < 0) index = models.length;
      return { parent: parent, index: index, after: after, before: before };
    }

    parent = wrapper;
    models = collectionModels(parent);
    return { parent: parent, index: models.length, after: null, before: null };
  }

  function setTarget(slot) {
    var target = computeInsertTarget(slot);
    window.R4V5InsertTarget = {
      afterCid: slot ? slot.getAttribute('data-r4v5-after-cid') || '' : '',
      beforeCid: slot ? slot.getAttribute('data-r4v5-before-cid') || '' : '',
      source: 'insert-slot'
    };

    var ed = editor();
    var doc = getCanvasDocument(ed);
    if (doc) {
      Array.prototype.slice.call(doc.querySelectorAll('[data-r4v5-insert-slot]')).forEach(function (node) {
        node.classList.toggle('is-active', node === slot);
      });
    }
    return target;
  }

  function widgets() {
    var registry = window.R4EditorV5Registry;
    if (!registry || typeof registry.widgets !== 'function') return [];
    return registry.widgets().filter(function (item) { return item && item.content; });
  }

  function fallbackMedia(label) {
    return '<span style="font-size:15px;font-weight:950;">' + String(label || '+').charAt(0).toUpperCase() + '</span>';
  }

  function groupedWidgets(filter) {
    var q = String(filter || '').toLowerCase().trim();
    var items = widgets().filter(function (item) {
      if (!q) return true;
      return String(item.label || item.key || '').toLowerCase().indexOf(q) >= 0 || String(item.category || '').toLowerCase().indexOf(q) >= 0;
    });
    var groups = {};
    items.forEach(function (item) {
      var cat = item.category || 'Base';
      if (!groups[cat]) groups[cat] = [];
      groups[cat].push(item);
    });
    return groups;
  }

  function insertContentAtSlot(slot, content) {
    var ed = editor();
    if (!ed || !content) return;

    try {
      state.moving = true;
      state.menuOpen = false;
      closeMenus();
      removeSlots(ed);
      var target = computeInsertTarget(slot);
      if (!target.parent || !target.parent.components) return;
      var added = target.parent.components().add(content, { at: target.index });
      var selected = Array.isArray(added) ? added[0] : added;
      if (selected && ed.select) ed.select(selected);
      window.R4V5InsertTarget = null;
      ed.trigger('component:update');
      ed.trigger('update');
      syncFields(ed);
      forceCanvasRefresh(ed);
    } catch (e) {
      console.warn('[R4 Editor V5] Inserimento widget non riuscito.', e);
    } finally {
      state.moving = false;
      window.setTimeout(function () { forceCanvasRefresh(editor()); refreshSoon(); }, 120);
    }
  }

  function renderMenuList(menu, slot, filter) {
    var list = menu.querySelector('.r4v5-insert-menu-list');
    if (!list) return;
    var groups = groupedWidgets(filter);
    var cats = Object.keys(groups);

    if (!cats.length) {
      list.innerHTML = '<div class="r4v5-insert-menu-empty">Nessun widget trovato.</div>';
      return;
    }

    list.innerHTML = cats.map(function (cat) {
      return '<div class="r4v5-insert-menu-group">' + esc(cat) + '</div>' + groups[cat].map(function (item) {
        return '<button type="button" class="r4v5-insert-menu-item" data-r4v5-widget-key="' + esc(item.key) + '">' +
          '<span class="r4v5-insert-menu-media">' + (item.media || fallbackMedia(item.label)) + '</span>' +
          '<span>' + esc(item.label || item.key) + '</span>' +
        '</button>';
      }).join('');
    }).join('');

    Array.prototype.slice.call(list.querySelectorAll('[data-r4v5-widget-key]')).forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var key = button.getAttribute('data-r4v5-widget-key');
        var item = widgets().filter(function (current) { return current.key === key; })[0];
        if (!item) return;
        insertContentAtSlot(slot, item.content);
      });
    });
  }

  function esc(v) {
    return String(v || '').replace(/[&<>'"]/g, function (c) {
      return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' })[c];
    });
  }

  function openWidgetMenu(slot) {
    var doc = slot.ownerDocument;
    closeMenus(doc);
    setTarget(slot);

    var menu = doc.createElement('div');
    menu.className = 'r4v5-insert-menu';
    menu.setAttribute('contenteditable', 'false');
    menu.innerHTML = [
      '<div class="r4v5-insert-menu-head">',
        '<div class="r4v5-insert-menu-title">Inserisci widget qui</div>',
        '<button type="button" class="r4v5-insert-menu-close" aria-label="Chiudi">×</button>',
      '</div>',
      '<input type="search" class="r4v5-insert-menu-search" placeholder="Cerca widget...">',
      '<div class="r4v5-insert-menu-list"></div>'
    ].join('');

    slot.appendChild(menu);
    state.menuOpen = true;

    var search = menu.querySelector('.r4v5-insert-menu-search');
    var close = menu.querySelector('.r4v5-insert-menu-close');
    renderMenuList(menu, slot, '');

    close.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      closeMenus(doc);
    });

    search.addEventListener('click', function (event) { event.stopPropagation(); });
    search.addEventListener('input', function () { renderMenuList(menu, slot, search.value); });
    setTimeout(function () { search.focus(); }, 20);
  }

  function openMedia(slot) {
    setTarget(slot);
    if (!window.R4V5Media || typeof window.R4V5Media.open !== 'function') {
      alert('Libreria Media V5 non disponibile.');
      return;
    }
    window.R4V5Media.open();
  }

  function quickSectionAt(slot) {
    insertContentAtSlot(slot, '<section style="display:block;width:100%;clear:both;position:relative;padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;"><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 18px;color:#111827;">Nuova sezione</h2><p style="font-size:18px;line-height:1.75;color:#475569;margin:0;">Contenuto modificabile della sezione.</p></div></section>');
  }

  function createSlot(doc, options) {
    options = options || {};
    var slot = doc.createElement('div');
    slot.setAttribute('data-r4v5-insert-slot', options.type || 'between');
    slot.setAttribute('data-r4v5-editor-helper', '1');
    slot.setAttribute('contenteditable', 'false');
    if (options.afterCid) slot.setAttribute('data-r4v5-after-cid', options.afterCid);
    if (options.beforeCid) slot.setAttribute('data-r4v5-before-cid', options.beforeCid);
    slot.innerHTML = [
      '<div class="r4v5-insert-slot-inner">',
        '<div class="r4v5-insert-slot-actions">',
          '<button type="button" class="r4v5-insert-slot-btn" data-r4v5-slot-action="widgets" title="Aggiungi widget qui">+</button>',
          '<button type="button" class="r4v5-insert-slot-btn" data-r4v5-slot-action="media" title="Apri media">▣</button>',
          '<button type="button" class="r4v5-insert-slot-btn" data-r4v5-slot-action="quick" title="Inserisci sezione rapida">✦</button>',
        '</div>',
        '<div class="r4v5-insert-slot-label">Trascina il widget qui</div>',
      '</div>'
    ].join('');

    slot.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var actionBtn = event.target.closest('[data-r4v5-slot-action]');
      var action = actionBtn ? actionBtn.getAttribute('data-r4v5-slot-action') : 'widgets';
      setTarget(slot);
      if (action === 'widgets') openWidgetMenu(slot);
      if (action === 'media') openMedia(slot);
      if (action === 'quick') quickSectionAt(slot);
    });

    return slot;
  }

  function componentEl(component) {
    try { return component && component.view && component.view.el ? component.view.el : null; } catch (e) { return null; }
  }

  function renderSlots() {
    if (state.menuOpen || state.moving) return;
    var ed = editor();
    var doc = getCanvasDocument(ed);
    if (!ed || !doc || !doc.body) return;

    ensureCanvasStyle(ed);
    removeSlots(ed);

    var comps = visualTopLevelComponents(ed);
    if (!comps.length) {
      doc.body.appendChild(createSlot(doc, { type: 'final' }));
      return;
    }

    comps.forEach(function (component, index) {
      var el = componentEl(component);
      if (!el || !el.parentNode) return;
      var before = comps[index + 1] || null;
      el.insertAdjacentElement('afterend', createSlot(doc, {
        type: index === comps.length - 1 ? 'final' : 'between',
        afterCid: componentCid(component),
        beforeCid: before ? componentCid(before) : ''
      }));
    });
  }

  function moveAddedComponent(component) {
    var ed = editor();
    var target = window.R4V5InsertTarget;
    if (!ed || !component || !target) return;
    if (state.moving) return;

    try {
      state.moving = true;
      removeSlots(ed);
      var fakeSlot = {
        getAttribute: function (name) {
          if (name === 'data-r4v5-after-cid') return target.afterCid || '';
          if (name === 'data-r4v5-before-cid') return target.beforeCid || '';
          return '';
        }
      };
      var insertTarget = computeInsertTarget(fakeSlot);
      if (!insertTarget.parent || !insertTarget.parent.components) return;
      if (component.move) {
        component.move(insertTarget.parent, { at: insertTarget.index });
      } else {
        component.remove({ temporary: true });
        insertTarget.parent.components().add(component, { at: insertTarget.index });
      }
      window.R4V5InsertTarget = null;
      ed.trigger('update');
      syncFields(ed);
      forceCanvasRefresh(ed);
    } catch (e) {
      console.warn('[R4 Editor V5] Impossibile riposizionare il widget nello slot selezionato.', e);
    } finally {
      state.moving = false;
      window.setTimeout(function () { refreshSoon(); }, 120);
    }
  }

  function refreshSoon() {
    window.clearTimeout(renderSlots._timer);
    renderSlots._timer = window.setTimeout(renderSlots, 90);
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('load canvas:frame:load component:add component:remove component:update component:drag:end component:selected device:select', refreshSoon);
        ed.on('component:add', moveAddedComponent);
        refreshSoon();
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
