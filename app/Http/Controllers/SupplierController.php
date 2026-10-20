<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Listado con búsqueda por nombre y filtro por categoría de suministro.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'category', 'active']);

        $registros = Supplier::query()
            ->search($filtros['q'] ?? null)
            ->category($filtros['category'] ?? null)
            ->when($request->filled('active'), fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', [
            'registros' => $registros,
            'categorias' => InventoryCategory::options(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('suppliers.create', [
            'categorias' => InventoryCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Supplier $supplier)
    {
        $ordenes = $supplier->recentPurchaseOrders();

        return view('suppliers.show', [
            'proveedor' => $supplier,
            'ordenes' => $ordenes,
            'totalOrdenes' => $supplier->purchaseOrders()->count(),
            'montoTotal' => (float) $supplier->purchaseOrders()->sum('total'),
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', [
            'registro' => $supplier,
            'categorias' => InventoryCategory::options(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validar($request);

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        // La FK purchase_orders.supplier_id es restrictOnDelete: si hay órdenes asociadas el borrado falla.
        if ($supplier->purchaseOrders()->exists()) {
            return redirect()
                ->route('suppliers.index')
                ->with('error', 'No se puede eliminar el proveedor '.$supplier->name.' porque tiene órdenes de compra asociadas.');
        }

        try {
            $nombre = $supplier->name;
            $supplier->delete();
        } catch (\Throwable $e) {
            return redirect()
                ->route('suppliers.index')
                ->with('error', 'No se puede eliminar el proveedor '.$supplier->name.' porque tiene órdenes de compra asociadas.');
        }

        return redirect()->route('suppliers.index')->with('success', 'Proveedor '.$nombre.' eliminado correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(InventoryCategory::options()))],
            'notes' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nombre',
            'contact_name' => 'persona de contacto',
            'phone' => 'teléfono',
            'email' => 'correo electrónico',
            'address' => 'dirección',
            'category' => 'categoría',
            'notes' => 'notas',
            'active' => 'activo',
        ]);

        $data['is_active'] = $request->boolean('active');
        unset($data['active']);

        return $data;
    }
}
