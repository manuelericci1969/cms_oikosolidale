@extends('admin.layout')

@section('title', 'I miei task')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">I miei task</h1>

        <div class="row g-3">
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
                            <ul class="list-unstyled mb-0">
                                @forelse($columnTasks as $task)
                                    <li class="mb-2 border rounded p-2">
                                        <div class="small text-muted">#{{ $task->id }}</div>
                                        <div class="fw-semibold">{{ $task->title }}</div>

                                        {{-- Descrizione breve, se presente --}}
                                        @if($task->description)
                                            <div class="small text-muted mt-1">
                                                {{ \Illuminate\Support\Str::limit($task->description, 80) }}
                                            </div>
                                        @endif

                                        {{-- Scadenza, se presente --}}
                                        @if(!empty($task->due_at))
                                            <div class="small mt-1">
                                                <i class="bi bi-calendar-event"></i>
                                                {{ \Illuminate\Support\Carbon::parse($task->due_at)->format('d/m/Y') }}
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

                                        {{-- Cambio stato --}}
                                        <form method="POST"
                                              action="{{ route('agent.crm.tasks.status.update', $task) }}"
                                              class="mt-2">
                                            @csrf
                                            @method('PATCH')

                                            <div class="input-group input-group-sm">
                                                <label class="input-group-text" for="task-status-{{ $task->id }}">
                                                    Stato
                                                </label>
                                                <select name="status"
                                                        id="task-status-{{ $task->id }}"
                                                        class="form-select form-select-sm"
                                                        onchange="this.form.submit()">
                                                    @foreach($statuses as $sKey => $sLabel)
                                                        <option value="{{ $sKey }}"
                                                            @selected($task->status === $sKey)>
                                                            {{ $sLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>

                                        {{-- Aggiungi nota --}}
                                        <form method="POST"
                                              action="{{ route('agent.crm.tasks.notes.store', $task) }}"
                                              class="mt-2">
                                            @csrf
                                            <div class="input-group input-group-sm">
                                                <input type="text"
                                                       name="note"
                                                       class="form-control form-control-sm"
                                                       placeholder="Aggiungi nota veloce…">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </li>
                                @empty
                                    <li class="text-muted small">
                                        Nessun task in questa colonna.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal "tutte le note" (stesso concetto dell'admin, ma solo per lettura) --}}
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
        document.addEventListener('DOMContentLoaded', function () {
            const notesModalEl = document.getElementById('taskNotesModal');
            if (!notesModalEl) return;

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
        });
    </script>
@endpush
