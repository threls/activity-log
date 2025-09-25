<?php

namespace Threls\ThrelsActivityLog\Enums;

enum ActivityLogTypeEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case LOGIN = 'login';

    public function getVerb(): string
    {
        return match ($this) {
            self::CREATE => 'created',
            self::UPDATE => 'updated',
            self::DELETE => 'deleted',
            self::RESTORE => 'restored',
            self::LOGIN => 'logged in',
        };
    }
}
