# Patron aplicado: detalle de producto y orden con render estable

Fecha: 2026-06-20

## Contexto

En la ficha publica de producto, las imagenes de la pestana de descripcion podian ocupar todo el ancho disponible cuando habia una sola imagen, generando una vista demasiado grande en desktop y dificil de revisar en pantallas pequenas.

En el panel administrativo, al abrir el detalle completo de una orden desde pagos, algunas cargas asincronas del detalle podian resolver despues de una navegacion o desmontaje de componente, provocando errores de render de Vue durante el cambio de ruta.

## Patron aplicado

- **Strategy visual responsive**: `frontend/src/modules/catalog/views/ProductDetailView.css` limita las imagenes de descripcion con columnas maximas, centrado, proporcion estable y altura maxima relativa al viewport. La imagen conserva inspeccion por zoom, pero deja de crecer hasta ocupar el documento completo.
- **Observer con descarte de cargas obsoletas**: `frontend/src/modules/admin/composables/useAdminOrderDetail.js` observa cambios de `route.params.id` y `route.query.vista`, y usa una secuencia interna para descartar respuestas anteriores cuando la ruta ya cambio o el componente fue desmontado.

## Reglas de continuidad

- Las imagenes de descripcion del producto deben usar `object-fit: contain` y limites de alto para evitar recortes o layouts gigantes.
- Las cargas del detalle de orden que combinan orden, productos, direcciones y pagos deben validar que siguen perteneciendo a la navegacion vigente antes de mutar estado reactivo.
- El enlace desde pagos o reembolsos a `/admin/ordenes/:id` debe mantener el detalle completo como pantalla de destino, no como modal embebido.

## Validacion esperada

- `npm run build` del frontend debe compilar.
- `/producto/:slug` debe mostrar la pestana de descripcion sin imagenes desbordadas.
- `/admin/pagos` hacia `/admin/ordenes/:id` no debe dejar errores `parentNode` en consola al navegar.
