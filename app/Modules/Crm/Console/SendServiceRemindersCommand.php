<?php

namespace App\Modules\Crm\Console;

use App\Modules\Crm\Mail\ServiceExpirationReminderMail;
use App\Modules\Crm\Models\Service;
use App\Modules\Crm\Models\ServiceReminderLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendServiceRemindersCommand extends Command
{
    protected $signature = 'crm:send-service-reminders';
    protected $description = 'Invia i promemoria di scadenza per i servizi clienti';

    public function handle(): int
    {
        $this->info('Invio promemoria servizi in scadenza...');

        // puoi filtrare ulteriormente (es. solo status active)
        $services = Service::with('customer')
            ->whereNotNull('expires_at')
            ->where('send_reminder', true)
            ->get();

        $count = 0;

        foreach ($services as $service) {
            $customer = $service->customer;
            if (!$customer || !$customer->email) {
                continue;
            }

            if (!$service->canSendReminderToday()) {
                continue;
            }

            // creo il log PRIMA, così lo passo alla mail (per pixel)
            $log = ServiceReminderLog::create([
                'service_id'    => $service->id,
                'customer_id'   => $customer->id,
                'to_email'      => $customer->email,
                'status'        => 'pending',
                'tracking_hash' => Str::random(40),
            ]);

            try {
                Mail::to($customer->email)
                    ->send(new ServiceExpirationReminderMail($service, $log));

                $log->status  = 'sent';
                $log->sent_at = now();
                $log->save();

                $count++;
                $this->info("Promemoria inviato a {$customer->email} per servizio #{$service->id}");
            } catch (\Throwable $e) {
                $log->status = 'failed';
                $log->error  = $e->getMessage();
                $log->save();

                $this->error("Errore invio promemoria per servizio #{$service->id}: {$e->getMessage()}");
            }
        }

        $this->info("Totale promemoria inviati: {$count}");

        return self::SUCCESS;
    }
}
