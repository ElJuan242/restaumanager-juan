<?php

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableLocation;
use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillPaymentTableCleaningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Cajero Mesas',
            'email' => 'cajero_mesas@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);

        $this->table = RestaurantTable::create([
            'table_number' => 'M-PAGO',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Occupied->value,
            'is_active' => true,
        ]);
    }

    public function test_paying_a_bill_sends_the_table_to_cleaning_and_ready_action_frees_it(): void
    {
        $orden = Order::create([
            'restaurant_table_id' => $this->table->id,
            'status' => OrderStatus::Served->value,
            'source' => OrderSource::DineIn->value,
            'subtotal' => 8403,
            'tax' => 1597,
            'total' => 10000,
        ]);

        $factura = Bill::create([
            'order_id' => $orden->id,
            'subtotal' => 8403,
            'tax' => 1597,
            'service_charge' => 0,
            'service_charge_percent' => 0,
            'service_charge_waived' => false,
            'discount' => 0,
            'total' => 10000,
            'status' => BillStatus::Pending->value,
        ]);

        $this->actingAs($this->user)
            ->post(route('bills.pay', $factura), [
                'amount' => 10000,
                'method' => PaymentMethod::Cash->value,
            ])
            ->assertSessionHas('success');

        $this->assertSame(BillStatus::Paid, $factura->fresh()->status);

        $this->table->refresh();
        $this->assertSame(TableStatus::Cleaning, $this->table->status);
        $this->assertNotNull($this->table->cleaning_started_at);

        $this->actingAs($this->user)
            ->from(route('tables.show', $this->table))
            ->patch(route('tables.ready', $this->table))
            ->assertSessionHas('success');

        $this->table->refresh();
        $this->assertSame(TableStatus::Available, $this->table->status);
        $this->assertSame($this->user->id, $this->table->ready_confirmed_by);
        $this->assertNotNull($this->table->ready_confirmed_at);
    }
}
