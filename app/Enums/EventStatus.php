<?php

namespace App\Enums;

enum EventStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
