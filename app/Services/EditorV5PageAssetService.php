<?php

namespace App\Services;

use App\Models\Page;
use App\Support\VisualEditorCssScope;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EditorV5PageAssetService
{
    public function sync(Page $page): array
    {
        if (($page->editor_mode ?? 'structured') !== 'visual' || !$page->exists) return ['css'=>null,'js'=>null];
        $directory=public_path('assets/editor-v5/pages/'.$page->getKey());
        if(!File::isDirectory($directory)&&!File::makeDirectory($directory,0755,true,true)&&!File::isDirectory($directory))throw new RuntimeException('Impossibile creare la directory degli asset Editor V5: '.$directory);
        $css=trim((string)($page->visual_css??'')); $js=trim((string)($page->visual_js??''));
        $this->writeOrDelete($directory.'/page.css',$css===''?'':VisualEditorCssScope::scope($css,'.page-visual-content')."\n");
        $this->writeOrDelete($directory.'/page.js',$js===''?'':$this->wrapJavaScript($js,(int)$page->id));
        return ['css'=>$css===''?null:asset('assets/editor-v5/pages/'.$page->getKey().'/page.css'),'js'=>$js===''?null:asset('assets/editor-v5/pages/'.$page->getKey().'/page.js')];
    }
    public function delete(Page $page):void{if($page->getKey())File::deleteDirectory(public_path('assets/editor-v5/pages/'.$page->getKey()));}
    private function writeOrDelete(string $path,string $content):void{if($content===''){File::delete($path);return;}if(!File::exists($path)||File::get($path)!==$content){if(File::put($path,$content,true)===false)throw new RuntimeException('Impossibile scrivere l’asset Editor V5: '.$path);}}
    private function wrapJavaScript(string $code,int $pageId):string{$needs=preg_match('/\b(?:THREE|R4Anime|R4AnimationEngines|animejs)\b/',$code)===1?'true':'false';return "(function(){'use strict';var started=false;var needsEngines={$needs};function execute(){if(started)return;started=true;try{\n{$code}\n}catch(error){console.warn('[R4 Editor V5] Custom JS pagina {$pageId}',error);}}function boot(){if(!needsEngines||window.R4AnimationEngines||window.R4Anime||window.animejs||window.THREE){execute();return;}window.addEventListener('r4:animation-engines:ready',execute,{once:true});import('/assets/editor-v5/runtime/animation-engines.module.js').then(execute).catch(function(error){console.warn('[R4 Editor V5] Motori animazione non disponibili',error);execute();});}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',boot,{once:true});}else{boot();}})();\n";}
}
