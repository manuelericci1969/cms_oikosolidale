<template id="r4v4-menu-template-advanced">
    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Elemento selezionato</div>
        <div class="r4v4-custom-help">Configura azioni, link, aspetto, immagini, testi e blocchi dell'elemento selezionato nel canvas.</div>

        <div class="r4v4-form-list">
            <label><span>Tipo elemento</span><input type="text" data-r4-selected-type readonly placeholder="Nessun elemento selezionato"></label>
            <label><span>CSS ID</span><input type="text" data-r4-attr="id" placeholder="sezione-hero"></label>
            <label><span>Classi CSS</span><input type="text" data-r4-classes placeholder="classe-uno classe-due"></label>
            <label><span>Title</span><input type="text" data-r4-attr="title" placeholder="Titolo elemento"></label>
            <label><span>ARIA label</span><input type="text" data-r4-attr="aria-label" placeholder="Etichetta accessibilita"></label>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Azione / Link</div>
        <div class="r4v4-custom-help">Funziona su pulsanti, immagini, testi e blocchi. Se l'elemento non è un link, diventa cliccabile.</div>

        <div class="r4v4-form-list">
            <label><span>URL / Link</span><input type="text" data-r4-link-href placeholder="/contatti oppure https://... oppure #sezione"></label>
            <label><span>Target</span>
                <select data-r4-link-target>
                    <option value="">Predefinito</option>
                    <option value="_self">Stessa finestra</option>
                    <option value="_blank">Nuova finestra</option>
                    <option value="_parent">Parent frame</option>
                    <option value="_top">Top frame</option>
                </select>
            </label>
            <label><span>Rel</span><input type="text" data-r4-attr="rel" placeholder="noopener noreferrer nofollow"></label>
        </div>

        <div class="r4v4-control-actions">
            <button type="button" data-r4-link-apply>Applica link/azione</button>
            <button type="button" data-r4-link-clear>Rimuovi link/azione</button>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Testo / Tipografia</div>
        <div class="r4v4-form-list">
            <label><span>Colore testo</span><input type="color" data-r4-style="color"></label>
            <label><span>Dimensione font</span><input type="text" data-r4-style="font-size" placeholder="18px, 1.2rem, clamp(...)"></label>
            <label><span>Peso font</span>
                <select data-r4-style="font-weight">
                    <option value="">Default</option>
                    <option value="300">Light</option>
                    <option value="400">Regular</option>
                    <option value="500">Medium</option>
                    <option value="600">SemiBold</option>
                    <option value="700">Bold</option>
                    <option value="800">ExtraBold</option>
                    <option value="900">Black</option>
                </select>
            </label>
            <label><span>Interlinea</span><input type="text" data-r4-style="line-height" placeholder="1.4, 28px"></label>
            <label><span>Allineamento</span>
                <select data-r4-style="text-align">
                    <option value="">Default</option>
                    <option value="left">Sinistra</option>
                    <option value="center">Centro</option>
                    <option value="right">Destra</option>
                    <option value="justify">Giustificato</option>
                </select>
            </label>
            <label><span>Trasformazione</span>
                <select data-r4-style="text-transform">
                    <option value="">Default</option>
                    <option value="none">Normale</option>
                    <option value="uppercase">Maiuscolo</option>
                    <option value="lowercase">Minuscolo</option>
                    <option value="capitalize">Capitalizza</option>
                </select>
            </label>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Sfondo / Box</div>
        <div class="r4v4-form-list">
            <label><span>Colore sfondo</span><input type="color" data-r4-style="background-color"></label>
            <label><span>Background CSS</span><input type="text" data-r4-style="background" placeholder="linear-gradient(...), url(...)"></label>
            <label><span>Padding</span><input type="text" data-r4-style="padding" placeholder="24px oppure 24px 32px"></label>
            <label><span>Margine</span><input type="text" data-r4-style="margin" placeholder="0 auto 24px"></label>
            <label><span>Larghezza</span><input type="text" data-r4-style="width" placeholder="100%, 320px, auto"></label>
            <label><span>Max width</span><input type="text" data-r4-style="max-width" placeholder="1140px"></label>
            <label><span>Altezza minima</span><input type="text" data-r4-style="min-height" placeholder="320px, 100vh"></label>
            <label><span>Display</span>
                <select data-r4-style="display">
                    <option value="">Default</option>
                    <option value="block">Block</option>
                    <option value="inline-block">Inline block</option>
                    <option value="flex">Flex</option>
                    <option value="grid">Grid</option>
                    <option value="none">Nascondi</option>
                </select>
            </label>
            <label><span>Gap</span><input type="text" data-r4-style="gap" placeholder="16px"></label>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Bordo / Effetti</div>
        <div class="r4v4-form-list">
            <label><span>Colore bordo</span><input type="color" data-r4-style="border-color"></label>
            <label><span>Bordo</span><input type="text" data-r4-style="border" placeholder="1px solid #e5e7eb"></label>
            <label><span>Raggio bordo</span><input type="text" data-r4-style="border-radius" placeholder="16px"></label>
            <label><span>Ombra</span><input type="text" data-r4-style="box-shadow" placeholder="0 16px 40px rgba(0,0,0,.12)"></label>
            <label><span>Opacita</span><input type="text" data-r4-style="opacity" placeholder="0.85"></label>
            <label><span>Transform</span><input type="text" data-r4-style="transform" placeholder="translateY(-4px), scale(1.02)"></label>
            <label><span>Transition</span><input type="text" data-r4-style="transition" placeholder="all .25s ease"></label>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Immagini / Media</div>
        <div class="r4v4-custom-help">Questi campi sono utili quando l'elemento selezionato è un'immagine o contiene comportamento media.</div>
        <div class="r4v4-form-list">
            <label><span>Src immagine</span><input type="text" data-r4-attr="src" placeholder="/storage/... oppure https://..."></label>
            <label><span>Alt immagine</span><input type="text" data-r4-attr="alt" placeholder="Descrizione immagine"></label>
            <label><span>Object fit</span>
                <select data-r4-style="object-fit">
                    <option value="">Default</option>
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="fill">Fill</option>
                    <option value="scale-down">Scale down</option>
                </select>
            </label>
            <label><span>Object position</span><input type="text" data-r4-style="object-position" placeholder="center center, top left"></label>
        </div>
    </div>

    <div class="r4v4-page-card r4v4-menu-advanced">
        <div class="r4v4-page-card-title">Custom CSS inline</div>
        <div class="r4v4-form-list">
            <label><span>CSS veloce</span><textarea rows="5" data-r4-inline-style placeholder="display:flex;\njustify-content:center;"></textarea></label>
        </div>
        <div class="r4v4-control-actions">
            <button type="button" data-r4-advanced-apply-inline>Applica CSS inline</button>
            <button type="button" data-r4-style-reset>Reset stile rapido</button>
            <button type="button" data-r4-advanced-reset>Reset avanzate</button>
        </div>
    </div>
</template>
