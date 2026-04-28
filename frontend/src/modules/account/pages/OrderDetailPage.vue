<template>
  <section class="order-detail-page">
    <section class="dashboard-header">
      <h1>Detalle de pedido</h1>
      <p>Consulta los datos completos de tu orden.</p>
    </section>

    <p v-if="loading" class="loading-box">Cargando detalle del pedido...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <template v-else-if="orderDetail">
      <section class="order-hero account-card">
        <h2>Pedido #{{ orderDetail.order_number }}</h2>
        <p><i class="fas fa-calendar-alt" /> Realizado el {{ formatDate(orderDetail.created_at) }}</p>

        <div class="hero-badges">
          <span class="status-badge" :class="statusClass(orderDetail.status)">
            {{ statusLabel(orderDetail.status) }}
          </span>
          <span class="status-badge" :class="paymentBadgeClass(orderDetail.payment_status)">
            {{ paymentStatusLabel(orderDetail.payment_status) }}
          </span>
        </div>

        <div v-if="canCancelCurrentOrder" class="order-hero-actions">
          <button type="button" class="btn-cancel-detail" :disabled="cancellingOrder" @click="confirmCancelCurrentOrder">
            <i class="fas fa-ban" />
            {{ cancellingOrder ? 'Cancelando...' : 'Cancelar pedido' }}
          </button>
        </div>
      </section>

      <section class="account-card">
        <header class="section-header">
          <h2>Productos del pedido</h2>
        </header>

        <article v-for="item in orderItems" :key="item.id" class="order-item-row">
          <div class="order-item-media">
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

      <section class="account-card">
        <header class="section-header">
          <h2>Estado del pedido</h2>
        </header>

        <div
          class="order-v2-progress"
          :class="{ 'order-v2-progress--refund': isRefundFlow(orderDetail) }"
        >
          <div class="progress-line" />
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

      <section v-if="orderHistory.length > 0" class="account-card">
        <header class="section-header">
          <h2>Historial de cambios</h2>
        </header>

        <ul class="history-list">
          <li v-for="entry in orderHistory" :key="entry.id">
            <span>{{ formatDate(entry.created_at) }}</span>
            <strong>{{ entry.description || 'Actualización de pedido' }}</strong>
          </li>
        </ul>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSession } from '../../../composables/useSession'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { cancelOrder, getOrderById } from '../../../services/orderApi'

const route = useRoute()
const { user } = useSession()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const errorMessage = ref('')
const orderDetail = ref(null)
const orderItems = ref([])
const orderHistory = ref([])
const cancellingOrder = ref(false)

const defaultOrderSteps = Object.freeze([
  { key: 'pending', label: 'Pendiente', icon: 'fas fa-clock' },
  { key: 'processing', label: 'En proceso', icon: 'fas fa-cog' },
  { key: 'shipped', label: 'Enviado', icon: 'fas fa-truck' },
  { key: 'delivered', label: 'Entregado', icon: 'fas fa-check' },
])

const refundOrderSteps = Object.freeze([
  { key: 'cancelled', label: 'Cancelado', icon: 'fas fa-ban' },
  { key: 'pending_refund', label: 'Reembolso en proceso', icon: 'fas fa-rotate' },
  { key: 'refunded', label: 'Reembolsado', icon: 'fas fa-hand-holding-usd' },
])

const canCancelCurrentOrder = computed(() => isOrderCancelable(orderDetail.value))

onMounted(async () => {
  await loadOrderDetail()
})

