<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Inventario
    |----------------------------------------------------------------------
    */

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{inventory}', [InventoryController::class, 'show'])->name('show');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
        Route::patch('/{inventory}/adjust-quantity', [InventoryController::class, 'adjustQuantity'])->name('adjust-quantity');
        Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Reservas
    |----------------------------------------------------------------------
    */

    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [ReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/edit', [ReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [ReservationController::class, 'update'])->name('update');
        Route::patch('/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('status');
        Route::delete('/{reservation}', [ReservationController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Mesas del restaurante
    |----------------------------------------------------------------------
    */

    Route::prefix('tables')->name('tables.')->group(function () {
        Route::get('/', [RestaurantTableController::class, 'index'])->name('index');
        Route::get('/create', [RestaurantTableController::class, 'create'])->name('create');
        Route::post('/', [RestaurantTableController::class, 'store'])->name('store');
        Route::get('/{table}', [RestaurantTableController::class, 'show'])->name('show');
        Route::get('/{table}/edit', [RestaurantTableController::class, 'edit'])->name('edit');
        Route::put('/{table}', [RestaurantTableController::class, 'update'])->name('update');
        Route::delete('/{table}', [RestaurantTableController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Menú · Categorías
    |----------------------------------------------------------------------
    */

    Route::prefix('menu-categories')->name('menu-categories.')->group(function () {
        Route::get('/', [MenuCategoryController::class, 'index'])->name('index');
        Route::get('/create', [MenuCategoryController::class, 'create'])->name('create');
        Route::post('/', [MenuCategoryController::class, 'store'])->name('store');
        Route::get('/{menuCategory}/edit', [MenuCategoryController::class, 'edit'])->name('edit');
        Route::put('/{menuCategory}', [MenuCategoryController::class, 'update'])->name('update');
        Route::delete('/{menuCategory}', [MenuCategoryController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Menú · Items
    |----------------------------------------------------------------------
    */

    Route::prefix('menu-items')->name('menu-items.')->group(function () {
        Route::get('/', [MenuItemController::class, 'index'])->name('index');
        Route::get('/create', [MenuItemController::class, 'create'])->name('create');
        Route::post('/', [MenuItemController::class, 'store'])->name('store');
        Route::get('/{menuItem}', [MenuItemController::class, 'show'])->name('show');
        Route::get('/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('edit');
        Route::put('/{menuItem}', [MenuItemController::class, 'update'])->name('update');
        Route::patch('/{menuItem}/toggle-available', [MenuItemController::class, 'toggleAvailable'])->name('toggle-available');
        Route::delete('/{menuItem}', [MenuItemController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Clientes
    |----------------------------------------------------------------------
    */

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Empleados
    |----------------------------------------------------------------------
    */

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Turnos / Horarios
    |----------------------------------------------------------------------
    */

    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::get('/create', [ShiftController::class, 'create'])->name('create');
        Route::post('/', [ShiftController::class, 'store'])->name('store');
        Route::get('/{shift}/edit', [ShiftController::class, 'edit'])->name('edit');
        Route::put('/{shift}', [ShiftController::class, 'update'])->name('update');
        Route::delete('/{shift}', [ShiftController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Órdenes / Punto de venta
    |----------------------------------------------------------------------
    */

    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::post('/{order}/add-items', [OrderController::class, 'addItems'])->name('add-items');
        Route::patch('/{orderItem}/item-status', [OrderController::class, 'updateItemStatus'])->name('item-status');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Facturación
    |----------------------------------------------------------------------
    */

    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/', [BillsController::class, 'index'])->name('index');
        Route::get('/create', [BillsController::class, 'create'])->name('create');
        Route::post('/', [BillsController::class, 'store'])->name('store');
        Route::get('/{bill}', [BillsController::class, 'show'])->name('show');
        Route::post('/{bill}/pay', [BillsController::class, 'pay'])->name('pay');
        Route::delete('/{bill}', [BillsController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Proveedores
    |----------------------------------------------------------------------
    */

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Órdenes de compra
    |----------------------------------------------------------------------
    */

    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchase}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{purchase}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
        Route::put('/{purchase}', [PurchaseOrderController::class, 'update'])->name('update');
        Route::post('/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->name('receive');
        Route::delete('/{purchase}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Reportes
    |----------------------------------------------------------------------
    */

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
