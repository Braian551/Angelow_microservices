# realtime-gateway - Persistencia relacional

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

`realtime-gateway` no posee base de datos relacional propia. El servicio mantiene conexiones WebSocket y reenvía eventos recibidos desde Redis hacia clientes conectados, por lo que se documenta como módulo sin tablas.

## Diagrama

```plantuml
@startuml
title realtime-gateway - Sin modelo relacional propio
left to right direction
skinparam componentStyle rectangle
skinparam shadowing false

component "realtime-gateway" as gateway {
  [Servidor HTTP /health]
  [Servidor WebSocket]
  [Difusión a clientes]
}

database "Redis\ncanales ws:orders:stock*" as redis
actor "Cliente SPA" as client

redis --> gateway : eventos publicados
gateway --> client : mensajes WebSocket

note bottom of gateway
No crea tablas ni almacena datos persistentes.
La persistencia de negocio queda en los microservicios dueños.
end note
@enduml
```

## Fuentes revisadas

- `services/realtime-gateway/server.js`
- `services/realtime-gateway/package.json`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
