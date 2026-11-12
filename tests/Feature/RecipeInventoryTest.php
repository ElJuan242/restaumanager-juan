<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected InventoryItem $inventory;

    protected MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Cocinero Inventario',
            'email' => 'cocina_inventario@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);

        $categoria = MenuCategory::create([
            'name' => 'Platos con receta',
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
            'category_id' => $categoria->id,
            'name' => 'Bandeja con receta',
            'price' => 30000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        RecipeItem::create([
            'menu_item_id' => $this->menuItem->id,
            'inventory_id' => $this->inventory->id,
            'quantity' => 2,
            'unit' => 'kg',
        ]);
    }

    public function test_starting_preparation_deducts_the_recipe_from_inventory(): void
    {
        $order = $this->createOrderWithItem(2);
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->user)
            ->patch(route('orders.item-status', $item), [
                'status' => OrderItemStatus::Preparing->value,
            ])
            ->assertSessionHas('success');

        $this->assertSame(OrderItemStatus::Preparing, $item->fresh()->status);
        $this->assertSame(6.0, $this->inventory->fresh()->quantity);

        $movimiento = InventoryMovement::query()
            ->where('type', InventoryMovementType::SaleConsumption->value)
            ->where('inventory_id', $this->inventory->id)
            ->firstOrFail();

        $this->assertSame(-4.0, $movimiento->quantity);
        $this->assertSame(6.0, $movimiento->balance_after);
    }

    public function test_order_creation_is_blocked_when_recipe_stock_is_insufficient(): void
    {
        $this->inventory->update(['quantity' => 1]);

        $response = $this->actingAs($this->user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'source' => OrderSource::DineIn->value,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Order::count());
    }

    public function test_starting_preparation_is_blocked_when_stock_became_insufficient(): void
    {
        $order = $this->createOrderWithItem(1);
        $item = $order->items()->firstOrFail();

        $this->inventory->update(['quantity' => 1]);

        $this->actingAs($this->user)
            ->from(route('orders.show', $order))
            ->patch(route('orders.item-status', $item), [
                'status' => OrderItemStatus::Preparing->value,
            ])
            ->assertSessionHas('error');

        $this->assertSame(OrderItemStatus::Pending, $item->fresh()->status);
        $this->assertSame(1.0, $this->inventory->fresh()->quantity);
        $this->assertSame(0, InventoryMovement::query()
            ->where('type', InventoryMovementType::SaleConsumption->value)
            ->count());
    }

    private function createOrderWithItem(int $quantity): Order
    {
        $order = Order::create([
            'status' => OrderStatus::Open->value,
            'source' => OrderSource::DineIn->value,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'item_name' => $this->menuItem->name,
            'quantity' => $quantity,
            'unit_price' => $this->menuItem->price,
            'subtotal' => $quantity * $this->menuItem->price,
            'status' => OrderItemStatus::Pending->value,
        ]);

        return $order->fresh();
    }
}
