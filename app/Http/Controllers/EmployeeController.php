<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Listado con búsqueda por nombre, filtro de rol y filtro de estado.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'role', 'active']);
        $filtros['role'] = is_string($filtros['role'] ?? null) && array_key_exists($filtros['role'], EmployeeRole::options())
            ? $filtros['role']
            : null;
        $filtros['active'] = in_array($filtros['active'] ?? null, ['1', '0'], true) ? $filtros['active'] : null;

        $registros = Employee::query()
            ->search($filtros['q'] ?? null)
            ->role($filtros['role'])
            ->activeFilter($filtros['active'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'registros' => $registros,
            'roles' => EmployeeRole::options(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('employees.create', [
            'roles' => EmployeeRole::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        Employee::create($data);

        return redirect()->route('employees.index')->with('success', 'Empleado registrado correctamente.');
    }

    public function show(Employee $employee)
    {
        return view('employees.show', [
            'empleado' => $employee,
            'turnos' => $employee->recentShifts(),
        ]);
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', [
            'empleado' => $employee,
            'roles' => EmployeeRole::options(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validar($request, $employee);

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Empleado eliminado correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request, ?Employee $empleado = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', 'in:'.implode(',', array_keys(EmployeeRole::options()))],
            'position' => ['nullable', 'string', 'max:255'],
            'document_id' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'document_id')->ignore($empleado)],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hired_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
        ], [
            'role.required' => 'Debes elegir el rol del empleado.',
            'role.in' => 'El rol elegido no es válido.',
            'salary.min' => 'El salario no puede ser negativo.',
        ], [
            'name' => 'nombre',
            'phone' => 'teléfono',
            'email' => 'correo electrónico',
            'role' => 'rol',
            'position' => 'cargo',
            'document_id' => 'documento',
            'salary' => 'salario',
            'hired_at' => 'fecha de contratación',
            'active' => 'activo',
        ]);

        $data['is_active'] = $request->boolean('active');

        unset($data['active']);

        return $data;
    }
}
