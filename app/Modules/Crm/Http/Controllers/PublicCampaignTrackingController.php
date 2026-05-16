<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CampaignLinkClick;
use App\Modules\Crm\Models\CampaignRecipient;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\EmailListContact;
use App\Modules\Crm\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicCampaignTrackingController extends Controller
{
    /**
     * Pixel di apertura.
     */
    public function open($recipientId, $hash)
    {
        $recipient = CampaignRecipient::where('id', $recipientId)
            ->where('hash', $hash)
            ->first();

        if ($recipient) {
            // Prima apertura
            if (! $recipient->opened_at) {
                $recipient->opened_at = now();
            }

            // Incrementa contatore aperture su questo destinatario
            $recipient->open_count = ($recipient->open_count ?? 0) + 1;
            $recipient->save();

            // Se è la prima apertura di questo destinatario,
            // incrementa anche il contatore globale sulla campagna
            if ($recipient->open_count === 1 && $recipient->campaign) {
                $recipient->campaign()->increment('open_count');
            }
        }

        // Pixel 1x1 trasparente
        $image = base64_decode(
            'R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='
        );

        return response($image, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Tracciamento click.
     * Il link nelle email deve puntare qui con ?url=...
     */
    public function click_OLD(Request $request, $recipientId, $hash)
    {
        $recipient = CampaignRecipient::where('id', $recipientId)
            ->where('hash', $hash)
            ->firstOrFail();

        $url = $request->query('url');
        if (! $url) {
            abort(404);
        }

        // Prima volta che clicca
        if (! $recipient->clicked_at) {
            $recipient->clicked_at = now();
        }

        // Incrementa contatore click per questo destinatario
        $recipient->click_count = ($recipient->click_count ?? 0) + 1;
        $recipient->save();

        // Se è il primo click di questo destinatario,
        // incrementa anche il contatore globale sulla campagna
        if ($recipient->click_count === 1 && $recipient->campaign) {
            $recipient->campaign()->increment('click_count');
        }

        return redirect()->away($url);
    }

    public function click(Request $request, $recipientId, $hash)
    {
        $recipient = CampaignRecipient::where('id', $recipientId)
            ->where('hash', $hash)
            ->firstOrFail();

        $url = $request->query('url');
        if (! $url) {
            abort(404);
        }

        // (opzionale) puoi fare un po' di sanitizzazione
        $normalizedUrl = trim($url);

        // -------------------------
        // 1) Aggiorno il recipient
        // -------------------------
        $wasClickedBefore = ! is_null($recipient->clicked_at);

        if (! $wasClickedBefore) {
            $recipient->clicked_at = now();
        }

        $recipient->click_count = (int) ($recipient->click_count ?? 0) + 1;
        $recipient->save();

        // Incremento il contatore "click" della campagna solo al PRIMO click del recipient
        if (! $wasClickedBefore && $recipient->campaign) {
            $recipient->campaign->increment('click_count');
        }

        // --------------------------------------------
        // 2) Traccio il link specifico (URL) cliccato
        // --------------------------------------------
        $now = now();

        $linkClick = CampaignLinkClick::where('campaign_id', $recipient->campaign_id)
            ->where('recipient_id', $recipient->id)
            ->where('url', $normalizedUrl)
            ->first();

        if (! $linkClick) {
            CampaignLinkClick::create([
                'campaign_id'      => $recipient->campaign_id,
                'recipient_id'     => $recipient->id,
                'url'              => $normalizedUrl,
                'click_count'      => 1,
                'first_clicked_at' => $now,
                'last_clicked_at'  => $now,
            ]);
        } else {
            $linkClick->click_count = (int) ($linkClick->click_count ?? 0) + 1;
            $linkClick->last_clicked_at = $now;
            $linkClick->save();
        }

        // --------------------------------------------
        // 3) Redirect alla pagina finale
        // --------------------------------------------
        return redirect()->to($url);
    }


    /**
     * Vecchia versione, lasciata solo come riferimento.
     * NON più usata.
     */
    public function unsubscribe_OLD($recipientId, $hash)
    {
        $recipient = CampaignRecipient::where('id', $recipientId)
            ->where('hash', $hash)
            ->firstOrFail();

        $recipient->update([
            'status'          => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        $recipient->campaign()->increment('unsubscribe_count');

        return view('crm::campaigns.unsubscribe-ok');
    }

    /**
     * Nuova gestione disiscrizione globale.
     * - marca il recipient come unsubscribed (solo una volta)
     * - incrementa unsubscribe_count sulla campagna
     * - disattiva il consenso marketing su:
     *   - Lead (crm_leads)
     *   - Customer (crm_customers, se ha la colonna)
     *   - EmailListContact (crm_email_list_contacts)
     */
    public function unsubscribe(int $recipientId, string $hash)
    {
        $recipient = CampaignRecipient::where('id', $recipientId)
            ->where('hash', $hash)
            ->first();

        // Link non valido
        if (! $recipient) {
            return view('crm::campaigns.unsubscribe-error');
        }

        $campaign = $recipient->campaign;
        $clientId = $campaign?->client_id ?? 1;
        $email    = trim((string) $recipient->email);

        // capiamo se era già disiscritto da prima
        $wasAlreadyUnsubscribed = ($recipient->status === 'unsubscribed');

        // 1) Se non era ancora disiscritto, aggiorno stato + contatore campagna
        if (! $wasAlreadyUnsubscribed) {
            $recipient->update([
                'status'          => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

            if ($campaign) {
                $campaign->increment('unsubscribe_count');
            }
        }

        // 2) In ogni caso (anche se era già unsubscribed) blocco il consenso
        if ($email !== '') {

            // LEAD
            Lead::where('client_id', $clientId)
                ->where('email', $email)
                ->update(['marketing_consense' => false]);

            // CLIENTI (se la colonna esiste)
            if (Schema::hasColumn('crm_customers', 'marketing_consense')) {
                Customer::where('client_id', $clientId)
                    ->where('email', $email)
                    ->update(['marketing_consense' => false]);
            }

            // CONTATTI LISTE (senza client_id, solo per email)
            if (
                Schema::hasTable('crm_email_list_contacts') &&
                Schema::hasColumn('crm_email_list_contacts', 'marketing_consense')
            ) {
                EmailListContact::where('email', $email)
                    ->update(['marketing_consense' => false]);
            }
        }

        return view('crm::campaigns.unsubscribe-ok', [
            'recipient'             => $recipient,
            'already_unsubscribed'  => $wasAlreadyUnsubscribed,
        ]);
    }

}
