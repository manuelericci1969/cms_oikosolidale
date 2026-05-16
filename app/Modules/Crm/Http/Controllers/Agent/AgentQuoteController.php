<?php

namespace App\Modules\Crm\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Modules\Crm\Mail\QuoteSentMail;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\Product;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Models\QuoteItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AgentQuoteController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function userId(Request $request): int
    {
        return $request->user()->id;
    }

    /**
     * Un agente può accedere solo ai preventivi dei clienti
     * di cui è owner (crm_customers.owner_id).
     */
    protected function authorizeQuote(Request $request, Quote $quote): void
    {
        $quote->loadMissing('customer');

        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        if ((int) $quote->client_id !== $clientId) {
            abort(403, 'Preventivo di un altro client.');
        }

        if (!$quote->customer || (int) $quote->customer->owner_id !== $userId) {
            abort(403, 'Non hai accesso a questo preventivo.');
        }
    }

    protected function authorizeLead(Request $request, Lead $lead): void
    {
        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        if ((int) $lead->client_id !== $clientId || (int) $lead->owner_id !== $userId) {
            abort(403, 'Non hai accesso a questo lead.');
        }
    }

    // ---------------------------------------------------------------------
    // INDEX: preventivi dell’agente (via owner_id sul cliente)
    // ---------------------------------------------------------------------
    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $query = Quote::with('customer')
            ->where('client_id', $clientId)
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('owner_id', $userId);
            });

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $quotes = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('crm::agent.quotes.index', compact('quotes', 'search', 'status'));
    }

    // ---------------------------------------------------------------------
    // CREATE "libero"
    // ---------------------------------------------------------------------
    public function create(Request $request)
    {
        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $quote = new Quote([
            'date'     => now()->toDateString(),
            'currency' => 'EUR',
            'status'   => 'draft',
        ]);

        $defaultIntro = Setting::get('crm.quote_intro_default')
            ?: "Gentile Cliente,\nci pregiamo di volerle fornire la nostra migliore quotazione.";
        $defaultPay   = Setting::get('crm.quote_payment_terms_default', "Pagamento da concordare.");

        $quote->intro_text    = $defaultIntro;
        $quote->payment_terms = $defaultPay;

        // SOLO clienti dell’agente
        $customers = Customer::where('client_id', $clientId)
            ->where('owner_id', $userId)
            ->orderBy('name')
            ->get();

        // Prodotti del client (comuni)
        $products = Product::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm::agent.quotes.create', compact('quote', 'customers', 'products'));
    }

    // ---------------------------------------------------------------------
    // CREATE FROM LEAD (solo precompilazione, nessun campo lead_id su quotes)
    // ---------------------------------------------------------------------
    public function createFromLead(Request $request, Lead $lead)
    {
        $this->authorizeLead($request, $lead);

        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $quote = new Quote([
            'date'     => now()->toDateString(),
            'currency' => 'EUR',
            'status'   => 'draft',
        ]);

        $defaultIntro = Setting::get('crm.quote_intro_default')
            ?: "Gentile {$lead->name},\nle inviamo la nostra migliore quotazione.";
        $defaultPay   = Setting::get('crm.quote_payment_terms_default', "Pagamento da concordare.");

        $quote->intro_text    = $defaultIntro;
        $quote->payment_terms = $defaultPay;

        // se il lead è già collegato a un cliente, pre-selezioniamolo
        if ($lead->customer && (int) $lead->customer->owner_id === $userId) {
            $quote->customer_id = $lead->customer_id;
        }

        $customers = Customer::where('client_id', $clientId)
            ->where('owner_id', $userId)
            ->orderBy('name')
            ->get();

        $products = Product::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm::agent.quotes.create', compact('quote', 'customers', 'products', 'lead'));
    }

    // ---------------------------------------------------------------------
    // STORE (con controllo max_discount)
    // ---------------------------------------------------------------------
    public function store(Request $request)
    {
        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $validator = Validator::make($request->all(), [
            'customer_id'              => 'required|exists:crm_customers,id',
            'date'                     => 'required|date',
            'valid_until'              => 'nullable|date',
            'status'                   => 'nullable|string|max:20',
            'currency'                 => 'nullable|string|max:3',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'nullable|exists:crm_products,id',
            'items.*.description'      => 'required|string|max:255',
            'items.*.quantity'         => 'required|numeric|min:0.01',
            'items.*.unit'             => 'nullable|string|max:20',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'intro_text'               => ['nullable', 'string'],
            'payment_terms'            => ['nullable', 'string'],
        ]);

        $validator->after(function ($v) use ($clientId, $userId, $request) {
            // Cliente deve appartenere all’agente
            $customerId = $request->input('customer_id');
            $okCustomer = Customer::where('client_id', $clientId)
                ->where('owner_id', $userId)
                ->where('id', $customerId)
                ->exists();

            if (!$okCustomer) {
                $v->errors()->add('customer_id', 'Cliente non valido o non assegnato al tuo account.');
            }

            // Applica max_discount (e prodotti attivi / del client)
            $this->validateItemsAgainstProducts($v, $clientId, $request->input('items', []));
        });

        $data = $validator->validate();

        return DB::transaction(function () use ($data, $clientId) {
            $quote = new Quote();

            $quote->client_id   = $clientId;
            $quote->customer_id = $data['customer_id'];
            $quote->date        = $data['date'];
            $quote->valid_until = $data['valid_until'] ?? null;
            $quote->status      = $data['status'] ?? 'draft';
            $quote->currency    = $data['currency'] ?? 'EUR';
            $quote->notes       = $data['notes'] ?? null;

            $quote->intro_text    = $data['intro_text'] ?? null;
            $quote->payment_terms = $data['payment_terms'] ?? null;

            $quote->number = $this->generateNumber($clientId);
            $quote->save();

            $this->syncItems($quote, $data['items']);
            $this->recalculateTotals($quote);

            return redirect()
                ->route('agent.crm.quotes.index')
                ->with('success', 'Preventivo creato con successo.');
        });
    }

    // ---------------------------------------------------------------------
    // EDIT / UPDATE (con controllo max_discount)
    // ---------------------------------------------------------------------
    public function edit(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);

        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $quote->load('items');

        $customers = Customer::where('client_id', $clientId)
            ->where('owner_id', $userId)
            ->orderBy('name')
            ->get();

        $products = Product::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm::agent.quotes.edit', compact('quote', 'customers', 'products'));
    }

    public function update(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);

        $clientId = $this->clientId($request);
        $userId   = $this->userId($request);

        $validator = Validator::make($request->all(), [
            'customer_id'              => 'required|exists:crm_customers,id',
            'date'                     => 'required|date',
            'valid_until'              => 'nullable|date',
            'status'                   => 'nullable|string|max:20',
            'currency'                 => 'nullable|string|max:3',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.id'               => 'nullable|integer|exists:crm_quote_items,id',
            'items.*.product_id'       => 'nullable|exists:crm_products,id',
            'items.*.description'      => 'required|string|max:255',
            'items.*.quantity'         => 'required|numeric|min:0.01',
            'items.*.unit'             => 'nullable|string|max:20',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'intro_text'               => ['nullable', 'string'],
            'payment_terms'            => ['nullable', 'string'],
        ]);

        $validator->after(function ($v) use ($clientId, $userId, $request) {
            // Cliente deve appartenere all’agente
            $customerId = $request->input('customer_id');
            $okCustomer = Customer::where('client_id', $clientId)
                ->where('owner_id', $userId)
                ->where('id', $customerId)
                ->exists();

            if (!$okCustomer) {
                $v->errors()->add('customer_id', 'Cliente non valido o non assegnato al tuo account.');
            }

            // Applica max_discount (e prodotti attivi / del client)
            $this->validateItemsAgainstProducts($v, $clientId, $request->input('items', []));
        });

        $data = $validator->validate();

        return DB::transaction(function () use ($data, $quote) {
            $quote->customer_id = $data['customer_id'];
            $quote->date        = $data['date'];
            $quote->valid_until = $data['valid_until'] ?? null;
            $quote->status      = $data['status'] ?? $quote->status;
            $quote->currency    = $data['currency'] ?? $quote->currency;
            $quote->notes       = $data['notes'] ?? null;

            $quote->intro_text    = $data['intro_text'] ?? null;
            $quote->payment_terms = $data['payment_terms'] ?? null;

            $quote->save();

            $this->syncItems($quote, $data['items']);
            $this->recalculateTotals($quote);

            return redirect()
                ->route('agent.crm.quotes.index')
                ->with('success', 'Preventivo aggiornato con successo.');
        });
    }

    // ---------------------------------------------------------------------
    // PDF (stessa view di admin)
    // ---------------------------------------------------------------------
    public function downloadPdf(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);

        $quote->load('customer', 'items');

        $company = [
            'name'     => Setting::get('company.name'),
            'vat'      => Setting::get('company.vat'),
            'address'  => Setting::get('company.address'),
            'city'     => Setting::get('company.city'),
            'zip'      => Setting::get('company.zip'),
            'province' => Setting::get('company.province'),
            'country'  => Setting::get('company.country', 'IT'),
            'email'    => Setting::get('company.email'),
            'phone'    => Setting::get('company.phone'),
            'bank'     => Setting::get('company.bank'),
            'iban'     => Setting::get('company.iban'),
            'bic'      => Setting::get('company.bic'),
            'pec'      => Setting::get('company.pec'),
            'sdi'      => Setting::get('company.sdi'),
        ];

        $logoDataUri = $this->getCompanyLogoDataUri();

        $pdf = Pdf::loadView('crm::quotes.pdf', [
            'quote'       => $quote,
            'company'     => $company,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Preventivo_'.$quote->number.'.pdf';

        return $pdf->download($fileName);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------
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

    protected function syncItems(Quote $quote, array $items): void
    {
        $existingIds = $quote->items()->pluck('id')->all();
        $keptIds     = [];

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
                $item = QuoteItem::where('quote_id', $quote->id)->where('id', $id)->first();
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
        $subtotal      = 0;
        $discountTotal = 0;
        $taxTotal      = 0;

        // forza un load pulito per evitare cache relation in casi strani
        $quote->loadMissing('items');

        foreach ($quote->items as $item) {
            $lineBase     = $item->quantity * $item->unit_price;
            $lineDiscount = $lineBase * ($item->discount_percent / 100);
            $lineNet      = $lineBase - $lineDiscount;
            $lineTax      = $lineNet * ($item->tax_rate / 100);

            $subtotal      += $lineBase;
            $discountTotal += $lineDiscount;
            $taxTotal      += $lineTax;
        }

        $quote->subtotal       = $subtotal;
        $quote->discount_total = $discountTotal;
        $quote->tax_total      = $taxTotal;
        $quote->total          = $subtotal - $discountTotal + $taxTotal;

        $quote->save();
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

    /**
     * Controllo: prodotti appartengono al client, sono attivi, e lo sconto non supera max_discount.
     */
    protected function validateItemsAgainstProducts(\Illuminate\Validation\Validator $validator, int $clientId, array $items): void
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->map(fn($v) => (int) $v)
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
            if (!$pid) continue;

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
                    $validator->errors()->add("items.$i.discount_percent", "Sconto massimo per \"{$p->name}\" è {$max}%.");
                }
            }
        }
    }
}
