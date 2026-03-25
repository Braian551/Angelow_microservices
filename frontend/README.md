# Frontend Angelow

Microservicio frontend en Vue 3 + Vite conectado al ecosistema de microservicios.

## Variables de entorno

Configurar con base en `frontend/.env.example`.

## APIs consumidas

- Auth: `VITE_AUTH_API_URL`
- Catalog: `VITE_CATALOG_API_URL`
- Cart: `VITE_CART_API_URL`
- Orders: `VITE_ORDER_API_URL`
- Payments: `VITE_PAYMENT_API_URL`
- Discounts: `VITE_DISCOUNT_API_URL`
- Shipping: `VITE_SHIPPING_API_URL`
- Notifications: `VITE_NOTIFICATION_API_URL`
- Uploads compartidos: `VITE_UPLOADS_BASE_URL`

## Arquitectura

- Enfoque modular por dominio en `src/modules/*`.
- Componentes reutilizables por dominio en `src/modules/*/components`.
- Composable de sesion en `src/composables/useSession.js`.
- Capa HTTP en `src/services/http.js` y `src/services/*Api.js`.

Documentacion de flujo y patrones: `frontend/docs/README.md`.

## Ejecucion local

```bash
npm install
npm run dev
```
