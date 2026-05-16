<?php

namespace App\Support\Blocks;

use Closure;
use Illuminate\Support\Arr;

class BlockRendererRegistry
{
    /** @var array<string, Closure(array $block, array $ctx): string> */
    protected array $renderers = [];

    public function register(string $type, Closure $renderer): void
    {
        $this->renderers[$type] = $renderer;
    }

    public function render(array $block, array $ctx = []): string
    {
        $type = Arr::get($block, 'type');
        if (isset($this->renderers[$type])) {
            return ($this->renderers[$type])($block, $ctx);
        }
        return '';
    }
}
