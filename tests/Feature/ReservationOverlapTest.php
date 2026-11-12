<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\TableLocation;
use App\Enums\TableStatus;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationOverlapTest extends TestCase
{
    use RefreshDatabase;

    protected RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00:00', 'America/Bogota'));

        $this->table = RestaurantTable::create([
            'table_number' => 'M-OVERLAP',
            'capacity' => 6,
            'location' => TableLocation::SalonPrincipal->value,
            'status' => TableStatus::Available->value,
            'is_active' => true,
        ]);

        $this->createReservation('2026-11-11 12:00:00', 120);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reservation_starting_after_the_turnover_buffer_is_accepted(): void
    {
        $candidata = $this->makeCandidate('2026-11-11 15:00:00');

        $this->assertNull($candidata->findConflictingReservation());
    }

    public function test_reservation_starting_exactly_when_the_previous_one_ends_is_rejected(): void
    {
        $candidata = $this->makeCandidate('2026-11-11 14:00:00');

        $this->assertNotNull($candidata->findConflictingReservation());
    }

    public function test_reservation_starting_at_the_end_of_the_turnover_buffer_is_accepted(): void
    {
        $candidata = $this->makeCandidate('2026-11-11 14:15:00');

        $this->assertNull($candidata->findConflictingReservation());
    }

    private function createReservation(string $inicio, int $duracion): Reservation
    {
        return Reservation::create([
            'client_name' => 'Reserva existente',
            'reservation_date' => Carbon::parse($inicio, 'America/Bogota'),
            'duration_minutes' => $duracion,
            'people_count' => 2,
            'restaurant_table_id' => $this->table->id,
            'status' => ReservationStatus::Confirmed->value,
        ]);
    }

    private function makeCandidate(string $inicio): Reservation
    {
        return new Reservation([
            'client_name' => 'Reserva candidata',
            'reservation_date' => Carbon::parse($inicio, 'America/Bogota'),
            'duration_minutes' => 60,
            'people_count' => 2,
            'restaurant_table_id' => $this->table->id,
            'status' => ReservationStatus::Pending->value,
        ]);
    }
}
