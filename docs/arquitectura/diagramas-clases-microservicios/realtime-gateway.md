# realtime-gateway - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio Node que reenvía eventos de stock publicados en Redis hacia clientes WebSocket. No contiene clases PHP ni tablas de dominio; se representa como módulo runtime con atributos de configuración, conexiones y relaciones de agregación.

## Diagrama

```plantuml
@startuml
title realtime-gateway - WebSocket de stock e inventario
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Runtime Node" {
  class "server.js" as RealtimeServerModule <<Runtime>> {
    -port: Integer
    -redisUrl: String
    -baseChannel: String
    -channelPattern: String
    -server: HTTP server
    -wss: WebSocketServer
    +broadcast(data)
    +bootstrap()
  }
}

package "Infraestructura interna" {
  class "HTTP server" as RealtimeHttpServer <<Infra>> {
    +healthPath: String
    +notFoundResponse: JSON
    +GET /health
    +404 JSON
  }
  class "WebSocketServer" as RealtimeWebSocketServer <<Gateway>> {
    +clients: Set<WebSocket>
    +connection(socket)
    +send(connection.ready)
  }
  class "Redis subscriber" as RealtimeRedisSubscriber <<Client>> {
    -url: String
    -channelPattern: String
    +connect()
    +pSubscribe(channelPattern)
  }
  class "WebSocket client" as RealtimeClient <<Runtime>> {
    +readyState: Integer
    +send(message)
  }
}

package "Configuración" {
  class "PORT" as RealtimePort <<Env>> {
    +value: Integer
  }
  class "REDIS_URL" as RealtimeRedisUrl <<Env>> {
    +value: String
  }
  class "STOCK_WS_CHANNEL" as RealtimeBaseChannel <<Env>> {
    +value: String
  }
  class "STOCK_WS_PATTERN" as RealtimeChannelPattern <<Env>> {
    +value: String
  }
}

package "Servicios externos" {
  class "Redis canales ws:orders:stock*" as RealtimeRedisChannels <<External>>
  class "Clientes WebSocket" as RealtimeClients <<External>>
}

RealtimeServerModule *-- "1" RealtimeHttpServer : levanta
RealtimeServerModule *-- "1" RealtimeWebSocketServer : levanta
RealtimeServerModule *-- "1" RealtimeRedisSubscriber : suscribe
RealtimeServerModule --> RealtimePort
RealtimeServerModule --> RealtimeRedisUrl
RealtimeServerModule --> RealtimeBaseChannel
RealtimeServerModule --> RealtimeChannelPattern

RealtimeWebSocketServer "1" o-- "0..*" RealtimeClient : conexiones
RealtimeRedisSubscriber "1" --> "1..*" RealtimeRedisChannels : escucha patrón
RealtimeRedisSubscriber --> RealtimeServerModule : mensaje parseado
RealtimeServerModule --> RealtimeWebSocketServer : broadcast()
RealtimeWebSocketServer ..> RealtimeClients : eventos JSON
@enduml
```

## Fuentes revisadas

- `services/realtime-gateway/server.js`
- `services/realtime-gateway/package.json`
- `services/realtime-gateway/Dockerfile`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
