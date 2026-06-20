import { onMounted, onUnmounted, readonly, ref } from 'vue'

const listeners = new Set()
const connectionStatus = ref('idle')
const lastMessageAt = ref(null)

let socket = null
let reconnectTimerId = null
let manualDisconnect = false

const RECONNECT_DELAY_MS = 2500

function resolveStockRealtimeUrl() {
  const configuredUrl = String(import.meta.env.VITE_STOCK_WS_URL || '').trim()
  if (configuredUrl) {
    return configuredUrl
  }

  if (typeof window === 'undefined') {
    return 'ws://localhost:8090'
  }

  const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
  return `${protocol}//${window.location.hostname}:8090`
}

function normalizeRealtimeItem(item = {}) {
  const variantId = Number(item?.size_variant_id || item?.variant_id || 0)
  if (!Number.isFinite(variantId) || variantId <= 0) {
    return null
  }

  return {
    ...item,
    size_variant_id: variantId,
    available_stock: item?.available_stock ?? item?.stock_after ?? item?.stock ?? null,
    database_stock: item?.database_stock ?? null,
    reserved_stock: item?.reserved_stock ?? null,
  }
}

function normalizeRealtimeMessage(rawMessage) {
  let parsed = null

  try {
    parsed = JSON.parse(rawMessage)
  } catch {
    return null
  }

  if (!parsed || typeof parsed !== 'object') {
    return null
  }

  const payload = parsed.payload && typeof parsed.payload === 'object'
    ? parsed.payload
    : {}

  const items = Array.isArray(payload.items)
    ? payload.items.map(normalizeRealtimeItem).filter(Boolean)
    : []

  return {
    event: String(parsed.event || '').trim(),
    channel: String(parsed.channel || '').trim(),
    source: String(payload.source || '').trim(),
    historyUpdated: Boolean(payload.history_updated),
    orderId: Number(payload.order_id || 0) || null,
    strictReservation: Boolean(payload.strict_reservation),
    items,
    variantIds: items.map((item) => item.size_variant_id),
    publishedAt: parsed.published_at || null,
    forwardedAt: parsed.forwarded_at || null,
    raw: parsed,
  }
}

function notifyListeners(message) {
  listeners.forEach((listener) => {
    try {
      listener(message)
    } catch (error) {
      console.error('[stock-realtime] listener error', error)
    }
  })
}

function clearReconnectTimer() {
  if (reconnectTimerId) {
    window.clearTimeout(reconnectTimerId)
    reconnectTimerId = null
  }
}

function scheduleReconnect() {
  if (typeof window === 'undefined' || reconnectTimerId || manualDisconnect || listeners.size === 0) {
    return
  }

  reconnectTimerId = window.setTimeout(() => {
    reconnectTimerId = null
    connectSocket()
  }, RECONNECT_DELAY_MS)
}

function closeSocket() {
  clearReconnectTimer()
  manualDisconnect = true

  if (socket) {
    socket.close()
    socket = null
  }

  connectionStatus.value = 'idle'
}

function connectSocket() {
  if (typeof window === 'undefined') {
    return
  }

  if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
    return
  }

  manualDisconnect = false
  connectionStatus.value = 'connecting'

  const nextSocket = new WebSocket(resolveStockRealtimeUrl())
  socket = nextSocket

  nextSocket.addEventListener('open', () => {
    if (socket !== nextSocket) return
    connectionStatus.value = 'open'
  })

  nextSocket.addEventListener('message', (event) => {
    if (socket !== nextSocket) return

    const message = normalizeRealtimeMessage(event.data)
    if (!message?.event) {
      return
    }

    lastMessageAt.value = message.publishedAt || new Date().toISOString()
    notifyListeners(message)
  })

  nextSocket.addEventListener('error', () => {
    if (socket !== nextSocket) return
    connectionStatus.value = 'error'
  })

  nextSocket.addEventListener('close', () => {
    if (socket !== nextSocket) return

    socket = null
    connectionStatus.value = manualDisconnect ? 'idle' : 'closed'
    scheduleReconnect()
  })
}

export function subscribeToStockRealtime(listener) {
  if (typeof listener !== 'function') {
    return () => {}
  }

  listeners.add(listener)
  connectSocket()

  return () => {
    listeners.delete(listener)

    if (listeners.size === 0) {
      closeSocket()
    }
  }
}

export function useStockRealtime(listener) {
  let unsubscribe = null

  onMounted(() => {
    if (typeof listener === 'function') {
      unsubscribe = subscribeToStockRealtime(listener)
      return
    }

    connectSocket()
  })

  onUnmounted(() => {
    unsubscribe?.()
  })

  return {
    connectionStatus: readonly(connectionStatus),
    lastMessageAt: readonly(lastMessageAt),
  }
}
