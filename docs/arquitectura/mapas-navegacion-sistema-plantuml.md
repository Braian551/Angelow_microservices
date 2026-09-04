# Mapas de navegación del sistema en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Fuente de verdad](#fuente-de-verdad)
- [1) Mapa principal de rutas SPA](#1-mapa-principal-de-rutas-spa)
- [2) Mapa de navegación por dominios funcionales](#2-mapa-de-navegación-por-dominios-funcionales)
- [3) Flujo de protección del router](#3-flujo-de-protección-del-router)
- [4) Mapa administrativo por dominio](#4-mapa-administrativo-por-dominio)
- [5) Navegación de la app móvil del repartidor](#5-navegación-de-la-app-móvil-del-repartidor)
- [6) Proceso operativo de entrega y seguimiento](#6-proceso-operativo-de-entrega-y-seguimiento)
- [Rutas cubiertas](#rutas-cubiertas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Objetivo

Este documento describe la navegación SPA vigente de Angelow, la navegación de la app Flutter del repartidor y su relación con los microservicios que atienden cada dominio funcional. El mapa está pensado como una guía de arquitectura para entender qué rutas existen, qué actor las usa, qué estados intermedios aparecen y qué reglas de acceso aplica cada cliente.

## Fuente de verdad

La fuente principal de las rutas web es `frontend/src/router/index.js`. Para la navegación móvil se revisan `mobile/repartidor/lib/app.dart`, `mobile/repartidor/lib/ui/features/auth/views/auth_flow_view.dart`, `mobile/repartidor/lib/ui/features/courier/views/courier_workspace.dart` y `mobile/repartidor/lib/ui/features/deliveries/views/deliveries_home_view.dart`. El proceso operativo se contrasta además con `services/order-service/app/Http/Controllers/OrderController.php`, `services/shipping-service/routes/api.php` y `frontend/src/modules/account/pages/OrderDetailPage.vue`.

## 1) Mapa principal de rutas SPA

```plantuml
@startuml
title Angelow - Mapa principal de navegación SPA
left to right direction
skinparam packageStyle rectangle
skinparam componentStyle rectangle
skinparam shadowing false

actor "Cliente" as Cliente
actor "Administrador" as Admin
actor "Visitante" as Visitante

package "Rutas públicas" {
  [/] as home
  [/tienda] as store
  [/producto/:slug] as product
  [/colecciones] as collections
  [/carrito] as cart
  [/terminos-y-condiciones] as terms
}

package "Checkout protegido" {
  [/checkout/envio] as checkout_shipping
  [/checkout/pago] as checkout_payment
  [/checkout/confirmacion] as checkout_confirmation
}

package "Autenticación" {
  [/login] as login
  [/registro] as register
  [/recuperar] as forgot
  [/admin/recuperar] as admin_forgot
}

package "Mi cuenta (/mi-cuenta)" {
  [/mi-cuenta] as account_root
  [/mi-cuenta/resumen] as account_dashboard
  [/mi-cuenta/pedidos] as account_orders
  [/mi-cuenta/pedidos/:id] as account_order_detail
  [/mi-cuenta/notificaciones] as account_notifications
  [/mi-cuenta/direcciones] as account_addresses
  [/mi-cuenta/favoritos] as account_wishlist
  [/mi-cuenta/configuracion] as account_settings
}

package "Panel administrativo (/admin)" {
  [/admin] as admin_dashboard

  package "Catálogo" {
    [/admin/productos] as admin_products
    [/admin/productos/nuevo] as admin_product_create
    [/admin/productos/:id/editar] as admin_product_edit
    [/admin/categorias] as admin_categories
    [/admin/colecciones] as admin_collections
    [/admin/tallas] as admin_sizes
    [/admin/inventario] as admin_inventory
  }

  package "Comercial y operación" {
    [/admin/ordenes] as admin_orders
    [/admin/ordenes/:id] as admin_order_detail
    [/admin/clientes] as admin_customers
    [/admin/resenas] as admin_reviews
    [/admin/preguntas] as admin_questions
    [/admin/pagos] as admin_payments
    [/admin/reembolsos] as admin_refunds
    [/admin/facturas] as admin_invoices
  }

  package "Envíos, repartidores, descuentos y contenido" {
    [/admin/envios/reglas] as admin_shipping_rules
    [/admin/envios/metodos] as admin_shipping_methods
    [/admin/repartidores] as admin_couriers
    [/admin/repartidores/envios] as admin_deliveries
    [/admin/descuentos/cantidad] as admin_bulk_discounts
    [/admin/descuentos/codigos] as admin_discount_codes
    [/admin/descuentos/codigos/usuarios-especificos] as admin_discount_specific
    [/admin/anuncios] as admin_announcements
    [/admin/informes] as admin_reports
    [/admin/informes/ventas] as admin_reports_sales
    [/admin/informes/productos] as admin_reports_products
    [/admin/informes/clientes] as admin_reports_customers
    [/admin/sliders] as admin_sliders
    [/admin/configuracion] as admin_settings
    [/admin/configuracion/general] as admin_settings_general
    [/admin/administradores] as admin_administrators
  }
}

package "Redirecciones de compatibilidad" {
  [/dashboard -> /mi-cuenta/resumen] as r_dashboard
  [/mis-pedidos -> /mi-cuenta/pedidos] as r_orders
  [/notificaciones -> /mi-cuenta/notificaciones] as r_notifications
  [/mis-direcciones -> /mi-cuenta/direcciones] as r_addresses
  [/mis-favoritos -> /mi-cuenta/favoritos] as r_wishlist
  [/configuracion-cuenta -> /mi-cuenta/configuracion] as r_settings
  [/favoritos -> /mi-cuenta/favoritos] as r_favorites
}

Visitante --> home
Visitante --> store
Visitante --> collections
Visitante --> product
Visitante --> terms
Visitante --> login
Visitante --> register
Visitante --> forgot

Cliente --> home
Cliente --> account_dashboard
Cliente --> cart

Admin --> admin_dashboard
Admin --> admin_forgot

home --> store
home --> collections
home --> terms
store --> product
collections --> store : filtro por colección
product --> cart
product --> checkout_shipping : comprar ahora
cart --> checkout_shipping
checkout_shipping --> checkout_payment
checkout_payment --> checkout_confirmation
checkout_confirmation --> account_orders
checkout_confirmation --> store

login --> account_dashboard : sesión cliente
login --> admin_dashboard : sesión admin
register --> terms : lectura legal
forgot --> login
admin_forgot --> login

account_root --> account_dashboard : redirección hija
account_dashboard --> account_orders
account_orders --> account_order_detail
account_dashboard --> account_notifications
account_dashboard --> account_addresses
account_dashboard --> account_wishlist
account_dashboard --> account_settings
account_wishlist --> product
account_notifications --> account_orders : notificación de pedido

admin_dashboard --> admin_products
admin_products --> admin_product_create
admin_products --> admin_product_edit
admin_dashboard --> admin_categories
admin_dashboard --> admin_collections
admin_dashboard --> admin_sizes
admin_dashboard --> admin_inventory
admin_dashboard --> admin_orders
admin_orders --> admin_order_detail
admin_dashboard --> admin_customers
admin_dashboard --> admin_reviews
admin_dashboard --> admin_questions
admin_dashboard --> admin_payments
admin_dashboard --> admin_refunds
admin_refunds --> admin_order_detail
admin_dashboard --> admin_invoices
admin_invoices --> admin_order_detail
admin_dashboard --> admin_shipping_rules
admin_dashboard --> admin_shipping_methods
admin_dashboard --> admin_couriers
admin_couriers --> admin_deliveries : consulta operativa
admin_dashboard --> admin_bulk_discounts
admin_dashboard --> admin_discount_codes
admin_discount_codes --> admin_discount_specific
admin_dashboard --> admin_announcements
admin_dashboard --> admin_reports
admin_reports --> admin_reports_sales
admin_reports --> admin_reports_products
admin_reports --> admin_reports_customers
admin_dashboard --> admin_sliders
admin_dashboard --> admin_settings
admin_settings --> admin_settings_general
admin_dashboard --> admin_administrators

r_dashboard --> account_dashboard
r_orders --> account_orders
r_notifications --> account_notifications
r_addresses --> account_addresses
r_wishlist --> account_wishlist
r_settings --> account_settings
r_favorites --> account_wishlist

note right of home
Reglas principales del router:
- /mi-cuenta requiere sesión de cliente.
- /checkout desde envío requiere sesión autenticada.
- /admin requiere sesión y rol administrativo.
- Si una sesión administrativa entra a /, redirige a /admin.
- Una sesión administrativa no puede avanzar por checkout ni cuenta cliente.
- El seguimiento de entrega se muestra dentro de /mi-cuenta/pedidos/:id; no existe una ruta web independiente.
end note
@enduml
```

## 2) Mapa de navegación por dominios funcionales

```plantuml
@startuml
title Angelow - Navegación por dominios funcionales
left to right direction
skinparam packageStyle rectangle
skinparam componentStyle rectangle
skinparam shadowing false

package "Clientes y navegación" {
  [Home, tienda, producto y colecciones] as nav_catalog
  [Carrito] as nav_cart
  [Checkout] as nav_checkout
  [Autenticación y perfil] as nav_auth
  [Mi cuenta] as nav_account
  [Legal público] as nav_legal
  [Panel administrativo] as nav_admin
  [App móvil del repartidor] as nav_courier
}

package "Servicios API" {
  [auth-service] as svc_auth
  [catalog-service] as svc_catalog
  [cart-service] as svc_cart
  [order-service] as svc_order
  [payment-service] as svc_payment
  [discount-service] as svc_discount
  [shipping-service] as svc_shipping
  [notification-service] as svc_notification
  [audit-service] as svc_audit
  [realtime-gateway] as svc_realtime
}

database "PostgreSQL por dominio" as postgres
queue "Redis" as redis

cloud "Mapbox" as ext_mapbox
cloud "Nominatim" as ext_nominatim
cloud "Google Maps / Waze" as ext_navigation

nav_catalog --> svc_catalog : catálogo, búsquedas, categorías, colecciones, favoritos
nav_cart --> svc_cart : carrito, cantidades y selección de ítems
nav_cart --> svc_catalog : producto, variante, imagen y disponibilidad

nav_checkout --> svc_shipping : direcciones, métodos, reglas y estimación
nav_checkout --> svc_discount : cupones y descuentos por cantidad
nav_checkout --> svc_order : creación de pedido, reserva y consulta
nav_checkout --> svc_payment : bancos, cuenta, comprobante y validación
nav_checkout --> svc_notification : confirmaciones y avisos

nav_auth --> svc_auth : login, registro, Google, recuperación y perfil
nav_legal --> nav_auth : enlace desde registro
nav_legal --> nav_checkout : enlace desde pago

nav_account --> svc_order : historial, detalle, factura y reembolso
nav_account --> svc_shipping : direcciones y seguimiento activo de entregas
nav_account --> svc_notification : notificaciones y preferencias
nav_account --> svc_catalog : favoritos y productos
nav_account --> svc_auth : perfil, contraseña y sesión

nav_admin --> svc_auth : clientes y administradores
nav_admin --> svc_catalog : productos, categorías, colecciones, tallas, inventario, sliders, reseñas y preguntas
nav_admin --> svc_order : órdenes, facturas, reembolsos e informes
nav_admin --> svc_payment : pagos y cuenta bancaria
nav_admin --> svc_discount : códigos, campañas y reglas por cantidad
nav_admin --> svc_shipping : métodos, reglas, repartidores y entregas
nav_admin --> svc_notification : anuncios, avisos y preferencias
nav_admin --> svc_audit : trazabilidad operativa
nav_admin --> svc_realtime : eventos de stock y progreso

nav_courier --> svc_auth : correo, Google, verificación, registro y sesión
nav_courier --> svc_shipping : perfil, revisión, entregas, mapa, estados y ubicación
nav_courier --> ext_mapbox : mapa y cálculo de rutas
nav_courier --> ext_nominatim : resolver destino sin coordenadas
nav_courier --> ext_navigation : abrir Google Maps o Waze

svc_shipping --> svc_order : actualiza shipped y delivered
svc_shipping --> svc_notification : revisión, asignación, código y cierre

svc_auth --> postgres
svc_catalog --> postgres
svc_cart --> postgres
svc_order --> postgres
svc_payment --> postgres
svc_discount --> postgres
svc_shipping --> postgres
svc_notification --> postgres
svc_audit --> postgres

svc_order --> redis : locks, reservas, colas
svc_catalog --> redis : cache y stock
svc_notification --> redis : colas
redis --> svc_realtime : Pub/Sub
@enduml
```

## 3) Flujo de protección del router

```plantuml
@startuml
title Angelow - Guard global del router
skinparam shadowing false
skinparam activity {
  BackgroundColor White
  BorderColor Black
}

start
:Leer token local;
if (¿Hay token?) then (sí)
  :Sincronizar sesión con /auth/me;
  if (¿Respuesta 401?) then (sí)
    :Limpiar sesión;
    :Redirigir a /login con redirect;
    stop
  endif
endif
:Leer angelow_user de localStorage;
:Normalizar rol de usuario;

if (¿Ruta "/" con sesión administrativa?) then (sí)
  :Redirigir a /admin;
  stop
endif

if (¿Ruta /admin o requiere rol admin?) then (sí)
  if (¿Tiene sesión?) then (no)
    :Redirigir a /login con redirect;
    stop
  endif
  if (¿Rol administrativo?) then (no)
    :Redirigir a /mi-cuenta/resumen;
    stop
  endif
endif

if (¿Ruta /checkout o requiere checkout autenticado?) then (sí)
  if (¿Tiene sesión?) then (no)
    :Redirigir a /login con redirect al checkout;
    stop
  endif
  if (¿Sesión administrativa?) then (sí)
    :Redirigir a /admin;
    stop
  endif
endif

if (¿Ruta /mi-cuenta?) then (sí)
  if (¿Tiene sesión?) then (no)
    :Redirigir a /login con redirect a cuenta;
    stop
  endif
  if (¿Sesión administrativa?) then (sí)
    :Redirigir a /admin;
    stop
  endif
endif

:Permitir navegación SPA;
stop
@enduml
```

Nota de comportamiento actual: la sincronización ante un error temporal distinto de 401 conserva la sesión local. La ruta `/admin/recuperar` está declarada, pero también coincide con el prefijo `/admin`; con el guard actual exige sesión y rol administrativo antes de mostrar la pantalla.

## 4) Mapa administrativo por dominio

```plantuml
@startuml
title Angelow - Panel administrativo por dominio operativo
left to right direction
skinparam packageStyle rectangle
skinparam componentStyle rectangle
skinparam shadowing false

actor "Administrador" as Admin

package "Panel /admin" {
  [Dashboard] as dashboard

  package "Catálogo e inventario" {
    [Productos] as productos
    [Crear producto] as producto_nuevo
    [Editar producto] as producto_editar
    [Categorías] as categorias
    [Colecciones] as colecciones
    [Tallas] as tallas
    [Inventario] as inventario
    [Sliders] as sliders
  }

  package "Clientes y contenido social" {
    [Clientes] as clientes
    [Reseñas] as resenas
    [Preguntas] as preguntas
    [Anuncios] as anuncios
    [Administradores] as administradores
  }

  package "Operación comercial" {
    [Órdenes] as ordenes
    [Detalle de orden] as detalle_orden
    [Pagos] as pagos
    [Reembolsos] as reembolsos
    [Facturas] as facturas
  }

  package "Configuración de negocio" {
    [Métodos de envío] as metodos_envio
    [Reglas de envío] as reglas_envio
    [Descuentos por cantidad] as descuentos_cantidad
    [Códigos de descuento] as codigos_descuento
    [Campaña por usuarios específicos] as campania_especifica
    [Configuración] as configuracion
    [Configuración general] as configuracion_general
  }

  package "Repartidores y entregas" {
    [Repartidores y revisión] as repartidores
    [Envíos asignados] as envios_asignados
  }

  package "Informes" {
    [Informes] as informes
    [Ventas] as informes_ventas
    [Productos] as informes_productos
    [Clientes] as informes_clientes
  }
}

Admin --> dashboard
dashboard --> productos
productos --> producto_nuevo
productos --> producto_editar
dashboard --> categorias
dashboard --> colecciones
dashboard --> tallas
dashboard --> inventario
dashboard --> sliders

dashboard --> clientes
dashboard --> resenas
dashboard --> preguntas
dashboard --> anuncios
dashboard --> administradores

dashboard --> ordenes
ordenes --> detalle_orden
dashboard --> pagos
dashboard --> reembolsos
reembolsos --> detalle_orden
dashboard --> facturas
facturas --> detalle_orden

dashboard --> metodos_envio
dashboard --> reglas_envio
dashboard --> descuentos_cantidad
dashboard --> codigos_descuento
codigos_descuento --> campania_especifica
dashboard --> configuracion
configuracion --> configuracion_general

dashboard --> repartidores
repartidores --> envios_asignados : seguimiento operativo

dashboard --> informes
informes --> informes_ventas
informes --> informes_productos
informes --> informes_clientes
@enduml
```

## 5) Navegación de la app móvil del repartidor

La app Flutter no usa rutas URL. `AngelowCourierApp` decide entre `AuthFlowView` y `CourierWorkspace`; el workspace decide entre registro, revisión o `DeliveriesHomeView`, y el detalle abre `DeliveryRouteView` según la sesión y el estado del perfil.

```plantuml
@startuml
title Angelow Repartidor - Navegación móvil vigente
skinparam shadowing false
skinparam activity {
  BackgroundColor White
  BorderColor Black
}

start
:Inicializar Firebase y restaurar sesión;

if (¿Sesión persistida?) then (sí)
  :Continuar con la sesión restaurada;
else (no)
  :Mostrar bienvenida;
  if (¿Continuar con Google?) then (sí)
    :Autenticar con Google;
  else (correo)
    :Solicitar correo;
    :Enviar y verificar código de 6 dígitos;
    if (¿Cuenta de repartidor existente?) then (sí)
      :Solicitar contraseña;
      :Iniciar sesión;
    else (cuenta nueva)
      :Registrar identidad, teléfono y contraseña;
    endif
  endif
endif

:Consultar perfil de repartidor;
if (¿Perfil inexistente?) then (sí)
  :Completar identidad, vehículo, documentos y términos;
  :Enviar solicitud de vinculación;
endif

if (¿Perfil aprobado y activo?) then (no)
  :Mostrar estado de revisión;
  if (¿Solicitud rechazada?) then (sí)
    :Corregir documentos y reenviar solicitud;
  endif
  :Actualizar estado manualmente o con gesto de recarga;
  stop
endif

:Mostrar entregas disponibles y entregas propias;
if (¿Toca una entrega disponible?) then (sí)
  :Abrir detalle;
  :Aceptar entrega;
  :Recargar entregas propias;
endif

:Abrir detalle de la entrega asignada;
:Obtener configuración de mapa;
:Resolver destino y preparar ruta;
:Solicitar permiso de ubicación;
:Elegir si comparte ubicación;
:Iniciar ruta;
if (¿Comparte ubicación?) then (sí)
  :Enviar posiciones mientras la ruta está activa;
endif
:Registrar llegada al destino;
:Ingresar código de entrega del cliente;
:Confirmar entrega y volver a la lista;
stop
@enduml
```

## 6) Proceso operativo de entrega y seguimiento

```plantuml
@startuml
title Angelow - Proceso actual de reparto y seguimiento
skinparam shadowing false
skinparam activity {
  BackgroundColor White
  BorderColor Black
}

start
:Pago aprobado;
:order-service publica la elegibilidad en shipping-service;
if (¿El método no requiere repartidor?) then (sí)
  :No crear asignación de entrega;
  stop
endif

:Crear o actualizar delivery_assignment en estado pendiente;
:Repartidor aprobado consulta entregas disponibles;
:Administrador monitorea solicitudes y envíos desde /admin;
:Repartidor abre el detalle y acepta la entrega;
:shipping-service cambia la orden a shipped;
:Enviar notificación y código al cliente;

:Cliente abre /mi-cuenta/pedidos/:id;
:El detalle consulta el seguimiento de entrega;
if (¿Repartidor comparte ubicación?) then (sí)
  :Mostrar última ubicación compartida;
else (no)
  :Mostrar estado y código sin ubicación en vivo;
endif

:Repartidor inicia la ruta;
:Repartidor registra llegada;
:Cliente entrega el código de seis dígitos;
:Repartidor confirma el código;
:shipping-service cambia la orden a delivered;
:Eliminar código operativo y detener el uso de ubicación;
stop
@enduml
```

## Rutas cubiertas

| Grupo | Rutas |
|---|---|
| Públicas | `/`, `/tienda`, `/producto/:slug`, `/colecciones`, `/carrito`, `/terminos-y-condiciones` |
| Checkout | `/checkout/envio`, `/checkout/pago`, `/checkout/confirmacion` |
| Autenticación | `/login`, `/registro`, `/recuperar`, `/admin/recuperar` |
| Mi cuenta | `/mi-cuenta`, `/mi-cuenta/resumen`, `/mi-cuenta/pedidos`, `/mi-cuenta/pedidos/:id`, `/mi-cuenta/notificaciones`, `/mi-cuenta/direcciones`, `/mi-cuenta/favoritos`, `/mi-cuenta/configuracion` |
| Admin catálogo | `/admin`, `/admin/productos`, `/admin/productos/nuevo`, `/admin/productos/:id/editar`, `/admin/categorias`, `/admin/colecciones`, `/admin/tallas`, `/admin/inventario` |
| Admin operación | `/admin/ordenes`, `/admin/ordenes/:id`, `/admin/clientes`, `/admin/resenas`, `/admin/preguntas`, `/admin/pagos`, `/admin/reembolsos`, `/admin/facturas` |
| Admin repartidores y entregas | `/admin/repartidores`, `/admin/repartidores/envios` |
| Admin configuración | `/admin/envios/reglas`, `/admin/envios/metodos`, `/admin/descuentos/cantidad`, `/admin/descuentos/codigos`, `/admin/descuentos/codigos/usuarios-especificos`, `/admin/anuncios`, `/admin/sliders`, `/admin/configuracion`, `/admin/configuracion/general`, `/admin/administradores` |
| Admin informes | `/admin/informes`, `/admin/informes/ventas`, `/admin/informes/productos`, `/admin/informes/clientes` |
| Redirecciones | `/dashboard`, `/mis-pedidos`, `/notificaciones`, `/mis-direcciones`, `/mis-favoritos`, `/configuracion-cuenta`, `/favoritos` |
| App móvil de repartidor | Sin URL: bienvenida, autenticación, registro, revisión, entregas, detalle de ruta y cierre con código |

## Documentos relacionados

- [Arquitectura web](arquitectura-web-plantuml.md)
- [Diagramas de clases por microservicio](diagramas-clases-microservicios-plantuml.md)
- [Modelos relacionales por microservicio](modelos-relacionales-bases-datos-plantuml.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
- [Casos de uso](../referencias/casos-uso-angelow.md)
- [Historias de usuario](../referencias/historias-usuario-angelow.md)
- [Patrones de diseño aplicados](../patrones/README.md)
- [README de la app de repartidores](../../mobile/repartidor/README.md)
- [Casos de prueba de repartidores y entregas](../testing/casos-de-prueba/repartidores-entregas.md)
