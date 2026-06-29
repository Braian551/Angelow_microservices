# Casos de uso del sistema Angelow

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Actores](#actores)
- [Resumen de casos de uso](#resumen-de-casos-de-uso)
- [Casos de uso](#casos-de-uso)
- [Reglas generales del sistema](#reglas-generales-del-sistema)
- [Fuentes funcionales revisadas](#fuentes-funcionales-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Este documento describe los casos de uso principales de Angelow en lenguaje funcional, orientado a cliente y usuarios del negocio. Cubre la tienda pública, registro, cuenta de cliente, catálogo, carrito, proceso de compra, pedidos, pagos, envíos, descuentos, notificaciones, panel administrativo, inventario, reportes, auditoría y operación general.

Los casos de uso se basan en las historias de usuario, la matriz de requerimientos y el comportamiento esperado del sistema. No incluyen nombres de archivos, clases, pantallas internas ni detalles internos de implementación.

## Actores

| Actor | Descripción |
|---|---|
| Visitante | Persona que navega la tienda sin iniciar sesión. |
| Cliente | Usuario registrado que compra, guarda direcciones, consulta pedidos y recibe notificaciones. |
| Administrador | Usuario autorizado para operar catálogo, pedidos, pagos, inventario, clientes, promociones, contenido y reportes. |
| Personal de soporte | Persona responsable de verificar disponibilidad, mantenimiento y continuidad del sistema. |
| Sistema | Procesos automáticos que validan, notifican, reservan stock, actualizan estados o sincronizan información. |

## Resumen de casos de uso

| ID | Caso de uso | Actor principal | Área |
|---|---|---|---|
| CU-001 | Registrarse en la tienda | Visitante | Usuarios y acceso |
| CU-002 | Verificar correo de registro | Visitante | Usuarios y acceso |
| CU-003 | Iniciar sesión | Usuario | Usuarios y acceso |
| CU-004 | Iniciar sesión con Google | Visitante | Usuarios y acceso |
| CU-005 | Recuperar contraseña | Usuario | Usuarios y acceso |
| CU-006 | Gestionar perfil y contraseña | Cliente | Mi cuenta |
| CU-007 | Administrar usuarios | Administrador | Usuarios y acceso |
| CU-008 | Navegar la tienda | Visitante | Navegación pública |
| CU-009 | Buscar productos | Visitante | Catálogo |
| CU-010 | Consultar inicio de tienda | Visitante | Contenido |
| CU-011 | Consultar catálogo filtrado | Visitante | Catálogo |
| CU-012 | Consultar detalle de producto | Visitante | Catálogo |
| CU-013 | Gestionar favoritos | Cliente | Favoritos |
| CU-014 | Consultar reseñas y preguntas | Visitante | Comunidad |
| CU-015 | Gestionar direcciones | Cliente | Envíos |
| CU-016 | Seleccionar ubicación de entrega | Cliente | Envíos |
| CU-017 | Gestionar carrito | Comprador | Carrito |
| CU-018 | Seleccionar productos para pagar | Comprador | Carrito |
| CU-019 | Elegir dirección y envío | Cliente | Compra |
| CU-020 | Registrar pago por transferencia | Cliente | Pagos |
| CU-021 | Confirmar compra | Cliente | Órdenes |
| CU-022 | Consultar pedidos | Cliente | Órdenes |
| CU-023 | Cancelar pedido | Cliente | Órdenes |
| CU-024 | Solicitar reembolso | Cliente | Postventa |
| CU-025 | Consultar notificaciones | Cliente | Notificaciones |
| CU-026 | Configurar preferencias | Cliente | Notificaciones |
| CU-027 | Gestionar productos | Administrador | Catálogo |
| CU-028 | Gestionar categorías y colecciones | Administrador | Catálogo |
| CU-029 | Gestionar tallas, colores y variantes | Administrador | Catálogo |
| CU-030 | Gestionar inventario | Administrador | Inventario |
| CU-031 | Gestionar pedidos | Administrador | Órdenes |
| CU-032 | Verificar pagos | Administrador | Pagos |
| CU-033 | Gestionar cuenta bancaria | Administrador | Pagos |
| CU-034 | Gestionar facturas | Administrador | Facturación |
| CU-035 | Gestionar solicitudes de reembolso | Administrador | Postventa |
| CU-036 | Gestionar métodos y reglas de envío | Administrador | Envíos |
| CU-037 | Gestionar descuentos | Administrador | Promociones |
| CU-038 | Enviar campañas promocionales | Administrador | Promociones |
| CU-039 | Gestionar anuncios, sliders y configuración | Administrador | Contenido |
| CU-040 | Consultar panel de indicadores | Administrador | Panel administrativo |
| CU-041 | Buscar información en el panel | Administrador | Panel administrativo |
| CU-042 | Gestionar alertas administrativas | Administrador | Notificaciones |
| CU-043 | Generar informes | Administrador | Informes |
| CU-044 | Consultar auditoría | Administrador | Auditoría |
| CU-045 | Enviar notificaciones automáticas | Sistema | Notificaciones |
| CU-046 | Controlar reservas de inventario | Sistema | Inventario |
| CU-047 | Verificar disponibilidad del sistema | Personal de soporte | Operación |
| CU-048 | Gestionar reseñas de productos | Administrador | Comunidad |
| CU-049 | Gestionar preguntas de productos | Administrador | Comunidad |

## Casos de uso

### CU-001 - Registrarse en la tienda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Crear una cuenta para comprar y acceder a servicios personalizados. |
| Precondiciones | El visitante no tiene sesión activa y cuenta con correo y teléfono válidos. |
| Flujo principal | 1. El visitante abre el formulario de registro. 2. Ingresa nombre, correo, teléfono y contraseña. 3. Acepta términos y condiciones. 4. El sistema valida los datos. 5. El sistema crea la cuenta activa. |
| Alternativas | Si el correo ya existe, se informa que debe iniciar sesión o recuperar acceso. Si la contraseña no cumple reglas, se solicita corrección. |
| Resultado esperado | La cuenta queda registrada y lista para iniciar sesión. |

### CU-002 - Verificar correo de registro

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Confirmar que el correo indicado pertenece al visitante. |
| Precondiciones | El visitante ingresó un correo válido durante el registro. |
| Flujo principal | 1. El visitante solicita código. 2. El sistema envía un código al correo. 3. El visitante escribe el código recibido. 4. El sistema valida vigencia y coincidencia. 5. El visitante continúa el registro. |
| Alternativas | Si el código vence, puede solicitar uno nuevo. Si el código es incorrecto, se muestra error claro. |
| Resultado esperado | El correo queda verificado para completar la cuenta. |

### CU-003 - Iniciar sesión

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Acceder a la tienda, cuenta o panel según el rol. |
| Precondiciones | El usuario tiene cuenta registrada y no bloqueada. |
| Flujo principal | 1. El usuario ingresa correo o teléfono. 2. Ingresa contraseña. 3. El sistema valida credenciales. 4. El sistema abre la sesión. 5. El sistema redirige al destino correspondiente. |
| Alternativas | Si hay fallos repetidos, el sistema solicita verificación adicional o aplica bloqueo temporal. |
| Resultado esperado | El usuario entra a su área permitida. |

### CU-004 - Iniciar sesión con Google

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Ingresar usando una cuenta de Google. |
| Precondiciones | El visitante autoriza el uso de su cuenta de Google. |
| Flujo principal | 1. El visitante elige ingreso con Google. 2. Google confirma identidad. 3. El sistema valida el correo. 4. Si la cuenta existe, la reutiliza. 5. Si no existe, crea un perfil básico. |
| Alternativas | Si Google no confirma identidad, se informa que no fue posible ingresar. |
| Resultado esperado | El usuario entra con sesión activa. |

### CU-005 - Recuperar contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Restablecer el acceso cuando olvidó la contraseña. |
| Precondiciones | La cuenta existe y el usuario puede recibir correo. |
| Flujo principal | 1. El usuario solicita recuperación. 2. El sistema envía un código. 3. El usuario valida el código. 4. Ingresa nueva contraseña y confirmación. 5. El sistema actualiza la contraseña. |
| Alternativas | Si el código vence o no coincide, se permite solicitar uno nuevo según tiempo de espera. |
| Resultado esperado | El usuario puede volver a iniciar sesión. |

### CU-006 - Gestionar perfil y contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mantener actualizados datos personales y acceso. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente entra a configuración de cuenta. 2. Edita nombre, teléfono o foto. 3. Opcionalmente solicita cambio de contraseña. 4. El sistema valida datos. 5. El sistema guarda cambios. |
| Alternativas | Si la contraseña actual no coincide, no se cambia la contraseña. Si una imagen no es válida, se solicita otro archivo. |
| Resultado esperado | El perfil queda actualizado. |

### CU-007 - Administrar usuarios

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Controlar clientes y equipo administrativo. |
| Precondiciones | El administrador tiene sesión y permisos. |
| Flujo principal | 1. El administrador consulta usuarios. 2. Filtra o busca registros. 3. Crea o edita administradores. 4. Bloquea o reactiva cuentas. 5. Exporta información cuando lo requiere. |
| Alternativas | Si intenta duplicar un correo, el sistema rechaza la acción. Si la acción es crítica, solicita confirmación. |
| Resultado esperado | Los usuarios quedan administrados de forma controlada. |

### CU-008 - Navegar la tienda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Recorrer la plataforma de forma clara. |
| Precondiciones | La tienda está disponible. |
| Flujo principal | 1. El visitante abre la tienda. 2. Usa menú principal o móvil. 3. Entra a inicio, tienda, colecciones, producto, carrito o cuenta. 4. El sistema conserva navegación fluida. |
| Alternativas | Si una pantalla requiere sesión, el sistema solicita ingreso y conserva destino de retorno. |
| Resultado esperado | El visitante se mueve sin perder contexto. |

### CU-009 - Buscar productos

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Encontrar productos rápidamente. |
| Precondiciones | Existen productos publicados. |
| Flujo principal | 1. El visitante escribe un término. 2. El sistema muestra sugerencias. 3. El visitante selecciona producto o búsqueda. 4. El sistema abre detalle o tienda filtrada. |
| Alternativas | Si no hay resultados, el sistema muestra un mensaje útil y permite seguir explorando. |
| Resultado esperado | El visitante encuentra productos o recibe orientación. |

### CU-010 - Consultar inicio de tienda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Ver novedades, colecciones y promociones. |
| Precondiciones | Hay contenido activo o productos disponibles. |
| Flujo principal | 1. El visitante abre inicio. 2. El sistema muestra carrusel, categorías, colecciones, productos y anuncios activos. 3. El visitante entra a una sección o producto. |
| Alternativas | Si falta una imagen, el sistema muestra una alternativa controlada. |
| Resultado esperado | El visitante descubre la oferta vigente de la tienda. |

### CU-011 - Consultar catálogo filtrado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Explorar productos según interés. |
| Precondiciones | El catálogo tiene productos activos. |
| Flujo principal | 1. El visitante abre tienda. 2. Aplica filtros de categoría, colección, género, ofertas o búsqueda. 3. El sistema actualiza resultados. 4. El visitante navega páginas o limpia filtros. |
| Alternativas | Si no hay productos para el filtro, se muestra estado vacío con opción de limpiar. |
| Resultado esperado | El visitante ve productos relevantes. |

### CU-012 - Consultar detalle de producto

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Revisar información antes de comprar. |
| Precondiciones | El producto existe y está disponible para consulta. |
| Flujo principal | 1. El visitante abre producto. 2. Revisa imágenes, descripción, precio, variantes, stock, reseñas y preguntas. 3. Selecciona talla, color y cantidad. 4. Agrega al carrito o continúa compra. |
| Alternativas | Si no hay stock, el sistema bloquea compra y muestra estado claro. |
| Resultado esperado | El visitante decide con información suficiente. |

### CU-013 - Gestionar favoritos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Guardar productos de interés. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente marca un producto como favorito. 2. El sistema lo agrega a su lista. 3. El cliente consulta favoritos desde su cuenta. 4. Puede abrir producto o quitarlo. |
| Alternativas | Si no tiene sesión, se solicita iniciar sesión. |
| Resultado esperado | El cliente conserva productos para revisar después. |

### CU-014 - Consultar reseñas y preguntas

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Resolver dudas y evaluar confianza del producto. |
| Precondiciones | El producto tiene reseñas o preguntas visibles. |
| Flujo principal | 1. El visitante abre el detalle. 2. Consulta calificaciones, comentarios, preguntas y respuestas. 3. Usa esa información para decidir. |
| Alternativas | Si no hay contenido, el sistema muestra un estado informativo. |
| Resultado esperado | El visitante cuenta con información social del producto. |

### CU-015 - Gestionar direcciones

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Tener direcciones listas para entrega. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente abre direcciones. 2. Crea, edita o elimina una dirección. 3. Marca una como predeterminada. 4. El sistema guarda y actualiza el listado. |
| Alternativas | Si faltan datos obligatorios, se solicitan correcciones. |
| Resultado esperado | El cliente tiene direcciones actualizadas para comprar. |

### CU-016 - Seleccionar ubicación de entrega

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mejorar precisión de entrega. |
| Precondiciones | El cliente está creando o editando una dirección. |
| Flujo principal | 1. El cliente abre el selector de ubicación. 2. Busca o selecciona un punto. 3. Revisa la referencia. 4. Guarda la ubicación con la dirección. |
| Alternativas | Si no encuentra ubicación exacta, puede ajustar datos manualmente. |
| Resultado esperado | La dirección queda con referencia útil de ubicación. |

### CU-017 - Gestionar carrito

| Campo | Descripción |
|---|---|
| Actor principal | Comprador |
| Objetivo | Preparar productos antes de comprar. |
| Precondiciones | El comprador seleccionó productos. |
| Flujo principal | 1. Agrega producto al carrito. 2. Consulta carrito. 3. Cambia cantidades o elimina productos. 4. El sistema recalcula totales. |
| Alternativas | Si la cantidad supera disponibilidad, el sistema solicita ajustar. |
| Resultado esperado | El carrito queda listo para continuar con el pago. |

### CU-018 - Seleccionar productos para pagar

| Campo | Descripción |
|---|---|
| Actor principal | Comprador |
| Objetivo | Pagar solo los productos seleccionados. |
| Precondiciones | El carrito tiene productos disponibles. |
| Flujo principal | 1. El comprador selecciona productos. 2. El sistema calcula total seleccionado. 3. El comprador continúa al pago. |
| Alternativas | Si no selecciona productos válidos, el sistema no permite avanzar. |
| Resultado esperado | El pago recibe solo productos elegidos. |

### CU-019 - Elegir dirección y envío

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Definir dónde y cómo recibir el pedido. |
| Precondiciones | El cliente tiene productos listos para comprar. |
| Flujo principal | 1. El cliente selecciona dirección. 2. El sistema muestra métodos de envío. 3. El cliente elige método. 4. El sistema calcula costo y permite continuar. |
| Alternativas | Si no hay dirección, el sistema permite crear una. Si no hay método disponible, informa el problema. |
| Resultado esperado | El pedido queda con datos de envío válidos. |

### CU-020 - Registrar pago por transferencia

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Registrar evidencia de pago. |
| Precondiciones | Existe una cuenta bancaria activa y el cliente está en el proceso de compra. |
| Flujo principal | 1. El cliente consulta datos de pago. 2. Realiza transferencia. 3. Sube comprobante. 4. El sistema registra el pago pendiente de verificación. |
| Alternativas | Si no hay cuenta activa o el archivo no es válido, el sistema no permite continuar. |
| Resultado esperado | El pago queda registrado para revisión. |

### CU-021 - Confirmar compra

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Finalizar el pedido. |
| Precondiciones | El cliente completó envío y pago. |
| Flujo principal | 1. El sistema valida productos, dirección y comprobante. 2. Crea el pedido. 3. Reserva inventario. 4. Muestra confirmación. |
| Alternativas | Si cambia la disponibilidad, se informa y no se confirma una compra inválida. |
| Resultado esperado | El pedido queda creado y visible para seguimiento. |

### CU-022 - Consultar pedidos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Hacer seguimiento de compras. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente abre sus pedidos. 2. Consulta listado. 3. Abre detalle. 4. Revisa estado, pago, envío, productos y factura. |
| Alternativas | Si no tiene pedidos, el sistema invita a comprar. |
| Resultado esperado | El cliente conoce el estado de sus compras. |

### CU-023 - Cancelar pedido

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Detener un pedido permitido. |
| Precondiciones | El pedido pertenece al cliente y su estado permite cancelación. |
| Flujo principal | 1. El cliente abre detalle. 2. Solicita cancelar. 3. Confirma acción. 4. El sistema cambia estado y registra motivo. |
| Alternativas | Si el pedido ya no se puede cancelar, se informa la razón. |
| Resultado esperado | El pedido queda cancelado y notificado. |

### CU-024 - Solicitar reembolso

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Reportar un problema postventa. |
| Precondiciones | El pedido cumple política de reembolso. |
| Flujo principal | 1. El cliente abre pedido. 2. Selecciona solicitar reembolso. 3. Ingresa motivo y evidencia. 4. El sistema registra solicitud pendiente. |
| Alternativas | Si la política no aplica, el sistema no muestra o no permite la solicitud. |
| Resultado esperado | La solicitud queda lista para revisión administrativa. |

### CU-025 - Consultar notificaciones

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Revisar avisos del sistema. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente abre notificaciones. 2. Revisa mensajes. 3. Marca como leído o elimina. 4. El contador se actualiza. |
| Alternativas | Si no hay notificaciones, se muestra estado vacío. |
| Resultado esperado | El cliente gestiona su bandeja. |

### CU-026 - Configurar preferencias

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Decidir qué avisos recibir. |
| Precondiciones | El cliente tiene sesión activa. |
| Flujo principal | 1. El cliente abre configuración. 2. Activa o desactiva preferencias. 3. Guarda cambios. 4. El sistema respeta preferencias futuras. |
| Alternativas | Si ocurre un error, se conserva el estado anterior y se informa. |
| Resultado esperado | Las preferencias quedan actualizadas. |

### CU-027 - Gestionar productos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mantener catálogo vendible. |
| Precondiciones | El administrador tiene permisos. |
| Flujo principal | 1. Consulta productos. 2. Crea o edita producto. 3. Gestiona imágenes, precio, estado y variantes. 4. Guarda cambios. |
| Alternativas | Si faltan datos obligatorios, el sistema bloquea guardado. |
| Resultado esperado | El producto queda publicado o actualizado correctamente. |

### CU-028 - Gestionar categorías y colecciones

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Organizar productos de la tienda. |
| Precondiciones | El administrador tiene sesión activa. |
| Flujo principal | 1. Consulta categorías o colecciones. 2. Crea, edita o elimina registros. 3. Define imagen y estado. 4. Guarda cambios. |
| Alternativas | Si hay productos asociados, el sistema puede impedir eliminación para proteger el catálogo. |
| Resultado esperado | La organización de tienda queda actualizada. |

### CU-029 - Gestionar tallas, colores y variantes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Controlar combinaciones vendibles. |
| Precondiciones | Existen productos para asociar variantes. |
| Flujo principal | 1. El administrador administra opciones de talla y color. 2. Crea variantes del producto. 3. Define stock e imágenes. 4. Guarda. |
| Alternativas | Si la opción ya existe, el sistema evita duplicidad. |
| Resultado esperado | Las variantes quedan listas para compra. |

### CU-030 - Gestionar inventario

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mantener disponibilidad real. |
| Precondiciones | Existen productos con variantes. |
| Flujo principal | 1. Consulta inventario. 2. Filtra por producto o estado. 3. Ajusta stock o transfiere unidades. 4. El sistema registra el cambio. |
| Alternativas | Si intenta transferir más de lo disponible, se bloquea la acción. |
| Resultado esperado | El inventario refleja existencias reales. |

### CU-031 - Gestionar pedidos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Supervisar y actualizar órdenes. |
| Precondiciones | Existen pedidos registrados. |
| Flujo principal | 1. Consulta órdenes. 2. Filtra o busca. 3. Abre detalle. 4. Actualiza estado de orden o pago cuando corresponde. |
| Alternativas | Si el cambio no es permitido, el sistema informa y conserva estado anterior. |
| Resultado esperado | La orden queda actualizada y trazable. |

### CU-032 - Verificar pagos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Confirmar transferencias recibidas. |
| Precondiciones | Existen pagos pendientes. |
| Flujo principal | 1. Consulta pagos. 2. Revisa datos y comprobante. 3. Aprueba o rechaza. 4. El sistema actualiza pago y pedido. |
| Alternativas | Si no se puede ver comprobante, el sistema muestra estado controlado. |
| Resultado esperado | El pago queda resuelto. |

### CU-033 - Gestionar cuenta bancaria

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Definir cuenta para transferencias. |
| Precondiciones | El administrador tiene permisos. |
| Flujo principal | 1. Abre configuración de pagos. 2. Registra banco, tipo, número, titular y contacto. 3. Guarda como cuenta activa. |
| Alternativas | Si faltan datos, se solicita completar. |
| Resultado esperado | La cuenta queda disponible para clientes. |

### CU-034 - Gestionar facturas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Consultar, descargar o reenviar facturas. |
| Precondiciones | Existen órdenes facturables. |
| Flujo principal | 1. Consulta facturas. 2. Filtra o busca. 3. Descarga o reenvía una factura. 4. El sistema informa resultado. |
| Alternativas | Si la factura aún no existe, el sistema intenta generarla cuando corresponda. |
| Resultado esperado | La factura queda disponible para cliente o administración. |

### CU-035 - Gestionar solicitudes de reembolso

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Resolver solicitudes postventa. |
| Precondiciones | Existen solicitudes registradas. |
| Flujo principal | 1. Consulta reembolsos. 2. Revisa motivo y evidencia. 3. Acepta, rechaza o completa. 4. El sistema registra historial y notifica al cliente. |
| Alternativas | Si falta evidencia o nota requerida, solicita completar antes de guardar. |
| Resultado esperado | La solicitud queda resuelta o en proceso. |

### CU-036 - Gestionar métodos y reglas de envío

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Controlar opciones y costos de envío. |
| Precondiciones | El administrador tiene permisos. |
| Flujo principal | 1. Consulta métodos o reglas. 2. Crea o edita datos. 3. Define costo, rangos y estado. 4. Guarda. |
| Alternativas | Si el rango es inválido, el sistema no permite guardar. |
| Resultado esperado | El proceso de compra usa reglas vigentes. |

### CU-037 - Gestionar descuentos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Crear promociones controladas. |
| Precondiciones | El administrador tiene permisos. |
| Flujo principal | 1. Consulta descuentos. 2. Crea código o regla por cantidad. 3. Define tipo, valor, fechas y límites. 4. Guarda. |
| Alternativas | Si el valor o la fecha no son válidos, se solicita corrección. |
| Resultado esperado | La promoción queda lista para aplicar. |

### CU-038 - Enviar campañas promocionales

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Comunicar promociones a clientes. |
| Precondiciones | Existe una promoción o mensaje definido. |
| Flujo principal | 1. Selecciona campaña. 2. Define destinatarios. 3. Revisa mensaje. 4. Envía. |
| Alternativas | Si no hay destinatarios válidos, se impide el envío. |
| Resultado esperado | La campaña se entrega o queda registrada con resultado. |

### CU-039 - Gestionar anuncios, sliders y configuración

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Controlar contenido visible de la tienda. |
| Precondiciones | El administrador tiene sesión activa. |
| Flujo principal | 1. Administra sliders, anuncios o datos generales. 2. Define imágenes, textos, orden, vigencia y estado. 3. Guarda cambios. |
| Alternativas | Si una imagen no es válida, se solicita reemplazarla. |
| Resultado esperado | La tienda muestra contenido actualizado. |

### CU-040 - Consultar panel de indicadores

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Ver estado general del negocio. |
| Precondiciones | El administrador tiene acceso al panel. |
| Flujo principal | 1. Abre el panel de indicadores. 2. Revisa métricas, alertas, gráficos y acciones rápidas. 3. Entra a la sección requerida. |
| Alternativas | Si no hay datos, se muestran estados vacíos entendibles. |
| Resultado esperado | El administrador identifica prioridades operativas. |

### CU-041 - Buscar información en el panel

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Encontrar rápidamente entidades y acciones. |
| Precondiciones | El administrador está en el panel. |
| Flujo principal | 1. Escribe término de búsqueda. 2. El sistema agrupa resultados. 3. Selecciona un resultado. 4. El sistema abre la sección correspondiente. |
| Alternativas | Si no hay resultados, se muestra mensaje claro. |
| Resultado esperado | El administrador accede rápido a la información. |

### CU-042 - Gestionar alertas administrativas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mantener el panel de alertas organizado. |
| Precondiciones | Existen alertas administrativas. |
| Flujo principal | 1. Abre notificaciones del panel. 2. Revisa alertas. 3. Marca como leídas o descarta. |
| Alternativas | Si una alerta tiene acción relacionada, puede abrir la sección asociada. |
| Resultado esperado | El administrador controla sus avisos operativos. |

### CU-043 - Generar informes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Analizar ventas, productos y clientes. |
| Precondiciones | Existen datos de operación. |
| Flujo principal | 1. Abre informes. 2. Elige ventas, productos o clientes. 3. Aplica filtros. 4. Revisa métricas y gráficos. 5. Exporta o imprime si necesita. |
| Alternativas | Si los filtros no devuelven datos, se muestra estado vacío y opción de limpiar. |
| Resultado esperado | El administrador obtiene información para decisiones. |

### CU-044 - Consultar auditoría

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Revisar trazabilidad de acciones importantes. |
| Precondiciones | El usuario tiene autorización para auditoría. |
| Flujo principal | 1. Consulta auditoría de pedidos, usuarios o productos. 2. Revisa fecha, responsable y detalle. 3. Usa la información para seguimiento. |
| Alternativas | Si no hay registros, se muestra estado vacío. |
| Resultado esperado | La operación cuenta con trazabilidad consultable. |

### CU-045 - Enviar notificaciones automáticas

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Informar eventos relevantes sin intervención manual. |
| Precondiciones | Ocurre un evento que requiere aviso. |
| Flujo principal | 1. El sistema identifica evento. 2. Determina destinatario. 3. Revisa preferencias. 4. Crea notificación o correo. 5. Actualiza contador. |
| Alternativas | Si el usuario desactivó el aviso, el sistema omite el envío. |
| Resultado esperado | Los usuarios reciben avisos oportunos. |

### CU-046 - Controlar reservas de inventario

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Evitar sobreventa durante el proceso de compra. |
| Precondiciones | Se crea una orden con productos disponibles. |
| Flujo principal | 1. El sistema reserva unidades. 2. Confirma reserva al completar operación. 3. Libera reserva si vence o se cancela. 4. Actualiza disponibilidad. |
| Alternativas | Si no hay stock suficiente, no permite crear o confirmar la compra. |
| Resultado esperado | El inventario se mantiene confiable. |

### CU-047 - Verificar disponibilidad del sistema

| Campo | Descripción |
|---|---|
| Actor principal | Personal de soporte |
| Objetivo | Confirmar que las áreas principales están operativas. |
| Precondiciones | El sistema está desplegado. |
| Flujo principal | 1. El operador consulta disponibilidad. 2. Revisa respuestas de áreas principales. 3. Identifica fallos si existen. 4. Toma acciones de soporte. |
| Alternativas | Si un área no responde, se registra para revisión de soporte. |
| Resultado esperado | El operador conoce el estado general del sistema. |

### CU-048 - Gestionar reseñas de productos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mantener opiniones confiables y útiles para quienes compran. |
| Precondiciones | Existen reseñas registradas y el administrador tiene permisos. |
| Flujo principal | 1. El administrador abre la bandeja de reseñas. 2. Filtra o busca opiniones. 3. Revisa el detalle de una reseña. 4. Aprueba, devuelve a revisión, marca compra verificada o elimina contenido cuando corresponde. 5. Exporta resultados si necesita soporte operativo. |
| Alternativas | Si no hay reseñas para el filtro aplicado, se muestra un estado vacío claro. Si una acción requiere confirmación, el sistema la solicita antes de aplicarla. |
| Resultado esperado | Las reseñas visibles quedan moderadas y la administración conserva una vista clara del estado de opiniones. |

### CU-049 - Gestionar preguntas de productos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Responder dudas de clientes y mantener información pública del producto en buen estado. |
| Precondiciones | Existen preguntas registradas y el administrador tiene permisos. |
| Flujo principal | 1. El administrador abre la bandeja de preguntas. 2. Filtra por búsqueda o estado. 3. Revisa la pregunta del cliente. 4. Publica una respuesta o elimina contenido cuando corresponde. 5. Exporta resultados si necesita soporte operativo. |
| Alternativas | Si la pregunta ya tiene respuesta, el sistema permite revisarla sin duplicar información. Si no hay preguntas para el filtro aplicado, se muestra un estado vacío claro. |
| Resultado esperado | Las preguntas quedan respondidas o controladas para que el comprador consulte información confiable. |

## Reglas generales del sistema

- El sistema debe mostrar mensajes claros, sin términos internos ni códigos de programación.
- Las acciones de compra, pago, inventario, reembolso y administración deben dejar trazabilidad.
- Los usuarios solo pueden ver y modificar información que les pertenece o para la que tienen permiso.
- Los formularios deben validar datos antes de guardar.
- Las acciones críticas deben solicitar confirmación.
- Los archivos no disponibles deben mostrarse con un mensaje controlado.
- La información presentada al cliente debe estar en español y ser comprensible.
- Las pantallas deben funcionar en computador, tablet y celular.

## Fuentes funcionales revisadas

- Ficha del proyecto Angelow.
- Historias de usuario del sistema.
- Matriz de requerimientos funcionales actualizada.
- Documentación de arquitectura y operación.
- Documentación general por áreas del sistema.
- Flujos visibles de tienda, cuenta, proceso de compra y panel administrativo.

## Documentos relacionados

- [Historias de usuario](historias-usuario-angelow.md)
- [Matriz de requerimientos funcionales](matriz-requerimientos-funcionales-actualizada.md)
- [Ficha del proyecto](../proyecto/FICHA_PROYECTO_ANGELOW.md)
- [Índice general de documentación](../README.md)
- [Manual de operación](../operaciones/manual-tecnico.md)
