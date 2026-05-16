@extends('admin.layout')

@section('title', 'Chatbot AI CRM')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            Chatbot AI
            <small class="text-muted">({{ $conversations->total() }})</small>
        </h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.crm.chatbot-conversations.index') }}" class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label mb-1">Cerca</label>
                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Nome, email, telefono, sessione, pagina..."
                           value="{{ $search ?? '' }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($status ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Intento</label>
                    <select name="intent" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($intentOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($intent ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Canale</label>
                    <select name="channel" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($channelOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($channel ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Assegnato a</label>
                    <select name="owner_id" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string)($ownerId ?? '') === (string)$owner->id)>
                                {{ $owner->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Lead collegato</label>
                    <select name="linked" class="form-select">
                        <option value="">Tutti</option>
                        <option value="yes" @selected(($linked ?? '') === 'yes')>Solo collegati</option>
                        <option value="no" @selected(($linked ?? '') === 'no')>Solo non collegati</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100 mb-1">
                        <i class="bi bi-search"></i> Filtra
                    </button>

                    <a href="{{ route('admin.crm.chatbot-conversations.index') }}"
                       class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>Visitatore</th>
                        <th>Canale / Intento</th>
                        <th>Workflow</th>
                        <th>Ultimo messaggio</th>
                        <th>Lead</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($conversations as $conversation)
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $conversation->visitor_display_name }}
                                </div>

                                <div class="small text-muted">
                                    Sessione: {{ $conversation->session_id }}
                                </div>

                                @if($conversation->visitor_company)
                                    <div class="small text-muted">
                                        Azienda: {{ $conversation->visitor_company }}
                                    </div>
                                @endif

                                @if($conversation->visitor_email)
                                    <div class="small mt-1">
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:{{ $conversation->visitor_email }}">
                                            {{ $conversation->visitor_email }}
                                        </a>
                                    </div>
                                @endif

                                @if($conversation->visitor_phone)
                                    <div class="small">
                                        <i class="bi bi-telephone"></i>
                                        {{ $conversation->visitor_phone }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div>
                                    <span class="small text-muted">Canale</span>
                                    <div>{{ $conversation->channel_label ?: '—' }}</div>
                                </div>

                                <div class="mt-1">
                                    <span class="small text-muted">Intento</span>
                                    <div>{{ $conversation->intent_label ?: '—' }}</div>
                                </div>

                                @if($conversation->source_page)
                                    <div class="mt-1">
                                        <span class="small text-muted">Pagina</span>
                                        <div class="small text-break">{{ $conversation->source_page }}</div>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="mb-1">
                                    <span class="badge {{ $conversation->status_badge_class }}">
                                        {{ $conversation->status_label }}
                                    </span>
                                </div>

                                <div class="mb-1">
                                    <span class="badge {{ $conversation->score_badge_class }}">
                                        Score: {{ $conversation->score }}
                                    </span>
                                </div>

                                <div class="small text-muted">
                                    Assegnato: {{ $conversation->owner?->name ?? 'Non assegnato' }}
                                </div>

                                <div class="small text-muted">
                                    Ultima attività:
                                    {{ $conversation->last_message_at?->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </td>

                            <td>
                                <div class="small">
                                    {{ $conversation->last_message_excerpt ?: 'Nessun messaggio' }}
                                </div>
                            </td>

                            <td>
                                @if($conversation->lead)
                                    <div class="fw-semibold">{{ $conversation->lead->name }}</div>

                                    <div class="small text-muted">
                                        {{ $conversation->lead->email ?: '—' }}
                                    </div>

                                    <div class="mt-1">
                                        <a href="{{ route('admin.crm.leads.edit', $conversation->lead) }}"
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-box-arrow-up-right"></i> Apri lead
                                        </a>
                                    </div>
                                @else
                                    <span class="text-muted small">Non collegata</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.crm.chatbot-conversations.show', $conversation) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <form action="{{ route('admin.crm.chatbot-conversations.destroy', $conversation) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Eliminare questa conversazione?');">
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
                            <td colspan="6" class="text-center text-muted py-4">
                                Nessuna conversazione registrata
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>
        </div>

        @if($conversations->hasPages())
            <div class="card-footer">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>

@endsection
