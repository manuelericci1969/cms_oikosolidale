/*! R4 Carosello Loghi V2 — FRONTEND (v2.4.8) */
(function () {
    var NS   = 'r4lc-v2';
    var TYPE = 'plugin:r4-logos-carousel-v2';

    function clamp(n, min, max){
        n = parseInt(n, 10);
        if (isNaN(n)) n = min;
        return Math.max(min, Math.min(max, n));
    }

    function makeItem(it){
        // CHILD diretto del track
        var item = document.createElement('div');
        item.className = NS + '-item';

        var img = document.createElement('img');
        img.loading  = 'lazy';
        img.decoding = 'async';
        img.alt = (it && it.alt) ? String(it.alt) : '';
        img.src = (it && it.src) ? String(it.src) : '';

        var href = it && (it.url || it.href); // supporta entrambi
        if (href){
            var a = document.createElement('a');
            a.className = NS + '-link';
            a.href   = String(href);
            a.target = String(it.target || '_self');
            a.rel    = 'noopener';
            a.appendChild(img);
            item.appendChild(a);
        } else {
            item.appendChild(img);
        }
        return item;
    }

    function mount(container, data){
        var d     = data || {};
        var items = Array.isArray(d.items) ? d.items : [];
        var opts  = d.options || d;

        if (!items.length){
            container.innerHTML = '<div class="alert alert-warning mb-0">Nessun logo.</div>';
            return;
        }

        // Opzioni base
        var SPEED = clamp(opts.speed || 30, 6, 400);  // px/s
        var GAP   = clamp(opts.gap   || 24, 0, 200);
        var dir   = String(opts.direction || 'ltr').toLowerCase();
        var pauseOnHover = (opts.pauseOnHover !== false);

        // Dimensioni
        var sizeMode = String(opts.sizeMode || 'height').toLowerCase(); // 'height' | 'box'
        var H  = clamp(opts.height    || 72, 24, 1024);
        var BW = clamp((opts.boxWidth  ?? opts.itemWidth  ?? H), 24, 2048);
        var BH = clamp((opts.boxHeight ?? opts.itemHeight ?? H), 24, 2048);

        // DOM
        container.innerHTML = '';
        var root = document.createElement('div');
        root.className = NS + (dir === 'rtl' ? ' is-rtl' : '');

        // Vars CSS
        root.style.setProperty('--r4lc-gap', GAP + 'px');
        root.style.setProperty('--r4lc-height', H + 'px');
        if (sizeMode === 'box'){
            root.style.setProperty('--r4lc-item-w', BW + 'px');
            root.style.setProperty('--r4lc-item-h', BH + 'px');
        } else {
            root.style.setProperty('--r4lc-item-w', 'auto');
            root.style.setProperty('--r4lc-item-h', H + 'px');
        }

        var viewport = document.createElement('div');
        viewport.className = NS + '-viewport';

        var track = document.createElement('div');
        track.className = NS + '-track';

        // Popola (duplicato per loop continuo)
        var frag = document.createDocumentFragment();
        items.forEach(function(it){ frag.appendChild(makeItem(it)); });
        items.forEach(function(it){ frag.appendChild(makeItem(it)); }); // dup
        track.appendChild(frag);

        viewport.appendChild(track);
        root.appendChild(viewport);
        container.appendChild(root);

        // --- Durata animazione: ricalcola su load immagini e resize ----------------
        function recalc(){
            var full = track.scrollWidth;           // due copie
            var distance = Math.max(1, full / 2);   // px per un ciclo
            var duration = distance / SPEED;        // s
            track.style.setProperty('--r4lc-duration', duration + 's');
        }
        // primo pass
        requestAnimationFrame(recalc);
        // quando le immagini finiscono di caricarsi
        Array.from(track.querySelectorAll('img')).forEach(function(img){
            if (!img.complete) img.addEventListener('load', recalc, { once: true });
        });
        // su resize/layout change
        if ('ResizeObserver' in window){
            var ro = new ResizeObserver(recalc);
            ro.observe(track);
        } else {
            window.addEventListener('resize', recalc);
        }

        // Pausa su hover/touch
        if (pauseOnHover){
            var setPlay = function(v){ track.style.setProperty('--r4lc-play', v); };
            root.addEventListener('mouseenter', function(){ setPlay('paused'); });
            root.addEventListener('mouseleave', function(){ setPlay('running'); });
            root.addEventListener('touchstart', function(){ setPlay('paused'); }, {passive:true});
            root.addEventListener('touchend', function(){ setPlay('running'); });
        }
    }

    // Registry
    window.BuilderPlugins = window.BuilderPlugins || {};
    window.BuilderPlugins[TYPE] = Object.assign(window.BuilderPlugins[TYPE] || {}, {
        mount: mount,
        renderView: function(data){
            var d = data || {};
            var items = Array.isArray(d.items) ? d.items : [];
            var opts = d.options || d;

            var GAP = clamp(opts.gap || 24, 0, 200);
            var dir = String(opts.direction || 'ltr').toLowerCase();
            var sizeMode = String(opts.sizeMode || 'height').toLowerCase();
            var H  = clamp(opts.height || 72, 24, 1024);
            var BW = clamp((opts.boxWidth  ?? opts.itemWidth  ?? H), 24, 2048);
            var BH = clamp((opts.boxHeight ?? opts.itemHeight ?? H), 24, 2048);

            var cls = NS + (dir === 'rtl' ? ' is-rtl' : '');
            var vars = [
                '--r4lc-gap:'+GAP+'px',
                '--r4lc-height:'+H+'px',
                '--r4lc-item-w:'+(sizeMode==='box' ? (BW+'px') : 'auto'),
                '--r4lc-item-h:'+(sizeMode==='box' ? (BH+'px') : (H+'px'))
            ].join(';');

            var logos = items.map(function(it){
                var src = it && it.src ? String(it.src) : '';
                var alt = it && it.alt ? String(it.alt) : '';
                var href = it && (it.url || it.href);
                var inner = '<img src="'+src+'" alt="'+alt+'">';
                if (href){
                    inner = '<a class="'+NS+'-link" href="'+String(href)+'" target="'+String(it.target||'_self')+'" rel="noopener">'+inner+'</a>';
                }
                return '<div class="'+NS+'-item">'+inner+'</div>';
            }).join('');

            return '\
<div class="'+cls+'" style="'+vars+'">\
  <div class="'+NS+'-viewport">\
    <div class="'+NS+'-track">'+logos+logos+'</div>\
  </div>\
</div>';
        }
    });

    try{ document.dispatchEvent(new Event('plugins:ready')); }catch(_){}
})();
