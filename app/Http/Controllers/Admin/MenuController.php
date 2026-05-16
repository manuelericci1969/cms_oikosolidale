<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(): View
    {
        $menus = Menu::with(['items.children'])->orderBy('name')->get();
        return view('admin.menus.index', compact('menus'));
    }

    public function create(): View
    {
        return view('admin.menus.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'alpha_dash', 'unique:menus,slug'],
            'location'  => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'is_active' => ['sometimes', 'boolean'],
            'settings'  => ['nullable', 'array'], // NEW
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['settings']  = $request->input('settings', []); // NEW

        $menu = Menu::create($data);

        return redirect()->route('admin.menus.edit', $menu)->with('ok', 'Menu creato.');
    }

    public function edit(Menu $menu): View
    {
        $menu->load([
            'items' => fn($q) => $q->whereNull('parent_id')->orderBy('order'),
            'items.children' => fn($q) => $q->orderBy('order'),
        ]);
        return view('admin.menus.edit', compact('menu'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('menus', 'slug')->ignore($menu->id)],
            'location'  => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'is_active' => ['sometimes', 'boolean'],
            'settings'  => ['nullable', 'array'], // NEW
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['settings']  = $request->input('settings', []); // NEW

        $menu->update($data);

        return back()->with('ok', 'Menu aggiornato.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        DB::transaction(function () use ($menu) {
            MenuItem::where('menu_id', $menu->id)->delete();
            $menu->delete();
        });

        return redirect()->route('admin.menus.index')->with('ok', 'Menu eliminato.');
    }

    // ---------------------------------------------------------------------
    // Items
    // ---------------------------------------------------------------------

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => [
                'nullable','integer',
                Rule::exists('menu_items','id')->where(fn($q) => $q->where('menu_id',$menu->id)),
            ],
            'title'     => ['required','string','max:255'],
            // Consenti http/https, mailto, tel, /, #
            'url'       => ['nullable','string','max:2048','regex:/^(https?:\/\/|mailto:|tel:|\/|#)/i'],
            'page_id'   => ['nullable','integer', Rule::exists('pages','id')],
            'target'    => ['nullable', Rule::in(['_self','_blank'])],
            'icon'      => ['nullable','string','max:255'],
            'order'     => ['nullable','integer','min:0'],
            'is_active' => ['sometimes','boolean'],
            'type'      => ['nullable','in:link,separator'], // NEW
            'settings'  => ['nullable','array'],              // NEW
        ], [
            'url.regex' => 'L’URL deve essere http/https, mailto:, tel:, /percorso o #ancora.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['type']      = $request->input('type','link');
        $data['settings']  = $request->input('settings', []);

        if ($data['type'] === 'separator') {
            // i separatori non hanno destinazione
            $data['url'] = null;
            $data['page_id'] = null;
            //$data['target'] = null;
            unset($data['target']);
        } else {
            // URL XOR page_id
            if ($request->filled('url') && $request->filled('page_id')) {
                return back()->withErrors([
                    'url'     => 'Scegli URL oppure Pagina, non entrambi.',
                    'page_id' => 'Scegli URL oppure Pagina, non entrambi.',
                ])->withInput();
            }

            // normalizza + harden
            if (isset($data['url'])) {
                $data['url'] = trim((string)$data['url']) ?: null;
                if (!empty($data['url']) && preg_match('/^\s*javascript:/i', $data['url'])) {
                    return back()->withErrors(['url' => 'Schema URL non ammesso.'])->withInput();
                }
            }
        }

        // ordine automatico se assente
        if (!isset($data['order'])) {
            $siblingsMax = MenuItem::where('menu_id', $menu->id)
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('order');
            $data['order'] = is_null($siblingsMax) ? 0 : $siblingsMax + 1;
        }

        $data['menu_id'] = $menu->id;

        MenuItem::create($data);

        return back()->with('ok', 'Voce aggiunta.');
    }

    public function toggleItem(MenuItem $item): RedirectResponse
    {
        $item->is_active = ! $item->is_active;
        $item->save();
        return back()->with('ok', 'Stato voce aggiornato.');
    }

    public function updateItem(Request $request, MenuItem $item): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => [
                'nullable','integer',
                Rule::exists('menu_items','id')->where(fn($q) => $q->where('menu_id',$item->menu_id)),
            ],
            'title'     => ['required','string','max:255'],
            // Allineo allo store: consenti mailto: e tel:
            'url'       => ['nullable','string','max:2048','regex:/^(https?:\/\/|mailto:|tel:|\/|#)/i'],
            'page_id'   => ['nullable','integer', Rule::exists('pages','id')],
            'target'    => ['nullable', Rule::in(['_self','_blank'])],
            'icon'      => ['nullable','string','max:255'],
            'order'     => ['nullable','integer','min:0'],
            'is_active' => ['sometimes','boolean'],
            'type'      => ['nullable','in:link,separator'], // NEW
            'settings'  => ['nullable','array'],              // NEW
        ], [
            'url.regex' => 'L’URL deve essere http/https, mailto:, tel:, /percorso o #ancora.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['type']      = $request->input('type','link');
        $data['settings']  = $request->input('settings', []);

        // no self-parenting
        if (!empty($data['parent_id']) && (int)$data['parent_id'] === (int)$item->id) {
            return back()->withErrors(['parent_id' => 'Una voce non può essere parent di sé stessa.'])->withInput();
        }

        // evita cicli
        if (!empty($data['parent_id']) && $this->isDescendant((int)$data['parent_id'], (int)$item->id, (int)$item->menu_id)) {
            return back()->withErrors(['parent_id' => 'Parent non valido: creerebbe un ciclo.'])->withInput();
        }

        if ($data['type'] === 'separator') {
            $data['url'] = null;
            $data['page_id'] = null;
            //$data['target'] = null;
            unset($data['target']);
        } else {
            // URL XOR page_id
            if ($request->filled('url') && $request->filled('page_id')) {
                return back()->withErrors([
                    'url'     => 'Scegli URL oppure Pagina, non entrambi.',
                    'page_id' => 'Scegli URL oppure Pagina, non entrambi.',
                ])->withInput();
            }
            if (isset($data['url'])) {
                $data['url'] = trim((string)$data['url']) ?: null;
                if (!empty($data['url']) && preg_match('/^\s*javascript:/i', $data['url'])) {
                    return back()->withErrors(['url' => 'Schema URL non ammesso.'])->withInput();
                }
            }
        }

        $originalParent = $item->parent_id;
        $newParent      = $data['parent_id'] ?? null;
        if (!isset($data['order']) && $originalParent !== $newParent) {
            $siblingsMax = MenuItem::where('menu_id', $item->menu_id)
                ->where('parent_id', $newParent)
                ->max('order');
            $data['order'] = is_null($siblingsMax) ? 0 : $siblingsMax + 1;
        }

        $item->update($data);

        return back()->with('ok', 'Voce aggiornata.');
    }

    public function destroyItem(MenuItem $item): RedirectResponse
    {
        DB::transaction(function () use ($item) {
            // porta i figli al livello del parent corrente (oppure radice)
            MenuItem::where('parent_id', $item->id)->update([
                'parent_id' => $item->parent_id,
            ]);
            $item->delete();
        });

        return back()->with('ok', 'Voce eliminata.');
    }

    public function reorderItems(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'orders'   => ['required', 'array'],   // [item_id => order, ...]
            'orders.*' => ['integer', 'min:0'],
        ]);

        $ids = array_map('intval', array_keys($data['orders']));
        $items = MenuItem::where('menu_id', $menu->id)
            ->whereIn('id', $ids)
            ->get(['id']);

        DB::transaction(function () use ($items, $data) {
            foreach ($items as $it) {
                $newOrder = (int) ($data['orders'][$it->id] ?? 0);
                MenuItem::whereKey($it->id)->update(['order' => $newOrder]);
            }
        });

        return back()->with('ok', 'Ordine aggiornato.');
    }

    /**
     * Ritorna true se $candidateParentId è un discendente di $ofItemId (stesso menu).
     */
    protected function isDescendant(int $candidateParentId, int $ofItemId, int $menuId): bool
    {
        $current = $candidateParentId;
        // risali la catena dei parent finché trovi root o l'item
        while ($current !== null) {
            if ($current === $ofItemId) {
                return true; // ciclo
            }
            $current = MenuItem::where('menu_id', $menuId)->whereKey($current)->value('parent_id');
        }
        return false;
    }
}
