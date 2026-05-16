@php
    /** @var \App\Models\PageComponent|null $component */
    $renderer = app(\App\Services\PageBuilder\ComponentRenderer::class);
    $rendered = $component ? $renderer->render($component, $block['props'] ?? []) : null;
@endphp

@if($component && is_array($rendered))
    @if(!empty($rendered['css']))
        <style>
            {!! $rendered['css'] !!}
        </style>
    @endif

    {!! $rendered['html'] ?? '' !!}

    @if(!empty($rendered['js']))
        <script>
            {!! $rendered['js'] !!}
        </script>
    @endif
@endif
