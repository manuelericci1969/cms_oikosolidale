@extends('admin.layout')

@section('title', 'Footer brand')

@section('content')
    <style>
        .r4-settings-shell{
            max-width:980px;
            margin:0 auto;
        }
        .r4-card{
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:18px;
            box-shadow:0 12px 34px rgba(15,23,42,.06);
        }
        .r4-preview-footer{
            border-top:1px solid rgba(15,23,42,.08);
            background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
            color:#64748b;
            border-radius:14px;
            overflow:hidden;
        }
        .r4-preview-footer__inner{
            padding:14px 20px;
            text-align:center;
        }
        .r4-preview-footer__line,
        .r4-preview-footer__copy{
            display:flex;
            flex-wrap:wrap;
            justify-content:center;
            align-items:center;
            gap:5px 8px;
        }
        .r4-preview-footer__line{
            font-size:12px;
            line-height:1.6;
        }
        .r4-preview-footer__copy{
            margin-top:4px;
            font-size:11px;
            line-height:1.5;
            color:#94a3b8;
        }
        .r4-preview-footer strong,
        .r4-preview-footer__brand{
            color:#0f172a;
            font-weight:700;
            text-decoration:none;
        }
        .r4-preview-footer a{
            color:#475569;
            text-decoration:none;
        }
        .r4-form-hint{
            font-size:.875rem;
            color:#64748b;
        }
    </style>

    @php
        $previewBrand = old('brand', $footer['brand']);
        $previewBrandUrl = old('brand_url', $footer['brand_url']);
        $previewBrandTarget = old('brand_target', $footer['brand_target'] ?: '_self');
        $previewProduct = old('product_label', $footer['product_label']);
        $previewEmail = old('email', $footer['email']);
        $previewCopyright = old('copyright', $footer['copyright']);
        $previewPrivacy = old('privacy_url', $footer['privacy_url']);
        $previewCookie = old('cookie_url', $footer['cookie_url']);
    @endphp

    <div class="r4-settings-shell">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h3 mb-1">Footer brand</h1>
                <div class="text-muted">Gestisci la firma globale del CMS visibile in fondo alle pagine pubbliche.</div>
            </div>
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Impostazioni
            </a>
        </div>

        @if(session('ok'))
            <div class="alert alert-success">
                <i class="bi bi-check2-circle me-1"></i>{{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.footer-brand.update') }}" class="r4-card p-4">
            @csrf
            @method('PUT')

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="footerEnabled" name="enabled" value="1" @checked($footer['enabled'])>
                <label class="form-check-label fw-semibold" for="footerEnabled">
                    Mostra footer brand R4Software
                </label>
                <div class="r4-form-hint mt-1">
                    Se disattivato, la firma del CMS non verrà mostrata nel frontend pubblico.
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="{{ old('brand', $footer['brand']) }}" placeholder="R4Software">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $footer['email']) }}" placeholder="info@r4software.it">
                </div>

                <div class="col-md-8">
                    <label class="form-label">URL brand</label>
                    <input type="text" name="brand_url" class="form-control" value="{{ old('brand_url', $footer['brand_url']) }}" placeholder="https://www.r4software.it">
                    <div class="r4-form-hint mt-1">Se valorizzato, il nome del brand diventa cliccabile.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Target brand</label>
                    <select name="brand_target" class="form-select">
                        <option value="_self" @selected(old('brand_target', $footer['brand_target']) !== '_blank')>Stessa scheda</option>
                        <option value="_blank" @selected(old('brand_target', $footer['brand_target']) === '_blank')>Nuova scheda</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Testo prodotto</label>
                    <input type="text" name="product_label" class="form-control" value="{{ old('product_label', $footer['product_label']) }}" placeholder="CMS sviluppato da R4Software">
                    <div class="r4-form-hint mt-1">Testo piccolo mostrato accanto al brand.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Copyright</label>
                    <input type="text" name="copyright" class="form-control" value="{{ old('copyright', $footer['copyright']) }}" placeholder="© {{ date('Y') }} R4Software. Tutti i diritti riservati.">
                </div>

                <div class="col-md-6">
                    <label class="form-label">URL Privacy Policy</label>
                    <input type="text" name="privacy_url" class="form-control" value="{{ old('privacy_url', $footer['privacy_url']) }}" placeholder="/privacy-policy">
                </div>

                <div class="col-md-6">
                    <label class="form-label">URL Cookie Policy</label>
                    <input type="text" name="cookie_url" class="form-control" value="{{ old('cookie_url', $footer['cookie_url']) }}" placeholder="/cookie-policy">
                </div>
            </div>

            <div class="mt-4">
                <label class="form-label">Anteprima</label>
                <div class="r4-preview-footer">
                    <div class="r4-preview-footer__inner">
                        <div class="r4-preview-footer__line">
                            @if($previewBrand !== '')
                                @if($previewBrandUrl !== '')
                                    <a class="r4-preview-footer__brand" href="{{ $previewBrandUrl }}" target="{{ $previewBrandTarget }}" @if($previewBrandTarget === '_blank') rel="noopener noreferrer" @endif>{{ $previewBrand }}</a>
                                @else
                                    <strong>{{ $previewBrand }}</strong>
                                @endif
                            @endif

                            @if($previewBrand !== '' && $previewProduct !== '')
                                <span>·</span>
                            @endif

                            @if($previewProduct !== '')
                                <span>{{ $previewProduct }}</span>
                            @endif

                            @if(($previewBrand !== '' || $previewProduct !== '') && $previewEmail !== '')
                                <span>·</span>
                            @endif

                            @if($previewEmail !== '')
                                <a href="mailto:{{ $previewEmail }}">{{ $previewEmail }}</a>
                            @endif
                        </div>

                        @if($previewCopyright !== '' || $previewPrivacy !== '' || $previewCookie !== '')
                            <div class="r4-preview-footer__copy">
                                @if($previewCopyright !== '')
                                    <span>{{ $previewCopyright }}</span>
                                @endif
                                @if($previewPrivacy !== '' || $previewCookie !== '')
                                    <span>
                                        @if($previewPrivacy !== '')<a href="#">Privacy Policy</a>@endif
                                        @if($previewPrivacy !== '' && $previewCookie !== '') · @endif
                                        @if($previewCookie !== '')<a href="#">Cookie Policy</a>@endif
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.settings.footer-brand.edit') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Annulla
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save2 me-1"></i> Salva footer brand
                </button>
            </div>
        </form>
    </div>
@endsection
