<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\BillingProfile;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Services\ContractGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteBillingDataController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function edit(Request $request, Quote $quote)
    {
        $this->ensureAccess($request, $quote);

        $clientId = $this->clientId($request);

        $quote->load('customer', 'billingProfile');

        $billingProfiles = BillingProfile::where('client_id', $clientId)
            ->where(function ($query) use ($quote) {
                $query->where('is_active', true);

                if ($quote->billing_profile_id) {
                    $query->orWhere('id', $quote->billing_profile_id);
                }
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('crm::quotes.billing-data', compact('quote', 'billingProfiles'));
    }

    public function update(Request $request, Quote $quote, ContractGeneratorService $contractGenerator)
    {
        $this->ensureAccess($request, $quote);

        $clientId = $this->clientId($request);

        $data = $request->validate([
            'billing_profile_id' => ['nullable', 'integer', 'exists:crm_billing_profiles,id'],
            'bank_details'       => ['nullable', 'string'],
            'regenerate_contract' => ['nullable', 'boolean'],
        ]);

        $profile = null;

        if (!empty($data['billing_profile_id'])) {
            $profile = BillingProfile::where('client_id', $clientId)
                ->where('id', (int) $data['billing_profile_id'])
                ->first();

            if (!$profile) {
                return back()
                    ->withErrors(['billing_profile_id' => 'Profilo di fatturazione non valido per questo account.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($quote, $profile, $data) {
            $quote->billing_profile_id = $profile?->id;
            $quote->billing_profile_snapshot = $profile?->snapshot();
            $quote->bank_details = $data['bank_details'] ?? $profile?->bank_details;
            $quote->save();
        });

        if ($request->boolean('regenerate_contract') && $quote->status === 'accepted' && $quote->accepted_at) {
            try {
                $contractGenerator->generateFromQuote($quote->fresh(['customer', 'items', 'billingProfile']), true);
            } catch (\Throwable $e) {
                report($e);

                return redirect()
                    ->route('admin.crm.quotes.show', $quote)
                    ->with('error', 'Dati emittente aggiornati, ma errore durante la rigenerazione del contratto: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.crm.quotes.show', $quote)
            ->with('success', 'Dati emittente e coordinate bancarie aggiornati correttamente.');
    }

    protected function ensureAccess(Request $request, Quote $quote): void
    {
        if ((int) $quote->client_id !== $this->clientId($request)) {
            abort(403);
        }
    }
}
