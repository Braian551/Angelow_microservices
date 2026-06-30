<template>
  <!-- Muestra un efecto shimmer mientras se cargan los pedidos -->
  <AccountShimmer v-if="loading" variant="orders" />

  <!-- Contenido principal visible solo cuando termina la carga -->
  <template v-else>
    <section class="dashboard-header">
      <h1>Mis pedidos</h1>
      <p>Consulta el estado y detalle de todas tus órdenes.</p>
    </section>

    <section class="account-card">
      <header class="section-header">
        <h2>Historial de pedidos</h2>
      </header>

      <!-- Muestra un mensaje de error si la carga de pedidos falló -->
      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <!-- Estado vacío: cuando el usuario no tiene pedidos registrados -->
      <div v-else-if="orders.length === 0" class="empty-state">
        <i class="fas fa-box-open" />
        <p>No tienes pedidos aún.</p>
        <RouterLink :to="{ name: 'store' }" class="btn-primary-small">Comprar ahora</RouterLink>
      </div>

      <!-- Lista de pedidos del usuario: se renderiza cada tarjeta de pedido -->
      <div v-else class="orders-v2-list">
        <!-- Tarjeta individual de cada pedido con resumen de información -->
        <!-- Resalta visualmente el pedido si fue seleccionado desde la URL (query param) -->
        <article
          v-for="order in orders"
          :key="order.id"
          class="order-v2-card"
          :class="{ 'order-highlight': String(selectedOrderId) === String(order.id) }"
        >
          <!-- Cabecera del pedido: número de orden, fecha y badge de estado -->
          <div class="order-v2-header">
            <div class="order-v2-title">
              <h3>Pedido #{{ order.order_number }}</h3>
              <span class="order-v2-date">
                <i class="fas fa-calendar-alt" /> {{ formatDate(order.created_at) }}
              </span>
            </div>
            <!-- Badge que muestra el estado del pedido con color según su valor -->
            <span class="status-badge" :class="statusClass(order.status)">
              {{ statusLabel(order.status) }}
            </span>
          </div>

          <!-- Métricas resumidas del pedido: cantidad de productos, total y estado de pago -->
          <div class="order-v2-metrics">
            <div class="metric-item">
              <i class="fas fa-box" />
              <span>{{ order.items_count || 0 }} producto(s)</span>
            </div>

            <div class="metric-item metric-total">
              <i class="fas fa-dollar-sign" />
              <span>{{ formatPrice(order.total) }}</span>
            </div>

            <div class="metric-item">
              <i class="fas fa-credit-card" />
              <span>{{ paymentStatusLabel(order) }}</span>
            </div>
          </div>

          <!-- Dirección de envío del pedido (solo se muestra si existe) -->
          <div v-if="order.shipping_address" class="order-v2-address">
            <i class="fas fa-map-marker-alt" />
            <span>{{ order.shipping_address }}</span>
          </div>

          <!-- Acciones disponibles para el pedido: ver detalles, repetir pedido y solicitar reembolso -->
          <div class="order-v2-actions">
            <RouterLink :to="{ name: 'account-order-detail', params: { id: order.id } }" class="btn-view-order">
              <i class="fas fa-eye" /> Ver detalles
            </RouterLink>
            <RouterLink :to="{ name: 'store' }" class="btn-repeat-order">
              <i class="fas fa-redo-alt" /> Volver a pedir
            </RouterLink>
            <!-- Botón de reembolso: solo se muestra si el pedido tiene reembolso disponible -->
            <button
              v-if="canRequestRefund(order)"
              type="button"
              class="btn-refund-order"
              :disabled="submittingRefund"
              @click="openRefundModal(order)"
            >
              <i class="fas fa-rotate-left" />
              Reembolso
            </button>
          </div>

          <!-- Barra de progreso del pedido: muestra los pasos del flujo (normal o reembolso) -->
          <div
            class="order-v2-progress"
            :class="{ 'order-v2-progress--refund': isRefundFlow(order) }"
          >
            <div class="progress-line" />
            <div
              v-for="step in orderProgressSteps(order)"
              :key="`${order.id}-${step.key}`"
              class="progress-step"
              :class="{ active: isStepActive(order, step.key) }"
            >
              <span class="progress-step-icon">
                <i :class="step.icon" />
              </span>
              <span class="progress-step-label">{{ step.label }}</span>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- Modal de solicitud de reembolso: se muestra al hacer clic en "Reembolso" -->
    <div v-if="refundModalOpen" class="refund-modal-overlay" @click.self="closeRefundModal">
      <!-- Formulario de solicitud de reembolso dentro del modal -->
      <form class="refund-modal" @submit.prevent="submitRefundRequest">
        <header class="refund-modal__header">
          <div>
            <h3>Solicitar reembolso</h3>
            <p>Pedido #{{ activeRefundOrder?.order_number }}</p>
          </div>
          <button type="button" aria-label="Cerrar" @click="closeRefundModal">
            <i class="fas fa-times" />
          </button>
        </header>

        <div class="refund-modal__body">
          <!-- Campo de selección del motivo de reembolso (obligatorio) -->
          <label for="refund-reason">Motivo de reembolso *</label>
          <select id="refund-reason" v-model="refundForm.reason" class="refund-control" :class="{ 'is-invalid': refundErrors.reason }" @change="validateRefundField('reason')">
            <option value="">Seleccionar motivo...</option>
            <option v-for="reason in refundReasons" :key="reason.value" :value="reason.value">{{ reason.label }}</option>
          </select>
          <p v-if="refundErrors.reason" class="form-error">{{ refundErrors.reason }}</p>

          <!-- Campo de textarea: solo se muestra cuando el motivo es "otros" -->
          <label v-if="refundForm.reason === 'otros'" for="refund-details">Cuéntanos qué pasó *</label>
          <textarea
            v-if="refundForm.reason === 'otros'"
            id="refund-details"
            v-model="refundForm.details"
            class="refund-control"
            :class="{ 'is-invalid': refundErrors.details }"
            rows="4"
            placeholder="Describe el motivo del reembolso."
            @input="validateRefundField('details')"
          />
          <p v-if="refundErrors.details" class="form-error">{{ refundErrors.details }}</p>

          <!-- Campo de carga de evidencia: acepta imágenes, PDFs y documentos Word -->
          <label for="refund-evidence">Evidencia (imagen, PDF o documento)</label>
          <input id="refund-evidence" class="refund-control" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" @change="handleRefundEvidence">
          <p v-if="refundForm.evidence" class="refund-modal__file">{{ refundForm.evidence.name }}</p>
        </div>

        <!-- Pie del modal con botones de acción: volver y enviar solicitud -->
        <footer class="refund-modal__footer">
          <button type="button" class="btn-repeat-order" @click="closeRefundModal">Volver</button>
          <button type="submit" class="btn-refund-order" :disabled="submittingRefund">
            <i class="fas fa-paper-plane" />
            {{ submittingRefund ? 'Enviando...' : 'Enviar solicitud' }}
          </button>
        </footer>
      </form>
    </div>
  </template>
