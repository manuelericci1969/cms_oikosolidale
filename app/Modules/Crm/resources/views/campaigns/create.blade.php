{{-- app/Modules/Crm/resources/views/campaigns/create.blade.php --}}
@extends('admin.layout')

@section('title', 'Nuova campagna')

@section('content')
    {{-- CSS del builder (toolbar ecc.) --}}
    <link rel="stylesheet" href="{{ asset('pb/pb.css') }}">

    <style>
        /* Immagini nell’editor delle campagne sempre responsive */
        #campaignRichtextEditor img {
            max-width: 100%;
            height: auto;
        }

        /* Evidenzia l'immagine selezionata */
        .pb-img-selected {
            outline: 2px solid #0d6efd;
            outline-offset: 2px;
        }

        /* Pannello per la modifica delle immagini */
        .pb-img-panel {
            position: absolute;
            z-index: 1080;
            border-radius: 0.5rem;
            background: #fff;
        }
        .pb-img-panel.pb-img-panel--visible {
            display: block;
        }
        .pb-img-panel:not(.pb-img-panel--visible) {
            display: none;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Nuova campagna</h1>
        <a href="{{ route('admin.crm.campaigns.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Torna alle campagne
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.crm.campaigns.store') }}">
        @csrf

        <div class="row">
            {{-- Colonna sinistra: contenuto campagna --}}
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">Dettagli campagna</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Nome interno *</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="{{ old('name') }}"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Oggetto *</label>
                            <input
                                type="text"
                                name="subject"
                                class="form-control"
                                value="{{ old('subject') }}"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preheader</label>
                            <input
                                type="text"
                                name="preheader"
                                class="form-control"
                                value="{{ old('preheader') }}"
                            >
                            <div class="form-text">
                                Testo breve che molti client mostrano dopo l’oggetto.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenuto HTML *</label>

                            {{-- textarea "vera" che verrà inviata al server (nascosta) --}}
                            <textarea
                                name="html_body"
                                id="campaignHtmlTextarea"
                                class="d-none"
                                rows="10"
                                required
                            >{{ old('html_body') }}</textarea>

                            {{-- Toolbar stile Page Builder --}}
                            <div id="campaignRichtextToolbar" class="pb-toolbar pb-richtext-toolbar mb-2"></div>

                            {{-- Editor WYSIWYG --}}
                            <div
                                id="campaignRichtextEditor"
                                class="pb-richtext-editor form-control"
                                contenteditable="true"
                                style="min-height: 260px;"
                            ></div>

                            {{-- Editor HTML grezzo (modalità codice) --}}
                            <textarea
                                id="campaignHtmlSource"
                                class="pb-richtext-html form-control form-control-sm mt-2 d-none"
                                style="font-family: monospace; min-height: 160px;"
                            ></textarea>

                            <div class="form-text mt-1">
                                Puoi usare placeholder tipo
                                <code>&lbrace;&lbrace;name&rbrace;&rbrace;</code>
                                e
                                <code>&lbrace;&lbrace;email&rbrace;&rbrace;</code>.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenuto testuale (opzionale)</label>
                            <textarea
                                name="text_body"
                                rows="5"
                                class="form-control"
                            >{{ old('text_body') }}</textarea>
                            <div class="form-text">
                                Usato come versione "solo testo" in alcuni client email.
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Colonna destra: mittente --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Mittente</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Da (nome)</label>
                            <input
                                type="text"
                                name="from_name"
                                class="form-control"
                                value="{{ old('from_name', $defaultFromName ?? config('mail.from.name')) }}"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Da (email)</label>
                            <input
                                type="email"
                                name="from_email"
                                class="form-control"
                                value="{{ old('from_email', $defaultFromEmail ?? config('mail.from.address')) }}"
                            >
                            <div class="form-text">
                                Se vuoto, verranno usati i dati predefiniti di sistema.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reply-to</label>
                            <input
                                type="email"
                                name="reply_to_email"
                                class="form-control"
                                value="{{ old('reply_to_email') }}"
                            >
                            <div class="form-text">
                                Indirizzo a cui arrivano le risposte (opzionale).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button class="btn btn-primary">
                <i class="bi bi-save"></i> Crea campagna
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    {{-- Config condivisa per l’editor: media picker + font --}}
    <script>
        // Endpoint JSON per il media picker (lo stesso del Page Builder)
        window.PB_MEDIA_PICKER_URL = '{{ url('/admin/media/picker') }}';

        // Font disponibili per il rich text
        window.PB_FONTS = [
            'Inter',
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Poppins',
            'Playfair Display',
            'Merriweather',
            'Source Sans 3',
            'Raleway',
            'Nunito',
            'Oswald',
            'PT Serif',
            'Work Sans',
            'Rubik',
            'Arial',
            'Verdana',
            'Times New Roman',
            'Georgia',
            'Tahoma',
            'Trebuchet MS',
            'Courier New'
        ];
    </script>

    {{-- Editor Rich Text delle campagne (riusa lo stesso di edit) --}}
    <script type="module" src="{{ asset('pb/campaignEditor.js') }}"></script>
@endpush
