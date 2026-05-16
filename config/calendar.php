<?php

return [
    // Metti qui la tua tabella appuntamenti reale (se diversa)
    'crm_table' => env('CRM_APPOINTMENTS_TABLE', 'crm_appointments'),

    // Colonne minime richieste
    'crm_id'    => 'id',
    'crm_title' => 'title',
    'crm_start' => 'start_at',
    'crm_end'   => 'end_at',

    // Opzionali
    'crm_description'      => 'description',
    'crm_location'         => 'location',
    'crm_google_event_id'  => 'google_event_id',
];
