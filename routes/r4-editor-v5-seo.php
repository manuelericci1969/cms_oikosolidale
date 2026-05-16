<?php

use App\Http\Controllers\Admin\PageVisualEditorV5Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EDITOR V5 - SEO TOOLS
|--------------------------------------------------------------------------
| Rotte dedicate agli strumenti SEO dell'Editor V5.
| Tenute separate da web.php per rendere più semplice il porting su altre
| distribuzioni del CMS R4Software.
*/

Route::middleware(['auth', 'verified', 'active', 'role:admin,superadmin', 'perm:content.create'])
    ->prefix('admin/pages')
    ->as('admin.pages.')
    ->group(function () {
        Route::get('/{page}/seo-meta-v5', function (Page $page) {
            $meta = is_array($page->meta ?? null) ? $page->meta : [];
            $savedSeo = data_get($meta, 'seo');
            $savedSeo = is_array($savedSeo) ? $savedSeo : [];
            $baseUrl = rtrim((string) (config('app.url') ?: url('/')), '/');
            $slug = trim((string) ($page->slug ?? ''), '/');
            $isHome = (bool) ($page->is_homepage ?? false) || in_array(strtolower($slug), ['', 'home', 'homepage', 'index'], true);
            $canonical = $isHome ? $baseUrl : $baseUrl . '/' . $slug;
            $title = (string) (data_get($savedSeo, 'title') ?: data_get($meta, 'seo_title') ?: data_get($meta, 'title') ?: $page->title);
            $description = (string) (data_get($savedSeo, 'description') ?: data_get($meta, 'seo_description') ?: data_get($meta, 'description') ?: $page->excerpt);
            $keywords = (string) (data_get($savedSeo, 'keywords') ?: data_get($meta, 'seo_keywords') ?: data_get($meta, 'keywords'));
            $ogImage = (string) (data_get($savedSeo, 'og.image') ?: data_get($savedSeo, 'og_image') ?: (function_exists('setting') ? setting('seo.og_image_url', '') : ''));

            $defaults = [
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
                'focus_keyword' => (string) data_get($savedSeo, 'focus_keyword', ''),
                'canonical_url' => (string) (data_get($savedSeo, 'canonical_url') ?: data_get($savedSeo, 'canonical') ?: $canonical),
                'robots' => [
                    'index' => (string) data_get($savedSeo, 'robots.index', data_get($savedSeo, 'robots_index', 'index')),
                    'follow' => (string) data_get($savedSeo, 'robots.follow', data_get($savedSeo, 'robots_follow', 'follow')),
                    'advanced' => is_array(data_get($savedSeo, 'robots.advanced')) ? data_get($savedSeo, 'robots.advanced') : [],
                ],
                'og' => [
                    'type' => (string) (data_get($savedSeo, 'og.type') ?: data_get($savedSeo, 'og_type') ?: ($isHome ? 'website' : 'article')),
                    'title' => (string) (data_get($savedSeo, 'og.title') ?: data_get($savedSeo, 'og_title') ?: $title),
                    'description' => (string) (data_get($savedSeo, 'og.description') ?: data_get($savedSeo, 'og_description') ?: $description),
                    'image' => $ogImage,
                ],
                'twitter' => [
                    'card' => (string) (data_get($savedSeo, 'twitter.card') ?: data_get($savedSeo, 'twitter_card') ?: ($ogImage ? 'summary_large_image' : 'summary')),
                    'title' => (string) (data_get($savedSeo, 'twitter.title') ?: data_get($savedSeo, 'twitter_title') ?: $title),
                    'description' => (string) (data_get($savedSeo, 'twitter.description') ?: data_get($savedSeo, 'twitter_description') ?: $description),
                    'image' => (string) (data_get($savedSeo, 'twitter.image') ?: data_get($savedSeo, 'twitter_image') ?: $ogImage),
                ],
                'schema' => [
                    'enabled' => data_get($savedSeo, 'schema.enabled', true),
                    'type' => ((string) data_get($savedSeo, 'schema.type', 'WebPage')) === 'Auto' ? 'WebPage' : (string) data_get($savedSeo, 'schema.type', 'WebPage'),
                    'custom_json' => (string) data_get($savedSeo, 'schema.custom_json', ''),
                ],
            ];

            return response()->json([
                'ok' => true,
                'seo' => array_replace_recursive($defaults, $savedSeo),
                'legacy' => [
                    'title' => data_get($meta, 'seo_title', data_get($meta, 'title')),
                    'description' => data_get($meta, 'seo_description', data_get($meta, 'description')),
                    'keywords' => data_get($meta, 'seo_keywords', data_get($meta, 'keywords')),
                ],
            ]);
        })->name('seo_meta_v5');

        Route::post('/{page}/generate-og-image-v5', [PageVisualEditorV5Controller::class, 'generateOgImage'])
            ->name('generate_og_image_v5');
    });
