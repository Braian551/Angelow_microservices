# Angelow Microservices

Migracion del monolito `angelow/` hacia microservicios Laravel con PostgreSQL, Redis, workers y frontend separado.

## Servicios y puertos

| Servicio | Puerto API | Base de datos |
|---|---:|---|
| `auth-service` | 8001 | `angelow_auth` |
| `catalog-service` | 8002 | `angelow_catalog` |
| `cart-service` | 8003 | `angelow_cart` |
| `order-service` | 8004 | `angelow_orders` |
| `payment-service` | 8005 | `angelow_payments` |
| `discount-service` | 8006 | `angelow_discounts` |
| `shipping-service` | 8007 | `angelow_shipping` |
| `notification-service` | 8008 | `angelow_notifications` |
| `audit-service` | 8009 | `angelow_audit` |
| `frontend` | 5173 | n/a |

## Levantar todo con Docker

```bash
docker compose up -d --build
docker compose ps
```

## Ejecutar migraciones

```bash
docker compose exec -T auth-service php artisan migrate --force
docker compose exec -T catalog-service php artisan migrate --force
docker compose exec -T cart-service php artisan migrate --force
docker compose exec -T order-service php artisan migrate --force
docker compose exec -T payment-service php artisan migrate --force
docker compose exec -T discount-service php artisan migrate --force
docker compose exec -T shipping-service php artisan migrate --force
docker compose exec -T notification-service php artisan migrate --force
docker compose exec -T audit-service php artisan migrate --force
```

## Importar datos desde `basededatos.sql`

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\importar-datos-microservicios.ps1
```

## Ejecutar pruebas

```bash
docker compose exec -T auth-service php artisan test
docker compose exec -T catalog-service php artisan test
docker compose exec -T cart-service php artisan test
docker compose exec -T order-service php artisan test
docker compose exec -T payment-service php artisan test
docker compose exec -T discount-service php artisan test
docker compose exec -T shipping-service php artisan test
docker compose exec -T notification-service php artisan test
docker compose exec -T audit-service php artisan test
```

## Documentacion

- [Arquitectura general](docs/README.md)
- [Mapa de tablas por microservicio](docs/migracion-tablas.md)
- [Importacion de datos](docs/importacion-datos.md)
