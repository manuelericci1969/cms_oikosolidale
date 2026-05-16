<?php

namespace App\Services\PageBuilder;

use App\Models\PageComponent;
use Illuminate\Support\Arr;

class ComponentRenderer
{
    public function render(PageComponent $component, array $props = []): array
    {
        $schema   = is_array($component->schema) ? $component->schema : [];
        $defaults = $this->defaultsFromSchema($schema);
        $data     = array_replace_recursive($defaults, $props);

        $rawFields = $this->collectRawFields($schema);

        $html = (string) $component->template_html;
        $css  = (string) ($component->template_css ?? '');
        $js   = (string) ($component->template_js ?? '');

        $html = $this->renderLoops($html, $data, $rawFields);
        $html = $this->renderPlaceholders($html, $data, $rawFields);

        return [
            'html' => $html,
            'css'  => $css,
            'js'   => $js,
        ];
    }

    private function defaultsFromSchema(array $schema): array
    {
        $out = [];

        foreach ($schema as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'text';

            if (!$name) {
                continue;
            }

            if ($type === 'group') {
                $out[$name] = $this->defaultsFromSchema($field['fields'] ?? []);
                continue;
            }

            if ($type === 'repeater') {
                $default = $field['default'] ?? [];
                $out[$name] = is_array($default) ? $default : [];
                continue;
            }

            $out[$name] = $field['default'] ?? null;
        }

        return $out;
    }

    private function collectRawFields(array $schema, string $prefix = ''): array
    {
        $raw = [];

        foreach ($schema as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'text';

            if (!$name) {
                continue;
            }

            $path = $prefix ? $prefix . '.' . $name : $name;

            if (in_array($type, ['html', 'richtext'], true)) {
                $raw[] = $path;
                $raw[] = $name;
            }

            if ($type === 'group') {
                $raw = array_merge($raw, $this->collectRawFields($field['fields'] ?? [], $path));
            }

            if ($type === 'repeater') {
                foreach (($field['fields'] ?? []) as $subField) {
                    $subName = $subField['name'] ?? null;
                    $subType = $subField['type'] ?? 'text';

                    if ($subName && in_array($subType, ['html', 'richtext'], true)) {
                        $raw[] = $subName;
                        $raw[] = $name . '.' . $subName;
                    }
                }
            }
        }

        return array_values(array_unique($raw));
    }

    private function renderLoops(string $template, array $data, array $rawFields): string
    {
        $pattern = '/<!--\s*@foreach\(([\w\.]+)\s+as\s+(\w+)\)\s*-->(.*?)<!--\s*@endforeach\s*-->/s';

        return preg_replace_callback($pattern, function ($matches) use ($data, $rawFields) {
            $collectionPath = $matches[1];
            $alias          = $matches[2];
            $chunk          = $matches[3];

            $items = $this->resolve($data, $collectionPath);

            if (!is_array($items) || empty($items)) {
                return '';
            }

            $output = '';

            foreach ($items as $item) {
                $scope = [$alias => $item];
                $output .= $this->renderPlaceholders($chunk, $data, $rawFields, $scope);
            }

            return $output;
        }, $template);
    }

    private function renderPlaceholders(string $template, array $data, array $rawFields, array $scope = []): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_\.\-]+)\s*}}/', function ($matches) use ($data, $rawFields, $scope) {
            $path = $matches[1];

            $value = $this->resolveScoped($scope, $data, $path);

            if (is_array($value) || is_object($value)) {
                return '';
            }

            $value = (string) ($value ?? '');

            $lastSegment = str_contains($path, '.') ? last(explode('.', $path)) : $path;
            $isRaw = in_array($path, $rawFields, true)
                || in_array($lastSegment, $rawFields, true)
                || str_ends_with($path, '_html');

            return $isRaw ? $value : e($value);
        }, $template);
    }

    private function resolveScoped(array $scope, array $data, string $path)
    {
        $root = explode('.', $path)[0] ?? null;

        if ($root && array_key_exists($root, $scope)) {
            return $this->resolve($scope, $path);
        }

        return $this->resolve($data, $path);
    }

    private function resolve(array $source, string $path)
    {
        return Arr::get($source, $path);
    }
}
