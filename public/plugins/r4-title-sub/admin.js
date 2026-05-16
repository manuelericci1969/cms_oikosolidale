// /public/plugins/r4-title-sub/admin.js
(function () {
    const TYPE = 'plugin:r4-title-sub';
    window.BuilderPlugins = window.BuilderPlugins || {};

    const esc = s => String(s || '').replace(/"/g, '&quot;');

    function bool(v){ return v === true || v === '1' || v === 1 || v === 'true'; }

    window.BuilderPlugins[TYPE] = {
        label: 'Titolo + Sottotitolo',

        renderEditor(sec, block) {
            const d = block.data || (block.data = {});
            d.style = d.style || {};
            // default
            if (!('title' in d)) d.title = '';
            if (!('subtitle' in d)) d.subtitle = d.sub || ''; // retro-compatibilità
            if (!('textColor' in d.style)) d.style.textColor = '#111827';
            if (!('bgColor' in d.style))   d.style.bgColor   = '#ffffff';
            if (!('align' in d.style))     d.style.align     = 'start'; // start|center|end
            if (!('titleBold' in d.style)) d.style.titleBold = true;
            if (!('titleItalic' in d.style)) d.style.titleItalic = false;
            if (!('subBold' in d.style))   d.style.subBold   = false;
            if (!('subItalic' in d.style)) d.style.subItalic = false;

            const alignOptions = [['start','Sinistra'],['center','Centro'],['end','Destra']]
                .map(([v,lab]) => `<option value="${v}" ${d.style.align===v?'selected':''}>${lab}</option>`).join('');

            return `
        <div class="row g-3">
          <div class="col-md-6">
            <label class="small">Titolo</label>
            <input type="text" class="form-control form-control-sm"
                   value="${esc(d.title)}"
                   oninput="updatePluginField('${sec.id}','${block.id}','data.title', this.value)">
            <div class="form-check form-check-inline mt-1">
              <input class="form-check-input" type="checkbox" id="tb_${block.id}" ${bool(d.style.titleBold)?'checked':''}
                     onchange="updatePluginField('${sec.id}','${block.id}','data.style.titleBold', this.checked)">
              <label class="form-check-label" for="tb_${block.id}">Bold</label>
            </div>
            <div class="form-check form-check-inline mt-1">
              <input class="form-check-input" type="checkbox" id="ti_${block.id}" ${bool(d.style.titleItalic)?'checked':''}
                     onchange="updatePluginField('${sec.id}','${block.id}','data.style.titleItalic', this.checked)">
              <label class="form-check-label" for="ti_${block.id}">Italic</label>
            </div>
          </div>

          <div class="col-md-6">
            <label class="small">Sottotitolo</label>
            <input type="text" class="form-control form-control-sm"
                   value="${esc(d.subtitle)}"
                   oninput="updatePluginField('${sec.id}','${block.id}','data.subtitle', this.value)">
            <div class="form-check form-check-inline mt-1">
              <input class="form-check-input" type="checkbox" id="sb_${block.id}" ${bool(d.style.subBold)?'checked':''}
                     onchange="updatePluginField('${sec.id}','${block.id}','data.style.subBold', this.checked)">
              <label class="form-check-label" for="sb_${block.id}">Bold</label>
            </div>
            <div class="form-check form-check-inline mt-1">
              <input class="form-check-input" type="checkbox" id="si_${block.id}" ${bool(d.style.subItalic)?'checked':''}
                     onchange="updatePluginField('${sec.id}','${block.id}','data.style.subItalic', this.checked)">
              <label class="form-check-label" for="si_${block.id}">Italic</label>
            </div>
          </div>

          <div class="col-md-4">
            <label class="small">Allineamento</label>
            <select class="form-select form-select-sm"
                    onchange="updatePluginField('${sec.id}','${block.id}','data.style.align', this.value)">
              ${alignOptions}
            </select>
          </div>

          <div class="col-md-4">
            <label class="small">Colore testo</label>
            <input type="color" class="form-control form-control-sm"
                   value="${d.style.textColor}"
                   oninput="updatePluginField('${sec.id}','${block.id}','data.style.textColor', this.value)">
          </div>

          <div class="col-md-4">
            <label class="small">Sfondo</label>
            <input type="color" class="form-control form-control-sm"
                   value="${d.style.bgColor}"
                   oninput="updatePluginField('${sec.id}','${block.id}','data.style.bgColor', this.value)">
          </div>
        </div>
      `;
        }
    };
})();
