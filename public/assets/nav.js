// R4 Nav — aggiunge/rimuove .is-scrolled in base allo scroll
(function(){
    const navs = document.querySelectorAll(
        '.r4-nav[data-scroll-toggle="1"], .navbar[data-r4-nav="1"][data-scroll-toggle="1"]'
    );
    if(!navs.length) return;

    function apply(){
        navs.forEach(nav => {
            const th = parseInt(nav.getAttribute('data-threshold')||'10',10);
            if(window.scrollY > th) nav.classList.add('is-scrolled');
            else nav.classList.remove('is-scrolled');
        });
    }

    apply();
    window.addEventListener('scroll', apply, {passive:true});
})();

// R4 Editor V4 public runtime loader
// Mantiene attivi slider, background slider, overlay, caroselli e componenti V4.
(function () {
    'use strict';

    function hasV4NonAnimationRuntimeNeeds() {
        return !!document.querySelector(
            '[data-r4-bg-slider="1"], [data-r4-bg-overlay="1"], [data-r4v4-slider], .r4v4-advanced-slider, .r4v4-fullscreen-slider, .r4v4-photo-slider, .r4v4-logo-carousel, .r4-image-slider, .r4-gallery'
        );
    }

    function ensureRuntimeCss() {
        if (document.getElementById('r4v4-public-runtime-css')) return;
        const link = document.createElement('link');
        link.id = 'r4v4-public-runtime-css';
        link.rel = 'stylesheet';
        link.href = '/assets/page-builder/v4/runtime.css?v=' + Date.now();
        document.head.appendChild(link);
    }

    function bootRuntime() {
        if (window.R4V4Runtime && typeof window.R4V4Runtime.boot === 'function') {
            window.R4V4Runtime.boot();
            return true;
        }
        return false;
    }

    function loadV4Runtime() {
        if (!hasV4NonAnimationRuntimeNeeds()) return;

        ensureRuntimeCss();

        if (bootRuntime()) return;

        if (!document.getElementById('r4v4-public-runtime-js')) {
            const script = document.createElement('script');
            script.id = 'r4v4-public-runtime-js';
            script.src = '/assets/page-builder/v4/runtime.js?v=' + Date.now();
            script.defer = true;
            script.onload = function () {
                bootRuntime();
                window.setTimeout(bootRuntime, 150);
            };
            document.body.appendChild(script);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadV4Runtime);
    } else {
        loadV4Runtime();
    }

    window.setTimeout(loadV4Runtime, 300);
    window.setTimeout(loadV4Runtime, 1200);
})();

