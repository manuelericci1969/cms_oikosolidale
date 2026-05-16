@extends('admin.layout')

@section('title', 'Domande Chatbot non riconosciute')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Domande Chatbot non riconosciute</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-3">
        <div class="row g-2">
            <div class="col-md-8">
                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? '' }}"
                    class="form-control"
                    placeholder="Cerca per domanda, intent o pagina"
                >
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tutti gli stati</option>
                    <option value="new" {{ ($status ?? '') === 'new' ? 'selected' : '' }}>Nuove</option>
                    <option value="reviewed" {{ ($status ?? '') === 'reviewed' ? 'selected' : '' }}>In revisione</option>
                    <option value="resolved" {{ ($status ?? '') === 'resolved' ? 'selected' : '' }}>Risolte</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" type="submit">
                    <i class="bi bi-search"></i> Filtra
                </button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Domanda</th>
                        <th>Intent</th>
                        <th>Pagina</th>
                        <th>Conversazione</th>
                        <th>Stato</th>
                        <th>Data</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($questions as $question)
                        <tr>
                            <td>{{ $question->id }}</td>

                            <td style="min-width: 280px;">
                                {{ $question->question }}
                            </td>

                            <td>
                                @if($question->intent_detected)
                                    <span class="badge bg-info text-dark">
                                        {{ $question->intent_detected }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td style="max-width: 260px;">
                                @if($question->source_page)
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($question->source_page, 80) }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($question->conversation_id)
                                    #{{ $question->conversation_id }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $badgeClass = match($question->status) {
                                        'new' => 'bg-danger',
                                        'reviewed' => 'bg-warning text-dark',
                                        'resolved' => 'bg-success',
                                        default => 'bg-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $badgeClass }}">
                                    {{ $question->status }}
                                </span>
                            </td>

                            <td>
                                <small>{{ optional($question->created_at)->format('d/m/Y H:i') }}</small>
                            </td>

                            <td class="text-end" style="min-width: 290px;">
                                <a href="{{ route('admin.crm.chatbot-faqs.create', [
            'question_pattern' => $question->question,
            'intent' => $question->intent_detected,
            'unknown_question_id'=> $question->id,
        ]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Crea FAQ
                                </a>

                                <form action="{{ route('admin.crm.chatbot-unknown-questions.status.update', $question) }}"
                                      method="post"
                                      class="d-inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="reviewed">
                                    <button class="btn btn-sm btn-outline-warning">
                                        In revisione
                                    </button>
                                </form>

                                <form action="{{ route('admin.crm.chatbot-unknown-questions.status.update', $question) }}"
                                      method="post"
                                      class="d-inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button class="btn btn-sm btn-outline-success">
                                        Risolta
                                    </button>
                                </form>

                                <form action="{{ route('admin.crm.chatbot-unknown-questions.destroy', $question) }}"
                                      method="post"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare questa domanda?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Nessuna domanda non riconosciuta trovata.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($questions->hasPages())
            <div class="card-footer">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
@endsection
