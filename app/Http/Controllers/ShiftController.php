<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    /**
     * Listado filtrado por fecha (por defecto hoy) y por empleado.
     */
    public function index(Request $request)
    {
        $fecha = $request->input('date');
        $empleado = $request->input('employee_id');

        $filtros = [
            'date' => is_string($fecha) && $fecha !== '' ? $fecha : today()->toDateString(),
            'employee_id' => is_string($empleado) && $empleado !== '' ? (int) $empleado : null,
        ];

        $registros = Shift::query()
            ->dateFilter($filtros['date'])
            ->employee($filtros['employee_id'])
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('employees.shifts.index', [
            'registros' => $registros,
            'empleados' => Employee::active()->orderBy('name')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('employees.shifts.create', [
            'empleados' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        Shift::create($data);

        return redirect()
            ->route('shifts.index', ['date' => $data['date']])
            ->with('success', 'Turno registrado correctamente.');
    }

    public function edit(Shift $shift)
    {
        return view('employees.shifts.edit', [
            'turno' => $shift,
            'empleados' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $data = $this->validar($request);

        $shift->update($data);

        return redirect()
            ->route('shifts.index', ['date' => $data['date']])
            ->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('shifts.index')->with('success', 'Turno eliminado correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'actual_start' => ['nullable', 'string'],
            'actual_end' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'employee_id.required' => 'Debes elegir el empleado.',
            'employee_id.exists' => 'El empleado elegido no existe.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha no es válida.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de fin es obligatoria.',
        ], [
            'employee_id' => 'empleado',
            'date' => 'fecha',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'actual_start' => 'hora real de inicio',
            'actual_end' => 'hora real de fin',
            'notes' => 'notas',
        ]);

        // Horario planificado: la hora de fin debe ser posterior a la de inicio.
        // Si fuera menor o igual el turno cruzaría la medianoche, lo cual no se
        // permite en la planificación (la duración real la calcula el modelo).
        $inicio = strtotime($data['start_time']);
        $fin = strtotime($data['end_time']);

        if ($inicio !== false && $fin !== false && $fin <= $inicio) {
            throw ValidationException::withMessages([
                'end_time' => 'La hora de fin debe ser posterior a la hora de inicio.',
            ]);
        }

        return $data;
    }
}
