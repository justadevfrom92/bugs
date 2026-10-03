<?php

namespace Tests\Unit;

use App\Services\Catalog;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    public function test_average_price_matches_the_efl_formula(): void
    {
        // 10¢ energy + 5¢ delivery + ($4 + $5) spread over 1,000 kWh = 15.9¢
        $this->assertEqualsWithDelta(15.9, Catalog::averagePrice(10, 5, 4, 5, 1000), 0.0001);
    }
}
