import { subscribeToStockRealtime } from './useStockRealtime'

const ORDER_REALTIME_EVENTS = new Set([
  'order.status.updated',
  'order.payment_status.updated',
  'order.updated',
  'order.stock_reservation.auto_cancelled',
])

function normalizeRealtimeOrderMessage(message = {}) {
  const payload = message?.raw?.payload && typeof message.raw.payload === 'object'
    ? message.raw.payload
    : {}
  const event = String(message?.event || message?.raw?.event || '').trim()
  const orderId = Number(message?.orderId || payload.order_id || payload.id || 0)

  if (!ORDER_REALTIME_EVENTS.has(event) || !Number.isFinite(orderId) || orderId <= 0) {
    return null
  }

  return {
    event,
    orderId,
    source: String(payload.source || '').trim() || null,
    field: String(payload.field || '').trim() || null,
    status: String(payload.status || payload.new_status || '').trim() || null,
    paymentStatus: String(payload.payment_status || payload.new_payment_status || '').trim() || null,
    oldValue: String(payload.old_value || '').trim() || null,
    newValue: String(payload.new_value || '').trim() || null,
    publishedAt: message.publishedAt || payload.published_at || null,
    raw: message.raw || message,
  }
}

export function subscribeToOrderRealtime(listener) {
  if (typeof listener !== 'function') {
    return () => {}
  }

  return subscribeToStockRealtime((message) => {
    // Reutiliza el websocket de stock porque el gateway ya publica todos los eventos del canal de pedidos.
    const orderMessage = normalizeRealtimeOrderMessage(message)
    if (orderMessage) {
      listener(orderMessage)
    }
  })
}
