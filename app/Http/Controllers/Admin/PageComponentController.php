<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = PageComponent::query()->latest();

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('key', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json(
            $query->get([
                'id',
                'name',
                'key',
                'category',
                'description',
                'schema',
                'preview_html',
                'is_active',
            ])
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $component = new PageComponent();
        $component->fill($validated);
        $component->key = $validated['key'] ?: Str::slug($validated['name'], '_');
        $component->created_by = auth()->id();
        $component->updated_by = auth()->id();
        $component->save();

        return response()->json([
            'ok' => true,
            'component' => $component,
        ], 201);
    }

    public function update(Request $request, PageComponent $pageComponent)
    {
        $validated = $this->validateData($request, $pageComponent->id);

        $pageComponent->fill($validated);
        $pageComponent->updated_by = auth()->id();

        if (empty($pageComponent->key)) {
            $pageComponent->key = Str::slug($pageComponent->name, '_');
        }

        $pageComponent->save();

        return response()->json([
            'ok' => true,
            'component' => $pageComponent,
        ]);
    }

    public function destroy(PageComponent $pageComponent)
    {
        $pageComponent->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'key'           => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('page_components', 'key')->ignore($ignoreId),
            ],
            'category'      => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'schema'        => ['nullable', 'array'],
            'template_html' => ['required', 'string'],
            'template_css'  => ['nullable', 'string'],
            'template_js'   => ['nullable', 'string'],
            'preview_html'  => ['nullable', 'string'],
            'is_active'     => ['sometimes', 'boolean'],
            'is_system'     => ['sometimes', 'boolean'],
        ]);
    }
}
