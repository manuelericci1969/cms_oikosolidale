// /public/plugins/r4-title-sub/view.js
(function(){
    const TYPE = 'plugin:r4-title-sub';

    const esc = s => String(s||'')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');

    // accetta sia block che data
    function getData(blockOrData){
        if (blockOrData && blockOrData.data) return blockOrData.data;
        return blockOrData || {};
    }

    // piccola sanitizzazione colori (solo hex 3/6 o rgba/hsla basilari)
    function safeColor(v, fallback){
        const s = String(v||'').trim();
        if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(s)) return s;
        if (/^rgba?\([\d\s.,%]+\)$/.test(s)) return s;
        if (/^hsla?\([\d\s.,%]+\)$/.test(s)) return s;
        return fallback;
    }

    function classesFor(bold, italic){
        let cls = '';
        if (bold) cls += ' r4ts-bold';
        if (italic) cls += ' r4ts-italic';
        return cls;
    }

    function alignClass(align){
        if (align === 'center') return 'text-center';
        if (align === 'end')    return 'text-end';
        return 'text-start';
    }

    function renderHTML(blockOrData){
        const d = getData(blockOrData);
        const st = d.style || {};
        const color = safeColor(st.textColor, '#111827');
        const bg    = safeColor(st.bgColor,   '#ffffff');
        const align = st.align || 'start';

        const tCls = classesFor(!!st.titleBold, !!st.titleItalic);
        const sCls = classesFor(!!st.subBold,   !!st.subItalic);

        return `
      <section class="r4ts-wrap ${alignClass(align)}" style="background-color:${bg}; color:${color}">
        ${d.title     ? `<h2 class="r4ts-title${tCls}">${esc(d.title)}</h2>` : ''}
        ${d.subtitle  ? `<p class="r4ts-sub${sCls}">${esc(d.subtitle)}</p>` : ''}
      </section>
    `;
    }

    // Runtime 1: il tuo renderer chiama funzione(block) => HTML
    window.FrontPlugins = window.FrontPlugins || {};
    window.FrontPlugins[TYPE] = function(block){ return renderHTML(block); };

    // Runtime 2: compatibilità con BuilderPlugins .mount(el, data)
    window.BuilderPlugins = window.BuilderPlugins || {};
    window.BuilderPlugins[TYPE] = Object.assign(window.BuilderPlugins[TYPE]||{}, {
        mount(el, data){ el.innerHTML = renderHTML(data); }
    });
})();
