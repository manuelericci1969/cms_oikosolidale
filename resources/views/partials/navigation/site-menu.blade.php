@php
    use App\Models\Menu;
    use App\Services\Navigation\MenuBuilderService;

    /** @var Menu|null $menu */
    $builder = app(MenuBuilderService::class);
    $items = $builder->activeItems($menu ?? null);
@endphp

@if(($menu ?? null) && $items->isNotEmpty())
    <nav class="{{ $builder->rootClasses($menu) }}" style="{{ $builder->cssVariables($menu) }}" data-r4-site-nav="{{ $menu->slug }}">
        <div class="r4-site-nav__inner">
            <a href="{{ route('home') }}" class="r4-site-nav__brand" aria-label="Home">
                <span class="r4-site-nav__brand-mark">R4</span>
                <span class="r4-site-nav__brand-text">R4Software</span>
            </a>

            <button type="button" class="r4-site-nav__toggle" data-r4-site-nav-toggle aria-expanded="false" aria-label="Apri menu">
                <span></span><span></span><span></span>
            </button>

            <ul class="r4-site-nav__menu" data-r4-site-nav-menu>
                @foreach($items as $item)
                    @php
                        $hasChildren = $item->children->where('is_active', true)->isNotEmpty();
                        $isActive = $builder->isActive($item);
                    @endphp
                    <li class="r4-site-nav__item {{ $hasChildren ? 'has-children' : '' }} {{ $isActive ? 'is-active' : '' }}">
                        @if(($item->type ?? 'link') === 'separator')
                            <span class="r4-site-nav__separator">{{ $item->title }}</span>
                        @else
                            <a href="{{ $builder->hrefFor($item) }}" class="r4-site-nav__link" target="{{ $item->target ?: '_self' }}" @if($item->target === '_blank') rel="noopener" @endif>
                                @if($item->icon)<i class="{{ $item->icon }}" aria-hidden="true"></i>@endif
                                <span>{{ $item->title }}</span>
                                @if($hasChildren)<span class="r4-site-nav__chevron">▾</span>@endif
                            </a>
                        @endif

                        @if($hasChildren)
                            <ul class="r4-site-nav__submenu">
                                @foreach($item->children->where('is_active', true) as $child)
                                    @php($childActive = $builder->isActive($child))
                                    <li class="r4-site-nav__subitem {{ $childActive ? 'is-active' : '' }}">
                                        @if(($child->type ?? 'link') === 'separator')
                                            <span class="r4-site-nav__separator">{{ $child->title }}</span>
                                        @else
                                            <a href="{{ $builder->hrefFor($child) }}" class="r4-site-nav__sublink" target="{{ $child->target ?: '_self' }}" @if($child->target === '_blank') rel="noopener" @endif>
                                                @if($child->icon)<i class="{{ $child->icon }}" aria-hidden="true"></i>@endif
                                                <span>{{ $child->title }}</span>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
