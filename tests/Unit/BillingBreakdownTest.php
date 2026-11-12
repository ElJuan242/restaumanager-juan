<?php

namespace Tests\Unit;

use App\Enums\OrderSource;
use App\Models\Bill;
use Tests\TestCase;

class BillingBreakdownTest extends TestCase
{
    public function test_iva_is_included_in_the_menu_price(): void
    {
        $importes = Bill::breakdown(10000);

        $this->assertSame(8403.0, $importes['subtotal']);
        $this->assertSame(1597.0, $importes['tax']);
        $this->assertSame(10000.0, $importes['total']);
    }

    public function test_service_charge_percent_depends_on_order_source(): void
    {
        $this->assertSame(10.0, Bill::defaultServiceChargePercent(OrderSource::DineIn));
        $this->assertSame(0.0, Bill::defaultServiceChargePercent(OrderSource::Takeaway));
        $this->assertSame(0.0, Bill::defaultServiceChargePercent(OrderSource::Delivery));
    }

    public function test_service_charge_is_calculated_on_the_consumption(): void
    {
        $importes = Bill::breakdown(10000, 0, 10, false);

        $this->assertSame(8403.0, $importes['subtotal']);
        $this->assertSame(1597.0, $importes['tax']);
        $this->assertSame(1000.0, $importes['service_charge']);
        $this->assertSame(10.0, $importes['service_charge_percent']);
        $this->assertSame(11000.0, $importes['total']);
    }

    public function test_service_charge_can_be_waived(): void
    {
        $importes = Bill::breakdown(10000, 0, 10, true);

        $this->assertSame(0.0, $importes['service_charge']);
        $this->assertSame(0.0, $importes['service_charge_percent']);
        $this->assertTrue($importes['service_charge_waived']);
        $this->assertSame(10000.0, $importes['total']);
    }
}
