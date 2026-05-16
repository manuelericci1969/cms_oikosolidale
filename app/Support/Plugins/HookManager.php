<?php

namespace App\Support\Plugins;

class HookManager
{
    protected array $filters = [];

    public function addFilter(string $tag, callable $cb, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $cb;
    }

    public function applyFilters(string $tag, $value, ...$args)
    {
        if (!isset($this->filters[$tag])) return $value;
        ksort($this->filters[$tag]);
        foreach ($this->filters[$tag] as $cbs) {
            foreach ($cbs as $cb) {
                $value = $cb($value, ...$args);
            }
        }
        return $value;
    }
}
