// /public/plugins/my-hero/admin.js
(function () {
    window.BuilderPlugins = window.BuilderPlugins || {};
    const TYPE = 'plugin:my-hero';

    const esc = s => String(s||'').replace(/"/g,'&quot;');

    window.BuilderPlugins[TYPE] = {
        label: 'Hero (plugin)',

        renderEditor(sec, block) {
            const data = block.data || (block.data = {});
            // retro-compatibilità: se esiste 'sub' e non 'subtitle', prefilla
            if (data.sub && !data.subtitle) data.subtitle = data.sub;

            return `
        <div class="mb-2">
          <label class="small">Titolo</label>
          <input type="text" class="form-control form-control-sm"
                 value="${esc(data.title||'')}"
                 oninput="window.updatePluginField('${sec.id}','${block.id}','data.title', this.value)">
        </div>
        <div class="mb-2">
          <label class="small">Sottotitolo</label>
          <input type="text" class="form-control form-control-sm"
                 value="${esc(data.subtitle||'')}"
                 oninput="window.updatePluginField('${sec.id}','${block.id}','data.subtitle', this.value)">
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="small">CTA Label</label>
            <input type="text" class="form-control form-control-sm"
                   value="${esc(data.cta_label||'')}"
                   oninput="window.updatePluginField('${sec.id}','${block.id}','data.cta_label', this.value)">
          </div>
          <div class="col-md-6">
            <label class="small">CTA URL</label>
            <input type="text" class="form-control form-control-sm"
                   value="${esc(data.cta_url||'')}"
                   oninput="window.updatePluginField('${sec.id}','${block.id}','data.cta_url', this.value)">
          </div>
        </div>
      `;
        }
    };
})();
