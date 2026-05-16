<?php

return [
    // cartella dove i plugin vengono estratti
    'path'        => base_path('plugins'),
    // cartella pubblica dove copiare gli asset (es. /public/plugins/{slug})
    'public_path' => public_path('plugins'),
    // nome del manifest dentro ogni plugin
    'manifest'    => 'plugin.json',
    // sotto-cartella del plugin che contiene gli asset pubblici
    'public_dir'  => 'public',
    // entry admin default (relativa alla cartella pubblica del plugin copiato in /public/plugins/{slug})
    'admin_entry' => 'admin.js',
    // URL base pubblico degli asset dei plugin
    'web_path' => '/plugins',
];
