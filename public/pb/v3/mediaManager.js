// public/pb/v3/mediaManager.js

import { getMediaUrlByQuality } from '../mediaPicker.js';

export function normalizeMediaItem(item) {
    if (!item || typeof item !== 'object') return null;

    const full = getMediaUrlByQuality(item, 'full', '');
    const thumb = getMediaUrlByQuality(item, 'thumb', full);
    const q25 = getMediaUrlByQuality(item, '25', full);
    const q59 = getMediaUrlByQuality(item, '59', full);
    const q75 = getMediaUrlByQuality(item, '75', full);

    return {
        id: item.id ?? null,
        src: item.src || full,
        url: item.url || full,
        full,
        thumb,
        q25,
        q59,
        q75,
        variants: {
            thumb,
            '25': q25,
            '59': q59,
            '75': q75,
            full
        },
        alt: String(item.alt || ''),
        caption: String(item.caption || item.title || item.alt || ''),
        title: String(item.title || item.original_name || ''),
        mime: String(item.mime || ''),
        width: item.width || item.w || null,
        height: item.height || item.h || null
    };
}

export function safeImages(value) {
    if (Array.isArray(value)) {
        return value
            .map(normalizeMediaItem)
            .filter(Boolean)
            .filter(item => item.full);
    }

    if (typeof value === 'string' && value.trim() !== '') {
        try {
            const parsed = JSON.parse(value);
            if (Array.isArray(parsed)) {
                return safeImages(parsed);
            }
        } catch (e) {
            return [];
        }
    }

    return [];
}

export function stringifyImages(images) {
    try {
        return JSON.stringify(safeImages(images));
    } catch (e) {
        return '[]';
    }
}

export function appendImagesToComponent(component, fieldName, items) {
    const current = safeImages(component.get(fieldName));
    const incoming = safeImages(items);
    component.set(fieldName, stringifyImages([...current, ...incoming]));
}

export function replaceImagesInComponent(component, fieldName, items) {
    const incoming = safeImages(items);
    component.set(fieldName, stringifyImages(incoming));
}

export function clearImagesInComponent(component, fieldName) {
    component.set(fieldName, '[]');
}

export function removeImageFromComponent(component, fieldName, index) {
    const current = safeImages(component.get(fieldName));
    if (index < 0 || index >= current.length) return;
    current.splice(index, 1);
    component.set(fieldName, stringifyImages(current));
}

export function moveImageInComponent(component, fieldName, fromIndex, toIndex) {
    const current = safeImages(component.get(fieldName));
    if (
        fromIndex < 0 || fromIndex >= current.length ||
        toIndex < 0 || toIndex >= current.length ||
        fromIndex === toIndex
    ) {
        return;
    }

    const [moved] = current.splice(fromIndex, 1);
    current.splice(toIndex, 0, moved);
    component.set(fieldName, stringifyImages(current));
}

export function updateImageMetaInComponent(component, fieldName, index, patch = {}) {
    const current = safeImages(component.get(fieldName));
    if (index < 0 || index >= current.length) return;

    current[index] = {
        ...current[index],
        alt: patch.alt !== undefined ? String(patch.alt || '') : current[index].alt,
        caption: patch.caption !== undefined ? String(patch.caption || '') : current[index].caption,
        title: patch.title !== undefined ? String(patch.title || '') : current[index].title
    };

    component.set(fieldName, stringifyImages(current));
}
