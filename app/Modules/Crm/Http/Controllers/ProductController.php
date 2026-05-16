<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $search   = $request->input('q');

        $query = Product::where('client_id', $clientId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('website_url', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(20);

        return view('crm::products.index', compact('products', 'search'));
    }

    public function create()
    {
        $product = new Product();

        return view('crm::products.create', compact('product'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'unit'             => 'nullable|string|max:20',
            'price'            => 'required|numeric|min:0',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'max_discount'     => 'nullable|numeric|min:0|max:100',
            'description'      => 'nullable|string',
            'website_url'      => 'nullable|url|max:2048',
            'is_active'        => 'nullable|boolean',
            'is_promo'         => 'nullable|boolean',
            'promo_expires_at' => 'nullable|date|after_or_equal:today|required_if:is_promo,1',
        ]);

        $data['client_id']    = $clientId;
        $data['unit']         = $data['unit'] ?? 'pz';
        $data['website_url']  = !empty($data['website_url']) ? trim($data['website_url']) : null;
        $data['is_active']    = $request->boolean('is_active', true);
        $data['is_promo']     = $request->boolean('is_promo', false);

        if (!$data['is_promo']) {
            $data['promo_expires_at'] = null;
        }

        Product::create($data);

        return redirect()
            ->route('admin.crm.products.index')
            ->with('success', 'Prodotto creato con successo.');
    }

    public function edit(Product $product)
    {
        return view('crm::products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'unit'             => 'nullable|string|max:20',
            'price'            => 'required|numeric|min:0',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'max_discount'     => 'nullable|numeric|min:0|max:100',
            'description'      => 'nullable|string',
            'website_url'      => 'nullable|url|max:2048',
            'is_active'        => 'nullable|boolean',
            'is_promo'         => 'nullable|boolean',
            'promo_expires_at' => 'nullable|date|after_or_equal:today|required_if:is_promo,1',
        ]);

        $data['unit']         = $data['unit'] ?? 'pz';
        $data['website_url']  = !empty($data['website_url']) ? trim($data['website_url']) : null;
        $data['is_active']    = $request->boolean('is_active', true);
        $data['is_promo']     = $request->boolean('is_promo', false);

        if (!$data['is_promo']) {
            $data['promo_expires_at'] = null;
        }

        $product->update($data);

        return redirect()
            ->route('admin.crm.products.index')
            ->with('success', 'Prodotto aggiornato con successo.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('admin.crm.products.index')
            ->with('success', 'Prodotto eliminato.');
    }
}
