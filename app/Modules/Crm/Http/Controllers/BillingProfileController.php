<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Modules\Crm\Models\BillingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingProfileController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $profiles = BillingProfile::where('client_id', $clientId)
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('crm::billing_profiles.index', compact('profiles'));
    }

    public function create(Request $request)
    {
        $profile = new BillingProfile([
            'country'   => 'IT',
            'is_active' => true,
        ]);

        if (!$request->boolean('blank')) {
            $profile->name = Setting::get('company.name');
            $profile->legal_name = Setting::get('company.name');
            $profile->vat = Setting::get('company.vat');
            $profile->tax_code = Setting::get('company.tax_code');
            $profile->address = Setting::get('company.address');
            $profile->city = Setting::get('company.city');
            $profile->zip = Setting::get('company.zip');
            $profile->province = Setting::get('company.province');
            $profile->country = Setting::get('company.country', 'IT');
            $profile->email = Setting::get('company.email');
            $profile->phone = Setting::get('company.phone');
            $profile->pec = Setting::get('company.pec');
            $profile->sdi = Setting::get('company.sdi');
            $profile->bank_details = Setting::get('crm.bank_details') ?: $this->fallbackBankDetails();
        }

        return view('crm::billing_profiles.create', compact('profile'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);
        $data = $this->validated($request);

        return DB::transaction(function () use ($data, $clientId) {
            if (!empty($data['is_default'])) {
                BillingProfile::where('client_id', $clientId)->update(['is_default' => false]);
            }

            $profile = BillingProfile::create($data + ['client_id' => $clientId]);

            if (BillingProfile::where('client_id', $clientId)->count() === 1) {
                $profile->update(['is_default' => true]);
            }

            return redirect()
                ->route('admin.crm.billing-profiles.index')
                ->with('success', 'Profilo di fatturazione creato correttamente.');
        });
    }

    public function edit(Request $request, BillingProfile $billingProfile)
    {
        $this->ensureAccess($request, $billingProfile);

        return view('crm::billing_profiles.edit', ['profile' => $billingProfile]);
    }

    public function update(Request $request, BillingProfile $billingProfile)
    {
        $this->ensureAccess($request, $billingProfile);

        $clientId = $this->clientId($request);
        $data = $this->validated($request);

        return DB::transaction(function () use ($data, $billingProfile, $clientId) {
            if (!empty($data['is_default'])) {
                BillingProfile::where('client_id', $clientId)
                    ->whereKeyNot($billingProfile->id)
                    ->update(['is_default' => false]);
            }

            $billingProfile->update($data);

            return redirect()
                ->route('admin.crm.billing-profiles.index')
                ->with('success', 'Profilo di fatturazione aggiornato correttamente.');
        });
    }

    public function destroy(Request $request, BillingProfile $billingProfile)
    {
        $this->ensureAccess($request, $billingProfile);

        if ($billingProfile->quotes()->exists()) {
            return back()->with('error', 'Non puoi eliminare questo profilo perché è già collegato ad almeno un preventivo. Puoi disattivarlo.');
        }

        $billingProfile->delete();

        return redirect()
            ->route('admin.crm.billing-profiles.index')
            ->with('success', 'Profilo di fatturazione eliminato.');
    }

    protected function ensureAccess(Request $request, BillingProfile $profile): void
    {
        if ((int) $profile->client_id !== $this->clientId($request)) {
            abort(403);
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'legal_name'   => ['nullable', 'string', 'max:255'],
            'vat'          => ['nullable', 'string', 'max:50'],
            'tax_code'     => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:255'],
            'zip'          => ['nullable', 'string', 'max:20'],
            'province'     => ['nullable', 'string', 'max:20'],
            'country'      => ['nullable', 'string', 'max:2'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:100'],
            'pec'          => ['nullable', 'email', 'max:255'],
            'sdi'          => ['nullable', 'string', 'max:20'],
            'bank_details' => ['nullable', 'string'],
            'notes'        => ['nullable', 'string'],
        ]);

        $data['country'] = strtoupper($data['country'] ?? 'IT');
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function fallbackBankDetails(): string
    {
        $parts = [];

        if ($bank = Setting::get('company.bank')) {
            $parts[] = 'Banca: ' . $bank;
        }

        if ($iban = Setting::get('company.iban')) {
            $parts[] = 'IBAN: ' . $iban;
        }

        if ($bic = Setting::get('company.bic')) {
            $parts[] = 'BIC/SWIFT: ' . $bic;
        }

        return implode("\n", $parts);
    }
}
