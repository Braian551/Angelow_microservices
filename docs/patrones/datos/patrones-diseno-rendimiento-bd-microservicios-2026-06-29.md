# Patrones de diseño - Rendimiento de base de datos en microservicios

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Adapter](#adapter)
- [Facade](#facade)
- [Strategy](#strategy)
- [Archivos modificados](#archivos-modificados)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Contexto

Implementación realizada el 2026-06-29 para incorporar objetos PostgreSQL de lectura, mantenimiento e indexación inspirados en el SQL completo del proyecto. El objetivo es acelerar consultas frecuentes sin cambiar endpoints, payloads ni responsabilidades entre microservicios.

## Adapter

**Archivos:**

- `services/catalog-service/database/migrations/2026_06_29_010000_create_catalog_performance_objects.php`
- `services/discount-service/database/migrations/2026_06_29_010000_create_discount_performance_objects.php`
- `services/order-service/database/migrations/2026_06_29_010000_create_order_performance_objects.php`
- `services/notification-service/database/migrations/2026_06_29_010000_create_notification_performance_objects.php`
- `docs/datos/estructura-unificada-microservicios.sql`

**Problema resuelto:** Los procedimientos y triggers del SQL completo no se podían copiar de forma literal porque el sistema actual separa bases por microservicio y usa PostgreSQL. Copiarlos sin adaptación podía duplicar escrituras o crear acoplamientos entre dominios.

**Aplicación:** Se adaptaron las ideas útiles a vistas, funciones e índices por dominio. Catálogo recibe vista y función de búsqueda; descuentos recibe función de limpieza; pedidos y notificaciones reciben vistas de lectura e índices para reportes y bandejas.

## Facade

**Archivos:**

- `services/catalog-service/app/Repositories/QueryBuilderProductRepository.php`
- `services/catalog-service/app/Http/Controllers/SearchController.php`

**Problema resuelto:** La aplicación no debía exponer a los endpoints si una consulta viene de Query Builder tradicional, vista PostgreSQL, función PostgreSQL o respaldo histórico.

**Aplicación:** El repositorio de productos y el controlador de búsqueda conservan una interfaz estable hacia el resto del sistema. Internamente activan la ruta optimizada solo si los objetos de base de datos existen y mantienen el flujo anterior como respaldo.

## Strategy

**Archivos:**

- `services/catalog-service/app/Repositories/QueryBuilderProductRepository.php`
- `services/catalog-service/app/Http/Controllers/SearchController.php`

**Problema resuelto:** Durante despliegues progresivos puede haber entornos con migraciones aplicadas y otros sin ellas. La lectura de productos y búsqueda debía seguir funcionando en ambos casos.

**Aplicación:** Se selecciona dinámicamente la estrategia de consulta: vista PostgreSQL cuando existe, función PostgreSQL para sugerencias cuando está disponible, Query Builder como estrategia base y respaldo histórico cuando la base distribuida aún no tiene datos suficientes.

## Archivos modificados

| Archivo | Cambio |
| --- | --- |
| `services/catalog-service/app/Repositories/QueryBuilderProductRepository.php` | Selección condicional de `catalog_product_listing_view` con respaldo Query Builder. |
| `services/catalog-service/app/Http/Controllers/SearchController.php` | Uso condicional de `catalog_search_products_and_terms()` con respaldo de procedimiento externo y consultas equivalentes. |
| `services/catalog-service/database/migrations/2026_06_29_010000_create_catalog_performance_objects.php` | Vista, función e índices de catálogo. |
| `services/discount-service/database/migrations/2026_06_29_010000_create_discount_performance_objects.php` | Función de limpieza e índices de descuentos. |
| `services/order-service/database/migrations/2026_06_29_010000_create_order_performance_objects.php` | Vistas e índices para historial y reportes de pedidos. |
| `services/notification-service/database/migrations/2026_06_29_010000_create_notification_performance_objects.php` | Vista e índices para bandeja y cola de notificaciones. |
| `docs/datos/rendimiento-bd-microservicios.md` | Guía operativa de los objetos de rendimiento. |
| `docs/datos/estructura-unificada-microservicios.sql` | Referencia SQL unificada con los objetos adaptados. |

## Documentos relacionados

- [Rendimiento de base de datos en microservicios](../../datos/rendimiento-bd-microservicios.md)
- [Estructura SQL unificada de microservicios](../../datos/estructura-unificada-microservicios.sql)
- [Patrones de diseño aplicados](../README.md)
