(function () {
  'use strict';

  function injectStyles(doc) {
    doc = doc || document;
    if (doc.getElementById('r4v5-animations-runtime-style')) return;

    var style = doc.createElement('style');
    style.id = 'r4v5-animations-runtime-style';
    style.textContent = '' +
      '.r4-anim{opacity:1;animation-duration:var(--r4-anim-duration,800ms);animation-delay:var(--r4-anim-delay,0ms);animation-timing-function:var(--r4-anim-easing,ease);animation-fill-mode:both;animation-play-state:running;will-change:transform,opacity,filter;}' +
      '.r4-anim:not(.is-r4-prepared){opacity:1!important;transform:none!important;filter:none!important;}' +
      '.r4-anim.is-r4-prepared:not(.is-animated){opacity:0;animation-play-state:paused;}' +
      '.r4-anim.is-animated{opacity:1;animation-play-state:running;}' +
      '.r4-anim-fade-in.is-animated{animation-name:r4FadeIn;}' +
      '.r4-anim-fade-out.is-animated{animation-name:r4FadeOut;}' +
      '.r4-anim-fade-up.is-animated{animation-name:r4FadeUp;}' +
      '.r4-anim-fade-down.is-animated{animation-name:r4FadeDown;}' +
      '.r4-anim-fade-left.is-animated{animation-name:r4FadeLeft;}' +
      '.r4-anim-fade-right.is-animated{animation-name:r4FadeRight;}' +
      '.r4-anim-zoom-in.is-animated{animation-name:r4ZoomIn;}' +
      '.r4-anim-zoom-out.is-animated{animation-name:r4ZoomOut;}' +
      '.r4-anim-flip-up.is-animated{animation-name:r4FlipUp;transform-origin:center bottom;}' +
      '.r4-anim-blur-in.is-animated{animation-name:r4BlurIn;}' +
      '.r4-anim-slide-up.is-animated{animation-name:r4SlideUp;}' +
      '.r4-anim-slide-left.is-animated{animation-name:r4SlideLeft;}' +
      '.r4-anim-slide-right.is-animated{animation-name:r4SlideRight;}' +
      '@keyframes r4FadeIn{from{opacity:0;}to{opacity:1;}}' +
      '@keyframes r4FadeOut{from{opacity:1;}to{opacity:.2;}}' +
      '@keyframes r4FadeUp{from{opacity:0;transform:translateY(28px);}to{opacity:1;transform:translateY(0);}}' +
      '@keyframes r4FadeDown{from{opacity:0;transform:translateY(-28px);}to{opacity:1;transform:translateY(0);}}' +
      '@keyframes r4FadeLeft{from{opacity:0;transform:translateX(-28px);}to{opacity:1;transform:translateX(0);}}' +
      '@keyframes r4FadeRight{from{opacity:0;transform:translateX(28px);}to{opacity:1;transform:translateX(0);}}' +
      '@keyframes r4ZoomIn{from{opacity:0;transform:scale(.92);}to{opacity:1;transform:scale(1);}}' +
      '@keyframes r4ZoomOut{from{opacity:0;transform:scale(1.08);}to{opacity:1;transform:scale(1);}}' +
      '@keyframes r4FlipUp{from{opacity:0;transform:perspective(800px) rotateX(20deg) translateY(20px);}to{opacity:1;transform:perspective(800px) rotateX(0) translateY(0);}}' +
      '@keyframes r4BlurIn{from{opacity:0;filter:blur(8px);}to{opacity:1;filter:blur(0);}}' +
      '@keyframes r4SlideUp{from{opacity:0;transform:translateY(40px);}to{opacity:1;transform:translateY(0);}}' +
      '@keyframes r4SlideLeft{from{opacity:0;transform:translateX(-40px);}to{opacity:1;transform:translateX(0);}}' +
      '@keyframes r4SlideRight{from{opacity:0;transform:translateX(40px);}to{opacity:1;transform:translateX(0);}}' +
      '[data-r4-bg-animation]{position:relative;overflow:hidden;}' +
      '[data-r4-bg-animation] > :not([data-r4-bg-animation-layer]):not([data-r4v5-bg-slider-layer]){position:relative;z-index:2;}' +
      '[data-r4-bg-animation-layer]{position:absolute;inset:0;z-index:0;pointer-events:none;border-radius:inherit;background-repeat:no-repeat;background-size:inherit;background-position:inherit;background-attachment:inherit;animation-delay:var(--r4-bg-anim-delay,0ms);animation-duration:var(--r4-bg-anim-duration,7000ms);animation-timing-function:var(--r4-bg-anim-easing,ease-in-out);animation-iteration-count:var(--r4-bg-anim-iteration,infinite);animation-fill-mode:both;will-change:background-position,background-size,opacity,transform;}' +
      '[data-r4v5-bg-slider-layer].r4-bg-layer-anim{animation-delay:var(--r4-bg-anim-delay,0ms);animation-duration:var(--r4-bg-anim-duration,7000ms);animation-timing-function:var(--r4-bg-anim-easing,ease-in-out);animation-iteration-count:var(--r4-bg-anim-iteration,infinite);animation-fill-mode:both;will-change:opacity,transform;}' +
      '.r4-bg-anim-fade{animation-name:r4BgFade;}' +
      '.r4-bg-anim-zoom-soft,.r4-bg-anim-kenburns{animation-name:r4BgKenBurns;}' +
      '.r4-bg-anim-zoom-in{animation-name:r4BgZoomIn;}' +
      '.r4-bg-anim-zoom-out{animation-name:r4BgZoomOut;}' +
      '.r4-bg-anim-pan-left{animation-name:r4BgPanLeft;}' +
      '.r4-bg-anim-pan-right{animation-name:r4BgPanRight;}' +
      '.r4-bg-anim-pan-up{animation-name:r4BgPanUp;}' +
      '.r4-bg-anim-pan-down{animation-name:r4BgPanDown;}' +
      '.r4-bg-anim-pulse-soft{animation-name:r4BgPulseSoft;}' +
      '@keyframes r4BgFade{0%,100%{opacity:1;}50%{opacity:.82;}}' +
      '@keyframes r4BgKenBurns{0%{transform:scale(1);background-position:center center;}50%{transform:scale(1.06);background-position:center top;}100%{transform:scale(1.1);background-position:center center;}}' +
      '@keyframes r4BgZoomIn{from{transform:scale(1);}to{transform:scale(1.1);}}' +
      '@keyframes r4BgZoomOut{from{transform:scale(1.1);}to{transform:scale(1);}}' +
      '@keyframes r4BgPanLeft{from{background-position:center center;}to{background-position:left center;}}' +
      '@keyframes r4BgPanRight{from{background-position:center center;}to{background-position:right center;}}' +
      '@keyframes r4BgPanUp{from{background-position:center center;}to{background-position:center top;}}' +
      '@keyframes r4BgPanDown{from{background-position:center center;}to{background-position:center bottom;}}' +
      '@keyframes r4BgPulseSoft{0%,100%{transform:scale(1);}50%{transform:scale(1.025);}}' +
      '@media (prefers-reduced-motion:reduce){.r4-anim,[data-r4-bg-animation-layer],[data-r4v5-bg-slider-layer].r4-bg-layer-anim{animation:none!important;opacity:1!important;transform:none!important;filter:none!important;}}';

    doc.head.appendChild(style);
  }

  function cleanAnimationClasses(el, prefix) {
    Array.prototype.slice.call(el.classList || []).forEach(function (cls) {
      if (cls.indexOf(prefix) === 0) el.classList.remove(cls);
    });
  }

  function applyAnimationVars(el) {
    var duration = parseInt(el.getAttribute('data-r4-animation-duration') || '800', 10);
    var delay = parseInt(el.getAttribute('data-r4-animation-delay') || '0', 10);
    var easing = el.getAttribute('data-r4-animation-easing') || 'ease';
    el.style.setProperty('--r4-anim-duration', Math.max(50, duration) + 'ms');
    el.style.setProperty('--r4-anim-delay', Math.max(0, delay) + 'ms');
    el.style.setProperty('--r4-anim-easing', easing);
  }

  function applyBgVars(target, source) {
    var duration = parseInt(source.getAttribute('data-r4-bg-animation-duration') || '7000', 10);
    var delay = parseInt(source.getAttribute('data-r4-bg-animation-delay') || '0', 10);
    var easing = source.getAttribute('data-r4-bg-animation-easing') || 'ease-in-out';
    var loop = source.getAttribute('data-r4-bg-animation-loop') || 'true';
    target.style.setProperty('--r4-bg-anim-duration', Math.max(500, duration) + 'ms');
    target.style.setProperty('--r4-bg-anim-delay', Math.max(0, delay) + 'ms');
    target.style.setProperty('--r4-bg-anim-easing', easing);
    target.style.setProperty('--r4-bg-anim-iteration', loop === 'false' ? '1' : 'infinite');
  }

  function applyBgClass(target, type) {
    cleanAnimationClasses(target, 'r4-bg-anim-');
    if (type) target.classList.add('r4-bg-anim-' + type);
  }

  function hasUsableBackground(el) {
    var style = window.getComputedStyle(el);
    return style.backgroundImage && style.backgroundImage !== 'none' || style.backgroundColor && style.backgroundColor !== 'rgba(0, 0, 0, 0)' && style.backgroundColor !== 'transparent';
  }

  function copyBackgroundToLayer(el, layer) {
    var style = window.getComputedStyle(el);
    layer.style.background = style.background;
    layer.style.backgroundImage = style.backgroundImage;
    layer.style.backgroundColor = style.backgroundColor;
    layer.style.backgroundSize = style.backgroundSize;
    layer.style.backgroundPosition = style.backgroundPosition;
    layer.style.backgroundRepeat = style.backgroundRepeat;
    layer.style.backgroundAttachment = style.backgroundAttachment;
    layer.style.opacity = '1';
    el.style.background = 'transparent';
    el.style.backgroundImage = 'none';
  }

  function ensureBackgroundLayer(el) {
    var layer = el.querySelector(':scope > [data-r4-bg-animation-layer]');
    if (layer) return layer;
    layer = document.createElement('div');
    layer.setAttribute('data-r4-bg-animation-layer', '1');
    copyBackgroundToLayer(el, layer);
    el.insertBefore(layer, el.firstChild);
    return layer;
  }

  function applyBgAnimation(el) {
    var type = el.getAttribute('data-r4-bg-animation') || '';
    var sliderLayer = el.querySelector(':scope > [data-r4v5-bg-slider-layer]');

    if (sliderLayer) {
      sliderLayer.classList.add('r4-bg-layer-anim');
      applyBgVars(sliderLayer, el);
      applyBgClass(sliderLayer, type);
      return;
    }

    if (!type) return;
    if (!hasUsableBackground(el) && !el.querySelector(':scope > [data-r4-bg-animation-layer]')) return;
    var layer = ensureBackgroundLayer(el);
    applyBgVars(layer, el);
    applyBgClass(layer, type);
  }

  function isAlreadyVisibleInViewport(el) {
    try {
      var rect = el.getBoundingClientRect();
      var height = window.innerHeight || document.documentElement.clientHeight;
      var width = window.innerWidth || document.documentElement.clientWidth;
      return rect.bottom >= 0 && rect.right >= 0 && rect.top <= height && rect.left <= width;
    } catch (e) { return true; }
  }

  function startElementAnimation(el) {
    if (!el || el.classList.contains('is-animated')) return;
    el.classList.add('is-animated');
  }

  function scheduleElementAnimation(el) {
    if (!el || el.__r4AnimationScheduled) return;
    el.__r4AnimationScheduled = true;

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        window.setTimeout(function () {
          startElementAnimation(el);
        }, 35);
      });
    });
  }

  function initElements(root) {
    var scope = root || document;

    Array.prototype.slice.call(scope.querySelectorAll('[data-r4-animation]')).forEach(function (el) {
      var type = el.getAttribute('data-r4-animation') || '';
      cleanAnimationClasses(el, 'r4-anim-');
      el.classList.remove('r4-anim', 'is-r4-prepared', 'is-animated');
      el.__r4AnimationScheduled = false;
      if (!type) return;
      applyAnimationVars(el);
      el.classList.add('r4-anim', 'r4-anim-' + type);
      window.requestAnimationFrame(function () {
        el.classList.add('is-r4-prepared');
        if ((el.getAttribute('data-r4-animation-trigger') || 'viewport') === 'load' || isAlreadyVisibleInViewport(el)) {
          scheduleElementAnimation(el);
        }
      });
    });

    Array.prototype.slice.call(scope.querySelectorAll('[data-r4-bg-animation]')).forEach(applyBgAnimation);
  }

  function bindViewportAnimations(root) {
    var scope = root || document;
    var elements = Array.prototype.slice.call(scope.querySelectorAll('[data-r4-animation]')).filter(function (el) {
      return !!(el.getAttribute('data-r4-animation') || '');
    });

    if (!elements.length) return;
    if (!('IntersectionObserver' in window)) {
      elements.forEach(scheduleElementAnimation);
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var el = entry.target;
        var once = (el.getAttribute('data-r4-animation-once') || 'true') === 'true';
        if (entry.isIntersecting) {
          scheduleElementAnimation(el);
          if (once) observer.unobserve(el);
        } else if (!once) {
          el.__r4AnimationScheduled = false;
          el.classList.remove('is-animated');
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });

    elements.forEach(function (el) {
      var trigger = el.getAttribute('data-r4-animation-trigger') || 'viewport';
      if (trigger === 'load') {
        scheduleElementAnimation(el);
      } else {
        observer.observe(el);
      }
    });
  }

  function failSafeVisible() {
    window.setTimeout(function () {
      Array.prototype.slice.call(document.querySelectorAll('.r4-anim[data-r4-animation]')).forEach(function (el) {
        if (!el.classList.contains('is-animated') && isAlreadyVisibleInViewport(el)) {
          startElementAnimation(el);
        }
      });
    }, 1400);
  }

  function init(root) {
    var scope = root || document;
    injectStyles(scope.ownerDocument || document);
    initElements(scope);
    bindViewportAnimations(scope);
    failSafeVisible();
  }

  window.R4V5AnimationsRuntime = {
    init: init,
    refresh: function () { init(document); }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }
})();
