<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case PickedUp = 'PickedUp';
    case Delivered = 'Delivered';
    case Cancelled = 'Cancelled';
}