// R4 Editor V4 public animations runtime
// Runtime autonomo: non dipende da show.blade legacy, runtime.css o runtime.js.
(function () {
    'use strict';

    const STYLE_ID = 'r4v4-public-animation-runtime-inline';

    function injectAnimationCss() {
        if (document.getElementById(STYLE_ID)) return;

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
.page-visual-content [data-r4-animation] {
    --r4-animation-duration: 700ms;
    --r4-animation-delay: 0ms;
    --r4-animation-distance: 40px;
    opacity: 0 !important;
    transition-property: opacity, transform, clip-path !important;
    transition-timing-function: cubic-bezier(.2, .75, .2, 1) !important;
    transition-duration: var(--r4-animation-duration) !important;
    transition-delay: var(--r4-animation-delay) !important;
    will-change: opacity, transform, clip-path;
}
.page-visual-content [data-r4-animation].r4-animation-visible,
.page-visual-content [data-r4-animation].is-animated {
    opacity: 1 !important;
    transform: none !important;
    clip-path: inset(0 0 0 0) !important;
}
.page-visual-content [data-r4-animation][data-anim],
.page-visual-content [data-r4-animation][data-anim].is-animated {
    animation: none !important;
    animation-name: none !important;
    animation-play-state: initial !important;
}
.page-visual-content [data-r4-animation="fade-in"] { transform: translate3d(0,0,0) !important; }
.page-visual-content [data-r4-animation="fade-up"] { transform: translate3d(0,var(--r4-animation-distance),0) !important; }
.page-visual-content [data-r4-animation="fade-down"] { transform: translate3d(0,calc(var(--r4-animation-distance) * -1),0) !important; }
.page-visual-content [data-r4-animation="fade-left"] { transform: translate3d(var(--r4-animation-distance),0,0) !important; }
.page-visual-content [data-r4-animation="fade-right"] { transform: translate3d(calc(var(--r4-animation-distance) * -1),0,0) !important; }
.page-visual-content [data-r4-animation="slide-up"] { transform: translate3d(0,var(--r4-animation-distance),0) !important; opacity: 1 !important; }
.page-visual-content [data-r4-animation="slide-down"] { transform: translate3d(0,calc(var(--r4-animation-distance) * -1),0) !important; opacity: 1 !important; }
.page-visual-content [data-r4-animation="slide-left"] { transform: translate3d(var(--r4-animation-distance),0,0) !important; opacity: 1 !important; }
.page-visual-content [data-r4-animation="slide-right"] { transform: translate3d(calc(var(--r4-animation-distance) * -1),0,0) !important; opacity: 1 !important; }
.page-visual-content [data-r4-animation="swipe-up"] { clip-path: inset(100% 0 0 0) !important; transform: translate3d(0,calc(var(--r4-animation-distance) / 2),0) !important; }
.page-visual-content [data-r4-animation="swipe-down"] { clip-path: inset(0 0 100% 0) !important; transform: translate3d(0,calc(var(--r4-animation-distance) / -2),0) !important; }
.page-visual-content [data-r4-animation="swipe-left"] { clip-path: inset(0 0 0 100%) !important; transform: translate3d(calc(var(--r4-animation-distance) / 2),0,0) !important; }
.page-visual-content [data-r4-animation="swipe-right"] { clip-path: inset(0 100% 0 0) !important; transform: translate3d(calc(var(--r4-animation-distance) / -2),0,0) !important; }
.page-visual-content [data-r4-animation="zoom-in"] { transform: scale(.92) !important; }
.page-visual-content [data-r4-animation="zoom-out"] { transform: scale(1.08) !important; }
.page-visual-content [data-r4-animation="flip-up"] { transform: perspective(900px) rotateX(12deg) translate3d(0,var(--r4-animation-distance),0) !important; transform-origin: center bottom !important; }
.page-visual-content [data-r4-animation="fade-out"] { opacity: 1 !important; transform: translate3d(0,0,0) !important; }
.page-visual-content [data-r4-animation="fade-out"].r4-animation-visible,
.page-visual-content [data-r4-animation="fade-out"].is-animated { opacity: 0 !important; }
@media (prefers-reduced-motion: reduce) {
    .page-visual-content [data-r4-animation] {
        opacity: 1 !important;
        transform: none !important;
        clip-path: inset(0 0 0 0) !important;
        transition: none !important;
    }
}`;
        document.head.appendChild(style);
    }

    function toNumber(value, fallback, min) {
        const parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed)) return fallback;
        return Math.max(min, parsed);
    }

    function prepareElement(el) {
        const duration = toNumber(el.getAttribute('data-r4-animation-duration'), 700, 100);
        const delay = toNumber(el.getAttribute('data-r4-animation-delay'), 0, 0);
        const distance = toNumber(el.getAttribute('data-r4-animation-distance'), 40, 0);

        // Neutralizza il vecchio runtime legacy di show.blade.php.
        el.removeAttribute('data-anim');
        el.removeAttribute('data-anim-duration');
        el.removeAttribute('data-anim-delay');
        el.removeAttribute('data-anim-distance');

        el.style.setProperty('--r4-animation-duration', duration + 'ms');
        el.style.setProperty('--r4-animation-delay', delay + 'ms');
        el.style.setProperty('--r4-animation-distance', distance + 'px');
    }

    function isInViewport(el) {
        const rect = el.getBoundingClientRect();
        const vh = window.innerHeight || document.documentElement.clientHeight;
        const vw = window.innerWidth || document.documentElement.clientWidth;
        return rect.bottom >= 0 && rect.right >= 0 && rect.top <= vh && rect.left <= vw;
    }

    function reveal(el) {
        if (!el) return;
        el.classList.add('r4-animation-visible', 'is-animated');
    }

    function bootAnimations() {
        const root = document.getElementById('pageVisualContent') || document.querySelector('.page-visual-content');
        if (!root) return;

        const animated = Array.from(root.querySelectorAll('[data-r4-animation]'));
        if (!animated.length) return;

        injectAnimationCss();
        animated.forEach(prepareElement);

        if (!('IntersectionObserver' in window)) {
            animated.forEach(reveal);
            return;
        }

        const observer = new IntersectionObserver(function (entries, io) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                reveal(entry.target);
                io.unobserve(entry.target);
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -2% 0px'
        });

        animated.forEach(function (el) {
            if (el.dataset.r4V4PublicAnimationReady === '1') {
                if (isInViewport(el)) reveal(el);
                return;
            }

            el.dataset.r4V4PublicAnimationReady = '1';
            observer.observe(el);

            window.setTimeout(function () {
                if (isInViewport(el)) reveal(el);
            }, 80);
        });
    }

    function boot() {
        bootAnimations();
        window.setTimeout(bootAnimations, 250);
        window.setTimeout(bootAnimations, 900);
        window.setTimeout(bootAnimations, 1600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.R4V4PublicAnimations = {
        boot: bootAnimations,
        revealAll: function () {
            document.querySelectorAll('.page-visual-content [data-r4-animation]').forEach(function (el) {
                prepareElement(el);
                reveal(el);
            });
        }
    };
})();

// R4 Editor V5 public background slider runtime loader
// Carica il runtime del background slider solo quando il markup V5 è presente nel frontend pubblico.
(function () {
    'use strict';

    const RUNTIME_ID = 'r4v5-background-slider-public-global-runtime';
    const RUNTIME_SRC = '/assets/admin/visual-editor-v5/runtime/background-slider-runtime.js?v=20260509-v5-bg-slider-global-loader';

    function hasV5BackgroundSlider() {
        return !!document.querySelector('[data-r4v5-bg-slider="1"], [data-r4v5-bg-slider]');
    }

    function bootExistingRuntime() {
        if (window.R4V5BackgroundSlider && typeof window.R4V5BackgroundSlider.init === 'function') {
            window.R4V5BackgroundSlider.init();
            return true;
        }
        return false;
    }

    function loadRuntime() {
        if (!hasV5BackgroundSlider()) return;
        if (bootExistingRuntime()) return;
        if (document.getElementById(RUNTIME_ID)) return;

        const script = document.createElement('script');
        script.id = RUNTIME_ID;
        script.src = RUNTIME_SRC;
        script.defer = true;
        script.onload = function () {
            bootExistingRuntime();
            window.setTimeout(bootExistingRuntime, 150);
        };
        document.body.appendChild(script);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadRuntime);
    } else {
        loadRuntime();
    }

    window.setTimeout(loadRuntime, 300);
    window.setTimeout(loadRuntime, 1200);
})();
