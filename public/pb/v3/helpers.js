export function normalizeTarget(v) {
    return v === '_blank' ? '_blank' : '_self';
}

export function traitValue(component, name, fallback = '') {
    const val = component.get(name);
    return val !== undefined && val !== null ? val : fallback;
}

export function formatHtml(html) {
    html = String(html || '').replace(/>\s*</g, '><').trim();
    if (!html) return '';

    const tokens = html
        .replace(/>\s*</g, '><')
        .replace(/</g, '\n<')
        .replace(/^\n/, '')
        .split('\n')
        .filter(Boolean);

    const voidTags = new Set([
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr'
    ]);

    let indent = 0;
    const out = [];

    tokens.forEach((raw) => {
        const token = raw.trim();
        if (!token) return;

        if (token.startsWith('</')) {
            indent = Math.max(indent - 1, 0);
        }

        out.push('  '.repeat(indent) + token);

        const openTagMatch = token.match(/^<([a-zA-Z0-9-]+)/);
        const closeSelf =
            token.endsWith('/>') ||
            token.startsWith('<?') ||
            token.startsWith('<!') ||
            token.startsWith('</');

        if (openTagMatch && !closeSelf) {
            const tag = openTagMatch[1].toLowerCase();
            const isInlineClosed = token.includes(`</${tag}>`);
            if (!voidTags.has(tag) && !isInlineClosed) {
                indent++;
            }
        }
    });

    return out.join('\n');
}

export function formatCss(css) {
    css = String(css || '').trim();
    if (!css) return '';

    css = css
        .replace(/\/\*[\s\S]*?\*\//g, (m) => m + '\n')
        .replace(/\s+/g, ' ')
        .replace(/\s*{\s*/g, ' {\n')
        .replace(/\s*}\s*/g, '\n}\n')
        .replace(/\s*;\s*/g, ';\n')
        .replace(/\s*:\s*/g, ': ')
        .replace(/\n{2,}/g, '\n');

    const lines = css.split('\n').map(l => l.trim()).filter(Boolean);
    let indent = 0;
    const out = [];

    lines.forEach((line) => {
        if (line.startsWith('}')) {
            indent = Math.max(indent - 1, 0);
        }

        out.push('  '.repeat(indent) + line);

        if (line.endsWith('{')) {
            indent++;
        }
    });

    return out.join('\n');
}

export function safeParseJson(value, fallback = null) {
    try {
        const parsed = JSON.parse(value);
        return parsed ?? fallback;
    } catch (e) {
        return fallback;
    }
}

export function syncEditorToFields(editor, fields) {
    const html = editor.getHtml() || '';
    const css = editor.getCss() || '';
    let project = null;

    try {
        project = editor.getProjectData();
    } catch (e) {
        console.warn('Errore getProjectData()', e);
        project = null;
    }

    if (fields.htmlField) fields.htmlField.value = html;
    if (fields.cssField) fields.cssField.value = css;

    if (fields.jsonField) {
        try {
            fields.jsonField.value = project ? JSON.stringify(project) : '';
        } catch (e) {
            console.warn('Errore serializzazione visual_json', e);
            fields.jsonField.value = '';
        }
    }
}

export function applyAnimationRuntime(editor) {
    const doc = editor.Canvas.getDocument();
    if (!doc) return;

    doc.querySelectorAll('[data-anim]').forEach((el) => {
        const dur = parseInt(el.getAttribute('data-anim-duration') || '600', 10);
        const del = parseInt(el.getAttribute('data-anim-delay') || '0', 10);

        el.style.animationDuration = `${isNaN(dur) ? 600 : dur}ms`;
        el.style.animationDelay = `${isNaN(del) ? 0 : del}ms`;
    });
}

export function getAnimationTraits() {
    return [
        {
            type: 'select',
            name: 'data-anim',
            label: 'Animazione',
            options: [
                { id: '', name: 'Nessuna' },
                { id: 'fade-in', name: 'Fade In' },
                { id: 'fade-up', name: 'Fade Up' },
                { id: 'fade-left', name: 'Fade Left' },
                { id: 'fade-right', name: 'Fade Right' },
                { id: 'zoom-in', name: 'Zoom In' },
                { id: 'flip-up', name: 'Flip Up' }
            ]
        },
        { type: 'number', name: 'data-anim-duration', label: 'Durata (ms)', placeholder: '600' },
        { type: 'number', name: 'data-anim-delay', label: 'Delay (ms)', placeholder: '0' }
    ];
}

export function mergeTraits(baseTraits = []) {
    return [...baseTraits, ...getAnimationTraits()];
}

export function getHoverOptions() {
    return [
        { id: '', name: 'Nessuno' },
        { id: 'lift', name: 'Lift' },
        { id: 'glow', name: 'Glow' },
        { id: 'border', name: 'Bordo colorato' },
        { id: 'scale', name: 'Scale' }
    ];
}
