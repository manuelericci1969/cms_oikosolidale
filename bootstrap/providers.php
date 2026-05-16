<?php

return [
    App\Providers\AppServiceProvider::class,
    //App\Providers\AuthServiceProvider::class,
    // ...

    // 👉 aggiungi il CRM:
    App\Modules\Crm\CrmServiceProvider::class,
];
