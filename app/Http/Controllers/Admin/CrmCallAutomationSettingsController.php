<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CrmCallAutomationSettingsController extends Controller
{
    public function edit()
    {
        $settingsAvailable = $this->tableExists('settings');

        $tables = [
            'crm_call_campaigns' => $this->tableExists('crm_call_campaigns'),
            'crm_call_queue' => $this->tableExists('crm_call_queue'),
            'crm_call_logs' => $this->tableExists('crm_call_logs'),
        ];

        $settings = [
            'enabled' => $this->settingBool('crm.calls.enabled', false),
            'run_active_enabled' => $this->settingBool('crm.calls.run_active_enabled', false),
            'recover_stuck_enabled' => $this->settingBool('crm.calls.recover_stuck_enabled', false),
        ];

        $canRunActive = $settingsAvailable && $settings['enabled'] && $settings['run_active_enabled'] && $tables['crm_call_campaigns'];
        $canRecoverStuck = $settingsAvailable && $settings['enabled'] && $settings['recover_stuck_enabled'] && $tables['crm_call_queue'] && $tables['crm_call_logs'];

        return view('admin.settings.crm-call-automation', compact(
            'settingsAvailable',
            'tables',
            'settings',
            'canRunActive',
            'canRecoverStuck'
        ));
    }

    public function update(Request $request)
    {
        if (! $this->tableExists('settings')) {
            return back()->withErrors([
                'settings' => 'La tabella settings non è presente: impossibile salvare le automazioni da pannello.',
            ]);
        }

        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'run_active_enabled' => ['sometimes', 'boolean'],
            'recover_stuck_enabled' => ['sometimes', 'boolean'],
        ]);

        Setting::put('crm.calls.enabled', (bool) ($data['enabled'] ?? false), 'crm');
        Setting::put('crm.calls.run_active_enabled', (bool) ($data['run_active_enabled'] ?? false), 'crm');
        Setting::put('crm.calls.recover_stuck_enabled', (bool) ($data['recover_stuck_enabled'] ?? false), 'crm');

        Cache::forget('settings.kv');

        return back()->with('ok', 'Automazioni chiamate CRM aggiornate.');
    }

    protected function settingBool(string $key, bool $default = false): bool
    {
        try {
            if (! $this->tableExists('settings')) {
                return $default;
            }

            return (bool) Setting::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
