<?php

namespace App\Modules\Crm\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotConversation;
use Illuminate\Http\Request;

class AgentChatbotConversationController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        abort(501, 'AgentChatbotConversationController@index non ancora implementato.');
    }

    public function show(Request $request, ChatbotConversation $conversation)
    {
        abort(501, 'AgentChatbotConversationController@show non ancora implementato.');
    }

    public function close(Request $request, ChatbotConversation $conversation)
    {
        abort(501, 'AgentChatbotConversationController@close non ancora implementato.');
    }

    public function reopen(Request $request, ChatbotConversation $conversation)
    {
        abort(501, 'AgentChatbotConversationController@reopen non ancora implementato.');
    }
}
