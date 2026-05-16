(function(){
    'use strict';

    const STYLE_ID = 'r4v5-widgets-pro-editor-style';
    const SCRIPT_ID = 'r4v5-widgets-pro-editor-runtime';
    const STYLE_HREF = '/assets/editor-v5/runtime/widgets-pro.css?v=20260507-v5-widgets-pro';
    const SCRIPT_SRC = '/assets/editor-v5/runtime/widgets-pro.js?v=20260507-v5-widgets-pro';

    function injectIntoCanvas(editor){
        const doc = editor && editor.Canvas && editor.Canvas.getDocument ? editor.Canvas.getDocument() : null;
        if(!doc || !doc.head) return;

        if(!doc.getElementById(STYLE_ID)){
            const link = doc.createElement('link');
            link.id = STYLE_ID;
            link.rel = 'stylesheet';
            link.href = STYLE_HREF;
            doc.head.appendChild(link);
        }

        if(!doc.getElementById(SCRIPT_ID)){
            const script = doc.createElement('script');
            script.id = SCRIPT_ID;
            script.src = SCRIPT_SRC;
            script.defer = true;
            doc.head.appendChild(script);
        }else if(doc.defaultView && doc.defaultView.R4EditorV5WidgetsPro){
            doc.defaultView.R4EditorV5WidgetsPro.init(doc);
        }
    }

    function boot(){
        const editor = window.R4EditorV5;
        if(!editor) return false;
        injectIntoCanvas(editor);
        editor.on('load canvas:frame:load component:add component:update', function(){
            injectIntoCanvas(editor);
        });
        return true;
    }

    if(!boot()){
        let attempts = 0;
        const timer = setInterval(function(){
            attempts += 1;
            if(boot() || attempts > 80) clearInterval(timer);
        }, 100);
    }
})();
