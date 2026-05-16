(function () {
    'use strict';

    var HIDE_TERMS = [
        'url link',
        'link url',
        'href',
        'apertura',
        'apri in nuova',
        'target',
        'rel'
    ];

    function ensureStyle() {
        if (document.getElementById('r4v5-hide-native-link-traits-style')) return;
        var style = document.createElement('style');
        style.id = 'r4v5-hide-native-link-traits-style';
        style.textContent = [
            '.r4v5-native-link-trait-hidden{display:none!important}',
            '.r4v5-native-link-group-hidden{display:none!important}'
        ].join('');
        document.head.appendChild(style);
    }

    function textOf(el) {
        return String(el ? (el.textContent || '') : '').trim().toLowerCase();
    }

    function shouldHide(el) {
        var text = textOf(el);
        if (!text) return false;
        return HIDE_TERMS.some(function (term) { return text.indexOf(term) !== -1; });
    }

    function hideRowsInside(root) {
        root.querySelectorAll('.gjs-trt-trait, .gjs-field, .gjs-trt-trait__wrp, .r4v5-field, label').forEach(function (el) {
            if (!shouldHide(el)) return;
            var row = el.closest('.gjs-trt-trait, .gjs-field, .gjs-trt-trait__wrp, .r4v5-field, .gjs-trt-trait__wrp, div');
            if (row) row.classList.add('r4v5-native-link-trait-hidden');
            else el.classList.add('r4v5-native-link-trait-hidden');
        });
    }

    function hideWholeLinkGroups(root) {
        root.querySelectorAll('*').forEach(function (el) {
            var ownText = textOf(el);
            if (ownText !== 'link') return;

            var group = el.closest('.gjs-trt-category, .gjs-sm-sector, .gjs-block-category, .r4v5-control-panel, .r4v5-panel, section, fieldset, div');
            if (!group || group === root) return;

            var groupText = textOf(group);
            var looksLikeNativeLinkGroup = groupText.indexOf('url link') !== -1 && groupText.indexOf('apertura') !== -1;
            var hasQuickBox = group.querySelector && group.querySelector('#r4v5QuickLinkUrlInspector');

            if (looksLikeNativeLinkGroup && !hasQuickBox) {
                group.classList.add('r4v5-native-link-group-hidden');
            }
        });

        root.querySelectorAll('div, section, fieldset').forEach(function (group) {
            var groupText = textOf(group);
            var startsWithLink = groupText.indexOf('link') === 0;
            var looksLikeNativeLinkGroup = groupText.indexOf('url link') !== -1 && groupText.indexOf('apertura') !== -1;
            var hasQuickBox = group.querySelector && group.querySelector('#r4v5QuickLinkUrlInspector');

            if (startsWithLink && looksLikeNativeLinkGroup && !hasQuickBox) {
                group.classList.add('r4v5-native-link-group-hidden');
            }
        });
    }

    function hideDuplicatedTraits() {
        ensureStyle();

        var roots = [];
        var cfg = window.R4EditorV5Config || {};
        var traitRoot = cfg.traitsId ? document.getElementById(cfg.traitsId) : null;
        if (traitRoot) roots.push(traitRoot);

        document.querySelectorAll('[data-r4v5-inspector-panel="props"], .gjs-trt-traits, .gjs-traits').forEach(function (root) {
            if (roots.indexOf(root) === -1) roots.push(root);
        });

        roots.forEach(function (root) {
            if (!root) return;
            hideWholeLinkGroups(root);
            hideRowsInside(root);
        });
    }

    function boot() {
        hideDuplicatedTraits();
        var ed = window.R4EditorV5 || null;
        if (ed && !ed.__r4v5HideNativeLinkTraitsBound) {
            ed.__r4v5HideNativeLinkTraitsBound = true;
            ed.on('component:selected component:update trait:update load canvas:frame:load', function () {
                window.setTimeout(hideDuplicatedTraits, 40);
                window.setTimeout(hideDuplicatedTraits, 180);
                window.setTimeout(hideDuplicatedTraits, 500);
            });
        }
        return true;
    }

    if (!boot()) {
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            boot();
            if (attempts > 100) window.clearInterval(timer);
        }, 100);
    }

    document.addEventListener('click', function () {
        window.setTimeout(hideDuplicatedTraits, 60);
        window.setTimeout(hideDuplicatedTraits, 220);
    }, true);
})();
