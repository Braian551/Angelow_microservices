# Gestión de Usuarios y Acceso — Administración de clientes

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Administración de clientes
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir a los administradores listar clientes\nregistrados con búsqueda y filtros" as uc_050
  usecase "permitir editar desde el listado los datos de\ncontacto, el estado y el rol de un cliente" as uc_051
  usecase "permitir bloquear o desbloquear clientes desde el\npanel" as uc_052
  usecase "mostrar métricas de clientes registrados, activos,\nbloqueados y con compras" as uc_053
  usecase "permitir abrir el detalle de un cliente en una ventana\nmodal" as uc_054
  usecase "permitir exportar clientes a PDF o Excel" as uc_055
}

actor_1 --> uc_050
actor_1 --> uc_051
actor_1 --> uc_052
actor_1 --> uc_053
actor_1 --> uc_054
actor_1 --> uc_055
@enduml
```



## Casos cubiertos

- El sistema debe permitir a los administradores listar clientes registrados con búsqueda y filtros.
- El sistema debe permitir editar desde el listado los datos de contacto, el estado y el rol de un cliente.
- El sistema debe permitir bloquear o desbloquear clientes desde el panel.
- El sistema debe mostrar métricas de clientes registrados, activos, bloqueados y con compras.
- El sistema debe permitir abrir el detalle de un cliente en una ventana modal.
- El sistema debe permitir exportar clientes a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
