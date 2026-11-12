<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Listado con búsqueda por cliente, filtro de estado y filtro de fecha.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'status', 'date']);

        $registros = Reservation::query()
            ->search($filtros['q'] ?? null)
            ->status($filtros['status'] ?? null)
            ->dateFilter($filtros['date'] ?? null)
            ->with('table')
            ->orderByDesc('reservation_date')
            ->paginate(15)
            ->withQueryString();

        return view('reservations.index', [
            'registros' => $registros,
            'estados' => ReservationStatus::options(),
            'mesas' => RestaurantTable::active()->orderBy('table_number')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('reservations.create', [
            'mesas' => RestaurantTable::orderBy('table_number')->get(),
            'estados' => ReservationStatus::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $reserva = new Reservation($data);
        $errores = $reserva->validateTableErrors();

        if ($errores !== []) {
            return redirect()->route('reservations.create')
                ->withErrors($errores)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $reserva->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('reservations.create')
                ->with('error', 'No se pudo registrar la reserva. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('reservations.index')->with('success', 'Reserva registrada correctamente.');
    }

    public function show(Reservation $reservation)
    {
        return view('reservations.show', [
            'registro' => $reservation,
            'estados' => ReservationStatus::options(),
            'mesas' => RestaurantTable::orderBy('table_number')->get(),
        ]);
    }

    public function edit(Reservation $reservation)
    {
        return view('reservations.edit', [
            'registro' => $reservation,
            'mesas' => RestaurantTable::orderBy('table_number')->get(),
            'estados' => ReservationStatus::options(),
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $this->validar($request);

        $reservation->fill($data);
        $errores = $reservation->validateTableErrors();

        if ($errores !== []) {
            return redirect()->route('reservations.edit', $reservation)
                ->withErrors($errores)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $reservation->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('reservations.edit', $reservation)
                ->with('error', 'No se pudo actualizar la reserva. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('reservations.index')->with('success', 'Reserva actualizada correctamente.');
    }

    /**
     * Cambio rápido de estado (desde el listado).
     * Al confirmar se reserva la mesa; al cancelar o completar se libera.
     */
    public function updateStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(ReservationStatus::options()))],
        ], [
            'status.required' => 'Debes elegir un estado para la reserva.',
            'status.in' => 'El estado elegido no es válido.',
        ]);

        $nuevo = ReservationStatus::from($request->input('status'));

        $reservation->update(['status' => $nuevo->value]);

        $mesa = $reservation->table;

        if ($mesa) {
            if ($nuevo === ReservationStatus::Confirmed && $mesa->status === TableStatus::Available) {
                $mesa->setStatus(TableStatus::Reserved);
            } elseif (in_array($nuevo, [ReservationStatus::Cancelled, ReservationStatus::Completed], true)
                && $mesa->status === TableStatus::Reserved) {
                $otrasActivas = $mesa->reservations()
                    ->where('id', '!=', $reservation->id)
                    ->whereIn('status', [
                        ReservationStatus::Pending->value,
                        ReservationStatus::Confirmed->value,
                    ])
                    ->count();

                if ($otrasActivas === 0) {
                    $mesa->setStatus(TableStatus::Available);
                }
            }
        }

        return redirect()->back()->with('success', 'Estado de la reserva cambiado a "'.$nuevo->label().'".');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reserva eliminada correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request): array
    {
        $defaultDuration = (int) config('restauranager.reservation_duration_minutes', 120);

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'reservation_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isPast()) {
                        $fail('La fecha y hora de inicio de la reserva no puede ser en el pasado.');
                    }
                },
            ],
            'duration_minutes' => [
                'nullable',
                'integer',
                'min:30',
                'max:480',
                function ($attribute, $value, $fail) {
                    if ($value && $value % 15 !== 0) {
                        $fail('La duración debe ser un múltiplo de 15 minutos.');
                    }
                },
            ],
            'people_count' => ['required', 'integer', 'min:1'],
            'restaurant_table_id' => ['nullable', 'exists:restaurant_tables,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(ReservationStatus::options()))],
            'notes' => ['nullable', 'string'],
        ], [
            'people_count.min' => 'La reserva debe ser para al menos 1 persona.',
            'client_email.email' => 'El correo electrónico no es válido.',
            'duration_minutes.min' => 'La duración mínima de la reserva es de 30 minutos.',
            'duration_minutes.max' => 'La duración máxima de la reserva es de 480 minutos (8 horas).',
        ], [
            'client_name' => 'nombre del cliente',
            'client_phone' => 'teléfono',
            'client_email' => 'correo electrónico',
            'reservation_date' => 'fecha de la reserva',
            'duration_minutes' => 'duración',
            'people_count' => 'número de personas',
            'restaurant_table_id' => 'mesa',
            'status' => 'estado',
            'notes' => 'notas',
        ]);

        $duracion = ! empty($data['duration_minutes'])
            ? (int) $data['duration_minutes']
            : $defaultDuration;

        $inicio = Carbon::parse($data['reservation_date']);

        $data['duration_minutes'] = $duracion;
        $data['reservation_end'] = $inicio->copy()->addMinutes($duracion);

        $data['people_count'] = (int) $data['people_count'];
        $data['restaurant_table_id'] = ! empty($data['restaurant_table_id']) ? (int) $data['restaurant_table_id'] : null;
        $data['client_phone'] = (($data['client_phone'] ?? null) ?: null);
        $data['client_email'] = (($data['client_email'] ?? null) ?: null);
        $data['notes'] = (($data['notes'] ?? null) ?: null);

        return $data;
    }
}
