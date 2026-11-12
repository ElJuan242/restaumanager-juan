<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Rn08AdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Usuario RN08',
            'email' => 'rn08@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_auto_refreshes_every_60_seconds(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('60000', false);
    }

    public function test_kitchen_auto_refreshes_every_15_seconds_and_only_shows_active_orders(): void
    {
        $abierta = $this->createOrder(OrderStatus::Open);
        $preparando = $this->createOrder(OrderStatus::Preparing);
        $lista = $this->createOrder(OrderStatus::Ready);
        $servida = $this->createOrder(OrderStatus::Served);
        $cancelada = $this->createOrder(OrderStatus::Cancelled);

        $response = $this->actingAs($this->user)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('15000', false)
            ->assertSee($abierta->order_number)
            ->assertSee($preparando->order_number)
            ->assertSee($lista->order_number)
            ->assertDontSee($servida->order_number)
            ->assertDontSee($cancelada->order_number);

        $this->assertCount(3, $response->viewData('registros'));
    }

    public function test_upcoming_reservations_are_pending_or_confirmed_within_next_12_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00', 'America/Bogota'));

        $pendiente = $this->createReservation('Cliente Pendiente', '2026-11-11 10:30:00', ReservationStatus::Pending);
        $confirmada = $this->createReservation('Cliente Confirmada', '2026-11-11 21:59:00', ReservationStatus::Confirmed);
        $fueraDeRango = $this->createReservation('Cliente Fuera', '2026-11-11 22:01:00', ReservationStatus::Pending);
        $cancelada = $this->createReservation('Cliente Cancelada', '2026-11-11 11:00:00', ReservationStatus::Cancelled);
        $completada = $this->createReservation('Cliente Completada', '2026-11-11 11:00:00', ReservationStatus::Completed);

        $ids = Reservation::query()->upcoming()->pluck('id')->all();

        $this->assertEqualsCanonicalizing(
            [$pendiente->id, $confirmada->id],
            $ids
        );

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cliente Pendiente')
            ->assertSee('Cliente Confirmada')
            ->assertDontSee('Cliente Fuera')
            ->assertDontSee('Cliente Cancelada')
            ->assertDontSee('Cliente Completada');

        $this->actingAs($this->user)
            ->get(route('reservations.index', ['date' => 'upcoming']))
            ->assertOk()
            ->assertSee('Cliente Pendiente')
            ->assertSee('Cliente Confirmada')
            ->assertDontSee('Cliente Fuera')
            ->assertDontSee('Cliente Cancelada')
            ->assertDontSee('Cliente Completada');
    }

    public function test_reservations_today_uses_the_bogota_calendar_day(): void
    {
        config(['app.timezone' => 'UTC']);

        Carbon::setTestNow(Carbon::parse('2026-11-11 23:30:00', 'America/Bogota'));

        $hoyBogota = $this->createReservation('Cliente Hoy Bogotá', '2026-11-11 20:00:00', ReservationStatus::Pending);
        $mananaBogota = $this->createReservation('Cliente Mañana Bogotá', '2026-11-12 00:30:00', ReservationStatus::Pending);

        $ids = Reservation::query()->dateFilter('today')->pluck('id')->all();

        $this->assertSame([$hoyBogota->id], $ids);
        $this->assertNotContains($mananaBogota->id, $ids);
    }

    public function test_reports_reject_a_start_date_that_is_not_before_the_end_date(): void
    {
        $this->actingAs($this->user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['from' => '2026-11-11', 'to' => '2026-11-11']))
            ->assertSessionHasErrors('from');

        $this->actingAs($this->user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['from' => '2026-11-12', 'to' => '2026-11-11']))
            ->assertSessionHasErrors('from');
    }

    public function test_reports_reject_ranges_longer_than_365_days_and_accept_the_limit(): void
    {
        $hoy = Carbon::parse('2026-11-11', 'America/Bogota');
        Carbon::setTestNow($hoy);

        $this->actingAs($this->user)
            ->from(route('reports.index'))
            ->get(route('reports.index', [
                'from' => $hoy->copy()->subDays(366)->toDateString(),
                'to' => $hoy->toDateString(),
            ]))
            ->assertSessionHasErrors('to');

        $this->actingAs($this->user)
            ->get(route('reports.index', [
                'from' => $hoy->copy()->subDays(365)->toDateString(),
                'to' => $hoy->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_reservation_status_can_be_changed_from_the_listing(): void
    {
        $reserva = $this->createReservation('Cliente Estado', '2026-11-11 12:00:00', ReservationStatus::Pending);

        $this->actingAs($this->user)
            ->from(route('reservations.index'))
            ->patch(route('reservations.status', $reserva), [
                'status' => ReservationStatus::Confirmed->value,
            ])
            ->assertSessionHas('success');

        $this->assertSame(ReservationStatus::Confirmed, $reserva->fresh()->status);
    }

    public function test_menu_item_availability_can_be_toggled_from_the_listing(): void
    {
        $categoria = MenuCategory::create([
            'name' => 'Bebidas RN08',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $item = MenuItem::create([
            'category_id' => $categoria->id,
            'name' => 'Limonada RN08',
            'price' => 5000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->user)
            ->patch(route('menu-items.toggle-available', $item), [
                'return_to' => 'index',
            ])
            ->assertRedirect(route('menu-items.index'))
            ->assertSessionHas('success');

        $this->assertFalse($item->fresh()->is_available);

        $this->actingAs($this->user)
            ->patch(route('menu-items.toggle-available', $item), [
                'return_to' => 'index',
            ])
            ->assertRedirect(route('menu-items.index'))
            ->assertSessionHas('success');

        $this->assertTrue($item->fresh()->is_available);
    }

    private function createOrder(OrderStatus $status): Order
    {
        return Order::create([
            'status' => $status->value,
            'source' => OrderSource::DineIn->value,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);
    }

    private function createReservation(string $name, string $date, ReservationStatus $status): Reservation
    {
        return Reservation::create([
            'client_name' => $name,
            'reservation_date' => Carbon::parse($date, 'America/Bogota'),
            'duration_minutes' => 60,
            'people_count' => 2,
            'status' => $status->value,
        ]);
    }
}