</template>

<script setup>
// Importaciones de Vue: composición reactiva y ciclo de vida
import { computed, onMounted, onUnmounted, ref } from 'vue'
// RouterLink y useRoute para navegación y acceso a parámetros de la ruta
import { RouterLink, useRoute } from 'vue-router'
// Componente de efecto shimmer que se muestra durante la carga inicial
import AccountShimmer from '../components/AccountShimmer.vue'
// Composable para mostrar alertas modales de confirmación
import { useAlertSystem } from '../../../composables/useAlertSystem'
// Composable para mostrar notificaciones tipo snackbar (toasts)
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
// Funciones de la API de pedidos: listar, cancelar y solicitar reembolso
import { cancelOrder, getOrders, requestOrderRefund } from '../../../services/orderApi'
// Composable que provee datos de sesión del usuario (autenticación)
import { useSession } from '../../../composables/useSession'
// Función para suscribirse a actualizaciones en tiempo real de pedidos (WebSocket)
import { subscribeToOrderRealtime } from '../../../composables/useOrderRealtime'
// Utilidades de presentación: etiquetas legibles y normalización de estados
import { getOrderStatusLabel, getPaymentStatusLabel, normalizeOrderStatus, normalizePaymentStatus } from '../../../utils/orderPresentation'

// Instancia de route para acceder a query parameters de la URL
const route = useRoute()
// Datos de sesión del usuario actual: id, email y estado de autenticación
const { user, isLoggedIn } = useSession()
// Sistema de alertas modales (confirmaciones, advertencias)
const { showAlert } = useAlertSystem()
// Sistema de notificaciones efímeras (snackbar/toast)
const { showSnackbar } = useSnackbarSystem()

