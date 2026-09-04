# Pagos y Facturación — Administración de facturas (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Administración de facturas (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar facturas desde el panel administrativo" as uc_011
  usecase "permitir descargar una factura en PDF" as uc_012
  usecase "permitir reenviar una factura al cliente por correo" as uc_013
  usecase "permitir filtrar facturas por búsqueda, estado, pago y\nfechas" as uc_014
  usecase "permitir limpiar filtros de facturas" as uc_015
  usecase "permitir recargar manualmente el listado de facturas" as uc_016
}

actor_1 --> uc_011
actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_015
actor_1 --> uc_016
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar facturas desde el panel administrativo.
- El sistema debe permitir descargar una factura en PDF.
- El sistema debe permitir reenviar una factura al cliente por correo.
- El sistema debe permitir filtrar facturas por búsqueda, estado, pago y fechas.
- El sistema debe permitir limpiar filtros de facturas.
- El sistema debe permitir recargar manualmente el listado de facturas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
