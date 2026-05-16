<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'superadmin';
    case Admin      = 'admin';
    case Agent      = 'agent';   // 👈 nuovo
    case User       = 'user';

    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }

    // parsing tollerante (string|enum)
    public static function tryFromMixed(mixed $v): ?self
    {
        if ($v instanceof self) return $v;
        if (is_string($v)) return self::tryFrom(strtolower($v));
        return null;
    }

    // gerarchia semplice per confronti
    public function rank(): int
    {
        return match ($this) {
            self::User  => 0,
            self::Agent => 1,
            self::Admin => 2,
            self::SuperAdmin => 3,
        };
    }

    public function isAtLeast(self $min): bool
    {
        return $this->rank() >= $min->rank();
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin      => 'Admin',
            self::Agent      => 'Agente',
            self::User       => 'User',
        };
    }
}
