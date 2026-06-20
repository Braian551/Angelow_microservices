# Cart Service

<!-- indice:auto:start -->
## Índice rápido

- [Responsabilidad](#responsabilidad)
- [Arquitectura](#arquitectura)
- [Endpoints](#endpoints)
- [Endpoints internos consumidos](#endpoints-internos-consumidos)
- [Tablas de dominio](#tablas-de-dominio)
- [Documentación técnica](#documentacion-tecnica)
<!-- indice:auto:end -->

Microservicio Laravel para la gestión del carrito de compras de Angelow.

## Responsabilidad

- Persistir carritos e ítems (`carts`, `cart_items`).
- Validar stock en tiempo real contra Redis y catalog-service.
- Calcular subtotales y enriquecer ítems con datos de productos.
- Disparar recordatorios de carritos abandonados vía notification-service.
- Exponer endpoints REST para frontend y otros servicios.

## Arquitectura

```
Frontend SPA → cart-service API → PostgreSQL (carts, cart_items)
                               → catalog-service (productos, variantes)
                               → Redis (stock en tiempo real)
                               → notification-service (recordatorios)
```

El carrito usa **API Composition**: los datos de productos y variantes se obtienen bajo demanda desde catalog-service. El stock disponible en tiempo real se consulta desde Redis para evitar sobreventas.

## Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/health` | Salud del servicio |
| `GET` | `/api/cart?user_id=&session_id=` | Obtener carrito con detalle y totales |
| `POST` | `/api/cart/add` | Agregar variante de producto al carrito |
| `PUT` | `/api/cart/{itemId}` | Actualizar cantidad de un ítem |
| `DELETE` | `/api/cart/{itemId}` | Eliminar un ítem del carrito |
| `GET` | `/api/cart/items?user_id=&session_id=` | IDs de productos en el carrito |
| `POST` | `/api/admin/cart/abandoned/reminders/dispatch` | Disparar recordatorios (interno) |

## Endpoints internos consumidos

- `GET /api/internal/products/{id}` en catalog-service
- `GET /api/internal/variants/{id}` en catalog-service

Variables requeridas: `CATALOG_API_URL`, `NOTIFICATION_SERVICE_URL`.

## Tablas de dominio

- `carts` — Carritos asociados a usuario o sesión anónima
- `cart_items` — Ítems individuales con producto, variantes y cantidad

## Documentación técnica

Ver [docs/README.md](docs/README.md) para diagrama de flujo, patrones de diseño y estructura de archivos.
