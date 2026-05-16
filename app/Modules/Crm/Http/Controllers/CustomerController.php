<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Customer;
use Illuminate\Http\Request;
use App\Models\User;


class CustomerController extends Controller
{
    protected function clientId(Request $request): int
    {
        // TODO: sostituisci con la tua logica multi-tenant
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $query = Customer::where('client_id', $clientId);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20);

        return view('crm::customers.index', compact('customers', 'search'));
    }

    public function create(Request $request)
    {
        $customer = new Customer();
        $agents   = User::orderBy('name')->get(['id', 'name']); // 👈 tutti gli utenti (puoi filtrare solo agent)

        return view('crm::customers.create', compact('customer', 'agents'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'pec_email'         => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'vat_number'        => 'nullable|string|max:50',
            'tax_code'          => 'nullable|string|max:50',
            'sdi_code'          => 'nullable|string|max:20',
            'billing_address'   => 'nullable|string|max:255',
            'billing_zip'       => 'nullable|string|max:20',
            'billing_city'      => 'nullable|string|max:255',
            'billing_province'  => 'nullable|string|max:10',
            'billing_country'   => 'nullable|string|max:2',
            'notes'             => 'nullable|string',
            'is_active'         => 'nullable|boolean',
            'owner_id'          => 'nullable|integer|exists:users,id', // 👈
        ]);

        $data['client_id'] = $clientId;
        $data['is_active'] = $request->boolean('is_active', true);

        Customer::create($data);

        return redirect()
            ->route('admin.crm.customers.index')
            ->with('success', 'Cliente creato con successo.');
    }


    public function edit(Request $request, Customer $customer)
    {
        $agents = User::orderBy('name')->get(['id', 'name']);

        return view('crm::customers.edit', compact('customer', 'agents'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'pec_email'         => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'vat_number'        => 'nullable|string|max:50',
            'tax_code'          => 'nullable|string|max:50',
            'sdi_code'          => 'nullable|string|max:20',
            'billing_address'   => 'nullable|string|max:255',
            'billing_zip'       => 'nullable|string|max:20',
            'billing_city'      => 'nullable|string|max:255',
            'billing_province'  => 'nullable|string|max:10',
            'billing_country'   => 'nullable|string|max:2',
            'notes'             => 'nullable|string',
            'is_active'         => 'nullable|boolean',
            'owner_id'          => 'nullable|integer|exists:users,id', // 👈
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $customer->update($data);

        return redirect()
            ->route('admin.crm.customers.index')
            ->with('success', 'Cliente aggiornato con successo.');
    }


    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('admin.crm.customers.index')
            ->with('success', 'Cliente eliminato.');
    }
}
