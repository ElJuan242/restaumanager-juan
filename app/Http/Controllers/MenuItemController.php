<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    /**
     * Listado con búsqueda, filtros de categoría, disponibilidad y destacados.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'category', 'availability', 'featured']);

        $registros = MenuItem::query()
            ->search($filtros['q'] ?? null)
            ->category($filtros['category'] ?? null)
            ->availability($filtros['availability'] ?? null)
            ->featured($filtros['featured'] ?? null)
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('menus.items.index', [
            'registros' => $registros,
            'categorias' => MenuCategory::ordered()->active()->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('menus.items.create', [
            'categorias' => MenuCategory::ordered()->active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        MenuItem::create($data);

        return redirect()->route('menu-items.index')->with('success', 'Plato o bebida registrado en el menú.');
    }

    public function show(MenuItem $menuItem)
    {
        return view('menus.items.show', [
            'registro' => $menuItem,
        ]);
    }

    public function edit(MenuItem $menuItem)
    {
        return view('menus.items.edit', [
            'registro' => $menuItem,
            'categorias' => MenuCategory::ordered()->active()->get(),
        ]);
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $this->validar($request);

        $data['category_id'] = (int) $data['category_id'];

        $menuItem->update($data);

        return redirect()->route('menu-items.index')->with('success', 'Plato o bebida actualizado correctamente.');
    }

    /**
     * Cambia la disponibilidad del item y regresa al origen.
     */
    public function toggleAvailable(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        $mensaje = $menuItem->is_available
            ? sprintf('"%s" ahora está disponible en el menú.', $menuItem->name)
            : sprintf('"%s" ahora no está disponible en el menú.', $menuItem->name);

        if ($request->input('return_to') === 'show') {
            return redirect()->route('menu-items.show', $menuItem)->with('success', $mensaje);
        }

        return redirect()->route('menu-items.index')->with('success', $mensaje);
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return redirect()->route('menu-items.index')->with('success', 'Plato o bebida eliminado del menú.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     * 'ingredients' llega como texto libre separado por comas; el mutator del modelo lo guarda como JSON.
     */
    private function validar(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:menu_categories,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'ingredients' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ], [], [
            'name' => 'nombre',
            'description' => 'descripción',
            'price' => 'precio',
            'category_id' => 'categoría',
            'image' => 'imagen',
            'ingredients' => 'ingredientes',
            'is_available' => 'disponibilidad',
            'is_featured' => 'destacado',
            'sort_order' => 'orden',
        ]);

        $data = $request->only([
            'name',
            'description',
            'price',
            'category_id',
            'image',
            'ingredients',
            'sort_order',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
