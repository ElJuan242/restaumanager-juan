# RestauManager

Sistema de gestión de restaurante — **Laravel 12** (PHP 8.2+) · MySQL · Blade + Bootstrap 5.3.
Esta entrega cubre la **parte web**. La app móvil (Flutter) queda pendiente para una etapa posterior.

---

## Puesta en marcha

Requisitos: PHP 8.2+, Composer, MySQL/MariaDB.

```bash
# 1. Dependencias
composer install

# 2. Entorno (ajusta las credenciales de BD si hace falta)
cp .env.example .env
php artisan key:generate

# 3. Base de datos
mysql -u root -e "CREATE DATABASE restauranager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate

# 4. Datos iniciales (usuarios por defecto + datos de demostración)
php artisan db:seed

# 5. Servidor
php artisan serve
```

Abrir <http://localhost:8000>. Serás redirigido al login.

### Credenciales por defecto

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@restauranager.com` | `admin123` |
| Usuario | `mesero@restauranager.com` | `mesero123` |

> El `DemoSeeder` (mesas, inventario, menú, clientes, empleados, proveedores y reservas de ejemplo) solo se ejecuta fuera de producción. Para no cargarlo: `php artisan db:seed --class=DatabaseSeeder` con `APP_ENV=production`, o comenta la llamada en `DatabaseSeeder`.

---

## Módulos implementados

| # | Módulo | Rutas |
|---|---|---|
| 1 | Autenticación | `/login`, `/logout` |
| 2 | Dashboard | `/dashboard` |
| 3 | Inventario | `/inventory` |
| 4 | Reservas | `/reservations` |
| 5 | Mesas | `/tables` |
| 6 | Menú · Categorías | `/menu-categories` |
| 7 | Menú · Items | `/menu-items` |
| 8 | Clientes | `/customers` |
| 9 | Empleados | `/employees` |
| 10 | Turnos | `/shifts` |
| 11 | Órdenes / POS | `/orders`, `/kitchen` |
| 12 | Facturación | `/bills` |
| 13 | Proveedores | `/suppliers` |
| 14 | Órdenes de compra | `/purchases` |
| 15 | Reportes | `/reports` |

Total: **93 rutas web**.

---

## Lógica de negocio

| Regla | Implementación |
|---|---|
| IVA | 19% en órdenes y órdenes de compra (`config/restauranager.php`) |
| Cargo por servicio | 10% por defecto en facturas |
| Numeración | `ORD-YYMMDD-NNN`, `FAC-YYMMDD-NNN`, `PO-YYMMDD-NNN` |
| Órdenes | 3 orígenes, 5 estados, seguimiento de estado por item |
| Inventario | Soft-delete + `active`, detección de stock bajo, ajuste que nunca deja negativos |
| Facturación | Pagos parciales, auto-cambio a *Pagada*, liberación automática de mesa |
| Compras | Recepción parcial, incremento automático de inventario, bloqueo al recibir |
| Protecciones | No se eliminan mesas con reservas/órdenes activas, categorías con platos, facturas con pagos, proveedores con compras |
| Reservas | Validación de capacidad de la mesa elegida |

---

## Estructura relevante

```
app/
├── Enums/                 11 enums con label() y badge() en español
├── Http/Controllers/      16 controladores web
├── Models/                16 modelos Eloquent
└── helpers.php            money(), pct(), badge(), date_es(), time_es()…
config/restauranager.php   Tasas de IVA y cargo por servicio
database/migrations/       19 migraciones
database/seeders/          DatabaseSeeder + DemoSeeder
resources/views/           50+ plantillas Blade
routes/web.php             Todas las rutas web
```

### Enums

Un solo lugar define los estados y sus etiquetas/colores: `ReservationStatus`, `TableStatus`,
`TableLocation`, `OrderStatus`, `OrderSource`, `OrderItemStatus`, `BillStatus`, `PaymentMethod`,
`PurchaseOrderStatus`, `EmployeeRole`, `InventoryCategory`.

Cada uno expone `options()` (para los `<select>`), `label()` y `badge()`. Las vistas las
renderizan con el helper `badge($modelo->status)`.

---

## Pendiente

- Módulo 16: app móvil en Flutter (`flutter_app/`).
- Endpoints de API con Laravel Sanctum (26 endpoints previstos en los requerimientos).
