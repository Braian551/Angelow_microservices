// Importaciones de Vue: ciclo de vida, solo lectura y referencias reactivas
import { onMounted, onUnmounted, readonly, ref } from 'vue'

// Conjunto global de funciones listener que recibirán actualizaciones de stock en tiempo real
// Se usa Set para evitar duplicados y permitir agregar/eliminar suscriptores eficientemente
const listeners = new Set()

// Estado de la conexión WebSocket: 'idle' | 'connecting' | 'open' | 'error' | 'closed'
// Es reactivo para que los componentes puedan observar el estado de la conexión
const connectionStatus = ref('idle')

// Timestamp del último mensaje recibido del servidor, útil para diagnóstico y UI
const lastMessageAt = ref(null)

// Instancia global del WebSocket compartida entre todos los suscriptores
let socket = null

// ID del temporizador para reconexión automática, permite cancelar si es necesario
let reconnectTimerId = null

// Bandera que indica si el usuario cerró la conexión manualmente (no debe reconectar)
let manualDisconnect = false

// Tiempo de espera en milisegundos antes de intentar reconectar tras desconexión
const RECONNECT_DELAY_MS = 2500

// Resuelve la URL del servidor WebSocket para el canal de stock en tiempo real
// Prioriza la variable de entorno, luego construye URL basada en el host actual
function resolveStockRealtimeUrl() {
  // Intenta obtener URL configurada en variables de entorno de Vite
  const configuredUrl = String(import.meta.env.VITE_STOCK_WS_URL || '').trim()
  if (configuredUrl) {
    return configuredUrl
  }

  // Si estamos en servidor (SSR), usa localhost como fallback
  if (typeof window === 'undefined') {
    return 'ws://localhost:8090'
  }

  // En el navegador, usa el mismo protocolo (ws/wss) y hostname que la página
  const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
  return `${protocol}//${window.location.hostname}:8090`
}

// Normaliza un ítem de stock individual recibido del servidor
// Estandariza los nombres de campos y asegura que el variant_id sea válido
function normalizeRealtimeItem(item = {}) {
  // Extrae y valida el ID de variante, aceptando ambos nombres posibles del campo
  const variantId = Number(item?.size_variant_id || item?.variant_id || 0)
  // Si no hay un ID válido, descarta el ítem
  if (!Number.isFinite(variantId) || variantId <= 0) {
    return null
  }

  // Retorna el ítem normalizado con nombres de campo consistentes
  return {
    ...item,
    size_variant_id: variantId,
    // El campo de stock disponible puede venir con diferentes nombres según la API
    available_stock: item?.available_stock ?? item?.stock_after ?? item?.stock ?? null,
    // Stock en base de datos (excluyendo reservas)
    database_stock: item?.database_stock ?? null,
    // Stock reservado por pedidos pendientes
    reserved_stock: item?.reserved_stock ?? null,
  }
}

// Convierte un mensaje JSON crudo del WebSocket en un objeto normalizado
// Maneja errores de parseo y extrae la información relevante para el frontend
function normalizeRealtimeMessage(rawMessage) {
  let parsed = null

  // Intenta parsear el mensaje JSON, si falla retorna null
  try {
    parsed = JSON.parse(rawMessage)
  } catch {
    return null
  }

  // Valida que el parseo haya devuelto un objeto válido
  if (!parsed || typeof parsed !== 'object') {
    return null
  }

  // Extrae el payload del mensaje, con fallback a objeto vacío
  const payload = parsed.payload && typeof parsed.payload === 'object'
    ? parsed.payload
    : {}

  // Normaliza cada ítem de stock del array y filtra los inválidos (null)
  const items = Array.isArray(payload.items)
    ? payload.items.map(normalizeRealtimeItem).filter(Boolean)
    : []

  // Construye el objeto de mensaje normalizado con todos los campos necesarios
  return {
    event: String(parsed.event || '').trim(),
    channel: String(parsed.channel || '').trim(),
    source: String(payload.source || '').trim(),
    // Indica si el historial de movimientos fue actualizado
    historyUpdated: Boolean(payload.history_updated),
    // ID del pedido que provocó el cambio de stock (si existe)
    orderId: Number(payload.order_id || 0) || null,
    // Indica si se aplicó reserva estricta (bloqueo total del stock)
    strictReservation: Boolean(payload.strict_reservation),
    // Lista de ítems normalizados con información de stock
    items,
    // Array de IDs de variantes afectadas, útil para filtrar rápidamente
    variantIds: items.map((item) => item.size_variant_id),
    // Timestamp de cuándo se publicó el evento en el servidor
    publishedAt: parsed.published_at || null,
    // Timestamp de cuándo se reenvió el mensaje
    forwardedAt: parsed.forwarded_at || null,
    // Mensaje original sin procesar, para debugging
    raw: parsed,
  }
}

// Notifica a todos los suscriptores registrados sobre un nuevo mensaje de stock
// Captura errores individualmente para que un listener fallido no afecte a los demás
function notifyListeners(message) {
  listeners.forEach((listener) => {
    try {
      listener(message)
    } catch (error) {
      console.error('[stock-realtime] listener error', error)
    }
  })
}

