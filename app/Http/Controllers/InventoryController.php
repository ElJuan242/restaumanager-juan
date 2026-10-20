<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Listado con búsqueda, filtro de categoría y filtro de stock bajo.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'category', 'low_stock']);

        $registros = InventoryItem::query()
            ->search($filtros['q'] ?? null)
            ->category($filtros['category'] ?? null)
            ->when($request->filled('low_stock'), fn ($q) => $q->lowStock())
            ->when(! $request->filled('q') && ! $request->filled('category') && ! $request->filled('low_stock'), fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.index', [
            'registros' => $registros,
            'categorias' => InventoryCategory::options(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('inventory.create', [
            'categorias' => InventoryCategory::options(),
            'unidades' => ['und', 'kg', 'g', 'lt', 'ml', 'caja', 'docena', 'libra'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        InventoryItem::create($data);

        return redirect()->route('inventory.index')->with('success', 'Producto registrado en el inventario.');
    }

    public function show(InventoryItem $inventory)
    {
        return view('inventory.show', [
            'registro' => $inventory,
            'categorias' => InventoryCategory::options(),
        ]);
    }

    public function edit(InventoryItem $inventory)
    {
        return view('inventory.edit', [
            'registro' => $inventory,
            'categorias' => InventoryCategory::options(),
            'unidades' => ['und', 'kg', 'g', 'lt', 'ml', 'caja', 'docena', 'libra'],
        ]);
    }

    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $data = $this->validar($request, $inventory);

        $inventory->update($data);

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Suma o resta cantidad sin permitir stock negativo.
     */
    public function adjustQuantity(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:sum,subtract',
            'amount' => 'required|numeric|min:0.01|max:1000000',
            'return_to' => 'nullable|in:index,show',
        ], [
            'action.in' => 'Debes elegir sumar o restar.',
            'amount.min' => 'La cantidad a ajustar debe ser mayor que cero.',
        ]);

        $delta = $request->action === 'sum'
            ? (float) $request->amount
            : -1 * (float) $request->amount;

        $anterior = $inventory->quantity;
        $inventory->adjustQuantity($delta);

        $mensaje = sprintf('Stock de "%s": %s → %s %s.', $inventory->name, $anterior, $inventory->quantity, $inventory->unit);

        if ($inventory->wasChanged('quantity') === false && $delta < 0) {
            $mensaje = sprintf('No se puede dejar el stock en negativo. "%s" se mantiene en %s %s.', $inventory->name, $inventory->quantity, $inventory->unit);
        }

        return redirect()
            ->route(($request->input('return_to') === 'show' ? 'inventory.show' : 'inventory.index'), $inventory)
            ->with('success', $mensaje);
    }

    public function destroy(InventoryItem $inventory): RedirectResponse
    {
        $inventory->update(['active' => false]);
        $inventory->delete();

        return redirect()->route('inventory.index')->with('success', 'Producto eliminado del inventario.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request, ?InventoryItem $registro = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:'.implode(',', array_keys(InventoryCategory::options()))],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nombre',
            'description' => 'descripción',
            'category' => 'categoría',
            'quantity' => 'cantidad',
            'unit' => 'unidad',
            'price' => 'precio',
            'min_stock' => 'stock mínimo',
            'active' => 'activo',
        ]);

        $data['active'] = $request->boolean('active');

        return $data;
    }
}
