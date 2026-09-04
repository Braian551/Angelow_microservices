# Flujo de shipping-service

<!-- indice:auto:start -->
## Índice rápido

- [Patrones usados](#patrones-usados)
<!-- indice:auto:end -->

```mermaid
flowchart TD
  A[Checkout] --> B[Consultar métodos]
  B --> C[(shipping_methods)]
  A --> D[Calcular envío]
  D --> E[(shipping_price_rules)]
  A --> F[Gestionar dirección]
  F --> G[(user_addresses)]
  H[Pago verificado] --> I[Publicar entrega idempotente]
  I --> J[(delivery_assignments)]
  K[Repartidor aprobado] --> L[Configuración Mapbox autenticada]
  J --> M[Aceptar entrega]
  M --> N[Notificación y correo con código]
```

## Patrones usados

- API REST de consulta y cálculo.
- Reglas desacopladas por tabla para costos variables.
- Persistencia de dirección separada del pedido.
- La publicación interna conserva el nombre y tiempo estimado del método; puede usar esa instantánea cuando el identificador distribuido no esté disponible.
- `GET /api/courier/map-config` entrega la configuración pública de Mapbox sin caché y exige una sesión de repartidor aprobada y activa.
- Al aceptar una entrega se solicita a `notification-service` el aviso en bandeja y el correo del código mediante su plantilla global. Una falla del canal no revierte la asignación y queda registrada para operación.
