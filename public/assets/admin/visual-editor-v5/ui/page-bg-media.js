(function () {
    'use strict';

    function findInput() {
        return document.getElementById('r4v5PageBgImageSrc') || document.querySelector('input[name="meta[page_bg][image][src]"]');
    }

    function findTypeSelect() {
        return document.getElementById('r4v5PageBgType') || document.querySelector('select[name="meta[page_bg][type]"]');
    }

    function makeButton(input) {
        if (!input || input.dataset.r4v5PageBgMediaReady === '1') return;
        input.dataset.r4v5PageBgMediaReady = '1';
        input.id = input.id || 'r4v5PageBgImageSrc';

        const type = findTypeSelect();
        if (type && !type.id) type.id = 'r4v5PageBgType';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'r4v5-mini-btn r4v5-mini-btn-primary';
        button.textContent = 'Scegli da Media';
        button.style.marginTop = '6px';

        button.addEventListener('click', function () {
            if (!window.R4V5Media || typeof window.R4V5Media.openForPageBackground !== 'function') {
                alert('Libreria Media V5 non disponibile.');
                return;
            }
            window.R4V5Media.openForPageBackground(input);
        });

        input.insertAdjacentElement('afterend', button);
    }

    function boot() {
        makeButton(findInput());
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
