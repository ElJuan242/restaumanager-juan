<?php

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\InventoryMovementType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableLocation;
use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RecipeItem;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected MenuItem $menuItem;

    protected InventoryItem $inventory;

    protected RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->user = User::create([
            'name' => 'Mesero Test',
            'email' => 'mesero_cancel@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_cancel@test.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        $category = MenuCategory::create([
            'name' => 'Platos Fuertes',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->inventory = InventoryItem::create([
            'name' => 'Carne de res',
            'category' => 'Carnes',
            'quantity' => 10,
            'unit' => 'kg',
            'price' => 20000,
            'min_stock' => 1,
            'active' => true,
        ]);

        $this->menuItem = MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Bandeja Paisa',
            'price' => 35000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        RecipeItem::create([
            'menu_item_id' => $this->menuItem->id,
            'inventory_id' => $this->inventory->id,
            'quantity' => 2,
            'unit' => 'kg',
        ]);

        $this->table = RestaurantTable::create([
            'table_number' => 'M-CANCEL',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Occupied->value,
            'is_active' => true,
        ]);
    }

    public function test_cancelling_open_order_requires_reason_and_does_not_create_waste(): void
    {
        $order = $this->createOrder(OrderStatus::Open, OrderItemStatus::Pending);

        $response = $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => '   ',
            ]);

        $response->assertSessionHasErrors('cancel_reason');
        $this->assertSame(OrderStatus::Open, $order->fresh()->status);

        $response = $this->actingAs($this->user)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Cliente se retiró',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame($this->user->id, $order->cancelled_by);
        $this->assertNull($order->authorized_by);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('Cliente se retiró', $order->cancel_reason);

        $this->assertSame(BillStatus::Void, $order->bill->fresh()->status);
        $this->assertSame(TableStatus::Cleaning, $this->table->fresh()->status);

        $this->assertSame(0, InventoryMovement::query()
            ->where('type', InventoryMovementType::Waste->value)
            ->count());
    }

    public function test_cancelling_preparing_order_requires_admin_pin_and_records_waste_without_replenishing(): void
    {
        $order = $this->createOrder(OrderStatus::Preparing, OrderItemStatus::Preparing);

        $response = $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Producto derramado',
            ]);

        $response->assertSessionHasErrors('pin');
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);

        $response = $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Producto derramado',
                'pin' => '000000',
            ]);

        $response->assertSessionHasErrors('pin');
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);

        $response = $this->actingAs($this->user)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Producto derramado',
                'pin' => '123456',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame($this->admin->id, $order->authorized_by);
        $this->assertSame(BillStatus::Void, $order->bill->fresh()->status);
        $this->assertSame(TableStatus::Cleaning, $this->table->fresh()->status);

        $waste = InventoryMovement::query()
            ->where('type', InventoryMovementType::Waste->value)
            ->first();

        $this->assertNotNull($waste);
        $this->assertSame($this->inventory->id, $waste->inventory_id);
        $this->assertSame(-2.0, $waste->quantity);
        $this->assertSame(10.0, $waste->balance_after);
        $this->assertSame('Producto derramado', $waste->reason);

        // El stock ya había sido descontado al iniciar la preparación: no se repone.
        $this->assertSame(10.0, $this->inventory->fresh()->quantity);
    }

    public function test_cancelling_ready_order_also_records_waste_for_ready_items(): void
    {
        $order = $this->createOrder(OrderStatus::Ready, OrderItemStatus::Ready);

        $response = $this->actingAs($this->user)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Orden duplicada',
                'pin' => '123456',
            ]);

        $response->assertSessionHas('success');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseHas('inventory_movements', [
            'type' => InventoryMovementType::Waste->value,
            'inventory_id' => $this->inventory->id,
            'quantity' => -2.00,
        ]);
    }

    public function test_cannot_cancel_served_order(): void
    {
        $order = $this->createOrder(OrderStatus::Served, OrderItemStatus::Served);

        $response = $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Intento inválido',
                'pin' => '123456',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(OrderStatus::Served, $order->fresh()->status);
    }

    public function test_cannot_cancel_order_with_bill_payments(): void
    {
        $order = $this->createOrder(OrderStatus::Open, OrderItemStatus::Pending);

        Payment::create([
            'bill_id' => $order->bill->id,
            'amount' => 1000,
            'method' => PaymentMethod::Cash->value,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Cliente se retiró',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(OrderStatus::Open, $order->fresh()->status);
        $this->assertSame(BillStatus::Pending, $order->bill->fresh()->status);
        $this->assertSame(TableStatus::Occupied, $this->table->fresh()->status);
    }

    public function test_cancelled_order_disappears_from_kitchen_view(): void
    {
        $order = $this->createOrder(OrderStatus::Open, OrderItemStatus::Pending);

        $this->actingAs($this->user)
            ->get(route('kitchen.index'))
            ->assertSee($order->order_number);

        $this->actingAs($this->user)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Cliente se retiró',
            ])
            ->assertSessionHas('success');

        $this->actingAs($this->user)
            ->get(route('kitchen.index'))
            ->assertSee('No hay órdenes activas');
    }

    public function test_direct_status_update_cannot_cancel_order(): void
    {
        $order = $this->createOrder(OrderStatus::Open, OrderItemStatus::Pending);

        $response = $this->actingAs($this->user)
            ->from(route('orders.edit', $order))
            ->put(route('orders.update', $order), [
                'source' => OrderSource::DineIn->value,
                'restaurant_table_id' => $this->table->id,
                'status' => OrderStatus::Cancelled->value,
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Open, $order->fresh()->status);
    }

    private function createOrder(OrderStatus $status, OrderItemStatus $itemStatus): Order
    {
        $order = Order::create([
            'restaurant_table_id' => $this->table->id,
            'status' => $status->value,
            'source' => OrderSource::DineIn->value,
            'subtotal' => 29412,
            'tax' => 5588,
            'total' => 35000,
        ]);

        $item = new OrderItem([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'item_name' => $this->menuItem->name,
            'quantity' => 1,
            'unit_price' => 35000,
            'status' => $itemStatus->value,
        ]);
        $item->recalculate();
        $item->save();

        Bill::create([
            'order_id' => $order->id,
            'subtotal' => 29412,
            'tax' => 5588,
            'service_charge' => 0,
            'service_charge_percent' => 0,
            'service_charge_waived' => false,
            'discount' => 0,
            'total' => 35000,
            'status' => BillStatus::Pending->value,
        ]);

        return $order->fresh();
    }
}
