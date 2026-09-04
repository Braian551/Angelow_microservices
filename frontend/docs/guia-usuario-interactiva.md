# Guía de usuario interactiva

## Objetivo

Angelow incorpora un manual dentro de la aplicación mediante recorridos paso a paso. La ayuda identifica los controles que están visibles en cada pantalla y explica encabezados, navegación, filtros, formularios, indicadores, tablas, tarjetas, acciones y paginación sin ejecutar operaciones por cuenta del usuario.

## Cómo abrir la ayuda

- Visitante o cliente: seleccionar **Ayuda** en el pie de página.
- Inicio de sesión, registro o recuperación: seleccionar el botón flotante **Ayuda**.
- Administrador: entrar en **Configuración > General** y seleccionar **Ayuda**.
- Acceso alternativo: presionar `Alt+H` en cualquier vista, útil para volver a abrir la guía en páginas de detalle.

El centro de ayuda permite buscar una funcionalidad, iniciar la guía de la vista actual o navegar a otra pantalla para comenzar su recorrido.

## Visibilidad según la sesión

| Tipo de acceso | Guías disponibles |
|---|---|
| Visitante | Inicio, tienda, colecciones, producto actual, carrito, términos, inicio de sesión, registro y recuperación. |
| Cliente autenticado | Tienda pública, checkout y todas las secciones de **Mi cuenta**. Las guías de acceso y administración no aparecen. |
| Administrador autenticado | Dashboard y todas las vistas administrativas. Las guías de cliente no aparecen. |

Un visitante no puede ver ni abrir recorridos del dashboard de cliente o del panel administrativo. Las guías que requieren un registro concreto, como el detalle de un producto o una orden, solo aparecen mientras esa vista está abierta.

## Catálogo de recorridos

### Primeros pasos y tienda

- Inicio.
- Iniciar sesión.
- Crear una cuenta.
- Recuperar la cuenta.
- Recuperar acceso administrativo, únicamente dentro de esa vista.
- Términos y condiciones.
- Explorar la tienda.
- Explorar colecciones.
- Detalle del producto abierto.
- Carrito de compras.

### Cuenta del cliente

- Resumen de mi cuenta.
- Mis pedidos.
- Detalle del pedido abierto.
- Mis notificaciones.
- Mis direcciones.
- Mis favoritos.
- Configuración de mi cuenta.

### Finalizar compra

- Dirección y método de envío.
- Pago y comprobante.
- Confirmación de compra.

### Administración

- Dashboard, búsqueda global y acciones rápidas.
- Productos, creación y edición de producto.
- Categorías, colecciones, tallas e inventario.
- Órdenes, detalle de orden, clientes, reseñas y preguntas.
- Pagos, reembolsos y facturas.
- Reglas de envío, métodos de envío, repartidores y entregas asignadas.
- Descuentos por cantidad, códigos y campañas específicas.
- Anuncios y sliders.
- Informes generales, de ventas, productos y clientes.
- Configuración general y administradores.

## Comportamiento del recorrido

- Los botones **Anterior**, **Siguiente**, **Finalizar** y **Salir** están en español.
- Se muestra progreso y número de paso.
- Se puede avanzar con teclado y cerrar con `Esc`.
- La pantalla se desplaza hasta la funcionalidad explicada.
- Las interacciones permanecen disponibles para que el usuario pueda reconocer cada control.
- Las secciones que todavía están cargando o no están visibles se omiten; al abrir de nuevo la ayuda se inspecciona el estado actual de la vista.

## Mantenimiento

La fuente canónica del catálogo es `frontend/src/features/user-guide/guideCatalog.js`. Debe existir una entrada por cada ruta Vue con nombre. El motor común reside en `frontend/src/features/user-guide/userGuide.js` y genera los pasos a partir de la interfaz realmente renderizada. Los accesos visuales se mantienen en `SiteFooter.vue`, `App.vue` y `AdminSettingsPage.vue`.

Al crear una vista nueva:

1. Registrar su guía, audiencia, grupo, título y descripción en el catálogo.
2. Usar HTML semántico y componentes compartidos para que el motor reconozca sus funciones.
3. Si una zona necesita una explicación específica, agregar `data-guide-title` y `data-guide-intro` al elemento estable correspondiente.
4. Confirmar que la ruta no se filtra para una audiencia sin permiso.
5. Ejecutar build, comprobación de cobertura de rutas y revisión responsive.
