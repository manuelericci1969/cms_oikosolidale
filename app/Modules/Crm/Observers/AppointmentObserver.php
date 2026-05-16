<?php

namespace App\Modules\Crm\Observers;

use App\Models\GoogleCalendarAccount;
use App\Models\Setting;
use App\Modules\Crm\Models\Appointment;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    public function created(Appointment $appt): void
    {
        $this->push($appt);
    }

    public function updated(Appointment $appt): void
    {
        $this->push($appt);
    }

    public function deleted(Appointment $appt): void
    {
        $this->deleteFromGoogle($appt);
    }

    public function forceDeleted(Appointment $appt): void
    {
        $this->deleteFromGoogle($appt);
    }

    // =========================================================
    // Helpers
    // =========================================================

    protected function push(Appointment $appt): void
    {
        // anti-loop: durante sync Google->CRM non pushare di nuovo su Google
        if (GoogleCalendarSyncService::isSuppressed()) return;

        if (!$this->googleEnabled()) return;

        $acc = $this->accountForAppointment($appt);
        if (!$acc) return;

        try {
            app(GoogleCalendarSyncService::class)->upsertAppointmentToGoogle($acc, $appt);
        } catch (\Throwable $e) {
            Log::warning('[AppointmentObserver] push failed', [
                'appt_id' => $appt->id ?? null,
                'user_id' => $appt->user_id ?? null,
                'err'     => $e->getMessage(),
            ]);
        }
    }

    protected function deleteFromGoogle(Appointment $appt): void
    {
        if (GoogleCalendarSyncService::isSuppressed()) return;

        if (!$this->googleEnabled()) return;

        $acc = $this->accountForAppointment($appt);
        if (!$acc) return;

        try {
            app(GoogleCalendarSyncService::class)->deleteAppointmentFromGoogle($acc, $appt);
        } catch (\Throwable $e) {
            Log::warning('[AppointmentObserver] delete failed', [
                'appt_id' => $appt->id ?? null,
                'user_id' => $appt->user_id ?? null,
                'err'     => $e->getMessage(),
            ]);
        }
    }

    protected function accountForAppointment(Appointment $appt): ?GoogleCalendarAccount
    {
        // logica attuale: un account Google per user_id
        return GoogleCalendarAccount::query()
            ->where('user_id', (int)$appt->user_id)
            ->where('enabled', 1)
            ->first();
    }

    protected function googleEnabled(): bool
    {
        // compatibilità: accetta entrambe le chiavi
        return (bool) Setting::get('calendar.google.enabled', false)
            || (bool) Setting::get('calendar.google_enabled', false);
    }
}
