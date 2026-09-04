# Casos de prueba de Angelow

## Datos de versión

- Fecha de la versión: 2026-08-19
- Fuente funcional: matriz vigente de 422 requerimientos.
- Cobertura documental: 17 módulos, 422 casos funcionales y 68 casos transversales.
- Total: 490 casos de prueba.

## Criterio de cobertura

Cada requerimiento de la matriz origina un caso con identificador estable dentro de su módulo. Los cuatro casos adicionales por módulo verifican error recuperable, prevención de doble envío, estados de carga/vacío y adaptación a distintos tamaños de pantalla. Los flujos destructivos incluyen cancelación y confirmación de alertas; los formularios comprueban validación en tiempo real, bloqueo mientras se procesa la acción y mensaje final.

## Formato fácil de ejecutar

Cada tabla usa las columnas **No**, **Nombre/Identificador**, **Descripción**, **Precondiciones**, **Entradas**, **Pasos**, **Resultados esperados**, **Poscondición**, **Estado**, **Observaciones** y **Prioridad**.

1. Lea solo **Precondiciones** y prepare lo indicado en **Entradas**.
2. Realice los **Pasos** en el orden escrito, una vez por caso.
3. Compare la pantalla o el dato guardado con **Resultados esperados**.
4. Marque **Estado** como `Aprobado`, `Fallido`, `Bloqueado` o `No aplica`.
5. Deje **Observaciones** vacía antes de ejecutar el caso. Úsela solo después de la prueba para anotar una diferencia real, sin incluir contraseñas, tokens ni datos personales.

## Datos reales verificados

Estos datos se consultaron directamente en las bases activas el 2026-07-28. La columna **Entradas** de cada caso usa solamente los valores necesarios para ejecutarlo.

| Dato | Valor real confirmado | Uso principal |
|---|---|---|
| Cliente | ID `6861e06ddcf49`; nombre `Braian`; correo `braianoquendurango@gmail.com`; teléfono `3013636902`; rol cliente; no bloqueado | Cuenta, pedidos, carrito y notificaciones |
| Administrador | ID `6860007924a6a`; nombre `Braian`; correo `braianoquen@gmail.com`; rol administrador; no bloqueado | Panel administrativo |
| Producto | ID `71`; nombre `Ropa deportiva`; slug `ropa-deportiva`; precio `35000`; activo | Catálogo, carrito e inventario |
| Variante | Color `Negro`, ID de color `35`; talla `XS`, ID de talla `47`; stock `17` | Selección de variante, carrito y stock |
| Carrito | ID `34`; cliente `6861e06ddcf49`; actualmente vacío | Estado vacío y preparación de productos |
| Pedido entregado | ID `19`; número `ORD202511263E6FF6`; total `88000`; pago realizado | Historial, detalle, factura y postventa |
| Pedido enviado | ID `11`; número `ORD2025112335EAC5`; total `308000`; pago realizado | Seguimiento de pedido |
| Pedido pendiente | ID `14`; número `ORD20251125A8B68D`; total `53000` | Confirmación y cambios de estado |
| Pedido cancelado | ID `13`; número `ORD2025112501BD10`; total `53000`; pago reembolsado | Cancelación y postventa |
| Pedido reembolsado | ID `15`; número `ORD20251125FB1BE6`; total `98000` | Reembolsos |
| Pago | Transacción ID `44`; pedido ID `19`; referencia `21212121`; valor `88000`; comprobante disponible; estado pendiente | Verificación de pagos |
| Cupón | Código `E20FA9C5`; descuento porcentual del `20 %`; activo | Aplicación de descuentos |
| Descuento por cantidad | Regla ID `1`; de `30` a `50` unidades; descuento del `10 %` | Compra por volumen |
| Notificación | ID `70`; pedido `ORD202511263E6FF6`; estado no leído | Bandeja y contador de notificaciones |
| Auditoría | ID `177`; pedido ID `19`; acción `UPDATE`; usuario `6861e06ddcf49` | Informes y trazabilidad |
| Contenido | Slider ID `1`, anuncio ID `1`, reseña ID `1` y pregunta ID `2` | Inicio, contenido y participación |

