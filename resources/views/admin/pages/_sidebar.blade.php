@isset($page)
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input"
                   type="checkbox"
                   name="is_homepage"
                   value="1"
                   id="isHomepage"
                @checked((bool) old('is_homepage', $page->is_homepage ?? false))>
            <label class="form-check-label" for="isHomepage">
                <strong>Imposta come Homepage</strong>
            </label>
            <small class="d-block text-muted">
                Se attivo, questa pagina sarà visibile su <code>/</code>
            </small>
        </div>
    </div>


    <hr>
    <h6>Menu</h6>
    <div class="mb-3">
        <label class="form-label small">Collegata ai menu:</label>
        @php $menuItems = $page->menuItems ?? collect(); @endphp
        @if($menuItems->count())
            <ul class="small mb-0">
                @foreach($menuItems as $item)
                    <li>{{ $item->menu->name }} → {{ $item->title }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-muted small mb-0">Non collegata a nessun menu</p>
        @endif
        <a href="{{ route('admin.menus.index') }}" class="btn btn-sm btn-outline-secondary mt-2">Gestisci Menu</a>
    </div>
@endisset
