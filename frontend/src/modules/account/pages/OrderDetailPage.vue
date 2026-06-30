<template>
  <!-- Sección principal del detalle del pedido -->
  <section class="order-detail-page">
    <!-- Muestra un efecto shimmer mientras se carga la información del pedido -->
    <AccountShimmer v-if="loading" variant="order-detail" />

    <!-- Contenido principal que se muestra cuando la carga ha terminado -->
    <template v-else>
      <!-- Encabezado de la página con título y descripción -->
      <section class="dashboard-header">
        <h1>Detalle de pedido</h1>
        <p>Consulta los datos completos de tu orden.</p>
      </section>

      <!-- Muestra mensajes de error si los hay -->
      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <!-- Contenido del detalle del pedido solo si se cargaron los datos correctamente -->
      <template v-else-if="orderDetail">
        <!-- Sección principal con información resumida del pedido -->
        <section class="order-hero account-card">
          <h2>Pedido #{{ orderDetail.order_number }}</h2>
          <p><i class="fas fa-calendar-alt" /> Realizado el {{ formatDate(orderDetail.created_at) }}</p>

          <!-- Badges que muestran el estado del pedido y del pago -->
          <div class="hero-badges">
            <span class="status-badge" :class="statusClass(orderDetail.status)">
              {{ statusLabel(orderDetail.status) }}
            </span>
            <span class="status-badge" :class="paymentBadgeClass(orderDetail.payment_status)">
              {{ paymentStatusLabel(orderDetail.payment_status) }}
            </span>
          </div>

          <!-- Acciones disponibles según el estado del pedido: descargar factura o cancelar -->
          <div v-if="hasInvoiceAvailable || canCancelCurrentOrder" class="order-hero-actions">
            <button v-if="hasInvoiceAvailable" type="button" class="btn-invoice-detail" :disabled="downloadingInvoice" @click="downloadCurrentInvoice">
              <i :class="downloadingInvoice ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'" />
              {{ downloadingInvoice ? 'Descargando...' : 'Descargar factura' }}
            </button>
            <button v-if="canCancelCurrentOrder" type="button" class="btn-cancel-detail" :disabled="cancellingOrder" @click="confirmCancelCurrentOrder">
              <i class="fas fa-ban" />
              {{ cancellingOrder ? 'Cancelando...' : 'Cancelar pedido' }}
            </button>
          </div>
        </section>

        <!-- Sección que lista todos los productos incluidos en el pedido -->
        <section class="account-card">
          <header class="section-header">
            <h2>Productos del pedido</h2>
          </header>

          <!-- Itera sobre cada producto del pedido para mostrar sus detalles -->
          <article v-for="item in orderItems" :key="item.id" class="order-item-row">
            <div class="order-item-media">
              <!-- Imagen del producto con manejo de errores en caso de URL inválida -->
              <img
                :src="resolveOrderItemImage(item)"
                :alt="item.product_name || 'Producto'"
                class="order-item-media__image"
                @error="onOrderItemImageError($event, item)"
              />
            </div>
            <div class="item-main">
              <h3>{{ item.product_name }}</h3>
              <p>Cantidad: {{ item.quantity }} | {{ item.variant_name || 'Sin variante' }}</p>
              <p>{{ formatPrice(item.price) }} c/u</p>
            </div>
            <strong class="item-total">{{ formatPrice(item.total) }}</strong>
          </article>
        </section>

        <!-- Sección que muestra el progreso visual del estado del pedido -->
        <section class="account-card">
          <header class="section-header">
            <h2>Estado del pedido</h2>
          </header>

          <!-- Barra de progreso que cambia de estilo si el pedido está en proceso de reembolso -->
          <div
            class="order-v2-progress"
            :class="{ 'order-v2-progress--refund': isRefundFlow(orderDetail) }"
          >
            <div class="progress-line" />
            <!-- Cada paso del progreso se renderiza dinámicamente según el flujo actual -->
            <div
              v-for="step in orderProgressSteps(orderDetail)"
              :key="step.key"
              class="progress-step"
              :class="{ active: isStepActive(orderDetail, step.key) }"
            >
              <span class="progress-step-icon">
                <i :class="step.icon" />
              </span>
              <span class="progress-step-label">{{ step.label }}</span>
            </div>
          </div>
        </section>

        <!-- Sección de historial de cambios del pedido, solo visible si hay registros -->
        <section v-if="orderHistory.length > 0" class="account-card">
          <header class="section-header">
            <h2>Historial de cambios</h2>
          </header>

          <!-- Lista cronológica de todos los cambios realizados al pedido -->
          <ul class="history-list">
            <li v-for="entry in orderHistory" :key="entry.id">
              <span>{{ formatDate(entry.created_at) }}</span>
              <strong>{{ entry.description || 'Actualización de pedido' }}</strong>
            </li>
          </ul>
        </section>
      </template>
    </template>
  </section>
