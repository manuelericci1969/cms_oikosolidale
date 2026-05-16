<?php

namespace App\Modules\Crm\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentTaskController extends Controller
{
    /**
     * Lista/board dei task assegnati all'agente.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $tasks = Task::forBoard()
            ->where('assigned_to_id', $userId)
            ->get()
            ->groupBy('status');

        return view('crm::agent.tasks.index', [
            'statuses'      => Task::statusOptions(),
            'tasksByStatus' => $tasks,
        ]);
    }

    /**
     * Gli agenti NON possono creare nuovi task.
     */
    public function store(Request $request)
    {
        abort(403, 'Gli agenti non sono autorizzati a creare task.');
    }

    /**
     * Gli agenti NON possono modificare i dettagli del task
     * (titolo, descrizione, scadenza, ecc.).
     */
    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        abort(403, 'Gli agenti non sono autorizzati a modificare i dettagli del task.');
    }

    /**
     * Gli agenti NON possono eliminare i task.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        abort(403, 'Gli agenti non sono autorizzati a eliminare i task.');
    }

    /**
     * Gli agenti NON possono riordinare i task (drag & drop sort).
     */
    public function sort(Request $request)
    {
        abort(403, 'Gli agenti non sono autorizzati a riordinare i task.');
    }

    /**
     * Gli agenti POSSONO cambiare SOLO lo stato dei propri task.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        // stessi stati ammessi dell'admin
        $allowedStatuses = array_keys(Task::statusOptions());

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', $allowedStatuses),
        ]);

        $oldStatus = $task->status;
        $newStatus = $validated['status'];

        // Se non cambia nulla, torniamo semplicemente alla board
        if ($oldStatus === $newStatus) {
            return redirect()->route('agent.crm.tasks.index');
        }

        // Metto il task in fondo alla nuova colonna (come admin)
        $maxSort = Task::where('status', $newStatus)
            ->where('assigned_to_id', $task->assigned_to_id)
            ->max('sort_order');

        $task->status     = $newStatus;
        $task->sort_order = ($maxSort ?? 0) + 1;
        $task->save();

        // Nota automatica sul cambio stato (come admin)
        $statusOptions = Task::statusOptions();
        $oldLabel = $statusOptions[$oldStatus] ?? $oldStatus;
        $newLabel = $statusOptions[$newStatus] ?? $newStatus;

        $task->notes()->create([
            'user_id' => Auth::id(),
            'note'    => sprintf('Stato cambiato da "%s" a "%s"', $oldLabel, $newLabel),
        ]);

        // IMPORTANTE: niente JSON, si torna alla pagina HTML
        return redirect()->route('agent.crm.tasks.index');
    }

    /**
     * Gli agenti POSSONO aggiungere note ai propri task.
     */
    public function storeNote(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $task->notes()->create([
            'user_id' => Auth::id(),
            'note'    => $validated['note'],
        ]);

        // Anche qui: niente JSON per i form HTML, semplice redirect indietro
        return redirect()
            ->back()
            ->with('success', 'Nota aggiunta al task.');
    }

    /**
     * L'agente può agire SOLO sui task assegnati a lui.
     */
    protected function authorizeTask(Task $task): void
    {
        if ((int) $task->assigned_to_id !== (int) Auth::id()) {
            abort(403, 'Non sei autorizzato a modificare questo task.');
        }
    }
}
