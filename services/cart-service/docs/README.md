# Flujo de cart-service

```mermaid
flowchart TD
  FE["Frontend"] --> API["cart-service API"]
  API --> DB[("carts + cart_items")]
  API --> CAT["catalog-service internal API"]
  CAT --> API
```

## Patrones de diseno

- `Service Layer`: `App\Services\CartService`.
- `Repository Pattern`: `App\Repositories\QueryBuilderCartRepository`.
- `API Composition`: el carrito enriquece datos con `catalog-service`.
- `Database per service`: solo persiste tablas de carrito.
