@extends('admin.layout')

@section('title', 'Conversazione Chatbot #' . $conversation->id)

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            Conversazione Chatbot #{{ $conversation->id }}
        </h1>

        <div class="d-flex gap-2 flex-wrap">
            @if(!$conversation->lead_id)
                <form action="{{ route('admin.crm.chatbot-conversations.convert_to_lead', $conversation) }}"
                      method="POST"
                      onsubmit="return confirm('Creare un lead da questa conversazione?');">
                    @csrf
                    <button class="btn btn-success btn-sm">
                        <i class="bi bi-person-plus"></i> Converti in lead
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.crm.chatbot-conversations.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna alla lista
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">

            <div class="card mb-3">
                <div class="card-header">Cronologia messaggi</div>
                <div class="card-body">
                    @if($conversation->messages->count())
                        <div class="d-flex flex-column gap-3">
                            @foreach($conversation->messages as $message)
                                <div class="border rounded-3 p-3 {{ $message->bubble_class }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="fw-semibold">
                                            <i class="bi {{ $message->sender_icon }}"></i>
                                            {{ $message->sender_type_label }}
                                            @if($message->message_type_label)
                                                <span class="badge bg-light text-dark border ms-2">
                                                    {{ $message->message_type_label }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="small text-muted text-end">
                                            {{ $message->created_at?->format('d/m/Y H:i') }}
                                            @if($message->model)
                                                <div>Model: {{ $message->model }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="white-space: pre-wrap;">{{ $message->message }}</div>

                                    @if(($message->token_usage_input || $message->token_usage_output))
                                        <div class="small text-muted mt-2">
                                            Token in: {{ $message->token_usage_input ?? '—' }}
                                            |
                                            Token out: {{ $message->token_usage_output ?? '—' }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">Nessun messaggio registrato.</p>
                    @endif
                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header">Dettagli conversazione</div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Stato</strong><br>
                        <span class="badge {{ $conversation->status_badge_class }}">
                            {{ $conversation->status_label }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <strong>Score</strong><br>
                        <span class="badge {{ $conversation->score_badge_class }}">
                            {{ $conversation->score }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <strong>Canale</strong><br>
                        {{ $conversation->channel_label ?: '—' }}
                    </div>

                    <div class="mb-3">
                        <strong>Intento</strong><br>
                        {{ $conversation->intent_label ?: '—' }}
                    </div>

                    <div class="mb-3">
                        <strong>Sessione</strong><br>
                        <code>{{ $conversation->session_id }}</code>
                    </div>

                    <div class="mb-3">
                        <strong>Pagina origine</strong><br>
                        <span class="small text-break">{{ $conversation->source_page ?: '—' }}</span>
                    </div>

                    <div class="mb-3">
                        <strong>Ultimo messaggio</strong><br>
                        {{ $conversation->last_message_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    <div class="mb-3">
                        <strong>Creata il</strong><br>
                        {{ $conversation->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    @if($conversation->closed_at)
                        <div class="mb-3">
                            <strong>Chiusa il</strong><br>
                            {{ $conversation->closed_at->format('d/m/Y H:i') }}
                        </div>
                    @endif

                    @if($conversation->converted_at)
                        <div class="mb-3">
                            <strong>Convertita il</strong><br>
                            {{ $conversation->converted_at->format('d/m/Y H:i') }}
                        </div>
                    @endif

                    @if($conversation->conversion_type_label)
                        <div class="mb-3">
                            <strong>Tipo conversione</strong><br>
                            {{ $conversation->conversion_type_label }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Visitatore</div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Nome</strong><br>
                        {{ $conversation->visitor_name ?: '—' }}
                    </div>

                    <div class="mb-3">
                        <strong>Email</strong><br>
                        @if($conversation->visitor_email)
                            <a href="mailto:{{ $conversation->visitor_email }}">
                                {{ $conversation->visitor_email }}
                            </a>
                        @else
                            —
                        @endif
                    </div>

                    <div class="mb-3">
                        <strong>Telefono</strong><br>
                        {{ $conversation->visitor_phone ?: '—' }}
                    </div>

                    <div class="mb-3">
                        <strong>Azienda</strong><br>
                        {{ $conversation->visitor_company ?: '—' }}
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Assegnazione</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.chatbot-conversations.assign', $conversation) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Assegna a</label>
                            <select name="owner_id" class="form-select">
                                <option value="">-- Nessun assegnatario --</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int)$conversation->owner_id === (int)$owner->id)>
                                        {{ $owner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Salva assegnazione
                        </button>
                    </form>
                </div>
            </div>

            @if($conversation->lead)
                <div class="card mb-3">
                    <div class="card-header">Lead collegato</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>{{ $conversation->lead->name }}</strong>
                        </div>

                        <div class="small text-muted mb-2">
                            Stato: {{ $conversation->lead->status_label ?? $conversation->lead->status }}
                        </div>

                        @if($conversation->lead->email)
                            <div class="small mb-1">{{ $conversation->lead->email }}</div>
                        @endif

                        @if($conversation->lead->phone)
                            <div class="small mb-3">{{ $conversation->lead->phone }}</div>
                        @endif

                        <a href="{{ route('admin.crm.leads.edit', $conversation->lead) }}"
                           class="btn btn-outline-success btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right"></i> Apri lead
                        </a>
                    </div>
                </div>
            @endif

            @if($conversation->customer)
                <div class="card mb-3">
                    <div class="card-header">Cliente collegato</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>{{ $conversation->customer->name }}</strong>
                        </div>

                        @if($conversation->customer->email)
                            <div class="small mb-1">{{ $conversation->customer->email }}</div>
                        @endif

                        @if($conversation->customer->phone)
                            <div class="small">{{ $conversation->customer->phone }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($conversation->notes)
                <div class="card mb-3">
                    <div class="card-header">Note interne</div>
                    <div class="card-body">
                        <div style="white-space: pre-wrap;">{{ $conversation->notes }}</div>
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-header">Azioni rapide</div>
                <div class="card-body d-grid gap-2">

                    @if($conversation->status !== 'closed')
                        <form action="{{ route('admin.crm.chatbot-conversations.close', $conversation) }}" method="POST">
                            @csrf
                            <button class="btn btn-outline-secondary w-100">
                                <i class="bi bi-lock"></i> Chiudi conversazione
                            </button>
                        </form>
                    @endif

                    @if($conversation->status === 'closed' || $conversation->status === 'spam')
                        <form action="{{ route('admin.crm.chatbot-conversations.reopen', $conversation) }}" method="POST">
                            @csrf
                            <button class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Riapri conversazione
                            </button>
                        </form>
                    @endif

                    @if($conversation->status !== 'spam')
                        <form action="{{ route('admin.crm.chatbot-conversations.mark_spam', $conversation) }}"
                              method="POST"
                              onsubmit="return confirm('Segnare la conversazione come spam?');">
                            @csrf
                            <button class="btn btn-outline-warning w-100">
                                <i class="bi bi-exclamation-triangle"></i> Segna come spam
                            </button>
                        </form>
                    @endif

                    @if(!$conversation->lead_id)
                        <form action="{{ route('admin.crm.chatbot-conversations.convert_to_lead', $conversation) }}"
                              method="POST"
                              onsubmit="return confirm('Creare un lead da questa conversazione?');">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="bi bi-person-plus"></i> Converti in lead
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.crm.chatbot-conversations.destroy', $conversation) }}"
                          method="POST"
                          onsubmit="return confirm('Eliminare definitivamente questa conversazione?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Elimina conversazione
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection
