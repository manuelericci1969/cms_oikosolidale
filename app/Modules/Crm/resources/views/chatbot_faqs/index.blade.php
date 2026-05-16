@extends('admin.layout')

@section('title', 'FAQ Chatbot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">FAQ Chatbot</h1>
        <a href="{{ route('admin.crm.chatbot-faqs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuova FAQ
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-3">
        <div class="input-group">
            <input
                type="text"
                name="q"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cerca per domanda, keyword, risposta o intent"
            >
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Pattern</th>
                        <th>Intent</th>
                        <th>Prodotto</th>
                        <th>Priorità</th>
                        <th>Utilizzi</th>
                        <th>Stato</th>
                        <th>Risposta</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($faqs as $faq)
                        <tr>
                            <td>
                                <strong>{{ $faq->question_pattern }}</strong>
                                @if($faq->keywords)
                                    <div class="small text-muted mt-1">
                                        {{ \Illuminate\Support\Str::limit($faq->keywords, 80) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($faq->intent)
                                    <span class="badge bg-info text-dark">{{ $faq->intent }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                {{ $faq->product?->name ?: '—' }}
                            </td>

                            <td>{{ (int) $faq->priority }}</td>

                            <td>{{ (int) $faq->times_used }}</td>

                            <td>
                                @if($faq->is_active)
                                    <span class="badge bg-success">Attiva</span>
                                @else
                                    <span class="badge bg-secondary">Disattiva</span>
                                @endif
                            </td>

                            <td>
                                <div style="max-width: 360px;">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 140) }}
                                </div>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.crm.chatbot-faqs.edit', $faq) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.crm.chatbot-faqs.destroy', $faq) }}"
                                      method="post"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare questa FAQ?');">
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
                                Nessuna FAQ trovata.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($faqs->hasPages())
            <div class="card-footer">
                {{ $faqs->links() }}
            </div>
        @endif
    </div>
@endsection
