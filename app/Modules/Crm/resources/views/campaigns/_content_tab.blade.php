<form method="POST" action="{{ route('admin.crm.campaigns.update', $campaign) }}">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Contenuto campagna</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome interno *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $campaign->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Oggetto *</label>
                        <input type="text" name="subject" class="form-control"
                               value="{{ old('subject', $campaign->subject) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Preheader</label>
                        <input type="text" name="preheader" class="form-control"
                               value="{{ old('preheader', $campaign->preheader) }}">
                        <div class="form-text">
                            Testo breve che molti client mostrano dopo l’oggetto.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contenuto HTML *</label>

                        <textarea name="html_body" id="campaignHtmlTextarea" class="d-none" rows="10" required>{{ old('html_body', $campaign->html_body) }}</textarea>

                        <div id="campaignRichtextToolbar" class="pb-toolbar pb-richtext-toolbar mb-2"></div>

                        <div id="campaignRichtextEditor" class="pb-richtext-editor form-control"
                             contenteditable="true" style="min-height: 260px;"></div>

                        <textarea id="campaignHtmlSource"
                                  class="pb-richtext-html form-control form-control-sm mt-2 d-none"
                                  style="font-family: monospace; min-height: 160px;"></textarea>

                        <div class="form-text">
                            Placeholder:
                            <code>&lbrace;&lbrace;name&rbrace;&rbrace;</code>,
                            <code>&lbrace;&lbrace;email&rbrace;&rbrace;</code>.
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Contenuto testuale (opzionale)</label>
                        <textarea name="text_body" rows="5" class="form-control">{{ old('text_body', $campaign->text_body) }}</textarea>
                        <div class="form-text">
                            Usato come versione "solo testo" in alcuni client email.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Mittente</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Da (nome)</label>
                        <input type="text" name="from_name" class="form-control"
                               value="{{ old('from_name', $campaign->from_name) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Da (email)</label>
                        <input type="email" name="from_email" class="form-control"
                               value="{{ old('from_email', $campaign->from_email) }}">
                        <div class="form-text">
                            Se vuoto, verranno usati i dati predefiniti di sistema.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reply-to</label>
                        <input type="email" name="reply_to_email" class="form-control"
                               value="{{ old('reply_to_email', $campaign->reply_to_email) }}">
                    </div>

                    <hr>

                    <div class="small text-muted mb-2">Riepilogo rapido</div>
                    <div class="small">
                        <div><strong>Nome campagna:</strong> {{ $campaign->name }}</div>
                        <div><strong>Oggetto:</strong> {{ $campaign->subject }}</div>
                        <div><strong>Stato:</strong> {{ \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status }}</div>
                    </div>
                </div>
            </div>

            <div class="text-end mb-4">
                <button class="btn btn-primary">
                    <i class="bi bi-save"></i> Salva contenuto
                </button>
            </div>
        </div>
    </div>
</form>
