@extends('admin.layout')
@section('title','Media')

@section('content')
    <h1 class="h4 mb-3">Media</h1>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input class="form-control" name="q" value="{{ $q }}" placeholder="Cerca per nome/titolo…">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">Cerca</button>
        </div>
    </form>

    @php
        $canEditMedia = auth()->user()?->hasPermission('content.media.edit');
    @endphp

    @if($canEditMedia)
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card mb-3 p-3 d-grid gap-2">
            @csrf
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input name="title" class="form-control" placeholder="Titolo (opz.)">
                </div>
                <div class="col-md-3">
                    <input name="alt" class="form-control" placeholder="Alt (opz.)">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Carica</button>
                </div>
            </div>
        </form>
    @endif

    <div class="row g-3">
        @foreach($items as $m)
            @php
                $isImage = \Illuminate\Support\Str::startsWith((string)$m->mime, 'image/');

                $baseUrl  = $m->url;
                $thumbUrl = $isImage ? ($m->variantUrl('thumb') ?? $m->url) : null;
                $v25Url   = $isImage ? ($m->variantUrl('25') ?? null) : null;
                $v59Url   = $isImage ? ($m->variantUrl('59') ?? null) : null;
                $v75Url   = $isImage ? ($m->variantUrl('75') ?? null) : null;
                $fullUrl  = $isImage ? ($m->variantUrl('full') ?? $m->url) : null;
            @endphp

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100">
                    @if($isImage)
                        <img src="{{ $thumbUrl }}" class="card-img-top" alt="{{ $m->alt ?? '' }}" style="max-height: 220px; object-fit: cover;">
                    @else
                        <a href="{{ $m->url }}" target="_blank" class="text-decoration-none">
                            <div class="p-4 text-center display-6">📎</div>
                        </a>
                    @endif

                    <div class="card-body p-3">
                        <div class="small fw-semibold text-break" title="{{ $m->original_name }}">
                            {{ $m->original_name }}
                        </div>

                        <div class="text-muted small mb-2">
                            {{ $m->mime }} · {{ number_format($m->size / 1024, 0) }} KB
                            @if(!empty($m->width) && !empty($m->height))
                                · {{ $m->width }}×{{ $m->height }}
                            @endif
                        </div>

                        @if($m->title || $m->alt)
                            <div class="text-muted small mb-2">
                                @if($m->title)
                                    <div><strong>Titolo:</strong> {{ $m->title }}</div>
                                @endif
                                @if($m->alt)
                                    <div><strong>Alt:</strong> {{ $m->alt }}</div>
                                @endif
                            </div>
                        @endif

                        <div class="border rounded p-2 bg-light small">
                            <div class="fw-semibold mb-2">Percorsi disponibili</div>

                            <div class="mb-2">
                                <div><strong>Base:</strong></div>
                                <div class="text-break">{{ $baseUrl }}</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($baseUrl))">Copia URL</button>
                            </div>

                            @if($isImage)
                                <div class="mb-2">
                                    <div><strong>Thumb:</strong></div>
                                    <div class="text-break">{{ $thumbUrl }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($thumbUrl))">Copia URL</button>
                                </div>

                                @if($v25Url)
                                    <div class="mb-2">
                                        <div><strong>25:</strong></div>
                                        <div class="text-break">{{ $v25Url }}</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($v25Url))">Copia URL</button>
                                    </div>
                                @endif

                                @if($v59Url)
                                    <div class="mb-2">
                                        <div><strong>59:</strong></div>
                                        <div class="text-break">{{ $v59Url }}</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($v59Url))">Copia URL</button>
                                    </div>
                                @endif

                                @if($v75Url)
                                    <div class="mb-2">
                                        <div><strong>75:</strong></div>
                                        <div class="text-break">{{ $v75Url }}</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($v75Url))">Copia URL</button>
                                    </div>
                                @endif

                                <div class="mb-0">
                                    <div><strong>Full:</strong></div>
                                    <div class="text-break">{{ $fullUrl }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="copyToClipboard(@js($fullUrl))">Copia URL</button>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($canEditMedia)
                        <div class="card-footer p-2 d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#mediaEdit{{ $m->id }}">
                                Modifica
                            </button>

                            <form method="POST" action="{{ route('admin.media.destroy', $m) }}" onsubmit="return confirm('Eliminare definitivamente?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Elimina</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            @if($canEditMedia)
                <div class="modal fade" id="mediaEdit{{ $m->id }}" tabindex="-1" aria-labelledby="mediaEditLabel{{ $m->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.media.update', $m) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="modal-header">
                                    <h5 class="modal-title" id="mediaEditLabel{{ $m->id }}">Modifica Media</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                </div>

                                <div class="modal-body">
                                    @if($isImage)
                                        <img src="{{ $thumbUrl }}" class="img-fluid rounded mb-3" alt="{{ $m->alt ?? '' }}">
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">Titolo</label>
                                        <input type="text" name="title" class="form-control" value="{{ $m->title }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Alt</label>
                                        <input type="text" name="alt" class="form-control" value="{{ $m->alt }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Sostituisci file (opz.)</label>
                                        <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                        <small class="text-muted">Max 20MB. Formati: jpeg, jpg, png, webp.</small>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                                    <button type="submit" class="btn btn-primary">Salva</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="mt-3">{{ $items->links() }}</div>

    <script>
        function copyToClipboard(text) {
            if (!text) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => alert('URL copiato'))
                    .catch(() => fallbackCopyText(text));
                return;
            }

            fallbackCopyText(text);
        }

        function fallbackCopyText(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                document.execCommand('copy');
                alert('URL copiato');
            } catch (err) {
                alert('Impossibile copiare automaticamente. Copia manualmente il testo.');
            }

            document.body.removeChild(textarea);
        }
    </script>
@endsection
