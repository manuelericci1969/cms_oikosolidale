{{-- resources/views/partials/breadcrumbs.blade.php --}}
@php
    use App\Models\Page;

    // Puoi disabilitare da una view con: @php($showBreadcrumbs = false)
    $show = $showBreadcrumbs ?? true;

    // Niente breadcrumb in home
    if (request()->routeIs('home')) {
        $show = false;
    }

    // 🔹 Gestione dimensione font breadcrumb
    //
    // Puoi impostare da una view, per esempio:
    //   @php($breadcrumbSize = 'xxs')   // 8px
    //   @php($breadcrumbSize = 'xs')    // 10px
    //   @php($breadcrumbSize = 'sm')    // 12px
    //   @php($breadcrumbSize = 'md')    // 14px
    //   @php($breadcrumbSize = 'lg')    // 16px
    //
    // Oppure:
    //   @php($breadcrumbSize = 9)       // 9px
    //   @php($breadcrumbSize = '11px')  // 11px
    //   @php($breadcrumbSize = '0.7rem')
    //
    // Se non passi nulla, uso il default (10px).
    $size = $breadcrumbSize ?? null;

    // (Opzionale) se vuoi in futuro leggerlo dai meta della pagina:
    if ($size === null && isset($page) && $page instanceof Page) {
        $size = data_get($page->meta, 'breadcrumb_size'); // es: 'sm','md','lg','10px'
    }

    // Default se ancora nullo
    $size = $size ?? 'xs';

    // Decodifica in una dimensione CSS concreta
    $breadcrumbFontSize = null;

    switch ($size) {
        case 'xxs': // molto piccolo
            $breadcrumbFontSize = '8px';
            break;
        case 'xs': // piccolo (default)
            $breadcrumbFontSize = '10px';
            break;
        case 'sm':
            $breadcrumbFontSize = '12px';
            break;
        case 'md':
            $breadcrumbFontSize = '14px';
            break;
        case 'lg':
            $breadcrumbFontSize = '16px';
            break;
        default:
            // Se è numerico (es: 9) → px
            if (is_numeric($size)) {
                $breadcrumbFontSize = $size . 'px';
            } elseif (is_string($size) && preg_match('/^\d+(\.\d+)?(px|rem|em)$/', $size)) {
                // Se è una stringa valida tipo "10px", "0.8rem", "0.7em"
                $breadcrumbFontSize = $size;
            } else {
                // fallback sicuro
                $breadcrumbFontSize = '10px';
            }
            break;
    }

    // Se la view ha già passato $breadcrumbs, usiamo quelli
    // Formato atteso:
    // [
    //   ['label' => 'Home', 'url' => route('home')],
    //   ['label' => 'Servizi', 'url' => route('servizi')],
    //   ['label' => 'Sviluppo siti', 'url' => null],
    // ]
    $items = $breadcrumbs ?? null;

    if ($show && $items === null) {
        $items = [];
        $homeUrl = route('home');

        // Proviamo a costruire automaticamente se esiste $page (model Page)
        if (isset($page) && $page instanceof Page) {

            // 1) cerco una voce di menu attiva nel menu "header" che punti a questa pagina
            $menuItem = $page->menuItems()
                ->whereHas('menu', function ($q) {
                    $q->where('location', 'header')
                      ->where('is_active', true);
                })
                ->with('parent')
                ->first();

            // 2) se non trovo nulla nel menu "header", prendo la prima voce di menu che trovo
            if (! $menuItem) {
                $menuItem = $page->menuItems()->with('parent')->first();
            }

            if ($menuItem) {
                // risali la catena parent -> parent -> ... fino alla root
                $stack = [];
                $current = $menuItem;
                while ($current) {
                    $stack[] = $current;
                    $current = $current->parent;
                }
                // ora $stack è [leaf, parent, parentRoot...] → invertiamo
                $stack = array_reverse($stack);

                // controllo se nella catena c'è già "Home"
                $hasHome = false;
                foreach ($stack as $mi) {
                    $url = $mi->url;
                    if ($url === $homeUrl || $url === '/') {
                        $hasHome = true;
                        break;
                    }
                }

                if (! $hasHome) {
                    $items[] = [
                        'label' => 'Home',
                        'url'   => $homeUrl,
                    ];
                }

                // aggiungi tutti i livelli del menu (root → leaf)
                foreach ($stack as $mi) {
                    $items[] = [
                        'label' => $mi->title,
                        'url'   => $mi->url, // usa accessor getUrlAttribute (pagina o URL custom)
                    ];
                }
            } else {
                // fallback: Home / Titolo pagina
                $items[] = ['label' => 'Home', 'url' => $homeUrl];
                $items[] = ['label' => $page->title, 'url' => $page->getUrl()];
            }
        }
    }

    // Se non ci sono abbastanza briciole, non mostrare nulla
    if (! $show || ! is_array($items) || count($items) < 2) {
        $items = [];
    }
@endphp

@if(!empty($items))
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0"
            style="font-size: {{ $breadcrumbFontSize }} !important;">
            @foreach($items as $index => $item)
                @php $isLast = $index === count($items) - 1; @endphp
                <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}" @if($isLast) aria-current="page" @endif>
                    @if(! $isLast && ! empty($item['url']))
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
