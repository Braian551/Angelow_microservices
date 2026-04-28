# Flujo de order-service

```mermaid
flowchart TD
  A[Cliente o frontend] --> B[API orders]
  B --> C[Validación]
  C --> D[(orders)]
  C --> E[(order_items)]
  B --> F[(order_status_history)]
  F --> G[Auditoría funcional]
```

## Diseño

- Arquitectura por capas Laravel.
- Persistencia en PostgreSQL con migraciones versionadas.
- Preparado para consumir eventos de pago/envío y publicar cambios de estado.