async function loadOrderDetail() {
  loading.value = true
  errorMessage.value = ''

  try {
    const orderId = Number(route.params.id)
    if (!Number.isFinite(orderId) || orderId <= 0) {
      throw new Error('invalid_order_id')
    }

    const response = await getOrderById(orderId)
    orderDetail.value = response?.order || null
    orderItems.value = Array.isArray(response?.items) ? response.items : []
    orderHistory.value = Array.isArray(response?.history) ? response.history : []

    if (!orderDetail.value) {
      errorMessage.value = 'No se encontró el pedido solicitado.'
    }
  } catch {
    errorMessage.value = 'No se pudo cargar el detalle del pedido.'
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) return '-'

  const date = new Date(value)
  return date.toLocaleString('es-CO')
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function statusLabel(status) {
  const value = String(status || '').toLowerCase()

  if (value === 'pending') return 'Pendiente'
  if (value === 'processing') return 'En proceso'
  if (value === 'paid') return 'Pagado'
  if (value === 'confirmed') return 'Confirmado'
  if (value === 'shipped') return 'Enviado'
  if (value === 'delivered') return 'Entregado'
  if (value === 'completed') return 'Completado'
  if (value === 'cancelled') return 'Cancelado'
  if (value === 'failed') return 'Fallido'

  return status || 'Sin estado'
}

function statusClass(status) {
  const value = String(status || '').toLowerCase()

  if (['pending', 'processing'].includes(value)) return `status-${value}`
  if (['paid', 'confirmed', 'shipped'].includes(value)) return `status-${value}`
  if (['delivered', 'completed'].includes(value)) return `status-${value}`
  if (['cancelled', 'failed'].includes(value)) return `status-${value}`

  return 'status-processing'
}

function paymentStatusLabel(paymentStatus) {
  const value = String(paymentStatus || '').toLowerCase()

  if (value === 'verified' || value === 'approved') return 'Pago verificado'
  if (value === 'pending_refund') return 'Reembolso en proceso'
  if (value === 'refunded') return 'Reembolsado'
  if (value === 'paid') return 'Pagado'
  if (value === 'failed' || value === 'rejected') return 'Pago rechazado'
  if (value === 'pending') return 'Pendiente de pago'
  return 'Pendiente de pago'
}

function paymentBadgeClass(paymentStatus) {
  const value = String(paymentStatus || '').toLowerCase()

  if (value === 'verified' || value === 'approved') return 'status-delivered'
  if (value === 'paid') return 'status-delivered'
  if (value === 'pending_refund') return 'status-processing'
  if (value === 'refunded') return 'status-cancelled'
  if (value === 'failed' || value === 'rejected') return 'status-cancelled'
  return 'status-pending'
}

function isRefundFlow(order) {
  const status = normalizeStatus(order?.status)
  if (!['cancelled', 'canceled'].includes(status)) {
    return false
  }

  const paymentStatus = normalizeStatus(order?.payment_status)
  return ['pending_refund', 'refunded', 'paid', 'verified', 'approved'].includes(paymentStatus)
}

function orderProgressSteps(order) {
  return isRefundFlow(order) ? refundOrderSteps : defaultOrderSteps
}

function resolveStandardProgressStatus(order) {
  let status = normalizeStatus(order?.status)

  if (status === 'confirmed' || status === 'paid') status = 'processing'
  if (status === 'completed') status = 'delivered'

  return status
}

function resolveRefundProgressStatus(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  if (paymentStatus === 'refunded') {
    return 'refunded'
  }

  if (['pending_refund', 'paid', 'verified', 'approved'].includes(paymentStatus)) {
    return 'pending_refund'
  }

  return 'cancelled'
}

function isStepActive(order, stepKey) {
  const flow = isRefundFlow(order)
    ? ['cancelled', 'pending_refund', 'refunded']
    : ['pending', 'processing', 'shipped', 'delivered']
  const currentStatus = isRefundFlow(order)
    ? resolveRefundProgressStatus(order)
    : resolveStandardProgressStatus(order)

  const currentIndex = flow.indexOf(currentStatus)
  const stepIndex = flow.indexOf(stepKey)

  if (currentIndex < 0 || stepIndex < 0) return stepKey === 'pending'
  return stepIndex <= currentIndex
}

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

function resolveOrderItemImage(item = {}) {
  return resolveMediaUrl(resolveOrderItemImagePath(item), 'product')
}

function onOrderItemImageError(event, item) {
  handleMediaError(event, resolveOrderItemImagePath(item), 'product')
}

function isOrderCancelable(order) {
  const status = normalizeStatus(order?.status)
  if (status === 'cancelled' || status === 'canceled') {
    return false
  }

  return ['pending', 'processing', 'confirmed', 'paid'].includes(status)
}

function requiresRefund(order) {
  const paymentStatus = normalizeStatus(order?.payment_status)
  if (['paid', 'approved', 'verified'].includes(paymentStatus)) {
    return true
  }

  const status = normalizeStatus(order?.status)
  return !paymentStatus && ['paid', 'confirmed'].includes(status)
}

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
        callback: async () => {
          await submitCancellationFromDetail()
        },
      },
    ],
  })
}

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

    await loadOrderDetail()
  } catch (error) {
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

function normalizeStatus(value) {
  return String(value || '').trim().toLowerCase()
}
</script>

<style scoped>
.order-detail-page {
  display: grid;
  gap: 1.2rem;
}

.order-hero h2 {
  margin: 0;
  font-size: 2.9rem;
}

.order-hero p {
  margin: 0.65rem 0 0;
  color: #6b7280;
  font-size: 1.4rem;
  display: inline-flex;
  gap: 0.5rem;
  align-items: center;
}

.hero-badges {
  margin-top: 1rem;
  display: flex;
  gap: 0.7rem;
}

.order-hero-actions {
  margin-top: 1rem;
}

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

.btn-cancel-detail:hover {
  background: #fff1f2;
}

.btn-cancel-detail:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.order-item-row {
  border: 1px solid #dbe3ed;
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.order-item-media {
  width: 8.2rem;
  height: 8.2rem;
  border-radius: 12px;
  border: 1px solid #dbe3ed;
  overflow: hidden;
  flex-shrink: 0;
  background: #f8fafc;
}

.order-item-media__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.item-main {
  flex: 1;
  min-width: 0;
}

.item-main h3 {
  margin: 0;
  font-size: 2rem;
}

.item-main p {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 1.3rem;
}

.item-total {
  font-size: 2.8rem;
  color: #0077b6;
}

.order-v2-progress {
  position: relative;
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

.history-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.75rem;
}

.history-list li {
  border: 1px solid #dbe3ed;
  border-radius: 10px;
  padding: 0.85rem 1rem;
  display: grid;
  gap: 0.35rem;
}

.history-list span {
  color: #6b7280;
  font-size: 1.2rem;
}

.history-list strong {
  font-size: 1.35rem;
  color: #1f2937;
}

@media (max-width: 900px) {
  .order-item-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-item-media {
    width: 100%;
    max-width: 12rem;
    height: 12rem;
  }

  .item-total {
    font-size: 2.3rem;
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
