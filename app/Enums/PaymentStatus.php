<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
}
