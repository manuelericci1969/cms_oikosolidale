@extends('admin.layout')

@section('title', 'Prodotti CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Prodotti</h1>
        <a href="{{ route('admin.crm.products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuovo prodotto
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-3">
        <div class="input-group">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                class="form-control"
                placeholder="Cerca per nome, SKU o URL"
            >
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>SKU</th>
                        <th>URL</th>
                        <th>Prezzo</th>
                        <th>IVA</th>
                        <th>Stato</th>
                        <th>Sconto max</th>
                        <th>Promo</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku ?: '—' }}</td>
                            <td>
                                @if($product->website_url)
                                    <a href="{{ $product->website_url }}" target="_blank" rel="noopener noreferrer">
                                        {{ \Illuminate\Support\Str::limit($product->website_url, 45) }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($product->price, 2, ',', '.') }} €</td>
                            <td>{{ number_format($product->tax_rate, 2, ',', '.') }} %</td>

                            <td>
                                @if($product->is_active)
                                    <span class="badge bg-success">Attivo</span>
                                @else
                                    <span class="badge bg-secondary">Inattivo</span>
                                @endif
                            </td>

                            <td>
                                @if(!is_null($product->max_discount))
                                    {{ number_format($product->max_discount, 2, ',', '.') }} %
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($product->is_promo)
                                    <span class="badge bg-info">Promo</span>
                                    @if($product->promo_expires_at)
                                        <small class="text-muted d-block">
                                            fino al {{ $product->promo_expires_at->format('d/m/Y') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.crm.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.crm.products.destroy', $product) }}"
                                      method="post"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare questo prodotto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Nessun prodotto trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
