<div class="accordion mb-4" id="campaignClickAccordion">

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingLinks">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseLinks"
                    aria-expanded="false" aria-controls="collapseLinks">
                <i class="bi bi-link-45deg me-2"></i>
                Link più cliccati
                <span class="ms-2 badge bg-secondary">{{ $linkStats->count() }}</span>
            </button>
        </h2>

        <div id="collapseLinks" class="accordion-collapse collapse"
             aria-labelledby="headingLinks" data-bs-parent="#campaignClickAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>URL</th>
                            <th class="text-end">Click</th>
                            <th class="text-end">Utenti</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($linkStats as $ls)
                            <tr>
                                <td class="small">
                                    <a href="{{ $ls->url }}" target="_blank" rel="noopener">
                                        {{ \Illuminate\Support\Str::limit($ls->url, 80) }}
                                    </a>
                                </td>
                                <td class="text-end">{{ (int) $ls->clicks }}</td>
                                <td class="text-end">{{ (int) $ls->unique_recipients }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Nessun click registrato.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingUsers">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="false" aria-controls="collapseUsers">
                <i class="bi bi-person-lines-fill me-2"></i>
                Utenti più attivi
                <span class="ms-2 badge bg-secondary">{{ $topRecipients->count() }}</span>
            </button>
        </h2>

        <div id="collapseUsers" class="accordion-collapse collapse"
             aria-labelledby="headingUsers" data-bs-parent="#campaignClickAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Destinatario</th>
                            <th class="text-end">Click</th>
                            <th class="text-end">Ultimo click</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topRecipients as $tr)
                            <tr>
                                <td class="small">
                                    <div class="fw-semibold">{{ $tr->email }}</div>
                                    @if(!empty($tr->name))
                                        <div class="text-muted">{{ $tr->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ (int) $tr->clicks }}</td>
                                <td class="text-end small text-muted">
                                    {{ $tr->last_clicked_at ? \Carbon\Carbon::parse($tr->last_clicked_at)->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Nessun click registrato.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingDetails">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseDetails"
                    aria-expanded="false" aria-controls="collapseDetails">
                <i class="bi bi-mouse2 me-2"></i>
                Dettaglio click (destinatario + link)
                <span class="ms-2 badge bg-secondary">{{ $clickRows->count() }}</span>
            </button>
        </h2>

        <div id="collapseDetails" class="accordion-collapse collapse"
             aria-labelledby="headingDetails" data-bs-parent="#campaignClickAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Destinatario</th>
                            <th>Link</th>
                            <th class="text-end">Click</th>
                            <th class="text-end">Primo</th>
                            <th class="text-end">Ultimo</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($clickRows as $c)
                            <tr>
                                <td class="small">
                                    <div class="fw-semibold">{{ $c->recipient->email ?? '—' }}</div>
                                    @if(!empty($c->recipient?->name))
                                        <div class="text-muted">{{ $c->recipient->name }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    <a href="{{ $c->url }}" target="_blank" rel="noopener">
                                        {{ \Illuminate\Support\Str::limit($c->url, 90) }}
                                    </a>
                                </td>
                                <td class="text-end">{{ (int) $c->click_count }}</td>
                                <td class="text-end small text-muted">
                                    {{ $c->first_clicked_at ? $c->first_clicked_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="text-end small text-muted">
                                    {{ $c->last_clicked_at ? $c->last_clicked_at->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Nessun click registrato.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 small text-muted">
                    Nota: mostra gli ultimi 200 record ordinati per “ultimo click”.
                </div>
            </div>
        </div>
    </div>
</div>
