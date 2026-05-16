<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp_opt_in',
        'password',
        // NON mettere 'role' qui se vuoi che lo cambi solo l’admin
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role'              => Role::class,
        'is_active'         => 'boolean',
        'last_login_at'     => 'datetime',
        'whatsapp_opt_in'   => 'boolean',
    ];

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
        'whatsapp_opt_in' => false,
    ];

    public function isAgent(): bool
    {
        return $this->role === \App\Enums\Role::Agent;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [Role::Admin, Role::SuperAdmin], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withTimestamps();
    }

    public function hasPermission(string $name): bool
    {
        $userHas = Cache::remember("uperm:{$this->id}:{$name}", 60, function () use ($name) {
            return $this->permissions()->where('name', $name)->exists();
        });

        if ($userHas) {
            return true;
        }

        $roleValue = $this->role instanceof Role
            ? $this->role->value
            : (is_string($this->role) ? $this->role : null);

        if (!$roleValue) {
            return false;
        }

        if ($roleValue === Role::SuperAdmin->value) {
            return true;
        }

        $permId = Cache::remember("perm-id:{$name}", 60, fn() =>
            Permission::where('name', $name)->value('id') ?? 0
        );

        if (!$permId) {
            return false;
        }

        return Cache::remember("rperm:{$roleValue}:{$permId}", 60, fn() =>
        DB::table('role_permissions')
            ->where('role', $roleValue)
            ->where('permission_id', $permId)
            ->exists()
        );
    }


    public function getWhatsappPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        // rimuove tutto tranne numeri
        $number = preg_replace('/\D+/', '', trim($this->phone));

        if ($number === '') {
            return null;
        }

        // gestisce numeri con 0039
        if (str_starts_with($number, '0039')) {
            $number = substr($number, 2);
        }

        // se non ha prefisso internazionale aggiunge 39
        if (!str_starts_with($number, '39')) {
            $number = '39' . ltrim($number, '0');
        }

        return $number;
    }

    public function canReceiveWhatsapp(): bool
    {
        return !empty($this->phone) && (bool) $this->whatsapp_opt_in;
    }
}
