(function(){
  'use strict';

  var ROOT_SELECTOR = '.page-visual-content';
  var BLOCK_SELECTOR = '[data-r4-animation]';
  var BG_SELECTOR = '[data-r4-bg-animation]';
  var PREPARED_CLASS = 'is-r4-prepared';
  var ANIMATED_CLASS = 'is-animated';
  var LEGACY_VISIBLE_CLASS = 'r4-animation-visible';
  var BG_READY_CLASS = 'is-r4-bg-animation-ready';

  function bgLayerSelectors(prefix){
    return prefix + ' > [data-r4-bg-animation-layer], ' + prefix + ' > [data-r4v5-bg-slider-layer]';
  }

  function addCss(){
    if(document.getElementById('r4v5-public-animation-css')) return;

    var blockBase = ROOT_SELECTOR + ' ' + BLOCK_SELECTOR;
    var bgBase = ROOT_SELECTOR + ' ' + BG_SELECTOR;
    var bgLayers = bgLayerSelectors(bgBase);

    var css = document.createElement('style');
    css.id = 'r4v5-public-animation-css';
    css.textContent = [
      blockBase + '{will-change:opacity,transform,filter;backface-visibility:hidden}',
      blockBase + '.' + PREPARED_CLASS + '{visibility:visible!important}',
      blockBase + '.' + ANIMATED_CLASS + '{opacity:1!important}',

      bgBase + '{position:relative;overflow:hidden}',
      bgLayers + '{animation-duration:var(--r4-bg-anim-duration,7000ms);animation-delay:var(--r4-bg-anim-delay,0ms);animation-timing-function:var(--r4-bg-anim-easing,ease-in-out);animation-fill-mode:both;animation-iteration-count:var(--r4-bg-anim-iteration,infinite);will-change:opacity,transform;transform-origin:center center;pointer-events:none}',
      bgBase + ' > [data-r4-bg-animation-layer]{position:absolute;inset:0;z-index:0;background:inherit;background-image:inherit;background-size:inherit;background-position:inherit;background-repeat:inherit;background-attachment:inherit}',
      bgBase + '.' + BG_READY_CLASS + ' > :not([data-r4-bg-animation-layer]):not([data-r4v5-bg-slider-layer]){position:relative;z-index:1}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="fade"]') + '{animation-name:r4V5BgFade}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="zoom-slow"]') + '{animation-name:r4V5BgZoomSlow}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="zoom-in"]') + '{animation-name:r4V5BgZoomIn}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="zoom-out"]') + '{animation-name:r4V5BgZoomOut}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="ken-burns"]') + '{animation-name:r4V5BgKenBurns}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="pan-left"]') + '{animation-name:r4V5BgPanLeft}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="pan-right"]') + '{animation-name:r4V5BgPanRight}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="pan-up"]') + '{animation-name:r4V5BgPanUp}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="pan-down"]') + '{animation-name:r4V5BgPanDown}',
      bgLayerSelectors(bgBase + '[data-r4-bg-animation="pulse-soft"]') + '{animation-name:r4V5BgPulseSoft}',

      '@keyframes r4V5BgFade{0%,100%{opacity:1}50%{opacity:.72}}',
      '@keyframes r4V5BgZoomSlow{from{transform:scale(1)}to{transform:scale(1.08)}}',
      '@keyframes r4V5BgZoomIn{from{transform:scale(1)}to{transform:scale(1.14)}}',
      '@keyframes r4V5BgZoomOut{from{transform:scale(1.14)}to{transform:scale(1)}}',
      '@keyframes r4V5BgKenBurns{0%{transform:scale(1.08) translate3d(-1.5%,-1.5%,0)}50%{transform:scale(1.15) translate3d(1.5%,1%,0)}100%{transform:scale(1.08) translate3d(-1.5%,-1.5%,0)}}',
      '@keyframes r4V5BgPanLeft{from{transform:scale(1.08) translateX(2%)}to{transform:scale(1.08) translateX(-2%)}}',
      '@keyframes r4V5BgPanRight{from{transform:scale(1.08) translateX(-2%)}to{transform:scale(1.08) translateX(2%)}}',
      '@keyframes r4V5BgPanUp{from{transform:scale(1.08) translateY(2%)}to{transform:scale(1.08) translateY(-2%)}}',
      '@keyframes r4V5BgPanDown{from{transform:scale(1.08) translateY(-2%)}to{transform:scale(1.08) translateY(2%)}}',
      '@keyframes r4V5BgPulseSoft{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.035);opacity:.9}}',

      '@media (prefers-reduced-motion: reduce){' + blockBase + ',' + bgLayers + '{animation:none!important;transition:none!important;opacity:1!important;transform:none!important;filter:none!important}}'
    ].join('');

    document.head.appendChild(css);
  }

  function parseTimeMs(value, fallback, min){
    var raw = String(value == null || value === '' ? fallback : value).trim().toLowerCase().replace(',', '.');
    var parsed = parseFloat(raw);

    if(!Number.isFinite(parsed)) parsed = fallback;

    // Supporta sia millisecondi numerici, sia valori CSS tipo "2.7s" / "2700ms".
    if(raw.indexOf('ms') !== -1){
      parsed = parsed;
    } else if(raw.indexOf('s') !== -1){
      parsed = parsed * 1000;
    } else if(parsed > 0 && parsed < 20){
      // Fallback difensivo: se arriva "2.7" è molto più probabile che siano secondi.
      parsed = parsed * 1000;
    }

    return Math.max(min || 0, Math.round(parsed));
  }

  function timeAttr(el, attr, fallback, min){
    return parseTimeMs(el.getAttribute(attr), fallback, min);
  }

  function toMs(value, fallback, min){
    return parseTimeMs(value, fallback, min) + 'ms';
  }

  function boolAttr(el, attr, fallback){
    var raw = el.getAttribute(attr);
    if(raw == null || raw === '') return fallback;
    raw = String(raw).trim().toLowerCase();
    if(['false', '0', 'no', 'off', 'repeat'].indexOf(raw) !== -1) return false;
    if(['true', '1', 'yes', 'on', 'once'].indexOf(raw) !== -1) return true;
    return fallback;
  }

  function isVisible(el){
    var r = el.getBoundingClientRect();
    var h = window.innerHeight || document.documentElement.clientHeight;
    var w = window.innerWidth || document.documentElement.clientWidth;
    return r.bottom >= 0 && r.right >= 0 && r.top <= h && r.left <= w;
  }

  function getRoots(){
    var roots = Array.prototype.slice.call(document.querySelectorAll(ROOT_SELECTOR));
    return roots.length ? roots : [document];
  }

  function animationStartState(anim){
    switch(anim){
      case 'fade-in':
        return { opacity: '0', transform: 'none', filter: 'none' };
      case 'fade-out':
        return { opacity: '1', transform: 'none', filter: 'none', finalOpacity: '.2' };
      case 'fade-up':
        return { opacity: '0', transform: 'translate3d(0, 42px, 0)', filter: 'none' };
      case 'fade-down':
        return { opacity: '0', transform: 'translate3d(0, -42px, 0)', filter: 'none' };
      case 'fade-left':
      case 'slide-left':
        return { opacity: '0', transform: 'translate3d(-64px, 0, 0)', filter: 'none' };
      case 'fade-right':
      case 'slide-right':
        return { opacity: '0', transform: 'translate3d(64px, 0, 0)', filter: 'none' };
      case 'slide-up':
        return { opacity: '0', transform: 'translate3d(0, 64px, 0)', filter: 'none' };
      case 'zoom-in':
        return { opacity: '0', transform: 'scale(.88)', filter: 'none' };
      case 'zoom-out':
        return { opacity: '0', transform: 'scale(1.12)', filter: 'none' };
      case 'flip-up':
        return { opacity: '0', transform: 'perspective(900px) rotateX(24deg) translate3d(0, 26px, 0)', filter: 'none' };
      case 'blur-in':
        return { opacity: '0', transform: 'translate3d(0, 12px, 0)', filter: 'blur(10px)' };
      default:
        return { opacity: '0', transform: 'translate3d(0, 32px, 0)', filter: 'none' };
    }
  }

  function clearBlockRuntime(el){
    el.classList.remove(ANIMATED_CLASS, LEGACY_VISIBLE_CLASS);
    el.style.animation = 'none';
    el.style.animationName = 'none';
  }

  function prepareBlock(el){
    var anim = (el.getAttribute('data-r4-animation') || '').trim();
    if(!anim || anim === 'none') return;

    var state = animationStartState(anim);
    var duration = timeAttr(el, 'data-r4-animation-duration', 800, 50);
    var delay = timeAttr(el, 'data-r4-animation-delay', 0, 0);
    var easing = el.getAttribute('data-r4-animation-easing') || 'ease';

    clearBlockRuntime(el);
    el.dataset.r4v5AnimRunning = '0';
    el.style.transition = 'none';
    el.style.opacity = state.opacity;
    el.style.transform = state.transform;
    el.style.filter = state.filter;
    el.style.setProperty('--r4-anim-duration', duration + 'ms');
    el.style.setProperty('--r4-anim-delay', delay + 'ms');
    el.style.setProperty('--r4-anim-easing', easing);
    el.classList.add(PREPARED_CLASS);
  }

  function animateBlock(el){
    if(!el) return;

    var anim = (el.getAttribute('data-r4-animation') || '').trim();
    if(!anim || anim === 'none') return;

    var duration = timeAttr(el, 'data-r4-animation-duration', 800, 50);
    var delay = timeAttr(el, 'data-r4-animation-delay', 0, 0);
    var easing = el.getAttribute('data-r4-animation-easing') || 'ease';
    var state = animationStartState(anim);
    var finalOpacity = state.finalOpacity || '1';

    clearBlockRuntime(el);
    el.dataset.r4v5AnimRunning = '1';
    el.style.transition = 'none';
    el.style.animation = 'none';
    el.style.opacity = state.opacity;
    el.style.transform = state.transform;
    el.style.filter = state.filter;

    void el.offsetWidth;

    window.setTimeout(function(){
      requestAnimationFrame(function(){
        el.style.transition = 'opacity ' + duration + 'ms ' + easing + ' ' + delay + 'ms, transform ' + duration + 'ms ' + easing + ' ' + delay + 'ms, filter ' + duration + 'ms ' + easing + ' ' + delay + 'ms';
        el.style.opacity = finalOpacity;
        el.style.transform = 'translate3d(0, 0, 0) scale(1) rotateX(0)';
        el.style.filter = 'none';
        el.classList.add(ANIMATED_CLASS, LEGACY_VISIBLE_CLASS);

        window.setTimeout(function(){
          el.dataset.r4v5AnimRunning = '0';
        }, duration + delay + 80);
      });
    }, 80);
  }

  function directChild(el, selector){
    var children = Array.prototype.slice.call(el.children || []);
    return children.find(function(child){ return child.matches(selector); }) || null;
  }

  function prepareBg(el){
    var anim = (el.getAttribute('data-r4-bg-animation') || '').trim();
    if(!anim || anim === 'none') return;

    var layer = directChild(el, '[data-r4v5-bg-slider-layer]') || directChild(el, '[data-r4-bg-animation-layer]');

    if(!layer){
      layer = document.createElement('div');
      layer.setAttribute('data-r4-bg-animation-layer', '1');
      el.insertBefore(layer, el.firstChild);
    }

    el.style.setProperty('--r4-bg-anim-duration', toMs(el.getAttribute('data-r4-bg-animation-duration'), 7000, 100));
    el.style.setProperty('--r4-bg-anim-delay', toMs(el.getAttribute('data-r4-bg-animation-delay'), 0, 0));
    el.style.setProperty('--r4-bg-anim-easing', el.getAttribute('data-r4-bg-animation-easing') || 'ease-in-out');
    el.style.setProperty('--r4-bg-anim-iteration', boolAttr(el, 'data-r4-bg-animation-loop', true) ? 'infinite' : '1');
    el.classList.add(BG_READY_CLASS);
  }

  function collect(selector){
    var found = [];
    getRoots().forEach(function(root){
      Array.prototype.push.apply(found, Array.prototype.slice.call(root.querySelectorAll(selector)));
    });
    return found;
  }

  function initBlocks(){
    var els = collect(BLOCK_SELECTOR);
    els.forEach(prepareBlock);

    if(!('IntersectionObserver' in window)){
      els.forEach(animateBlock);
      return;
    }

    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        var el = entry.target;
        var once = boolAttr(el, 'data-r4-animation-once', true);

        if(entry.isIntersecting){
          if(el.dataset.r4v5AnimRunning !== '1') animateBlock(el);
          if(once) io.unobserve(el);
        } else if(!once){
          prepareBlock(el);
        }
      });
    }, {threshold:0.16, rootMargin:'0px 0px -8% 0px'});

    els.forEach(function(el){
      var trigger = el.getAttribute('data-r4-animation-trigger') || 'viewport';
      var once = boolAttr(el, 'data-r4-animation-once', true);

      if(trigger === 'load'){
        animateBlock(el);
        return;
      }

      // Anche se già visibile al caricamento, va osservato: serve per repeat viewport.
      io.observe(el);

      if(isVisible(el)){
        window.setTimeout(function(){
          if(el.dataset.r4v5AnimRunning !== '1') animateBlock(el);
          if(once) io.unobserve(el);
        }, 120);
      }
    });
  }

  function initBackgrounds(){
    collect(BG_SELECTOR).forEach(prepareBg);
  }

  function init(){
    addCss();
    initBackgrounds();
    initBlocks();
  }

  window.R4V5PublicAnimations = { init: init };

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
