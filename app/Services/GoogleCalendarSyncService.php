<?php

namespace App\Services;

use App\Models\CrmAppointmentGoogleEvent;
use App\Models\GoogleCalendarAccount;
use App\Models\Setting;
use App\Modules\Crm\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarSyncService
{
    /**
     * Soppressione globale SOLO per evitare loop Observer <-> Sync.
     * La soppressione va controllata nell'Observer (come hai già fatto).
     */
    private static int $suppressLevel = 0;

    public static function isSuppressed(): bool
    {
        return self::$suppressLevel > 0;
    }

    public static function suppress(callable $fn)
    {
        self::$suppressLevel++;
        try {
            return $fn();
        } finally {
            self::$suppressLevel = max(0, self::$suppressLevel - 1);
        }
    }

    // =========================================================
    // SYNC MANUALE (pull/push)
    // =========================================================

    public function syncAll(): array
    {
        return self::suppress(function () {

            if (!$this->isGoogleEnabled()) {
                throw new \RuntimeException("Google Calendar non abilitato (setting calendar.google.enabled).");
            }

            $acc = $this->resolveAccountForSync();

            $calendarId = $this->normalizeCalendarId(
                $acc->calendar_id ?: (string) Setting::get('calendar.google.calendar_id', Setting::get('calendar.google_calendar_id', 'primary'))
            );

            $direction  = (string) Setting::get('calendar.sync.direction', 'two_way');

            $pastDays   = (int) Setting::get('calendar.sync.past_days', 30);
            $futureDays = (int) Setting::get('calendar.sync.future_days', 180);

            $tz = $this->getTz();

            $from = Carbon::now($tz)->subDays($pastDays)->startOfDay();
            $to   = Carbon::now($tz)->addDays($futureDays)->endOfDay();

            $this->assertCalendarAccessible($acc, $calendarId);

            $result = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'errors'  => [],
            ];

            if (in_array($direction, ['two_way', 'pull_only'], true)) {
                $this->pullGoogleToCrm($acc, $calendarId, $from, $to, $tz, $result);
            }

            if (in_array($direction, ['two_way', 'push_only'], true)) {
                $this->pushCrmToGoogle($acc, $calendarId, $from, $to, $tz, $result);
            }

            try {
                $acc->last_synced_at = now();
                $acc->save();
            } catch (\Throwable $e) {
                // non bloccare
            }

            return $result;
        });
    }

    // =========================================================
    // OBSERVER API (CRM -> GOOGLE)
    // =========================================================

    public function upsertAppointmentToGoogle(GoogleCalendarAccount $acc, Appointment $appt): array
    {
        if (!$this->isGoogleEnabled()) return ['ok' => false, 'reason' => 'disabled'];
        if (!(int) $acc->enabled) return ['ok' => false, 'reason' => 'account_disabled'];

        $calendarId = $this->normalizeCalendarId(
            $acc->calendar_id ?: (string) Setting::get('calendar.google.calendar_id', Setting::get('calendar.google_calendar_id', 'primary'))
        );

        $this->assertCalendarAccessible($acc, $calendarId);

        $map = CrmAppointmentGoogleEvent::query()
            ->where('google_calendar_account_id', $acc->id)
            ->where('calendar_id', $calendarId)
            ->where('appointment_id', $appt->id)
            ->first();

        if ($map && $map->last_synced_at && $appt->updated_at && $appt->updated_at->lte($map->last_synced_at)) {
            return ['ok' => true, 'skipped' => true];
        }

        $g = $this->toGoogleEventPayload($appt, $this->getTz());

        if (!$map) {
            $found = $this->findGoogleEventByCrmId($acc, $calendarId, (int) $appt->id);
            if ($found && !empty($found['id'])) {
                $map = CrmAppointmentGoogleEvent::updateOrCreate([
                    'google_calendar_account_id' => $acc->id,
                    'calendar_id'                => $calendarId,
                    'event_id'                   => (string) $found['id'],
                ], [
                    'appointment_id'    => (int) $appt->id,
                    'ical_uid'          => $found['iCalUID'] ?? null,
                    'etag'              => $found['etag'] ?? null,
                    'google_updated_at' => isset($found['updated']) ? Carbon::parse($found['updated']) : null,
                    'last_synced_at'    => now(),
                ]);
            }
        }

        if ($map && $map->event_id) {
            try {
                $updated = $this->googlePatch(
                    $acc,
                    "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events/{$this->enc($map->event_id)}",
                    $g
                );

                $map->fill([
                    'etag'              => $updated['etag'] ?? $map->etag,
                    'ical_uid'          => $updated['iCalUID'] ?? $map->ical_uid,
                    'google_updated_at' => isset($updated['updated']) ? Carbon::parse($updated['updated']) : $map->google_updated_at,
                    'last_synced_at'    => now(),
                ])->save();

                return ['ok' => true, 'updated' => true, 'event_id' => $map->event_id];

            } catch (\Throwable $e) {
                if ($this->isNotFound($e)) {
                    try { $map->delete(); } catch (\Throwable $x) {}
                    $map = null;
                } else {
                    throw $e;
                }
            }
        }

        $created = $this->googlePost(
            $acc,
            "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events",
            $g
        );

        $eventId = (string) ($created['id'] ?? '');
        if ($eventId === '') throw new \RuntimeException('Evento Google creato ma manca id nella risposta.');

        CrmAppointmentGoogleEvent::updateOrCreate([
            'google_calendar_account_id' => $acc->id,
            'calendar_id'                => $calendarId,
            'appointment_id'             => (int) $appt->id,
        ], [
            'event_id'          => $eventId,
            'ical_uid'          => $created['iCalUID'] ?? null,
            'etag'              => $created['etag'] ?? null,
            'google_updated_at' => isset($created['updated']) ? Carbon::parse($created['updated']) : null,
            'last_synced_at'    => now(),
        ]);

        return ['ok' => true, 'created' => true, 'event_id' => $eventId];
    }

    public function deleteAppointmentFromGoogle(GoogleCalendarAccount $acc, Appointment $appt): array
    {
        if (!$this->isGoogleEnabled()) return ['ok' => false, 'reason' => 'disabled'];
        if (!(int) $acc->enabled) return ['ok' => false, 'reason' => 'account_disabled'];

        $calendarId = $this->normalizeCalendarId(
            $acc->calendar_id ?: (string) Setting::get('calendar.google.calendar_id', Setting::get('calendar.google_calendar_id', 'primary'))
        );

        $map = CrmAppointmentGoogleEvent::query()
            ->where('google_calendar_account_id', $acc->id)
            ->where('calendar_id', $calendarId)
            ->where('appointment_id', $appt->id)
            ->first();

        $eventId = $map?->event_id;

        if (!$eventId) {
            $found = $this->findGoogleEventByCrmId($acc, $calendarId, (int) $appt->id);
            $eventId = $found['id'] ?? null;
        }

        if (!$eventId) {
            if ($map) $map->delete();
            return ['ok' => true, 'skipped' => true, 'reason' => 'no_event'];
        }

        try {
            $this->googleDelete(
                $acc,
                "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events/{$this->enc((string)$eventId)}"
            );
        } catch (\Throwable $e) {
            if (!$this->isNotFound($e)) throw $e;
        }

        if ($map) $map->delete();

        return ['ok' => true, 'deleted' => true, 'event_id' => (string)$eventId];
    }

    // =========================================================
    // GOOGLE -> CRM (pull)
    // =========================================================

    private function pullGoogleToCrm(
        GoogleCalendarAccount $acc,
        string $calendarId,
        Carbon $from,
        Carbon $to,
        string $tz,
        array &$result
    ): void {
        $pageToken = null;
        $includeDeleted = (bool) Setting::get('calendar.sync.include_deleted', false);

        do {
            $params = [
                'timeMin'      => $from->copy()->utc()->toRfc3339String(),
                'timeMax'      => $to->copy()->utc()->toRfc3339String(),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
                'showDeleted'  => $includeDeleted,
                'maxResults'   => 2500,
            ];
            if ($pageToken) $params['pageToken'] = $pageToken;

            $json = $this->googleGet(
                $acc,
                "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events",
                $params
            );

            foreach (($json['items'] ?? []) as $ev) {
                try {
                    $this->importOneGoogleEvent($acc, $calendarId, $ev, $tz, $result);
                } catch (\Throwable $e) {
                    $result['errors'][] = "PULL: " . $e->getMessage();
                    Log::warning('[GoogleSync] PULL error', [
                        'err'   => $e->getMessage(),
                        'event' => $ev['id'] ?? null,
                    ]);
                }
            }

            $pageToken = $json['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    private function importOneGoogleEvent(
        GoogleCalendarAccount $acc,
        string $calendarId,
        array $ev,
        string $tz,
        array &$result
    ): void {
        $status  = $ev['status'] ?? 'confirmed';
        $eventId = $ev['id'] ?? null;
        if (!$eventId) { $result['skipped']++; return; }

        $summary = (string)($ev['summary'] ?? '');

        $crmIdFromExt = $ev['extendedProperties']['private']['crm_appointment_id'] ?? null;
        if (!$crmIdFromExt && str_starts_with($summary, '[CRM]')) {
            $result['skipped']++;
            return;
        }

        $map = CrmAppointmentGoogleEvent::query()
            ->where('google_calendar_account_id', $acc->id)
            ->where('calendar_id', $calendarId)
            ->where('event_id', (string)$eventId)
            ->first();

        if (!$map && $crmIdFromExt) {
            $map = CrmAppointmentGoogleEvent::query()
                ->where('google_calendar_account_id', $acc->id)
                ->where('calendar_id', $calendarId)
                ->where('appointment_id', (int)$crmIdFromExt)
                ->first();
        }

        $icalUid = $ev['iCalUID'] ?? null;

        $isRecurringInstance = !empty($ev['recurringEventId']);
        if (!$map && $icalUid && !$isRecurringInstance) {
            $map = CrmAppointmentGoogleEvent::query()
                ->where('google_calendar_account_id', $acc->id)
                ->where('calendar_id', $calendarId)
                ->where('ical_uid', (string)$icalUid)
                ->first();
        }

        if ($status === 'cancelled') {
            if ($map) {
                Appointment::query()->where('id', $map->appointment_id)->delete();
                $map->delete();
                $result['deleted']++;
            } else {
                $result['skipped']++;
            }
            return;
        }

        [$startAt, $endAt, $allDay] = $this->parseGoogleDatesForCrm($ev, $tz);
        if (!$startAt || !$endAt) { $result['skipped']++; return; }

        $payload = [
            'title'       => $summary ?: '(senza titolo)',
            'description' => (string)($ev['description'] ?? ''),
            'location'    => (string)($ev['location'] ?? ''),
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'all_day'     => $allDay ? 1 : 0,
            'status'      => 'planned',
            'type'        => 'appointment',
        ];

        $defaultClientId  = (int) Setting::get('calendar.google.default_client_id', 1);
        $defaultUserId = (int) ($acc->user_id ?: Setting::get('calendar.google.default_user_id', 1));
        $defaultCreatedBy = (int) Setting::get('calendar.google.default_created_by', $defaultUserId);

        DB::transaction(function () use (
            $map, $payload, $defaultClientId, $defaultUserId, $defaultCreatedBy,
            $calendarId, $eventId, $icalUid, $ev, $acc, &$result
        ) {
            if ($map) {
                $exists = Appointment::query()->whereKey($map->appointment_id)->exists();
                if (!$exists) $map = null;
            }

            if ($map) {
                Appointment::query()->where('id', $map->appointment_id)->update($payload);

                $map->fill([
                    'etag'              => $ev['etag'] ?? $map->etag,
                    'ical_uid'          => $icalUid ?? $map->ical_uid,
                    'google_updated_at' => isset($ev['updated']) ? Carbon::parse($ev['updated']) : $map->google_updated_at,
                    'last_synced_at'    => now(),
                ])->save();

                $result['updated']++;
                return;
            }

            $appt = Appointment::create(array_merge($payload, [
                'client_id'  => $defaultClientId,
                'user_id'    => $defaultUserId,
                'created_by' => $defaultCreatedBy,
            ]));

            CrmAppointmentGoogleEvent::updateOrCreate([
                'google_calendar_account_id' => $acc->id,
                'calendar_id'                => $calendarId,
                'event_id'                   => (string)$eventId,
            ], [
                'appointment_id'    => $appt->id,
                'ical_uid'          => $icalUid,
                'etag'              => $ev['etag'] ?? null,
                'google_updated_at' => isset($ev['updated']) ? Carbon::parse($ev['updated']) : null,
                'last_synced_at'    => now(),
            ]);

            $result['created']++;
        });
    }

    // =========================================================
    // CRM -> GOOGLE (push bulk)
    // =========================================================

    private function pushCrmToGoogle(
        GoogleCalendarAccount $acc,
        string $calendarId,
        Carbon $from,
        Carbon $to,
        string $tz,
        array &$result
    ): void {
        Appointment::query()
            ->where('user_id', (int) $acc->user_id)
            ->where(function ($q) use ($from, $to) {
                $q->where('start_at', '<', $to)
                    ->where(function ($q2) use ($from) {
                        $q2->whereNull('end_at')->orWhere('end_at', '>', $from);
                    });
            })
            ->orderBy('start_at')
            ->chunk(200, function ($appts) use ($acc, &$result) {
                foreach ($appts as $appt) {
                    try {
                        $r = $this->upsertAppointmentToGoogle($acc, $appt);

                        if (!empty($r['created'])) $result['created']++;
                        elseif (!empty($r['updated'])) $result['updated']++;
                        elseif (!empty($r['skipped'])) $result['skipped']++;

                    } catch (\Throwable $e) {
                        $result['errors'][] = "PUSH(appt {$appt->id}): " . $e->getMessage();
                        Log::warning('[GoogleSync] PUSH error', [
                            'err'  => $e->getMessage(),
                            'appt' => $appt->id,
                        ]);
                    }
                }
            });
    }

    // =========================================================
    // DEDUPE - GOOGLE
    // =========================================================

    public function dedupeCrmEventsOnGoogle(GoogleCalendarAccount $acc, Carbon $from, Carbon $to): array
    {
        $calendarId = $this->normalizeCalendarId(
            $acc->calendar_id ?: (string) Setting::get('calendar.google.calendar_id', Setting::get('calendar.google_calendar_id', 'primary'))
        );

        $this->assertCalendarAccessible($acc, $calendarId);

        $deleted = 0;
        $kept = 0;
        $groups = 0;

        $pageToken = null;
        $events = [];

        do {
            $params = [
                'timeMin'      => $from->copy()->utc()->toRfc3339String(),
                'timeMax'      => $to->copy()->utc()->toRfc3339String(),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
                'maxResults'   => 2500,
                'showDeleted'  => false,
                'privateExtendedProperty' => 'source=crm',
            ];

            if ($pageToken) $params['pageToken'] = $pageToken;

            $json = $this->googleGet(
                $acc,
                "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events",
                $params
            );

            foreach (($json['items'] ?? []) as $ev) {
                $events[] = $ev;
            }

            $pageToken = $json['nextPageToken'] ?? null;
        } while ($pageToken);

        $byCrm = [];
        foreach ($events as $ev) {
            $crmId = $ev['extendedProperties']['private']['crm_appointment_id'] ?? null;
            if (!$crmId) continue;
            $byCrm[(string)$crmId][] = $ev;
        }

        foreach ($byCrm as $crmId => $list) {
            if (count($list) <= 1) continue;

            $groups++;

            $mappedEventId = CrmAppointmentGoogleEvent::query()
                ->where('google_calendar_account_id', $acc->id)
                ->where('calendar_id', $calendarId)
                ->where('appointment_id', (int)$crmId)
                ->value('event_id');

            $keep = null;

            if ($mappedEventId) {
                foreach ($list as $ev) {
                    if (($ev['id'] ?? null) === $mappedEventId) {
                        $keep = $ev;
                        break;
                    }
                }
            }

            if (!$keep) {
                usort($list, function ($a, $b) {
                    $ua = isset($a['updated']) ? strtotime($a['updated']) : 0;
                    $ub = isset($b['updated']) ? strtotime($b['updated']) : 0;
                    return $ub <=> $ua;
                });
                $keep = $list[0];
            }

            $kept++;

            foreach ($list as $ev) {
                $eid = $ev['id'] ?? null;
                if (!$eid) continue;
                if (($keep['id'] ?? null) === $eid) continue;

                try {
                    $this->googleDelete(
                        $acc,
                        "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events/{$this->enc((string)$eid)}"
                    );
                } catch (\Throwable $e) {
                    if (!$this->isNotFound($e)) throw $e;
                }

                $deleted++;
            }

            $keepId = (string)($keep['id'] ?? '');
            if ($keepId !== '') {
                CrmAppointmentGoogleEvent::updateOrCreate([
                    'google_calendar_account_id' => $acc->id,
                    'calendar_id'                => $calendarId,
                    'appointment_id'             => (int)$crmId,
                ], [
                    'event_id'          => $keepId,
                    'ical_uid'          => $keep['iCalUID'] ?? null,
                    'etag'              => $keep['etag'] ?? null,
                    'google_updated_at' => isset($keep['updated']) ? Carbon::parse($keep['updated']) : null,
                    'last_synced_at'    => now(),
                ]);
            }
        }

        return [
            'ok'      => true,
            'groups'  => $groups,
            'kept'    => $kept,
            'deleted' => $deleted,
        ];
    }

    // =========================================================
    // DEDUPE - DB (mapping duplicati)
    // =========================================================

    public function dedupeMappingsInDb(?int $accountId = null, ?string $calendarId = null): array
    {
        $q = CrmAppointmentGoogleEvent::query();

        if ($accountId) $q->where('google_calendar_account_id', $accountId);
        if ($calendarId) $q->where('calendar_id', $calendarId);

        $rows = $q->get(['id', 'google_calendar_account_id', 'calendar_id', 'event_id', 'appointment_id']);

        $deleted = 0;

        $byAppt = [];
        foreach ($rows as $r) {
            $k = $r->google_calendar_account_id.'|'.$r->calendar_id.'|appt|'.$r->appointment_id;
            $byAppt[$k][] = $r;
        }

        foreach ($byAppt as $list) {
            if (count($list) <= 1) continue;

            usort($list, fn($a,$b) => $b->id <=> $a->id);
            array_shift($list); // keep max id

            foreach ($list as $r) {
                CrmAppointmentGoogleEvent::query()->whereKey($r->id)->delete();
                $deleted++;
            }
        }

        $rows2 = $q->get(['id', 'google_calendar_account_id', 'calendar_id', 'event_id', 'appointment_id']);
        $byEvent = [];
        foreach ($rows2 as $r) {
            if (!$r->event_id) continue;
            $k = $r->google_calendar_account_id.'|'.$r->calendar_id.'|ev|'.$r->event_id;
            $byEvent[$k][] = $r;
        }

        foreach ($byEvent as $list) {
            if (count($list) <= 1) continue;

            usort($list, fn($a,$b) => $b->id <=> $a->id);
            array_shift($list); // keep max id

            foreach ($list as $r) {
                CrmAppointmentGoogleEvent::query()->whereKey($r->id)->delete();
                $deleted++;
            }
        }

        return ['ok' => true, 'deleted' => $deleted];
    }

    // =========================================================
    // Helpers: payload + parsing
    // =========================================================

    private function toGoogleEventPayload(Appointment $appt, string $tz): array
    {
        $summary = $appt->title ?? '(senza titolo)';
        if (!str_starts_with($summary, '[CRM] ')) {
            $summary = '[CRM] ' . $summary;
        }

        $payload = [
            'summary'     => $summary,
            'description' => (string)($appt->description ?? ''),
            'location'    => (string)($appt->location ?? ''),
            'extendedProperties' => [
                'private' => [
                    'source'             => 'crm',
                    'crm_appointment_id' => (string) $appt->id,
                ],
            ],
        ];

        if ((bool)$appt->all_day) {
            $start = $appt->start_at ? $appt->start_at->copy()->timezone($tz)->startOfDay() : now($tz)->startOfDay();
            $end   = $appt->end_at ? $appt->end_at->copy()->timezone($tz)->startOfDay() : $start->copy()->addDay();

            if ($end->lte($start)) $end = $start->copy()->addDay();

            $payload['start'] = ['date' => $start->toDateString()];
            $payload['end']   = ['date' => $end->toDateString()];
        } else {
            $start = $appt->start_at ? $appt->start_at->copy()->timezone($tz) : now($tz);
            $end   = $appt->end_at ? $appt->end_at->copy()->timezone($tz) : $start->copy()->addMinutes(30);

            if ($end->lte($start)) $end = $start->copy()->addMinutes(30);

            $payload['start'] = [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => $tz,
            ];
            $payload['end'] = [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => $tz,
            ];
        }

        return $payload;
    }

    private function parseGoogleDatesForCrm(array $ev, string $tz): array
    {
        $s = $ev['start'] ?? [];
        $e = $ev['end'] ?? [];

        if (!empty($s['date']) && !empty($e['date'])) {
            $start = Carbon::parse($s['date'], $tz)->startOfDay();
            $end   = Carbon::parse($e['date'], $tz)->startOfDay(); // ESCLUSIVO
            return [$start, $end, true];
        }

        if (!empty($s['dateTime']) && !empty($e['dateTime'])) {
            $start = Carbon::parse($s['dateTime'])->timezone($tz);
            $end   = Carbon::parse($e['dateTime'])->timezone($tz);
            return [$start, $end, false];
        }

        return [null, null, false];
    }

    private function findGoogleEventByCrmId(GoogleCalendarAccount $acc, string $calendarId, int $crmId): ?array
    {
        $params = [
            'privateExtendedProperty' => "crm_appointment_id={$crmId}",
            'maxResults'              => 10,
            'singleEvents'            => true,
            'showDeleted'             => false,
        ];

        $json = $this->googleGet(
            $acc,
            "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}/events",
            $params
        );

        return ($json['items'][0] ?? null);
    }

    // =========================================================
    // Account + settings
    // =========================================================

    private function isGoogleEnabled(): bool
    {
        return (bool) Setting::get('calendar.google.enabled', false)
            || (bool) Setting::get('calendar.google_enabled', false);
    }

    private function resolveAccountForSync(): GoogleCalendarAccount
    {
        // ✅ Se sono in web (utente loggato), uso SEMPRE il suo account
        $uid = auth()->id();
        if ($uid) {
            $acc = GoogleCalendarAccount::query()
                ->where('user_id', (int)$uid)
                ->where('enabled', 1)
                ->first();

            if ($acc) return $acc;
        }

        // ✅ Fallback per cron/console: primo account abilitato
        $acc = GoogleCalendarAccount::query()
            ->where('enabled', 1)
            ->orderBy('id')
            ->first();

        if (!$acc) {
            throw new \RuntimeException(
                "Nessun record presente in 'google_calendar_accounts'. Collega Google Calendar da Impostazioni."
            );
        }

        return $acc;
    }


    private function normalizeCalendarId(string $calendarId): string
    {
        $calendarId = urldecode(trim((string)$calendarId));
        return $calendarId !== '' ? $calendarId : 'primary';
    }

    private function getTz(): string
    {
        return (string) Setting::get('calendar.timezone', config('app.timezone', 'Europe/Rome'));
    }

    private function assertCalendarAccessible(GoogleCalendarAccount $acc, string $calendarId): void
    {
        $this->googleGet(
            $acc,
            "https://www.googleapis.com/calendar/v3/calendars/{$this->enc($calendarId)}",
            ['fields' => 'id,summary,timeZone']
        );
    }

    // =========================================================
    // Token + HTTP  (FIX 401)
    // =========================================================

    private function getClientId(): string
    {
        $a = (string) Setting::get('calendar.google.client_id', '');
        if ($a !== '') return $a;
        return (string) Setting::get('calendar.google_client_id', '');
    }

    private function getClientSecret(): string
    {
        $raw = (string) Setting::get('calendar.google.client_secret', '');
        if ($raw === '') $raw = (string) Setting::get('calendar.google_client_secret', '');

        if ($raw === '') return '';

        // prova decrypt (se è stato salvato cifrato)
        try {
            return (string) Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    private function ensureValidAccessToken(GoogleCalendarAccount $acc): string
    {
        $token = $this->getTokenArray($acc);

        $access    = (string)($token['access_token'] ?? '');
        $refresh   = (string)($token['refresh_token'] ?? '');
        $expiresAt = $acc->token_expires_at ? Carbon::parse($acc->token_expires_at) : null;

        if ($access !== '' && $expiresAt && $expiresAt->isFuture()) {
            return $access;
        }

        if ($refresh === '') {
            throw new \RuntimeException('Token Google scaduto e nessun refresh token disponibile. Riconnetti Google.');
        }

        $clientId     = trim($this->getClientId());
        $clientSecret = trim($this->getClientSecret());

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Manca client_id/client_secret per refresh token.');
        }

        $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refresh,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$res->ok()) {
            throw new \RuntimeException('Refresh token fallito: ' . $res->body());
        }

        $j = $res->json();
        $newAccess = $j['access_token'] ?? null;
        $expiresIn = (int)($j['expires_in'] ?? 0);

        if (!$newAccess) {
            throw new \RuntimeException('Access token non ricevuto nel refresh.');
        }

        $token['access_token'] = $newAccess;
        if (!empty($j['refresh_token'])) {
            $token['refresh_token'] = $j['refresh_token'];
        }

        $acc->token_json = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $acc->token_expires_at = $expiresIn > 0 ? now()->addSeconds(max(0, $expiresIn - 60)) : now()->addHour();
        $acc->save();

        return $newAccess;
    }

    private function getTokenArray(GoogleCalendarAccount $acc): array
    {
        $raw = (string)($acc->token_json ?? '');
        if ($raw === '') return [];
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    private function googleGet(GoogleCalendarAccount $acc, string $url, array $params = []): array
    {
        return $this->googleRequest($acc, 'get', $url, $params);
    }

    private function googlePost(GoogleCalendarAccount $acc, string $url, array $payload): array
    {
        return $this->googleRequest($acc, 'post', $url, [], $payload);
    }

    private function googlePatch(GoogleCalendarAccount $acc, string $url, array $payload): array
    {
        return $this->googleRequest($acc, 'patch', $url, [], $payload);
    }

    private function googleDelete(GoogleCalendarAccount $acc, string $url): array
    {
        return $this->googleRequest($acc, 'delete', $url, []);
    }

    private function googleRequest(
        GoogleCalendarAccount $acc,
        string $method,
        string $url,
        array $params = [],
        array $payload = []
    ): array {
        $token = $this->ensureValidAccessToken($acc);

        $params = $this->normalizeParams($params);

        $req = Http::withToken($token)
            ->acceptJson()
            ->timeout(120)
            ->retry(2, 250);

        $method = strtolower($method);

        $res = match ($method) {
            'get'    => $req->get($url, $params),
            'post'   => $req->post($url . (empty($params) ? '' : ('?' . http_build_query($params))), $payload),
            'patch'  => $req->patch($url . (empty($params) ? '' : ('?' . http_build_query($params))), $payload),
            'delete' => $req->delete($url . (empty($params) ? '' : ('?' . http_build_query($params)))),
            default  => throw new \InvalidArgumentException("Metodo non supportato: $method"),
        };

        // se 401 riprova dopo refresh
        if ($res->status() === 401) {
            $token = $this->ensureValidAccessToken($acc);

            $req = Http::withToken($token)
                ->acceptJson()
                ->timeout(120)
                ->retry(1, 250);

            $res = match ($method) {
                'get'    => $req->get($url, $params),
                'post'   => $req->post($url . (empty($params) ? '' : ('?' . http_build_query($params))), $payload),
                'patch'  => $req->patch($url . (empty($params) ? '' : ('?' . http_build_query($params))), $payload),
                'delete' => $req->delete($url . (empty($params) ? '' : ('?' . http_build_query($params)))),
                default  => $res,
            };
        }

        if (!$res->ok()) {
            throw new \RuntimeException("Google API error {$res->status()}: " . $res->body());
        }

        $j = $res->json();
        return is_array($j) ? $j : [];
    }

    private function normalizeParams(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if ($v === null) continue;
            if (is_bool($v)) {
                $out[$k] = $v ? 'true' : 'false';
                continue;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    private function enc(string $v): string
    {
        return rawurlencode($v);
    }

    private function isNotFound(\Throwable $e): bool
    {
        $m = $e->getMessage();
        return str_contains($m, ' 404') || str_contains($m, '"notFound"') || str_contains($m, 'notFound');
    }

    public function syncAccountRange(
        GoogleCalendarAccount $acc,
        string $direction,
        Carbon $from,
        Carbon $to
    ): array {
        return self::suppress(function () use ($acc, $direction, $from, $to) {

            if (!$this->isGoogleEnabled()) {
                throw new \RuntimeException("Google Calendar non abilitato (setting calendar.google.enabled).");
            }

            $calendarId = $this->normalizeCalendarId(
                $acc->calendar_id ?: (string) Setting::get('calendar.google.calendar_id', 'primary')
            );

            $tz = $this->getTz();

            $this->assertCalendarAccessible($acc, $calendarId);

            $result = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'errors'  => [],
            ];

            if (in_array($direction, ['two_way', 'pull_only'], true)) {
                $this->pullGoogleToCrm($acc, $calendarId, $from, $to, $tz, $result);
            }

            if (in_array($direction, ['two_way', 'push_only'], true)) {
                $this->pushCrmToGoogle($acc, $calendarId, $from, $to, $tz, $result);
            }

            try {
                $acc->last_synced_at = now();
                $acc->save();
            } catch (\Throwable $e) {}

            return $result;
        });
    }

}
