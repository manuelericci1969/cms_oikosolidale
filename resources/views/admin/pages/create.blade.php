@extends('admin.layout')
@section('title', 'Nuova Pagina')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Nuova Pagina</h1>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">← Torna all'elenco</a>
    </div>

    <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titolo *</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" required autofocus>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}">
                            <small class="text-muted">Lascia vuoto per generarlo automaticamente dal titolo</small>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estratto</label>
                            <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt') }}</textarea>
                            <small class="text-muted">Breve descrizione della pagina</small>
                        </div>
                    </div>
                </div>

                @if(isset($templates) && $templates->count() > 0)
                    <div class="card">
                        <div class="card-header">🎨 Scegli un Template (opzionale)</div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($templates as $tpl)
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-body text-center">
                                                @if($tpl->thumbnail)
                                                    <img src="{{ $tpl->thumbnail }}" class="img-fluid mb-2" alt="{{ $tpl->name }}">
                                                @endif
                                                <h6>{{ $tpl->name }}</h6>
                                                <p class="small text-muted">{{ $tpl->description }}</p>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="applyTemplate({{ json_encode($tpl->content) }})">
                                                    Usa questo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">⚙️ Impostazioni</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <select name="status" class="form-select">
                                <option value="draft" selected>Bozza</option>
                                <option value="published">Pubblicata</option>
                                <option value="archived">Archiviata</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data Pubblicazione</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <hr>
                        <h6>SEO</h6>
                        <div class="mb-3">
                            <label class="form-label small">Meta Title</label>
                            <input type="text" name="meta[title]" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Meta Description</label>
                            <textarea name="meta[description]" class="form-control form-control-sm" rows="2"></textarea>
                        </div>

                        {{-- 👉 QUI inserisci il blocco pagina (homepage/menu) --}}
                        @include('admin.pages._sidebar', ['page' => $page])


                        <hr>
                        <button type="submit" class="btn btn-primary w-100">Crea Pagina</button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="content" value="[]">
    </form>

    @push('scripts')
        <script>
            function applyTemplate(content) {
                document.querySelector('[name="content"]').value = JSON.stringify(content);
                alert('Template applicato! Dopo la creazione potrai modificarlo nel builder.');
            }
        </script>
    @endpush
@endsection
