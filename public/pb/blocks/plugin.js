function getPreviewHTML(block){
    try{
        const type = block.type || ''
        const admin = (window.BuilderPlugins && window.BuilderPlugins[type])
        if (admin && typeof admin.renderView === 'function'){
            const out = admin.renderView(block)
            if (out) return String(out)
        }
        const front = (window.FrontPlugins && window.FrontPlugins[type])
        if (typeof front === 'function'){
            const out = front(block)
            if (out) return String(out)
        }
    }catch(_){}
    return ''
}

// public/pb/blocks/plugin.js

export const blockPluginFallback = {
    label: 'Plugin',
    renderPreview({ block }) {
        return `
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted mb-2">
          <i class="bi bi-puzzle me-1"></i>
          Plugin: <code>${block.type || 'sconosciuto'}</code>
        </div>
        <div class="text-muted">Anteprima non disponibile in questo builder.</div>
      </div>
    `;
    },
    bindPreviewEvents(){},
    renderSettings(){ return ''; }
};


function escapeHTML(s){ return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])) }
