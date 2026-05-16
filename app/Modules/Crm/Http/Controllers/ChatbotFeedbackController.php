<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotFeedback;
use Illuminate\Http\Request;

class ChatbotFeedbackController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $search   = trim((string) $request->input('q', ''));
        $type     = trim((string) $request->input('type', ''));

        $query = ChatbotFeedback::query()
            ->with(['conversation'])
            ->where('client_id', $clientId);

        if ($type !== '') {
            if ($type === 'positive') {
                $query->where('is_helpful', true);
            } elseif ($type === 'negative') {
                $query->where('is_helpful', false);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('conversation', function ($qq) use ($search) {
                        $qq->where('session_id', 'like', "%{$search}%");
                    });
            });
        }

        $feedbacks = $query
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('crm::chatbot_feedback.index', compact('feedbacks', 'search', 'type'));
    }

    public function destroy(ChatbotFeedback $chatbotFeedback)
    {
        $chatbotFeedback->delete();

        return redirect()
            ->route('admin.crm.chatbot-feedback.index')
            ->with('success', 'Feedback eliminato.');
    }
}
