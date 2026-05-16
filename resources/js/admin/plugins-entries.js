// resources/js/admin/plugins-entries.js
export default async function loadPlugins(API){
    try {
        const res = await fetch('/admin/plugins/entries-json', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const { entries = [] } = await res.json();

        for (const url of entries) {
            try {
                // ogni modulo può esportare default(API) oppure register(API)
                const mod = await import(/* @vite-ignore */ url);
                if (typeof mod?.default === 'function')      mod.default(API);
                else if (typeof mod?.register === 'function') mod.register(API);
            } catch (e) {
                console.warn('Errore caricando plugin admin:', url, e);
            }
        }
    } catch (e) {
        console.warn('Nessun plugin admin caricato:', e.message);
    }
}
