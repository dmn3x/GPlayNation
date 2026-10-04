<?php

namespace App\Support;

class Money
{
    public static function format(string|int|float $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
