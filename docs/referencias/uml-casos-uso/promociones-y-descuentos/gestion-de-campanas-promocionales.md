# Promociones y Descuentos — Gestión de campañas promocionales

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Gestión de campañas promocionales
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir consultar clientes elegibles para campañas de\ndescuento" as uc_012
  usecase "permitir enviar campañas masivas con códigos de\ndescuento" as uc_013
  usecase "permitir enviar códigos a usuarios específicos" as uc_014
  usecase "permitir seleccionar todos los clientes filtrados para\nuna campaña específica" as uc_015
  usecase "permitir limpiar la selección de clientes de una\ncampaña específica" as uc_016
  usecase "permitir seleccionar si una campaña específica se\nenvía por notificación del sistema, correo o ambos" as uc_017
}

actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_015
actor_1 --> uc_016
actor_1 --> uc_017
@enduml
```



## Casos cubiertos

- El sistema debe permitir consultar clientes elegibles para campañas de descuento.
- El sistema debe permitir enviar campañas masivas con códigos de descuento.
- El sistema debe permitir enviar códigos a usuarios específicos.
- El sistema debe permitir seleccionar todos los clientes filtrados para una campaña específica.
- El sistema debe permitir limpiar la selección de clientes de una campaña específica.
- El sistema debe permitir seleccionar si una campaña específica se envía por notificación del sistema, correo o ambos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