</template>

<script setup>
// ============================================================
// IMPORTACIONES
// ============================================================
// Funcionalidades reactivas de Vue 3 Composition API
import { computed, onMounted, onUnmounted, ref } from 'vue'
// Router para obtener parámetros de la URL (ID del pedido)
import { useRoute } from 'vue-router'
// Componente de efecto de carga (shimmer) mientras se obtienen los datos
import AccountShimmer from '../components/AccountShimmer.vue'
// Composable para mostrar alertas modales al usuario (confirmaciones, advertencias)
import { useAlertSystem } from '../../../composables/useAlertSystem'
// Composable para acceder a la sesión del usuario actual
import { useSession } from '../../../composables/useSession'
// Composable para mostrar notificaciones temporales (snackbar)
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
// Utilidades para manejar URLs de imágenes y errores de carga de medios
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
// Funciones de la API para cancelar pedidos, descargar facturas y obtener detalles
import { cancelOrder, downloadOrderInvoice, getOrderById } from '../../../services/orderApi'
// Función para suscribirse a actualizaciones en tiempo real vía WebSocket
import { subscribeToOrderRealtime } from '../../../composables/useOrderRealtime'
// Utilidades para formatear y normalizar estados de pedidos y pagos
import { getOrderStatusLabel, getPaymentStatusLabel, normalizeOrderStatus, normalizePaymentStatus } from '../../../utils/orderPresentation'

// ============================================================
// COMposables Y SERVICIOS
// ============================================================
// Obtiene la ruta actual para extraer el ID del pedido desde la URL
const route = useRoute()
// Datos del usuario autenticado actualmente en la sesión
const { user } = useSession()
// Función para mostrar alertas modales de confirmación o error
const { showAlert } = useAlertSystem()
// Función para mostrar notificaciones temporales (snackbar) al usuario
const { showSnackbar } = useSnackbarSystem()

// ============================================================
// VARIABLES REACTIVAS DEL ESTADO LOCAL
// ============================================================
// Indica si los datos del pedido aún se están cargando
const loading = ref(true)
// Mensaje de error que se muestra al usuario si falla la carga
const errorMessage = ref('')
// Objeto con la información principal del pedido (número, fecha, estado, etc.)
const orderDetail = ref(null)
// Lista de productos incluidos en el pedido
const orderItems = ref([])
// Historial de cambios realizados al pedido (estados, pagos, etc.)
const orderHistory = ref([])
// Bandera para evitar múltiples clics durante la cancelación del pedido
const cancellingOrder = ref(false)
// Bandera para evitar múltiples clics durante la descarga de la factura
const downloadingInvoice = ref(false)
// Referencia a la función de desuscripción del WebSocket para limpiar al desmontar
let unsubscribeOrderRealtime = null

// ============================================================
// PASOS DEL PROGRESO DEL PEDIDO
// ============================================================
// Pasos estándar de un pedido: pendiente -> en proceso -> enviado -> entregado
const defaultOrderSteps = Object.freeze([
  { key: 'pending', label: 'Pendiente', icon: 'fas fa-clock' },
  { key: 'processing', label: 'En proceso', icon: 'fas fa-cog' },
  { key: 'shipped', label: 'Enviado', icon: 'fas fa-truck' },
  { key: 'delivered', label: 'Entregado', icon: 'fas fa-check' },
])

