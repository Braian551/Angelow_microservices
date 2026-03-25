# Arquitectura General

## Objetivo

Separar el monolito Angelow en microservicios de dominio, cada uno con su base PostgreSQL, comunicacion por API y soporte asincrono con Redis + workers.

## Flujo de alto nivel

```mermaid
flowchart LR
  FE[Frontend] --> AUTH[auth-service]
  FE --> CAT[catalog-service]
  FE --> CART[cart-service]
  FE --> ORD[order-service]
  FE --> PAY[payment-service]

  ORD --> SHIP[shipping-service]
  ORD --> DISC[discount-service]
  ORD --> NOTI[notification-service]
  ORD --> AUD[audit-service]

  NOTI --> REDIS[(Redis)]
  ORD --> REDIS
```

## Patrones aplicados

- `Database per service`
- `Service Layer`
- `Repository Pattern` (servicios existentes)
- `Event + Job` para notificaciones y colas
- `Health Check` estandar en `GET /api/health`

## Infraestructura

- Docker Compose para todos los servicios
- Redis para cache y cola
- Workers dedicados: `order-worker` y `notification-worker`
- Carpeta compartida `uploads/` montada en microservicios y frontend
- Importacion de datos automatizada desde `basededatos.sql` con `scripts/importar-datos-microservicios.ps1`
