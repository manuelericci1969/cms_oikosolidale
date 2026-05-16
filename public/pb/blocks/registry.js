// public/pb/blocks/registry.js

const registry = new Map();

/**
 * Registra un tipo di blocco.
 * @param {string} type
 * @param {{label:string, icon?:string, render:Function}} def
 */
export function registerBlock(type, def) {
    if (!type || typeof def !== 'object') return;
    registry.set(type, def);
}

/**
 * Restituisce la definizione di blocco per type.
 */
export function getBlockDef(type) {
    return registry.get(type) || null;
}

/**
 * Ritorna tutti i blocchi registrati
 * (utile in futuro per un menu "Aggiungi blocco").
 */
export function getRegisteredBlocks() {
    return Array.from(registry.entries());
}
