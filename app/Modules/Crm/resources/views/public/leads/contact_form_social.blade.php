{{-- app/Modules/Crm/resources/views/public/leads/contact_form.blade.php --}}

@section('title', 'Contattaci')
@section('meta_description', 'Modulo di contatto per richiedere informazioni sui servizi e prodotti.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">

                    {{-- Titolo pagina --}}
                    <div class="mb-4 text-center">
                        <h1 class="h3 mb-1">Contattaci</h1>
                        <p class="text-muted mb-0">
                            Compila il modulo qui sotto, ti ricontatteremo al più presto.
                        </p>
                    </div>

                    {{-- Messaggi di stato --}}
                    @if(session('success'))
                        <div class="alert alert-success mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger mb-4">
                            <strong>Attenzione:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Card con form Bootstrap pulito --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('crm.leads.store') }}">
                                @csrf

                                {{-- ANTI-SPAM: honeypot (campo che l'utente non deve mai vedere né compilare) --}}
                                <div style="display:none;" aria-hidden="true">
                                    <label for="website">Non compilare questo campo</label>
                                    <input type="text"
                                           name="website"
                                           id="website"
                                           tabindex="-1"
                                           autocomplete="off">
                                </div>

                                {{-- ANTI-SPAM: timestamp di generazione form --}}
                                <input type="hidden" name="form_ts" value="{{ now()->timestamp }}">

                                <div class="row">
                                    {{-- Nome --}}
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nome *</label>
                                        <input type="text" name="name"
                                               class="form-control rounded-3 @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}" required>
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Email --}}
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email"
                                               class="form-control rounded-3 @error('email') is-invalid @enderror"
                                               value="{{ old('email') }}">
                                        @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    {{-- Telefono --}}
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefono</label>
                                        <input type="text" name="phone"
                                               class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                               value="{{ old('phone') }}">
                                        @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Oggetto --}}
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Oggetto</label>
                                        <input type="text" name="subject"
                                               class="form-control rounded-3 @error('subject') is-invalid @enderror"
                                               value="{{ old('subject') }}">
                                        @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Messaggio --}}
                                <div class="mb-3">
                                    <label class="form-label">Messaggio</label>
                                    <textarea name="message" rows="5"
                                              class="form-control rounded-3 @error('message') is-invalid @enderror"
                                              placeholder="Descrivi brevemente la tua richiesta...">{{ old('message') }}</textarea>
                                    @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Consensi --}}
                                <div class="mb-3">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input @error('gdpr') is-invalid @enderror"
                                               type="checkbox" value="1" id="gdpr" name="gdpr"
                                            {{ old('gdpr') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="gdpr">
                                            Accetto l'informativa privacy *
                                        </label>
                                        @error('gdpr')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox" value="1" id="marketing" name="marketing"
                                            {{ old('marketing') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="marketing">
                                            Acconsento a ricevere comunicazioni commerciali
                                        </label>
                                    </div>

                                    <p class="small text-muted mb-0 mt-2">
                                        I dati saranno trattati nel rispetto della normativa sulla privacy.
                                    </p>
                                </div>

                                {{-- Pulsante invio --}}
                                <div class="d-grid mt-2">
                                    <button type="submit" class="btn btn-primary rounded-3">
                                        Invia richiesta
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
