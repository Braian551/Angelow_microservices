# Angelow Microservices

Migracion del monolito `angelow/` hacia microservicios Laravel con PostgreSQL, Redis, workers y frontend separado.

## Servicios y puertos

| Servicio | Puerto API | Base de datos |
|---|---:|---|
| `auth-servi![1776289969924](image/README/1776289969924.png)ce` | 8001 | `angelow_auth` |
| `catalog-service` | 8002 | `angelow_catalog` |
| `cart-service` | 8003 | `angelow_cart` |
| `order-service` | 8004 | `angelow_orders` |
| `payment-service` | 8005 | `angelow_payments` |
| `discount-service` | 8006 | `angelow_discounts` |
| `shipping-service` | 8007 | `angelow_shipping` |
| `notification-service` | 8008 | `angelow_notifications` |
| `audit-service` | 8009 | `angelow_audit` |
| `frontend` | 5173 | n/a |

## PostgreSQL en pgAdmin (evitar confusion)

Cada microservicio usa su propia base PostgreSQL en un puerto distinto.
Si en pgAdmin te conectas solo a `localhost:5432`, veras la instancia local general, no las bases de microservicios.

| Base de datos | Host | Puerto | Usuario | Contrasena |
|---|---|---:|---|---|
| `angelow_auth` | `localhost` | 5433 | `postgres` | `root` |
| `angelow_catalog` | `localhost` | 5434 | `postgres` | `root` |
| `angelow_cart` | `localhost` | 5435 | `postgres` | `root` |
| `angelow_orders` | `localhost` | 5436 | `postgres` | `root` |
| `angelow_payments` | `localhost` | 5437 | `postgres` | `root` |
| `angelow_discounts` | `localhost` | 5438 | `postgres` | `root` |
| `angelow_shipping` | `localhost` | 5439 | `postgres` | `root` |
| `angelow_notifications` | `localhost` | 5440 | `postgres` | `root` |
| `angelow_audit` | `localhost` | 5441 | `postgres` | `root` |

Validacion rapida en pgAdmin (sobre cada base):

```sql
SELECT count(*) AS total_tablas
FROM information_schema.tables
WHERE table_schema = 'public';
```

## Levantar todo con Docker

```bash
docker compose up -d --build
docker compose ps
```
![1776440031278](image/README/1776440031278.png)
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