// Pasos alternativos cuando el pedido está en proceso de reembolso
const refundOrderSteps = Object.freeze([
  { key: 'refund_requested', label: 'Solicitado', icon: 'fas fa-undo' },
  { key: 'pending_refund', label: 'Reembolso en proceso', icon: 'fas fa-rotate' },
  { key: 'refunded', label: 'Reembolsado', icon: 'fas fa-hand-holding-usd' },
])

// ============================================================
// PROPIEDADES COMPUTADAS
// ============================================================
// Determina si el pedido puede ser cancelado según su estado actual
const canCancelCurrentOrder = computed(() => isOrderCancelable(orderDetail.value))

// Verifica si existe una factura disponible para descargar (número o fecha de factura)
const hasInvoiceAvailable = computed(() => {
  const invoiceNumber = String(orderDetail.value?.invoice_number || '').trim()
  const invoiceDate = String(orderDetail.value?.invoice_date || '').trim()
  return invoiceNumber !== '' || invoiceDate !== ''
})

// ============================================================
// CICLO DE VIDA
// ============================================================
// Al montar el componente: carga los datos del pedido y se suscribe a actualizaciones en tiempo real
onMounted(async () => {
  await loadOrderDetail()
  unsubscribeOrderRealtime = subscribeToOrderRealtime(handleRealtimeOrderUpdate)
})

// Al desmontar el componente: se desuscribe del WebSocket para evitar fugas de memoria
onUnmounted(() => {
  unsubscribeOrderRealtime?.()
})

// ============================================================
// FUNCIONES PRINCIPALES
// ============================================================
// Carga los datos completos del pedido desde la API
// options.silent: si es true, no muestra el indicador de carga (para actualizaciones en segundo plano)
async function loadOrderDetail(options = {}) {
  const silent = Boolean(options.silent)
  if (!silent) {
    loading.value = true
  }
  errorMessage.value = ''

  try {
    // Extrae el ID del pedido desde los parámetros de la URL
    const orderId = Number(route.params.id)
    if (!Number.isFinite(orderId) || orderId <= 0) {
      throw new Error('invalid_order_id')
    }

    // Realiza la petición a la API para obtener el detalle completo
    const response = await getOrderById(orderId)
    orderDetail.value = response?.order || null
    orderItems.value = Array.isArray(response?.items) ? response.items : []
    orderHistory.value = Array.isArray(response?.history) ? response.history : []

    // Si la API no devuelve datos del pedido, muestra un mensaje de error
    if (!orderDetail.value) {
      errorMessage.value = 'No se encontró el pedido solicitado.'
    }
  } catch {
    // En caso de error de red o de la API, solo muestra el error si no es una carga silenciosa
    if (!silent) {
      errorMessage.value = 'No se pudo cargar el detalle del pedido.'
    }
  } finally {
    // Oculta el indicador de carga solo si no es una actualización silenciosa
    if (!silent) {
      loading.value = false
    }
  }
}

// Maneja las actualizaciones en tiempo real recibidas por WebSocket
// Actualiza el estado del pedido y del pago en la interfaz sin recargar la página
async function handleRealtimeOrderUpdate(message) {
  const currentOrderId = Number(route.params.id)
  // Ignora mensajes que no correspondan al pedido actual o si no hay datos cargados
  if (!Number.isFinite(currentOrderId) || currentOrderId !== Number(message.orderId) || !orderDetail.value) {
    return
  }

  // Crea una copia del pedido actual para aplicar los cambios sin mutar el objeto original
  const nextOrder = { ...orderDetail.value }
  // Maneja diferentes formatos de mensajes del WebSocket para actualizar campos específicos
  if (message.field === 'status' && message.newValue) {
    nextOrder.status = message.newValue
  }
  if (message.field === 'payment_status' && message.newValue) {
    nextOrder.payment_status = message.newValue
  }
  // Formato alternativo de mensajes con propiedades directas
  if (message.status) {
    nextOrder.status = message.status
  }
  if (message.paymentStatus) {
    nextOrder.payment_status = message.paymentStatus
  }
  // Actualiza el estado de reembolso si está presente en el mensaje
  if (message.refundStatus) {
    nextOrder.refund_status = message.refundStatus
    nextOrder.refund_request_status = message.refundStatus
  }

  // Aplica los cambios al pedido y recarga los datos en segundo plano para mantener consistencia
  orderDetail.value = nextOrder
  await loadOrderDetail({ silent: true })
}

