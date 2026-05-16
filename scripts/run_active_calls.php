<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);

$status = $kernel->call('crm:calls:run-active', [
    '--limit' => 10,
]);

echo $kernel->output();

exit($status);
