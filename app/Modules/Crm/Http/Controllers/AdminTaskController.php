<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Crm\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTaskController extends Controller
{
    public function index(Request $request)
    {
        // Filtro opzionale per assegnatario
        $assignedTo = $request->integer('assigned_to_id');

        $query = Task::forBoard();

        if ($assignedTo) {
            $query->where('assigned_to_id', $assignedTo);
        }

        $tasks = $query->get()->groupBy('status');

        // Utenti assegnabili: admin, superadmin, agent
        $assignableUsers = User::query()
            ->whereIn('role', ['admin', 'superadmin', 'agent'])
            ->orderBy('name')
            ->get();

        return view('crm::tasks.index', [
            'statuses'        => Task::statusOptions(),
            'tasksByStatus'   => $tasks,
            'assignableUsers' => $assignableUsers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'nullable|string|in:' . implode(',', array_keys(Task::STATUSES)),
            'assigned_to_id' => 'nullable|exists:users,id',
            'due_at'         => 'nullable|date',
            'priority'       => 'nullable|integer|min:0|max:9',
            'taskable_type'  => 'nullable|string',
            'taskable_id'    => 'nullable|integer',
        ]);

        $validated['created_by_id'] = Auth::id();
        $validated['status'] ??= Task::STATUS_START;

        // sort_order alla fine della colonna
        $maxSort = Task::where('status', $validated['status'])->max('sort_order');
        $validated['sort_order'] = ($maxSort ?? 0) + 1;

        $task = Task::create($validated);

        if ($request->wantsJson()) {
            return response()->json($task->load('assignedTo'));
        }

        return redirect()
            ->route('admin.crm.tasks.index')
            ->with('success', 'Task creato correttamente.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'assigned_to_id' => 'nullable|exists:users,id',
            'due_at'         => 'nullable|date',
            'priority'       => 'nullable|integer|min:0|max:9',
        ]);

        $task->update($validated);

        if ($request->wantsJson()) {
            return response()->json($task->fresh()->load('assignedTo'));
        }

        return redirect()
            ->route('admin.crm.tasks.index')
            ->with('success', 'Task aggiornato.');
    }

    public function destroy(Request $request, Task $task)
    {
        // A questo punto sei già passato dal middleware 'role:admin,superadmin'
        // quindi l'utente è autorizzato.

        $task->delete();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()
            ->route('admin.crm.tasks.index')
            ->with('success', 'Task eliminato.');
    }


    /**
     * Cambia solo lo stato (usato dal select "Stato" sulla card)
     */
    public function updateStatus(Request $request, Task $task)
    {
        // Prendiamo gli stati ammessi dalle options del modello
        $allowedStatuses = array_keys(Task::statusOptions());

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', $allowedStatuses),
        ]);

        $oldStatus = $task->status;
        $newStatus = $validated['status'];

        // Se non cambia nulla, non facciamo casino
        if ($oldStatus === $newStatus) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'ok',
                    'task'   => $task->fresh()->load('assignedTo', 'notes.user'),
                ]);
            }

            return redirect()->route('admin.crm.tasks.index');
        }

        // Metto il task in fondo alla nuova colonna
        $maxSort = Task::where('status', $newStatus)->max('sort_order');
        $task->status     = $newStatus;
        $task->sort_order = ($maxSort ?? 0) + 1;
        $task->save();

        // Nota automatica sul cambio stato
        $statusOptions = Task::statusOptions();
        $oldLabel = $statusOptions[$oldStatus] ?? $oldStatus;
        $newLabel = $statusOptions[$newStatus] ?? $newStatus;

        $task->notes()->create([
            'user_id' => Auth::id(),
            'note'    => sprintf('Stato cambiato da "%s" a "%s"', $oldLabel, $newLabel),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'task'   => $task->fresh()->load('assignedTo', 'notes.user'),
            ]);
        }

        // 🔹 Da form HTML (select sulla card): torno alla board
        return redirect()->route('admin.crm.tasks.index');
    }


    /**
     * Drag & drop: aggiorna ordinamento e stato dei task nella colonna.
     * Frontend manda:
     *   status: 'start' | 'elaborazione' | 'concluso' | 'verificato'
     *   order: [taskId1, taskId2, ...]
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'status'  => 'required|string|in:' . implode(',', array_keys(Task::STATUSES)),
            'order'   => 'required|array',
            'order.*' => 'integer|exists:crm_tasks,id',
        ]);

        $newStatus = $validated['status'];

        foreach ($validated['order'] as $index => $taskId) {
            /** @var Task|null $task */
            $task = Task::find($taskId);
            if (! $task) {
                continue;
            }

            $oldStatus = $task->status;
            $task->status     = $newStatus;
            $task->sort_order = $index + 1;
            $task->save();

            // Se il task è stato spostato in un'altra colonna, logghiamo una nota
            if ($oldStatus !== $newStatus) {
                $oldLabel = Task::STATUSES[$oldStatus] ?? $oldStatus;
                $newLabel = Task::STATUSES[$newStatus] ?? $newStatus;

                $task->notes()->create([
                    'user_id' => Auth::id(),
                    'note'    => sprintf('Stato cambiato da "%s" a "%s"', $oldLabel, $newLabel),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Salva una nota manuale sul task
     */
    public function storeNote(Request $request, Task $task)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $task->notes()->create([
            'user_id' => Auth::id(),
            'note'    => $validated['note'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'note'   => $task->notes()->first(),
            ]);
        }

        return back()->with('success', 'Nota aggiunta al task.');
    }

    /**
     * Assegna il task a un utente (admin/superadmin/agent)
     */
    public function assignUser(Request $request, Task $task)
    {
        $validated = $request->validate([
            'assigned_to_id' => 'nullable|exists:users,id',
        ]);

        $task->assigned_to_id = $validated['assigned_to_id'] ?? null;
        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'task'   => $task->fresh()->load('assignedTo'),
            ]);
        }

        return back()->with('success', 'Assegnazione task aggiornata.');
    }

}