// ============================================================
// FUNCIONES DE FORMATEO
// ============================================================
// Formatea una fecha usando el formato local de Colombia (es-CO)
function formatDate(value) {
  if (!value) return '-'

  const date = new Date(value)
  return date.toLocaleString('es-CO')
}

// Formatea un valor numérico como moneda colombiana (COP) sin decimales
function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

// ============================================================
// FUNCIONES DE DESCARGA DE FACTURA
// ============================================================
// Extrae el nombre del archivo desde los headers HTTP de la respuesta
// Soporta formato UTF-8 para nombres con caracteres especiales (tildes, ñ)
function parseFilenameFromHeaders(headers, fallbackName) {
  const disposition = String(headers?.['content-disposition'] || '').trim()
  // Intenta extraer el nombre del archivo en formato UTF-8 (filename*=UTF-8''nombre)
  const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1])
  }

  // Si no hay formato UTF-8, extrae el nombre estándar (filename="nombre")
  const regularMatch = disposition.match(/filename="?([^";]+)"?/i)
  return regularMatch?.[1] ? regularMatch[1].trim() : fallbackName
}

// Descarga la factura PDF del pedido actual
// Crea un enlace temporal para forzar la descarga en el navegador
async function downloadCurrentInvoice() {
  // Valida que existan datos, no haya otra descarga en curso, y haya factura disponible
  if (!orderDetail.value || downloadingInvoice.value || !hasInvoiceAvailable.value) return

  downloadingInvoice.value = true
  // Nombre por defecto en caso de que el backend no envíe el nombre del archivo
  const fallbackName = `factura_${orderDetail.value.invoice_number || orderDetail.value.order_number || orderDetail.value.id}.pdf`

  try {
    // Realiza la petición a la API para descargar la factura
    const response = await downloadOrderInvoice(orderDetail.value.id, {
      source: String(orderDetail.value.order_source || '').trim() || undefined,
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
    })
    // Extrae el nombre del archivo desde los headers o usa el nombre por defecto
    const fileName = parseFilenameFromHeaders(response.headers, fallbackName)
    // Crea un Blob con los datos del PDF y genera una URL temporal
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const objectUrl = window.URL.createObjectURL(blob)
    // Crea un elemento <a> temporal para simular la descarga
    const anchor = document.createElement('a')
    anchor.href = objectUrl
    anchor.download = fileName
    document.body.appendChild(anchor)
    anchor.click()
    // Limpia los recursos temporales para evitar fugas de memoria
    document.body.removeChild(anchor)
    window.URL.revokeObjectURL(objectUrl)
    showSnackbar({ type: 'success', message: 'Factura descargada correctamente.' })
  } catch {
    showSnackbar({ type: 'error', message: 'No pudimos descargar la factura de este pedido.' })
  } finally {
    downloadingInvoice.value = false
  }
}

// ============================================================
// FUNCIONES DE ESTADO DEL PEDIDO
// ============================================================
// Devuelve la etiqueta de texto para el estado del pedido
function statusLabel(status) {
  return getOrderStatusLabel(status) || status || 'Sin estado'
}

