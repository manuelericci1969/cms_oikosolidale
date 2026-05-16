<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallQueue;
use App\Modules\Crm\Models\EmailListContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CallQueueBuilderService
{
    public function buildFromEmailListContacts(CallCampaign $campaign): int
    {
        $filters = $campaign->filters ?? [];

        $listId = data_get($filters, 'list_id');

        if (!$listId) {
            throw new \InvalidArgumentException('Filtro list_id obbligatorio per buildFromEmailListContacts.');
        }

        $query = EmailListContact::query()
            ->where('list_id', $listId)
            ->where('marketing_consense', true)
            ->whereNull('unsubscribed_at')
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if (!empty($filters['business_type'])) {
            $query->where('business_type', $filters['business_type']);
        }

        if (!empty($filters['province'])) {
            $query->where('province', strtoupper($filters['province']));
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['segment'])) {
            $query->where('segment', $filters['segment']);
        }

        if (!empty($filters['contact_role'])) {
            $query->where('contact_role', $filters['contact_role']);
        }

        if (!empty($filters['commercial_potential'])) {
            $query->where('commercial_potential', $filters['commercial_potential']);
        }

        $contacts = $query->get();

        $inserted = 0;

        DB::transaction(function () use ($contacts, $campaign, &$inserted) {
            foreach ($contacts as $contact) {
                $exists = CallQueue::query()
                    ->where('campaign_id', $campaign->id)
                    ->where('source_type', CallQueue::SOURCE_EMAIL_LIST_CONTACT)
                    ->where('source_id', $contact->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $normalizedPhone = $this->normalizePhone($contact->phone);

                if (!$this->isValidE164Phone($normalizedPhone)) {
                    continue;
                }

                CallQueue::create([
                    'client_id'         => $campaign->client_id,
                    'campaign_id'       => $campaign->id,
                    'owner_id'          => $campaign->owner_id,
                    'source_type'       => CallQueue::SOURCE_EMAIL_LIST_CONTACT,
                    'source_id'         => $contact->id,
                    'contact_name'      => $contact->name,
                    'phone'             => $normalizedPhone,
                    'email'             => $contact->email,
                    'status'            => CallQueue::STATUS_PENDING,
                    'attempts'          => 0,
                    'max_attempts'      => data_get($campaign->settings, 'max_attempts', 3),
                    'scheduled_at'      => Carbon::now(),
                    'payload'           => [
                        'list_id'               => $contact->list_id,
                        'segment'               => $contact->segment,
                        'city'                  => $contact->city,
                        'province'              => $contact->province,
                        'region'                => $contact->region,
                        'country'               => $contact->country,
                        'business_type'         => $contact->business_type,
                        'contact_role'          => $contact->contact_role,
                        'source_type_original'  => $contact->source_type,
                        'source_url'            => $contact->source_url,
                        'commercial_potential'  => $contact->commercial_potential,
                        'site_rating'           => $contact->site_rating,
                        'seo_score'             => $contact->seo_score,
                        'notes'                 => $contact->notes,
                    ],
                ]);

                $inserted++;
            }
        });

        return $inserted;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = trim($phone);

        // Mantiene il + se presente, rimuove tutto il resto
        $phone = preg_replace('/(?!^\+)[^\d]/', '', $phone);

        // Caso comune numeri italiani senza prefisso
        if ($phone && !str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '00')) {
                $phone = '+' . substr($phone, 2);
            } elseif (str_starts_with($phone, '3') || str_starts_with($phone, '0')) {
                $phone = '+39' . $phone;
            }
        }

        return $phone;
    }

    protected function isValidE164Phone(?string $phone): bool
    {
        if (!$phone) {
            return false;
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $phone);
    }
}
