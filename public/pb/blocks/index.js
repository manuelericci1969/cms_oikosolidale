// public/pb/blocks/index.js

import { renderRichTextBlock } from './richtext.js';
import { renderImageBlock } from './image.js';
import { renderGalleryBlock } from './gallery.js';
import { renderVideoBlock } from './video.js';
import { renderLogoCarouselBlock } from './logoCarousel.js';
import { renderComponentBlock } from './component.js';

function renderUnknownBlock(ctx) {
    const { container, block, section, state, rerender } = ctx;
    container.innerHTML = '';

    const body = document.createElement('div');
    body.className = 'card-body';

    const title = document.createElement('div');
    title.className = 'd-flex align-items-center justify-content-between mb-2';

    const left = document.createElement('div');
    left.innerHTML = `<span class="badge bg-secondary-subtle text-secondary-emphasis">
        Blocco non gestito
    </span>`;
    title.appendChild(left);

    const right = document.createElement('div');
    right.className = 'd-flex align-items-center gap-1';

    const up = document.createElement('button');
    up.type = 'button';
    up.className = 'btn btn-sm btn-light border';
    up.title = 'Sposta blocco su';
    up.innerHTML = '<i class="bi bi-arrow-up"></i>';
    up.addEventListener('click', (e) => {
        e.preventDefault();
        state.moveBlock(section.id, block.id, -1);
        rerender && rerender();
    });
    right.appendChild(up);

    const down = document.createElement('button');
    down.type = 'button';
    down.className = 'btn btn-sm btn-light border';
    down.title = 'Sposta blocco giù';
    down.innerHTML = '<i class="bi bi-arrow-down"></i>';
    down.addEventListener('click', (e) => {
        e.preventDefault();
        state.moveBlock(section.id, block.id, +1);
        rerender && rerender();
    });
    right.appendChild(down);

    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'btn btn-sm btn-outline-danger';
    del.title = 'Elimina blocco';
    del.innerHTML = '<i class="bi bi-trash"></i>';
    del.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('Eliminare questo blocco?')) {
            state.removeBlock(section.id, block.id);
            rerender && rerender();
        }
    });
    right.appendChild(del);

    title.appendChild(right);
    body.appendChild(title);

    const info = document.createElement('p');
    info.className = 'small text-muted mb-0';
    info.textContent = `Tipo blocco: ${block.type || 'sconosciuto'}. Verrà comunque renderizzato nel frontend se supportato dal tema.`;
    body.appendChild(info);

    container.appendChild(body);
}

export function renderBlock(ctx) {
    const { block } = ctx;
    const type = (block && block.type) || 'richtext';

    if (type === 'text' || type === 'richtext') {
        return renderRichTextBlock(ctx);
    }

    if (type === 'image') {
        return renderImageBlock(ctx);
    }

    if (type === 'gallery') {
        return renderGalleryBlock(ctx);
    }

    if (type === 'video') {
        return renderVideoBlock(ctx);
    }

    if (type === 'logo_carousel') {
        return renderLogoCarouselBlock(ctx);
    }

    if (type === 'component') {
        return renderComponentBlock(ctx);
    }

    return renderUnknownBlock(ctx);
}
