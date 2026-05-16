(function () {
    'use strict';

    function parseImages(value) {
        try {
            const parsed = JSON.parse(value || '[]');
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }

    function normalizeDuration(value) {
        const duration = parseInt(value || '5000', 10);
        return Number.isFinite(duration) ? Math.max(1000, duration) : 5000;
    }

    function normalizeEffectDuration(value) {
        const duration = parseInt(value || '800', 10);
        return Number.isFinite(duration) ? Math.max(150, duration) : 800;
    }

    function normalizeEffect(value) {
        return ['fade', 'zoom', 'slide', 'kenburns'].includes(value) ? value : 'fade';
    }

    function setLayerImage(layer, src, effect, active) {
        layer.style.backgroundImage = 'url(' + src + ')';
        layer.style.opacity = active ? '1' : '0';
        layer.style.transform = active ? 'scale(1)' : 'scale(1.02)';

        if (effect === 'zoom') layer.style.transform = active ? 'scale(1.08)' : 'scale(1)';
        if (effect === 'slide') layer.style.transform = active ? 'translateX(0)' : 'translateX(32px)';
        if (effect === 'kenburns') layer.style.transform = active ? 'scale(1.12) translate3d(-1.5%, -1.5%, 0)' : 'scale(1) translate3d(0, 0, 0)';
    }

    function makeLayer(element, index, effect, effectDuration) {
        const layer = document.createElement('span');
        layer.className = 'r4-bg-slider-layer r4-bg-slider-layer-' + index;
        layer.setAttribute('aria-hidden', 'true');
        layer.style.position = 'absolute';
        layer.style.inset = '0';
        layer.style.zIndex = '0';
        layer.style.pointerEvents = 'none';
        layer.style.backgroundSize = element.style.backgroundSize || 'cover';
        layer.style.backgroundPosition = element.style.backgroundPosition || 'center center';
        layer.style.backgroundRepeat = 'no-repeat';
        layer.style.opacity = '0';
        layer.style.willChange = 'opacity, transform';
        layer.style.transition = effect === 'kenburns'
            ? 'opacity ' + effectDuration + 'ms ease, transform ' + Math.max(effectDuration * 4, 3000) + 'ms ease'
            : 'opacity ' + effectDuration + 'ms ease, transform ' + effectDuration + 'ms ease';
        return layer;
    }

    function protectChildren(element) {
        Array.from(element.children).forEach(function (child) {
            if (child.classList && (child.classList.contains('r4-bg-slider-layer') || child.classList.contains('r4-bg-overlay-layer'))) return;
            const childStyle = window.getComputedStyle(child);
            if (childStyle.position === 'static') child.style.position = 'relative';
            if (!child.style.zIndex) child.style.zIndex = '1';
        });
    }

    function initBackgroundSliders() {
        document.querySelectorAll('[data-r4-bg-slider="1"]').forEach(function (element) {
            if (element.dataset.r4BgSliderReady === '1') return;

            const images = parseImages(element.getAttribute('data-r4-bg-slider-images'));
            if (images.length < 2) return;

            element.dataset.r4BgSliderReady = '1';

            let index = 0;
            let activeLayer = 0;
            const duration = normalizeDuration(element.getAttribute('data-r4-bg-slider-duration'));
            const effectDuration = normalizeEffectDuration(element.getAttribute('data-r4-bg-slider-effect-duration'));
            const effect = normalizeEffect(element.getAttribute('data-r4-bg-slider-effect') || 'fade');
            const computed = window.getComputedStyle(element);

            if (computed.position === 'static') element.style.position = 'relative';
            element.style.overflow = element.style.overflow || 'hidden';
            element.style.backgroundImage = 'none';
            element.setAttribute('data-r4-bg-slider-effect-active', effect);
            element.setAttribute('data-r4-bg-slider-effect-duration-active', String(effectDuration));

            const layerA = makeLayer(element, 0, effect, effectDuration);
            const layerB = makeLayer(element, 1, effect, effectDuration);
            element.insertBefore(layerB, element.firstChild);
            element.insertBefore(layerA, element.firstChild);

            setLayerImage(layerA, images[0], effect, true);
            setLayerImage(layerB, images[1], effect, false);
            layerA.style.opacity = '1';
            layerB.style.opacity = '0';

            protectChildren(element);

            window.setInterval(function () {
                index = (index + 1) % images.length;
                const nextLayer = activeLayer === 0 ? layerB : layerA;
                const currentLayer = activeLayer === 0 ? layerA : layerB;

                setLayerImage(nextLayer, images[index], effect, true);
                nextLayer.style.opacity = '1';
                currentLayer.style.opacity = '0';

                if (effect === 'slide') currentLayer.style.transform = 'translateX(-32px)';
                else if (effect === 'fade') currentLayer.style.transform = 'scale(1.02)';
                else if (effect === 'zoom') currentLayer.style.transform = 'scale(1)';
                else if (effect === 'kenburns') currentLayer.style.transform = 'scale(1.03) translate3d(1%, 1%, 0)';

                activeLayer = activeLayer === 0 ? 1 : 0;
            }, duration);
        });
    }

    function initBackgroundOverlays() {
        document.querySelectorAll('[data-r4-bg-overlay="1"]').forEach(function (element) {
            if (element.dataset.r4BgOverlayReady === '1') return;
            element.dataset.r4BgOverlayReady = '1';

            const computed = window.getComputedStyle(element);
            if (computed.position === 'static') element.style.position = 'relative';
            element.style.overflow = element.style.overflow || 'hidden';

            const overlay = document.createElement('span');
            overlay.className = 'r4-bg-overlay-layer';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.cssText = 'position:absolute;inset:0;pointer-events:none;z-index:0;';
            overlay.style.background = element.getAttribute('data-r4-bg-overlay-color') || '#000000';
            overlay.style.opacity = element.getAttribute('data-r4-bg-overlay-opacity') || '0.35';

            protectChildren(element);
            element.insertBefore(overlay, element.firstChild);
        });
    }

    function initLegacyPhotoSliders() {
        document.querySelectorAll('.page-visual-content .r4v4-photo-slider').forEach(function (slider) {
            const track = slider.querySelector('[style*="scroll-snap-type"]');
            if (!track || track.dataset.r4v4Ready === '1') return;
            track.dataset.r4v4Ready = '1';

            const slides = Array.from(track.children);
            if (slides.length <= 1) return;

            let index = 0;
            let timer = null;
            const interval = 5000;

            const controls = document.createElement('div');
            controls.className = 'r4v4-photo-slider-controls';
            controls.style.cssText = 'display:flex;gap:10px;justify-content:center;align-items:center;margin-top:18px;';

            const prev = document.createElement('button');
            prev.type = 'button';
            prev.textContent = '‹';
            prev.className = 'r4v4-slider-arrow-inline';
            prev.style.cssText = 'width:38px;height:38px;border-radius:999px;border:1px solid #dbe4ee;background:#fff;font-size:24px;line-height:1;cursor:pointer;';

            const dots = document.createElement('div');
            dots.className = 'r4v4-slider-dots-inline';
            dots.style.cssText = 'display:flex;gap:8px;align-items:center;';

            const dotButtons = slides.map(function (_, i) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Vai alla slide ' + (i + 1));
                dot.style.cssText = 'width:10px;height:10px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:width .2s ease, background .2s ease;';
                dot.addEventListener('click', function () { go(i); restart(); });
                dots.appendChild(dot);
                return dot;
            });

            const next = document.createElement('button');
            next.type = 'button';
            next.textContent = '›';
            next.className = 'r4v4-slider-arrow-inline';
            next.style.cssText = 'width:38px;height:38px;border-radius:999px;border:1px solid #dbe4ee;background:#fff;font-size:24px;line-height:1;cursor:pointer;';

            controls.appendChild(prev);
            controls.appendChild(dots);
            controls.appendChild(next);
            track.parentElement.appendChild(controls);

            function updateDots() {
                dotButtons.forEach(function (dot, i) {
                    dot.style.background = i === index ? '#0d6efd' : '#cbd5e1';
                    dot.style.width = i === index ? '24px' : '10px';
                });
            }

            function go(nextIndex) {
                index = (nextIndex + slides.length) % slides.length;
                track.scrollTo({ left: slides[index].offsetLeft, behavior: 'smooth' });
                updateDots();
            }

            function start() { stop(); timer = window.setInterval(function () { go(index + 1); }, interval); }
            function stop() { if (timer) window.clearInterval(timer); timer = null; }
            function restart() { stop(); start(); }

            prev.addEventListener('click', function () { go(index - 1); restart(); });
            next.addEventListener('click', function () { go(index + 1); restart(); });
            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);

            updateDots();
            start();
        });
    }

    function ensureControls(slider, slides) {
        let prev = slider.querySelector('.r4v4-slider-arrow--prev');
        let next = slider.querySelector('.r4v4-slider-arrow--next');
        let dotsWrap = slider.querySelector('.r4v4-slider-dots');

        if (!prev) {
            prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'r4v4-slider-arrow r4v4-slider-arrow--prev';
            prev.setAttribute('aria-label', 'Slide precedente');
            prev.textContent = '‹';
            slider.appendChild(prev);
        }

        if (!next) {
            next = document.createElement('button');
            next.type = 'button';
            next.className = 'r4v4-slider-arrow r4v4-slider-arrow--next';
            next.setAttribute('aria-label', 'Slide successiva');
            next.textContent = '›';
            slider.appendChild(next);
        }

        if (!dotsWrap) {
            dotsWrap = document.createElement('div');
            dotsWrap.className = 'r4v4-slider-dots';
            slider.appendChild(dotsWrap);
        }

        if (!dotsWrap.querySelector('.r4v4-slider-dot')) {
            slides.forEach(function (_, i) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'r4v4-slider-dot';
                dot.setAttribute('aria-label', 'Vai alla slide ' + (i + 1));
                dotsWrap.appendChild(dot);
            });
        }

        return { prev: prev, next: next, dots: Array.from(dotsWrap.querySelectorAll('.r4v4-slider-dot')) };
    }

    function initAdvancedSliders() {
        document.querySelectorAll('[data-r4v4-slider], .r4v4-advanced-slider, .r4v4-fullscreen-slider').forEach(function (slider) {
            if (slider.dataset.r4v4Ready === '1') return;

            const slides = Array.from(slider.querySelectorAll('[data-r4v4-slide], .r4v4-advanced-slider__slide, .r4v4-fullscreen-slider__slide'));
            if (!slides.length) return;

            slider.dataset.r4v4Ready = '1';
            if (!slider.hasAttribute('data-r4v4-slider')) slider.setAttribute('data-r4v4-slider', '');

            const controls = ensureControls(slider, slides);
            const prev = controls.prev;
            const next = controls.next;
            const dots = controls.dots;
            const autoplay = slider.dataset.r4v4Autoplay !== 'false';
            const interval = parseInt(slider.dataset.r4v4Interval || '5000', 10);

            let index = slides.findIndex(function (slide) { return slide.classList.contains('is-active'); });
            if (index < 0) index = 0;
            let timer = null;

            function show(nextIndex) {
                index = (nextIndex + slides.length) % slides.length;
                slides.forEach(function (slide, i) {
                    slide.classList.toggle('is-active', i === index);
                    slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
                });
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === index);
                    dot.setAttribute('aria-current', i === index ? 'true' : 'false');
                });
            }

            function start() {
                if (!autoplay || slides.length <= 1) return;
                stop();
                timer = window.setInterval(function () { show(index + 1); }, Math.max(2500, interval));
                slider.dataset.r4v4AutoplayRunning = '1';
            }

            function stop() {
                if (timer) window.clearInterval(timer);
                timer = null;
                slider.dataset.r4v4AutoplayRunning = '0';
            }

            function restart() { stop(); start(); }

            prev.addEventListener('click', function (event) { event.preventDefault(); show(index - 1); restart(); });
            next.addEventListener('click', function (event) { event.preventDefault(); show(index + 1); restart(); });
            dots.forEach(function (dot, i) {
                dot.addEventListener('click', function (event) { event.preventDefault(); show(i); restart(); });
            });
            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);

            show(index);
            start();
        });
    }

    function normalizeLegacyAnimationAttrs(el) {
        if (!el.hasAttribute('data-r4-animation') && el.hasAttribute('data-anim')) {
            el.setAttribute('data-r4-animation', el.getAttribute('data-anim') || '');
        }
        if (!el.hasAttribute('data-r4-animation-duration') && el.hasAttribute('data-anim-duration')) {
            el.setAttribute('data-r4-animation-duration', el.getAttribute('data-anim-duration') || '700');
        }
        if (!el.hasAttribute('data-r4-animation-delay') && el.hasAttribute('data-anim-delay')) {
            el.setAttribute('data-r4-animation-delay', el.getAttribute('data-anim-delay') || '0');
        }
        if (!el.hasAttribute('data-r4-animation-distance') && el.hasAttribute('data-anim-distance')) {
            el.setAttribute('data-r4-animation-distance', el.getAttribute('data-anim-distance') || '40');
        }
    }

    function initAnimations() {
        const animated = Array.from(document.querySelectorAll('.page-visual-content [data-r4-animation], .page-visual-content [data-anim]'));
        if (!animated.length) return;

        animated.forEach(function (el) {
            normalizeLegacyAnimationAttrs(el);

            const duration = parseInt(el.getAttribute('data-r4-animation-duration') || '700', 10);
            const delay = parseInt(el.getAttribute('data-r4-animation-delay') || '0', 10);
            const distance = parseInt(el.getAttribute('data-r4-animation-distance') || '40', 10);

            el.style.setProperty('--r4-animation-duration', Math.max(100, Number.isFinite(duration) ? duration : 700) + 'ms');
            el.style.setProperty('--r4-animation-delay', Math.max(0, Number.isFinite(delay) ? delay : 0) + 'ms');
            el.style.setProperty('--r4-animation-distance', Math.max(0, Number.isFinite(distance) ? distance : 40) + 'px');
        });

        if (!('IntersectionObserver' in window)) {
            animated.forEach(function (el) {
                el.classList.add('r4-animation-visible', 'is-animated');
            });
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('r4-animation-visible', 'is-animated');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

        animated.forEach(function (el) {
            if (el.dataset.r4AnimationObserved === '1') return;
            el.dataset.r4AnimationObserved = '1';
            observer.observe(el);
        });
    }

    function boot() {
        initBackgroundSliders();
        initBackgroundOverlays();
        initLegacyPhotoSliders();
        initAdvancedSliders();
        initAnimations();
    }

    window.R4V4Runtime = window.R4V4Runtime || {};
    window.R4V4Runtime.boot = boot;
    window.R4V4Runtime.bootBackgrounds = function () {
        initBackgroundSliders();
        initBackgroundOverlays();
    };
    window.R4V4Runtime.bootAnimations = initAnimations;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

    window.setTimeout(boot, 250);
    window.setTimeout(boot, 1000);
})();
