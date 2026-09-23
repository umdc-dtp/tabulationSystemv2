<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountRole: string
{
    case Admin = 'admin';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Auditor => 'Auditor',
        };
    }
}
