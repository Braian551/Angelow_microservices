# Historias de usuario de Angelow

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Estándar de historia de usuario](#estándar-de-historia-de-usuario)
- [Resumen del análisis](#resumen-del-análisis)
- [Cobertura por épica](#cobertura-por-épica)
- [Historias de usuario](#historias-de-usuario)
- [Reglas transversales](#reglas-transversales)
- [Fuentes funcionales revisadas](#fuentes-funcionales-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Este documento consolida las historias de usuario del sistema Angelow en lenguaje funcional y entendible para cliente, usuarios finales, administradores, instructores y equipo de negocio.

Las historias se basan en la ficha del proyecto, la matriz de requerimientos, el comportamiento visible de la tienda, la cuenta del cliente, el proceso de compra, el panel administrativo, los reportes, las notificaciones y la operación general del sistema.

No se incluyen nombres de archivos, clases, rutas internas ni detalles de implementación. La intención es que cada historia pueda leerse como una necesidad del negocio y validarse con usuarios de negocio.

## Estándar de historia de usuario

Cada historia usa este formato:

- **Historia:** Como actor, quiero una capacidad, para obtener un beneficio.
- **Criterios de aceptación:** condiciones visibles que permiten saber si la historia se cumple.
- **Prioridad:** Alta, Media o Baja según impacto en compra, operación, seguridad o experiencia.
- **Área funcional:** módulo de negocio al que pertenece.

## Resumen del análisis

| Elemento revisado | Resultado |
|---|---:|
| Requerimientos funcionales, reglas y datos revisados | 396 registros funcionales detectados en la matriz actual |
| Épicas funcionales cubiertas | 16 |
| Historias de usuario consolidadas | 76 |
| Actores considerados | Visitante, cliente, administrador, operador y sistema |
| Nivel de redacción | Funcional, orientado a cliente y sin referencias internas |

## Cobertura por épica

| Épica | Historias |
|---|---:|
| Gestión de Usuarios y Acceso | 10 |
| Navegación Pública | 3 |
| Catálogo y Productos | 8 |
| Reseñas, Preguntas y Favoritos | 6 |
| Direcciones y Envíos | 4 |
| Carrito de Compras | 4 |
| Compra, Órdenes y Postventa | 8 |
| Pagos y Facturación | 5 |
| Inventario | 4 |
| Promociones y Descuentos | 4 |
| Contenido y Configuración del Sitio | 3 |
| Notificaciones y Postventa | 5 |
| Panel Administrativo | 4 |
| Informes y Auditoría | 4 |
| Operación e Integración del Sistema | 3 |
| Mi cuenta | 1 |

## Historias de usuario

### Gestión de Usuarios y Acceso

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-001 | Como visitante, quiero registrarme con mis datos personales básicos, para poder comprar y consultar mi cuenta. | El sistema solicita nombre, correo, teléfono y contraseña; valida campos obligatorios; confirma la contraseña; exige aceptación de términos; crea una cuenta activa cuando todo es correcto. | Alta | Registro de usuarios |
| HU-002 | Como visitante, quiero verificar mi correo con un código, para confirmar que la cuenta me pertenece antes de terminar el registro. | El sistema envía un código al correo; permite reenviarlo con tiempo de espera; valida que el código esté vigente; no permite continuar si el correo no fue verificado. | Alta | Verificación de correo |
| HU-003 | Como usuario, quiero iniciar sesión con correo o teléfono y contraseña, para acceder a mi cuenta, mis pedidos y mis datos. | El sistema valida que la cuenta exista, no esté bloqueada y tenga credenciales correctas; al ingresar redirige al área correspondiente según el rol. | Alta | Inicio de sesión |
| HU-004 | Como usuario, quiero que el sistema proteja mi acceso ante intentos fallidos, para reducir riesgos de uso indebido de mi cuenta. | El sistema cuenta intentos fallidos; solicita verificación adicional cuando hay varios errores; puede bloquear temporalmente el acceso; muestra mensajes claros sin exponer detalles sensibles. | Alta | Seguridad de acceso |
| HU-005 | Como visitante, quiero iniciar sesión con Google, para entrar más rápido usando una cuenta externa confiable. | El sistema valida la cuenta de Google; si el correo ya existe, reutiliza la cuenta; si no existe, crea un perfil con los datos recibidos. | Media | Inicio social |
| HU-006 | Como usuario autenticado, quiero cerrar sesión y consultar mi sesión activa, para controlar mi acceso en tienda o panel. | El sistema permite ver los datos básicos de la sesión; al cerrar sesión cancela el acceso activo y redirige a una pantalla segura. | Alta | Sesión |
| HU-007 | Como cliente, quiero actualizar mi perfil, teléfono, foto y contraseña, para mantener mi información vigente. | El sistema permite editar datos personales; valida formatos; conserva el correo como identificador; para cambiar contraseña exige la contraseña actual y confirmación de la nueva. | Alta | Perfil de usuario |
| HU-008 | Como usuario, quiero recuperar mi contraseña con un código enviado al correo, para volver a ingresar si la olvido. | El sistema solicita un identificador registrado; envía un código temporal; valida el código; permite crear una nueva contraseña cuando la verificación fue exitosa. | Alta | Recuperación de cuenta |
| HU-009 | Como administrador, quiero recuperar mi contraseña desde una pantalla administrativa, para restablecer mi acceso sin intervención manual. | El sistema valida el identificador; envía y verifica un código; permite registrar una nueva contraseña; redirige al inicio de sesión cuando termina el proceso. | Alta | Recuperación administrativa |
| HU-010 | Como administrador, quiero gestionar administradores y clientes, para controlar accesos, estados y datos de operación. | El sistema permite listar, crear, editar, bloquear, reactivar, eliminar o exportar usuarios según permisos; exige confirmación en acciones críticas; evita duplicar correos. | Alta | Administración de usuarios |

### Navegación Pública

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-011 | Como visitante, quiero navegar por inicio, tienda, colecciones, carrito, ingreso y cuenta, para moverme fácilmente por la plataforma. | El sistema muestra enlaces claros; mantiene la experiencia fluida; conserva el estado de sesión; permite regresar a la compra sin perder contexto. | Alta | Navegación pública |
| HU-012 | Como visitante, quiero abrir y cerrar el menú en celular, para navegar cómodamente en pantallas pequeñas. | El sistema muestra un menú adaptado a móvil; permite cerrarlo al navegar o tocar fuera; mantiene accesos principales visibles. | Media | Menú móvil |
| HU-013 | Como visitante, quiero buscar productos con sugerencias, para encontrar rápido prendas, categorías o colecciones. | El sistema muestra sugerencias al escribir; permite abrir productos o ir a la tienda filtrada; no muestra resultados vacíos sin orientación. | Media | Búsqueda pública |

### Catálogo y Productos

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-014 | Como visitante, quiero ver una página principal con productos, categorías, colecciones, anuncios y promociones, para descubrir la oferta de la tienda. | El sistema muestra solo contenido activo; usa imágenes válidas; permite entrar a productos, colecciones o tienda; oculta elementos no vigentes. | Alta | Página principal |
| HU-015 | Como comprador, quiero filtrar y buscar productos por categoría, colección, género, oferta o texto, para encontrar prendas adecuadas. | El sistema actualiza resultados al aplicar filtros; muestra cantidad de productos encontrados; permite limpiar filtros; mantiene paginación clara. | Alta | Tienda pública |
| HU-016 | Como comprador, quiero ver el detalle completo de un producto, para decidir si lo compro. | El sistema muestra nombre, precio, descripción, imágenes, variantes, tallas, disponibilidad, reseñas y preguntas; controla productos no disponibles. | Alta | Detalle de producto |
| HU-017 | Como comprador, quiero seleccionar color, talla y cantidad, para agregar al carrito una combinación válida. | El sistema solo permite continuar si la combinación existe, tiene stock y la cantidad es válida; informa cuando una opción no está disponible. | Alta | Selección de variante |
| HU-018 | Como administrador, quiero crear, editar, activar, desactivar y eliminar productos, para mantener actualizado el catálogo. | El sistema permite gestionar datos, imágenes, precios, variantes y estado; valida campos obligatorios; protege productos con información incompleta. | Alta | Gestión de productos |
| HU-019 | Como administrador, quiero gestionar categorías y colecciones, para organizar la tienda de forma clara. | El sistema permite crear, editar, activar o eliminar grupos; controla imágenes; evita eliminar elementos usados cuando puede afectar el catálogo. | Alta | Categorías y colecciones |
| HU-020 | Como administrador, quiero gestionar tallas, colores y variantes, para vender productos por combinación real. | El sistema permite administrar opciones; evita duplicados; informa si una talla o variante está en uso; mantiene el catálogo consistente. | Media | Variantes |
| HU-021 | Como sistema, quiero compartir información básica de productos con los procesos de compra, para validar disponibilidad antes de vender. | El sistema confirma si el producto y la variante existen; responde disponibilidad; evita continuar compras con datos inexistentes o agotados. | Alta | Validación de producto |

### Reseñas, Preguntas y Favoritos

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-022 | Como cliente, quiero marcar y desmarcar favoritos, para guardar productos que quiero revisar después. | El sistema exige sesión para guardar favoritos; actualiza el estado del producto; evita duplicados en la lista personal. | Media | Favoritos |
| HU-023 | Como cliente, quiero ver mi lista de favoritos, para volver fácilmente a productos guardados. | El sistema muestra solo mis favoritos; permite abrir el producto; muestra estado vacío cuando no hay productos guardados. | Media | Lista de favoritos |
| HU-024 | Como comprador, quiero ver reseñas y calificaciones, para conocer la experiencia de otros clientes. | El sistema muestra calificación promedio, distribución de estrellas y comentarios aprobados; diferencia información confiable cuando aplica. | Media | Reseñas |
| HU-025 | Como comprador, quiero consultar preguntas y respuestas del producto, para resolver dudas antes de comprar. | El sistema muestra preguntas visibles y respuestas disponibles; diferencia si una respuesta proviene de la tienda. | Media | Preguntas |
| HU-026 | Como administrador, quiero revisar y moderar reseñas de productos, para mantener opiniones confiables en la tienda. | El sistema permite listar reseñas, filtrar, revisar detalle, aprobar, devolver a revisión, marcar compra verificada, eliminar y exportar resultados según filtros. | Media | Moderación de reseñas |
| HU-027 | Como administrador, quiero responder y gestionar preguntas de productos, para resolver dudas de clientes y cuidar la información publicada. | El sistema permite listar preguntas, filtrar por estado, responder, eliminar, ver indicadores y exportar resultados según filtros. | Media | Gestión de preguntas |

### Direcciones y Envíos

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-028 | Como cliente, quiero gestionar mis direcciones, para tener opciones de entrega actualizadas. | El sistema permite listar, crear, editar y eliminar direcciones propias; valida datos obligatorios; muestra mensajes de éxito o error claros. | Alta | Direcciones |
| HU-029 | Como cliente, quiero marcar una dirección como predeterminada, para agilizar mis compras. | El sistema permite elegir una dirección principal; desmarca las demás como principales; usa esa dirección como primera opción cuando compro. | Media | Dirección predeterminada |
| HU-030 | Como cliente, quiero apoyar mi dirección con ubicación en mapa, para mejorar la precisión de entrega. | El sistema permite buscar o seleccionar ubicación; guarda datos de referencia; permite corregir la ubicación antes de guardar. | Media | Ubicación de entrega |
| HU-031 | Como administrador, quiero gestionar métodos y reglas de envío, para controlar costos y condiciones logísticas. | El sistema permite crear, editar o desactivar métodos y reglas; valida rangos; usa solo opciones activas en el proceso de compra. | Alta | Configuración de envíos |

### Carrito de Compras

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-032 | Como comprador, quiero ver mi carrito con productos, cantidades y totales, para revisar la compra antes de pagar. | El sistema muestra producto, variante, cantidad, precio y subtotal; calcula total; informa si algún producto ya no está disponible. | Alta | Carrito |
| HU-033 | Como comprador, quiero agregar, actualizar o eliminar productos del carrito, para ajustar mi compra. | El sistema valida cantidad, disponibilidad y variantes; actualiza totales; confirma eliminación cuando corresponde. | Alta | Gestión de carrito |
| HU-034 | Como comprador, quiero seleccionar qué productos del carrito pagar, para comprar solo una parte si lo necesito. | El sistema permite seleccionar uno o varios productos disponibles; bloquea productos no disponibles; lleva al pago solo lo seleccionado. | Alta | Selección para pago |
| HU-035 | Como administrador, quiero enviar recordatorios de carritos abandonados, para recuperar compras incompletas. | El sistema identifica carritos inactivos; evita enviar recordatorios repetidos sin control; notifica a clientes elegibles. | Media | Recuperación de carrito |

### Checkout, Órdenes y Postventa

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-036 | Como comprador, quiero elegir dirección y método de envío durante la compra, para recibir mi pedido correctamente. | El sistema muestra direcciones disponibles; permite crear o elegir una; calcula costo de envío; no permite avanzar si faltan datos. | Alta | Envío en compra |
| HU-037 | Como comprador, quiero ver la cuenta bancaria y subir comprobante, para pagar por transferencia. | El sistema muestra la cuenta activa; acepta comprobantes permitidos; registra referencia de pago; informa si no hay cuenta disponible. | Alta | Checkout de pago |
| HU-038 | Como comprador, quiero ver confirmación de compra, para saber que mi pedido fue recibido. | El sistema muestra número de pedido, resumen, dirección, productos, total y estado inicial; ofrece ir a pedidos o seguir comprando. | Alta | Confirmación de compra |
| HU-039 | Como cliente, quiero consultar mis pedidos y detalles, para hacer seguimiento de estado, pago, envío y productos. | El sistema muestra solo pedidos propios; permite abrir detalle; informa estado actual; permite descargar factura cuando exista. | Alta | Mis pedidos |
| HU-040 | Como cliente, quiero cancelar una orden permitida, para detener una compra que aún no debe procesarse. | El sistema valida si la orden se puede cancelar; solicita confirmación; actualiza estado; registra motivo y notifica el cambio. | Alta | Cancelación de orden |
| HU-041 | Como cliente, quiero solicitar reembolso cuando mi pedido cumpla condiciones, para reportar un problema postventa. | El sistema permite solicitar reembolso solo dentro de la política vigente; solicita motivo y evidencia; informa el estado de la solicitud. | Alta | Solicitud de reembolso |
| HU-042 | Como administrador, quiero resolver solicitudes de reembolso, para aceptar, rechazar o completar el proceso de forma trazable. | El sistema muestra solicitudes; permite revisar evidencia; bloquea doble acción; actualiza estado; notifica al cliente; registra historial. | Alta | Administración de reembolsos |
| HU-043 | Como cliente, quiero ver cambios de mi pedido en tiempo real, para conocer avances sin recargar la página. | El sistema actualiza estado visible cuando hay cambios relevantes; mantiene la información sincronizada; muestra mensajes entendibles. | Media | Seguimiento en tiempo real |

### Pagos y Facturación

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-044 | Como cliente, quiero consultar bancos y cuenta de pago activa, para transferir al destino correcto. | El sistema muestra bancos disponibles y datos de cuenta necesarios; oculta información que no debe publicarse; informa si falta configuración. | Alta | Información de pago |
| HU-045 | Como administrador, quiero verificar pagos manuales, para aprobar o rechazar transferencias. | El sistema muestra pagos pendientes; permite revisar datos y comprobante; actualiza estado de pago; refleja el cambio en la orden. | Alta | Verificación de pagos |
| HU-046 | Como administrador, quiero previsualizar comprobantes de pago, para validar evidencias sin abrir enlaces rotos. | El sistema muestra el comprobante si está disponible; informa de forma controlada si no se puede ver; nunca expone rutas internas. | Alta | Comprobantes |
| HU-047 | Como administrador, quiero configurar la cuenta bancaria activa, para controlar dónde pagan los clientes. | El sistema valida banco, tipo, número, titular y datos de contacto; deja una cuenta activa para el proceso de compra; guarda cambios con confirmación. | Alta | Configuración bancaria |
| HU-048 | Como cliente o administrador, quiero descargar o reenviar facturas, para conservar soporte de la compra. | El sistema genera o recupera la factura; permite descarga; permite reenvío cuando aplica; informa resultado de la operación. | Alta | Facturación |

### Inventario

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-049 | Como administrador, quiero ver inventario por producto, color y talla, para detectar existencias bajas o agotadas. | El sistema muestra stock, reservado, disponible, alertas y filtros; diferencia estados bajo, crítico y agotado. | Alta | Inventario |
| HU-050 | Como administrador, quiero ajustar stock de una variante, para corregir disponibilidad operativa. | El sistema valida cantidad; actualiza existencia; registra motivo; informa el cambio a las pantallas que dependen del stock. | Alta | Ajuste de stock |
| HU-051 | Como administrador, quiero transferir stock entre variantes, para corregir distribución de inventario. | El sistema valida origen y destino; evita transferir más de lo disponible; registra la transferencia y deja ambas variantes actualizadas. | Media | Transferencia de stock |
| HU-052 | Como sistema, quiero reservar y liberar stock de pedidos, para evitar vender más unidades de las disponibles. | El sistema reserva unidades al crear orden; confirma o libera según avance del pedido; cancela reservas vencidas; mantiene conteos consistentes. | Alta | Reservas de inventario |

### Promociones y Descuentos

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-053 | Como comprador, quiero aplicar códigos de descuento, para obtener beneficios cuando cumplo las condiciones. | El sistema valida vigencia, estado, tipo, productos, límites y uso; informa si el código no aplica. | Alta | Códigos de descuento |
| HU-054 | Como comprador, quiero recibir descuentos por cantidad, para acceder a mejores precios al comprar más unidades. | El sistema reconoce cantidades elegibles; calcula descuento; muestra el beneficio en el resumen. | Media | Descuento por cantidad |
| HU-055 | Como administrador, quiero gestionar códigos y reglas de descuento, para controlar promociones de la tienda. | El sistema permite crear, editar, activar, desactivar o eliminar promociones; valida valores, fechas y condiciones. | Alta | Gestión de promociones |
| HU-056 | Como administrador, quiero enviar campañas promocionales a clientes, para comunicar ofertas específicas o masivas. | El sistema permite seleccionar destinatarios; prepara mensaje; envía campaña; registra resultado de envío. | Media | Campañas |

### Contenido y Configuración del Sitio

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-057 | Como administrador, quiero gestionar el carrusel principal, para controlar las promociones visibles en inicio. | El sistema permite crear, editar, ordenar, activar y eliminar slides; conserva orden correcto; muestra solo contenido activo. | Alta | Carrusel |
| HU-058 | Como administrador, quiero configurar datos generales del sitio, para mantener actualizados logo, textos, contacto y enlaces. | El sistema guarda configuración vigente; refleja cambios en tienda y documentos generados; valida datos requeridos. | Alta | Configuración general |
| HU-059 | Como administrador, quiero publicar anuncios, para informar novedades o avisos importantes a clientes. | El sistema permite definir contenido, prioridad y vigencia; muestra anuncios activos en el lugar correspondiente. | Media | Anuncios |

### Notificaciones y Postventa

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-060 | Como usuario, quiero ver, leer y eliminar mis notificaciones, para mantener mi bandeja organizada. | El sistema muestra notificaciones propias; permite marcar una o todas como leídas; permite eliminar; actualiza contador. | Alta | Notificaciones |
| HU-061 | Como usuario, quiero configurar preferencias de notificación, para decidir qué avisos recibir. | El sistema muestra opciones por canal o evento; guarda preferencias; respeta lo desactivado antes de enviar avisos. | Media | Preferencias |
| HU-062 | Como sistema, quiero enviar notificaciones por eventos importantes, para mantener informados a clientes y administradores. | El sistema crea notificaciones por pedidos, pagos, inventario, descuentos o reembolsos; evita enviar avisos no permitidos. | Alta | Despacho de notificaciones |
| HU-063 | Como administrador, quiero descartar alertas administrativas, para ocultar avisos que ya revisé. | El sistema registra descartes por administrador; no elimina la alerta para otros usuarios; permite mantener el panel limpio. | Media | Alertas administrativas |
| HU-064 | Como cliente, quiero recibir avisos postventa, para conocer facturas, cancelaciones, envíos o reembolsos. | El sistema informa cambios importantes del pedido; usa mensajes claros; respeta preferencias del usuario cuando aplica. | Alta | Postventa |

### Panel Administrativo

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-065 | Como administrador, quiero ver un panel de indicadores con métricas y alertas, para revisar la operación diaria. | El sistema muestra órdenes, ingresos, clientes, pagos pendientes, inventario crítico, gráficos y accesos rápidos. | Alta | Panel de indicadores |
| HU-066 | Como administrador, quiero navegar por el panel sin perder sesión ni contexto, para operar con fluidez. | El sistema muestra menú lateral, submenús y encabezado; resalta la sección activa; protege pantallas administrativas. | Alta | Navegación administrativa |
| HU-067 | Como administrador, quiero buscar órdenes, productos, clientes y accesos rápidos, para encontrar información operativa. | El sistema agrupa resultados; limita búsquedas vacías; abre la pantalla correspondiente al seleccionar un resultado. | Media | Búsqueda administrativa |
| HU-068 | Como administrador, quiero pantallas consistentes con filtros, tablas, modales y estados claros, para trabajar sin confusión. | El sistema reutiliza patrones visuales; muestra cargas, vacíos y errores; mantiene acciones deshabilitadas durante guardado. | Alta | Experiencia administrativa |

### Informes y Auditoría

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-069 | Como administrador, quiero generar informes de ventas, para analizar ingresos, órdenes, descuentos y métodos de pago. | El sistema permite filtrar por período; excluye canceladas cuando corresponde; muestra métricas y gráficos entendibles. | Alta | Informe de ventas |
| HU-070 | Como administrador, quiero generar informes de productos, para identificar prendas populares y stock relevante. | El sistema muestra ranking por ventas e ingresos; permite revisar productos destacados o críticos. | Media | Informe de productos |
| HU-071 | Como administrador, quiero generar informes de clientes, para reconocer clientes recurrentes y valor de compra. | El sistema muestra clientes con compras, total gastado, promedio y última compra; permite filtros de análisis. | Media | Informe de clientes |
| HU-072 | Como personal autorizado, quiero consultar auditoría, para revisar acciones importantes sobre pedidos, usuarios y productos. | El sistema muestra registros con fecha, responsable, entidad y detalle; restringe acceso a personal autorizado. | Media | Auditoría |

### Operación e Integración del Sistema

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-073 | Como personal de soporte, quiero saber si las áreas del sistema están disponibles, para detectar fallos de operación. | El sistema permite consultar disponibilidad básica; informa estado de cada área principal; facilita revisión rápida. | Alta | Disponibilidad |
| HU-074 | Como sistema, quiero consultar datos mínimos de usuarios cuando una operación lo requiere, para completar pedidos, reportes o atención. | El sistema comparte solo información necesaria; evita duplicar datos; mantiene consistencia entre áreas. | Alta | Sincronización de datos |
| HU-075 | Como usuario, quiero que cambios de stock, pedidos y pagos se reflejen oportunamente, para ver información actualizada. | El sistema comunica cambios importantes a las pantallas abiertas; actualiza datos sin obligar al usuario a recargar manualmente. | Alta | Actualización oportuna |

### Mi cuenta

| ID | Historia | Criterios de aceptación | Prioridad | Área funcional |
|---|---|---|---|---|
| HU-076 | Como cliente, quiero ver un resumen de mi cuenta, para acceder rápido a pedidos, direcciones, favoritos, notificaciones y configuración. | El sistema muestra solo información propia; ofrece accesos claros; presenta estados vacíos cuando aún no hay actividad. | Alta | Resumen de cuenta |

## Reglas transversales

- Todo formulario debe validar campos antes de guardar.
- Las acciones que guardan cambios deben evitar doble envío.
- Los mensajes deben ser claros, en español y orientados al usuario.
- Los estados internos deben mostrarse con términos entendibles.
- Los archivos o imágenes no disponibles deben mostrar un mensaje controlado.
- Las pantallas administrativas deben conservar diseño consistente.
- Los datos de cliente solo deben verse o editarse por usuarios autorizados.
- Las operaciones críticas deben dejar trazabilidad visible o consultable.

## Fuentes funcionales revisadas

- Ficha del proyecto Angelow.
- Matriz de requerimientos funcionales actualizada.
- Documentación general del sistema.
- Manual de operación.
- Registro de patrones de diseño.
- Navegación pública, cuenta de cliente, proceso de compra y panel administrativo.
- Flujos de autenticación, catálogo, carrito, pagos, pedidos, envíos, descuentos, notificaciones, inventario, reportes y auditoría.

## Documentos relacionados

- [Ficha del proyecto](../proyecto/FICHA_PROYECTO_ANGELOW.md)
- [Matriz de requerimientos funcionales](matriz-requerimientos-funcionales-actualizada.md)
- [Casos de uso del sistema](casos-uso-angelow.md)
- [Índice general de documentación](../README.md)
- [Manual de operación](../operaciones/manual-tecnico.md)
