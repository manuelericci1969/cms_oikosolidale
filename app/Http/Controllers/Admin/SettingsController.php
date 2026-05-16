<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleCalendarSyncService;
use App\Services\ImageUploadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\GoogleCalendarAccount;
use Illuminate\Support\Facades\Crypt;



class SettingsController extends Controller
{
    public function index()
    {
        $s = fn($k, $d = null) => Setting::get($k, $d);

        $asInt = function ($v) {
            if (is_array($v)) $v = $v[0] ?? null;
            if (!is_numeric($v)) return null;
            $n = (int) $v;
            return $n > 0 ? $n : null;
        };

        $defaultQuoteIntro =
            "Gentile Cliente,\n\n".
            "con la presente siamo lieti di sottoporLe la nostra migliore offerta per la fornitura e realizzazione dei servizi/prodotti richiesti.\n\n".
            "L'offerta è stata elaborata sulla base delle informazioni ad oggi in nostro possesso ed è pensata per garantirLe il miglior equilibrio tra qualità, affidabilità e investimento economico.\n\n".
            "Restiamo a Sua completa disposizione per qualsiasi chiarimento, integrazione o adattamento dell'offerta alle Sue specifiche esigenze.";

        $branding = [
            'logo_id'       => $asInt($s('branding.logo_id')),
            'logo_dark_id'  => $asInt($s('branding.logo_dark_id')),
            'favicon_id'    => $asInt($s('branding.favicon_id')),
            'theme_color'   => (string) ($s('ui.theme_color', '#0d6efd') ?? '#0d6efd'),
            'logo_fit'      => (string) ($s('branding.logo_fit', 'contain') ?? 'contain'),
            'logo_dark_fit' => (string) ($s('branding.logo_dark_fit', 'contain') ?? 'contain'),
        ];

        $company = [
            'name'     => $s('company.name'),
            'vat'      => $s('company.vat'),
            'address'  => $s('company.address'),
            'city'     => $s('company.city'),
            'zip'      => $s('company.zip'),
            'province' => $s('company.province'),
            'country'  => $s('company.country', 'IT'),
            'email'    => $s('company.email'),
            'phone'    => $s('company.phone'),
            'bank'     => $s('company.bank'),
            'iban'     => $s('company.iban'),
            'bic'      => $s('company.bic'),
            'pec'      => $s('company.pec'),
            'sdi'      => $s('company.sdi'),
        ];

        $seo = [
            'meta_title'       => $s('seo.meta_title', 'R4Software'),
            'meta_description' => $s('seo.meta_description'),
            'og_image_id'      => $asInt($s('seo.og_image_id')),
            'robots'           => (string) ($s('seo.robots', 'index, follow') ?? 'index, follow'),
            'robots_extra'     => (string) ($s('seo.robots_extra', '') ?? ''),
        ];

        $analytics = [
            'ga4_id' => $s('analytics.ga4_id'),
            'gtm_id' => $s('analytics.gtm_id'),
        ];

        $typography = [
            'body_family'     => $s('typography.body_family', 'Inter'),
            'heading_family'  => $s('typography.heading_family', 'Inter'),
            'title_family'    => $s('typography.title_family', ''),
            'body_weight'     => $s('typography.body_weight', '400'),
            'heading_weight'  => $s('typography.heading_weight', '700'),
            'title_weight'    => $s('typography.title_weight', '700'),
            'body_italic'     => (bool) $s('typography.body_italic', false),
            'heading_italic'  => (bool) $s('typography.heading_italic', false),
            'title_italic'    => (bool) $s('typography.title_italic', false),
            'body_size'       => $s('typography.body_size', '1rem'),
            'lead_size'       => $s('typography.lead_size', '1.25rem'),
            'h1_size'         => $s('typography.h1_size', '2.5rem'),
            'h2_size'         => $s('typography.h2_size', '2rem'),
            'h3_size'         => $s('typography.h3_size', '1.75rem'),
            'h4_size'         => $s('typography.h4_size', '1.5rem'),
            'h5_size'         => $s('typography.h5_size', '1.25rem'),
            'h6_size'         => $s('typography.h6_size', '1rem'),
        ];

        $crm = [
            'quote_intro_default'         => $s('crm.quote_intro_default', $defaultQuoteIntro),
            'quote_payment_terms_default' => $s('crm.quote_payment_terms_default', "Pagamento da concordare."),
            'contract_terms'              => $s('crm.contract_terms', ''),
            'contract_privacy'            => $s('crm.contract_privacy', ''),
            'bank_details'                => $s('crm.bank_details', ''),
        ];

        $legal = [
            'cookie_enabled'    => (bool) ($s('legal.cookie_enabled', true)),
            'cookie_message'    => (string) ($s('legal.cookie_message', '') ?? ''),
            'cookie_button'     => (string) ($s('legal.cookie_button', 'Accetta') ?? 'Accetta'),
            'cookie_link_label' => (string) ($s('legal.cookie_link_label', 'Leggi l’informativa') ?? 'Leggi l’informativa'),
            'privacy_url'       => (string) ($s('legal.privacy_url', '/privacy-policy') ?? '/privacy-policy'),
        ];

        // ==========================
        // CALENDAR (Google)
        // ==========================
        $redirectUri = route('admin.settings.google.callback');

        $enabled = (bool) $s('calendar.google.enabled', false);

        $me = auth()->user();
        $acc = $me
            ? GoogleCalendarAccount::where('user_id', $me->id)->where('enabled', 1)->first()
            : null;

        $calendar = [
            'google_enabled'      => $enabled,
            'google_client_id'    => (string) ($s('calendar.google.client_id', '') ?? ''),
            'google_secret_set'   => !empty($s('calendar.google.client_secret', '')),
            'google_redirect_uri' => route('admin.settings.google.callback'),
            'google_calendar_id'  => (string) ($s('calendar.google.calendar_id', 'primary') ?? 'primary'),

            'default_client_id'   => (int) ($s('calendar.google.default_client_id', 1) ?? 1),

            // ✅ stato connessione da google_calendar_accounts
            'connected'       => $enabled && $acc !== null,
            'connected_email' => $acc?->google_email,
            'last_synced_at'  => $acc?->last_synced_at,
        ];


        return view('admin.settings.index', compact(
            'branding',
            'company',
            'seo',
            'analytics',
            'typography',
            'crm',
            'legal',
            'calendar',
        ));
    }

