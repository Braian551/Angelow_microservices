# Audit Service

<!-- indice:auto:start -->
## Índice rápido

- [Descripción](#descripción)
- [Endpoints](#endpoints)
- [Tablas de dominio](#tablas-de-dominio)
- [Arquitectura](#arquitectura)
- [Documentación relacionada](#documentación-relacionada)
<!-- indice:auto:end -->

## Descripción

Microservicio Laravel para trazabilidad y auditoría de usuarios, productos, categorías y órdenes del sistema Angelow.

Este servicio registra cada operación significativa (creación, modificación, eliminación) sobre las entidades principales del negocio, permitiendo consultar el historial de cambios para soporte, debugging y cumplimiento normativo.

## Endpoints

| Método | Ruta                     | Descripción                              |
|--------|--------------------------|------------------------------------------|
| GET    | `/api/health`            | Health check del servicio                |
| GET    | `/api/audits/orders`     | Trazabilidad de pedidos (últimos 200)    |
| GET    | `/api/audits/users`      | Trazabilidad de usuarios (últimos 200)   |
| GET    | `/api/audits/products`   | Trazabilidad de productos (últimos 200)  |

## Tablas de dominio

| Tabla                     | Propósito                                    |
|---------------------------|----------------------------------------------|
| `audit_categories`        | Cambios en categorías del catálogo           |
| `audit_orders`            | Operaciones sobre pedidos                    |
| `audit_users`             | Modificaciones en cuentas de usuario         |
| `productos_auditoria`     | Altas/bajas/cambios de productos             |
| `eliminaciones_auditoria` | Registro de eliminaciones de productos       |

## Arquitectura

- **Framework:** Laravel 12
- **Base de datos:** PostgreSQL (principal), con soporte multi-motor
- **Caché/Colas:** Redis
- **Autenticación:** Sanctum (preparado para SPA)
- **Patrón:** Controlador único con Query Builder para tablas append-only

Ver [docs/README.md](docs/README.md) para el flujo detallado.

## Documentación relacionada

- [Flujo de auditoría](docs/README.md)
- [Manual técnico](../../docs/operaciones/manual-tecnico.md) (raíz del repositorio)
