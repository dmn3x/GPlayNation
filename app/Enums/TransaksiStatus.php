<?php

namespace App\Enums;

enum TransaksiStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';
}
