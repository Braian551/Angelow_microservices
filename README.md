# Angelow Microservices

Migración del monolito PHP de **Angelow** (tienda de ropa infantil) a una arquitectura de microservicios con **Laravel** en el backend y **React** en el frontend.

## Arquitectura

```
frontend/          →  React + Vite (puerto 5173)
services/
  auth-service/    →  Laravel API (puerto 8001) — Autenticación
  catalog-service/ →  Laravel API (puerto 8002) — Productos, categorías, colecciones, favoritos, sliders, anuncios, configuración
  cart-service/    →  Laravel API (puerto 8003) — Carrito de compras
angelow/           →  Monolito PHP de referencia
```

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL (con las bases `angelow` y `angelow_auth`)

## Instalación

```bash
# Backend (cada servicio)
cd services/auth-service
composer install
cp .env.example .env
php artisan key:generate

# Repetir para catalog-service y cart-service

# Frontend
cd frontend
npm install
```

## Ejecución

```bash
# Microservicios
php artisan serve --port=8001   # auth-service
php artisan serve --port=8002   # catalog-service
php artisan serve --port=8003   # cart-service

# Frontend
cd frontend
npm run dev
```

## Tecnologías

- **Backend**: Laravel 12, PHP 8.2, Query Builder (no Eloquent)
- **Frontend**: React 19, Vite, Axios
- **Base de datos**: MySQL
- **Patrones**: Repository Pattern, Dependency Injection, Service Layer
