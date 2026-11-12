<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duracionPorDefecto = (int) config('restauranager.reservation_duration_minutes', 120);

        Schema::table('reservations', function (Blueprint $table) use ($duracionPorDefecto) {
            $table->integer('duration_minutes')->default($duracionPorDefecto)->after('people_count');
            $table->dateTime('reservation_end')->nullable()->after('duration_minutes');
        });

        // Poblado retroactivo de reservation_end en registros existentes
        DB::table('reservations')->orderBy('id')->chunkById(100, function ($reservas) use ($duracionPorDefecto) {
            foreach ($reservas as $reserva) {
                $duracion = (int) ($reserva->duration_minutes ?: $duracionPorDefecto);
                $fin = Carbon::parse($reserva->reservation_date)->addMinutes($duracion);

                DB::table('reservations')->where('id', $reserva->id)->update([
                    'duration_minutes' => $duracion,
                    'reservation_end' => $fin,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'reservation_end']);
        });
    }
};
