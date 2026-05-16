<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotSettingsController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) Setting::get('chatbot.enabled', false),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'chatbot_enabled' => ['sometimes', 'boolean'],
        ]);

        Setting::put('chatbot.enabled', $request->boolean('chatbot_enabled'), 'chatbot');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'chatbot'])
            ->with('ok', 'Impostazioni ChatBot salvate')
            ->with('tab', 'chatbot');
    }
}
