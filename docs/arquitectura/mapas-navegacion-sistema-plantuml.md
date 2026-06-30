# Mapas de navegación del sistema en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Fuente de verdad](#fuente-de-verdad)
- [1) Mapa principal de rutas SPA](#1-mapa-principal-de-rutas-spa)
- [2) Mapa de navegación por dominios funcionales](#2-mapa-de-navegación-por-dominios-funcionales)
- [3) Flujo de protección del router](#3-flujo-de-protección-del-router)
- [4) Mapa administrativo por dominio](#4-mapa-administrativo-por-dominio)
- [Rutas cubiertas](#rutas-cubiertas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Objetivo

Este documento describe la navegación SPA vigente de Angelow y su relación con los microservicios que atienden cada dominio funcional. El mapa está pensado como una guía de arquitectura para entender qué rutas existen, qué actor las usa y qué reglas de acceso aplica el router.

## Fuente de verdad

La fuente principal para este mapa es `frontend/src/router/index.js`. Las rutas públicas, rutas de cuenta, rutas administrativas, redirecciones y reglas de guard se derivan de ese archivo para evitar divergencias entre la documentación y la SPA.

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

  package "Envíos, descuentos y contenido" {
    [/admin/envios/reglas] as admin_shipping_rules
    [/admin/envios/metodos] as admin_shipping_methods
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

package "Frontend (rutas)" {
  [Home, tienda, producto y colecciones] as nav_catalog
  [Carrito] as nav_cart
  [Checkout] as nav_checkout
  [Autenticación y perfil] as nav_auth
  [Mi cuenta] as nav_account
  [Legal público] as nav_legal
  [Panel administrativo] as nav_admin
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
nav_account --> svc_shipping : direcciones del usuario
nav_account --> svc_notification : notificaciones y preferencias
nav_account --> svc_catalog : favoritos y productos
nav_account --> svc_auth : perfil, contraseña y sesión

nav_admin --> svc_auth : clientes y administradores
nav_admin --> svc_catalog : productos, categorías, colecciones, tallas, inventario, sliders, reseñas y preguntas
nav_admin --> svc_order : órdenes, facturas, reembolsos e informes
nav_admin --> svc_payment : pagos y cuenta bancaria
nav_admin --> svc_discount : códigos, campañas y reglas por cantidad
nav_admin --> svc_shipping : métodos y reglas de envío
nav_admin --> svc_notification : anuncios, avisos y preferencias
nav_admin --> svc_audit : trazabilidad operativa
nav_admin --> svc_realtime : eventos de stock y progreso

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

dashboard --> informes
informes --> informes_ventas
informes --> informes_productos
informes --> informes_clientes
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
| Admin configuración | `/admin/envios/reglas`, `/admin/envios/metodos`, `/admin/descuentos/cantidad`, `/admin/descuentos/codigos`, `/admin/descuentos/codigos/usuarios-especificos`, `/admin/anuncios`, `/admin/sliders`, `/admin/configuracion`, `/admin/configuracion/general`, `/admin/administradores` |
| Admin informes | `/admin/informes`, `/admin/informes/ventas`, `/admin/informes/productos`, `/admin/informes/clientes` |
| Redirecciones | `/dashboard`, `/mis-pedidos`, `/notificaciones`, `/mis-direcciones`, `/mis-favoritos`, `/configuracion-cuenta`, `/favoritos` |

## Documentos relacionados

- [Arquitectura web](arquitectura-web-plantuml.md)
- [Diagramas de clases por microservicio](diagramas-clases-microservicios-plantuml.md)
- [Modelos relacionales por microservicio](modelos-relacionales-bases-datos-plantuml.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
- [Casos de uso](../referencias/casos-uso-angelow.md)
- [Historias de usuario](../referencias/historias-usuario-angelow.md)
- [Patrones de diseño aplicados](../patrones/README.md)
