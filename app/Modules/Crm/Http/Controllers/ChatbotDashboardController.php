<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotConversation;
use App\Modules\Crm\Models\ChatbotFaq;
use App\Modules\Crm\Models\ChatbotFeedback;
use App\Modules\Crm\Models\ChatbotUnknownQuestion;
use Illuminate\Http\Request;

class ChatbotDashboardController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $conversations = ChatbotConversation::where('client_id', $clientId)->count();

        $faqActive = ChatbotFaq::where('client_id', $clientId)
            ->where('is_active', true)
            ->count();

        $unknownQuestions = ChatbotUnknownQuestion::where('client_id', $clientId)
            ->where('status', 'new')
            ->count();

        $resolvedQuestions = ChatbotUnknownQuestion::where('client_id', $clientId)
            ->where('status', 'resolved')
            ->count();

        $feedbackPositive = ChatbotFeedback::where('client_id', $clientId)
            ->where('is_helpful', true)
            ->count();

        $feedbackNegative = ChatbotFeedback::where('client_id', $clientId)
            ->where('is_helpful', false)
            ->count();

        $leads = ChatbotConversation::where('client_id', $clientId)
            ->where('status', 'qualified')
            ->count();

        return view('crm::chatbot_dashboard.index', compact(
            'conversations',
            'faqActive',
            'unknownQuestions',
            'resolvedQuestions',
            'feedbackPositive',
            'feedbackNegative',
            'leads'
        ));
    }
}
