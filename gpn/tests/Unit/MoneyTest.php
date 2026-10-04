<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_idr_without_decimals(): void
    {
        $this->assertSame('Rp 1.500.000', Money::format('1500000.00'));
        $this->assertSame('Rp 0', Money::format('0.00'));
        $this->assertSame('Rp 49.500', Money::format(49500));
    }
}
