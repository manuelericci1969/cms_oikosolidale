<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotFaq;
use App\Modules\Crm\Models\ChatbotUnknownQuestion;
use App\Modules\Crm\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ChatbotFaqController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function resolveView(string $view): string
    {
        $candidates = [
            'crm::' . $view,
            $view,
        ];

        foreach ($candidates as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        abort(500, 'View non trovata: ' . implode(' oppure ', $candidates));
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $search   = trim((string) $request->input('q', ''));

        $query = ChatbotFaq::query()
            ->with('product')
            ->where('client_id', $clientId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question_pattern', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%")
                    ->orWhere('intent', 'like', "%{$search}%");
            });
        }

        $faqs = $query
            ->orderBy('priority')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view($this->resolveView('chatbot_faqs.index'), compact('faqs', 'search'));
    }

    public function create(Request $request)
    {
        $productOptions = Product::query()
            ->where('client_id', $this->clientId($request))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $faq = new ChatbotFaq([
            'question_pattern' => trim((string) $request->input('question_pattern', '')),
            'keywords'         => trim((string) $request->input('keywords', '')),
            'intent'           => trim((string) $request->input('intent', '')) ?: null,
            'answer'           => trim((string) $request->input('answer', '')),
            'priority'         => 100,
            'is_active'        => true,
        ]);

        $unknownQuestionId = $request->input('unknown_question_id');

        return view($this->resolveView('chatbot_faqs.create'), compact('faq', 'productOptions', 'unknownQuestionId'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'question_pattern'   => 'required|string|max:255',
            'keywords'           => 'nullable|string',
            'intent'             => 'nullable|string|max:100',
            'product_id'         => 'nullable|integer|exists:crm_products,id',
            'answer'             => 'required|string',
            'priority'           => 'nullable|integer|min:0|max:999999',
            'is_active'          => 'nullable|boolean',
            'unknown_question_id'=> 'nullable|integer|exists:crm_chatbot_unknown_questions,id',
        ]);

        $data['client_id']  = $clientId;
        $data['priority']   = $data['priority'] ?? 100;
        $data['is_active']  = $request->boolean('is_active', true);
        $data['keywords']   = trim((string) ($data['keywords'] ?? '')) ?: null;
        $data['intent']     = trim((string) ($data['intent'] ?? '')) ?: null;
        $data['product_id'] = $data['product_id'] ?: null;

        $unknownQuestionId = $data['unknown_question_id'] ?? null;
        unset($data['unknown_question_id']);

        ChatbotFaq::create($data);

        if ($unknownQuestionId) {
            ChatbotUnknownQuestion::query()
                ->where('id', $unknownQuestionId)
                ->where('client_id', $clientId)
                ->update([
                    'status' => 'resolved',
                ]);
        }

        return redirect()
            ->route('admin.crm.chatbot-faqs.index')
            ->with('success', 'FAQ chatbot creata con successo.');
    }

    public function edit(Request $request, ChatbotFaq $chatbotFaq)
    {
        $productOptions = Product::query()
            ->where('client_id', $this->clientId($request))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $faq = $chatbotFaq;

        return view($this->resolveView('chatbot_faqs.edit'), compact('faq', 'productOptions'));
    }

    public function update(Request $request, ChatbotFaq $chatbotFaq)
    {
        $data = $request->validate([
            'question_pattern' => 'required|string|max:255',
            'keywords'         => 'nullable|string',
            'intent'           => 'nullable|string|max:100',
            'product_id'       => 'nullable|integer|exists:crm_products,id',
            'answer'           => 'required|string',
            'priority'         => 'nullable|integer|min:0|max:999999',
            'is_active'        => 'nullable|boolean',
        ]);

        $data['priority']   = $data['priority'] ?? 100;
        $data['is_active']  = $request->boolean('is_active', true);
        $data['keywords']   = trim((string) ($data['keywords'] ?? '')) ?: null;
        $data['intent']     = trim((string) ($data['intent'] ?? '')) ?: null;
        $data['product_id'] = $data['product_id'] ?: null;

        $chatbotFaq->update($data);

        return redirect()
            ->route('admin.crm.chatbot-faqs.index')
            ->with('success', 'FAQ chatbot aggiornata con successo.');
    }

    public function destroy(ChatbotFaq $chatbotFaq)
    {
        $chatbotFaq->delete();

        return redirect()
            ->route('admin.crm.chatbot-faqs.index')
            ->with('success', 'FAQ chatbot eliminata.');
    }
}
