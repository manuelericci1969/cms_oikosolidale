<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FooterBrandSettingsController extends Controller
{
    public function edit()
    {
        $footer = [
            'enabled'       => (bool) Setting::get('footer.enabled', true),
            'brand'         => (string) Setting::get('footer.brand', ''),
            'brand_url'     => (string) Setting::get('footer.brand_url', ''),
            'brand_target'  => (string) Setting::get('footer.brand_target', '_self'),
            'email'         => (string) Setting::get('footer.email', ''),
            'product_label' => (string) Setting::get('footer.product_label', ''),
            'copyright'     => (string) Setting::get('footer.copyright', ''),
            'privacy_url'   => (string) Setting::get('footer.privacy_url', ''),
            'cookie_url'    => (string) Setting::get('footer.cookie_url', ''),
        ];

        return view('admin.settings.footer-brand', compact('footer'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'       => ['sometimes', 'boolean'],
            'brand'         => ['nullable', 'string', 'max:120'],
            'brand_url'     => ['nullable', 'string', 'max:255'],
            'brand_target'  => ['nullable', Rule::in(['_self', '_blank'])],
            'email'         => ['nullable', 'email', 'max:160'],
            'product_label' => ['nullable', 'string', 'max:180'],
            'copyright'     => ['nullable', 'string', 'max:220'],
            'privacy_url'   => ['nullable', 'string', 'max:255'],
            'cookie_url'    => ['nullable', 'string', 'max:255'],
        ]);

        Setting::put('footer.enabled', $request->boolean('enabled'), 'footer');
        Setting::put('footer.mode', 'simple', 'footer');
        Setting::put('footer.brand', trim((string) ($data['brand'] ?? '')), 'footer');
        Setting::put('footer.brand_url', trim((string) ($data['brand_url'] ?? '')), 'footer');
        Setting::put('footer.brand_target', ($data['brand_target'] ?? '_self') === '_blank' ? '_blank' : '_self', 'footer');
        Setting::put('footer.email', trim((string) ($data['email'] ?? '')), 'footer');
        Setting::put('footer.product_label', trim((string) ($data['product_label'] ?? '')), 'footer');
        Setting::put('footer.copyright', trim((string) ($data['copyright'] ?? '')), 'footer');
        Setting::put('footer.privacy_url', trim((string) ($data['privacy_url'] ?? '')), 'footer');
        Setting::put('footer.cookie_url', trim((string) ($data['cookie_url'] ?? '')), 'footer');

        return redirect()
            ->route('admin.settings.footer-brand.edit')
            ->with('ok', 'Footer brand salvato');
    }
}
