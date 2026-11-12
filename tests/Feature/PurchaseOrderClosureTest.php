<?php

namespace Tests\Feature;

use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PurchaseOrderClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected Supplier $supplier;

    protected InventoryItem $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->user = User::create([
            'name' => 'Mesero Compras',
            'email' => 'mesero_compras@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Compras',
            'email' => 'admin_compras@test.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Distribuidora Test',
            'category' => 'Abarrotes',
            'is_active' => true,
        ]);

        $this->inventory = InventoryItem::create([
            'name' => 'Harina',
            'category' => 'Abarrotes',
            'quantity' => 10,
            'unit' => 'kg',
            'price' => 1000,
            'min_stock' => 1,
            'active' => true,
        ]);
    }

    public function test_force_close_requires_partial_status(): void
    {
        $order = $this->createOrder(PurchaseOrderStatus::Received, 10, 10);

        $response = $this->actingAs($this->user)
            ->from(route('purchases.show', $order))
            ->post(route('purchases.close', $order), [
                'closed_reason' => 'Intento inválido',
                'pin' => '123456',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(PurchaseOrderStatus::Received, $order->fresh()->status);
    }

    public function test_force_close_requires_reason_and_valid_admin_pin(): void
    {
        $order = $this->createOrder(PurchaseOrderStatus::Partial, 10, 4);

        $response = $this->actingAs($this->user)
            ->from(route('purchases.show', $order))
            ->post(route('purchases.close', $order), [
                'closed_reason' => '   ',
                'pin' => '123456',
            ]);

        $response->assertSessionHasErrors('closed_reason');
        $this->assertSame(PurchaseOrderStatus::Partial, $order->fresh()->status);

        $response = $this->actingAs($this->user)
            ->from(route('purchases.show', $order))
            ->post(route('purchases.close', $order), [
                'closed_reason' => 'Proveedor no completó el pedido',
            ]);

        $response->assertSessionHasErrors('pin');
        $this->assertSame(PurchaseOrderStatus::Partial, $order->fresh()->status);

        $response = $this->actingAs($this->user)
            ->from(route('purchases.show', $order))
            ->post(route('purchases.close', $order), [
                'closed_reason' => 'Proveedor no completó el pedido',
                'pin' => '000000',
            ]);

        $response->assertSessionHasErrors('pin');
        $this->assertSame(PurchaseOrderStatus::Partial, $order->fresh()->status);
    }

    public function test_force_close_recalculates_total_from_received_and_keeps_inventory_unchanged(): void
    {
        $order = $this->createOrder(PurchaseOrderStatus::Partial, 10, 4);

        $stockAntes = $this->inventory->fresh()->quantity;

        $response = $this->actingAs($this->user)
            ->post(route('purchases.close', $order), [
                'closed_reason' => 'Pedido incompleto y vencido',
                'pin' => '123456',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();

        $this->assertSame(PurchaseOrderStatus::Closed, $order->status);
        $this->assertSame($this->admin->id, $order->closed_by);
        $this->assertNotNull($order->closed_at);
        $this->assertSame('Pedido incompleto y vencido', $order->closed_reason);

        // Recibido: 4 x 1.000 = 4.000; IVA 19% = 760; total = 4.760.
        $this->assertSame(4000.0, $order->subtotal);
        $this->assertSame(760.0, $order->tax);
        $this->assertSame(4760.0, $order->total);

        // El cierre forzoso no altera el stock del inventario.
        $this->assertSame($stockAntes, $this->inventory->fresh()->quantity);
    }

    public function test_closed_order_is_final_and_cannot_be_edited_received_or_deleted(): void
    {
        $order = $this->createOrder(PurchaseOrderStatus::Partial, 10, 4);

        $this->actingAs($this->user)
            ->post(route('purchases.close', $order), [
                'closed_reason' => 'Cierre definitivo',
                'pin' => '123456',
            ])
            ->assertSessionHas('success');

        $this->assertTrue($order->fresh()->isLocked());

        $this->actingAs($this->user)
            ->get(route('purchases.edit', $order))
            ->assertRedirect(route('purchases.show', $order))
            ->assertSessionHas('error');

        $this->actingAs($this->user)
            ->post(route('purchases.receive', $order), [])
            ->assertSessionHas('error');

        $this->actingAs($this->user)
            ->delete(route('purchases.destroy', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id]);
    }

    public function test_closed_status_cannot_be_set_through_normal_update(): void
    {
        $order = $this->createOrder(PurchaseOrderStatus::Partial, 10, 4);

        $response = $this->actingAs($this->user)
            ->from(route('purchases.edit', $order))
            ->put(route('purchases.update', $order), [
                'supplier_id' => $this->supplier->id,
                'order_date' => today()->format('Y-m-d'),
                'expected_date' => today()->format('Y-m-d'),
                'status' => PurchaseOrderStatus::Closed->value,
                'items' => [
                    [
                        'inventory_id' => $this->inventory->id,
                        'quantity' => 10,
                        'unit_price' => 1000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(PurchaseOrderStatus::Partial, $order->fresh()->status);
    }

    public function test_overdue_partial_order_is_highlighted_in_listing(): void
    {
        $diasVencimiento = (int) config('restauranager.po_overdue_days', 15);

        $vencida = $this->createOrder(
            PurchaseOrderStatus::Partial,
            10,
            4,
            today()->subDays($diasVencimiento + 1)
        );

        $this->actingAs($this->user)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('Parcial vencida');

        $this->assertTrue($vencida->fresh()->isOverduePartial());
    }

    public function test_partial_order_within_overdue_window_is_not_flagged(): void
    {
        $order = $this->createOrder(
            PurchaseOrderStatus::Partial,
            10,
            4,
            today()->subDays(2)
        );

        $this->assertFalse($order->fresh()->isOverduePartial());

        $this->actingAs($this->user)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertDontSee('Parcial vencida');
    }

    private function createOrder(
        PurchaseOrderStatus $status,
        float $quantity,
        float $received,
        ?\DateTimeInterface $expectedDate = null
    ): PurchaseOrder {
        $unitPrice = 1000.0;

        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id,
            'order_date' => today()->subDays(20),
            'expected_date' => $expectedDate ?? today()->addDays(3),
            'status' => $status->value,
            'notes' => 'Orden de prueba',
        ]);

        $order->items()->create([
            'inventory_id' => $this->inventory->id,
            'item_name' => $this->inventory->name,
            'quantity' => $quantity,
            'unit' => $this->inventory->unit,
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'received_qty' => $received,
        ]);

        $order->recalculateTotals();

        return $order->fresh();
    }
}