Por decisión de preparación manual, todos los casos que requieren una contraseña usan temporalmente `Braian8052@`. Este valor se debe reemplazar cuando cambien las credenciales del ambiente QA.

Actualmente no hay direcciones, métodos de envío, perfiles de repartidor ni entregas asignadas en la base de envíos. En esos módulos, la entrada dice expresamente **crear para la prueba** o **preparar en QA**; no se presentan como datos existentes.

## Cómo leer la columna Entradas

- Un valor como `Producto ID=71, Color="Negro", Talla="XS", Cantidad=1` se puede copiar directamente al flujo.
- Un valor entre ángulos, como `<código recibido>` o `<fecha>`, se obtiene o reemplaza durante la ejecución.
- Cuando el caso crea información, se usan nombres reconocibles como `Usuario QA`, `Casa QA` o `Producto QA` para poder eliminarlos después de la prueba.

## Índice por módulo

| Módulo | Prefijo | Casos funcionales | Casos transversales | Total |
|---|---|---:|---:|---:|
| [Ayuda y Manual de Usuario](ayuda-manual-usuario.md) | HELP | 4 | 4 | 8 |
| [Carrito de Compras](carrito-compras.md) | CART | 12 | 4 | 16 |
| [Catálogo y Productos](catalogo-productos.md) | CAT | 80 | 4 | 84 |
| [Contenido y Configuración del Sitio](contenido-configuracion-sitio.md) | SITE | 26 | 4 | 30 |
| [Direcciones y Envíos](direcciones-envios.md) | SHIP | 33 | 4 | 37 |
| [Gestión de Usuarios y Acceso](usuarios-acceso.md) | AUTH | 63 | 4 | 67 |
| [Informes y Auditoría](informes-auditoria.md) | REPORT | 12 | 4 | 16 |
| [Inventario](inventario.md) | INV | 19 | 4 | 23 |
| [Navegación Pública](navegacion-publica.md) | PUBLIC | 8 | 4 | 12 |
| [Notificaciones y Postventa](notificaciones-postventa.md) | POST | 18 | 4 | 22 |
| [Operación e Integración del Sistema](operacion-integracion-sistema.md) | OPS | 5 | 4 | 9 |
| [Órdenes](ordenes.md) | ORDER | 31 | 4 | 35 |
| [Pagos y Facturación](pagos-facturacion.md) | PAY | 30 | 4 | 34 |
| [Panel Administrativo](panel-administrativo.md) | ADMIN | 15 | 4 | 19 |
| [Promociones y Descuentos](promociones-descuentos.md) | PROMO | 24 | 4 | 28 |
| [Repartidores y Entregas](repartidores-entregas.md) | COURIER | 16 | 4 | 20 |
| [Reseñas, Preguntas y Favoritos](resenas-preguntas-favoritos.md) | SOCIAL | 26 | 4 | 30 |

## Ejecución y mantenimiento

1. Confirmar servicios y bases del ambiente QA.
2. Usar los datos reales indicados o crear únicamente el dato QA que el caso solicite.
3. Ejecutar primero prioridades altas y después medias y bajas.
4. Cambiar `Estado` a `Aprobado`, `Fallido`, `Bloqueado` o `No aplica`. Mantener `Observaciones` vacía si el caso aprueba; si falla o se bloquea, anotar allí una explicación breve y adjuntar la evidencia en la herramienta de gestión elegida. No almacenar secretos en este directorio.
5. Cuando cambie un requisito, actualizar su caso homólogo; cuando aparezca un flujo nuevo, agregarlo al final del archivo del módulo con el siguiente correlativo.

## Fuentes revisadas

- [Matriz de requerimientos funcionales](../../referencias/matriz-requerimientos-funcionales-actualizada.md)
- [Casos de uso](../../referencias/casos-uso-angelow.md)
- [Historias de usuario](../../referencias/historias-usuario-angelow.md)
- [Requisitos no funcionales](../../referencias/requisitos-no-funcionales-angelow.md)
- Rutas Vue, páginas, composables de alertas/snackbars, endpoints Laravel, app móvil y bases PostgreSQL activas.
