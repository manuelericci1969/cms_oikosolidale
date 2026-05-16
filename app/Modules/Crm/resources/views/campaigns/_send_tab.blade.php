<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">Stato invio</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Stato campagna</div>
                            <div class="fw-semibold mt-1">
                                {{ \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Destinatari totali</div>
                            <div class="fw-semibold mt-1">{{ $totalRecipients }}</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Email inviate</div>
                            <div class="fw-semibold mt-1">{{ $sentCount }}</div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" id="btn-send-now-tab">
                        <i class="bi bi-send"></i> Avvia invio progressivo
                    </button>

                    <form method="POST" action="{{ route('admin.crm.campaigns.queue', $campaign) }}">
                        @csrf
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-clock-history"></i> Metti in coda
                        </button>
                    </form>

                    @if($errorCount > 0)
                        <form method="POST" action="{{ route('admin.crm.campaigns.retry_errors', $campaign) }}">
                            @csrf
                            <button class="btn btn-outline-warning">
                                <i class="bi bi-arrow-repeat"></i> Ripeti errori
                            </button>
                        </form>
                    @endif
                </div>

                <div class="mt-3 small text-muted">
                    Usa questa sezione quando il contenuto e i destinatari sono già pronti.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Checklist</div>
            <div class="card-body">
                <div class="mb-2">
                    @if(!empty($campaign->subject))
                        <span class="badge bg-success me-2">OK</span> Oggetto compilato
                    @else
                        <span class="badge bg-danger me-2">NO</span> Oggetto mancante
                    @endif
                </div>

                <div class="mb-2">
                    @if(!empty($campaign->html_body))
                        <span class="badge bg-success me-2">OK</span> Contenuto HTML presente
                    @else
                        <span class="badge bg-danger me-2">NO</span> Contenuto HTML mancante
                    @endif
                </div>

                <div class="mb-2">
                    @if($totalRecipients > 0)
                        <span class="badge bg-success me-2">OK</span> Destinatari presenti
                    @else
                        <span class="badge bg-danger me-2">NO</span> Nessun destinatario
                    @endif
                </div>

                <div class="mb-2">
                    @if($errorCount > 0)
                        <span class="badge bg-warning text-dark me-2">ATT</span> {{ $errorCount }} destinatari in errore
                    @else
                        <span class="badge bg-success me-2">OK</span> Nessun errore pendente
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainBtn = document.getElementById('btn-send-now');
        const tabBtn = document.getElementById('btn-send-now-tab');

        if (mainBtn && tabBtn) {
            tabBtn.addEventListener('click', function () {
                mainBtn.click();
            });
        }
    });
</script>
