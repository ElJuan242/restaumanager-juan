<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Models\Reservation;
use App\Models\RestaurantTable;
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

        DB::beginTransaction();

        try {
            $reserva = Reservation::create($data);
            $error = $reserva->validateTable();

            if ($error !== null) {
                DB::rollBack();

                return redirect()->route('reservations.create')
                    ->with('error', $error)
                    ->withInput();
            }

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
        $data = $this->validar($request, $reservation);

        DB::beginTransaction();

        try {
            $reservation->update($data);
            $error = $reservation->validateTable();

            if ($error !== null) {
                DB::rollBack();

                return redirect()->route('reservations.edit', $reservation)
                    ->with('error', $error)
                    ->withInput();
            }

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
    private function validar(Request $request, ?Reservation $registro = null): array
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'reservation_date' => ['required', 'date'],
            'people_count' => ['required', 'integer', 'min:1'],
            'restaurant_table_id' => ['nullable', 'exists:restaurant_tables,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(ReservationStatus::options()))],
            'notes' => ['nullable', 'string'],
        ], [
            'people_count.min' => 'La reserva debe ser para al menos 1 persona.',
            'client_email.email' => 'El correo electrónico no es válido.',
        ], [
            'client_name' => 'nombre del cliente',
            'client_phone' => 'teléfono',
            'client_email' => 'correo electrónico',
            'reservation_date' => 'fecha de la reserva',
            'people_count' => 'número de personas',
            'restaurant_table_id' => 'mesa',
            'status' => 'estado',
            'notes' => 'notas',
        ]);

        $data['people_count'] = (int) $data['people_count'];
        $data['restaurant_table_id'] = ! empty($data['restaurant_table_id']) ? (int) $data['restaurant_table_id'] : null;
        $data['client_phone'] = (($data['client_phone'] ?? null) ?: null);
        $data['client_email'] = (($data['client_email'] ?? null) ?: null);
        $data['notes'] = (($data['notes'] ?? null) ?: null);

        return $data;
    }
}
