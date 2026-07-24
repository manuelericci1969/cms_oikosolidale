<?php

namespace App\Providers;

use App\Models\Page;
use App\Modules\Crm\Models\Appointment;
use App\Modules\Crm\Observers\AppointmentObserver;
use App\Services\EditorV5PageAssetService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register():void{$this->app->singleton(EditorV5PageAssetService::class);}
    public function boot():void
    {
        Paginator::useBootstrapFive();Appointment::observe(AppointmentObserver::class);
        Page::saving(function(Page $page):void{if(($page->editor_mode??request()->input('editor_mode'))!=='visual')return;$has=request()->has('visual_js');if($has)$page->visual_js=(string)request()->input('visual_js','');$json=is_array($page->visual_json??null)?$page->visual_json:[];$legacy=trim((string)data_get($json,'r4v5CustomJs',''));if(!$has&&$legacy!=='')$page->visual_js=$legacy;unset($json['r4v5CustomJs']);$page->visual_json=$json;$html=(string)($page->visual_html??'');$embedded=[];$html=preg_replace_callback('/<script\b([^>]*)>([\s\S]*?)<\/script>/i',function(array $m)use(&$embedded):string{$a=(string)($m[1]??'');$b=trim((string)($m[2]??''));if(preg_match('/\bsrc\s*=/i',$a)||preg_match('/\bid=["\']r4v5-[^"\']*["\']/i',$a))return(string)$m[0];if($b!=='')$embedded[]=$b;return'';},$html)??$html;if(!$has&&$legacy===''&&$embedded!==[])$page->visual_js=trim(implode("\n\n",$embedded));$page->visual_html=trim($html);});
        Page::saved(fn(Page $page)=>app(EditorV5PageAssetService::class)->sync($page));Page::deleted(function(Page $page):void{if($page->isForceDeleting())app(EditorV5PageAssetService::class)->delete($page);});
        View::composer('admin.pages.editV5',function($view):void{$page=$view->getData()['page']??null;if(!$page||($page->editor_mode??'structured')!=='visual')return;$json=is_array($page->visual_json??null)?$page->visual_json:[];$json['r4v5CustomJs']=(string)($page->visual_js??data_get($json,'r4v5CustomJs',''));$page->setAttribute('visual_json',$json);});
        View::composer('page.show',function($view):void{$page=$view->getData()['page']??null;if(!$page||($page->editor_mode??'structured')!=='visual')return;$json=is_array($page->visual_json??null)?$page->visual_json:[];$legacy=trim((string)data_get($json,'r4v5CustomJs',''));if(blank($page->visual_js??null)&&$legacy!==''){$page->visual_js=$legacy;unset($json['r4v5CustomJs']);$page->visual_json=$json;$page->saveQuietly();}unset($json['r4v5CustomJs']);$assets=app(EditorV5PageAssetService::class)->sync($page);$v=$page->updated_at?->timestamp??time();$page->setAttribute('visual_css',$assets['css']?'@import url("'.$assets['css'].'?v='.$v.'");':'');if($assets['js']){$url=json_encode($assets['js'].'?v='.$v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);$json['r4v5CustomJs']="(function(){if(document.querySelector('script[data-r4v5-page-asset=\"js\"]'))return;var s=document.createElement('script');s.src={$url};s.defer=true;s.setAttribute('data-r4v5-page-asset','js');document.head.appendChild(s);})();";}$page->setAttribute('visual_json',$json);});
        if($this->app->runningInConsole())$this->commands([\App\Console\Commands\SeoImprovePagesCommand::class]);
    }
}
