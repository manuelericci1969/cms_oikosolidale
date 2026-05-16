<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use App\Modules\Crm\Services\AiCallAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallAiAgentTestController extends Controller
{
    public function reply(Request $request, AiCallAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:crm_call_campaigns,id'],
            'queue_id' => ['required', 'integer', 'exists:crm_call_queue,id'],
            'call_log_id' => ['required', 'integer', 'exists:crm_call_logs,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $campaign = CallCampaign::query()->findOrFail($data['campaign_id']);
        $queueItem = CallQueue::query()->findOrFail($data['queue_id']);
        $callLog = CallLog::query()->findOrFail($data['call_log_id']);

        if ((int) $queueItem->campaign_id !== (int) $campaign->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Il queue item non appartiene alla campagna indicata.',
            ], 422);
        }

        if ((int) $callLog->campaign_id !== (int) $campaign->id || (int) $callLog->queue_id !== (int) $queueItem->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Il call log non è coerente con campagna e queue item.',
            ], 422);
        }

        if (
            !empty($callLog->business_outcome) ||
            in_array($queueItem->status, [
                \App\Modules\Crm\Models\CallQueue::STATUS_COMPLETED,
                \App\Modules\Crm\Models\CallQueue::STATUS_CALLBACK,
                \App\Modules\Crm\Models\CallQueue::STATUS_FAILED,
                \App\Modules\Crm\Models\CallQueue::STATUS_CANCELLED,
            ], true)
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Questo call log ha già un esito finale o una callback pianificata. Usa un nuovo call_log per una nuova simulazione.',
                'call_log' => $callLog->fresh([
                    'queueItem',
                    'conversationMessages',
                ]),
            ], 422);
        }

        $result = $agent->replyForCall(
            campaign: $campaign,
            queueItem: $queueItem,
            callLog: $callLog,
            userText: $data['message']
        );

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'reply' => $result['reply'] ?? null,
            'suggested_outcome' => $result['suggested_outcome'] ?? null,
            'message' => $result['message'] ?? null,
            'ai_mode' => $result['ai_mode'] ?? null,
            'call_log' => $callLog->fresh([
                'queueItem',
                'conversationMessages',
            ]),
        ]);
    }

    public function postCall(Request $request, AiCallAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:crm_call_campaigns,id'],
            'queue_id' => ['required', 'integer', 'exists:crm_call_queue,id'],
            'call_log_id' => ['required', 'integer', 'exists:crm_call_logs,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $campaign = CallCampaign::query()->findOrFail($data['campaign_id']);
        $queueItem = CallQueue::query()->findOrFail($data['queue_id']);
        $callLog = CallLog::query()->findOrFail($data['call_log_id']);

        if ((int) $queueItem->campaign_id !== (int) $campaign->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Il queue item non appartiene alla campagna indicata.',
            ], 422);
        }

        if ((int) $callLog->campaign_id !== (int) $campaign->id || (int) $callLog->queue_id !== (int) $queueItem->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Il call log non è coerente con campagna e queue item.',
            ], 422);
        }

        $result = $agent->replyForCall(
            campaign: $campaign,
            queueItem: $queueItem,
            callLog: $callLog,
            userText: $data['message']
        );

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'reply' => $result['reply'] ?? null,
            'suggested_outcome' => $result['suggested_outcome'] ?? null,
            'message' => $result['message'] ?? null,
            'ai_mode' => $result['ai_mode'] ?? null,
            'call_log' => $callLog->fresh([
                'queueItem',
                'conversationMessages',
            ]),
        ]);
    }
}
