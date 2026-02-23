# Angelow Microservices

Arquitectura de microservicios para la plataforma e-commerce Angelow.
Migración del monolito PHP (`angelow/`) a servicios Laravel independientes.

## Servicios

| Servicio | Puerto | Descripción | Base URL |
|----------|--------|-------------|----------|
| auth-service | 8001 | Autenticación (registro, login, tokens) | `/api/auth` |
| catalog-service | 8002 | Catálogo de productos, categorías, colecciones, favoritos | `/api/products`, `/api/categories`, `/api/collections`, `/api/wishlist` |
| cart-service | 8003 | Carrito de compras (agregar, actualizar, eliminar) | `/api/cart` |
| frontend | 5173 | Aplicación React + Vite | — |

## Arquitectura

Cada microservicio sigue una arquitectura por capas:

```
app/
├── Http/Controllers/    # Controladores API (validación + respuesta)
├── Services/            # Lógica de negocio
├── Repositories/
│   ├── Contracts/       # Interfaces (contratos)
│   └── QueryBuilder*.php # Implementaciones con DB::table()
├── Exceptions/          # Excepciones personalizadas
├── Providers/           # Inyección de dependencias
└── DTOs/                # Data Transfer Objects (auth-service)
```

### Patrones de Diseño Utilizados

- **Repository Pattern**: Separa la lógica de acceso a datos de la lógica de negocio
- **Dependency Injection**: Los servicios dependen de interfaces, no de implementaciones
- **Service Layer**: Toda la lógica de negocio está encapsulada en servicios
- **Query Builder**: Se usa `DB::table()` en lugar de Eloquent ORM para mayor control

## Inicio Rápido

```bash
# Levantar todos los servicios
docker compose up --build -d

# O individualmente con Laragon/artisan:
cd services/auth-service    && php artisan serve --port=8001
cd services/catalog-service && php artisan serve --port=8002
cd services/cart-service    && php artisan serve --port=8003
cd frontend                 && npm run dev
```

## Base de Datos

Todos los microservicios se conectan a la base de datos `angelow` (MySQL).
El servicio de autenticación usa su propia base `angelow_auth`.

| Servicio | Base de Datos | Tablas Principales |
|----------|--------------|-------------------|
| auth-service | angelow_auth | users, personal_access_tokens |
| catalog-service | angelow | products, categories, collections, product_images, product_color_variants, product_size_variants, variant_images, product_reviews, product_questions, question_answers, wishlist |
| cart-service | angelow | carts, cart_items, product_size_variants, product_color_variants |

## API Endpoints

### auth-service (`:8001`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/register` | Registrar nuevo usuario |
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/logout` | Cerrar sesión |

### catalog-service (`:8002`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/products` | Listar productos con filtros y paginación |
| GET | `/api/products/{slug}` | Detalle de producto (variantes, imágenes, reseñas, Q&A) |
| GET | `/api/categories` | Listar categorías activas |
| GET | `/api/collections` | Listar colecciones activas |
| GET | `/api/wishlist?user_id=` | Obtener favoritos del usuario |
| POST | `/api/wishlist/toggle` | Alternar producto en favoritos |

### cart-service (`:8003`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/cart?user_id=` | Obtener carrito con detalles y totales |
| POST | `/api/cart/add` | Agregar producto al carrito |
| PUT | `/api/cart/{itemId}` | Actualizar cantidad de un ítem |
| DELETE | `/api/cart/{itemId}` | Eliminar ítem del carrito |
| GET | `/api/cart/items?user_id=` | Obtener IDs de productos en el carrito |

## Estructura del Proyecto

```
Angelow_microservices/
├── angelow/                  # Monolito PHP original (referencia)
├── services/
│   ├── auth-service/         # Laravel — Autenticación
│   ├── catalog-service/      # Laravel — Catálogo y Favoritos
│   └── cart-service/         # Laravel — Carrito de Compras
├── frontend/                 # React + Vite
├── shared/assets/            # Assets compartidos
├── docs/                     # Documentación
└── docker-compose.yml
```
