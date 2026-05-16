{{--
    Partial per renderizzare un menu dinamicamente (Bootstrap 5)
    Utilizzato dall'helper renderMenu()
--}}

@php
    // Helper locale: attivo se l'URL corrisponde oppure se una child è attiva
    $isItemActive = function($item) {
        if (function_exists('isActiveMenu') && isActiveMenu($item->url)) return true;
        foreach ($item->children as $c) {
            if ($c->is_active && function_exists('isActiveMenu') && isActiveMenu($c->url)) return true;
        }
        return false;
    };
@endphp

<ul class="navbar-nav me-auto mb-2 mb-lg-0 enhanced-nav">
    @foreach($menu->items as $item)
        @if($item->is_active)
            @php
                $hasChildren  = $item->children->count() > 0;
                $activeParent = $isItemActive($item);
                $target       = $item->target ?: '_self';
                $isExternal   = $target === '_blank';
                $rel          = $isExternal ? 'noopener' : null;
            @endphp

            @if($hasChildren)
                {{-- Genitore con dropdown --}}
                <li class="nav-item dropdown hover-nav {{ $activeParent ? 'active-parent' : '' }}">
                    <a class="nav-link nav-link-underline dropdown-toggle d-flex align-items-center {{ $activeParent ? 'active' : '' }}"
                       href="#"
                       id="navDrop{{ $item->id }}"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false"
                       aria-current="{{ $activeParent ? 'page' : 'false' }}">
                        @if($item->icon)<i class="{{ $item->icon }} me-1"></i>@endif
                        <span>{{ $item->title }}</span>
                        <i class="bi bi-chevron-down small ms-1 caret"></i>
                    </a>

                    {{--<ul class="dropdown-menu shadow dropdown-menu-custom rounded-3 p-2 border-0"
                        aria-labelledby="navDrop{{ $item->id }}">
                        @foreach($item->children as $child)
                            @if($child->is_active)
                                @php
                                    $childActive = function_exists('isActiveMenu') && isActiveMenu($child->url);
                                    $ctarget     = $child->target ?: '_self';
                                    $cext        = $ctarget === '_blank';
                                @endphp
                                <li>
                                    <a class="dropdown-item rounded-2 d-flex align-items-center gap-2 {{ $childActive ? 'active' : '' }}"
                                       href="{{ $child->url }}"
                                       @if($ctarget) target="{{ $ctarget }}" @endif
                                       @if($cext) rel="noopener" @endif
                                       aria-current="{{ $childActive ? 'page' : 'false' }}">
                                        @if($child->icon)<i class="{{ $child->icon }}"></i>@endif
                                        <span>{{ $child->title }}</span>
                                        @if($cext)<i class="bi bi-box-arrow-up-right ms-auto opacity-75"></i>@endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>--}}

                    <ul class="dropdown-menu shadow dropdown-menu-custom rounded-3 p-2 border-0"
                        aria-labelledby="navDrop{{ $item->id }}">

                        @foreach($item->children as $child)
                            @if(!$child->is_active)
                                @continue
                            @endif

                            @php
                                $childType = $child->type ?? 'link';
                            @endphp

                            @if($childType === 'separator')
                                @php $hasLabel = trim($child->title) !== ''; @endphp

                                @if($hasLabel)
                                    {{-- Separatore con etichetta (es. "Servizi") --}}
                                    <li class="dropdown-header fw-semibold">
                                        {{ $child->title }}
                                    </li>
                                @else
                                    {{-- Solo linea orizzontale --}}
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                            @else
                                @php
                                    $childActive = function_exists('isActiveMenu') && isActiveMenu($child->url);
                                    $ctarget     = $child->target ?: '_self';
                                    $cext        = $ctarget === '_blank';
                                @endphp

                                <li>
                                    <a class="dropdown-item rounded-2 d-flex align-items-center gap-2 {{ $childActive ? 'active' : '' }}"
                                       href="{{ $child->url }}"
                                       @if($ctarget) target="{{ $ctarget }}" @endif
                                       @if($cext) rel="noopener" @endif
                                       aria-current="{{ $childActive ? 'page' : 'false' }}">
                                        @if($child->icon)<i class="{{ $child->icon }}"></i>@endif
                                        <span>{{ $child->title }}</span>
                                        @if($cext)<i class="bi bi-box-arrow-up-right ms-auto opacity-75"></i>@endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>

                </li>
            @else
                {{-- Voce semplice --}}
                @php $selfActive = function_exists('isActiveMenu') && isActiveMenu($item->url); @endphp
                <li class="nav-item">
                    <a class="nav-link nav-link-underline d-flex align-items-center {{ $selfActive ? 'active' : '' }}"
                       href="{{ $item->url }}"
                       @if($target) target="{{ $target }}" @endif
                       @if($rel) rel="{{ $rel }}" @endif
                       aria-current="{{ $selfActive ? 'page' : 'false' }}">
                        @if($item->icon)<i class="{{ $item->icon }} me-1"></i>@endif
                        <span>{{ $item->title }}</span>
                        @if($isExternal)<i class="bi bi-box-arrow-up-right ms-1 opacity-75"></i>@endif
                    </a>
                </li>
            @endif
        @endif
    @endforeach
</ul>

@once
    @push('styles')
        <style>
            /* --- Migliorie grafiche --- */
            .enhanced-nav .nav-link{
                position:relative;
                transition: color .15s ease-in-out;
            }
            /* underline animata su hover/attivo */
            .nav-link-underline::after{
                content:""; position:absolute; left:0; right:0; bottom:.15rem;
                height:2px; transform:scaleX(0); transform-origin:left center;
                background: currentColor; opacity:.35; transition: transform .2s ease;
            }
            .nav-link-underline:hover::after,
            .nav-link-underline.active::after,
            .active-parent > .nav-link-underline::after{
                transform:scaleX(1);
            }

            /* caret che ruota quando il dropdown è aperto */
            .hover-nav .caret{ transition: transform .15s ease; }
            .hover-nav.show .caret,
            .hover-nav .dropdown-toggle[aria-expanded="true"] .caret{ transform: rotate(180deg); }

            /* dropdown più “soft” */
            .dropdown-menu-custom{
                min-width: 220px;
                --bs-dropdown-link-hover-bg: rgba(13,110,253,.08); /* come btn-soft */
            }
            .dropdown-menu-custom .dropdown-item{
                padding:.45rem .6rem;
            }
            .dropdown-menu-custom .dropdown-item.active{
                background: rgba(13,110,253,.12);
                color:#0d6efd;
                font-weight:500;
            }

            /* migliore leggibilità dell’icona “esterno” */
            .dropdown-item .bi-box-arrow-up-right{ font-size:.9em; }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            // Apertura dropdown su hover solo su dispositivi con hover (desktop/laptop)
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.matchMedia('(hover: hover)').matches) return;

                document.querySelectorAll('.hover-nav.dropdown').forEach(function (wrap) {
                    const trigger = wrap.querySelector('[data-bs-toggle="dropdown"]');
                    if (!trigger) return;

                    const dd = bootstrap.Dropdown.getOrCreateInstance(trigger, { autoClose: true });

                    wrap.addEventListener('mouseenter', () => dd.show());
                    wrap.addEventListener('mouseleave', () => dd.hide());

                    // accessibilità da tastiera: resta aperto se si naviga dentro col TAB
                    wrap.addEventListener('focusin', () => dd.show());
                    wrap.addEventListener('focusout', (e) => {
                        // chiudi quando il focus esce fuori dal container
                        if (!wrap.contains(e.relatedTarget)) dd.hide();
                    });
                });
            });
        </script>
    @endpush
@endonce
