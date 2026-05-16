<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Appointment;
use App\Modules\Crm\Models\ChatbotFaq;
use App\Modules\Crm\Models\Product;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AiGatewayController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function ensureAiKey(Request $request): void
    {
        $expectedKey = (string) config('services.ai_gateway.key', '');
        $providedKey = (string) $request->header('X-AI-KEY', '');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401));
        }
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'q'     => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);

        $query = Product::query()
            ->where('client_id', $clientId)
            ->where('is_active', true);

        if ($q !== '') {
            $tokens = array_values(array_filter(
                preg_split('/\s+/', mb_strtolower($q)) ?: [],
                fn ($token) => mb_strlen(trim($token)) >= 2
            ));

            $query->where(function ($builder) use ($q, $tokens) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");

                foreach ($tokens as $token) {
                    $builder->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('sku', 'like', "%{$token}%")
                        ->orWhere('description', 'like', "%{$token}%");
                }
            });
        }

        $products = $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                return [
                    'id'           => $product->id,
                    'name'         => $product->name,
                    'sku'          => $product->sku,
                    'unit'         => $product->unit,
                    'price'        => $product->price,
                    'tax_rate'     => $product->tax_rate,
                    'max_discount' => $product->max_discount,
                    'description'  => $product->description,
                    'website_url'  => $product->website_url,
                    'is_promo'     => (bool) $product->is_promo,
                    'promo_expires_at' => optional($product->promo_expires_at)?->format('Y-m-d'),
                ];
            })
            ->values();

        return response()->json([
            'ok'    => true,
            'type'  => 'products',
            'query' => $q,
            'count' => $products->count(),
            'items' => $products,
        ]);
    }


    public function searchFaqs(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'q'      => 'nullable|string|max:255',
            'intent' => 'nullable|string|max:100',
            'limit'  => 'nullable|integer|min:1|max:20',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $intent = trim((string) ($validated['intent'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);

        $query = ChatbotFaq::query()
            ->with('product')
            ->where('client_id', $clientId)
            ->where('is_active', true);

        if ($intent !== '') {
            $query->where('intent', $intent);
        }

        if ($q !== '') {
            $tokens = array_values(array_filter(
                preg_split('/\s+/', mb_strtolower($q)) ?: [],
                fn ($token) => mb_strlen(trim($token)) >= 2
            ));

            $query->where(function ($builder) use ($q, $tokens) {
                $builder->where('question_pattern', 'like', "%{$q}%")
                    ->orWhere('keywords', 'like', "%{$q}%")
                    ->orWhere('answer', 'like', "%{$q}%");

                foreach ($tokens as $token) {
                    $builder->orWhere('question_pattern', 'like', "%{$token}%")
                        ->orWhere('keywords', 'like', "%{$token}%")
                        ->orWhere('answer', 'like', "%{$token}%");
                }
            });
        }

        $faqs = $query
            ->orderBy('priority')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (ChatbotFaq $faq) {
                return [
                    'id'               => $faq->id,
                    'question_pattern' => $faq->question_pattern,
                    'keywords'         => $faq->keywordsArray(),
                    'intent'           => $faq->intent,
                    'answer'           => $faq->answer,
                    'priority'         => $faq->priority,
                    'times_used'       => $faq->times_used,
                    'product'          => $faq->product ? [
                        'id'          => $faq->product->id,
                        'name'        => $faq->product->name,
                        'sku'         => $faq->product->sku,
                        'price'       => $faq->product->price,
                        'website_url' => $faq->product->website_url,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'ok'     => true,
            'type'   => 'faqs',
            'query'  => $q,
            'intent' => $intent !== '' ? $intent : null,
            'count'  => $faqs->count(),
            'items'  => $faqs,
        ]);
    }

    public function appointmentAvailability(Request $request): JsonResponse
    {

        $this->ensureAiKey($request);

        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'days'       => 'nullable|integer|min:1|max:31',
        ]);

        $days = (int) ($validated['days'] ?? 14);

        $startDate = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfDay();

        $endDate = (clone $startDate)->addDays($days - 1)->endOfDay();

        $appointments = Appointment::query()
            ->where('client_id', $clientId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_at', '<', $endDate)
                    ->where(function ($q2) use ($startDate) {
                        $q2->whereNull('end_at')
                            ->orWhere('end_at', '>', $startDate);
                    });
            })
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->get(['id', 'title', 'status', 'start_at', 'end_at']);

        $candidateSlots = [
            ['start' => '10:00', 'end' => '10:30'],
            ['start' => '10:30', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '11:30'],
            ['start' => '11:30', 'end' => '12:00'],
            ['start' => '12:00', 'end' => '12:30'],
        ];

        $allowedWeekdays = [1, 2, 3, 4]; // lunedì-giovedì
        $items = [];

        foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
            if (!in_array($date->dayOfWeekIso, $allowedWeekdays, true)) {
                continue;
            }

            $availableSlots = [];

            foreach ($candidateSlots as $slot) {
                $slotStart = $date->copy()->setTimeFromTimeString($slot['start']);
                $slotEnd   = $date->copy()->setTimeFromTimeString($slot['end']);

                $isBusy = $appointments->contains(function (Appointment $appointment) use ($slotStart, $slotEnd) {
                    if (!$appointment->start_at) {
                        return false;
                    }

                    $apptStart = $appointment->start_at->copy();
                    $apptEnd   = $appointment->end_at
                        ? $appointment->end_at->copy()
                        : $apptStart->copy()->addMinutes(30);

                    return $apptStart < $slotEnd && $apptEnd > $slotStart;
                });

                if (!$isBusy) {
                    $availableSlots[] = [
                        'start' => $slot['start'],
                        'end'   => $slot['end'],
                    ];
                }
            }

            $items[] = [
                'date'            => $date->format('Y-m-d'),
                'weekday'         => $date->locale('it')->isoFormat('dddd'),
                'available'       => !empty($availableSlots),
                'available_slots' => $availableSlots,
            ];
        }

        return response()->json([
            'ok'         => true,
            'type'       => 'appointment_availability',
            'start_date' => $startDate->format('Y-m-d'),
            'days'       => $days,
            'count'      => count($items),
            'items'      => $items,
        ]);
    }

    public function requestAppointment(Request $request): JsonResponse
    {

        $this->ensureAiKey($request);

        $clientId = $this->clientId($request);

        $validated = $request->validate([
            'name'        => 'required|string|max:190',
            'email'       => 'nullable|email|max:190',
            'phone'       => 'nullable|string|max:50',
            'company'     => 'nullable|string|max:190',
            'date'        => 'required|date_format:Y-m-d',
            'start_time'  => 'required|date_format:H:i',
            'notes'       => 'nullable|string|max:5000',
            'title'       => 'nullable|string|max:190',
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $startTime = $validated['start_time'];

        $allowedWeekdays = [1, 2, 3, 4]; // lun-giov
        if (!in_array($date->dayOfWeekIso, $allowedWeekdays, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'Gli appuntamenti sono disponibili solo dal lunedì al giovedì.',
            ], 422);
        }

        $allowedSlots = [
            '10:00',
            '10:30',
            '11:00',
            '11:30',
            '12:00',
        ];

        if (!in_array($startTime, $allowedSlots, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'Orario non disponibile. Gli slot validi sono tra le 10:00 e le 12:30.',
            ], 422);
        }

        $startAt = $date->copy()->setTimeFromTimeString($startTime);
        $endAt   = $startAt->copy()->addMinutes(30);

        $isBusy = Appointment::query()
            ->where('client_id', $clientId)
            ->where(function ($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                    ->where(function ($q2) use ($startAt) {
                        $q2->whereNull('end_at')
                            ->orWhere('end_at', '>', $startAt);
                    });
            })
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->exists();

        if ($isBusy) {
            return response()->json([
                'ok' => false,
                'message' => 'Lo slot selezionato non è più disponibile.',
            ], 409);
        }

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = 'Appuntamento sito web';
        }

        $descriptionParts = [];
        $descriptionParts[] = 'Richiesta appuntamento da AI / sito web';
        $descriptionParts[] = 'Nome: ' . $validated['name'];

        if (!empty($validated['email'])) {
            $descriptionParts[] = 'Email: ' . $validated['email'];
        }

        if (!empty($validated['phone'])) {
            $descriptionParts[] = 'Telefono: ' . $validated['phone'];
        }

        if (!empty($validated['company'])) {
            $descriptionParts[] = 'Azienda: ' . $validated['company'];
        }

        if (!empty($validated['notes'])) {
            $descriptionParts[] = 'Note: ' . $validated['notes'];
        }

        $appointment = Appointment::create([
            'client_id'   => $clientId,
            'user_id'     => null,
            'lead_id'     => null,
            'customer_id' => null,
            'title'       => $title,
            'description' => implode("\n", $descriptionParts),
            'location'    => 'Online / Da confermare',
            'type'        => 'meeting',
            'status'      => 'planned',
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'all_day'     => false,
            'created_by'  => null,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Appuntamento richiesto correttamente.',
            'item' => [
                'id'    => $appointment->id,
                'title' => $appointment->title,
                'date'  => $startAt->format('Y-m-d'),
                'start' => $startAt->format('H:i'),
                'end'   => $endAt->format('H:i'),
                'status'=> $appointment->status,
            ],
        ], 201);
    }
}
