<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MenuItemController extends Controller
{
    public function __construct(private RecipeService $recipeService) {}

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
            ->withCount('recipeItems')
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
            'insumos' => InventoryItem::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);
        $receta = $this->validarReceta($request);

        DB::transaction(function () use ($data, $receta) {
            $menuItem = MenuItem::create($data);

            $this->guardarReceta($menuItem, $receta);
        });

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
        $menuItem->load('recipeItems.inventory');

        return view('menus.items.edit', [
            'registro' => $menuItem,
            'categorias' => MenuCategory::ordered()->active()->get(),
            'insumos' => InventoryItem::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $this->validar($request);
        $receta = $this->validarReceta($request);

        $data['category_id'] = (int) $data['category_id'];

        DB::transaction(function () use ($menuItem, $data, $receta) {
            $menuItem->update($data);

            $menuItem->recipeItems()->delete();
            $this->guardarReceta($menuItem, $receta);
        });

        return redirect()->route('menu-items.index')->with('success', 'Plato o bebida actualizado correctamente.');
    }

    /**
     * Cambia la disponibilidad del item y regresa al origen.
     * Solo permite activar cuando la receta tiene stock suficiente.
     */
    public function toggleAvailable(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $activar = ! $menuItem->is_available;

        if ($activar && ! $this->recipeService->canProduce($menuItem, 1)) {
            $mensaje = $this->recipeService->missingMessage($menuItem, 1);

            if ($request->input('return_to') === 'show') {
                return redirect()->route('menu-items.show', $menuItem)->with('error', $mensaje);
            }

            return redirect()->route('menu-items.index')->with('error', $mensaje);
        }

        $menuItem->update(['is_available' => $activar]);

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

    /**
     * Valida y normaliza las filas de receta enviadas desde el formulario.
     *
     * @return array<int, array{inventory_id: int, quantity: float, unit: string}>
     */
    private function validarReceta(Request $request): array
    {
        $request->validate([
            'recipe' => ['nullable', 'array'],
            'recipe.*.inventory_id' => [
                'nullable',
                Rule::exists('inventory', 'id')->where(fn ($query) => $query->where('active', true)->whereNull('deleted_at')),
            ],
            'recipe.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'recipe.*.unit' => ['nullable', 'string', 'max:20'],
        ], [
            'recipe.*.inventory_id.exists' => 'Uno de los insumos seleccionados no existe o está inactivo.',
            'recipe.*.quantity.min' => 'La cantidad de cada insumo debe ser mayor que cero.',
        ], [
            'recipe' => 'receta',
            'recipe.*.inventory_id' => 'insumo',
            'recipe.*.quantity' => 'cantidad',
            'recipe.*.unit' => 'unidad',
        ]);

        $filas = collect($request->input('recipe', []))
            ->filter(fn ($fila) => ! empty($fila['inventory_id']))
            ->values();

        $vistos = [];

        foreach ($filas as $indice => $fila) {
            if (! isset($fila['quantity']) || (float) $fila['quantity'] <= 0) {
                throw ValidationException::withMessages([
                    "recipe.$indice.quantity" => 'La cantidad de cada insumo de la receta debe ser mayor que cero.',
                ]);
            }

            $inventarioId = (int) $fila['inventory_id'];

            if (isset($vistos[$inventarioId])) {
                throw ValidationException::withMessages([
                    "recipe.$indice.inventory_id" => 'No puedes repetir el mismo insumo en la receta.',
                ]);
            }

            $vistos[$inventarioId] = true;
        }

        return $filas->all();
    }

    /**
     * Guarda las filas de receta tomando la unidad directamente del inventario.
     *
     * @param  array<int, array{inventory_id: int, quantity: float, unit: string}>  $filas
     */
    private function guardarReceta(MenuItem $menuItem, array $filas): void
    {
        foreach ($filas as $fila) {
            $inventario = InventoryItem::find($fila['inventory_id']);

            if (! $inventario) {
                continue;
            }

            $menuItem->recipeItems()->create([
                'inventory_id' => $inventario->id,
                'quantity' => round((float) $fila['quantity'], 2),
                'unit' => $inventario->unit,
            ]);
        }
    }
}
