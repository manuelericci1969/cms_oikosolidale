<?php

namespace App\Services\Navigation;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MenuBuilderService
{
    /**
     * Recupera il primo menu attivo associato a una location.
     */
    public function getByLocation(string $location): ?Menu
    {
        return Menu::query()
            ->active()
            ->byLocation($location)
            ->with([
                'items' => fn ($query) => $query->active()->orderBy('order'),
                'items.children' => fn ($query) => $query->active()->orderBy('order'),
                'items.page',
                'items.children.page',
            ])
            ->first();
    }

    /**
     * Recupera un menu attivo tramite slug.
     */
    public function getBySlug(string $slug): ?Menu
    {
        return Menu::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'items' => fn ($query) => $query->active()->orderBy('order'),
                'items.children' => fn ($query) => $query->active()->orderBy('order'),
                'items.page',
                'items.children.page',
            ])
            ->first();
    }

    /**
     * Normalizza le impostazioni grafiche del menu.
     */
    public function styleConfig(?Menu $menu): array
    {
        $settings = is_array($menu?->settings ?? null) ? $menu->settings : [];

        return array_replace_recursive([
            'layout' => [
                'mode' => 'boxed',
                'height' => 76,
                'nav_align' => $settings['nav_align'] ?? 'right',
                'item_gap' => 20,
                'is_sticky' => (bool) ($settings['is_sticky'] ?? false),
            ],
            'typography' => [
                'font_family' => $settings['font_family'] ?? 'system-ui',
                'font_size' => (int) ($settings['font_size'] ?? 15),
                'font_weight' => (string) ($settings['font_weight'] ?? '700'),
                'font_style' => $settings['font_style'] ?? 'normal',
                'letter_spacing' => $settings['letter_spacing'] ?? '0',
                'text_transform' => $settings['text_transform'] ?? 'none',
            ],
            'colors' => [
                'background' => $settings['bg_color'] ?? '#ffffff',
                'background_scrolled' => $settings['scrolled_bg_color'] ?? '#ffffff',
                'link' => $settings['link_color'] ?? '#111827',
                'link_hover' => $settings['link_color_hover'] ?? '#0d6efd',
                'link_active' => $settings['link_color_active'] ?? '#0d6efd',
                'dropdown_background' => $settings['sub_bg_color'] ?? '#ffffff',
                'border' => $settings['border_color'] ?? 'rgba(148, 163, 184, .18)',
            ],
            'effects' => [
                'shadow' => (bool) ($settings['shadow'] ?? false),
                'blur' => (bool) ($settings['blur'] ?? false),
                'dropdown_radius' => (int) ($settings['dropdown_radius'] ?? 16),
            ],
            'mobile' => [
                'mode' => $settings['mobile_mode'] ?? 'offcanvas',
            ],
        ], $settings['builder'] ?? []);
    }

    /**
     * Genera CSS variables sicure da usare nella partial frontend.
     */
    public function cssVariables(?Menu $menu): string
    {
        $config = $this->styleConfig($menu);

        $vars = [
            '--r4-nav-height' => ((int) $config['layout']['height']) . 'px',
            '--r4-nav-gap' => ((int) $config['layout']['item_gap']) . 'px',
            '--r4-nav-bg' => $this->safeCssColor($config['colors']['background'], '#ffffff'),
            '--r4-nav-bg-scrolled' => $this->safeCssColor($config['colors']['background_scrolled'], '#ffffff'),
            '--r4-nav-link' => $this->safeCssColor($config['colors']['link'], '#111827'),
            '--r4-nav-link-hover' => $this->safeCssColor($config['colors']['link_hover'], '#0d6efd'),
            '--r4-nav-link-active' => $this->safeCssColor($config['colors']['link_active'], '#0d6efd'),
            '--r4-nav-dropdown-bg' => $this->safeCssColor($config['colors']['dropdown_background'], '#ffffff'),
            '--r4-nav-border' => $this->safeCssColor($config['colors']['border'], 'rgba(148, 163, 184, .18)'),
            '--r4-nav-font-family' => $this->fontStack($config['typography']['font_family']),
            '--r4-nav-font-size' => ((int) $config['typography']['font_size']) . 'px',
            '--r4-nav-font-weight' => preg_replace('/[^0-9]/', '', (string) $config['typography']['font_weight']) ?: '700',
            '--r4-nav-font-style' => in_array($config['typography']['font_style'], ['normal', 'italic'], true) ? $config['typography']['font_style'] : 'normal',
            '--r4-nav-letter-spacing' => $this->safeCssSize((string) $config['typography']['letter_spacing'], '0'),
            '--r4-nav-text-transform' => in_array($config['typography']['text_transform'], ['none', 'uppercase', 'lowercase', 'capitalize'], true) ? $config['typography']['text_transform'] : 'none',
            '--r4-nav-dropdown-radius' => ((int) $config['effects']['dropdown_radius']) . 'px',
        ];

        return collect($vars)
            ->map(fn ($value, $key) => $key . ':' . $value)
            ->implode(';');
    }

    /**
     * Ritorna le classi root del menu frontend.
     */
    public function rootClasses(?Menu $menu): string
    {
        $config = $this->styleConfig($menu);

        return collect([
            'r4-site-nav',
            'r4-site-nav--' . ($config['layout']['mode'] ?? 'boxed'),
            'r4-site-nav--align-' . ($config['layout']['nav_align'] ?? 'right'),
            !empty($config['layout']['is_sticky']) ? 'r4-site-nav--sticky' : null,
            !empty($config['effects']['shadow']) ? 'r4-site-nav--shadow' : null,
            !empty($config['effects']['blur']) ? 'r4-site-nav--blur' : null,
        ])->filter()->implode(' ');
    }

    public function activeItems(?Menu $menu): Collection
    {
        if (!$menu) {
            return collect();
        }

        if (!$menu->relationLoaded('items')) {
            $menu->load([
                'items' => fn ($query) => $query->active()->orderBy('order'),
                'items.children' => fn ($query) => $query->active()->orderBy('order'),
                'items.page',
                'items.children.page',
            ]);
        }

        return $menu->items ?? collect();
    }

    public function hrefFor(MenuItem $item): string
    {
        if (($item->type ?? 'link') === 'separator') {
            return '#';
        }

        return $item->url ?: '#';
    }

    public function isActive(MenuItem $item): bool
    {
        $href = $this->hrefFor($item);
        if ($href === '#' || Str::startsWith($href, ['http://', 'https://', 'mailto:', 'tel:'])) {
            return false;
        }

        return request()->is(ltrim($href, '/')) || request()->url() === url($href);
    }

    protected function safeCssColor(?string $value, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
            return $value;
        }

        if (preg_match('/^rgba?\([0-9\s,.%]+\)$/i', $value)) {
            return $value;
        }

        return $fallback;
    }

    protected function safeCssSize(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        return preg_match('/^-?[0-9.]+(px|rem|em|%)?$/', $value) ? $value : $fallback;
    }

    protected function fontStack(?string $font): string
    {
        $font = trim((string) $font);

        return match ($font) {
            'Inter', 'Roboto', 'Montserrat', 'Poppins' => "'{$font}', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'Georgia' => "Georgia, 'Times New Roman', serif",
            'serif' => "Georgia, 'Times New Roman', serif",
            'monospace' => "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace",
            default => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        };
    }
}
