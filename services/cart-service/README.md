# Cart Service

Microservicio Laravel para gestion del carrito de compras.

## Responsabilidad

- Persistir carritos y items (`carts`, `cart_items`).
- Validar stock por integracion con `catalog-service`.
- Exponer endpoints para frontend y otros servicios.

## Endpoints

- `GET /api/health`
- `GET /api/cart`
- `POST /api/cart/add`
- `PUT /api/cart/{itemId}`
- `DELETE /api/cart/{itemId}`
- `GET /api/cart/items`

## Endpoints internos consumidos

- `GET /api/internal/products/{id}` en `catalog-service`
- `GET /api/internal/variants/{id}` en `catalog-service`

Variable requerida: `CATALOG_API_URL`.

## Tablas de dominio

- `carts`
- `cart_items`
