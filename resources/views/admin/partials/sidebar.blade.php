@php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();

    // Rileviamo se l'utente è un "agent" in modo tollerante
    $isAgent = false;

    if ($u) {
        if (method_exists($u, 'isAgent') && $u->isAgent()) {
            $isAgent = true;
        } elseif ($u->role instanceof \UnitEnum) {
            $isAgent = ($u->role->value ?? null) === 'agent';
        } else {
            $isAgent = (string) ($u->role ?? '') === 'agent';
        }
    }
@endphp

<nav class="sidebar-inner nav flex-column">

    @if($isAgent)
        {{-- ===================== AREA AGENTE ===================== --}}
        <div class="sidebar-section" data-section="agent-crm">
            <button type="button" class="sidebar-section-toggle" aria-expanded="false">
                <span class="label">
                    <i class="bi bi-kanban"></i>
                    <span>CRM Agente</span>
                </span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>

            <div class="sidebar-section-body">
                @if(Route::has('agent.crm.leads.index'))
                    <a class="nav-link {{ request()->routeIs('agent.crm.leads.*') ? 'active' : '' }}"
                       href="{{ route('agent.crm.leads.index') }}">
                        <i class="bi bi-person-lines-fill"></i> I miei lead
                    </a>
                @endif

                @if(Route::has('agent.crm.quotes.index'))
                    <a class="nav-link {{ request()->routeIs('agent.crm.quotes.*') ? 'active' : '' }}"
                       href="{{ route('agent.crm.quotes.index') }}">
                        <i class="bi bi-card-checklist"></i> I miei preventivi
                    </a>
                @endif

                @if(Route::has('agent.crm.tasks.index'))
                    <a class="nav-link {{ request()->routeIs('agent.crm.tasks.*') ? 'active' : '' }}"
                       href="{{ route('agent.crm.tasks.index') }}">
                        <i class="bi bi-kanban"></i> I miei task
                    </a>
                @endif

                {{--@if(Route::has('agent.crm.chatbot-conversations.index'))
                    <a class="nav-link {{ request()->routeIs('agent.crm.chatbot-conversations.*') ? 'active' : '' }}"
                       href="{{ route('agent.crm.chatbot-conversations.index') }}">
                        <i class="bi bi-robot"></i> Chat AI
                    </a>
                @endif--}}

                    @if(Route::has('admin.crm.chatbot-feedback.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.chatbot-feedback.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.chatbot-feedback.index') }}">
                            <i class="bi bi-hand-thumbs-up"></i> Feedback Chatbot
                        </a>
                    @endif

                @if(Route::has('agent.crm.calendar.index'))
                    <a class="nav-link {{ request()->routeIs('agent.crm.calendar.*') ? 'active' : '' }}"
                       href="{{ route('agent.crm.calendar.index') }}">
                        <i class="bi bi-calendar3"></i> Calendario
                    </a>
                @endif
            </div>
        </div>

    @else
        {{-- ===================== AREA ADMIN ====================== --}}

        {{-- Navigazione --}}
        <div class="sidebar-section" data-section="navigation">
            <button type="button" class="sidebar-section-toggle" aria-expanded="false">
                <span class="label">
                    <i class="bi bi-compass"></i>
                    <span>Navigazione</span>
                </span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>

            <div class="sidebar-section-body">
                @if($u?->hasPermission('view.admin') && Route::has('admin.dashboard'))
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                @endif

                @if($u?->hasPermission('content.create') && Route::has('admin.pages.index'))
                    <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
                       href="{{ route('admin.pages.index') }}">
                        <i class="bi bi-file-text"></i> Pagine
                    </a>
                @endif

                @if($u?->hasPermission('content.media.view') && Route::has('admin.media.index'))
                    <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}"
                       href="{{ route('admin.media.index') }}">
                        <i class="bi bi-images"></i> Media
                    </a>
                @endif

                {{-- Card contestuale: Menu --}}
                @if(isset($page) && request()->routeIs('admin.pages.*'))
                    <hr class="mt-3 mb-2 opacity-25">

                    <div class="side-card mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Menu</div>
                            @if(Route::has('admin.menus.index'))
                                <a href="{{ route('admin.menus.index') }}" class="btn btn-sm btn-outline-secondary">
                                    Gestisci
                                </a>
                            @endif
                        </div>

                        @php $menuItems = $page->menuItems ?? collect(); @endphp
                        @if($menuItems->count() > 0)
                            <ul class="small mb-0">
                                @foreach($menuItems as $item)
                                    <li>{{ $item->menu->name }} → {{ $item->title }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">Non collegata a nessun menu</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- CRM --}}
        <div class="sidebar-section" data-section="crm">
            <button type="button" class="sidebar-section-toggle" aria-expanded="false">
                <span class="label">
                    <i class="bi bi-diagram-3"></i>
                    <span>CRM</span>
                </span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>

            <div class="sidebar-section-body">
                @if($u?->hasPermission('view.admin'))

                    @php
                        $faqActiveCount = 0;
                        $unknownNewCount = 0;
                        $unknownResolvedCount = 0;

                        try {
                            $faqActiveCount = \App\Modules\Crm\Models\ChatbotFaq::query()
                                ->where('client_id', 1)
                                ->where('is_active', true)
                                ->count();

                            $unknownNewCount = \App\Modules\Crm\Models\ChatbotUnknownQuestion::query()
                                ->where('client_id', 1)
                                ->where('status', 'new')
                                ->count();

                            $unknownResolvedCount = \App\Modules\Crm\Models\ChatbotUnknownQuestion::query()
                                ->where('client_id', 1)
                                ->where('status', 'resolved')
                                ->count();
                        } catch (\Throwable $e) {
                            $faqActiveCount = 0;
                            $unknownNewCount = 0;
                            $unknownResolvedCount = 0;
                        }
                    @endphp

                    @if(Route::has('admin.crm.customers.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.customers.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.customers.index') }}">
                            <i class="bi bi-person-vcard"></i> Clienti
                        </a>
                    @endif

                    @if(Route::has('admin.crm.products.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.products.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.products.index') }}">
                            <i class="bi bi-box-seam"></i> Prodotti
                        </a>
                    @endif

                    @if(Route::has('admin.crm.quotes.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.quotes.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.quotes.index') }}">
                            <i class="bi bi-card-checklist"></i> Preventivi
                        </a>
                    @endif

                    @if(Route::has('admin.crm.billing-profiles.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.billing-profiles.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.billing-profiles.index') }}">
                            <i class="bi bi-buildings"></i> Profili di fatturazione
                        </a>
                    @endif

                    @if(Route::has('admin.crm.services.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.services.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.services.index') }}">
                            <i class="bi bi-hdd-network"></i> Servizi
                        </a>
                    @endif

                    @if(Route::has('admin.crm.leads.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.leads.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.leads.index') }}">
                            <i class="bi bi-person-lines-fill"></i> Leads
                        </a>
                    @endif

                    @if(Route::has('admin.crm.tasks.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.tasks.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.tasks.index') }}">
                            <i class="bi bi-kanban"></i> Task
                        </a>
                    @endif

                    @if(Route::has('admin.crm.calendar.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.calendar.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.calendar.index') }}">
                            <i class="bi bi-calendar3"></i> Calendario
                        </a>
                    @endif

                    <div class="section-title mt-2">Chatbot AI</div>

                    @if(Route::has('admin.crm.chatbot-dashboard.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.chatbot-dashboard.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.chatbot-dashboard.index') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard AI
                        </a>
                    @endif

                    @if(Route::has('admin.crm.chatbot-faqs.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.chatbot-faqs.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.chatbot-faqs.index') }}">
                            <i class="bi bi-patch-question"></i> FAQ Chatbot
                        </a>
                    @endif

                    @if(Route::has('admin.crm.chatbot-unknown-questions.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.chatbot-unknown-questions.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.chatbot-unknown-questions.index') }}">
                            <i class="bi bi-question-diamond"></i> Domande non riconosciute
                        </a>
                    @endif

                    @if(Route::has('admin.crm.chatbot-conversations.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.chatbot-conversations.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.chatbot-conversations.index') }}">
                            <i class="bi bi-robot"></i> Conversazioni Chatbot
                        </a>
                    @endif

                    <div class="side-card mb-2 mt-2">
                        <div class="fw-semibold mb-2">Chatbot AI</div>

                        <div class="small d-flex justify-content-between">
                            <span>FAQ attive</span>
                            <strong>{{ $faqActiveCount }}</strong>
                        </div>

                        <div class="small d-flex justify-content-between">
                            <span>Domande nuove</span>
                            <strong class="{{ $unknownNewCount > 0 ? 'text-danger' : '' }}">{{ $unknownNewCount }}</strong>
                        </div>

                        <div class="small d-flex justify-content-between">
                            <span>Domande risolte</span>
                            <strong class="text-success">{{ $unknownResolvedCount }}</strong>
                        </div>
                    </div>

                    <div class="section-title mt-2">Mail marketing</div>

                    @if(Route::has('admin.crm.campaigns.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.campaigns.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.campaigns.index') }}">
                            <i class="bi bi-megaphone"></i> Campagne email
                        </a>
                    @endif

                    @if(Route::has('admin.crm.email-lists.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.email-lists.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.email-lists.index') }}">
                            <i class="bi bi-people-fill"></i> Liste email
                        </a>
                    @endif

                    @if(Route::has('admin.crm.call-campaigns.index'))
                        <a class="nav-link {{ request()->routeIs('admin.crm.call-campaigns.*') ? 'active' : '' }}"
                           href="{{ route('admin.crm.call-campaigns.index') }}">
                            <i class="bi bi-telephone-outbound"></i> Campagne chiamate
                        </a>
                    @endif

                @endif
            </div>
        </div>

        {{-- Amministrazione --}}
        <div class="sidebar-section" data-section="admin">
            <button type="button" class="sidebar-section-toggle" aria-expanded="false">
                <span class="label">
                    <i class="bi bi-gear-wide-connected"></i>
                    <span>Amministrazione</span>
                </span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>

            <div class="sidebar-section-body">
                @if($u?->hasPermission('manage.users') && Route::has('admin.users.index'))
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i> Utenti
                    </a>
                @endif

                @if($u?->hasPermission('manage.roles') && Route::has('admin.roles.index'))
                    <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                       href="{{ route('admin.roles.index') }}">
                        <i class="bi bi-shield-lock"></i> Ruoli
                    </a>
                @endif

                @if($u?->hasPermission('manage.permissions') && Route::has('admin.permissions.index'))
                    <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"
                       href="{{ route('admin.permissions.index') }}">
                        <i class="bi bi-key"></i> Permessi
                    </a>
                @endif

                @if($u?->hasPermission('manage.plugins') && Route::has('admin.plugins.index'))
                    <a class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}"
                       href="{{ route('admin.plugins.index') }}">
                        <i class="bi bi-puzzle"></i> Plugin
                    </a>
                @endif

                @if(($u?->hasPermission('settings.view') || $u?->hasPermission('settings.manage')) && Route::has('admin.settings.index'))
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                       href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-sliders"></i> Impostazioni
                    </a>
                @endif

                @if($u?->hasPermission('settings.manage') && Route::has('admin.seo.regenerate'))
                    <a class="nav-link {{ request()->routeIs('admin.seo.regenerate') ? 'active' : '' }}"
                       href="{{ route('admin.seo.regenerate') }}">
                        <i class="bi bi-arrow-repeat"></i> Rigenera robots &amp; sitemap
                    </a>
                @endif

                @if($u?->hasPermission('manage.plugins') && request()->routeIs('admin.plugins.*'))
                    @php
                        $hasPbPlugins = \App\Models\Plugin::where('enabled', true)->get()->contains(function($p){
                            return !empty(($p->manifest['blocks'] ?? []));
                        });
                    @endphp

                    @if($hasPbPlugins)
                        <hr class="mt-3 mb-2 opacity-25">

                        <div class="side-card mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-semibold">Componenti Page Builder</div>
                                <a href="{{ route('admin.plugins.index') }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    Gestisci
                                </a>
                            </div>
                            <p class="text-muted small mb-0">
                                Sono attivi componenti aggiuntivi per l’editor.
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Account --}}
    <div class="sidebar-section mt-1" data-section="account">
        <button type="button" class="sidebar-section-toggle" aria-expanded="false">
            <span class="label">
                <i class="bi bi-person-circle"></i>
                <span>Account</span>
            </span>
            <i class="bi bi-chevron-down chevron"></i>
        </button>

        <div class="sidebar-section-body">
            @if(Route::has('profile.edit'))
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                   href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-circle"></i> Profilo / Password
                </a>
            @endif
        </div>
    </div>

    <hr class="mt-3 mb-2 opacity-25">

    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" class="mb-1">
        @csrf
        <button type="submit"
                class="nav-link border-0 bg-transparent text-start w-100 text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </button>
    </form>
</nav>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sections = Array.from(document.querySelectorAll('.sidebar-inner .sidebar-section'));
            if (!sections.length) return;

            const openSection = (section) => {
                sections.forEach(sec => {
                    const btn = sec.querySelector('.sidebar-section-toggle');
                    if (sec === section) {
                        sec.classList.add('is-open');
                        if (btn) btn.setAttribute('aria-expanded', 'true');
                    } else {
                        sec.classList.remove('is-open');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            let current = sections.find(sec => sec.querySelector('.nav-link.active'));
            if (!current) current = sections[0] || null;
            if (current) openSection(current);

            sections.forEach(section => {
                const toggle = section.querySelector('.sidebar-section-toggle');
                if (!toggle) return;

                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (section.classList.contains('is-open')) {
                        return;
                    }
                    openSection(section);
                });
            });
        });
    </script>
@endpush
