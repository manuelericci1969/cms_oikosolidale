// public/pb/state.js

function makeId(prefix = 'id_') {
    return prefix + Math.random().toString(36).slice(2, 10);
}

function normalizeBlocks(blocks) {
    if (!Array.isArray(blocks)) return [];
    return blocks.map((b) => {
        const clone = { ...b };
        if (!clone.id) clone.id = makeId('blk_');
        const cols = parseInt(clone.columns, 10);
        clone.columns = !isNaN(cols) && cols >= 1 && cols <= 12 ? cols : 12;
        if (!clone.type) clone.type = 'richtext';
        if (!clone.style || typeof clone.style !== 'object') clone.style = {};
        return clone;
    });
}

function normalizeSections(initial) {
    if (!Array.isArray(initial)) return [];
    return initial.map((sec) => {
        const clone = { ...sec };
        clone.id = typeof clone.id === 'string' ? clone.id : makeId('sec_');
        clone.rowAlign = typeof clone.rowAlign === 'string' ? clone.rowAlign : 'start';
        if (!clone.style || typeof clone.style !== 'object') clone.style = {};
        clone.blocks = normalizeBlocks(clone.blocks);
        return clone;
    });
}

function createDefaultBlock(type = 'richtext') {
    const base = {
        id: makeId('blk_'),
        type,
        columns: 12,
        style: {},
    };

    switch (type) {
        case 'text':
        case 'richtext':
            return {
                ...base,
                type: 'richtext',
                html: '<p>Nuovo testo…</p>',
            };
        case 'image':
            return {
                ...base,
                image: {
                    src: '',
                    full: '',
                    alt: '',
                    caption: '',
                    quality: 'full',
                    options: {
                        heightMode: 'auto',      // auto|fixed|ratio
                        heightPx: 450,
                        aspectRatio: '16 / 9',
                        objectFit: 'cover',      // cover|contain
                        objectPosition: 'center center',
                        widthMode: 'auto',       // auto|px|percent
                        widthPx: 0,
                        widthPercent: 100,
                        align: 'center',         // left|center|right
                    },
                    border: {
                        w: 0,
                        s: 'solid',
                        c: '#000000',
                        r: 0,
                    },
                    fx: {
                        parallax: false,
                        parallaxMode: 'y',
                        parallaxStrength: 20,
                        parallaxPerspective: 800,
                        ripple: false,
                        rippleRadius: 60,
                        rippleDuration: 1200,
                        rippleThrottle: 120,
                    },
                    animation: {
                        name: 'none',
                        duration: 600,
                        delay: 0,
                    },
                },
            };
        case 'component':
            return {
                ...base,
                type: 'component',
                component_id: null,
                component_key: '',
                component_name: '',
                props: {},
            };
        default:
            return base;
    }
}

export function createState(initial) {
    let sections = normalizeSections(initial || []);

    function get() {
        return sections;
    }

    function set(next) {
        sections = normalizeSections(next || []);
    }

    function findSection(sectionId) {
        const idx = sections.findIndex((s) => s.id === sectionId);
        if (idx === -1) return null;
        return { index: idx, section: sections[idx] };
    }

    function addSection() {
        const sec = {
            id: makeId('sec_'),
            rowAlign: 'start',
            style: {},
            blocks: [],
        };
        sections.push(sec);
        return sec.id;
    }

    function removeSection(sectionId) {
        sections = sections.filter((s) => s.id !== sectionId);
    }

    function moveSection(sectionId, delta) {
        const entry = findSection(sectionId);
        if (!entry) return;
        const { index } = entry;
        const next = index + delta;
        if (next < 0 || next >= sections.length) return;
        const tmp = sections[index];
        sections[index] = sections[next];
        sections[next] = tmp;
    }

    function updateSection(sectionId, patch) {
        const entry = findSection(sectionId);
        if (!entry) return;
        const { index, section } = entry;
        sections[index] = { ...section, ...(patch || {}) };
    }

    function addBlock(sectionId, type = 'richtext', initialData = {}) {
        const entry = findSection(sectionId);
        if (!entry) return null;

        const block = {
            ...createDefaultBlock(type),
            ...(initialData || {}),
        };

        if (!block.id) {
            block.id = makeId('blk_');
        }

        const cols = parseInt(block.columns, 10);
        block.columns = !isNaN(cols) && cols >= 1 && cols <= 12 ? cols : 12;

        if (!block.style || typeof block.style !== 'object') {
            block.style = {};
        }

        if (block.type === 'component') {
            if (!block.props || typeof block.props !== 'object' || Array.isArray(block.props)) {
                block.props = {};
            }
            if (!('component_id' in block)) block.component_id = null;
            if (!('component_key' in block)) block.component_key = '';
            if (!('component_name' in block)) block.component_name = '';
        }

        entry.section.blocks.push(block);
        return block.id;
    }

    function updateBlock(sectionId, blockId, patch) {
        const entry = findSection(sectionId);
        if (!entry) return;
        const { section } = entry;
        const idx = section.blocks.findIndex((b) => b.id === blockId);
        if (idx === -1) return;

        const block = section.blocks[idx];
        const next = { ...block };

        Object.keys(patch || {}).forEach((key) => {
            const value = patch[key];
            if (
                value &&
                typeof value === 'object' &&
                !Array.isArray(value)
            ) {
                const prev = next[key];
                if (prev && typeof prev === 'object' && !Array.isArray(prev)) {
                    next[key] = { ...prev, ...value };
                } else {
                    next[key] = { ...value };
                }
            } else {
                next[key] = value;
            }
        });

        if ('columns' in patch) {
            const cols = parseInt(next.columns, 10);
            next.columns = !isNaN(cols) && cols >= 1 && cols <= 12 ? cols : 12;
        }

        section.blocks[idx] = next;
    }

    function removeBlock(sectionId, blockId) {
        const entry = findSection(sectionId);
        if (!entry) return;
        entry.section.blocks = entry.section.blocks.filter((b) => b.id !== blockId);
    }

    function moveBlock(sectionId, blockId, delta) {
        const entry = findSection(sectionId);
        if (!entry) return;
        const { section } = entry;
        const idx = section.blocks.findIndex((b) => b.id === blockId);
        if (idx === -1) return;
        const next = idx + delta;
        if (next < 0 || next >= section.blocks.length) return;
        const tmp = section.blocks[idx];
        section.blocks[idx] = section.blocks[next];
        section.blocks[next] = tmp;
    }

    function duplicateBlock(sectionId, blockId) {
        const entry = findSection(sectionId);
        if (!entry) return;
        const { section } = entry;
        const idx = section.blocks.findIndex((b) => b.id === blockId);
        if (idx === -1) return;
        const orig = section.blocks[idx];
        const copy = JSON.parse(JSON.stringify(orig));
        copy.id = makeId('blk_');
        section.blocks.splice(idx + 1, 0, copy);
        return copy.id;
    }

    return {
        get,
        set,
        addSection,
        removeSection,
        moveSection,
        updateSection,
        addBlock,
        updateBlock,
        removeBlock,
        moveBlock,
        duplicateBlock,
    };
}