// Determina la clase CSS correspondiente al estado del pedido para estilizar el badge
function statusClass(status) {
  const normalizedStatus = normalizeOrderStatus(status)
  const rawStatus = String(status || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_')

  // Mapea estados normalizados a clases CSS específicas
  if (normalizedStatus === 'pending') return 'status-pending'
  if (['in_review', 'processing'].includes(normalizedStatus)) return 'status-processing'
  if (['paid', 'confirmed', 'shipped'].includes(rawStatus)) return `status-${rawStatus}`
  if (['delivered', 'completed'].includes(normalizedStatus)) return `status-${normalizedStatus}`
  if (['cancelled', 'canceled', 'failed', 'refunded'].includes(rawStatus)) return 'status-cancelled'

  // Estado por defecto si no coincide con ninguno conocido
  return 'status-processing'
}

// ============================================================
// FUNCIONES DE ESTADO DE PAGO
// ============================================================
// Devuelve la etiqueta de texto en español para el estado de pago
function paymentStatusLabel(paymentStatus) {
  const value = normalizePaymentStatus(paymentStatus)

  // Mapea cada estado de pago a su correspondiente etiqueta en español
  if (value === 'verified' || value === 'approved') return 'Pago verificado'
  if (value === 'pending') return 'Pendiente de pago'
  if (value === 'pending_refund') return 'Reembolso en proceso'
  if (value === 'refunded') return 'Reembolsado'
  if (value === 'paid') return 'Pagado'
  if (value === 'failed' || value === 'rejected') return 'Pago rechazado'
  // Si el estado no está mapeado explícitamente, usa la función de utilidad general
  if (value) return getPaymentStatusLabel(value)
  return 'Pendiente de pago'
}

// Determina la clase CSS para el badge de estado de pago
function paymentBadgeClass(paymentStatus) {
  const value = normalizePaymentStatus(paymentStatus)

  // Mapea estados de pago a clases CSS que definen colores del badge
  if (value === 'verified' || value === 'approved') return 'status-delivered'
  if (value === 'paid') return 'status-delivered'
  if (['pending', 'pending_payment', 'in_review', 'en_revision'].includes(value)) return 'status-pending'
  if (value === 'pending_refund') return 'status-processing'
  if (value === 'refunded') return 'status-cancelled'
  if (value === 'failed' || value === 'rejected') return 'status-cancelled'
  return 'status-pending'
}

// ============================================================
// FUNCIONES DE FLUJO DE REEMBOLSO
// ============================================================
// Determina si el pedido está en un flujo de reembolso
// Compara estados de pago y reembolso para detectar si se requiere devolución de dinero
function isRefundFlow(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  const refundStatus = normalizeStatus(order?.refund_status || order?.refund_request_status)
  // Verifica si el estado de pago indica reembolso o si el estado de reembolso está activo
  return ['refund_requested', 'pending_refund', 'refunded'].includes(paymentStatus)
    || ['requested', 'processing', 'completed'].includes(refundStatus)
}

// Devuelve los pasos de progreso según el flujo actual (estándar o reembolso)
function orderProgressSteps(order) {
  return isRefundFlow(order) ? refundOrderSteps : defaultOrderSteps
}

// ============================================================
// FUNCIONES DE PROGRESO DEL PEDIDO
// ============================================================
// Normaliza el estado del pedido para el progreso estándar
// Agrupa estados similares (confirmado/pagado -> en proceso, completado -> entregado)
function resolveStandardProgressStatus(order) {
  let status = normalizeStatus(order?.status)

  // Estados que se consideran equivalentes para el progreso visual
  if (status === 'confirmed' || status === 'paid') status = 'processing'
  if (status === 'in_review' || status === 'en_revision') status = 'processing'
  if (status === 'completed') status = 'delivered'

  return status
}

// Normaliza el estado del pedido para el progreso de reembolso
// Determina en qué paso del flujo de reembolso se encuentra el pedido
function resolveRefundProgressStatus(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  const refundStatus = normalizeStatus(order?.refund_status || order?.refund_request_status)
  if (paymentStatus === 'refunded') {
    return 'refunded'
  }

  if (paymentStatus === 'pending_refund' || refundStatus === 'processing') {
    return 'pending_refund'
  }

  return 'refund_requested'
}

// Determina si un paso específico del progreso está activo (completado o en curso)
// Compara la posición del paso con el estado actual del pedido
function isStepActive(order, stepKey) {
  // Selecciona el flujo de pasos y el estado actual según el tipo de pedido
  const flow = isRefundFlow(order)
    ? ['refund_requested', 'pending_refund', 'refunded']
    : ['pending', 'processing', 'shipped', 'delivered']
  const currentStatus = isRefundFlow(order)
    ? resolveRefundProgressStatus(order)
    : resolveStandardProgressStatus(order)

  // Encuentra la posición del estado actual y del paso en el flujo
  const currentIndex = flow.indexOf(currentStatus)
  const stepIndex = flow.indexOf(stepKey)

  // Si el estado actual o el paso no existen en el flujo, solo activa el primer paso
  if (currentIndex < 0 || stepIndex < 0) return stepKey === 'pending'
  // El paso está activo si su posición es menor o igual al estado actual
  return stepIndex <= currentIndex
}

// ============================================================
// FUNCIONES DE IMÁGENES DE PRODUCTOS
// ============================================================
// Extrae la ruta de la imagen del producto desde diferentes propiedades posibles del item
// Prioriza product_image, luego image, image_path, variant_image y thumbnail
function resolveOrderItemImagePath(item = {}) {
  return String(
    item?.product_image
    || item?.image
    || item?.image_path
    || item?.variant_image
    || item?.thumbnail
    || '',
  ).trim()
}

// Resuelve la URL completa de la imagen del producto usando la utilidad de medios
function resolveOrderItemImage(item = {}) {
  return resolveMediaUrl(resolveOrderItemImagePath(item), 'product')
}

// Maneja errores de carga de imágenes de productos
// Muestra una imagen de respaldo si la URL original falla
function onOrderItemImageError(event, item) {
  handleMediaError(event, resolveOrderItemImagePath(item), 'product')
}

// ============================================================
// FUNCIONES DE VALIDACIÓN DE CANCELACIÓN
// ============================================================
// Determina si un pedido puede ser cancelado según su estado
// Solo permite cancelar pedidos en estados iniciales (pendiente, procesando, confirmado, pagado)
function isOrderCancelable(order) {
  const status = normalizeStatus(order?.status)
  // Los pedidos ya cancelados no se pueden cancelar nuevamente
  if (status === 'cancelled' || status === 'canceled') {
    return false
  }

  return ['pending', 'processing', 'confirmed', 'paid'].includes(status)
}

// Determina si un pedido requiere proceso de reembolso al cancelarse
// Retorna true si el pago ya fue verificado/aprobado/pagado
function requiresRefund(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  if (['paid', 'approved', 'verified'].includes(paymentStatus)) {
    return true
  }

  // Caso alternativo: si no hay estado de pago pero el estado del pedido indica pago/confirmación
  const status = normalizeStatus(order?.status)
  return !paymentStatus && ['paid', 'confirmed'].includes(status)
}

// ============================================================
// FUNCIONES DE CANCELACIÓN DEL PEDIDO
// ============================================================
// Muestra una alerta de confirmación antes de cancelar el pedido
// Adapta el mensaje según si se requiere reembolso o no
function confirmCancelCurrentOrder() {
  if (!orderDetail.value || cancellingOrder.value || !isOrderCancelable(orderDetail.value)) return

  const orderLabel = String(orderDetail.value.order_number || `#${orderDetail.value.id || ''}`)
  const refundRequired = requiresRefund(orderDetail.value)

  showAlert({
    type: 'warning',
    title: 'Cancelar pedido',
    message: refundRequired
      ? `¿Deseas cancelar el pedido ${orderLabel}? Iniciaremos el proceso de reembolso y te notificaremos por correo.`
      : `¿Deseas cancelar el pedido ${orderLabel}? Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Volver', style: 'secondary' },
      {
        text: 'Sí, cancelar pedido',
        style: 'danger',
        // Callback que se ejecuta cuando el usuario confirma la cancelación
        callback: async () => {
          await submitCancellationFromDetail()
        },
      },
    ],
  })
}

// Ejecuta la cancelación del pedido enviando la solicitud a la API
// Incluye información del usuario y razón de cancelación para auditoría
async function submitCancellationFromDetail() {
  if (!orderDetail.value || cancellingOrder.value) return

  cancellingOrder.value = true

  try {
    const response = await cancelOrder(orderDetail.value.id, {
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
      cancelled_by_name: String(user.value?.name || '').trim() || undefined,
      reason: 'Cancelación solicitada por el cliente desde el detalle del pedido.',
    })

    showSnackbar({
      type: 'success',
      title: 'Pedido cancelado',
      message: String(response?.message || 'Tu pedido fue cancelado correctamente.'),
    })

    // Recarga los datos del pedido para reflejar el nuevo estado
    await loadOrderDetail()
  } catch (error) {
    // Extrae el mensaje de error de la respuesta de la API o usa un mensaje por defecto
    const apiMessage = String(
      error?.response?.data?.message
      || error?.response?.data?.error
      || 'No pudimos cancelar tu pedido. Intenta nuevamente.',
    ).trim()

    showSnackbar({
      type: 'error',
      title: 'No se pudo cancelar',
      message: apiMessage || 'No pudimos cancelar tu pedido. Intenta nuevamente.',
    })
  } finally {
    cancellingOrder.value = false
  }
}

// ============================================================
// UTILIDADES
// ============================================================
// Normaliza un valor de estado: convierte a minúsculas, elimina espacios
function normalizeStatus(value) {
  return String(value || '').trim().toLowerCase()
}
</script>

<style scoped>
/* ============================================================
   ESTILOS DEL COMPONENTE OrderDetailPage
   Estilos scoped que solo aplican a este componente
   ============================================================ */

/* Contenedor principal del detalle del pedido con espacio entre secciones */
.order-detail-page {
  display: grid;
  gap: 1.2rem;
}

/* Estilos del encabezado principal del pedido */
.order-hero h2 {
  margin: 0;
  font-size: 2.9rem;
}

/* Texto de fecha con icono alineado */
.order-hero p {
  margin: 0.65rem 0 0;
  color: #6b7280;
  font-size: 1.4rem;
  display: inline-flex;
  gap: 0.5rem;
  align-items: center;
}

/* Contenedor de badges de estado (pedido y pago) */
.hero-badges {
  margin-top: 1rem;
  display: flex;
  gap: 0.7rem;
}

/* Contenedor de botones de acción (factura y cancelar) */
.order-hero-actions {
  margin-top: 1rem;
}

/* Botón de cancelar pedido con estilo de peligro (rojo) */
.btn-cancel-detail {
  border: 1px solid #ef4444;
  background: #fff;
  color: #dc2626;
  border-radius: 10px;
  font-size: 1.35rem;
  font-weight: 700;
  padding: 0.9rem 1.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  cursor: pointer;
}

/* Botón de descargar factura con estilo informativo (azul) */
.btn-invoice-detail {
  border: 1px solid #90c6e8;
  background: #e9f6fd;
  color: #0077b6;
  border-radius: 10px;
  font-size: 1.35rem;
  font-weight: 700;
  padding: 0.9rem 1.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  cursor: pointer;
}

/* Hover del botón de factura */
.btn-invoice-detail:hover {
  background: #dff0fb;
}

/* Estado deshabilitado del botón de factura */
.btn-invoice-detail:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Hover del botón de cancelar */
.btn-cancel-detail:hover {
  background: #fff1f2;
}

/* Estado deshabilitado del botón de cancelar */
.btn-cancel-detail:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Fila de producto individual en el pedido */
.order-item-row {
  border: 1px solid #dbe3ed;
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

/* Contenedor de la imagen del producto */
.order-item-media {
  width: 8.2rem;
  height: 8.2rem;
  border-radius: 12px;
  border: 1px solid #dbe3ed;
  overflow: hidden;
  flex-shrink: 0;
  background: #f8fafc;
}

/* Imagen del producto ajustada al contenedor */
.order-item-media__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Información principal del producto (nombre, cantidad, precio) */
.item-main {
  flex: 1;
  min-width: 0;
}

/* Nombre del producto */
.item-main h3 {
  margin: 0;
  font-size: 2rem;
}

/* Detalles del producto (cantidad, variante, precio unitario) */
.item-main p {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 1.3rem;
}

/* Precio total del producto (cantidad x precio unitario) */
.item-total {
  font-size: 2.8rem;
  color: #0077b6;
}

/* Contenedor del progreso del pedido con diseño flex para分布均匀分布 */
.order-v2-progress {
  position: relative;
  border: 1px solid #cfe0ec;
  border-radius: 14px;
  padding: 1.2rem 1rem 1rem;
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

/* Línea de progreso horizontal que conecta los pasos */
.progress-line {
  position: absolute;
  top: 27px;
  left: 8%;
  right: 8%;
  border-top: 3px solid #0f7bb8;
}

/* Cada paso individual del progreso */
.progress-step {
  z-index: 1;
  display: grid;
  justify-items: center;
  gap: 0.55rem;
  min-width: 90px;
}

/* Icono circular de cada paso del progreso */
.progress-step-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #d1d5db;
  color: #4b5563;
  display: grid;
  place-items: center;
  font-size: 1.6rem;
}

/* Etiqueta de texto debajo del icono */
.progress-step-label {
  font-size: 1.25rem;
  color: #6b7280;
  font-weight: 700;
}

/* Estado activo: pasos completados o en curso se muestran en verde */
.progress-step.active .progress-step-icon {
  background: #44ae42;
  color: #fff;
}

/* Último paso activo usa color azul para destacar el estado final */
.progress-step.active:last-child .progress-step-icon {
  background: #0f7bb8;
}

/* Color del texto para pasos activos */
.progress-step.active .progress-step-label {
  color: #2f9f47;
}

/* Color del texto para el último paso activo */
.progress-step.active:last-child .progress-step-label {
  color: #0f7bb8;
}

/* ============================================================
   ESTILOS PARA FLUJO DE REEMBOLSO
   Cambia los colores a tonos naranja/ámbar para indicar proceso de reembolso
   ============================================================ */

/* Línea de progreso en color naranja para reembolso */
.order-v2-progress--refund .progress-line {
  border-top-color: #d97706;
}

/* Iconos activos en naranja durante flujo de reembolso */
.order-v2-progress--refund .progress-step.active .progress-step-icon {
  background: #d97706;
}

/* Texto de pasos activos en tono ámbar */
.order-v2-progress--refund .progress-step.active .progress-step-label {
  color: #b45309;
}

/* Último paso de reembolso completado usa verde para indicar éxito */
.order-v2-progress--refund .progress-step.active:last-child .progress-step-icon {
  background: #16a34a;
}

/* Texto del último paso de reembolso completado en verde */
.order-v2-progress--refund .progress-step.active:last-child .progress-step-label {
  color: #15803d;
}

/* ============================================================
   ESTILOS DEL HISTORIAL DE CAMBIOS
   ============================================================ */

/* Lista de eventos del historial del pedido */
.history-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.75rem;
}

/* Cada entrada del historial con borde y espaciado */
.history-list li {
  border: 1px solid #dbe3ed;
  border-radius: 10px;
  padding: 0.85rem 1rem;
  display: grid;
  gap: 0.35rem;
}

/* Fecha de cada evento del historial */
.history-list span {
  color: #6b7280;
  font-size: 1.2rem;
}

/* Descripción del evento del historial */
.history-list strong {
  font-size: 1.35rem;
  color: #1f2937;
}

/* ============================================================
   DISEÑO RESPONSIVO
   Ajustes para pantallas pequeñas (menores a 900px)
   ============================================================ */
@media (max-width: 900px) {
  /* En móvil, los productos se apilan verticalmente */
  .order-item-row {
    flex-direction: column;
    align-items: flex-start;
  }

  /* Imagen del producto ajustada para móviles */
  .order-item-media {
    width: 100%;
    max-width: 12rem;
    height: 12rem;
  }

  /* Precio total ligeramente más pequeño en móviles */
  .item-total {
    font-size: 2.3rem;
  }

  /* El progreso se vuelve scroll horizontal en pantallas pequeñas */
  .order-v2-progress {
    overflow-x: auto;
    justify-content: flex-start;
    gap: 1.2rem;
  }

  /* Línea de progreso con ancho mínimo para mantener diseño en scroll */
  .progress-line {
    min-width: 420px;
    left: 35px;
    right: 35px;
  }
}
</style>
