{{-- resources/views/partials/footer.blade.php --}}
@php
    $footerMode = (string) setting('footer.mode', 'simple');
    $footerEnabled = $footerMode !== 'disabled' && (bool) setting('footer.enabled', true);

    $brand = trim((string) setting('footer.brand', ''));
    $brandUrl = trim((string) setting('footer.brand_url', ''));
    $brandTarget = (string) setting('footer.brand_target', '_self');
    $brandTarget = $brandTarget === '_blank' ? '_blank' : '_self';
    $email = trim((string) setting('footer.email', ''));
    $productLabel = trim((string) setting('footer.product_label', ''));
    $copyright = trim((string) setting('footer.copyright', ''));
    $privacyUrl = trim((string) setting('footer.privacy_url', ''));
    $cookieUrl = trim((string) setting('footer.cookie_url', ''));

    $customHtml = (string) setting('footer.html', '');
    $customCss = (string) setting('footer.css', '');

    $hasTopLine = $brand !== '' || $productLabel !== '' || $email !== '';
    $hasBottomLine = $copyright !== '' || $privacyUrl !== '' || $cookieUrl !== '';
@endphp

@if($footerEnabled)
    @if($footerMode === 'custom' && trim($customHtml) !== '')
        @if(trim($customCss) !== '')
            <style id="r4-global-footer-custom-css">
                {!! $customCss !!}
            </style>
        @endif

        {!! $customHtml !!}
    @elseif($hasTopLine || $hasBottomLine)
        <style id="r4-cms-footer-css">
            html,
            body{
                min-height:100%;
            }

            body{
                min-height:100vh;
                min-height:100dvh;
            }

            .pb-site-content{
                min-height:100vh;
                min-height:100dvh;
                display:flex;
                flex-direction:column;
            }

            .pb-site-content > main{
                flex:1 0 auto;
            }

            .r4-cms-footer{
                flex-shrink:0;
                margin-top:auto;
                border-top:1px solid rgba(15,23,42,.08);
                background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
                color:#64748b;
            }

            .r4-cms-footer__inner{
                max-width:980px;
                margin:0 auto;
                padding:14px 20px;
                text-align:center;
            }

            .r4-cms-footer__line,
            .r4-cms-footer__copy,
            .r4-cms-footer__legal{
                display:flex;
                flex-wrap:wrap;
                justify-content:center;
                align-items:center;
            }

            .r4-cms-footer__line{
                gap:5px 8px;
                font-size:12px;
                line-height:1.6;
            }

            .r4-cms-footer__copy{
                margin-top:4px;
                gap:5px 8px;
                font-size:11px;
                line-height:1.5;
                color:#94a3b8;
            }

            .r4-cms-footer__legal{
                gap:5px 7px;
            }

            .r4-cms-footer strong,
            .r4-cms-footer__brand-link{
                color:#0f172a;
                font-weight:700;
            }

            .r4-cms-footer a{
                color:#475569;
                text-decoration:none;
                text-underline-offset:3px;
            }

            .r4-cms-footer a:hover{
                color:#0f172a;
                text-decoration:underline;
            }

            .r4-cms-footer__brand-link:hover{
                text-decoration:none;
            }

            .r4-cms-footer__dot{
                color:#cbd5e1;
            }

            @media (max-width: 640px){
                .r4-cms-footer__inner{
                    padding:12px 16px;
                }

                .r4-cms-footer__line{
                    font-size:11px;
                }

                .r4-cms-footer__copy{
                    font-size:10px;
                }
            }
        </style>

        <footer class="r4-cms-footer" role="contentinfo">
            <div class="r4-cms-footer__inner">
                @if($hasTopLine)
                    <div class="r4-cms-footer__line">
                        @if($brand !== '')
                            @if($brandUrl !== '')
                                <a class="r4-cms-footer__brand-link"
                                   href="{{ $brandUrl }}"
                                   target="{{ $brandTarget }}"
                                   @if($brandTarget === '_blank') rel="noopener noreferrer" @endif>{{ $brand }}</a>
                            @else
                                <strong>{{ $brand }}</strong>
                            @endif
                        @endif

                        @if($brand !== '' && $productLabel !== '')
                            <span class="r4-cms-footer__dot" aria-hidden="true">·</span>
                        @endif

                        @if($productLabel !== '')
                            <span>{{ $productLabel }}</span>
                        @endif

                        @if(($brand !== '' || $productLabel !== '') && $email !== '')
                            <span class="r4-cms-footer__dot" aria-hidden="true">·</span>
                        @endif

                        @if($email !== '')
                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                        @endif
                    </div>
                @endif

                @if($hasBottomLine)
                    <div class="r4-cms-footer__copy">
                        @if($copyright !== '')
                            <span>{{ $copyright }}</span>
                        @endif

                        @if($privacyUrl !== '' || $cookieUrl !== '')
                            <span class="r4-cms-footer__legal">
                                @if($privacyUrl !== '')
                                    <a href="{{ $privacyUrl }}">Privacy Policy</a>
                                @endif

                                @if($privacyUrl !== '' && $cookieUrl !== '')
                                    <span aria-hidden="true">·</span>
                                @endif

                                @if($cookieUrl !== '')
                                    <a href="{{ $cookieUrl }}">Cookie Policy</a>
                                @endif
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </footer>
    @endif
@endif
