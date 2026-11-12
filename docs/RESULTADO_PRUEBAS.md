# Resultado de pruebas — RestauManager

Fecha de ejecución: 2026-11-11  
Comando ejecutado: `php artisan test`

## Resumen

| Indicador | Resultado |
|---|---:|
| Pruebas ejecutadas | 55 |
| Pruebas exitosas | 54 |
| Pruebas fallidas | 1 |
| Aserciones | 253 |
| Duración | 4.20 s |

Las pruebas nuevas agregadas para esta fase son **15** y pasaron todas (**61 aserciones**).

## Configuración de base de datos de pruebas

- `phpunit.xml` ya define `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`.
- No existe archivo `.env.testing` en el repositorio.
- El driver SQLite está disponible en el entorno, por lo que no fue necesario crear `restauranager_test`.
- Las pruebas se ejecutaron contra SQLite en memoria. No se usó ni se modificó la base de datos `restauranager`.

## Cobertura por archivo

### Pruebas unitarias

| Archivo | Regla que cubre |
|---|---|
| `tests/Unit/BillingBreakdownTest.php` | IVA incluido en el precio del menú y cargo por servicio según origen (`dine_in` 10 %, `takeaway` y `delivery` 0 %), incluyendo la opción de retirar el cargo. |
| `tests/Unit/AdminPinServiceTest.php` | Configuración, hash y verificación del PIN administrativo, bloqueo por intentos fallidos. |
| `tests/Unit/ExampleTest.php` | Prueba base de PHPUnit. |

### Pruebas de integración (Feature)

| Archivo | Regla que cubre |
|---|---|
| `tests/Feature/BillingByOriginTest.php` | Totales de orden con IVA incluido (10.000 → subtotal 8.403, IVA 1.597, total 10.000), cargo por servicio por origen y su retiro desde la factura. |
| `tests/Feature/ReservationOverlapTest.php` | Solapamiento de reservas con margen de rotación: 15:00 se acepta, 14:00 se rechaza y 14:15 se acepta frente a una reserva de 12:00 a 14:00. |
| `tests/Feature/RecipeInventoryTest.php` | Descuento automático de inventario al iniciar preparación según receta y bloqueo por stock insuficiente al crear la orden o al intentar prepararla. |
| `tests/Feature/OrderCancellationTest.php` | Cancelación por estado: orden `open` sin PIN, orden `preparing` con y sin PIN, registros de desperdicio y bloqueo cuando existen pagos. |
| `tests/Feature/BillPaymentTableCleaningTest.php` | Pago completo de factura envía la mesa a `cleaning` y la acción “Mesa lista” la devuelve a `available`. |
| `tests/Feature/PurchaseOrderClosureTest.php` | Cierre forzoso de órdenes de compra parciales con PIN y motivo, recálculo de totales, inventario intacto y bloqueo de estados finales. |
| `tests/Feature/TableCleaningAndAssignmentTest.php` | Estado `cleaning`, columnas de limpieza, transición con “Mesa lista” y validación de asignación de mesas. |
| `tests/Feature/Rn08AdjustmentsTest.php` | Auto-refresco de dashboard/cocina, reservas próximas, día calendario de Bogotá, límites de reportes y toggles desde listados. |
| `tests/Feature/ExampleTest.php` | Prueba base de Laravel que consulta `GET /`. |

## Pruebas fallidas

### `tests/Feature/ExampleTest.php`

**Prueba:** `the application returns a successful response`

**Mensaje de error:**

```text
Expected response status code [200] but received 404.
Failed asserting that 404 is identical to 200.

at tests\Feature\ExampleTest.php:17
```

**Causa:** es una prueba base preexistente que espera que `GET /` responda `200`, pero la aplicación no define una ruta para `/` y responde `404`. No está relacionada con las reglas implementadas en esta fase. No se modificó el código de la aplicación.

## Archivos cambiados

- `tests/Unit/BillingBreakdownTest.php` (nuevo)
- `tests/Feature/BillingByOriginTest.php` (nuevo)
- `tests/Feature/ReservationOverlapTest.php` (nuevo)
- `tests/Feature/RecipeInventoryTest.php` (nuevo)
- `tests/Feature/BillPaymentTableCleaningTest.php` (nuevo)
- `docs/RESULTADO_PRUEBAS.md` (nuevo)

No se modificó código de producción, migraciones, `phpunit.xml` ni pruebas existentes.
