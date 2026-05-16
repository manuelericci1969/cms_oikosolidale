(function () {
  'use strict';

  function tabName(button) {
    return button ? (button.getAttribute('data-r4v5-inspector-tab') || 'base') : 'base';
  }

  function panelName(panel) {
    return panel ? (panel.getAttribute('data-r4v5-inspector-panel') || 'base') : 'base';
  }

  function activateTab(name) {
    name = name || 'base';

    document.querySelectorAll('[data-r4v5-inspector-tab]').forEach(function (button) {
      var active = tabName(button) === name;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
      button.setAttribute('tabindex', active ? '0' : '-1');
    });

    document.querySelectorAll('[data-r4v5-inspector-panel]').forEach(function (panel) {
      var active = panelName(panel) === name;
      panel.classList.toggle('is-active', active);
      if (active) panel.removeAttribute('hidden');
      else panel.setAttribute('hidden', 'hidden');
    });
  }

  function bind() {
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-r4v5-inspector-tab]');
      if (!button) return;
      event.preventDefault();
      event.stopPropagation();
      activateTab(tabName(button));
    }, true);

    document.addEventListener('keydown', function (event) {
      var button = event.target.closest && event.target.closest('[data-r4v5-inspector-tab]');
      if (!button) return;
      if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

      var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-r4v5-inspector-tab]'));
      var index = tabs.indexOf(button);
      if (index < 0) return;
      event.preventDefault();

      var nextIndex = event.key === 'ArrowRight' ? index + 1 : index - 1;
      if (nextIndex >= tabs.length) nextIndex = 0;
      if (nextIndex < 0) nextIndex = tabs.length - 1;
      tabs[nextIndex].focus();
      activateTab(tabName(tabs[nextIndex]));
    });

    activateTab('base');
  }

  window.R4V5InspectorTabs = { activate: activateTab };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
})();
