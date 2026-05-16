<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarAccount;
use App\Models\Setting;
use App\Services\GoogleCalendarSyncService;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;


class GoogleCalendarController extends Controller
{
    /**
     * Legge una setting supportando sia chiavi "dot" sia "underscore".
     */
    protected function sget(string $dotKey, string $underscoreKey = null, $default = null)
    {
        $v = Setting::get($dotKey, null);
        if ($v !== null && $v !== '') return $v;

        if ($underscoreKey) {
            $v2 = Setting::get($underscoreKey, null);
            if ($v2 !== null && $v2 !== '') return $v2;
        }

        return $default;
    }

    protected function decryptIfNeeded(?string $value): ?string
    {
        if (!$value) return null;

        // se è cifrato da Crypt::encryptString prova decrypt, altrimenti ritorna com'è
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function googleClient(): GoogleClient
    {
        $clientIdRaw     = (string) $this->sget('calendar.google.client_id', 'calendar.google_client_id', '');
        $secretEncRaw    = (string) $this->sget('calendar.google.client_secret', 'calendar.google_client_secret', '');

        $clientId = trim($clientIdRaw);
        $clientSecret = trim((string) $this->decryptIfNeeded($secretEncRaw));

        if ($clientId === '' || $clientSecret === '') {
            abort(422, 'Imposta Client ID e Client Secret in Impostazioni > Calendario.');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);

        // ⚠️ questa route DEVE essere HTTPS e deve essere presente tra gli Authorized Redirect URIs in Google Cloud
        $client->setRedirectUri(route('admin.settings.google.callback'));

        // offline => refresh token
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        // Scope scrittura calendario
        $client->addScope('https://www.googleapis.com/auth/calendar');

        // opzionale: email "connesso come"
        $client->addScope('openid');
        $client->addScope('email');

        return $client;
    }

    public function connect(Request $request)
    {
        $url = $this->googleClient()->createAuthUrl();
        return redirect()->away($url);
    }


    public function disconnect(Request $request)
    {
        GoogleCalendarAccount::where('user_id', $request->user()->id)->delete();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'calendar'])
            ->with('ok', 'Google Calendar disconnesso')
            ->with('tab', 'calendar');
    }

    /**
     * (Vecchio) pulsante su settings: tienilo se lo usi ancora.
     * Ora usa syncAll() invece di metodi inesistenti.
     */


    // =========================================================
    // ✅ METODI NUOVI richiesti dalle rotte CRM
    // POST /{admin|agent}/crm/calendar/google/sync
    // =========================================================
    public function callback(Request $request)
    {
        $client = $this->googleClient();

        $code = $request->query('code');
        if (!$code) {
            abort(422, 'Codice OAuth mancante.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (!is_array($token) || isset($token['error'])) {
            abort(422, 'OAuth fallito: ' . json_encode($token));
        }

        $accessToken  = (string)($token['access_token'] ?? '');
        $refreshToken = (string)($token['refresh_token'] ?? '');

        if ($accessToken === '') {
            abort(422, 'Access token mancante.');
        }

        // prova a leggere email utente
        $email = null;
        try {
            $me = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://www.googleapis.com/oauth2/v2/userinfo')
                ->json();
            $email = is_array($me) ? ($me['email'] ?? null) : null;
        } catch (\Throwable $e) {}

        $uid = (int) $request->user()->id;

        $acc = GoogleCalendarAccount::firstOrNew(['user_id' => $uid]);

        // preserva refresh token se Google non lo rimanda (succede spesso dopo il primo consenso)
        $oldToken = [];
        if (!empty($acc->token_json)) {
            $oldToken = json_decode((string)$acc->token_json, true);
            if (!is_array($oldToken)) $oldToken = [];
        }
        if ($refreshToken === '' && !empty($oldToken['refresh_token'])) {
            $refreshToken = (string)$oldToken['refresh_token'];
        }

        $expiresIn = (int)($token['expires_in'] ?? 0);
        $expiresAt = $expiresIn > 0 ? now()->addSeconds(max(0, $expiresIn - 60)) : now()->addHour();

        $calendarId = (string) $this->sget('calendar.google.calendar_id', 'calendar.google_calendar_id', 'primary');

        $acc->google_email      = $email;
        $acc->calendar_id       = $calendarId ?: 'primary';
        $acc->enabled           = 1;
        $acc->token_json        = json_encode([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => $token['token_type'] ?? 'Bearer',
            'scope'         => $token['scope'] ?? null,
            'created'       => time(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $acc->token_expires_at  = $expiresAt;
        $acc->save();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'calendar'])
            ->with('ok', 'Google Calendar connesso')
            ->with('tab', 'calendar');
    }

    /**
     * Pulsante SYNC nella pagina impostazioni (se lo usi).
     * Qui puoi usare syncAll() che prende direction/past/future dalle settings.
     */
    public function syncNow(Request $request, GoogleCalendarSyncService $sync)
    {
        try {
            $result = $sync->syncAll();
            return back()
                ->with('ok', 'Sync completata')
                ->with('sync_result', $result)
                ->with('tab', 'calendar');
        } catch (\Throwable $e) {
            return back()
                ->with('err', $e->getMessage())
                ->with('tab', 'calendar');
        }
    }

    /**
     * Endpoint CRM: POST /{admin|agent}/crm/calendar/google/sync
     * Body JSON: { direction, range_start, range_end }
     */
    public function sync(Request $request, GoogleCalendarSyncService $sync)
    {
        $acc = GoogleCalendarAccount::where('user_id', $request->user()->id)
            ->where('enabled', 1)
            ->first();

        if (!$acc) {
            return response()->json([
                'ok' => false,
                'message' => 'Nessun account Google Calendar abilitato per questo utente.',
            ], 422);
        }

        $tz = (string) Setting::get('calendar.timezone', config('app.timezone', 'Europe/Rome'));

        $direction = (string) $request->input('direction', 'two_way');

        $from = $request->input('range_start')
            ? Carbon::parse($request->input('range_start'))->timezone($tz)
            : now($tz)->subDays(30)->startOfDay();

        $to = $request->input('range_end')
            ? Carbon::parse($request->input('range_end'))->timezone($tz)
            : now($tz)->addDays(180)->endOfDay();

        try {
            $result = $sync->syncAccountRange($acc, $direction, $from, $to);

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (\Throwable $e) {
            Log::warning('[GoogleCalendarController] sync failed', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dedupeGoogle(Request $request, GoogleCalendarSyncService $sync)
    {
        $acc = GoogleCalendarAccount::where('user_id', $request->user()->id)
            ->where('enabled', 1)
            ->first();

        if (!$acc) {
            return response()->json(['ok' => false, 'message' => 'Account Google non collegato.'], 422);
        }

        $tz = (string) Setting::get('calendar.timezone', config('app.timezone', 'Europe/Rome'));

        $from = $request->input('range_start')
            ? Carbon::parse($request->input('range_start'))->timezone($tz)->startOfDay()
            : now($tz)->subDays(90)->startOfDay();

        $to = $request->input('range_end')
            ? Carbon::parse($request->input('range_end'))->timezone($tz)->endOfDay()
            : now($tz)->addDays(365)->endOfDay();

        try {
            $result = $sync->dedupeCrmEventsOnGoogle($acc, $from, $to);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dedupeDb(Request $request, GoogleCalendarSyncService $sync)
    {
        $acc = GoogleCalendarAccount::where('user_id', $request->user()->id)
            ->where('enabled', 1)
            ->first();

        if (!$acc) {
            return response()->json(['ok' => false, 'message' => 'Account Google non collegato.'], 422);
        }

        try {
            $result = $sync->dedupeMappingsInDb($acc->id, $acc->calendar_id ?: null);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }



}
