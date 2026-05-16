function mountOne(root) {
    if (!(root instanceof HTMLElement) || INST.has(root)) return;

    const track = root.querySelector('.r4lc-track');
    if (!track) return;

    // ⬇️ Base styles in-line (se il CSS non carica NON andiamo in verticale)
    const applyTrackBaseStyles = () => {
        const gapVar = getComputedStyle(root).getPropertyValue('--r4lc-gap').trim();
        track.style.display = 'flex';
        track.style.alignItems = 'center';
        track.style.flexWrap = 'nowrap';
        track.style.gap = gapVar || '24px';
        track.style.width = 'max-content';
        track.style.willChange = 'transform';
    };
    applyTrackBaseStyles();

    const readNumVar = (name, fb) => {
        const v = parseFloat(getComputedStyle(root).getPropertyValue(name));
        return Number.isFinite(v) ? v : fb;
    };

    // Items originali (prima dei cloni)
    const baseItems = Array.from(track.querySelectorAll('.r4lc-item'));
    const count = baseItems.length;
    if (!count) return;

    // Clona una volta per marquee seamless
    if (track.dataset.cloned !== '1') {
        const clones = baseItems.map((n) => {
            const c = n.cloneNode(true);
            c.setAttribute('aria-hidden', 'true');
            c.dataset.clone = '1';
            return c;
        });
        clones.forEach((c) => track.appendChild(c));
        track.dataset.cloned = '1';
    }

    function layout() {
        // rilettura dinamica (risponde a media queries)
        const gap = readNumVar('--r4lc-gap', 24);
        const visible = Math.max(1, readNumVar('--r4lc-visible', 5));
        const itemWVar = readNumVar('--r4lc-item-w', NaN); // può essere 'auto' => NaN
        const speed = Math.max(10, Number(root.getAttribute('data-speed') || 35)); // px/sec

        const viewport = root.querySelector('.r4lc-viewport') || root;
        const rootW = viewport.clientWidth || root.clientWidth || 0;

        const perW = Number.isFinite(itemWVar)
            ? itemWVar
            : Math.max(1, Math.floor((rootW - (visible - 1) * gap) / visible));

        // larghezza rigida px su tutti gli item (originali + cloni)
        track.querySelectorAll('.r4lc-item').forEach((el) => {
            el.style.flex = `0 0 ${perW}px`;
            el.style.minWidth = `${perW}px`;
            el.style.maxWidth = `${perW}px`;
            el.style.display = 'flex';
            el.style.alignItems = 'center';
            el.style.justifyContent = 'center';
            el.style.height = `var(--r4lc-item-h, 72px)`; // fallback
        });

        root.style.setProperty('--r4lc-item-w-px', perW + 'px');

        const baseWidth = count * perW + Math.max(0, count - 1) * gap;
        const dur = Math.max(1, baseWidth / speed);
        track.style.setProperty('--r4lc-duration', `${dur}s`);
    }

    // Primo layout
    layout();

    // Ricalcolo su resize
    let rafId = null;
    const onResize = () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            applyTrackBaseStyles(); // nel dubbio, ribadisco i base styles
            layout();
            rafId = null;
        });
    };
    window.addEventListener('resize', onResize, { passive: true });

    // Pausa quando fuori viewport
    const io = new IntersectionObserver(
        ([en]) => { root.setAttribute('data-out', en.isIntersecting ? '0' : '1'); },
        { threshold: 0 }
    );
    io.observe(root);

    // Ricalcolo dopo il load immagini
    const imgs = track.querySelectorAll('img');
    let pending = 0;
    imgs.forEach((img) => {
        if (!img.complete) {
            pending++;
            img.addEventListener('load', () => { if (--pending === 0) layout(); }, { once: true });
            img.addEventListener('error', () => { if (--pending === 0) layout(); }, { once: true });
        }
    });

    INST.set(root, { io, onResize, layout });
}
