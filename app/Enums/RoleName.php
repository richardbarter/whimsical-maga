<?php

namespace App\Enums;

/**
 * Values of roles.name. RoleSeeder creates one role per case.
 */
enum RoleName: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';

    public function description(): string
    {
        return match ($this) {
            RoleName::Admin => 'Full access to all features and settings',
            RoleName::Moderator => 'Can approve quotes, manage tags/categories',
            RoleName::User => 'Can submit quotes and view own submissions',
        };
    }
}
