// Servicio de gateway WebSocket para actualizaciones en tiempo real
// Maneja conexiones de clientes WebSocket y suscribe canales de Redis
// para difundir actualizaciones de stock e inventario a todos los clientes conectados

const http = require('http')
const { WebSocketServer } = require('ws')
const { createClient } = require('redis')

// Configuración del puerto desde variable de entorno o valor por defecto
const port = Number(process.env.PORT || 8080)
// URL de conexión a Redis desde variable de entorno o valor por defecto
const redisUrl = String(process.env.REDIS_URL || 'redis://redis:6379').trim()
// Nombre base del canal WebSocket para stock desde variable de entorno
const baseChannel = String(process.env.STOCK_WS_CHANNEL || 'ws:orders:stock').trim() || 'ws:orders:stock'
// Patrón de canales para suscripción (permite múltiplos canales con wildcard)
const channelPattern = String(process.env.STOCK_WS_PATTERN || `${baseChannel}*`).trim() || `${baseChannel}*`

// Crear servidor HTTP básico para health checks y manejo de peticiones
const server = http.createServer((request, response) => {
  // Endpoint de salud para monitoreo del servicio (usado por Docker/Kubernetes)
  if (request.url === '/health') {
    response.writeHead(200, { 'Content-Type': 'application/json' })
    response.end(JSON.stringify({
      ok: true,
      service: 'realtime-gateway',
      channelPattern,
      clients: wss.clients.size,
    }))
    return
  }

  // Respuesta 404 para cualquier otra ruta no reconocida
  response.writeHead(404, { 'Content-Type': 'application/json' })
  response.end(JSON.stringify({ ok: false, message: 'Not found' }))
})

// Crear servidor WebSocket sobre el mismo servidor HTTP
const wss = new WebSocketServer({ server })

/**
 * Función para difundir un mensaje a todos los clientes WebSocket conectados
 * @param {any} data - Dato a enviar (se convierte a JSON si es objeto)
 */
function broadcast(data) {
  // Convertir el dato a string JSON si no lo es ya
  const message = typeof data === 'string' ? data : JSON.stringify(data)

  // Iterar sobre todos los clientes conectados y enviar el mensaje
  // Solo enviar si la conexión está en estado OPEN (lista para recibir)
  for (const client of wss.clients) {
    if (client.readyState === client.OPEN) {
      client.send(message)
    }
  }
}

// Evento: Nuevo cliente WebSocket se conecta al servidor
wss.on('connection', (socket) => {
  // Enviar mensaje de bienvenida al cliente recién conectado
  socket.send(JSON.stringify({
    type: 'connection.ready',
    channel_pattern: channelPattern,
    connected_at: new Date().toISOString(),
  }))

  // Manejar errores de conexión WebSocket
  socket.on('error', (error) => {
    console.error('[realtime-gateway] websocket error:', error.message)
  })
})

/**
 * Función principal de inicialización del servicio
 * Conecta a Redis, se suscribe a canales de stock y empieza a escuchar conexiones
 */
async function bootstrap() {
  // Crear cliente Redis suscrito (solo para recibir mensajes)
  const subscriber = createClient({ url: redisUrl })

  // Manejar errores de conexión a Redis
  subscriber.on('error', (error) => {
    console.error('[realtime-gateway] redis error:', error.message)
  })

  // Establecer conexión con el servidor Redis
  await subscriber.connect()

  // Suscribirse al patrón de canales especificado (ej: ws:orders:stock*)
  // Cada vez que llegue un mensaje a cualquiera de estos canales, ejecutar callback
  await subscriber.pSubscribe(channelPattern, (message, channel) => {
    let parsed = {
      event: 'stock.message.raw',
      payload: { raw: message },
      published_at: new Date().toISOString(),
    }

    try {
      // Intentar parsear el mensaje como JSON (formato esperado)
      parsed = JSON.parse(message)
    } catch {
      // Si falla el parsing, conservar el payload bruto para no perder eventos
      // Esto permite manejar tanto mensajes JSON estructurados como texto plano
    }

    // Difundir el mensaje (parseado o bruto) a todos los clientes WebSocket conectados
    // Incluir metadatos como el canal original y timestamp de reenvío
    broadcast({
      ...parsed,
      channel,
      forwarded_at: new Date().toISOString(),
    })
  })

  // Iniciar el servidor HTTP/WebSocket en el puerto configurado
  server.listen(port, () => {
    console.log(`[realtime-gateway] listening on :${port} pattern=${channelPattern}`)
  })
}

// Ejecutar la función de bootstrap y manejar errores críticos
bootstrap().catch((error) => {
  console.error('[realtime-gateway] bootstrap failed:', error)
  // Salir con código de error si no se puede iniciar el servicio correctamente
  process.exit(1)
})
