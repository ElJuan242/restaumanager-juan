<?php

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingByOriginTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Cajero Facturación',
            'email' => 'cajero_facturacion@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);
    }

    public function test_order_totals_keep_iva_included_in_the_menu_price(): void
    {
        $orden = $this->createOrder(OrderSource::DineIn);

        OrderItem::create([
            'order_id' => $orden->id,
            'item_name' => 'Plato de 10.000',
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $orden->recalculateTotals();
        $orden->refresh();

        $this->assertSame(8403.0, $orden->subtotal);
        $this->assertSame(1597.0, $orden->tax);
        $this->assertSame(10000.0, $orden->total);
    }

    public function test_dine_in_bill_applies_ten_percent_service_charge(): void
    {
        $orden = $this->createOrder(OrderSource::DineIn);

        $this->actingAs($this->user)
            ->post(route('bills.store'), ['order_id' => $orden->id])
            ->assertSessionHas('success');

        $factura = Bill::where('order_id', $orden->id)->firstOrFail();

        $this->assertSame(8403.0, $factura->subtotal);
        $this->assertSame(1597.0, $factura->tax);
        $this->assertSame(1000.0, $factura->service_charge);
        $this->assertSame(10.0, $factura->service_charge_percent);
        $this->assertSame(11000.0, $factura->total);
        $this->assertSame(BillStatus::Pending, $factura->status);
    }

    public function test_takeaway_and_delivery_bills_do_not_apply_service_charge(): void
    {
        foreach ([OrderSource::Takeaway, OrderSource::Delivery] as $origen) {
            $orden = $this->createOrder($origen);

            $this->actingAs($this->user)
                ->post(route('bills.store'), ['order_id' => $orden->id])
                ->assertSessionHas('success');

            $factura = Bill::where('order_id', $orden->id)->firstOrFail();

            $this->assertSame(0.0, $factura->service_charge);
            $this->assertSame(0.0, $factura->service_charge_percent);
            $this->assertSame(10000.0, $factura->total);
        }
    }

    public function test_service_charge_can_be_removed_from_a_dine_in_bill(): void
    {
        $orden = $this->createOrder(OrderSource::DineIn);

        $this->actingAs($this->user)
            ->post(route('bills.store'), ['order_id' => $orden->id]);

        $factura = Bill::where('order_id', $orden->id)->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('bills.update', $factura), [
                'service_charge_waived' => 1,
            ])
            ->assertSessionHas('success');

        $factura->refresh();

        $this->assertTrue($factura->service_charge_waived);
        $this->assertSame(0.0, $factura->service_charge);
        $this->assertSame(0.0, $factura->service_charge_percent);
        $this->assertSame(10000.0, $factura->total);
    }

    private function createOrder(OrderSource $source): Order
    {
        return Order::create([
            'status' => OrderStatus::Open->value,
            'source' => $source->value,
            'subtotal' => 8403,
            'tax' => 1597,
            'total' => 10000,
        ]);
    }
}