// Estado reactivo: indica si los pedidos se están cargando (muestra shimmer)
const loading = ref(true)
// Mensaje de error global si falla la carga de pedidos desde la API
const errorMessage = ref('')
// Lista reactiva de pedidos del usuario logueado
const orders = ref([])
// ID del pedido que se está cancelando actualmente (para deshabilitar botones duplicados)
const cancellingOrderId = ref(null)
// Controla la visibilidad del modal de solicitud de reembolso
const refundModalOpen = ref(false)
// Pedido seleccionado para el proceso de reembolso
const activeRefundOrder = ref(null)
// Indica si se está enviando la solicitud de reembolso a la API (deshabilita el botón de envío)
const submittingRefund = ref(false)
// Formulario reactivo del reembolso: motivo, detalles y archivo de evidencia
const refundForm = ref({ reason: '', details: '', evidence: null })
// Errores de validación del formulario de reembolso
const refundErrors = ref({ reason: '', details: '' })
// Referencia a la función de desuscripción del canal de tiempo real (se invoca en onUnmounted)
let unsubscribeOrderRealtime = null

// Lista de motivos de reembolso disponibles para seleccionar en el formulario
// Se usa Object.freeze para evitar mutaciones accidentales
const refundReasons = Object.freeze([
  { value: 'talla_incorrecta', label: 'La talla no fue adecuada' },
  { value: 'producto_defectuoso', label: 'El producto llegó defectuoso' },
  { value: 'producto_equivocado', label: 'Recibí un producto diferente' },
  { value: 'no_cumple_expectativa', label: 'No cumple con lo esperado' },
  { value: 'otros', label: 'Otros' },
])

// Pasos del flujo normal de un pedido: Pendiente → En proceso → Enviado → Entregado
const defaultOrderSteps = Object.freeze([
  { key: 'pending', label: 'Pendiente', icon: 'fas fa-clock' },
  { key: 'processing', label: 'En proceso', icon: 'fas fa-cog' },
  { key: 'shipped', label: 'Enviado', icon: 'fas fa-truck' },
  { key: 'delivered', label: 'Entregado', icon: 'fas fa-check' },
])

// Pasos del flujo de reembolso: Solicitado → En proceso → Reembolsado
const refundOrderSteps = Object.freeze([
  { key: 'refund_requested', label: 'Solicitado', icon: 'fas fa-undo' },
  { key: 'pending_refund', label: 'Reembolso en proceso', icon: 'fas fa-rotate' },
  { key: 'refunded', label: 'Reembolsado', icon: 'fas fa-hand-holding-usd' },
])

// Computed que extrae el ID del pedido seleccionado desde el query param "order" de la URL
// Permite resaltar un pedido específico cuando el usuario llega desde un enlace
const selectedOrderId = computed(() => String(route.query.order || '').trim())

// Al montar el componente: carga los pedidos y se suscribe a actualizaciones en tiempo real
onMounted(async () => {
  await loadOrders()
  unsubscribeOrderRealtime = subscribeToOrderRealtime(handleRealtimeOrderUpdate)
})

// Al desmontar el componente: cancela la suscripción al canal de tiempo real para evitar fugas de memoria
onUnmounted(() => {
  unsubscribeOrderRealtime?.()
})

/**
 * Carga la lista de pedidos del usuario desde la API.
 * @param {Object} options - Opciones de carga.
 * @param {boolean} options.silent - Si es true, no muestra el shimmer ni limpia errores
 *   (usado para refrescos en segundo plano tras actualizaciones en tiempo real).
 */
async function loadOrders(options = {}) {
  // En modo silencioso se omite el indicador de carga para no parpadeos visuales
  const silent = Boolean(options.silent)
  if (!silent) {
    loading.value = true
  }
  errorMessage.value = ''

  try {
    // Si el usuario no está autenticado, limpia la lista y retorna
    if (!isLoggedIn.value) {
      orders.value = []
      return
    }

    // Solicita los pedidos a la API usando el id y email del usuario como filtros
    const response = await getOrders({
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
    })
    // Asigna los pedidos; si la respuesta no es un array válido, usa un array vacío
    orders.value = Array.isArray(response?.data) ? response.data : []
  } catch {
    // En caso de error de red o del servidor, solo muestra el mensaje si no es carga silenciosa
    if (!silent) {
      errorMessage.value = 'No se pudieron cargar los pedidos.'
    }
  } finally {
    // Oculta el shimmer siempre que no sea carga silenciosa
    if (!silent) {
      loading.value = false
    }
  }
}

/**
 * Callback invocado cuando el servidor envía una actualización en tiempo real de un pedido.
 * Actualiza el estado del pedido en la lista local sin recargar toda la página.
 * @param {Object} message - Mensaje recibido del canal de tiempo real.
 */
