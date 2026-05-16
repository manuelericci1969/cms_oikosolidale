<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// CRM
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\Appointment;
use App\Modules\Crm\Models\ChatbotConversation;

class DashboardController extends Controller
{
    protected function clientId(Request $request): int
    {
        // TODO: multi-tenant (per ora fisso)
        return 1;
    }

    public function index(Request $request)
    {
        $user     = $request->user();
        $isAdmin  = $user && method_exists($user, 'isAdmin') ? (bool) $user->isAdmin() : false;
        $clientId = $this->clientId($request);

        // RANGE grafici
        $days = 14;
        $to   = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        $labels = [];
        $dateIndex = [];

        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $dateIndex[$key] = $i;
        }

        /*
        |--------------------------------------------------------------------------
        | LEADS
        |--------------------------------------------------------------------------
        */

        $leadBase = Lead::query()->where('client_id', $clientId);

        if (!$isAdmin && $user) {
            $leadBase->where('owner_id', $user->id);
        }

        $leadsTotal = (clone $leadBase)->count();
        $leadsNew   = (clone $leadBase)->where('status', 'new')->count();

        $leadsOpen = (clone $leadBase)
            ->whereIn('status', ['new', 'contacted', 'qualified', 'proposal'])
            ->count();

        $leadsWon  = (clone $leadBase)->where('status', 'won')->count();
        $leadsLost = (clone $leadBase)->where('status', 'lost')->count();

        $leadsUnassigned = $isAdmin
            ? Lead::where('client_id', $clientId)->whereNull('owner_id')->count()
            : 0;

        $assignedToMe = ($user)
            ? Lead::where('client_id', $clientId)
                ->where('owner_id', $user->id)
                ->whereIn('status', ['new', 'contacted', 'qualified', 'proposal'])
                ->count()
            : 0;

        $nextActionsOverdue = (clone $leadBase)
            ->whereNotNull('next_action_at')
            ->where('next_action_at', '<', now())
            ->whereNotIn('status', ['won', 'lost', 'archived'])
            ->count();

        $leadsByStatus = (clone $leadBase)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $leadDaily = array_fill(0, $days, 0);

        $leadDailyRows = (clone $leadBase)
            ->whereDate('created_at', '>=', $from->format('Y-m-d'))
            ->whereDate('created_at', '<=', $to->format('Y-m-d'))
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        foreach ($leadDailyRows as $r) {
            $k = (string) $r->d;
            if (isset($dateIndex[$k])) {
                $leadDaily[$dateIndex[$k]] = (int) $r->c;
            }
        }

        $latestLeads = (clone $leadBase)
            ->with(['customer', 'owner'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | APPOINTMENTS
        |--------------------------------------------------------------------------
        */

        $apptBase = Appointment::query()->where('client_id', $clientId);

        if (!$isAdmin && $user) {
            $apptBase->where('user_id', $user->id);
        }

        $appointmentsNext7 = (clone $apptBase)
            ->whereNotNull('start_at')
            ->whereBetween('start_at', [now(), now()->copy()->addDays(7)])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PAGES
        |--------------------------------------------------------------------------
        */

        $pagesTotal     = Page::count();
        $pagesPublished = Page::where('status', 'published')->count();
        $pagesDraft     = Page::where('status', 'draft')->count();
        $pagesArchived  = Page::where('status', 'archived')->count();

        $pagesMine = $user ? Page::where('created_by', $user->id)->count() : 0;

        $pagesDaily = array_fill(0, $days, 0);

        $pagesDailyRows = Page::query()
            ->whereDate('created_at', '>=', $from->format('Y-m-d'))
            ->whereDate('created_at', '<=', $to->format('Y-m-d'))
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        foreach ($pagesDailyRows as $r) {
            $k = (string) $r->d;
            if (isset($dateIndex[$k])) {
                $pagesDaily[$dateIndex[$k]] = (int) $r->c;
            }
        }

        $latestPages = Page::query()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | CHATBOT CONVERSATIONS
        |--------------------------------------------------------------------------
        */

        $chatbotBase = ChatbotConversation::query()
            ->where('client_id', $clientId);

        if (!$isAdmin && $user) {
            $chatbotBase->where('owner_id', $user->id);
        }

        $chatbotTotal = (clone $chatbotBase)->count();

        $chatbotOpen = (clone $chatbotBase)
            ->where('status', 'open')
            ->count();

        $chatbotQualified = (clone $chatbotBase)
            ->where('status', 'qualified')
            ->count();

        $chatbotConverted = (clone $chatbotBase)
            ->where('status', 'converted')
            ->count();

        $chatbotSpam = (clone $chatbotBase)
            ->where('status', 'spam')
            ->count();

        $chatbotUnassigned = $isAdmin
            ? ChatbotConversation::query()
                ->where('client_id', $clientId)
                ->whereNull('owner_id')
                ->count()
            : 0;

        $latestChatbotConversations = (clone $chatbotBase)
            ->with([
                'owner:id,name',
                'lead:id,name,email,phone',
            ])
            ->orderByRaw('CASE WHEN last_message_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'isAdmin',

            // leads
            'leadsTotal',
            'leadsNew',
            'leadsOpen',
            'leadsWon',
            'leadsLost',
            'leadsUnassigned',
            'assignedToMe',
            'nextActionsOverdue',

            // appointments
            'appointmentsNext7',

            // pages
            'pagesTotal',
            'pagesPublished',
            'pagesDraft',
            'pagesArchived',
            'pagesMine',

            // latest lists
            'latestLeads',
            'latestPages',

            // charts
            'labels',
            'leadDaily',
            'pagesDaily',
            'leadsByStatus',

            // chatbot
            'chatbotTotal',
            'chatbotOpen',
            'chatbotQualified',
            'chatbotConverted',
            'chatbotSpam',
            'chatbotUnassigned',
            'latestChatbotConversations'
        ));
    }
}
