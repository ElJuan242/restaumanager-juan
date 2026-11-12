<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MenuItem;
use App\Models\OrderItem;
use App\Models\RecipeItem;
use Illuminate\Validation\ValidationException;

class RecipeService
{
    /**
     * Devuelve los insumos que no alcanzan para preparar la cantidad indicada.
     *
     * @return array<int, array{name: string, required: float, available: float, unit: string}>
     */
    public function missingIngredients(MenuItem $menuItem, float $portions = 1): array
    {
        $menuItem->loadMissing('recipeItems.inventory');

        $faltantes = [];

        foreach ($menuItem->recipeItems as $recipe) {
            $inventario = $recipe->inventory;
            $requerido = round((float) $recipe->quantity * $portions, 2);

            if ($requerido <= 0) {
                continue;
            }

            $disponible = $inventario ? (float) $inventario->quantity : 0.0;

            if (! $inventario || ! $inventario->active || $disponible + 0.0001 < $requerido) {
                $faltantes[] = [
                    'name' => $inventario?->name ?? 'Insumo no disponible',
                    'required' => $requerido,
                    'available' => $disponible,
                    'unit' => $recipe->unit ?: ($inventario?->unit ?? 'und'),
                ];
            }
        }

        return $faltantes;
    }

    public function canProduce(MenuItem $menuItem, float $portions = 1): bool
    {
        return $this->missingIngredients($menuItem, $portions) === [];
    }

    public function missingMessage(MenuItem $menuItem, float $portions = 1): string
    {
        $faltantes = $this->missingIngredients($menuItem, $portions);

        if ($faltantes === []) {
            return '';
        }

        return $this->formatMissing($menuItem, $faltantes);
    }

    /**
     * Bloquea una operación si la receta no tiene stock suficiente.
     */
    public function assertStock(MenuItem $menuItem, float $portions = 1): void
    {
        $mensaje = $this->missingMessage($menuItem, $portions);

        if ($mensaje !== '') {
            throw ValidationException::withMessages([
                'items' => $mensaje,
            ]);
        }
    }

    /**
     * Descuenta los insumos de un item de orden y registra los movimientos.
     * Debe ejecutarse dentro de una transacción.
     *
     * @return array<int, int> IDs de inventario afectados.
     */
    public function consumeForOrderItem(OrderItem $orderItem): array
    {
        $menuItem = $orderItem->menuItem;

        if (! $menuItem) {
            return [];
        }

        $yaConsumido = InventoryMovement::query()
            ->where('type', InventoryMovementType::SaleConsumption->value)
            ->where('reference_type', OrderItem::class)
            ->where('reference_id', $orderItem->getKey())
            ->exists();

        if ($yaConsumido) {
            return [];
        }

        $menuItem->loadMissing('recipeItems');

        $consumos = [];
        $faltantes = [];
        $inventarioIds = [];

        foreach ($menuItem->recipeItems as $recipe) {
            $inventario = InventoryItem::query()
                ->whereKey($recipe->inventory_id)
                ->lockForUpdate()
                ->first();

            $requerido = round((float) $recipe->quantity * (float) $orderItem->quantity, 2);

            if ($requerido <= 0) {
                continue;
            }

            if (! $inventario || ! $inventario->active || (float) $inventario->quantity + 0.0001 < $requerido) {
                $faltantes[] = [
                    'name' => $inventario?->name ?? 'Insumo no disponible',
                    'required' => $requerido,
                    'available' => $inventario ? (float) $inventario->quantity : 0.0,
                    'unit' => $recipe->unit ?: ($inventario?->unit ?? 'und'),
                ];

                continue;
            }

            $consumos[] = [$inventario, $requerido];
            $inventarioIds[] = $inventario->id;
        }

        if ($faltantes !== []) {
            throw ValidationException::withMessages([
                'stock' => $this->formatMissing($menuItem, $faltantes),
            ]);
        }

        foreach ($consumos as [$inventario, $requerido]) {
            $nuevoSaldo = round((float) $inventario->quantity - $requerido, 2);

            $inventario->forceFill(['quantity' => $nuevoSaldo])->save();

            $inventario->recordMovement(
                InventoryMovementType::SaleConsumption,
                -1 * $requerido,
                null,
                $orderItem,
                auth()->id()
            );
        }

        return $inventarioIds;
    }

    /**
     * Registra como merma los insumos de un item que ya había consumido stock.
     *
     * El stock ya fue descontado al iniciar la preparación, por lo que este
     * movimiento solo deja la trazabilidad de la merma y no repone existencias.
     */
    public function recordWasteForOrderItem(OrderItem $orderItem, string $reason, ?int $userId = null): void
    {
        $menuItem = $orderItem->menuItem;

        if (! $menuItem) {
            return;
        }

        $menuItem->loadMissing('recipeItems.inventory');

        foreach ($menuItem->recipeItems as $recipe) {
            $inventario = $recipe->inventory;

            if (! $inventario) {
                continue;
            }

            $requerido = round((float) $recipe->quantity * (float) $orderItem->quantity, 2);

            if ($requerido <= 0) {
                continue;
            }

            $inventario->recordMovement(
                InventoryMovementType::Waste,
                -1 * $requerido,
                $reason,
                $orderItem,
                $userId
            );
        }
    }

    /**
     * Marca el plato como no disponible si su receta ya no alcanza para una porción.
     * No reactiva automáticamente: la reactivación es manual y valida stock.
     */
    public function deactivateIfInsufficient(MenuItem $menuItem): void
    {
        if (! $menuItem->recipeItems()->exists()) {
            return;
        }

        if ($menuItem->is_available && ! $this->canProduce($menuItem, 1)) {
            $menuItem->update(['is_available' => false]);
        }
    }

    /**
     * Revisa la disponibilidad de todos los platos que usan los insumos indicados.
     *
     * @param  array<int, int>  $inventarioIds
     */
    public function deactivateAffectedMenuItems(array $inventarioIds): void
    {
        if ($inventarioIds === []) {
            return;
        }

        $menuItemIds = RecipeItem::query()
            ->whereIn('inventory_id', array_unique($inventarioIds))
            ->pluck('menu_item_id')
            ->unique();

        if ($menuItemIds->isEmpty()) {
            return;
        }

        MenuItem::query()
            ->whereIn('id', $menuItemIds)
            ->get()
            ->each(fn (MenuItem $menuItem) => $this->deactivateIfInsufficient($menuItem));
    }

    /**
     * @param  array<int, array{name: string, required: float, available: float, unit: string}>  $faltantes
     */
    private function formatMissing(MenuItem $menuItem, array $faltantes): string
    {
        $detalle = collect($faltantes)->map(function (array $faltante) {
            return sprintf(
                '%s (requiere %s %s, disponible %s %s)',
                $faltante['name'],
                $this->formatQuantity($faltante['required']),
                $faltante['unit'],
                $this->formatQuantity($faltante['available']),
                $faltante['unit']
            );
        })->implode('; ');

        return sprintf('No hay stock suficiente para "%s": %s.', $menuItem->name, $detalle);
    }

    private function formatQuantity(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
