@extends('admin.layout')

@section('title', 'Clienti CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Clienti</h1>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.whatsapp.index') }}" class="btn btn-outline-success">
                <i class="bi bi-whatsapp"></i> Messaggi WhatsApp
            </a>

            <a href="{{ route('admin.crm.customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuovo cliente
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   class="form-control"
                   placeholder="Cerca per nome, email o telefono">
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
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Stato</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>
                                @if($customer->is_active)
                                    <span class="badge bg-success">Attivo</span>
                                @else
                                    <span class="badge bg-secondary">Inattivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($customer->phone)
                                    <a href="{{ route('admin.crm.whatsapp.create', ['customer_id' => $customer->id]) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="Invia WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif

                                <a href="{{ route('admin.crm.customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.crm.customers.destroy', $customer) }}"
                                      method="post"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare questo cliente?');">
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
                            <td colspan="5" class="text-center text-muted py-4">
                                Nessun cliente trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customers->hasPages())
            <div class="card-footer">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
