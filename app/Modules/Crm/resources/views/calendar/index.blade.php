{{-- resources/views/crm/calendar/index.blade.php --}}
@extends('admin.layout')

@section('title', 'Calendario appuntamenti')

@section('content')
    @php
        $palette = ['#0d6efd','#198754','#dc3545','#6f42c1','#fd7e14','#20c997','#0dcaf0','#6c757d'];

        $userColors = [];
        if (isset($users) && $users->count()) {
            $i = 0;
            foreach ($users as $u) {
                $userColors[$u->id] = $palette[$i % count($palette)];
                $i++;
            }
        }
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Calendario</h1>

            <div class="small text-muted d-flex gap-2 flex-wrap">
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-calendar-check"></i> Appuntamenti manuali
                </span>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-flag"></i> Prossima azione (Lead)
                </span>
                <span class="badge bg-info text-dark">
                    <i class="bi bi-clock-history"></i> Contatto registrato (Attività)
                </span>
            </div>

            {{-- Toolbar azioni Google/CRM --}}
            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Azioni calendario">
                    <button type="button" class="btn btn-outline-secondary" id="btn_refresh">
                        <i class="bi bi-arrow-clockwise"></i> Aggiorna
                    </button>

                    <button type="button" class="btn btn-outline-primary" id="btn_google_pull">
                        <i class="bi bi-cloud-download"></i> Sync (Pull)
                    </button>

                    <button type="button" class="btn btn-primary" id="btn_google_sync">
                        <i class="bi bi-arrow-left-right"></i> Sync (2-way)
                    </button>
                </div>

                <div class="btn-group btn-group-sm" role="group" aria-label="Dedupe">
                    <button type="button" class="btn btn-outline-warning" id="btn_dedupe_google">
                        <i class="bi bi-copy"></i> Elimina duplicati Google
                    </button>
                    <button type="button" class="btn btn-outline-warning" id="btn_dedupe_db">
                        <i class="bi bi-database"></i> Pulisci duplicati DB
                    </button>
                </div>

                <span class="small text-muted ms-1">
                    <i class="bi bi-info-circle"></i>
                    Le azioni lavorano sul <b>range visibile</b> del calendario.
                </span>
            </div>

            <div class="alert d-none mt-3 mb-0" id="syncBox"></div>
        </div>

        @if(isset($users) && $users->count())
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted me-1">Assegnatari:</span>
                @foreach($users as $u)
                    <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-2">
                        <span class="assignee-dot" style="background: {{ $userColors[$u->id] ?? '#6c757d' }}"></span>
                        {{ $u->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>

    <style>
        #crm-calendar { min-height: 75vh; }
        .fc .fc-toolbar-title { font-size: 1.2rem; }

        .fc .fc-event.fc-lead-event {
            background: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #111 !important;
        }

        .fc .fc-event.fc-activity-event {
            background: #0dcaf0 !important;
            border-color: #0dcaf0 !important;
            color: #0b1220 !important;
        }
        .fc .fc-event.fc-activity-meeting { background: #20c997 !important; border-color: #20c997 !important; color:#0b1220 !important; }
        .fc .fc-event.fc-activity-call    { background: #0d6efd !important; border-color: #0d6efd !important; color:#fff !important; }
        .fc .fc-event.fc-activity-email   { background: #6f42c1 !important; border-color: #6f42c1 !important; color:#fff !important; }
        .fc .fc-event.fc-activity-note    { background: #adb5bd !important; border-color: #adb5bd !important; color:#111 !important; }

        .assignee-dot {
            width: 8px; height: 8px; border-radius: 50%;
            display: inline-block; flex: 0 0 8px;
        }

        .fc .fc-event.fc-appointment-assigned {
            box-shadow: inset 4px 0 0 var(--assignee-color, transparent);
            padding-left: 2px;
        }

        .fc .fc-event .fc-event-title { font-weight: 600; }
    </style>

    <div class="card">
        <div class="card-body">
            <div id="crm-calendar"></div>
        </div>
    </div>

    {{-- Modal appuntamenti manuali --}}
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="appointmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentModalTitle">Appuntamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="appt_id" value="">

                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label">Titolo *</label>
                                <input type="text" class="form-control" id="appt_title" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tutto il giorno</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="appt_all_day">
                                    <label class="form-check-label" for="appt_all_day">All day</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Inizio *</label>
                                <input type="datetime-local" class="form-control" id="appt_start" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fine</label>
                                <input type="datetime-local" class="form-control" id="appt_end">
                                <div class="form-text">Se vuoto, imposta automaticamente +30 minuti.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Luogo</label>
                                <input type="text" class="form-control" id="appt_location">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" id="appt_type">
                                    <option value="">--</option>
                                    <option value="meeting">Incontro</option>
                                    <option value="call">Telefonata</option>
                                    <option value="task">Task</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stato</label>
                                <select class="form-select" id="appt_status">
                                    <option value="planned">Pianificato</option>
                                    <option value="done">Completato</option>
                                    <option value="canceled">Annullato</option>
                                </select>
                            </div>

                            @if(isset($users) && $users->count())
                                <div class="col-md-6">
                                    <label class="form-label">Assegna a</label>
                                    <select class="form-select" id="appt_user_id">
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <label class="form-label">Lead ID (opz.)</label>
                                <input type="number" class="form-control" id="appt_lead_id" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Customer ID (opz.)</label>
                                <input type="number" class="form-control" id="appt_customer_id" min="1">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" id="appt_description" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-danger d-none mt-3" id="appt_error"></div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-danger d-none" id="appt_delete">
                            <i class="bi bi-trash"></i> Elimina
                        </button>

                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        // URL API CRM
        $eventsUrl = route($routePrefix . '.calendar.events');
        $storeUrl  = route($routePrefix . '.calendar.appointments.store');

        // Template URL per update/delete: rimpiazzo __ID__
        $updateUrlTpl = route($routePrefix . '.calendar.appointments.update', ['appointment' => '__ID__']);
        $deleteUrlTpl = route($routePrefix . '.calendar.appointments.destroy', ['appointment' => '__ID__']);

        $csrf = csrf_token();
    @endphp

    @push('scripts')
        <script>
            (function () {
                const eventsUrl    = @json($eventsUrl);
                const storeUrl     = @json($storeUrl);
                const updateUrlTpl = @json($updateUrlTpl);
                const deleteUrlTpl = @json($deleteUrlTpl);
                const csrf         = @json($csrf);
                const userColors   = @json($userColors);

                const calendarEl = document.getElementById('crm-calendar');
                const modalEl    = document.getElementById('appointmentModal');
                const modal      = new bootstrap.Modal(modalEl);

                const $ = (id) => document.getElementById(id);

                const errorBox = $('appt_error');
                const syncBox  = $('syncBox');

                function showError(msg) {
                    errorBox.textContent = msg || 'Errore';
                    errorBox.classList.remove('d-none');
                }
                function clearError() {
                    errorBox.textContent = '';
                    errorBox.classList.add('d-none');
                }

                function showSync(type, msg) {
                    syncBox.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
                    syncBox.classList.add('alert-' + (type || 'info'));
                    syncBox.innerHTML = msg || '';
                }
                function hideSync() {
                    syncBox.classList.add('d-none');
                    syncBox.innerHTML = '';
                }

                function setBusy(on) {
                    const ids = ['btn_refresh','btn_google_pull','btn_google_sync','btn_dedupe_google','btn_dedupe_db'];
                    ids.forEach((id) => {
                        const el = $(id);
                        if (!el) return;
                        el.disabled = !!on;
                        el.classList.toggle('disabled', !!on);
                    });
                }

                function colorFromId(id) {
                    if (!id) return '#6c757d';
                    const hue = (parseInt(id, 10) * 47) % 360;
                    return `hsl(${hue}, 70%, 45%)`;
                }
                function getUserColor(id) {
                    if (!id) return '#6c757d';
                    return userColors[id] || colorFromId(id);
                }

                function assigneeNameFromProps(p) {
                    return p.user_name || p.owner_name || '';
                }

                function applyAssigneeDotColor(info, userId) {
                    if (!userId) return;
                    const c = getUserColor(userId);

                    const dayDot = info.el.querySelector('.fc-daygrid-event-dot');
                    if (dayDot) {
                        dayDot.style.borderColor = c;
                        dayDot.style.backgroundColor = c;
                    }
                    const listDot = info.el.querySelector('.fc-list-event-dot');
                    if (listDot) {
                        listDot.style.borderColor = c;
                        listDot.style.backgroundColor = c;
                    }
                }

                function toDatetimeLocalValue(date) {
                    const pad = (n) => String(n).padStart(2, '0');
                    return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate())
                        + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
                }

                function resetForm() {
                    clearError();
                    $('appt_id').value = '';
                    $('appt_title').value = '';
                    $('appt_description').value = '';
                    $('appt_location').value = '';
                    $('appt_type').value = '';
                    $('appt_status').value = 'planned';
                    $('appt_all_day').checked = false;
                    $('appt_start').value = '';
                    $('appt_end').value = '';
                    $('appt_lead_id').value = '';
                    $('appt_customer_id').value = '';
                    @if(isset($users) && $users->count())
                    $('appt_user_id').selectedIndex = 0;
                    @endif
                    $('appt_delete').classList.add('d-none');
                }

                function openCreate(info) {
                    resetForm();
                    $('appointmentModalTitle').textContent = 'Nuovo appuntamento';

                    $('appt_all_day').checked = !!info.allDay;

                    const start = info.start instanceof Date ? info.start : new Date(info.startStr);
                    const end   = info.end instanceof Date ? info.end : (info.endStr ? new Date(info.endStr) : null);

                    $('appt_start').value = toDatetimeLocalValue(start);
                    if (end) $('appt_end').value = toDatetimeLocalValue(end);

                    modal.show();
                }

                function openEdit(event) {
                    resetForm();

                    const kind = (event.extendedProps?.kind || '');

                    // Lead e Activity sono read-only: ti porto alla scheda
                    if (kind === 'lead_next_action' || kind === 'lead_activity') {
                        if (event.url) window.location.href = event.url;
                        return;
                    }

                    $('appointmentModalTitle').textContent = 'Modifica appuntamento';
                    $('appt_id').value = String(event.id || '').replace(/^appt-/, '');

                    $('appt_title').value = event.title || '';
                    $('appt_all_day').checked = !!event.allDay;

                    $('appt_start').value = event.start ? toDatetimeLocalValue(event.start) : '';
                    $('appt_end').value   = event.end ? toDatetimeLocalValue(event.end) : '';

                    $('appt_description').value = event.extendedProps?.description || '';
                    $('appt_location').value = event.extendedProps?.location || '';
                    $('appt_type').value = event.extendedProps?.type || '';
                    $('appt_status').value = event.extendedProps?.status || 'planned';

                    $('appt_lead_id').value = event.extendedProps?.lead_id || '';
                    $('appt_customer_id').value = event.extendedProps?.customer_id || '';

                    @if(isset($users) && $users->count())
                    if (event.extendedProps?.user_id) $('appt_user_id').value = event.extendedProps.user_id;
                    @endif

                    $('appt_delete').classList.remove('d-none');
                    modal.show();
                }

                async function api(url, method, bodyObj) {
                    const res = await fetch(url, {
                        method,
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: bodyObj ? JSON.stringify(bodyObj) : null
                    });

                    const text = await res.text();
                    let json = null;
                    try { json = text ? JSON.parse(text) : null; } catch(e) {}

                    if (!res.ok) {
                        const msg = (json && (json.message || json.error))
                            ? (json.message || json.error)
                            : ('HTTP ' + res.status + ' - ' + (text || ''));
                        throw new Error(msg);
                    }
                    return json;
                }

                function updateUrlFor(id) {
                    return updateUrlTpl.replace('__ID__', encodeURIComponent(id));
                }
                function deleteUrlFor(id) {
                    return deleteUrlTpl.replace('__ID__', encodeURIComponent(id));
                }

                // Base path coerente con la tua app (come prima)
                function crmBasePath() {
                    // admin.crm / agent.crm
                    // se un domani cambi i prefissi, adegua qui
                    const isAdmin = window.location.pathname.startsWith('/admin');
                    return isAdmin ? '/admin/crm' : '/agent/crm';
                }

                // Endpoint “attesi” per Google actions
                function googleSyncUrl()        { return crmBasePath() + '/calendar/google/sync'; }
                function googleDedupeGoogleUrl(){ return crmBasePath() + '/calendar/google/dedupe-google'; }
                function googleDedupeDbUrl()    { return crmBasePath() + '/calendar/google/dedupe-db'; }

                function currentRangePayload(cal) {
                    const v = cal.view;
                    // activeStart/activeEnd sono Date
                    return {
                        range_start: v.activeStart ? v.activeStart.toISOString() : null,
                        range_end:   v.activeEnd ? v.activeEnd.toISOString() : null,
                    };
                }

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'it',
                    initialView: 'dayGridMonth',
                    nowIndicator: true,
                    selectable: true,
                    editable: true,
                    dayMaxEvents: true,
                    height: 'auto',

                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },

                    events: { url: eventsUrl, method: 'GET' },

                    select: function(info) {
                        const start = info.start;
                        const forced = {
                            start: new Date(start.getFullYear(), start.getMonth(), start.getDate(), 9, 0),
                            end:   new Date(start.getFullYear(), start.getMonth(), start.getDate(), 9, 30),
                            allDay: false
                        };
                        openCreate(forced);
                    },

                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                        openEdit(info.event);
                    },

                    eventDidMount: function(info) {
                        const p = info.event.extendedProps || {};
                        const kind = p.kind || '';
                        let tip = '';

                        const uid = p.user_id || null;
                        const uname = assigneeNameFromProps(p);

                        applyAssigneeDotColor(info, uid);

                        if (kind === 'lead_next_action') {
                            tip = `Prossima azione (Lead)\n`;
                            if (uname) tip += `Assegnato a: ${uname}\n`;
                            if (p.subject) tip += `Oggetto: ${p.subject}\n`;

                        } else if (kind === 'lead_activity') {
                            tip = `Contatto registrato (Attività)\n`;
                            if (uname) tip += `di ${uname}\n`;
                            if (p.subject) tip += `${p.subject}\n`;
                            if (p.outcome) tip += `Esito: ${p.outcome}\n`;
                            if (p.body) tip += `${p.body}`;

                        } else if (kind === 'appointment' || String(info.event.id || '').startsWith('appt-')) {
                            tip = `Appuntamento manuale\n`;
                            if (uname) tip += `Assegnato a: ${uname}\n`;
                            if (p.location) tip += `Luogo: ${p.location}\n`;
                            if (p.description) tip += p.description;

                            if (uid) {
                                const dotColor = getUserColor(uid);
                                info.el.classList.add('fc-appointment-assigned');
                                info.el.style.setProperty('--assignee-color', dotColor);
                            }
                        }

                        if (tip) {
                            info.el.setAttribute('title', tip);
                            if (window.bootstrap && bootstrap.Tooltip) {
                                new bootstrap.Tooltip(info.el, { trigger: 'hover', container: 'body' });
                            }
                        }
                    },

                    eventDrop: async function(info) {
                        const ev = info.event;
                        if ((ev.extendedProps?.kind || '') !== 'appointment') return;

                        try {
                            await api(updateUrlFor(String(ev.id).replace(/^appt-/, '')), 'PATCH', {
                                start_at: ev.start ? ev.start.toISOString() : null,
                                end_at: ev.end ? ev.end.toISOString() : null,
                                all_day: !!ev.allDay
                            });
                        } catch (e) {
                            info.revert();
                            alert(e.message);
                        }
                    },

                    eventResize: async function(info) {
                        const ev = info.event;
                        if ((ev.extendedProps?.kind || '') !== 'appointment') return;

                        try {
                            await api(updateUrlFor(String(ev.id).replace(/^appt-/, '')), 'PATCH', {
                                start_at: ev.start ? ev.start.toISOString() : null,
                                end_at: ev.end ? ev.end.toISOString() : null,
                                all_day: !!ev.allDay
                            });
                        } catch (e) {
                            info.revert();
                            alert(e.message);
                        }
                    }
                });

                calendar.render();

                // -------------------------
                // Toolbar actions
                // -------------------------
                $('btn_refresh')?.addEventListener('click', function() {
                    hideSync();
                    calendar.refetchEvents();
                });

                $('btn_google_pull')?.addEventListener('click', async function() {
                    hideSync();
                    if (!confirm('Eseguire SYNC (solo Pull: Google → CRM) sul range visibile?')) return;

                    setBusy(true);
                    showSync('info', '<i class="bi bi-hourglass-split"></i> Sync in corso (pull)...');
                    try {
                        const payload = Object.assign({ direction: 'pull_only' }, currentRangePayload(calendar));
                        const res = await api(googleSyncUrl(), 'POST', payload);

                        showSync('success', `<i class="bi bi-check-circle"></i> Pull completato.<br><small>${escapeHtmlSafe(JSON.stringify(res))}</small>`);
                        calendar.refetchEvents();
                    } catch (e) {
                        showSync('danger', `<i class="bi bi-x-circle"></i> Errore: ${escapeHtmlSafe(e.message)}`);
                    } finally {
                        setBusy(false);
                    }
                });

                $('btn_google_sync')?.addEventListener('click', async function() {
                    hideSync();
                    if (!confirm('Eseguire SYNC 2-way (Google ↔ CRM) sul range visibile?')) return;

                    setBusy(true);
                    showSync('info', '<i class="bi bi-hourglass-split"></i> Sync in corso (2-way)...');
                    try {
                        const payload = Object.assign({ direction: 'two_way' }, currentRangePayload(calendar));
                        const res = await api(googleSyncUrl(), 'POST', payload);

                        showSync('success', `<i class="bi bi-check-circle"></i> Sync completata.<br><small>${escapeHtmlSafe(JSON.stringify(res))}</small>`);
                        calendar.refetchEvents();
                    } catch (e) {
                        showSync('danger', `<i class="bi bi-x-circle"></i> Errore: ${escapeHtmlSafe(e.message)}`);
                    } finally {
                        setBusy(false);
                    }
                });

                $('btn_dedupe_google')?.addEventListener('click', async function() {
                    hideSync();
                    if (!confirm('Eliminare duplicati CRM su Google nel range visibile? (azione irreversibile)')) return;

                    setBusy(true);
                    showSync('warning', '<i class="bi bi-hourglass-split"></i> Dedupe Google in corso...');
                    try {
                        const payload = currentRangePayload(calendar);
                        const res = await api(googleDedupeGoogleUrl(), 'POST', payload);

                        showSync('success', `<i class="bi bi-check-circle"></i> Dedupe Google completato.<br><small>${escapeHtmlSafe(JSON.stringify(res))}</small>`);
                    } catch (e) {
                        showSync('danger', `<i class="bi bi-x-circle"></i> Errore: ${escapeHtmlSafe(e.message)}`);
                    } finally {
                        setBusy(false);
                    }
                });

                $('btn_dedupe_db')?.addEventListener('click', async function() {
                    hideSync();
                    if (!confirm('Pulire duplicati DB (mapping) ?')) return;

                    setBusy(true);
                    showSync('warning', '<i class="bi bi-hourglass-split"></i> Pulizia DB in corso...');
                    try {
                        const res = await api(googleDedupeDbUrl(), 'POST', {});

                        showSync('success', `<i class="bi bi-check-circle"></i> Pulizia DB completata.<br><small>${escapeHtmlSafe(JSON.stringify(res))}</small>`);
                    } catch (e) {
                        showSync('danger', `<i class="bi bi-x-circle"></i> Errore: ${escapeHtmlSafe(e.message)}`);
                    } finally {
                        setBusy(false);
                    }
                });

                function escapeHtmlSafe(str) {
                    return String(str ?? '')
                        .replaceAll('&','&amp;')
                        .replaceAll('<','&lt;')
                        .replaceAll('>','&gt;')
                        .replaceAll('"','&quot;')
                        .replaceAll("'","&#039;");
                }

                // -------------------------
                // CRUD appuntamenti manuali
                // -------------------------
                $('appointmentForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    clearError();

                    const id = $('appt_id').value.trim();

                    const payload = {
                        title: $('appt_title').value.trim(),
                        description: $('appt_description').value.trim() || null,
                        location: $('appt_location').value.trim() || null,
                        type: $('appt_type').value || null,
                        status: $('appt_status').value || 'planned',
                        all_day: $('appt_all_day').checked ? 1 : 0,
                        start_at: $('appt_start').value ? new Date($('appt_start').value).toISOString() : null,
                        end_at: $('appt_end').value ? new Date($('appt_end').value).toISOString() : null,
                        lead_id: $('appt_lead_id').value ? parseInt($('appt_lead_id').value, 10) : null,
                        customer_id: $('appt_customer_id').value ? parseInt($('appt_customer_id').value, 10) : null,
                    };

                    @if(isset($users) && $users->count())
                        payload.user_id = $('appt_user_id').value ? parseInt($('appt_user_id').value, 10) : null;
                    @endif

                        try {
                        if (!payload.title) throw new Error('Titolo obbligatorio.');
                        if (!payload.start_at) throw new Error('Data/ora inizio obbligatoria.');

                        if (!id) {
                            await api(storeUrl, 'POST', payload);
                        } else {
                            await api(updateUrlFor(id), 'PATCH', payload);
                        }

                        modal.hide();
                        calendar.refetchEvents();
                    } catch (err) {
                        showError(err.message);
                    }
                });

                $('appt_delete').addEventListener('click', async function() {
                    const id = $('appt_id').value.trim();
                    if (!id) return;

                    if (!confirm('Eliminare questo appuntamento?')) return;

                    try {
                        await api(deleteUrlFor(id), 'DELETE');
                        modal.hide();
                        calendar.refetchEvents();
                    } catch (err) {
                        showError(err.message);
                    }
                });
            })();
        </script>
    @endpush
@endsection
