(function () {
    'use strict';

    const ICONS = {
        dashboard: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>',
        pages: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path><path d="M20 12H9"></path><path d="M5 4v16"></path></svg>',
        legacy: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v6h6"></path><path d="M12 7v5l3 2"></path></svg>',
        eye: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        external: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h7v7"></path><path d="M10 14L21 3"></path><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path></svg>',
        image: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.5"></circle><path d="M21 16l-5-5L5 19"></path></svg>',
        layers: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l9 5-9 5-9-5 9-5z"></path><path d="M3 12l9 5 9-5"></path><path d="M3 17l9 5 9-5"></path></svg>',
        focus: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V5a1 1 0 0 1 1-1h4"></path><path d="M15 4h4a1 1 0 0 1 1 1v4"></path><path d="M20 15v4a1 1 0 0 1-1 1h-4"></path><path d="M9 20H5a1 1 0 0 1-1-1v-4"></path><circle cx="12" cy="12" r="2"></circle></svg>',
        undo: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14l-5-5 5-5"></path><path d="M4 9h11a5 5 0 0 1 0 10h-2"></path></svg>',
        redo: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 14l5-5-5-5"></path><path d="M20 9H9a5 5 0 0 0 0 10h2"></path></svg>',
        monitorPlay: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M10 8l5 3-5 3V8z"></path><path d="M8 21h8"></path><path d="M12 17v4"></path></svg>',
        trash: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 15H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        desktop: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path></svg>',
        tablet: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="3" width="12" height="18" rx="2"></rect><path d="M11 17h2"></path></svg>',
        mobile: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path></svg>',
        save: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14l2 2v16H3V5a2 2 0 0 1 2-2z"></path><path d="M7 3v6h10V3"></path><path d="M7 21v-8h10v8"></path></svg>',
        rocket: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 16.5c-1 1-1.5 3-1.5 4.5 1.5 0 3.5-.5 4.5-1.5"></path><path d="M9 15l-3-3c1.4-3.7 4.7-7.8 9-9l6 6c-1.2 4.3-5.3 7.6-9 9l-3-3z"></path><circle cx="15" cy="9" r="2"></circle></svg>'
    };

    const TEXT_ICON_MAP = [
        ['Dashboard', 'dashboard'],
        ['Esci / Pagine', 'pages'],
        ['V3 legacy', 'legacy'],
        ['Anteprima admin', 'eye'],
        ['Apri pubblica', 'external'],
        ['Salva bozza', 'save'],
        ['Pubblica', 'rocket']
    ];

    const COMMAND_ICON_MAP = { media: 'image', layers: 'layers', focus: 'focus', undo: 'undo', redo: 'redo', preview: 'monitorPlay', clear: 'trash' };
    const DEVICE_ICON_MAP = { Desktop: 'desktop', Tablet: 'tablet', Mobile: 'mobile' };

    function injectStyles() {
        if (document.getElementById('r4v4-topbar-icons-style')) return;
        const style = document.createElement('style');
        style.id = 'r4v4-topbar-icons-style';
        style.textContent = `
.r4v4-topbar .r4v4-actions .r4v4-btn,.r4v4-topbar .r4v4-actions a.r4v4-btn,.r4v4-topbar .r4v4-actions button.r4v4-btn{position:relative!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:0!important;width:38px!important;min-width:38px!important;height:38px!important;padding:0!important;overflow:visible!important;white-space:nowrap!important}
.r4v4-topbar .r4v4-action-icon{display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;min-width:17px;line-height:1;opacity:.96;pointer-events:none}
.r4v4-topbar .r4v4-action-icon svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.r4v4-topbar .r4v4-action-label{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
.r4v4-topbar .r4v4-btn[data-r4-tooltip]::after{content:attr(data-r4-tooltip);position:absolute;left:50%;top:calc(100% + 9px);transform:translateX(-50%) translateY(-4px);z-index:99999;max-width:220px;padding:7px 9px;border-radius:9px;background:#111827;color:#fff;font-size:11px;font-weight:800;line-height:1.2;white-space:nowrap;box-shadow:0 12px 28px rgba(15,23,42,.32);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .14s ease,transform .14s ease,visibility .14s ease}
.r4v4-topbar .r4v4-btn[data-r4-tooltip]::before{content:"";position:absolute;left:50%;top:calc(100% + 3px);transform:translateX(-50%) translateY(-4px);border:6px solid transparent;border-bottom-color:#111827;z-index:100000;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .14s ease,transform .14s ease,visibility .14s ease}
.r4v4-topbar .r4v4-btn[data-r4-tooltip]:hover::after,.r4v4-topbar .r4v4-btn[data-r4-tooltip]:focus-visible::after,.r4v4-topbar .r4v4-btn[data-r4-tooltip]:hover::before,.r4v4-topbar .r4v4-btn[data-r4-tooltip]:focus-visible::before{opacity:1;visibility:visible;transform:translateX(-50%) translateY(0)}
.r4v4-topbar .r4v4-btn-primary .r4v4-action-icon,.r4v4-topbar .r4v4-btn-secondary .r4v4-action-icon,.r4v4-topbar .r4v4-btn-danger .r4v4-action-icon{opacity:1}
@media(max-width:1380px){.r4v4-topbar .r4v4-actions .r4v4-btn{width:36px!important;min-width:36px!important;height:36px!important}.r4v4-topbar .r4v4-action-icon,.r4v4-topbar .r4v4-action-icon svg{width:16px;height:16px;min-width:16px}}
`;
        document.head.appendChild(style);
    }

    function textOf(element) {
        return String(element.textContent || element.getAttribute('aria-label') || element.getAttribute('title') || '').replace(/\s+/g, ' ').trim();
    }

    function addIcon(element, iconName) {
        if (!element || !ICONS[iconName] || element.dataset.r4v4IconReady === '1') return;
        const label = textOf(element);
        element.dataset.r4v4IconReady = '1';
        element.dataset.r4Tooltip = label;
        element.setAttribute('title', label);
        element.setAttribute('aria-label', label);
        const icon = document.createElement('span');
        icon.className = 'r4v4-action-icon';
        icon.innerHTML = ICONS[iconName];
        const labelSpan = document.createElement('span');
        labelSpan.className = 'r4v4-action-label';
        labelSpan.textContent = label;
        element.textContent = '';
        element.appendChild(icon);
        element.appendChild(labelSpan);
    }

    function setStatusValue(value) {
        const cfg = window.R4VisualEditorV4 || {};
        const statusField = document.getElementById(cfg.statusFieldId || 'statusFieldV4');
        if (statusField) statusField.value = value;
    }

    function bindSubmitStatus() {
        const cfg = window.R4VisualEditorV4 || {};
        const form = document.getElementById(cfg.formId || 'pageFormV4');
        const statusField = document.getElementById(cfg.statusFieldId || 'statusFieldV4');
        if (!form || !statusField || form.dataset.r4SubmitStatusBound === '1') return;
        form.dataset.r4SubmitStatusBound = '1';
        form.querySelectorAll('[data-r4v4-submit-status]').forEach((button) => {
            const status = button.getAttribute('data-r4v4-submit-status');
            button.setAttribute('name', 'status');
            button.setAttribute('value', status);
            button.addEventListener('pointerdown', function () { setStatusValue(status); }, true);
            button.addEventListener('mousedown', function () { setStatusValue(status); }, true);
            button.addEventListener('click', function () { setStatusValue(status); }, true);
        });
        form.addEventListener('submit', function (event) {
            const submitter = event.submitter || document.activeElement;
            const status = submitter && submitter.getAttribute ? submitter.getAttribute('data-r4v4-submit-status') : '';
            if (status) setStatusValue(status);
        }, true);
    }

    function decorateTopbar() {
        injectStyles();
        bindSubmitStatus();
        const root = document.querySelector('.r4v4-topbar .r4v4-actions');
        if (!root) return false;
        TEXT_ICON_MAP.forEach(([label, iconName]) => {
            root.querySelectorAll('.r4v4-btn').forEach((button) => { if (textOf(button) === label) addIcon(button, iconName); });
        });
        Object.entries(COMMAND_ICON_MAP).forEach(([command, iconName]) => addIcon(root.querySelector('[data-r4v4-command="' + command + '"]'), iconName));
        Object.entries(DEVICE_ICON_MAP).forEach(([device, iconName]) => addIcon(root.querySelector('[data-r4v4-device="' + device + '"]'), iconName));
        root.querySelectorAll('[data-r4v4-submit-status="draft"]').forEach((button) => addIcon(button, 'save'));
        root.querySelectorAll('[data-r4v4-submit-status="published"]').forEach((button) => addIcon(button, 'rocket'));
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts++;
            if (decorateTopbar() || attempts > 40) window.clearInterval(timer);
        }, 120);
    });
})();
