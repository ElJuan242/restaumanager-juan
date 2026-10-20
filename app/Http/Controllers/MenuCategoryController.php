<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    /**
     * Listado de categorías del menú ordenadas, con conteo de items.
     */
    public function index()
    {
        $registros = MenuCategory::ordered()->get();

        return view('menus.categories.index', [
            'registros' => $registros,
        ]);
    }

    public function create()
    {
        return view('menus.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        MenuCategory::create($data);

        return redirect()->route('menu-categories.index')->with('success', 'Categoría registrada correctamente.');
    }

    public function edit(MenuCategory $menuCategory)
    {
        return view('menus.categories.edit', [
            'registro' => $menuCategory,
        ]);
    }

    public function update(Request $request, MenuCategory $menuCategory): RedirectResponse
    {
        $data = $this->validar($request);

        $menuCategory->update($data);

        return redirect()->route('menu-categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Protección: no se puede eliminar una categoría con items asociados.
     */
    public function destroy(MenuCategory $menuCategory): RedirectResponse
    {
        $totalItems = $menuCategory->items()->count();

        if ($totalItems > 0) {
            return redirect()->route('menu-categories.index')->with(
                'error',
                sprintf('No se puede eliminar la categoría %s porque tiene %d plato(s)/bebida(s) asociados.', $menuCategory->name, $totalItems)
            );
        }

        $menuCategory->delete();

        return redirect()->route('menu-categories.index')->with('success', 'Categoría eliminada correctamente.');
    }

    /**
     * Reglas compartidas de validación del módulo.
     * El slug lo genera el modelo automáticamente desde el nombre.
     */
    private function validar(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nombre',
            'description' => 'descripción',
            'sort_order' => 'orden',
            'active' => 'activo',
        ]);

        $data['is_active'] = $request->boolean('active');
        unset($data['active']);

        return $data;
    }
}
