// Importa la función para suscribirse al canal de tiempo real de stock.
// Se reutiliza este canal porque el gateway publica todos los eventos de pedidos en el mismo.
import { subscribeToStockRealtime } from './useStockRealtime'

// Conjunto de eventos válidos relacionados con pedidos que se procesarán en tiempo real.
// Se usa Set para búsquedas O(1) y evitar duplicados.
const ORDER_REALTIME_EVENTS = new Set([
  'order.status.updated',          // Cambio de estado general del pedido
  'order.payment_status.updated',  // Cambio de estado de pago
  'order.payment.updated',         // Actualización de datos de pago
  'order.refund.updated',          // Actualización de reembolso
  'order.updated',                 // Actualización genérica del pedido
  'order.stock_reservation.auto_cancelled', // Cancelación automática de reserva de stock
])

// Extrae el primer valor válido de un payload buscando en múltiples claves posibles.
// Útil porque diferentes proveedores de webhook usan nombres de campo distintos para el mismo dato.
// Parámetros:
//   payload - Objeto con los datos del mensaje (opcional, por defecto {})
//   keys    - Array de claves a buscar en orden de prioridad
// Retorna el primer valor encontrado como string limpio, o null si ninguno es válido.
function pickRealtimeValue(payload = {}, keys = []) {
  // Itera sobre cada clave en orden de prioridad
  for (const key of keys) {
    const value = payload[key]
    // Verifica que el valor exista, no sea nulo y no esté vacío después de recortar espacios
    if (value !== undefined && value !== null && String(value).trim() !== '') {
      return String(value).trim()
    }
  }

  // Si ningún valor cumple las condiciones, retorna null
  return null
}

// Normaliza un mensaje raw del WebSocket de pedidos a un formato estándar.
// Esta función centraliza la lógica de extracción de datos porque los mensajes
// pueden venir con diferentes estructuras según el evento y el proveedor.
// Parámetros:
//   message - Mensaje crudo recibido del WebSocket (opcional, por defecto {})
// Retorna un objeto normalizado o null si el mensaje no es relevante.
function normalizeRealtimeOrderMessage(message = {}) {
  // Extrae el payload del mensaje, verificando que sea un objeto válido
  const payload = message?.raw?.payload && typeof message.raw.payload === 'object'
    ? message.raw.payload
    : {}

  // Normaliza el nombre del evento, proviniendo de diferentes ubicaciones del mensaje
  const event = String(message?.event || message?.raw?.event || '').trim()

  // Extrae el ID del pedido, intentando múltiples campos posibles
  const orderId = Number(message?.orderId || payload.order_id || payload.orderId || payload.id || 0)

  // Si el evento no está en la lista de eventos válidos o el ID no es válido, descarta el mensaje
  if (!ORDER_REALTIME_EVENTS.has(event) || !Number.isFinite(orderId) || orderId <= 0) {
    return null
  }

  // Construye y retorna el objeto normalizado con todos los campos relevantes
  return {
    event,      // Nombre del evento que disparó el mensaje
    orderId,    // ID numérico del pedido afectado
    source: String(payload.source || '').trim() || null,   // Fuente del evento (ej: "webhook", "sistema")
    field: pickRealtimeValue(payload, ['field', 'field_changed', 'type']),  // Campo específico que cambió (si aplica)
    status: pickRealtimeValue(payload, ['status', 'new_status', 'order_status', 'newOrderStatus']),  // Nuevo estado del pedido
    paymentStatus: pickRealtimeValue(payload, ['payment_status', 'new_payment_status', 'paymentStatus', 'newPaymentStatus']),  // Nuevo estado de pago
    refundStatus: pickRealtimeValue(payload, ['refund_status', 'new_refund_status', 'refundStatus', 'newRefundStatus']),  // Nuevo estado de reembolso
    oldValue: pickRealtimeValue(payload, ['old_value', 'oldValue', 'old_status', 'old_payment_status']),  // Valor anterior del campo (para tracking de cambios)
    newValue: pickRealtimeValue(payload, ['new_value', 'newValue', 'new_status', 'new_payment_status']),  // Valor nuevo del campo
    publishedAt: message.publishedAt || payload.published_at || null,  // Timestamp de cuándo se publicó el evento
    raw: message.raw || message,  // Mensaje original sin procesar (para debugging)
  }
}

// Función exportada que permite suscribirse a eventos de pedidos en tiempo real.
// Retorna una función de limpieza para cancelar la suscripción.
// Parámetros:
//   listener - Función callback que se ejecutará con cada mensaje normalizado
export function subscribeToOrderRealtime(listener) {
  // Validación: si el listener no es función, retorna función vacía para evitar errores
  if (typeof listener !== 'function') {
    return () => {}
  }

  // Se suscribe al canal de stock (reutilizado para pedidos) y filtra solo eventos de pedidos
  return subscribeToStockRealtime((message) => {
    // Normaliza el mensaje crudo a un formato estándar para pedidos
    const orderMessage = normalizeRealtimeOrderMessage(message)
    // Solo notifica al listener si el mensaje es un evento de pedido válido
    if (orderMessage) {
      listener(orderMessage)
    }
  })
}
