<?php

namespace App\Http\Controllers;

use App\Enums\TableLocation;
use App\Enums\TableStatus;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    /**
     * Listado con búsqueda, filtros de estado, ubicación y capacidad mínima.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'status', 'location', 'min_capacity']);

        $registros = RestaurantTable::query()
            ->search($filtros['q'] ?? null)
            ->status($filtros['status'] ?? null)
            ->location($filtros['location'] ?? null)
            ->minCapacity($request->filled('min_capacity') ? (int) $request->input('min_capacity') : null)
            ->orderBy('table_number')
            ->paginate(15)
            ->withQueryString();

        $conteos = RestaurantTable::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('tables.index', [
            'registros' => $registros,
            'estados' => TableStatus::options(),
            'ubicaciones' => TableLocation::options(),
            'conteos' => $conteos,
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('tables.create', [
            'estados' => TableStatus::options(),
            'ubicaciones' => TableLocation::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        if ($data['status'] === TableStatus::Cleaning->value) {
            $data['cleaning_started_at'] = now();
        }

        RestaurantTable::create($data);

        return redirect()->route('tables.index')->with('success', 'Mesa registrada correctamente.');
    }

    public function show(RestaurantTable $table)
    {
        $table->load('readyConfirmedBy');

        return view('tables.show', [
            'registro' => $table,
            'reservas' => $table->reservations()
                ->with('table')
                ->orderByDesc('reservation_date')
                ->limit(10)
                ->get(),
            'ordenes' => $table->orders()
                ->with('table')
                ->latest()
                ->limit(10)
                ->get(),
            'estados' => TableStatus::options(),
            'ubicaciones' => TableLocation::options(),
        ]);
    }

    public function edit(RestaurantTable $table)
    {
        return view('tables.edit', [
            'registro' => $table,
            'estados' => TableStatus::options(),
            'ubicaciones' => TableLocation::options(),
        ]);
    }

    public function update(Request $request, RestaurantTable $table): RedirectResponse
    {
        $data = $this->validar($request, $table);

        if ($data['status'] === TableStatus::Cleaning->value && $table->status !== TableStatus::Cleaning) {
            $data['cleaning_started_at'] = now();
        }

        $table->update($data);

        return redirect()->route('tables.index')->with('success', 'Mesa actualizada correctamente.');
    }

    /**
     * Marca una mesa en limpieza como lista y disponible (RF-47).
     * Solo permitido si la mesa está en estado 'cleaning'.
     */
    public function ready(RestaurantTable $table): RedirectResponse
    {
        if ($table->status !== TableStatus::Cleaning) {
            return redirect()->back()->with(
                'error',
                sprintf(
                    'La mesa %s no se puede marcar como lista porque su estado es "%s" (solo permitido cuando está "Por limpiar").',
                    $table->table_number,
                    $table->status->label()
                )
            );
        }

        DB::transaction(function () use ($table) {
            $table->markAsReady(auth()->id());
        });

        return redirect()->back()->with(
            'success',
            sprintf('Mesa %s marcada como lista y disponible.', $table->table_number)
        );
    }

    /**
     * Protección de eliminación: no se puede eliminar una mesa con
     * reservas activas (pending/confirmed) u órdenes activas (open/preparing/ready).
     */
    public function destroy(RestaurantTable $table): RedirectResponse
    {
        $reservasActivas = $table->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $ordenesActivas = $table->orders()
            ->whereIn('status', ['open', 'preparing', 'ready'])
            ->count();

        if ($reservasActivas > 0 || $ordenesActivas > 0) {
            $partes = [];

            if ($reservasActivas > 0) {
                $partes[] = $reservasActivas.' reserva(s) activa(s)';
            }

            if ($ordenesActivas > 0) {
                $partes[] = $ordenesActivas.' orden(es) activa(s)';
            }

            return redirect()->route('tables.index')->with(
                'error',
                'No se puede eliminar la mesa '.$table->table_number.' porque tiene '.implode(' y ', $partes).'.'
            );
        }

        $table->update(['is_active' => false]);
        $table->delete();

        return redirect()->route('tables.index')->with('success', 'Mesa eliminada correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request, ?RestaurantTable $registro = null): array
    {
        $data = $request->validate([
            'table_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('restaurant_tables', 'table_number')->ignore($registro?->id),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'in:'.implode(',', array_keys(TableLocation::options()))],
            'status' => ['required', 'in:'.implode(',', array_keys(TableStatus::options()))],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'table_number.unique' => 'Ya existe una mesa con ese número.',
            'capacity.min' => 'La capacidad debe ser al menos 1 persona.',
        ], [
            'table_number' => 'número de mesa',
            'capacity' => 'capacidad',
            'location' => 'ubicación',
            'status' => 'estado',
            'is_active' => 'activa',
        ]);

        $data['capacity'] = (int) $data['capacity'];
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
