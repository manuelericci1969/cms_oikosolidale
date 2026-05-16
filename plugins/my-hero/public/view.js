// /public/plugins/my-hero/view.js
(function(){
    const TYPE = 'plugin:my-hero';

    const esc = s => String(s||'')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');

    // Normalizza input: accetta "block" o direttamente "data"
    function getData(blockOrData){
        if (blockOrData && blockOrData.data) return blockOrData.data;
        return blockOrData || {};
    }

    function renderHTML(blockOrData){
        const d = getData(blockOrData);
        const subtitle = d.subtitle ?? d.sub ?? '';
        return `
      <section class="py-5 text-center bg-light rounded">
        ${d.title ? `<h2 class="mb-2">${esc(d.title)}</h2>` : ''}
        ${subtitle ? `<p class="text-muted mb-3">${esc(subtitle)}</p>` : ''}
        ${d.cta_url ? `<a class="btn btn-primary btn-pill" href="${esc(d.cta_url)}">
            <i class="bi bi-lightning-charge me-1"></i>${esc(d.cta_label || 'Scopri di più')}
          </a>` : ''}
      </section>
    `;
    }

    // 1) Compat: renderer che chiama funzione(block) -> string HTML
    window.FrontPlugins = window.FrontPlugins || {};
    window.FrontPlugins[TYPE] = function(block){ return renderHTML(block); };

    // 2) Compat: renderer che chiama BuilderPlugins[TYPE].mount(el, data)
    window.BuilderPlugins = window.BuilderPlugins || {};
    window.BuilderPlugins[TYPE] = Object.assign(window.BuilderPlugins[TYPE]||{}, {
        mount(el, data){ el.innerHTML = renderHTML(data); }
    });
})();
