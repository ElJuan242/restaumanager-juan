<?php

namespace Database\Seeders;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Datos de demostración para explorar el sistema.
     */
    public function run(): void
    {
        $this->seedMesas();
        $this->seedInventario();
        $this->seedMenu();
        $this->seedClientes();
        $this->seedEmpleados();
        $this->seedProveedores();
        $this->seedReservas();
    }

    private function seedMesas(): void
    {
        if (RestaurantTable::count() > 0) {
            return;
        }

        $mesas = [
            ['M-01', 2, 'Salon Principal', TableStatus::Available->value],
            ['M-02', 4, 'Salon Principal', TableStatus::Available->value],
            ['M-03', 4, 'Salon Principal', TableStatus::Occupied->value],
            ['M-04', 6, 'Salon Principal', TableStatus::Available->value],
            ['T-01', 4, 'Terraza', TableStatus::Available->value],
            ['T-02', 4, 'Terraza', TableStatus::Reserved->value],
            ['V-01', 8, 'VIP', TableStatus::Available->value],
            ['B-01', 2, 'Barra', TableStatus::Available->value],
            ['B-02', 2, 'Barra', TableStatus::Maintenance->value],
            ['P-01', 10, 'Salon Privado', TableStatus::Available->value],
            ['E-01', 4, 'Exterior', TableStatus::Available->value],
        ];

        foreach ($mesas as [$numero, $capacidad, $zona, $estado]) {
            RestaurantTable::create([
                'table_number' => $numero,
                'capacity' => $capacidad,
                'location' => $zona,
                'status' => $estado,
                'is_active' => true,
            ]);
        }
    }

    private function seedInventario(): void
    {
        if (InventoryItem::count() > 0) {
            return;
        }

        $items = [
            ['Pechuga de pollo', 'Carnes', 25, 'kg', 8500, 5],
            ['Carne de res molida', 'Carnes', 18, 'kg', 16000, 5],
            ['Salmón fresco', 'Carnes', 6, 'kg', 32000, 8],
            ['Tomate', 'Verduras', 12, 'kg', 2400, 3],
            ['Cebolla', 'Verduras', 15, 'kg', 1800, 3],
            ['Aguacate', 'Verduras', 2.5, 'kg', 6500, 4],
            ['Arroz blanco', 'Granos', 40, 'kg', 3200, 10],
            ['Frijol rojo', 'Granos', 22, 'kg', 4100, 8],
            ['Gaseosa cola', 'Bebidas', 96, 'und', 2500, 24],
            ['Agua mineral', 'Bebidas', 140, 'und', 1500, 30],
            ['Leche entera', 'Lacteos', 18, 'lt', 3900, 10],
            ['Queso mozzarella', 'Lacteos', 4.5, 'kg', 18000, 3],
            ['Aceite de oliva', 'Aceites', 8, 'lt', 24000, 2],
            ['Sal refinada', 'Condimentos', 10, 'kg', 2200, 2],
            ['Pimienta molida', 'Condimentos', 1.2, 'kg', 21000, 1],
            ['Servilletas de papel', 'Insumos', 30, 'caja', 6500, 5],
            ['Guantes desechables', 'Insumos', 4, 'caja', 9000, 2],
        ];

        foreach ($items as [$nombre, $categoria, $cantidad, $unidad, $precio, $minimo]) {
            InventoryItem::create([
                'name' => $nombre,
                'category' => $categoria,
                'quantity' => $cantidad,
                'unit' => $unidad,
                'price' => $precio,
                'min_stock' => $minimo,
                'description' => null,
                'active' => true,
            ]);
        }
    }

    private function seedMenu(): void
    {
        if (MenuCategory::count() > 0) {
            return;
        }

        $categorias = [
            ['Entradas', 1],
            ['Platos fuertes', 2],
            ['Bebidas', 3],
            ['Postres', 4],
        ];

        $models = [];

        foreach ($categorias as [$nombre, $orden]) {
            $models[$nombre] = MenuCategory::create([
                'name' => $nombre,
                'description' => 'Categoría de '.mb_strtolower($nombre),
                'sort_order' => $orden,
                'is_active' => true,
            ]);
        }

        $platos = [
            ['Nachos con guacamole', 'Entradas', 18000, ['totopos', 'aguacate', 'queso'], true],
            ['Alitas BBQ', 'Entradas', 22000, ['alitas', 'salsa bbq'], true],
            ['Sopa del día', 'Entradas', 9000, ['verduras', 'pollo'], false],
            ['Bandeja paisa', 'Platos fuertes', 28000, ['carne molida', 'arroz', 'frijol', 'huevo', 'platano'], true],
            ['Pechuga a la plancha', 'Platos fuertes', 24000, ['pechuga', 'ensalada', 'papas'], false],
            ['Salmón al horno', 'Platos fuertes', 38000, ['salmón', 'verduras'], false],
            ['Hamburguesa clásica', 'Platos fuertes', 21000, ['carne', 'queso', 'pan', 'lechuga'], true],
            ['Ensalada César', 'Platos fuertes', 17000, ['lechuga', 'pollo', 'queso'], false],
            ['Gaseosa 400ml', 'Bebidas', 4500, [], true],
            ['Jugo natural 500ml', 'Bebidas', 6500, ['fruta'], false],
            ['Café americano', 'Bebidas', 5000, [], false],
            ['Brownie con helado', 'Postres', 12000, ['chocolate', 'helado'], true],
            ['Flan napolitano', 'Postres', 10000, [], false],
        ];

        foreach ($platos as [$nombre, $categoria, $precio, $ingredientes, $destacado]) {
            MenuItem::create([
                'name' => $nombre,
                'category_id' => $models[$categoria]->id,
                'description' => 'Delicioso plato de nuestra cocina.',
                'price' => $precio,
                'ingredients' => $ingredientes === [] ? null : $ingredientes,
                'is_available' => true,
                'is_featured' => $destacado,
                'sort_order' => 0,
            ]);
        }
    }

    private function seedClientes(): void
    {
        if (Customer::count() > 0) {
            return;
        }

        $clientes = [
            ['María González', '3001234567', 'maria.gonzalez@mail.com', true, 12, 480000],
            ['Carlos Pérez', '3109876543', 'carlos.perez@mail.com', false, 5, 150000],
            ['Ana Rodríguez', '3205551234', 'ana.rodriguez@mail.com', true, 21, 920000],
            ['Luis Fernández', '3154448877', 'luis.fernandez@mail.com', false, 3, 78000],
            ['Sofía Martínez', '3047772211', 'sofia.martinez@mail.com', false, 8, 260000],
            ['Jorge Ramírez', '3182223344', 'jorge.ramirez@mail.com', true, 15, 640000],
        ];

        foreach ($clientes as [$nombre, $tel, $correo, $vip, $visitas, $gasto]) {
            Customer::create([
                'name' => $nombre,
                'phone' => $tel,
                'email' => $correo,
                'address' => 'Calle 10 # 20-30, Bogotá',
                'birth_date' => now()->subYears(rand(25, 55))->toDateString(),
                'notes' => null,
                'total_visits' => $visitas,
                'total_spent' => $gasto,
                'last_visit' => now()->subDays(rand(1, 40))->toDateString(),
                'is_vip' => $vip,
            ]);
        }
    }

    private function seedEmpleados(): void
    {
        if (Employee::count() > 0) {
            return;
        }

        $empleados = [
            ['Pedro Sánchez', 'Mesero', '3001112233', 1600000],
            ['Laura Torres', 'Cocinero', '3102223344', 2100000],
            ['Diego Herrera', 'Bartender', '3203334455', 1750000],
            ['Valentina Ruiz', 'Anfitrion', '3154446677', 1550000],
            ['Andrés Molina', 'Gerente', '3185557788', 3200000],
        ];

        foreach ($empleados as [$nombre, $rol, $tel, $salario]) {
            Employee::create([
                'name' => $nombre,
                'role' => $rol,
                'phone' => $tel,
                'email' => \Illuminate\Support\Str::slug($nombre, '.').'@restauranager.com',
                'position' => $rol,
                'document_id' => rand(10000000, 99999999).'',
                'salary' => $salario,
                'hired_at' => now()->subMonths(rand(2, 36))->toDateString(),
                'is_active' => true,
            ]);
        }
    }

    private function seedProveedores(): void
    {
        if (Supplier::count() > 0) {
            return;
        }

        $proveedores = [
            ['Distribuidora El Campo', 'Carnes', 'Hernán Díaz'],
            ['Verduras del Valle', 'Verduras', 'Rosa Mejía'],
            ['Bebidas Express', 'Bebidas', 'Iván Castro'],
            ['Lácteos Andinos', 'Lacteos', 'Paula Núñez'],
        ];

        foreach ($proveedores as [$nombre, $categoria, $contacto]) {
            Supplier::create([
                'name' => $nombre,
                'contact_name' => $contacto,
                'phone' => '601'.rand(1000000, 9999999),
                'email' => \Illuminate\Support\Str::slug($nombre, '.').'@proveedor.com',
                'address' => 'Zona industrial, Bogotá',
                'category' => $categoria,
                'notes' => null,
                'is_active' => true,
            ]);
        }
    }

    private function seedReservas(): void
    {
        if (Reservation::count() > 0) {
            return;
        }

        $reservas = [
            ['María González', '3001234567', now()->addHours(2), 2, 2, ReservationStatus::Confirmed->value],
            ['Carlos Pérez', '3109876543', now()->addHours(5), 4, 2, ReservationStatus::Pending->value],
            ['Ana Rodríguez', '3205551234', now()->addDay()->setTime(19, 0), 6, 4, ReservationStatus::Confirmed->value],
            ['Luis Fernández', '3154448877', now()->subDay()->setTime(13, 0), 3, 1, ReservationStatus::Completed->value],
        ];

        foreach ($reservas as [$nombre, $tel, $fecha, $personas, $mesa, $estado]) {
            Reservation::create([
                'client_name' => $nombre,
                'client_phone' => $tel,
                'client_email' => null,
                'reservation_date' => $fecha,
                'people_count' => $personas,
                'restaurant_table_id' => RestaurantTable::where('capacity', '>=', $personas)->value('id'),
                'status' => $estado,
                'notes' => 'Reserva de demostración.',
            ]);
        }
    }
}
