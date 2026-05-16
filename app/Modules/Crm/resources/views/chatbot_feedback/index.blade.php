@extends('admin.layout')

@section('title', 'Feedback Chatbot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Feedback Chatbot</h1>
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
                    placeholder="Cerca per note o sessione"
                >
            </div>

            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Tutti</option>
                    <option value="positive" {{ ($type ?? '') === 'positive' ? 'selected' : '' }}>Solo 👍</option>
                    <option value="negative" {{ ($type ?? '') === 'negative' ? 'selected' : '' }}>Solo 👎</option>
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
                        <th>Feedback</th>
                        <th>Conversazione</th>
                        <th>Messaggio</th>
                        <th>Note</th>
                        <th>Data</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($feedbacks as $feedback)
                        <tr>
                            <td>{{ $feedback->id }}</td>

                            <td>
                                @if($feedback->is_helpful)
                                    <span class="badge bg-success">👍 Utile</span>
                                @else
                                    <span class="badge bg-danger">👎 Non utile</span>
                                @endif
                            </td>

                            <td>
                                @if($feedback->conversation_id)
                                    #{{ $feedback->conversation_id }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($feedback->message_id)
                                    #{{ $feedback->message_id }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td style="max-width: 320px;">
                                {{ $feedback->notes ?: '—' }}
                            </td>

                            <td>
                                <small>{{ optional($feedback->created_at)->format('d/m/Y H:i') }}</small>
                            </td>

                            <td class="text-end">
                                <form action="{{ route('admin.crm.chatbot-feedback.destroy', $feedback) }}"
                                      method="post"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare questo feedback?');">
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
                            <td colspan="7" class="text-center text-muted py-4">
                                Nessun feedback trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($feedbacks->hasPages())
            <div class="card-footer">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
@endsection
