<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $table = 'crm_leads';

    protected $fillable = [
        'client_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'source',
        'status',
        'internal_notes',
        'gdpr_consense',
        'marketing_consense',
        'owner_id',
        'last_contact_at',
        'next_action_at',
        'closed_at',
        'closed_reason',
        'how_found',
        'how_found_other',
    ];

    public const HOW_FOUND_OPTIONS = [
        'web'         => 'Web',
        'social'      => 'Social',
        'passaparola' => 'Passaparola',
        'altro'       => 'Altro',
    ];

    protected $casts = [
        'gdpr_consense'      => 'boolean',
        'marketing_consense' => 'boolean',
        'last_contact_at'    => 'datetime',
        'next_action_at'     => 'datetime',
        'closed_at'          => 'datetime',
    ];

    public const STATUS_OPTIONS = [
        'new'       => 'Nuovo',
        'contacted' => 'Contattato',
        'qualified' => 'Qualificato',
        'proposal'  => 'Preventivo inviato',
        'won'       => 'Convertito',
        'lost'      => 'Perso',
        'archived'  => 'Archiviato',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('contacted_at');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'lead_id')->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return static::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'new'       => 'bg-primary-subtle text-primary',
            'contacted' => 'bg-info-subtle text-info',
            'qualified' => 'bg-info-subtle text-info',
            'proposal'  => 'bg-warning-subtle text-warning',
            'won'       => 'bg-success',
            'lost'      => 'bg-danger-subtle text-danger',
            'archived'  => 'bg-secondary',
            default     => 'bg-light text-muted',
        };
    }

    public function getSourceLabelAttribute(): ?string
    {
        return match ($this->source) {
            'contact_form'        => 'Form contatti',
            'contact_form_social' => 'Form contatti social',
            'manual'              => 'Inserimento manuale',
            'chatbot_ai'          => 'Chatbot AI',
            default               => $this->source,
        };
    }

    public function getHowFoundLabelAttribute(): ?string
    {
        if (!$this->how_found) {
            return null;
        }

        return static::HOW_FOUND_OPTIONS[$this->how_found] ?? $this->how_found;
    }

    public function getHowFoundFullLabelAttribute(): ?string
    {
        if (!$this->how_found) {
            return null;
        }

        $label = $this->how_found_label;

        if ($this->how_found === 'altro' && !empty($this->how_found_other)) {
            $label .= ' - ' . $this->how_found_other;
        }

        return $label;
    }

    public function getMessageExcerptAttribute(): ?string
    {
        if (!$this->message) {
            return null;
        }

        return mb_strimwidth(trim($this->message), 0, 110, '…');
    }

    public function chatbotConversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class, 'customer_id');
    }
}
