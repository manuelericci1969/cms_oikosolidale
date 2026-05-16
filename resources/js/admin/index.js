export default function (API) {
    API.addPaletteButton({
        label: 'Hero',
        icon: 'bi bi-lightning',
        onClick: ({ addSection, addBlock, renderBuilder }) => {
            if (!window.builderData.length) addSection();
            const sec = window.builderData[window.builderData.length-1];
            // esempio: crea un blocco "image" + un blocco "text"
            addBlock(sec.id, 12, 'image');
            addBlock(sec.id, 12, 'text');
            renderBuilder();
        }
    });
}
