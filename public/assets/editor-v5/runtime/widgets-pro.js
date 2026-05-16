(function(){
  'use strict';

  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  function initFaq(root){
    root.querySelectorAll('[data-r4v5-faq-accordion]').forEach(function(list){
      if(list.dataset.r4v5FaqReady === '1') return;
      list.dataset.r4v5FaqReady = '1';
      list.querySelectorAll('.r4v5-pro-faq-question').forEach(function(button){
        button.addEventListener('click', function(){
          var item = button.closest('.r4v5-pro-faq-item');
          if(!item) return;
          var single = list.getAttribute('data-r4v5-faq-single') !== 'false';
          if(single){
            list.querySelectorAll('.r4v5-pro-faq-item.is-open').forEach(function(openItem){
              if(openItem !== item){
                openItem.classList.remove('is-open');
                var openButton = openItem.querySelector('.r4v5-pro-faq-question');
                if(openButton) openButton.setAttribute('aria-expanded', 'false');
              }
            });
          }
          var isOpen = item.classList.toggle('is-open');
          button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
      });
    });
  }

  function initStats(root){
    root.querySelectorAll('[data-r4v5-count]').forEach(function(el){
      if(el.dataset.r4v5CountReady === '1') return;
      el.dataset.r4v5CountReady = '1';
      var target = parseFloat(el.getAttribute('data-r4v5-count'));
      if(!isFinite(target)) return;
      var suffix = el.getAttribute('data-r4v5-count-suffix') || '';
      var prefix = el.getAttribute('data-r4v5-count-prefix') || '';
      var duration = parseInt(el.getAttribute('data-r4v5-count-duration') || '900', 10);
      var start = null;
      function step(ts){
        if(start === null) start = ts;
        var progress = Math.min(1, (ts - start) / duration);
        var eased = 1 - Math.pow(1 - progress, 3);
        var value = Math.round(target * eased);
        el.textContent = prefix + value + suffix;
        if(progress < 1) requestAnimationFrame(step);
      }
      if('IntersectionObserver' in window){
        var observer = new IntersectionObserver(function(entries){
          entries.forEach(function(entry){
            if(entry.isIntersecting){ requestAnimationFrame(step); observer.disconnect(); }
          });
        }, {threshold:.25});
        observer.observe(el);
      }else{
        requestAnimationFrame(step);
      }
    });
  }

  function init(root){
    initFaq(root || document);
    initStats(root || document);
  }

  ready(function(){ init(document); });
  window.R4EditorV5WidgetsPro = { init:init };
})();
