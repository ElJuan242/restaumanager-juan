<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Listado con búsqueda por nombre y filtro VIP.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'vip']);
        $filtros['vip'] = ($filtros['vip'] ?? null) === '1' ? '1' : null;

        $registros = Customer::query()
            ->search($filtros['q'] ?? null)
            ->vip($filtros['vip'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'registros' => $registros,
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        // Métricas del cliente: nunca se envían desde el formulario.
        $data['total_visits'] = 0;
        $data['total_spent'] = 0;
        $data['last_visit'] = null;

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', [
            'cliente' => $customer,
            'ordenes' => $customer->orders()->latest()->take(10)->get(),
            'reservas' => $customer->reservations()->orderBy('reservation_date')->take(10)->get(),
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'cliente' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->validar($request, $customer);

        // Solo campos del formulario: total_visits, total_spent y last_visit
        // se actualizan solos con Customer::registerVisit() y no se pisan aquí.
        $customer->update($request->only([
            'name',
            'phone',
            'email',
            'address',
            'birth_date',
            'notes',
        ]) + [
            'is_vip' => $request->boolean('is_vip'),
        ]);

        return redirect()->route('customers.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request, ?Customer $cliente = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($cliente)],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_vip' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nombre',
            'phone' => 'teléfono',
            'email' => 'correo electrónico',
            'address' => 'dirección',
            'birth_date' => 'fecha de nacimiento',
            'notes' => 'notas',
            'is_vip' => 'cliente VIP',
        ]);

        $data['is_vip'] = $request->boolean('is_vip');

        return $data;
    }
}
