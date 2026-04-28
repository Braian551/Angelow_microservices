# Diagramas de arquitectura web en PlantUML

Analisis realizado sobre microservicios, frontend y capas compartidas, excluyendo la carpeta `angelow/`.

## 1) Arquitectura de contenedores (Docker Compose)

```plantuml
@startuml
title Angelow - Arquitectura Web de Contenedores (Frontend + Microservicios)
left to right direction
skinparam componentStyle rectangle
skinparam packageStyle rectangle

actor "Cliente Web\nNavegador" as User
cloud "Firebase Auth\nGoogle Sign-In" as Firebase

database "MySQL de migracion\nhost.docker.internal:3306\nangelow" as Legacy

node "Docker Compose" {
  component "Frontend SPA\nVue 3 + Vite\n:5173" as Frontend

  package "Microservicios Laravel (API)" {
    component "auth-service\n:8001" as Auth
    component "catalog-service\n:8002" as Catalog
    component "cart-service\n:8003" as Cart
    component "order-service\n:8004" as Order
    component "payment-service\n:8005" as Payment
    component "discount-service\n:8006" as Discount
    component "shipping-service\n:8007" as Shipping
    component "notification-service\n:8008" as Notification
    component "audit-service\n:8009" as Audit
  }

  database "auth-db\nPostgreSQL 5433\nangelow_auth" as AuthDb
  database "catalog-db\nPostgreSQL 5434\nangelow_catalog" as CatalogDb
  database "cart-db\nPostgreSQL 5435\nangelow_cart" as CartDb
  database "order-db\nPostgreSQL 5436\nangelow_orders" as OrderDb
  database "payment-db\nPostgreSQL 5437\nangelow_payments" as PaymentDb
  database "discount-db\nPostgreSQL 5438\nangelow_discounts" as DiscountDb
  database "shipping-db\nPostgreSQL 5439\nangelow_shipping" as ShippingDb
  database "notification-db\nPostgreSQL 5440\nangelow_notifications" as NotificationDb
  database "audit-db\nPostgreSQL 5441\nangelow_audit" as AuditDb

  component "Redis\ncache + colas + locks\n:6379" as Redis
  component "order-worker\nqueue: orders" as OrderWorker
  component "order-scheduler\nschedule:work" as OrderScheduler
  component "notification-worker\nqueue: default" as NotificationWorker

  folder "Volumen compartido\n/uploads" as Uploads
}

User --> Frontend : Navegacion SPA
Frontend --> Auth
Frontend --> Catalog
Frontend --> Cart
Frontend --> Order
Frontend --> Payment
Frontend --> Discount
Frontend --> Shipping
Frontend --> Notification
Frontend --> Firebase : Login Google

Auth --> AuthDb
Catalog --> CatalogDb
Cart --> CartDb
Order --> OrderDb
Payment --> PaymentDb
Discount --> DiscountDb
Shipping --> ShippingDb
Notification --> NotificationDb
Audit --> AuditDb

Auth --> Uploads
Catalog --> Uploads
Cart --> Uploads
Order --> Uploads
Payment --> Uploads
Discount --> Uploads
Shipping --> Uploads
Notification --> Uploads
Audit --> Uploads
Frontend --> Uploads : lectura publica

Order --> Redis : reservas + pub/sub
Notification --> Redis : cola de notificaciones
OrderWorker --> Redis : consume queue orders
OrderScheduler --> Redis : tareas programadas
NotificationWorker --> Redis : consume queue default

Cart --> Catalog : /internal/products y /internal/variants
Order --> Catalog : /internal/variants y /internal/inventory/commit
Order --> Notification : eventos de pedido
Discount --> Notification : envio de campanas
Catalog --> Notification : anuncios home

Catalog --> Auth : perfiles internos + control admin
Order --> Auth : perfiles internos + control admin
Payment --> Auth : control admin
Discount --> Auth : control admin
Shipping --> Auth : control admin
Notification --> Auth : control admin

Catalog --> Legacy : fallback durante migracion
Order --> Legacy : fallback durante migracion
Payment --> Legacy : fallback durante migracion
Discount --> Legacy : fallback durante migracion
Shipping --> Legacy : fallback durante migracion
Notification --> Legacy : fallback durante migracion
@enduml
```

## 2) Frontend modular y capa API

