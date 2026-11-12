<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\TableLocation;
use App\Enums\TableStatus;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableCleaningAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Mesero Test',
            'email' => 'mesero@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);

        $category = MenuCategory::create([
            'name' => 'Platos Fuertes',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->menuItem = MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Bandeja Paisa',
            'price' => 35000,
            'is_available' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_table_status_enum_has_cleaning_case_with_label_and_badge(): void
    {
        $status = TableStatus::Cleaning;

        $this->assertSame('cleaning', $status->value);
        $this->assertSame('Por limpiar', $status->label());
        $this->assertSame('bg-info text-dark', $status->badge());
        $this->assertArrayHasKey('cleaning', TableStatus::options());
    }

    public function test_restaurant_tables_table_has_cleaning_and_ready_columns(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-99',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Cleaning->value,
            'cleaning_started_at' => now(),
            'ready_confirmed_by' => $this->user->id,
            'ready_confirmed_at' => now(),
            'is_active' => true,
        ]);

        $this->assertNotNull($table->cleaning_started_at);
        $this->assertSame($this->user->id, $table->ready_confirmed_by);
        $this->assertNotNull($table->ready_confirmed_at);
        $this->assertTrue($table->readyConfirmedBy->is($this->user));
    }

    public function test_patch_table_ready_transitions_cleaning_table_to_available_and_records_user_and_timestamp(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-05',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Cleaning->value,
            'cleaning_started_at' => now()->subMinutes(20),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tables.ready', $table));

        $response->assertSessionHas('success');

        $table->refresh();
        $this->assertSame(TableStatus::Available, $table->status);
        $this->assertSame($this->user->id, $table->ready_confirmed_by);
        $this->assertNotNull($table->ready_confirmed_at);
    }

    public function test_patch_table_ready_rejects_tables_not_in_cleaning_status(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-06',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Occupied->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tables.ready', $table));

        $response->assertSessionHas('error');

        $table->refresh();
        $this->assertSame(TableStatus::Occupied, $table->status);
        $this->assertNull($table->ready_confirmed_at);
    }

    public function test_cannot_assign_table_in_occupied_status_to_new_order(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-01',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Occupied->value,
            'is_active' => true,
        ]);

        $this->assertFalse($table->isAssignableToOrder());

        $response = $this->actingAs($this->user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'source' => OrderSource::DineIn->value,
                'restaurant_table_id' => $table->id,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('restaurant_table_id');
        $this->assertDatabaseMissing('orders', [
            'restaurant_table_id' => $table->id,
        ]);
    }

    public function test_cannot_assign_table_in_cleaning_status_to_new_order(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-02',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Cleaning->value,
            'cleaning_started_at' => now(),
            'is_active' => true,
        ]);

        $this->assertFalse($table->isAssignableToOrder());

        $response = $this->actingAs($this->user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'source' => OrderSource::DineIn->value,
                'restaurant_table_id' => $table->id,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('restaurant_table_id');
        $this->assertDatabaseMissing('orders', [
            'restaurant_table_id' => $table->id,
        ]);
    }

    public function test_cannot_assign_table_in_maintenance_status_to_new_order(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-03',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Maintenance->value,
            'is_active' => true,
        ]);

        $this->assertFalse($table->isAssignableToOrder());

        $response = $this->actingAs($this->user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'source' => OrderSource::DineIn->value,
                'restaurant_table_id' => $table->id,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('restaurant_table_id');
        $this->assertDatabaseMissing('orders', [
            'restaurant_table_id' => $table->id,
        ]);
    }

    public function test_can_assign_available_table_to_new_order(): void
    {
        $table = RestaurantTable::create([
            'table_number' => 'M-04',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Available->value,
            'is_active' => true,
        ]);

        $this->assertTrue($table->isAssignableToOrder());

        $response = $this->actingAs($this->user)
            ->post(route('orders.store'), [
                'source' => OrderSource::DineIn->value,
                'restaurant_table_id' => $table->id,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', [
            'restaurant_table_id' => $table->id,
        ]);

        $table->refresh();
        $this->assertSame(TableStatus::Occupied, $table->status);
    }

    public function test_dashboard_highlights_tables_with_more_than_15_minutes_in_cleaning(): void
    {
        // Mesa con 20 minutos en limpieza (debe resaltarse)
        $delayedTable = RestaurantTable::create([
            'table_number' => 'M-10',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Cleaning->value,
            'cleaning_started_at' => now()->subMinutes(20),
            'is_active' => true,
        ]);

        // Mesa con solo 5 minutos en limpieza (no retrasada)
        $freshTable = RestaurantTable::create([
            'table_number' => 'M-11',
            'capacity' => 4,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Cleaning->value,
            'cleaning_started_at' => now()->subMinutes(5),
            'is_active' => true,
        ]);

        $this->assertTrue($delayedTable->isDelayedCleaning(15));
        $this->assertFalse($freshTable->isDelayedCleaning(15));

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Mesa M-10');
        $response->assertSee('Alerta de rotación');
    }
}
