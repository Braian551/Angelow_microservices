# Rendimiento de base de datos en microservicios

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Objetos incorporados](#objetos-incorporados)
- [Criterios de seguridad](#criterios-de-seguridad)
- [Operación](#operación)
- [Validación recomendada](#validación-recomendada)
- [Validación ejecutada](#validación-ejecutada)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Implementación realizada el 2026-06-29 a partir del análisis del archivo SQL completo recibido y del estado actual de los microservicios. El objetivo es mejorar tiempos de lectura en catálogo, búsqueda, descuentos, pedidos y notificaciones sin cambiar contratos de API ni duplicar efectos de escritura.

## Objetos incorporados

| Microservicio | Migración | Objeto | Uso |
| --- | --- | --- | --- |
| catalog-service | `2026_06_29_010000_create_catalog_performance_objects.php` | `catalog_product_listing_view` | Consolida datos de producto, imagen principal, categoría, colección, precios, stock y reseñas para listados. |
| catalog-service | `2026_06_29_010000_create_catalog_performance_objects.php` | `catalog_search_products_and_terms()` | Resuelve sugerencias de productos y términos en una sola consulta PostgreSQL. |
| discount-service | `2026_06_29_010000_create_discount_performance_objects.php` | `discount_cleanup_expired_codes()` | Desactiva códigos vencidos y limpia descuentos aplicados expirados hace más de dos meses. |
| order-service | `2026_06_29_010000_create_order_performance_objects.php` | `order_history_view` | Prepara historial de pedidos con número, estado y estado de pago. |
| order-service | `2026_06_29_010000_create_order_performance_objects.php` | `order_sales_daily_view` | Agrupa ventas diarias por estado y estado de pago para reportes. |
| notification-service | `2026_06_29_010000_create_notification_performance_objects.php` | `notification_inbox_view` | Prepara bandeja de notificaciones vigentes con tipo asociado. |

También se agregaron índices compuestos para filtros frecuentes: productos activos, imágenes principales, reseñas aprobadas, historial de búsqueda, reglas de descuento, reportes de pedidos, solicitudes de reembolso, bandeja de notificaciones y cola de envío.

## Criterios de seguridad

- Los objetos nuevos se crean solo en PostgreSQL; en SQLite se omiten para conservar pruebas locales.
- El catálogo usa la vista solo si existe. Si la migración no ha corrido, conserva el Query Builder actual.
- Las sugerencias usan la función PostgreSQL primero y conservan el flujo anterior como respaldo.
- No se incorporaron triggers que escriben auditoría entre dominios porque los microservicios mantienen bases separadas.
- No se incorporaron triggers de historial de pedidos porque los controladores ya registran esos cambios y un trigger duplicaría eventos.
- No se cambió ningún endpoint ni formato de respuesta público.

## Operación

Después de desplegar migraciones, la limpieza de descuentos se puede ejecutar desde PostgreSQL cuando operación lo requiera:

```sql
SELECT * FROM discount_cleanup_expired_codes();
```

Las vistas de catálogo, pedidos y notificaciones no requieren ejecución manual. Quedan disponibles para consultas internas y para los controladores que las usan de forma condicional.

## Validación recomendada

- Ejecutar migraciones en entorno de pruebas antes de producción.
- Validar `/api/products` y `/api/search/suggestions` en `catalog-service`.
- Validar cupones activos y descuentos por cantidad en `discount-service`.
- Validar reportes administrativos de pedidos y solicitudes de reembolso en `order-service`.
- Validar listado y lectura de notificaciones en `notification-service`.

## Validación ejecutada

El 2026-06-29 se reconstruyeron `catalog-service`, `discount-service`, `order-service` y `notification-service`; luego se aplicaron las migraciones con `php artisan migrate --force`.

Validaciones realizadas:

- Confirmación PostgreSQL de objetos creados con `to_regclass()` y `to_regprocedure()`.
- Endpoints públicos principales con respuesta `200`: productos, sugerencias, descuentos, órdenes y notificaciones.
- Suites Laravel en contenedor:
  - `catalog-service`: 3 pruebas correctas.
  - `discount-service`: 4 pruebas correctas.
  - `order-service`: 7 pruebas correctas.
  - `notification-service`: 5 pruebas correctas.

Nota operativa: en la base local de catálogo no había productos activos al momento de validar, por eso `catalog_product_listing_view` existía pero devolvía cero filas. El endpoint conserva respaldos para no romper la experiencia durante la migración de datos.

## Documentos relacionados

- [Estructura SQL unificada de microservicios](estructura-unificada-microservicios.sql)
- [Mapa de tablas por microservicio](migracion-tablas.md)
- [Patrones de rendimiento de base de datos](../patrones/datos/patrones-diseno-rendimiento-bd-microservicios-2026-06-29.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
