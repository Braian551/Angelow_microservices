# Documentación por microservicio

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Documentos relacionados](#documentos-relacionados)
- [Diagramas de clases separados](#diagramas-de-clases-separados)
- [Modelos relacionales separados](#modelos-relacionales-separados)
- [Índice](#índice)
- [Regla de ubicación](#regla-de-ubicación)
<!-- indice:auto:end -->

## Objetivo

Concentrar los accesos a la documentación específica de cada servicio sin mezclarla con la documentación compartida del repositorio.

## Documentos relacionados

- `../README.md`
- `../operaciones/manual-tecnico.md`
- `../arquitectura/diagramas-clases-microservicios-plantuml.md`
- `../arquitectura/modelos-relacionales-bases-datos-plantuml.md`
- `../arquitectura/modelo-relacional-completo-plantuml.md`

## Diagramas de clases separados

- `../arquitectura/diagramas-clases-microservicios/auth-service.md`
- `../arquitectura/diagramas-clases-microservicios/catalog-service.md`
- `../arquitectura/diagramas-clases-microservicios/cart-service.md`
- `../arquitectura/diagramas-clases-microservicios/order-service.md`
- `../arquitectura/diagramas-clases-microservicios/payment-service.md`
- `../arquitectura/diagramas-clases-microservicios/discount-service.md`
- `../arquitectura/diagramas-clases-microservicios/shipping-service.md`
- `../arquitectura/diagramas-clases-microservicios/notification-service.md`
- `../arquitectura/diagramas-clases-microservicios/audit-service.md`
- `../arquitectura/diagramas-clases-microservicios/realtime-gateway.md`

## Modelos relacionales separados

- `../arquitectura/modelo-relacional-completo-plantuml.md`
- `../arquitectura/modelos-relacionales-bases-datos/auth-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/catalog-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/cart-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/order-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/payment-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/discount-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/shipping-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/notification-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/audit-service.md`
- `../arquitectura/modelos-relacionales-bases-datos/realtime-gateway.md`
- `../arquitectura/modelos-relacionales-bases-datos/referencias-logicas.md`

## Índice

- `services/auth-service/docs/README.md`
- `services/catalog-service/docs/README.md`
- `services/cart-service/docs/README.md`
- `services/order-service/docs/README.md`
- `services/payment-service/docs/README.md`
- `services/discount-service/docs/README.md`
- `services/shipping-service/docs/README.md`
- `services/notification-service/docs/README.md`
- `services/audit-service/docs/README.md`
- `services/realtime-gateway/`

## Regla de ubicación

Si una guía, bitácora o explicación solo aplica a un servicio, debe vivir en la carpeta `docs/` de ese microservicio y no en `docs/` raíz.
