<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotUnknownQuestion;
use Illuminate\Http\Request;

class ChatbotUnknownQuestionController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $search   = trim((string) $request->input('q', ''));
        $status   = trim((string) $request->input('status', ''));

        $query = ChatbotUnknownQuestion::query()
            ->with('conversation')
            ->where('client_id', $clientId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('intent_detected', 'like', "%{$search}%")
                    ->orWhere('source_page', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $questions = $query
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('crm::chatbot_unknown_questions.index', compact('questions', 'search', 'status'));
    }

    public function updateStatus(Request $request, ChatbotUnknownQuestion $chatbotUnknownQuestion)
    {
        $data = $request->validate([
            'status' => 'required|string|in:new,reviewed,resolved',
        ]);

        $chatbotUnknownQuestion->update([
            'status' => $data['status'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Stato domanda aggiornato.');
    }

    public function destroy(ChatbotUnknownQuestion $chatbotUnknownQuestion)
    {
        $chatbotUnknownQuestion->delete();

        return redirect()
            ->route('admin.crm.chatbot-unknown-questions.index')
            ->with('success', 'Domanda eliminata.');
    }
}
