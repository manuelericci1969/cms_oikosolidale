<?php

namespace App\Modules\Crm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    protected $table = 'crm_quotes';

    protected $fillable = [
        'client_id',
        'customer_id',
        'billing_profile_id',
        'billing_profile_snapshot',
        'number',
        'date',
        'valid_until',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'intro_text',
        'payment_terms',
        'payment_type',
        'payment_schedule',
        'bank_details',
    ];

    protected $casts = [
        'date'                        => 'date',
        'valid_until'                 => 'date',
        'sent_at'                     => 'datetime',
        'accepted_at'                 => 'datetime',
        'rejected_at'                 => 'datetime',
        'accept_click_at'             => 'datetime',
        'acceptance_code_sent_at'     => 'datetime',
        'acceptance_code_expires_at'  => 'datetime',
        'acceptance_token_expires_at' => 'datetime',
        'contract_sent_at'            => 'datetime',
        'billing_profile_snapshot'    => 'array',
        'payment_schedule'            => 'array',
        'subtotal'                    => 'float',
        'discount_total'              => 'float',
        'tax_total'                   => 'float',
        'total'                       => 'float',
    ];

    protected $appends = [
        'paid_total',
        'remaining_total',
        'payment_status',
        'payment_schedule_rows',
        'payment_schedule_total',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(QuotePayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class)->orderByDesc('id');
    }

    public function latestContract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany();
    }

    public function getPaidTotalAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingTotalAttribute(): float
    {
        $remaining = (float) $this->total - (float) $this->paid_total;
        return max(0, round($remaining, 2));
    }

    public function getPaymentStatusAttribute(): string
    {
        $paid = (float) $this->paid_total;
        $total = (float) $this->total;

        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid > 0 && $paid < $total) {
            return 'partial';
        }

        return 'paid';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'paid'    => 'Pagato',
            'partial' => 'Parzialmente pagato',
            default   => 'Non pagato',
        };
    }

    public function getPaymentScheduleRowsAttribute(): array
    {
        $schedule = is_array($this->payment_schedule) ? $this->payment_schedule : [];
        $rows = [];

        $deposit = $schedule['deposit'] ?? [];
        if (($deposit['enabled'] ?? false) && (float) ($deposit['amount'] ?? 0) > 0) {
            $rows[] = [
                'type' => 'deposit',
                'label' => $deposit['label'] ?: 'Acconto alla firma',
                'due_date' => $deposit['due_date'] ?? null,
                'due_date_label' => !empty($deposit['due_date'])
                    ? Carbon::parse($deposit['due_date'])->format('d/m/Y')
                    : 'Alla firma',
                'amount' => round((float) $deposit['amount'], 2),
            ];
        }

        foreach (($schedule['installments'] ?? []) as $index => $installment) {
            if ((float) ($installment['amount'] ?? 0) <= 0) {
                continue;
            }

            $dueDate = $installment['due_date'] ?? null;

            $rows[] = [
                'type' => 'installment',
                'label' => $installment['label'] ?: 'Rata ' . ($index + 1),
                'due_date' => $dueDate,
                'due_date_label' => $dueDate ? Carbon::parse($dueDate)->format('d/m/Y') : 'Da concordare',
                'amount' => round((float) $installment['amount'], 2),
            ];
        }

        return $rows;
    }

    public function getPaymentScheduleTotalAttribute(): float
    {
        return round(collect($this->payment_schedule_rows)->sum('amount'), 2);
    }
}
