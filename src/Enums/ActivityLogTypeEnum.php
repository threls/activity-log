<?php

namespace Threls\ThrelsActivityLog\Enums;

enum ActivityLogTypeEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case LOGIN = 'login';

}