```plantuml
@startuml
title Frontend SPA - Modulos y Capa de Integracion API
left to right direction
skinparam packageStyle rectangle
skinparam componentStyle rectangle

package "Frontend Vue 3 (src/modules)" {
  [auth] as MAuth
  [home + catalog] as MCatalog
  [cart] as MCart
  [checkout] as MCheckout
  [account] as MAccount
  [admin] as MAdmin
}

package "Capa de servicios (src/services)" {
  [authApi + authHttp] as CAuth
  [catalogApi + wishlistApi] as CCatalog
  [cartApi] as CCart
  [orderApi + invoiceApi] as COrder
  [paymentApi] as CPayment
  [discountApi] as CDiscount
  [shippingApi] as CShipping
  [notificationApi] as CNotification
  [http.js interceptors\nBearer + UTF-8] as HttpCore
}

cloud "Microservicios API" {
  [auth-service] as AAuth
  [catalog-service] as ACatalog
  [cart-service] as ACart
  [order-service] as AOrder
  [payment-service] as APayment
  [discount-service] as ADiscount
  [shipping-service] as AShipping
  [notification-service] as ANotification
}

MAuth --> CAuth
MCatalog --> CCatalog
MCart --> CCart

MCheckout --> CShipping
MCheckout --> CDiscount
MCheckout --> COrder
MCheckout --> CPayment

MAccount --> COrder
MAccount --> CShipping
MAccount --> CNotification
MAccount --> CCatalog

MAdmin --> CAuth
MAdmin --> CCatalog
MAdmin --> COrder
MAdmin --> CPayment
MAdmin --> CDiscount
MAdmin --> CShipping
MAdmin --> CNotification

CAuth --> HttpCore
CCatalog --> HttpCore
CCart --> HttpCore
COrder --> HttpCore
CPayment --> HttpCore
CDiscount --> HttpCore
CShipping --> HttpCore
CNotification --> HttpCore

HttpCore --> AAuth
HttpCore --> ACatalog
HttpCore --> ACart
HttpCore --> AOrder
HttpCore --> APayment
HttpCore --> ADiscount
HttpCore --> AShipping
HttpCore --> ANotification

note bottom of HttpCore
- Inyecta token Bearer desde localStorage
- Normaliza texto UTF-8 en responses
- Maneja FormData sin forzar Content-Type
end note
@enduml
```

## 3) Dependencias entre microservicios

```plantuml
@startuml
title Dependencias internas entre microservicios (runtime)
left to right direction
skinparam componentStyle rectangle

component "auth-service" as Auth
component "catalog-service" as Catalog
component "cart-service" as Cart
component "order-service" as Order
component "payment-service" as Payment
component "discount-service" as Discount
component "shipping-service" as Shipping
component "notification-service" as Notification
component "audit-service" as Audit
component "Redis" as Redis

Cart --> Catalog : consulta productos/variantes
Catalog --> Notification : anuncios home

Order --> Catalog : reserva y confirmacion de inventario
Order --> Notification : notificaciones de pedido
Order --> Redis : locks, reservas y pub/sub

Discount --> Notification : campanas de codigos
Notification --> Redis : cola default

Catalog --> Auth : perfiles + control admin
Order --> Auth : perfiles + control admin
Payment --> Auth : control admin
Discount --> Auth : control admin
Shipping --> Auth : control admin
Notification --> Auth : control admin

note right of Audit
Servicio de trazabilidad (/api/audits/*).
No se detecta consumo directo desde el frontend actual.
end note
@enduml
```

## 4) Secuencia checkout, pago y confirmacion

```plantuml
@startuml
title Secuencia web - Checkout, pago y confirmacion de orden
autonumber

actor "Cliente" as Cliente
actor "Administrador" as Admin
participant "Frontend SPA" as FE
participant "cart-service" as Cart
participant "shipping-service" as Shipping
participant "discount-service" as Discount
participant "order-service" as Order
participant "catalog-service" as Catalog
participant "payment-service" as Payment
participant "notification-service" as Notification
participant "Redis" as Redis
participant "order-worker/scheduler" as OrderWorker

Cliente -> FE : Inicia checkout
FE -> Cart : GET /cart (user_id o session_id)
Cart -> Catalog : GET /internal/products/{id}
Cart -> Catalog : GET /internal/variants/{id}
Cart --> FE : carrito con detalle de productos

FE -> Shipping : POST /shipping/estimate
Shipping --> FE : costo y metodo de envio

FE -> Discount : POST /discounts/validate
Discount --> FE : aplica/no aplica descuento

Cliente -> FE : Confirmar compra
FE -> Order : POST /orders
Order -> Catalog : GET /internal/variants/{id}
Order -> Redis : crea reserva de stock temporal
Order --> FE : orden creada (pendiente)

Cliente -> FE : Subir comprobante
FE -> Payment : POST /payments
Payment --> FE : pago registrado

Admin -> FE : Validar pago
FE -> Payment : PATCH /admin/payments/{id}
FE -> Order : PATCH /orders/{id}/payment-status
Order -> Catalog : POST /internal/inventory/commit
Order -> Notification : POST /notifications
Notification -> Redis : encola despacho
Order --> FE : orden confirmada

OrderWorker -> Redis : procesa expiraciones/reconciliacion
OrderWorker -> Order : expira reservas vencidas
@enduml
```