async function handleRealtimeOrderUpdate(message) {
  // Busca el índice del pedido afectado en la lista local
  const orderIndex = orders.value.findIndex((order) => Number(order?.id) === Number(message.orderId))
  // Si el pedido no está en la lista actual, se ignora
  if (orderIndex < 0) {
    return
  }

  // Actualiza el estado visible de inmediato y refresca en segundo plano los campos derivados del pedido.
  // Se crea una copia superficial para mantener la reactividad de Vue
  const nextOrder = { ...orders.value[orderIndex] }
  // Normaliza el nombre del campo cambiado para comparar de forma insensible a mayúsculas/espacios
  const changedField = String(message.field || '').trim().toLowerCase()

  // Actualiza campos individuales según el tipo de cambio reportado por el WebSocket
  if (changedField === 'status' && message.newValue) {
    nextOrder.status = message.newValue
  }
  if (changedField === 'payment_status' && message.newValue) {
    nextOrder.payment_status = message.newValue
  }
  // Soporta formatos alternativos del mensaje (status directo, paymentStatus, refundStatus)
  if (message.status) {
    nextOrder.status = message.status
  }
  if (message.paymentStatus) {
    nextOrder.payment_status = message.paymentStatus
  }
  if (message.refundStatus) {
    nextOrder.refund_status = message.refundStatus
    nextOrder.refund_request_status = message.refundStatus
  }

  // Reemplaza el pedido antiguo por la versión actualizada en la lista reactiva
  orders.value.splice(orderIndex, 1, nextOrder)
  // Refresca silenciosamente toda la lista para obtener campos derivados del servidor
  await loadOrders({ silent: true })
}

/**
 * Formatea un valor numérico como moneda colombiana (COP).
 * Ejemplo: 150000 → "$150.000"
 */
function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

/**
 * Formatea una fecha ISO a formato local colombiano (dd/mm/yyyy).
 * Retorna guion si no se proporciona valor.
 */
function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('es-CO')
}

/**
 * Retorna la etiqueta legible de un estado de pedido.
 * Usa la utilidad getOrderStatusLabel; si no hay coincidencia, muestra "Sin estado".
 */
function statusLabel(status) {
  return getOrderStatusLabel(status) || status || 'Sin estado'
}

/**
 * Determina la clase CSS del badge de estado según el valor normalizado del pedido.
 * Mapea cada estado a una clase CSS específica para colorear el badge.
 */
