# Pagos y Facturación — Verificación y administración de pagos (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Verificación y administración de pagos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir a administradores listar pagos y comprobantes" as uc_020
  usecase "permitir abrir el comprobante de pago en una ventana\nmodal administrativa" as uc_021
  usecase "permitir aprobar o rechazar un pago desde\nadministración" as uc_022
  usecase "permitir filtrar pagos administrativos por estado y\nbúsqueda" as uc_023
  usecase "permitir abrir una ventana modal para editar la\nconfiguración bancaria desde pagos" as uc_024
  usecase "sincronizar el estado de pago con la orden después de\naprobar o rechazar un comprobante" as uc_025
}

actor_1 --> uc_020
actor_1 --> uc_021
actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
uc_022 ..> uc_025 : <<include>>
@enduml
```



## Casos cubiertos

- El sistema debe permitir a administradores listar pagos y comprobantes.
- El sistema debe permitir abrir el comprobante de pago en una ventana modal administrativa.
- El sistema debe permitir aprobar o rechazar un pago desde administración.
- El sistema debe permitir filtrar pagos administrativos por estado y búsqueda.
- El sistema debe permitir abrir una ventana modal para editar la configuración bancaria desde pagos.
- El sistema debe sincronizar el estado de pago con la orden después de aprobar o rechazar un comprobante.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
