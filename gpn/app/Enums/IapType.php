<?php

namespace App\Enums;

enum IapType: string
{
    case Currency = 'currency';
    case Item = 'item';
    case Upgrade = 'upgrade';
    case Cosmetic = 'cosmetic';
}
