<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Modules\Crm\Mail\QuoteSentMail;
use App\Modules\Crm\Models\BillingProfile;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Product;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Models\QuoteItem;
use App\Modules\Crm\Services\BillingProfileDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    protected function clientId(Request $request): int
    {
        // TODO: sostituisci con la tua logica multi-tenant reale
        return 1;
    }

    protected function ensureQuoteClientAccess(Request $request, Quote $quote): void
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId) {
            abort(403);
        }
    }

    protected function validateCustomerForClient(int $clientId, int $customerId): void
    {
        $exists = Customer::where('client_id', $clientId)
            ->where('id', $customerId)
            ->exists();

        if (!$exists) {
            abort(422, 'Cliente non valido per questo account.');
        }
    }

    // ======================== PDF ============================================

    public function pdf(Request $request, Quote $quote, BillingProfileDataService $billingData)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        $quote->load('customer', 'items', 'billingProfile');

        $company = $billingData->companyDataForQuote($quote);
        $logoDataUri = $this->getCompanyLogoDataUri();

        $pdf = Pdf::loadView('crm::quotes.pdf', [
            'quote'       => $quote,
            'company'     => $company,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Preventivo_' . $quote->number . '.pdf';

        return $pdf->download($fileName);
    }

    protected function getCompanyLogoDataUri(): ?string
    {
        $logoId = Setting::get('branding.logo_id');

        if (!$logoId) {
            return null;
        }

        $media = Media::find($logoId);
        if (!$media) {
            return null;
        }

        $relativePath = $media->path ?? null;
        if (!$relativePath) {
            return null;
        }

        $path = storage_path('app/public/' . ltrim($relativePath, '/'));
        if (!file_exists($path)) {
            $path = public_path(ltrim($relativePath, '/'));
            if (!file_exists($path)) {
                return null;
            }
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = @file_get_contents($path);

        if ($data === false) {
            return null;
        }

        $base64 = base64_encode($data);

        return "data:image/{$type};base64,{$base64}";
    }

    // ======================== CRUD QUOTES ====================================

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $mode = $request->query('mode', 'accepted');

        $quotesQuery = Quote::with(['customer', 'payments', 'billingProfile'])
            ->where('client_id', $clientId);

        if ($mode === 'accepted') {
            $quotesQuery->where('status', 'accepted');
        } elseif ($mode === 'pending') {
            $quotesQuery->whereIn('status', ['draft', 'sent', 'rejected']);
        }

        $quotes = $quotesQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('crm::quotes.index', compact('quotes', 'mode'));
    }

    public function create(Request $request, BillingProfileDataService $billingData)
    {
        $clientId = $this->clientId($request);

        $defaultProfile = $billingData->defaultProfile($clientId);

        $quote = new Quote([
            'date'               => now()->toDateString(),
            'currency'           => 'EUR',
            'status'             => 'draft',
            'billing_profile_id' => $defaultProfile?->id,
            'payment_type'       => 'free_text',
            'payment_schedule'   => null,
        ]);

        $defaultIntro = Setting::get('crm.quote_intro_default');
        if (!$defaultIntro) {
            $defaultIntro = "Gentile Cliente,\nci pregiamo di volerle fornire la nostra migliore quotazione.";
        }

        $defaultPayTerm = Setting::get('crm.quote_payment_terms_default', "Pagamento da concordare.");
        $defaultBankDetails = $defaultProfile?->bank_details ?: Setting::get('crm.bank_details', '');

        $quote->intro_text    = $defaultIntro;
        $quote->payment_terms = $defaultPayTerm;
        $quote->bank_details  = $defaultBankDetails;

        $customers = Customer::where('client_id', $clientId)
            ->orderBy('name')
            ->get();

        $products = Product::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $billingProfiles = BillingProfile::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('crm::quotes.create', compact('quote', 'customers', 'products', 'billingProfiles'));
    }

    protected function validateItemsAgainstProducts(\Illuminate\Validation\Validator $validator, int $clientId, array $items): void
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return;
        }

        $products = Product::where('client_id', $clientId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $i => $row) {
            $pid = isset($row['product_id']) && $row['product_id'] !== '' ? (int) $row['product_id'] : null;
            if (!$pid) {
                continue;
            }

            $p = $products->get($pid);

            if (!$p) {
                $validator->errors()->add("items.$i.product_id", "Prodotto non valido.");
                continue;
            }

            if (!$p->is_active) {
                $validator->errors()->add("items.$i.product_id", "Il prodotto \"{$p->name}\" non è attivo.");
            }

            $discount = (float) ($row['discount_percent'] ?? 0);

            if (!is_null($p->max_discount)) {
                $max = (float) $p->max_discount;

                if ($discount > $max) {
                    $validator->errors()->add(
                        "items.$i.discount_percent",
                        "Sconto massimo per \"{$p->name}\" è {$max}%."
                    );
                }
            }
        }
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $validator = Validator::make($request->all(), $this->quoteValidationRules());

        $validator->after(function ($v) use ($clientId, $request) {
            $this->validateItemsAgainstProducts($v, $clientId, $request->input('items', []));
            $this->validateBillingProfileForClient($v, $clientId, $request->input('billing_profile_id'));
            $this->validatePaymentScheduleTotal($v, $request);
        });

        $data = $validator->validate();

        $customerExists = Customer::where('client_id', $clientId)
            ->where('id', $data['customer_id'])
            ->exists();

        if (!$customerExists) {
            return back()
                ->withErrors(['customer_id' => 'Cliente non valido per questo account.'])
                ->withInput();
        }

        return DB::transaction(function () use ($data, $clientId) {
            $profile = !empty($data['billing_profile_id'])
                ? BillingProfile::where('client_id', $clientId)->where('id', $data['billing_profile_id'])->first()
                : null;

            $quote = new Quote();

            $quote->client_id                = $clientId;
            $quote->customer_id              = $data['customer_id'];
            $quote->billing_profile_id       = $profile?->id;
            $quote->billing_profile_snapshot = $profile?->snapshot();
            $quote->date                     = $data['date'];
            $quote->valid_until              = $data['valid_until'] ?? null;
            $quote->status                   = $data['status'] ?? 'draft';
            $quote->currency                 = $data['currency'] ?? 'EUR';
            $quote->notes                    = $data['notes'] ?? null;
            $quote->intro_text               = $data['intro_text'] ?? null;
            $quote->payment_terms            = $data['payment_terms'] ?? null;
            $quote->payment_type             = $data['payment_type'] ?? 'free_text';
            $quote->payment_schedule         = $this->normalizePaymentSchedule($data['payment_schedule'] ?? null, $quote->payment_type);
            $quote->bank_details             = $data['bank_details'] ?? $profile?->bank_details;
            $quote->number                   = $this->generateNumber($clientId);

            $quote->save();

            $this->syncItems($quote, $data['items']);
            $this->recalculateTotals($quote);

            return redirect()
                ->route('admin.crm.quotes.index')
                ->with('success', 'Preventivo creato con successo.');
        });
    }

    public function show(Request $request, Quote $quote)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        $quote->load('customer', 'items', 'payments', 'billingProfile');

        return view('crm::quotes.show', compact('quote'));
    }

    public function edit(Request $request, Quote $quote)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        $clientId = $this->clientId($request);

        $quote->load('items', 'billingProfile');

        $customers = Customer::where('client_id', $clientId)
            ->orderBy('name')
            ->get();

        $products = Product::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $billingProfiles = BillingProfile::where('client_id', $clientId)
            ->where('is_active', true)
            ->orWhere('id', $quote->billing_profile_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('crm::quotes.edit', compact('quote', 'customers', 'products', 'billingProfiles'));
    }

    public function update(Request $request, Quote $quote)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        if ($quote->status === 'accepted') {
            return back()->with('error', 'Un preventivo accettato non può essere modificato.');
        }

        $clientId = $this->clientId($request);

        $validator = Validator::make($request->all(), $this->quoteValidationRules(true));

        $validator->after(function ($v) use ($clientId, $request) {
            $this->validateItemsAgainstProducts($v, $clientId, $request->input('items', []));
            $this->validateBillingProfileForClient($v, $clientId, $request->input('billing_profile_id'));
            $this->validatePaymentScheduleTotal($v, $request);
        });

        $data = $validator->validate();

        $customerExists = Customer::where('client_id', $clientId)
            ->where('id', $data['customer_id'])
            ->exists();

        if (!$customerExists) {
            return back()
                ->withErrors(['customer_id' => 'Cliente non valido per questo account.'])
                ->withInput();
        }

        return DB::transaction(function () use ($data, $quote, $clientId) {
            $profile = !empty($data['billing_profile_id'])
                ? BillingProfile::where('client_id', $clientId)->where('id', $data['billing_profile_id'])->first()
                : null;

            $quote->customer_id              = $data['customer_id'];
            $quote->billing_profile_id       = $profile?->id;
            $quote->billing_profile_snapshot = $profile?->snapshot();
            $quote->date                     = $data['date'];
            $quote->valid_until              = $data['valid_until'] ?? null;
            $quote->status                   = $data['status'] ?? $quote->status;
            $quote->currency                 = $data['currency'] ?? $quote->currency;
            $quote->notes                    = $data['notes'] ?? null;
            $quote->intro_text               = $data['intro_text'] ?? null;
            $quote->payment_terms            = $data['payment_terms'] ?? null;
            $quote->payment_type             = $data['payment_type'] ?? 'free_text';
            $quote->payment_schedule         = $this->normalizePaymentSchedule($data['payment_schedule'] ?? null, $quote->payment_type);
            $quote->bank_details             = $data['bank_details'] ?? $profile?->bank_details;

            $quote->save();

            $this->syncItems($quote, $data['items']);
            $this->recalculateTotals($quote);

            return redirect()
                ->route('admin.crm.quotes.index')
                ->with('success', 'Preventivo aggiornato con successo.');
        });
    }

    public function destroy(Request $request, Quote $quote)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        if ($quote->status === 'accepted') {
            return redirect()
                ->route('admin.crm.quotes.index')
                ->with('error', 'Un preventivo accettato non può essere eliminato.');
        }

        $quote->delete();

        return redirect()
            ->route('admin.crm.quotes.index')
            ->with('success', 'Preventivo eliminato.');
    }

    // ======================== INVIO E ACCETTAZIONE ===========================

    public function send(Request $request, Quote $quote, BillingProfileDataService $billingData)
    {
        $this->ensureQuoteClientAccess($request, $quote);

        $quote->load('customer', 'items', 'billingProfile');

        if (!$quote->customer || !$quote->customer->email) {
            return back()->with('error', 'Il cliente non ha un indirizzo email configurato.');
        }

        if (!$quote->acceptance_token) {
            $quote->acceptance_token = Str::random(64);
        }

        $quote->acceptance_token_expires_at = $quote->valid_until ?: now()->addDays(30);
        $quote->sent_at = now();

        if ($quote->status === 'draft') {
            $quote->status = 'sent';
        }

        $quote->save();

        $company = $billingData->companyDataForQuote($quote);
        $logoDataUri = $this->getCompanyLogoDataUri();

        $pdf = Pdf::loadView('crm::quotes.pdf', [
            'quote'       => $quote,
            'company'     => $company,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        $pdfBinary = $pdf->output();

        $acceptUrl = route('crm.quotes.accept.show', $quote->acceptance_token);

        try {
            Mail::to($quote->customer->email)->send(
                new QuoteSentMail($quote, $pdfBinary, $acceptUrl, $company)
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore durante l\'invio del preventivo.');
        }

        return redirect()
            ->route('admin.crm.quotes.show', $quote)
            ->with('success', 'Preventivo inviato al cliente.')
            ->with('ok', 'Preventivo inviato al cliente.');
    }

    // ======================== HELPERS ========================================

    protected function generateNumber(int $clientId): string
    {
        $year = now()->year;

        $last = Quote::where('client_id', $clientId)
            ->whereYear('date', $year)
            ->orderByDesc('id')
            ->first();

        $seq = 1;

        if ($last && preg_match('/^' . $year . '\-(\d{4})$/', $last->number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%d-%04d', $year, $seq);
    }

    protected function quoteValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'customer_id'                                  => 'required|exists:crm_customers,id',
            'billing_profile_id'                           => 'nullable|exists:crm_billing_profiles,id',
            'date'                                         => 'required|date',
            'valid_until'                                  => 'nullable|date',
            'status'                                       => 'nullable|string|max:20',
            'currency'                                     => 'nullable|string|max:3',
            'notes'                                        => 'nullable|string',
            'items'                                        => 'required|array|min:1',
            'items.*.product_id'                           => 'nullable|exists:crm_products,id',
            'items.*.description'                          => 'required|string|max:255',
            'items.*.quantity'                             => 'required|numeric|min:0.01',
            'items.*.unit'                                 => 'nullable|string|max:20',
            'items.*.unit_price'                           => 'required|numeric|min:0',
            'items.*.discount_percent'                     => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate'                             => 'nullable|numeric|min:0|max:100',
            'intro_text'                                   => ['nullable', 'string'],
            'payment_terms'                                => ['nullable', 'string'],
            'payment_type'                                 => ['nullable', 'string', 'in:free_text,structured'],
            'payment_schedule'                             => ['nullable', 'array'],
            'payment_schedule.deposit'                     => ['nullable', 'array'],
            'payment_schedule.deposit.enabled'             => ['nullable', 'boolean'],
            'payment_schedule.deposit.label'               => ['nullable', 'string', 'max:100'],
            'payment_schedule.deposit.amount'              => ['nullable', 'numeric', 'min:0'],
            'payment_schedule.deposit.due_date'            => ['nullable', 'date'],
            'payment_schedule.installments'                => ['nullable', 'array'],
            'payment_schedule.installments.*.label'        => ['nullable', 'string', 'max:100'],
            'payment_schedule.installments.*.due_date'     => ['nullable', 'date'],
            'payment_schedule.installments.*.amount'       => ['nullable', 'numeric', 'min:0'],
            'bank_details'                                 => ['nullable', 'string'],
        ];

        if ($isUpdate) {
            $rules['items.*.id'] = 'nullable|integer|exists:crm_quote_items,id';
        }

        return $rules;
    }

    protected function validateBillingProfileForClient(\Illuminate\Validation\Validator $validator, int $clientId, mixed $billingProfileId): void
    {
        if (!$billingProfileId) {
            return;
        }

        $exists = BillingProfile::where('client_id', $clientId)
            ->where('id', (int) $billingProfileId)
            ->exists();

        if (!$exists) {
            $validator->errors()->add('billing_profile_id', 'Profilo di fatturazione non valido per questo account.');
        }
    }

    protected function normalizePaymentSchedule(?array $schedule, ?string $paymentType): ?array
    {
        if ($paymentType !== 'structured') {
            return null;
        }

        $schedule = $schedule ?: [];
        $deposit = $schedule['deposit'] ?? [];

        $installments = collect($schedule['installments'] ?? [])
            ->filter(fn ($row) => (float) ($row['amount'] ?? 0) > 0)
            ->values()
            ->map(function ($row, $index) {
                return [
                    'label' => trim((string) ($row['label'] ?? '')) ?: 'Rata ' . ($index + 1),
                    'due_date' => $row['due_date'] ?? null,
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                ];
            })
            ->all();

        return [
            'deposit' => [
                'enabled' => (bool) ($deposit['enabled'] ?? false),
                'label' => trim((string) ($deposit['label'] ?? '')) ?: 'Acconto alla firma',
                'due_date' => $deposit['due_date'] ?? null,
                'amount' => round((float) ($deposit['amount'] ?? 0), 2),
            ],
            'installments' => $installments,
        ];
    }

    protected function validatePaymentScheduleTotal(\Illuminate\Validation\Validator $validator, Request $request): void
    {
        if ($request->input('payment_type') !== 'structured') {
            return;
        }

        $schedule = $this->normalizePaymentSchedule($request->input('payment_schedule', []), 'structured');
        $deposit = $schedule['deposit'] ?? [];
        $total = 0;

        if (($deposit['enabled'] ?? false) && (float) ($deposit['amount'] ?? 0) > 0) {
            $total += (float) $deposit['amount'];
        }

        foreach (($schedule['installments'] ?? []) as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        if ($total <= 0) {
            $validator->errors()->add('payment_schedule', 'Inserisci almeno un acconto o una rata con importo maggiore di zero.');
        }
    }

    protected function syncItems(Quote $quote, array $items): void
    {
        $existingIds = $quote->items()->pluck('id')->all();
        $keptIds = [];

        foreach ($items as $index => $row) {
            $id = $row['id'] ?? null;

            $payload = [
                'product_id'       => $row['product_id'] ?? null,
                'description'      => $row['description'],
                'quantity'         => $row['quantity'],
                'unit'             => $row['unit'] ?? 'pz',
                'unit_price'       => $row['unit_price'],
                'discount_percent' => $row['discount_percent'] ?? 0,
                'tax_rate'         => $row['tax_rate'] ?? 22,
                'sort_order'       => $index,
            ];

            if ($id) {
                $item = QuoteItem::where('quote_id', $quote->id)
                    ->where('id', $id)
                    ->first();

                if ($item) {
                    $item->update($payload);
                    $keptIds[] = $item->id;
                    continue;
                }
            }

            $item = $quote->items()->create($payload);
            $keptIds[] = $item->id;
        }

        $toDelete = array_diff($existingIds, $keptIds);

        if (!empty($toDelete)) {
            QuoteItem::whereIn('id', $toDelete)->delete();
        }
    }

    protected function recalculateTotals(Quote $quote): void
    {
        $quote->load('items');

        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($quote->items as $item) {
            $lineBase = $item->quantity * $item->unit_price;
            $lineDiscount = $lineBase * ($item->discount_percent / 100);
            $lineNet = $lineBase - $lineDiscount;
            $lineTax = $lineNet * ($item->tax_rate / 100);

            $subtotal += $lineBase;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;
        }

        $quote->subtotal = round($subtotal, 2);
        $quote->discount_total = round($discountTotal, 2);
        $quote->tax_total = round($taxTotal, 2);
        $quote->total = round($subtotal - $discountTotal + $taxTotal, 2);

        $quote->save();
    }
}
