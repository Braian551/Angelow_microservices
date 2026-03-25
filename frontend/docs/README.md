# Flujo de frontend

```mermaid
flowchart LR
  HOME["HomePage"] --> STORE["StorePage"]
  STORE --> DETAIL["ProductDetailPage"]
  DETAIL --> CART["CartPage"]
  CART --> SHIP["ShippingPage"]
  SHIP --> PAY["PaymentPage"]
  PAY --> CONF["ConfirmationPage"]
  CONF --> ORDERS["OrdersPage"]
```

## Patrones aplicados

- `Modular by feature`: cada dominio vive en `src/modules/<dominio>`.
- `Service layer`: consumo API centralizado en `src/services/*Api.js`.
- `Composables`: sesion compartida en `src/composables/useSession.js`.
- `Presentational components`: componentes reutilizables en `modules/*/components`.
