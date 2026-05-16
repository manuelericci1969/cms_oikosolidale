<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallQueue;
use App\Modules\Crm\Models\EmailListContact;
use Illuminate\Support\Facades\DB;

class CallQueueBuilderService
{
    public function buildFromEmailListContacts(CallCampaign $campaign): int
    {
        $listId = (int) data_get($campaign->filters, 'list_id', 0);

        if ($listId <= 0) {
            return 0;
        }

        $maxAttempts = max(1, (int) data_get($campaign->settings, 'max_attempts', 3));
        $now = now();

        $contacts = EmailListContact::query()
            ->where('list_id', $listId)
            ->orderBy('id')
            ->get();

        if ($contacts->isEmpty()) {
            return 0;
        }

        $existingKeys = $campaign->queueItems()
            ->get(['email', 'phone'])
            ->map(function (CallQueue $item) {
                return $this->makeUniqKey(
                    $this->normalizeEmail($item->email),
                    $this->normalizePhone($item->phone)
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingMap = array_fill_keys($existingKeys, true);

        $rows = [];

        foreach ($contacts as $contact) {
            $phone = $this->normalizePhone($contact->phone ?: $contact->whatsapp);

            if (!$this->isUsablePhone($phone)) {
                continue;
            }

            $email = $this->normalizeEmail($contact->email);
            $uniqKey = $this->makeUniqKey($email, $phone);

            if (!$uniqKey || isset($existingMap[$uniqKey])) {
                continue;
            }

            $rows[] = [
                'client_id' => $campaign->client_id,
                'owner_id' => $campaign->owner_id,
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->contact_id,
                'contact_type' => $contact->contact_type ?: CallQueue::SOURCE_EMAIL_LIST_CONTACT,
                'contact_name' => $this->cleanString($contact->name),
                'email' => $email,
                'phone' => $phone,
                'source_type' => CallQueue::SOURCE_EMAIL_LIST_CONTACT,
                'source_id' => $contact->id,
                'status' => CallQueue::STATUS_PENDING,
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
                'last_attempt_at' => null,
                'next_attempt_at' => null,
                'completed_at' => null,
                'last_outcome' => null,
                'last_outcome_note' => null,
                'payload' => json_encode([
                    'list_id' => $listId,
                    'campaign_id' => $campaign->id,
                    'email_list_contact_id' => $contact->id,
                    'segment' => $contact->segment,
                    'city' => $contact->city,
                    'province' => $contact->province,
                    'business_type' => $contact->business_type,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'metadata' => json_encode([
                    'imported_from' => 'email_list_contacts',
                    'imported_at' => $now->toDateTimeString(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $existingMap[$uniqKey] = true;
        }

        if (empty($rows)) {
            return 0;
        }

        DB::table('crm_call_queue')->insert($rows);

        return count($rows);
    }

    protected function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email !== '' ? mb_strtolower($email) : null;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (!$phone) {
            return null;
        }

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (!str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D/', '', $phone);

            if (!$digits) {
                return null;
            }

            if (strlen($digits) === 9 || strlen($digits) === 10) {
                $phone = '+39' . $digits;
            } else {
                $phone = '+' . $digits;
            }
        }

        return $phone;
    }

    protected function isUsablePhone(?string $phone): bool
    {
        if (!$phone) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }

    protected function makeUniqKey(?string $email, ?string $phone): ?string
    {
        if ($phone) {
            return 'p:' . $phone;
        }

        if ($email) {
            return 'e:' . $email;
        }

        return null;
    }

    protected function cleanString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