    public function update(Request $r, ImageUploadService $img)
    {
        $tab = $r->input('tab', 'branding');

        // ==========================================================
        // BRANDING
        // ==========================================================
        if ($tab === 'branding') {

            foreach (['logo_id', 'logo_dark_id', 'favicon_id'] as $k) {
                $v = $r->input($k);

                if ($v === '' || $v === null || $v === 'null' || $v === 'undefined') {
                    $r->merge([$k => null]);
                    continue;
                }

                if (!is_numeric($v)) {
                    $r->merge([$k => null]);
                    continue;
                }

                $n = (int) $v;
                $r->merge([$k => ($n > 0 ? $n : null)]);
            }

            $rules = [
                'theme_color'   => ['nullable', 'string', 'max:20'],
                'logo'          => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:20480'],
                'logo_dark'     => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:20480'],
                'favicon'       => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:10240'],
                'logo_fit'      => ['nullable', 'in:contain,cover'],
                'logo_dark_fit' => ['nullable', 'in:contain,cover'],
            ];

            if (!$r->hasFile('logo')) {
                $rules['logo_id'] = ['nullable', 'integer', Rule::exists('media', 'id')];
            }
            if (!$r->hasFile('logo_dark')) {
                $rules['logo_dark_id'] = ['nullable', 'integer', Rule::exists('media', 'id')];
            }
            if (!$r->hasFile('favicon')) {
                $rules['favicon_id'] = ['nullable', 'integer', Rule::exists('media', 'id')];
            }

            $r->validate($rules);

            $logoFit     = $r->input('logo_fit', 'contain');
            $logoDarkFit = $r->input('logo_dark_fit', 'contain');

            if ($r->hasFile('logo')) {
                $m = $img->storeWithVariants($r->file('logo'), [
                    'dir'     => 'uploads/branding',
                    'profile' => 'logo',
                    'fit'     => $logoFit,
                ]);
                Setting::put('branding.logo_id', $m->id, 'branding');
            } elseif ($r->has('logo_id') && $r->input('logo_id') === null) {
                Setting::put('branding.logo_id', null, 'branding');
            } elseif ($r->filled('logo_id')) {
                Setting::put('branding.logo_id', (int) $r->input('logo_id'), 'branding');
            }

            if ($r->hasFile('logo_dark')) {
                $m = $img->storeWithVariants($r->file('logo_dark'), [
                    'dir'     => 'uploads/branding',
                    'profile' => 'logo',
                    'fit'     => $logoDarkFit,
                ]);
                Setting::put('branding.logo_dark_id', $m->id, 'branding');
            } elseif ($r->has('logo_dark_id') && $r->input('logo_dark_id') === null) {
                Setting::put('branding.logo_dark_id', null, 'branding');
            } elseif ($r->filled('logo_dark_id')) {
                Setting::put('branding.logo_dark_id', (int) $r->input('logo_dark_id'), 'branding');
            }

            if ($r->hasFile('favicon')) {
                $m = $img->storeWithVariants($r->file('favicon'), [
                    'dir'     => 'uploads/branding',
                    'profile' => 'logo',
                    'fit'     => 'contain',
                ]);
                Setting::put('branding.favicon_id', $m->id, 'branding');
            } elseif ($r->has('favicon_id') && $r->input('favicon_id') === null) {
                Setting::put('branding.favicon_id', null, 'branding');
            } elseif ($r->filled('favicon_id')) {
                Setting::put('branding.favicon_id', (int) $r->input('favicon_id'), 'branding');
            }

            Setting::put('branding.logo_fit', $logoFit, 'branding');
            Setting::put('branding.logo_dark_fit', $logoDarkFit, 'branding');

            if ($r->filled('theme_color')) {
                Setting::put('ui.theme_color', $r->string('theme_color')->toString(), 'branding');
            }

            return back()->with('ok', 'Branding salvato')->with('tab', $tab);
        }

        // ==========================================================
        // COMPANY
        // ==========================================================
        if ($tab === 'company') {
            $data = $r->validate([
                'name'     => ['required', 'string', 'max:120'],
                'vat'      => ['nullable', 'string', 'max:50'],
                'address'  => ['nullable', 'string', 'max:200'],
                'city'     => ['nullable', 'string', 'max:80'],
                'zip'      => ['nullable', 'string', 'max:20'],
                'province' => ['nullable', 'string', 'max:20'],
                'country'  => ['nullable', 'string', 'max:2'],
                'email'    => ['nullable', 'email', 'max:120'],
                'phone'    => ['nullable', 'string', 'max:40'],
                'bank'     => ['nullable', 'string', 'max:120'],
                'iban'     => ['nullable', 'string', 'max:50'],
                'bic'      => ['nullable', 'string', 'max:50'],
                'pec'      => ['nullable', 'email', 'max:120'],
                'sdi'      => ['nullable', 'string', 'max:30'],
            ]);

            foreach ($data as $k => $v) {
                Setting::put("company.$k", $v, 'company');
            }

            return back()->with('ok', 'Dati azienda salvati')->with('tab', $tab);
        }

        // ==========================================================
        // SEO
        // ==========================================================
        if ($tab === 'seo') {
            $og = $r->input('og_image_id');
            if ($og === '' || $og === null || $og === 'null' || $og === 'undefined' || !is_numeric($og) || (int) $og <= 0) {
                $r->merge(['og_image_id' => null]);
            } else {
                $r->merge(['og_image_id' => (int) $og]);
            }

            $rules = [
                'meta_title'       => ['nullable', 'string', 'max:70'],
                'meta_description' => ['nullable', 'string', 'max:160'],
                'robots'           => ['nullable', 'string', 'max:50'],
                'robots_extra'     => ['nullable', 'string'],
                'og_image'         => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:20480'],
            ];

            if (!$r->hasFile('og_image')) {
                $rules['og_image_id'] = ['nullable', 'integer', Rule::exists('media', 'id')];
            }

            $data = $r->validate($rules);

            foreach (['meta_title', 'meta_description', 'robots'] as $k) {
                if ($r->filled($k)) {
                    Setting::put("seo.$k", $r->string($k)->toString(), 'seo');
                }
            }

            Setting::put('seo.robots_extra', $data['robots_extra'] ?? '', 'seo');

            if ($r->hasFile('og_image')) {
                $m = $img->storeWithVariants($r->file('og_image'), ['dir' => 'uploads/seo', 'profile' => 'photo']);
                Setting::put('seo.og_image_id', $m->id, 'seo');
            } elseif ($r->has('og_image_id') && $r->input('og_image_id') === null) {
                Setting::put('seo.og_image_id', null, 'seo');
            } elseif ($r->filled('og_image_id')) {
                Setting::put('seo.og_image_id', (int) $r->input('og_image_id'), 'seo');
            }

            return back()->with('ok', 'SEO salvato')->with('tab', $tab);
        }

        // ==========================================================
        // ANALYTICS
        // ==========================================================
        if ($tab === 'analytics') {
            $data = $r->validate([
                'ga4_id' => ['nullable', 'string', 'max:30'],
                'gtm_id' => ['nullable', 'string', 'max:30'],
            ]);

            foreach ($data as $k => $v) {
                Setting::put("analytics.$k", $v, 'analytics');
            }

            return back()->with('ok', 'Analytics salvato')->with('tab', $tab);
        }

        // ==========================================================
        // CRM
        // ==========================================================
        if ($tab === 'crm') {
            $data = $r->validate([
                'quote_intro_default'         => ['nullable', 'string'],
                'quote_payment_terms_default' => ['nullable', 'string'],
                'contract_terms'              => ['nullable', 'string'],
                'contract_privacy'            => ['nullable', 'string'],
                'bank_details'                => ['nullable', 'string'],
            ]);

            foreach ($data as $k => $v) {
                Setting::put("crm.$k", $v, 'crm');
            }

            return back()->with('ok', 'Impostazioni CRM salvate')->with('tab', $tab);
        }

        // ==========================================================
        // LEGAL
        // ==========================================================
        if ($tab === 'legal') {
            $data = $r->validate([
                'cookie_enabled'    => ['sometimes', 'boolean'],
                'privacy_url'       => ['nullable', 'string', 'max:255'],
                'cookie_message'    => ['nullable', 'string'],
                'cookie_button'     => ['nullable', 'string', 'max:60'],
                'cookie_link_label' => ['nullable', 'string', 'max:60'],
            ]);

            Setting::put('legal.cookie_enabled', $r->boolean('cookie_enabled'), 'legal');
            Setting::put('legal.privacy_url', $data['privacy_url'] ?? '/privacy-policy', 'legal');
            Setting::put('legal.cookie_message', $data['cookie_message'] ?? '', 'legal');
            Setting::put('legal.cookie_button', $data['cookie_button'] ?? 'Accetta', 'legal');
            Setting::put('legal.cookie_link_label', $data['cookie_link_label'] ?? 'Leggi l’informativa', 'legal');

            return back()->with('ok', 'Impostazioni Privacy / Cookie salvate')->with('tab', $tab);
        }

        // ==========================================================
        // TYPOGRAPHY
        // ==========================================================
        if ($tab === 'typography') {
            $data = $r->validate([
                'body_family'    => ['nullable', 'string', 'max:80'],
                'heading_family' => ['nullable', 'string', 'max:80'],
                'title_family'   => ['nullable', 'string', 'max:80'],
                'body_weight'    => ['nullable', 'in:400,500,600,700'],
                'heading_weight' => ['nullable', 'in:400,500,600,700'],
                'title_weight'   => ['nullable', 'in:400,500,600,700'],
                'body_italic'    => ['sometimes', 'boolean'],
                'heading_italic' => ['sometimes', 'boolean'],
                'title_italic'   => ['sometimes', 'boolean'],
                'body_size'      => ['nullable', 'string', 'max:20'],
                'lead_size'      => ['nullable', 'string', 'max:20'],
                'h1_size'        => ['nullable', 'string', 'max:20'],
                'h2_size'        => ['nullable', 'string', 'max:20'],
                'h3_size'        => ['nullable', 'string', 'max:20'],
                'h4_size'        => ['nullable', 'string', 'max:20'],
                'h5_size'        => ['nullable', 'string', 'max:20'],
                'h6_size'        => ['nullable', 'string', 'max:20'],
            ]);

            $data['body_italic']    = $r->boolean('body_italic');
            $data['heading_italic'] = $r->boolean('heading_italic');
            $data['title_italic']   = $r->boolean('title_italic');

            foreach ($data as $k => $v) {
                if ($v !== null && $v !== '') {
                    Setting::put("typography.$k", $v, 'typography');
                }
            }

            return back()->with('ok', 'Tipografia salvata')->with('tab', $tab);
        }

        // ==========================================================
        // CALENDAR (Google)
        // ==========================================================
        if ($tab === 'calendar') {
            $data = $r->validate([
                'google_enabled'       => ['sometimes', 'boolean'],
                'google_client_id'     => ['nullable', 'string', 'max:255'],
                'google_client_secret' => ['nullable', 'string', 'max:255'],
                'google_calendar_id'   => ['nullable', 'string', 'max:255'],
                'default_client_id'    => ['nullable', 'integer', 'min:1'],
            ]);

            Setting::put('calendar.google.enabled', $r->boolean('google_enabled'), 'calendar');
            Setting::put('calendar.google.client_id', $data['google_client_id'] ?? '', 'calendar');
            Setting::put('calendar.google.calendar_id', $data['google_calendar_id'] ?? 'primary', 'calendar');

            if ($r->filled('google_client_secret')) {
                Setting::put('calendar.google.client_secret', $data['google_client_secret'], 'calendar');
            }

            Setting::put('calendar.google.redirect_uri', route('admin.settings.google.callback'), 'calendar');
            Setting::put('calendar.google.default_client_id', (int)($data['default_client_id'] ?? 1), 'calendar');

            return back()->with('ok', 'Impostazioni Calendario salvate')->with('tab', $tab);
        }

        return back()->with('ok', 'Impostazioni salvate')->with('tab', $tab);
    }

}