// Limpia el temporizador de reconexión programada si existe
// Evita múltiples intentos de reconexión simultáneos
function clearReconnectTimer() {
  if (reconnectTimerId) {
    window.clearTimeout(reconnectTimerId)
    reconnectTimerId = null
  }
}

// Programa un intento de reconexión automática después de un delay
// No reconecta si: estamos en servidor, ya hay un timer activo, fue desconexión manual,
// o no hay suscriptores activos (no hay interés en la conexión)
function scheduleReconnect() {
  if (typeof window === 'undefined' || reconnectTimerId || manualDisconnect || listeners.size === 0) {
    return
  }

  // Configura el temporizador para reconectar después del delay establecido
  reconnectTimerId = window.setTimeout(() => {
    reconnectTimerId = null
    connectSocket()
  }, RECONNECT_DELAY_MS)
}

// Cierra la conexión WebSocket de forma controlada
// Marcada como desconexión manual para evitar reconexión automática
function closeSocket() {
  // Cancela cualquier reconexión programada
  clearReconnectTimer()
  // Marca como desconexión intencional del usuario
  manualDisconnect = true

  // Cierra el socket si está abierto
  if (socket) {
    socket.close()
    socket = null
  }

  // Actualiza el estado a inactivo
  connectionStatus.value = 'idle'
}

// Establece la conexión WebSocket con el servidor de stock en tiempo real
// Implementa patrón singleton: solo una conexión activa compartida entre suscriptores
function connectSocket() {
  // No conectar en entorno de servidor (SSR)
  if (typeof window === 'undefined') {
    return
  }

  // Evita crear múltiples conexiones: si ya existe una abierta o conectándose, sale
  if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
    return
  }

  // Resetea la bandera de desconexión manual para permitir reconexiones automáticas
  manualDisconnect = false
  connectionStatus.value = 'connecting'

  // Crea una nueva conexión WebSocket con la URL resuelta
  const nextSocket = new WebSocket(resolveStockRealtimeUrl())
  socket = nextSocket

  // Evento: conexión establecida exitosamente
  nextSocket.addEventListener('open', () => {
    // Verifica que esta instancia sigue siendo la activa (evita race conditions)
    if (socket !== nextSocket) return
    connectionStatus.value = 'open'
  })

  // Evento: mensaje recibido del servidor
  nextSocket.addEventListener('message', (event) => {
    // Ignora mensajes de conexiones anteriores que ya no son válidas
    if (socket !== nextSocket) return

    // Normaliza y valida el mensaje recibido
    const message = normalizeRealtimeMessage(event.data)
    if (!message?.event) {
      return
    }

    // Actualiza el timestamp del último mensaje recibido
    lastMessageAt.value = message.publishedAt || new Date().toISOString()
    // Notifica a todos los suscriptores registrados
    notifyListeners(message)
  })

  // Evento: error en la conexión WebSocket
  nextSocket.addEventListener('error', () => {
    if (socket !== nextSocket) return
    connectionStatus.value = 'error'
  })

  // Evento: conexión cerrada (por servidor o red)
  nextSocket.addEventListener('close', () => {
    if (socket !== nextSocket) return

    socket = null
    // Si fue desconexión manual, mantiene 'idle'; si no, marca 'closed' e intenta reconectar
    connectionStatus.value = manualDisconnect ? 'idle' : 'closed'
    scheduleReconnect()
  })
}

// Función exportada para suscribirse a actualizaciones de stock en tiempo real
// Retorna una función de limpieza para cancelar la suscripción
// Patrón de diseño: pub/sub con gestión automática de la conexión
export function subscribeToStockRealtime(listener) {
  // Valida que el listener sea una función, si no retorna función vacía
  if (typeof listener !== 'function') {
    return () => {}
  }

  // Agrega el listener al conjunto de suscriptores
  listeners.add(listener)
  // Asegura que la conexión WebSocket esté activa
  connectSocket()

  // Retorna función de limpieza para desuscribirse
  return () => {
    listeners.delete(listener)

    // Si no quedan suscriptores, cierra la conexión para liberar recursos
    if (listeners.size === 0) {
      closeSocket()
    }
  }
}

// Composable de Vue para usar stock en tiempo real en componentes
// Gestiona automáticamente la suscripción y limpieza del ciclo de vida
export function useStockRealtime(listener) {
  // Variable local para almacenar la función de desuscripción
  let unsubscribe = null

  // Al montar el componente, establece la suscripción o solo conecta el socket
  onMounted(() => {
    if (typeof listener === 'function') {
      // Si se proporciona un listener, se suscribe a las actualizaciones
      unsubscribe = subscribeToStockRealtime(listener)
      return
    }

    // Si no hay listener, solo asegura que la conexión esté activa
    connectSocket()
  })

  // Al desmontar el componente, cancela la suscripción para evitar memory leaks
  onUnmounted(() => {
    unsubscribe?.()
  })

  // Retorna el estado de conexión y último mensaje como props de solo lectura
  // readonly() evita que los componentes modifiquen el estado global
  return {
    connectionStatus: readonly(connectionStatus),
    lastMessageAt: readonly(lastMessageAt),
  }
}
