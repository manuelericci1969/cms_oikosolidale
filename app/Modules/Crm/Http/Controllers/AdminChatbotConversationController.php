<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Crm\Models\ChatbotConversation;
use App\Modules\Crm\Models\Lead;
use Illuminate\Http\Request;

class AdminChatbotConversationController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function ensureConversationBelongsToClient(Request $request, ChatbotConversation $conversation): void
    {
        $clientId = $this->clientId($request);

        if ((int) $conversation->client_id !== (int) $clientId) {
            abort(404);
        }
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $search  = trim((string) $request->input('q', ''));
        $status  = trim((string) $request->input('status', ''));
        $intent  = trim((string) $request->input('intent', ''));
        $channel = trim((string) $request->input('channel', ''));
        $ownerId = trim((string) $request->input('owner_id', ''));
        $linked  = trim((string) $request->input('linked', ''));

        $query = ChatbotConversation::query()
            ->with([
                'owner:id,name',
                'lead:id,name,email,phone',
            ])
            ->where('client_id', $clientId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                    ->orWhere('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('visitor_company', 'like', "%{$search}%")
                    ->orWhere('source_page', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($intent !== '') {
            $query->where('intent', $intent);
        }

        if ($channel !== '') {
            $query->where('channel', $channel);
        }

        if ($ownerId !== '') {
            $query->where('owner_id', $ownerId);
        }

        if ($linked === 'yes') {
            $query->whereNotNull('lead_id');
        } elseif ($linked === 'no') {
            $query->whereNull('lead_id');
        }

        $conversations = $query
            ->orderByRaw('CASE WHEN last_message_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statusOptions  = ChatbotConversation::STATUS_OPTIONS;
        $intentOptions  = ChatbotConversation::INTENT_OPTIONS;
        $channelOptions = ChatbotConversation::CHANNEL_OPTIONS;

        $owners = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('crm::chatbot_conversations.index', [
            'conversations'  => $conversations,
            'statusOptions'  => $statusOptions,
            'intentOptions'  => $intentOptions,
            'channelOptions' => $channelOptions,
            'owners'         => $owners,
            'search'         => $search,
            'status'         => $status,
            'intent'         => $intent,
            'channel'        => $channel,
            'ownerId'        => $ownerId,
            'linked'         => $linked,
        ]);
    }

    public function show(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $conversation->load([
            'owner:id,name',
            'lead:id,name,email,phone,status,owner_id',
            'lead.owner:id,name',
            'customer:id,name,email,phone',
            'messages' => function ($q) {
                $q->orderBy('id');
            },
        ]);

        $owners = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $statusOptions         = ChatbotConversation::STATUS_OPTIONS;
        $intentOptions         = ChatbotConversation::INTENT_OPTIONS;
        $channelOptions        = ChatbotConversation::CHANNEL_OPTIONS;
        $conversionTypeOptions = ChatbotConversation::CONVERSION_TYPE_OPTIONS;

        return view('crm::chatbot_conversations.show', [
            'conversation'         => $conversation,
            'owners'               => $owners,
            'statusOptions'        => $statusOptions,
            'intentOptions'        => $intentOptions,
            'channelOptions'       => $channelOptions,
            'conversionTypeOptions'=> $conversionTypeOptions,
        ]);
    }

    public function assign(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $data = $request->validate([
            'owner_id' => 'nullable|integer|exists:users,id',
        ]);

        $conversation->update([
            'owner_id' => $data['owner_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.crm.chatbot-conversations.show', $conversation)
            ->with('success', 'Assegnazione conversazione aggiornata.');
    }

    public function close(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $conversation->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.crm.chatbot-conversations.show', $conversation)
            ->with('success', 'Conversazione chiusa.');
    }

    public function reopen(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $conversation->update([
            'status'    => 'open',
            'closed_at' => null,
        ]);

        return redirect()
            ->route('admin.crm.chatbot-conversations.show', $conversation)
            ->with('success', 'Conversazione riaperta.');
    }

    public function markSpam(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $conversation->update([
            'status'    => 'spam',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.crm.chatbot-conversations.show', $conversation)
            ->with('success', 'Conversazione segnata come spam.');
    }

    public function convertToLead(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        if ($conversation->lead_id) {
            return redirect()
                ->route('admin.crm.leads.edit', $conversation->lead_id)
                ->with('info', 'Questa conversazione è già collegata a un lead.');
        }

        $summaryLines = [];

        if ($conversation->source_page) {
            $summaryLines[] = 'Pagina origine: ' . $conversation->source_page;
        }

        if ($conversation->channel_label) {
            $summaryLines[] = 'Canale: ' . $conversation->channel_label;
        }

        if ($conversation->intent_label) {
            $summaryLines[] = 'Intento: ' . $conversation->intent_label;
        }

        $summaryLines[] = 'Score: ' . ((int) $conversation->score);

        $latestMessages = $conversation->messages()
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse();

        if ($latestMessages->isNotEmpty()) {
            $summaryLines[] = '';
            $summaryLines[] = 'Estratto conversazione:';

            foreach ($latestMessages as $msg) {
                $who = match ($msg->sender_type) {
                    'visitor' => 'Visitatore',
                    'ai'      => 'AI',
                    'agent'   => 'Operatore',
                    'system'  => 'Sistema',
                    default   => 'Messaggio',
                };

                $summaryLines[] = $who . ': ' . trim($msg->message);
            }
        }

        $lead = Lead::create([
            'client_id'         => $conversation->client_id,
            'customer_id'       => null,
            'name'              => $conversation->visitor_name ?: 'Lead da chatbot',
            'email'             => $conversation->visitor_email,
            'phone'             => $conversation->visitor_phone,
            'subject'           => 'Richiesta da chatbot AI',
            'message'           => implode("\n", $summaryLines),
            'source'            => 'chatbot_ai',
            'status'            => 'qualified',
            'internal_notes'    => $conversation->notes,
            'owner_id'          => $conversation->owner_id ?: $request->user()?->id,
            'last_contact_at'   => $conversation->last_message_at ?: now(),
            'next_action_at'    => now()->addDay(),
            'how_found'         => 'web',
            'how_found_other'   => null,
        ]);

        $lead->activities()->create([
            'user_id'      => $request->user()?->id,
            'type'         => 'note',
            'subject'      => 'Lead creato da conversazione chatbot',
            'body'         => implode("\n", $summaryLines),
            'contacted_at' => now(),
        ]);

        $conversation->update([
            'lead_id'          => $lead->id,
            'status'           => 'converted',
            'converted_at'     => now(),
            'conversion_type'  => 'lead',
        ]);

        return redirect()
            ->route('admin.crm.leads.edit', $lead)
            ->with('success', 'Lead creato dalla conversazione chatbot.');
    }

    public function destroy(Request $request, ChatbotConversation $conversation)
    {
        $this->ensureConversationBelongsToClient($request, $conversation);

        $conversation->delete();

        return redirect()
            ->route('admin.crm.chatbot-conversations.index')
            ->with('success', 'Conversazione eliminata.');
    }
}
