# Catalog Service

Microservicio Laravel para catalogo, contenido comercial y descubrimiento.

## Endpoints publicos

- `GET /api/health`
- `GET /api/home`
- `GET /api/settings`
- `GET /api/sliders`
- `GET /api/products`
- `GET /api/products/{slug}`
- `GET /api/categories`
- `GET /api/collections`
- `GET /api/wishlist`
- `POST /api/wishlist/toggle`

## Endpoints internos

- `GET /api/internal/products/{id}`
- `GET /api/internal/variants/{id}`

Estos endpoints son usados por `cart-service`.

## Tablas de dominio

- Catalogo: `products`, `categories`, `collections`, `product_collections`
- Variantes: `product_color_variants`, `product_size_variants`, `colors`, `sizes`
- Medios: `product_images`, `variant_images`, `sliders`, `announcements`
- Social: `product_reviews`, `review_votes`, `product_questions`, `question_answers`, `wishlist`
- Descubrimiento: `search_history`, `popular_searches`, `site_settings`, `stock_history`
