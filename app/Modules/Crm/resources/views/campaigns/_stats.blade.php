<div class="row mb-4">
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Destinatari totali</div>
                <div class="h4 mb-0">{{ $totalRecipients }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Inviate</div>
                <div class="h4 mb-0">{{ $campaign->sent_count }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Aperture</div>
                <div class="h4 mb-0">{{ $campaign->open_count }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Click</div>
                <div class="h4 mb-0">{{ $campaign->click_count }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Bounce</div>
                <div class="h4 mb-0">{{ $campaign->bounce_count }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Cancellati</div>
                <div class="h4 mb-0">{{ $campaign->unsubscribe_count }}</div>
            </div>
        </div>
    </div>
</div>