function statusClass(status) {
  // Normaliza el estado usando la utilidad centralizada
  const normalizedStatus = normalizeOrderStatus(status)
  // Versión en minúsculas con guiones bajos para comparaciones literales
  const rawStatus = String(status || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_')

  // Estados pendientes: se muestra en amarillo
  if (normalizedStatus === 'pending') return 'status-pending'
  // Estados de revisión y procesamiento: se muestran en color de proceso
  if (['in_review', 'processing'].includes(normalizedStatus)) return 'status-processing'
  // Estados de pago confirmado y envío: se usa el nombre raw como clase
  if (['paid', 'confirmed', 'shipped'].includes(rawStatus)) return `status-${rawStatus}`
  // Estados de entrega completada
  if (['delivered', 'completed'].includes(normalizedStatus)) return `status-${normalizedStatus}`
  // Estados finales negativos (cancelado, fallido, reembolsado): todos comparten la misma clase
  if (['cancelled', 'canceled', 'failed', 'refunded'].includes(rawStatus)) return 'status-cancelled'

  // Valor por defecto si no se reconoce el estado
  return 'status-processing'
}

/**
 * Genera la etiqueta de estado de pago para un pedido.
 * Prioriza el payment_status normalizado; si no existe, infiere desde el estado del pedido.
 */
function paymentStatusLabel(order) {
  // Normaliza el estado de pago del pedido
  const paymentStatus = normalizePaymentStatus(order?.payment_status)

  // Mapeo de estados de pago a etiquetas en español
  if (paymentStatus === 'verified' || paymentStatus === 'approved') return 'Pago verificado'
  if (paymentStatus === 'pending') return 'Pendiente de pago'
  if (paymentStatus === 'refund_requested') return 'Reembolso solicitado'
  if (paymentStatus === 'pending_refund') return 'Reembolso en proceso'
  if (paymentStatus === 'refunded') return 'Reembolsado'
  if (paymentStatus === 'paid') return 'Pagado'
  if (paymentStatus === 'failed' || paymentStatus === 'rejected') return 'Pago rechazado'
  // Si hay un estado de pago no mapeado, delega a la utilidad de presentación
  if (paymentStatus) return getPaymentStatusLabel(paymentStatus)

  // Fallback: infiere el estado de pago a partir del estado general del pedido
  const status = normalizeOrderStatus(order?.status)
  const rawStatus = String(order?.status || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_')
  // Si el pedido ya fue entregado o confirmado/pagado, se asume que el pago está completo
  if (['delivered', 'completed'].includes(status) || ['paid', 'confirmed'].includes(rawStatus)) {
    return 'Pagado'
  }

  // Por defecto se muestra como pendiente de pago
  return 'Pendiente de pago'
}

/**
 * Determina si un pedido puede ser cancelado por el usuario.
 * Solo permite cancelar pedidos que no estén ya cancelados y estén en estados iniciales.
 */
function canCancelOrder(order) {
  const status = normalizeStatus(order?.status)
  // No se puede cancelar un pedido que ya fue cancelado
  if (status === 'cancelled' || status === 'canceled') {
    return false
  }

  // Solo se permite cancelar en estados antes de que el pedido sea enviado
  return ['pending', 'processing', 'confirmed', 'paid'].includes(status)
}

/**
 * Determina si un pedido requiere proceso de reembolso al cancelarse.
 * Retorna true si el pago ya fue verificado/aprobado o si el estado del pedido indica pago confirmado.
 */
function requiresRefund(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  // Si el pago ya fue procesado positivamente, se necesita reembolso
  if (['paid', 'approved', 'verified'].includes(paymentStatus)) {
    return true
  }

  // Si no hay estado de pago explícito pero el pedido está marcado como pagado/confirmado
  const status = normalizeStatus(order?.status)
  return !paymentStatus && ['paid', 'confirmed'].includes(status)
}

/**
 * Muestra un modal de confirmación antes de cancelar un pedido.
 * Adapta el mensaje según si se requiere reembolso o no.
 */
function confirmCancelOrder(order) {
  // Evita doble clic y verifica que el pedido pueda ser cancelado
  if (!canCancelOrder(order) || cancellingOrderId.value) return

  const orderLabel = String(order?.order_number || `#${order?.id || ''}`)
  // Determina si la cancelación implica un proceso de reembolso
  const refundRequired = requiresRefund(order)

  // Muestra alerta modal con mensaje contextualizado
  showAlert({
    type: 'warning',
    title: 'Cancelar pedido',
    message: refundRequired
      ? `¿Deseas cancelar el pedido ${orderLabel}? Se iniciará el proceso de reembolso y te enviaremos confirmación por correo.`
      : `¿Deseas cancelar el pedido ${orderLabel}? Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Volver', style: 'secondary' },
      {
        text: 'Sí, cancelar pedido',
        style: 'danger',
        // Callback que se ejecuta al confirmar la cancelación
        callback: async () => {
          await submitOrderCancellation(order)
        },
      },
    ],
  })
}

/**
 * Envía la solicitud de cancelación del pedido a la API.
 * Muestra notificación de éxito o error según la respuesta del servidor.
 */
async function submitOrderCancellation(order) {
  // Evita cancelaciones concurrentes del mismo pedido
  if (cancellingOrderId.value) return

  // Marca el pedido como "en proceso de cancelación" para deshabilitar botones
  cancellingOrderId.value = order.id

  try {
    // Llama a la API de cancelación con datos del usuario y razón predefinida
    const response = await cancelOrder(order.id, {
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
      cancelled_by_name: String(user.value?.name || '').trim() || undefined,
      reason: 'Cancelación solicitada por el cliente desde Mis pedidos.',
    })

    // Muestra notificación de éxito con el mensaje del servidor
    showSnackbar({
      type: 'success',
      title: 'Pedido cancelado',
      message: String(response?.message || 'Tu pedido fue cancelado correctamente.'),
    })

    // Recarga la lista de pedidos para reflejar el cambio de estado
    await loadOrders()
  } catch (error) {
    // Extrae el mensaje de error de la respuesta de la API o usa uno genérico
    const apiMessage = String(
      error?.response?.data?.message
      || error?.response?.data?.error
      || 'No pudimos cancelar tu pedido. Intenta nuevamente.',
    ).trim()

    // Muestra notificación de error al usuario
    showSnackbar({
      type: 'error',
      title: 'No se pudo cancelar',
      message: apiMessage || 'No pudimos cancelar tu pedido. Intenta nuevamente.',
    })
  } finally {
    // Libera el lock de cancelación sin importar el resultado
    cancellingOrderId.value = null
  }
}

/**
 * Verifica si un pedido tiene reembolso disponible según la API.
 * El campo refund_available viene calculado en el backend.
 */
function canRequestRefund(order) {
  return Boolean(order?.refund_available)
}

/**
 * Abre el modal de solicitud de reembolso para un pedido específico.
 * Resetea el formulario y los errores previos.
 */
function openRefundModal(order) {
  activeRefundOrder.value = order
  // Limpia el formulario para evitar datos residuales de solicitudes anteriores
  refundForm.value = { reason: '', details: '', evidence: null }
  refundErrors.value = { reason: '', details: '' }
  refundModalOpen.value = true
}

/**
 * Cierra el modal de reembolso.
 * No permite cerrar si se está enviando una solicitud (evita pérdida de datos).
 */
function closeRefundModal() {
  if (submittingRefund.value) return
  refundModalOpen.value = false
  activeRefundOrder.value = null
}

/**
 * Valida un campo individual del formulario de reembolso.
 * Se ejecuta al cambiar el valor de un campo para mostrar errores en tiempo real.
 * @param {string} field - Nombre del campo a validar ('reason' o 'details').
 */
function validateRefundField(field) {
  // El motivo es obligatorio siempre
  if (field === 'reason') {
    refundErrors.value.reason = refundForm.value.reason ? '' : 'Selecciona un motivo de reembolso.'
  }

  // Los detalles solo son obligatorios cuando el motivo es "otros"
  if (field === 'details') {
    const requiresDetails = refundForm.value.reason === 'otros'
    // Si no se requieren detalles o el texto tiene al menos 8 caracteres, no hay error
    refundErrors.value.details = !requiresDetails || refundForm.value.details.trim().length >= 8
      ? ''
      : 'Cuéntanos el motivo con un poco más de detalle.'
  }
}

/**
 * Valida todos los campos del formulario de reembolso antes de enviar.
 * @returns {boolean} true si el formulario es válido, false si hay errores.
 */
function validateRefundForm() {
  // Valida cada campo individualmente
  validateRefundField('reason')
  validateRefundField('details')

  // Retorna true solo si no hay errores en ningún campo
  return !refundErrors.value.reason && !refundErrors.value.details
}

/**
 * Maneja la selección de archivo de evidencia para el reembolso.
 * Extrae el primer archivo seleccionado del input[type=file].
 */
function handleRefundEvidence(event) {
  refundForm.value.evidence = event.target.files?.[0] || null
}

/**
 * Envía la solicitud de reembolso a la API.
 * Construye un FormData con los datos del formulario, incluyendo archivo opcional.
 */
async function submitRefundRequest() {
  // Verifica que haya un pedido activo, que no se esté enviando ya, y que el formulario sea válido
  if (!activeRefundOrder.value || submittingRefund.value || !validateRefundForm()) return

  // Bloquea el envío duplicado
  submittingRefund.value = true

  try {
    // Construye el FormData para soportar el envío de archivos (evidencia)
    const body = new FormData()
    body.append('user_id', String(user.value?.id || '').trim())
    body.append('user_email', String(user.value?.email || '').trim())
    body.append('reason', refundForm.value.reason)
    body.append('details', refundForm.value.details.trim())
    // Solo adjunta evidencia si el usuario seleccionó un archivo
    if (refundForm.value.evidence) {
      body.append('evidence', refundForm.value.evidence)
    }

    // Envía la solicitud a la API
    const response = await requestOrderRefund(activeRefundOrder.value.id, body)
    // Muestra notificación de éxito
    showSnackbar({
      type: 'success',
      title: 'Reembolso solicitado',
      message: String(response?.message || 'Solicitud de reembolso enviada correctamente.'),
    })
    // Cierra el modal y recarga la lista de pedidos para reflejar el nuevo estado
    closeRefundModal()
    await loadOrders()
  } catch (error) {
    // Extrae el mensaje de error de la respuesta de la API o usa uno genérico
    const apiMessage = String(
      error?.response?.data?.message
      || error?.response?.data?.error
      || 'No pudimos enviar la solicitud de reembolso.',
    ).trim()

    // Muestra notificación de error al usuario
    showSnackbar({
      type: 'error',
      title: 'No se pudo solicitar',
      message: apiMessage || 'No pudimos enviar la solicitud de reembolso.',
    })
  } finally {
    // Desbloquea el envío sin importar el resultado
    submittingRefund.value = false
  }
}

/**
 * Normaliza un valor de estado: lo convierte a minúsculas, sin espacios extra.
 * Función auxiliar interna para comparaciones consistentes de estados.
 */
function normalizeStatus(value) {
  return String(value || '').trim().toLowerCase()
}

/**
 * Determina si un pedido está en flujo de reembolso.
 * Verifica tanto el payment_status como el refund_status/refund_request_status.
 */
function isRefundFlow(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  const refundStatus = normalizeStatus(order?.refund_status || order?.refund_request_status)
  // Retorna true si el estado de pago indica reembolso o si el estado de reembolso está activo
  return ['refund_requested', 'pending_refund', 'refunded'].includes(paymentStatus)
    || ['requested', 'processing', 'completed'].includes(refundStatus)
}

/**
 * Retorna los pasos de progreso adecuados según el flujo del pedido.
 * Si está en proceso de reembolso usa refundOrderSteps, de lo contrario defaultOrderSteps.
 */
function orderProgressSteps(order) {
  return isRefundFlow(order) ? refundOrderSteps : defaultOrderSteps
}

/**
 * Resuelve el estado normalizado del pedido para el flujo estándar de entrega.
 * Mapea estados equivalentes a los pasos de progreso predefinidos.
 */
function resolveStandardProgressStatus(order) {
  let normalizedStatus = normalizeStatus(order?.status)

  // "confirmed" y "paid" se consideran parte del paso "processing"
  if (normalizedStatus === 'confirmed' || normalizedStatus === 'paid') normalizedStatus = 'processing'
  // "in_review" (o variante en español) también se mapea a "processing"
  if (normalizedStatus === 'in_review' || normalizedStatus === 'en_revision') normalizedStatus = 'processing'
  // "completed" se equipara a "delivered" para el progreso visual
  if (normalizedStatus === 'completed') normalizedStatus = 'delivered'

  return normalizedStatus
}

/**
 * Resuelve el estado normalizado del pedido para el flujo de reembolso.
 * Determina en qué paso del proceso de reembolso se encuentra el pedido.
 */
function resolveRefundProgressStatus(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  const refundStatus = normalizeStatus(order?.refund_status || order?.refund_request_status)
  // Si el pago ya fue reembolsado completamente
  if (paymentStatus === 'refunded') {
    return 'refunded'
  }

  // Si el reembolso está en proceso (payment_status o refundStatus lo indican)
  if (paymentStatus === 'pending_refund' || refundStatus === 'processing') {
    return 'pending_refund'
  }

  // Por defecto se asume que la solicitud fue recibida pero aún no procesada
  return 'refund_requested'
}

/**
 * Determina si un paso específico de la barra de progreso está activo (completado o actual).
 * Un paso se marca como activo si su posición es menor o igual al estado actual del pedido.
 */
function isStepActive(order, stepKey) {
  // Define el flujo completo según el tipo de pedido
  const flow = isRefundFlow(order)
    ? ['refund_requested', 'pending_refund', 'refunded']
    : ['pending', 'processing', 'shipped', 'delivered']
  // Resuelve el estado actual del pedido al paso correspondiente del flujo
  const currentStatus = isRefundFlow(order)
    ? resolveRefundProgressStatus(order)
    : resolveStandardProgressStatus(order)

  // Encuentra las posiciones en el flujo del estado actual y del paso evaluado
  const currentIndex = flow.indexOf(currentStatus)
  const stepIndex = flow.indexOf(stepKey)

  // Si no se encuentra alguno de los valores, solo el primer paso está activo por defecto
  if (currentIndex < 0 || stepIndex < 0) {
    return stepKey === 'pending'
  }

  // Un paso está activo si está antes o en la posición del estado actual
  return stepIndex <= currentIndex
}
</script>

<!-- Estilos scoped: se aplican únicamente a este componente -->
<style scoped>
.order-highlight {
  border-color: #90e0ef;
  box-shadow: 0 0 0 2px rgba(0, 119, 182, 0.15);
}

.orders-v2-list {
  display: grid;
  gap: 1.2rem;
}

.order-v2-card {
  border: 1px solid #cfe0ec;
  border-radius: 14px;
  padding: 1.35rem;
  background: #fff;
}

.order-v2-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.order-v2-title h3 {
  margin: 0;
  font-size: 2rem;
}

.order-v2-date {
  margin-top: 0.45rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: #6b7280;
  font-size: 1.25rem;
}

.order-v2-metrics {
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.85rem;
}

.metric-item {
  border: 1px solid #cfe0ec;
  border-radius: 10px;
  background: #f8fcff;
  padding: 0.95rem 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.35rem;
  color: #334155;
}

.metric-item i {
  color: #0f7bb8;
}

.metric-total span {
  font-weight: 700;
}

.order-v2-address {
  margin-top: 0.85rem;
  border: 1px solid #cfe0ec;
  border-radius: 10px;
  padding: 0.9rem 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  color: #334155;
  background: #f9fcff;
}

.order-v2-address i {
  color: #0f7bb8;
}

.order-v2-actions {
  margin-top: 1rem;
  display: flex;
  gap: 0.75rem;
}

.btn-repeat-order {
  border: 0;
  background: #44ae42;
  color: #fff;
  border-radius: 10px;
  text-decoration: none;
  font-size: 1.4rem;
  font-weight: 700;
  padding: 0.95rem 1.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
}

.btn-repeat-order:hover {
  background: #369e34;
}

.btn-cancel-order {
  border: 1px solid #ef4444;
  background: #fff;
  color: #dc2626;
  border-radius: 10px;
  font-size: 1.4rem;
  font-weight: 700;
  padding: 0.95rem 1.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  cursor: pointer;
}

.btn-cancel-order:hover {
  background: #fff1f2;
}

.btn-cancel-order:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-refund-order {
  border: 1px solid #0f7bb8;
  background: #0f7bb8;
  color: #fff;
  border-radius: 10px;
  font-size: 1.4rem;
  font-weight: 700;
  padding: 0.95rem 1.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  cursor: pointer;
}

.btn-refund-order:hover {
  background: #0b679b;
}

.btn-refund-order:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.refund-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  background: rgba(15, 23, 42, 0.42);
  display: grid;
  place-items: center;
  padding: 1.6rem;
}

.refund-modal {
  width: min(560px, 100%);
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
  overflow: hidden;
}

.refund-modal__header,
.refund-modal__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.4rem 1.6rem;
  border-bottom: 1px solid #dbe5ef;
}

.refund-modal__header h3 {
  margin: 0;
  font-size: 1.9rem;
}

.refund-modal__header p {
  margin: 0.25rem 0 0;
  color: #64748b;
}

.refund-modal__header button {
  border: 0;
  background: #eef6fb;
  color: #0f7bb8;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  cursor: pointer;
}

.refund-modal__body {
  padding: 1.6rem;
  display: grid;
  gap: 0.85rem;
}

.refund-control {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 0.9rem 1rem;
  font-size: 1.35rem;
}

.refund-control.is-invalid {
  border-color: #ef4444;
}

.refund-modal__file {
  margin: 0;
  color: #0f7bb8;
  font-weight: 700;
}

.refund-modal__footer {
  border-top: 1px solid #dbe5ef;
  border-bottom: 0;
}

.order-v2-progress {
  position: relative;
  margin-top: 1rem;
  border: 1px solid #cfe0ec;
  border-radius: 14px;
  padding: 1.2rem 1rem 1rem;
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.progress-line {
  position: absolute;
  top: 27px;
  left: 8%;
  right: 8%;
  border-top: 3px solid #0f7bb8;
}

.progress-step {
  z-index: 1;
  display: grid;
  justify-items: center;
  gap: 0.55rem;
  min-width: 90px;
}

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

.progress-step-label {
  font-size: 1.25rem;
  color: #6b7280;
  font-weight: 700;
  text-align: center;
}

.progress-step.active .progress-step-icon {
  background: #44ae42;
  color: #fff;
}

.progress-step.active:last-child .progress-step-icon {
  background: #0f7bb8;
}

.progress-step.active .progress-step-label {
  color: #2f9f47;
}

.progress-step.active:last-child .progress-step-label {
  color: #0f7bb8;
}

.order-v2-progress--refund .progress-line {
  border-top-color: #d97706;
}

.order-v2-progress--refund .progress-step.active .progress-step-icon {
  background: #d97706;
}

.order-v2-progress--refund .progress-step.active .progress-step-label {
  color: #b45309;
}

.order-v2-progress--refund .progress-step.active:last-child .progress-step-icon {
  background: #16a34a;
}

.order-v2-progress--refund .progress-step.active:last-child .progress-step-label {
  color: #15803d;
}

@media (max-width: 980px) {
  .order-v2-header,
  .order-v2-actions {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-v2-metrics {
    grid-template-columns: 1fr;
  }

  .order-v2-progress {
    overflow-x: auto;
    justify-content: flex-start;
    gap: 1.2rem;
  }

  .progress-line {
    min-width: 420px;
    left: 35px;
    right: 35px;
  }
}
</style>
