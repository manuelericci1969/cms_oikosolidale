@extends('admin.layout')

@section('title', 'Task CRM')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Task CRM ({{ auth()->user()->role }})</h1>

        <div class="row g-3" id="kanban-board"
             data-sort-url="{{ route('admin.crm.tasks.sort') }}"
             data-csrf="{{ csrf_token() }}"
        >
            @foreach($statuses as $statusKey => $statusLabel)
                @php
                    /** @var \Illuminate\Support\Collection $columnTasks */
                    $columnTasks = $tasksByStatus[$statusKey] ?? collect();
                @endphp

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>{{ $statusLabel }}</strong>
                            <span class="badge bg-secondary">{{ $columnTasks->count() }}</span>
                        </div>

                        <div class="card-body p-2">
                            <div class="kanban-column"
                                 data-status="{{ $statusKey }}">

                                @foreach($columnTasks as $task)
                                    <div class="card mb-2 kanban-task"
                                         data-task-id="{{ $task->id }}">
                                        <div class="card-body p-2">

                                            {{-- Titolo + priorità + maniglia drag --}}
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="text-muted small task-drag-handle"
                                                          title="Trascina il task">
                                                        <i class="bi bi-grip-vertical"></i>
                                                    </span>
                                                    <strong class="mb-0">{{ $task->title }}</strong>
                                                </div>
                                                @if($task->priority > 0)
                                                    <span class="badge bg-danger">P{{ $task->priority }}</span>
                                                @endif
                                            </div>

                                            {{-- Descrizione breve --}}
                                            @if($task->description)
                                                <div class="small text-muted mt-1">
                                                    {{ \Illuminate\Support\Str::limit($task->description, 80) }}
                                                </div>
                                            @endif

                                            {{-- Ultima nota + link al modal con tutte le note --}}
                                            @if(($task->notes ?? collect())->isNotEmpty())
                                                @php $lastNote = $task->notes->first(); @endphp
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-chat-left-text"></i>
                                                    {{ \Illuminate\Support\Str::limit($lastNote->note, 80) }}

                                                    <button type="button"
                                                            class="btn btn-link btn-sm p-0 ms-1 align-baseline"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#taskNotesModal"
                                                            data-task-id="{{ $task->id }}"
                                                            data-task-title="{{ $task->title }}">
                                                        Vedi tutte le note ({{ $task->notes->count() }})
                                                    </button>
                                                </div>

                                                {{-- HTML completo delle note, nascosto: lo copia il modal via JS --}}
                                                <div class="d-none" id="task-notes-{{ $task->id }}">
                                                    @foreach($task->notes as $note)
                                                        <div class="mb-2">
                                                            <div class="small text-muted">
                                                                <strong>{{ optional($note->user)->name ?? 'Sistema' }}</strong>
                                                                · {{ $note->created_at?->format('d/m/Y H:i') }}
                                                            </div>
                                                            <div>{{ $note->note }}</div>
                                                        </div>
                                                        @if(!$loop->last)
                                                            <hr class="my-1">
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Meta: scadenza + assegnatario --}}
                                            <div class="small mt-2 d-flex justify-content-between">
                                                @if($task->due_at)
                                                    <span>
                                                        <i class="bi bi-calendar-event"></i>
                                                        {{ $task->due_at->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                                @if($task->assignedTo)
                                                    <span>
                                                        <i class="bi bi-person"></i>
                                                        {{ $task->assignedTo->name }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Select per assegnare il task a un utente (admin/superadmin/agent) --}}
                                            @isset($assignableUsers)
                                                <form method="POST"
                                                      action="{{ route('admin.crm.tasks.assign', $task) }}"
                                                      class="mt-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="assigned_to_id"
                                                            class="form-select form-select-sm"
                                                            onchange="this.form.submit()">
                                                        <option value="">Non assegnato</option>
                                                        @foreach($assignableUsers as $user)
                                                            <option value="{{ $user->id }}"
                                                                @selected($task->assigned_to_id === $user->id)>
                                                                {{ $user->name }}
                                                                @if(!empty($user->role)) ({{ $user->role }}) @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @endisset

                                            {{-- Cambio rapido di stato (fallback al drag & drop) --}}
                                            <form method="POST"
                                                  action="{{ route('admin.crm.tasks.status.update', $task) }}"
                                                  class="mt-2">
                                                @csrf
                                                @method('PATCH')
                                                <div class="input-group input-group-sm">
                                                    <label class="input-group-text"
                                                           for="task-status-{{ $task->id }}">
                                                        Stato
                                                    </label>
                                                    <select name="status"
                                                            id="task-status-{{ $task->id }}"
                                                            class="form-select form-select-sm"
                                                            onchange="this.form.submit()">
                                                        @foreach($statuses as $key => $label)
                                                            <option value="{{ $key }}"
                                                                @selected($task->status === $key)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </form>

                                            {{-- Form nota veloce --}}
                                            <form method="POST"
                                                  action="{{ route('admin.crm.tasks.notes.store', $task) }}"
                                                  class="mt-2">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="text"
                                                           name="note"
                                                           class="form-control"
                                                           placeholder="Aggiungi nota...">
                                                    <button class="btn btn-outline-secondary"
                                                            type="submit"
                                                            title="Aggiungi nota">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </form>

                                            {{-- Cancella task (solo admin/superadmin) --}}
                                            @if(auth()->user()->role = 'admin')
                                                <form method="POST"
                                                      action="{{ route('admin.crm.tasks.destroy', $task) }}"
                                                      class="mt-2"
                                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo task?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                        <i class="bi bi-trash"></i> Cancella
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pulsante per aggiungere un task veloce nella colonna --}}
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary w-100 mt-2"
                                    onclick="openNewTaskModal('{{ $statusKey }}')">
                                + Nuovo task
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal "nuovo task" --}}
    <div class="modal fade" id="newTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.crm.tasks.store') }}" class="modal-content">
                @csrf
                <input type="hidden" name="status" id="newTaskStatusInput">

                <div class="modal-header">
                    <h5 class="modal-title">Nuovo task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    {{-- Assegnazione iniziale --}}
                    @isset($assignableUsers)
                        <div class="mb-3">
                            <label class="form-label">Assegnato a</label>
                            <select name="assigned_to_id" class="form-select">
                                <option value="">Non assegnato</option>
                                @foreach($assignableUsers as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                        @if(!empty($user->role)) ({{ $user->role }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endisset

                    <div class="mb-3">
                        <label class="form-label">Scadenza</label>
                        <input type="date" name="due_at" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Priorità</label>
                        <input type="number" name="priority" class="form-control" min="0" max="9" value="0">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal "tutte le note" --}}
    <div class="modal fade" id="taskNotesModal" tabindex="-1" aria-labelledby="taskNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskNotesModalLabel">Note task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-3" id="taskNotesModalTitle"></h6>
                    <div id="taskNotesContainer" class="small"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {

            const board = document.getElementById('kanban-board');
            if (!board) {
                console.error('Kanban board non trovato');
                return;
            }

            const sortUrl   = board.dataset.sortUrl;
            const csrfToken = board.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!sortUrl) {
                console.error('data-sort-url mancante su #kanban-board');
                return;
            }

            // Maniglia di drag: solo l'icona con grip è draggable
            board.querySelectorAll('.task-drag-handle').forEach(el => {
                el.setAttribute('draggable', 'true');
                el.style.cursor = 'grab';
            });

            let draggedTaskId = null;
            let draggedEl     = null;

            // ================================
            // DRAG & DROP (delegato sulla board)
            // ================================

            // dragstart SOLO dalla maniglia
            board.addEventListener('dragstart', function (event) {
                const handle = event.target.closest('.task-drag-handle');
                if (!handle) {
                    // Drag partito da altro elemento (input, select, ecc.) → ignoriamo
                    return;
                }

                const card = handle.closest('.kanban-task');
                if (!card) return;

                draggedEl     = card;
                draggedTaskId = card.dataset.taskId;

                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', draggedTaskId);
                }

                card.style.opacity = '0.6';
                console.debug('dragstart task', draggedTaskId);
            });

            board.addEventListener('dragend', function () {
                if (draggedEl) {
                    draggedEl.style.opacity = '';
                }
                draggedTaskId = null;
                draggedEl     = null;
            });

            // dragover sulle colonne
            board.addEventListener('dragover', function (event) {
                const column = event.target.closest('.kanban-column');
                if (!column) return;

                // fondamentale per permettere il drop
                event.preventDefault();

                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
            });

            // drop sulle colonne
            board.addEventListener('drop', function (event) {
                const column = event.target.closest('.kanban-column');
                if (!column) return;

                event.preventDefault();

                if (!draggedEl) {
                    const idFromEvent = event.dataTransfer?.getData('text/plain');
                    if (idFromEvent) {
                        draggedEl     = board.querySelector('.kanban-task[data-task-id="' + idFromEvent + '"]');
                        draggedTaskId = idFromEvent;
                    }
                }

                if (!draggedEl) {
                    console.warn('drop senza draggedEl');
                    return;
                }

                const status = column.dataset.status;
                if (!status) {
                    console.error('Colonna senza data-status');
                    return;
                }

                // Sposto la card DOM nella nuova colonna
                column.appendChild(draggedEl);

                // Raccolgo i task ID nell'ordine attuale della colonna
                const taskIds = Array.from(column.querySelectorAll('.kanban-task'))
                    .map(el => el.dataset.taskId);

                console.debug('drop su status', status, 'ordine', taskIds);

                // Chiamata al backend per salvare stato + sort_order
                fetch(sortUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        status: status,
                        order: taskIds,
                    }),
                }).then(async (response) => {
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Errore salvataggio sort', response.status, text);
                        alert('Errore nel salvataggio del task (HTTP ' + response.status + '). Controlla la console.');
                    } else {
                        console.debug('Sort salvato ok');
                    }
                }).catch(err => {
                    console.error('Fetch sort error', err);
                    alert('Errore di rete durante il salvataggio del task.');
                });
            });

            // ================================
            // Modal "nuovo task"
            // ================================
            window.openNewTaskModal = function (status) {
                const input = document.getElementById('newTaskStatusInput');
                const modalEl = document.getElementById('newTaskModal');
                if (!input || !modalEl) return;

                input.value = status;
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            };

            // ================================
            // Modal "tutte le note"
            // ================================
            const notesModalEl = document.getElementById('taskNotesModal');
            if (notesModalEl) {
                notesModalEl.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const taskId    = button.getAttribute('data-task-id');
                    const taskTitle = button.getAttribute('data-task-title') || '';

                    const titleEl   = document.getElementById('taskNotesModalTitle');
                    const container = document.getElementById('taskNotesContainer');

                    if (titleEl) {
                        titleEl.textContent = taskTitle;
                    }

                    const source = document.getElementById('task-notes-' + taskId);
                    if (source && container) {
                        container.innerHTML = source.innerHTML;
                    } else if (container) {
                        container.innerHTML = '<p class="text-muted">Nessuna nota disponibile per questo task.</p>';
                    }
                });
            }

        })();
    </script>
@endpush
