# Flujo de catalog-service

```mermaid
flowchart TD
  FE["Frontend"] --> HOME["GET /api/home"]
  FE --> PRODUCTS["GET /api/products"]
  FE --> DETAIL["GET /api/products/{slug}"]
  FE --> WISHLIST["POST /api/wishlist/toggle"]
  CART["cart-service"] --> INTERNAL["GET /api/internal/*"]
  PRODUCTS --> DB[("catalog tables")]
  DETAIL --> DB
  WISHLIST --> DB
  INTERNAL --> DB
```

## Patrones de diseno

- `Repository + Service`: controladores delgados y reglas concentradas.
- `API composition`: `/api/home` agrega contenido transversal.
- `Internal API`: contratos ligeros para comunicacion entre servicios.
