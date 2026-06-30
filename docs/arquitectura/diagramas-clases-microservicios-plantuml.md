# Diagramas de clases por microservicio en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Estándar aplicado](#estándar-aplicado)
- [Diagramas separados](#diagramas-separados)
- [Hallazgos de cobertura](#hallazgos-de-cobertura)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Análisis realizado el 2026-06-28 sobre `services/*/app/**/*.php`, `services/*/routes/api.php`, `services/*/database/migrations/*.php`, `services/realtime-gateway/server.js` y `shared/src/**/*.php`.

Este índice reemplaza el documento monolítico anterior. Los diagramas ahora viven en archivos independientes para que cada microservicio pueda renderizarse, revisarse y versionarse sin mezclar responsabilidades. El documento queda limitado a microservicios y dependencias runtime de backend.

## Estándar aplicado

- Cada archivo incluye `Controller`, `Service`, `Repository`, `Interface`, `Model`, `Request`, `DTO`, `Job`, `Event`, `Command`, `Middleware`, `Provider`, `Support`, `Tabla`, `Storage`, `External` o `Runtime` según aplique.
- Las clases de dominio, modelos y tablas incluyen atributos principales con tipo aproximado, tomados de modelos, migraciones y SQL del servicio.
- Los métodos públicos expuestos por rutas o por contratos de servicio/repositorio se listan explícitamente.
- Las tablas usadas con Query Builder se representan con `<<Tabla>>` cuando no existe modelo Eloquent dedicado.
- Las relaciones entre entidades usan multiplicidad (`1`, `0..1`, `0..*`, `1..*`) y distinguen asociación, agregación (`o--`) y composición (`*--`) cuando el ciclo de vida lo permite.
- Se omiten `Controller` base, factories, seeders, tests y tablas técnicas de Laravel (`cache`, `jobs`, `failed_jobs`, `job_batches`) porque no forman parte de la arquitectura de clases runtime del dominio.
- El middleware de `shared/src` fue revisado como dependencia transversal, pero no tiene diagrama propio porque no es microservicio.

## Diagramas separados

- [auth-service](diagramas-clases-microservicios/auth-service.md)
- [catalog-service](diagramas-clases-microservicios/catalog-service.md)
- [cart-service](diagramas-clases-microservicios/cart-service.md)
- [order-service](diagramas-clases-microservicios/order-service.md)
- [payment-service](diagramas-clases-microservicios/payment-service.md)
- [discount-service](diagramas-clases-microservicios/discount-service.md)
- [shipping-service](diagramas-clases-microservicios/shipping-service.md)
- [notification-service](diagramas-clases-microservicios/notification-service.md)
- [audit-service](diagramas-clases-microservicios/audit-service.md)
- [realtime-gateway](diagramas-clases-microservicios/realtime-gateway.md)

## Hallazgos de cobertura

- `auth-service/routes/api.php` contiene tres bloques repetidos para `auth/registration-verification`; el diagrama documenta una sola clase `RegistrationVerificationController` porque las rutas duplicadas apuntan al mismo controlador y métodos.
- En la revisión se encontró que los diagramas anteriores listaban principalmente métodos y omitían atributos de entidades; se actualizaron modelos y tablas principales para cumplir el estándar UML de clase con atributos, métodos y relaciones.
- `catalog-service`, `order-service`, `payment-service`, `discount-service`, `notification-service` y `audit-service` usan Query Builder de forma relevante; por eso sus tablas principales aparecen como clases `<<Tabla>>`.
- `realtime-gateway` no tiene clases PHP; se documenta como módulo Node con funciones runtime, servidor HTTP/WebSocket y suscripción Redis.

## Documentos relacionados

- [Arquitectura web en PlantUML](arquitectura-web-plantuml.md)
- [Modelos relacionales de bases de datos en PlantUML](modelos-relacionales-bases-datos-plantuml.md)
- [Mapa de navegación del sistema en PlantUML](mapas-navegacion-sistema-plantuml.md)
- [Documentación por microservicio](../microservicios/README.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
