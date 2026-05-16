<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:role {email} {role : superadmin|admin|agent|user} {--deactivate} {--activate}';
    protected $description = 'Imposta il ruolo di un utente e (opz.) attiva/disattiva l’account';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $role  = strtolower($this->argument('role'));

        if (! in_array($role, array_map(fn($c) => $c->value, Role::cases()), true)) {
            $this->error("Ruolo non valido: {$role}");
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Utente non trovato: {$email}");
            return self::FAILURE;
        }

        $user->role = Role::from($role);

        if ($this->option('activate'))   $user->is_active = true;
        if ($this->option('deactivate')) $user->is_active = false;

        $user->save();

        $state = $user->is_active ? 'attivo' : 'disattivo';
        $this->info("OK: {$user->email} -> ruolo={$user->role->value}, stato={$state}");

        return self::SUCCESS;
    }
}
