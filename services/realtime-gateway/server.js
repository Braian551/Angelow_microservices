const http = require('http')
const { WebSocketServer } = require('ws')
const { createClient } = require('redis')

const port = Number(process.env.PORT || 8080)
const redisUrl = String(process.env.REDIS_URL || 'redis://redis:6379').trim()
const baseChannel = String(process.env.STOCK_WS_CHANNEL || 'ws:orders:stock').trim() || 'ws:orders:stock'
const channelPattern = String(process.env.STOCK_WS_PATTERN || `${baseChannel}*`).trim() || `${baseChannel}*`

const server = http.createServer((request, response) => {
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

  response.writeHead(404, { 'Content-Type': 'application/json' })
  response.end(JSON.stringify({ ok: false, message: 'Not found' }))
})

const wss = new WebSocketServer({ server })

function broadcast(data) {
  const message = typeof data === 'string' ? data : JSON.stringify(data)

  for (const client of wss.clients) {
    if (client.readyState === client.OPEN) {
      client.send(message)
    }
  }
}

wss.on('connection', (socket) => {
  socket.send(JSON.stringify({
    type: 'connection.ready',
    channel_pattern: channelPattern,
    connected_at: new Date().toISOString(),
  }))

  socket.on('error', (error) => {
    console.error('[realtime-gateway] websocket error:', error.message)
  })
})

async function bootstrap() {
  const subscriber = createClient({ url: redisUrl })

  subscriber.on('error', (error) => {
    console.error('[realtime-gateway] redis error:', error.message)
  })

  await subscriber.connect()

  await subscriber.pSubscribe(channelPattern, (message, channel) => {
    let parsed = {
      event: 'stock.message.raw',
      payload: { raw: message },
      published_at: new Date().toISOString(),
    }

    try {
      parsed = JSON.parse(message)
    } catch {
      // Conserva el payload bruto para no perder eventos inválidos.
    }

    broadcast({
      ...parsed,
      channel,
      forwarded_at: new Date().toISOString(),
    })
  })

  server.listen(port, () => {
    console.log(`[realtime-gateway] listening on :${port} pattern=${channelPattern}`)
  })
}

bootstrap().catch((error) => {
  console.error('[realtime-gateway] bootstrap failed:', error)
  process.exit(1)
})
