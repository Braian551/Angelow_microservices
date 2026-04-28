<template>
  <section class="dashboard-header">
    <h1>Mis pedidos</h1>
    <p>Consulta el estado y detalle de todas tus órdenes.</p>
  </section>

  <section class="account-card">
    <header class="section-header">
      <h2>Historial de pedidos</h2>
    </header>

    <p v-if="loading" class="loading-box">Cargando pedidos...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <div v-else-if="orders.length === 0" class="empty-state">
      <i class="fas fa-box-open" />
      <p>No tienes pedidos aún.</p>
      <RouterLink :to="{ name: 'store' }" class="btn-primary-small">Comprar ahora</RouterLink>
    </div>

    <div v-else class="orders-v2-list">
      <article
        v-for="order in orders"
        :key="order.id"
        class="order-v2-card"
        :class="{ 'order-highlight': String(selectedOrderId) === String(order.id) }"
      >
        <div class="order-v2-header">
          <div class="order-v2-title">
            <h3>Pedido #{{ order.order_number }}</h3>
            <span class="order-v2-date">
              <i class="fas fa-calendar-alt" /> {{ formatDate(order.created_at) }}
            </span>
          </div>
          <span class="status-badge" :class="statusClass(order.status)">
            {{ statusLabel(order.status) }}
          </span>
        </div>

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

        <div v-if="order.shipping_address" class="order-v2-address">
          <i class="fas fa-map-marker-alt" />
          <span>{{ order.shipping_address }}</span>
        </div>

        <div class="order-v2-actions">
          <RouterLink :to="{ name: 'account-order-detail', params: { id: order.id } }" class="btn-view-order">
            <i class="fas fa-eye" /> Ver detalles
          </RouterLink>
          <RouterLink :to="{ name: 'store' }" class="btn-repeat-order">
            <i class="fas fa-redo-alt" /> Volver a pedir
          </RouterLink>
          <button
            v-if="canCancelOrder(order)"
            type="button"
            class="btn-cancel-order"
            :disabled="cancellingOrderId === order.id"
            @click="confirmCancelOrder(order)"
          >
            <i class="fas fa-ban" />
            {{ cancellingOrderId === order.id ? 'Cancelando...' : 'Cancelar pedido' }}
          </button>
        </div>

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
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { cancelOrder, getOrders } from '../../../services/orderApi'
import { useSession } from '../../../composables/useSession'

const route = useRoute()
const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const errorMessage = ref('')
const orders = ref([])
const cancellingOrderId = ref(null)

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

const selectedOrderId = computed(() => String(route.query.order || '').trim())

onMounted(async () => {
  await loadOrders()
})

async function loadOrders() {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isLoggedIn.value) {
      orders.value = []
      return
    }

    const response = await getOrders({
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
    })
    orders.value = Array.isArray(response?.data) ? response.data : []
  } catch {
    errorMessage.value = 'No se pudieron cargar los pedidos.'
  } finally {
    loading.value = false
  }
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('es-CO')
}

function statusLabel(status) {
  const value = String(status || '').toLowerCase()

  if (value === 'pending') return 'Pendiente'
  if (value === 'processing') return 'Procesando'
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

function paymentStatusLabel(order) {
  const paymentStatus = String(order?.payment_status || '').toLowerCase().trim()

  if (paymentStatus === 'verified' || paymentStatus === 'approved') return 'Pago verificado'
  if (paymentStatus === 'pending_refund') return 'Reembolso en proceso'
  if (paymentStatus === 'refunded') return 'Reembolsado'
  if (paymentStatus === 'paid') return 'Pagado'
  if (paymentStatus === 'failed' || paymentStatus === 'rejected') return 'Pago rechazado'
  if (paymentStatus === 'pending') return 'Pendiente de pago'

  const status = String(order?.status || '').toLowerCase().trim()
  if (['delivered', 'completed', 'paid', 'confirmed'].includes(status)) {
    return 'Pagado'
  }

  return 'Pendiente de pago'
}

function canCancelOrder(order) {
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

function confirmCancelOrder(order) {
  if (!canCancelOrder(order) || cancellingOrderId.value) return

  const orderLabel = String(order?.order_number || `#${order?.id || ''}`)
  const refundRequired = requiresRefund(order)

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
        callback: async () => {
          await submitOrderCancellation(order)
        },
      },
    ],
  })
}

async function submitOrderCancellation(order) {
  if (cancellingOrderId.value) return

  cancellingOrderId.value = order.id

  try {
    const response = await cancelOrder(order.id, {
      user_id: String(user.value?.id || '').trim() || undefined,
      user_email: String(user.value?.email || '').trim() || undefined,
      cancelled_by_name: String(user.value?.name || '').trim() || undefined,
      reason: 'Cancelación solicitada por el cliente desde Mis pedidos.',
    })

    showSnackbar({
      type: 'success',
      title: 'Pedido cancelado',
      message: String(response?.message || 'Tu pedido fue cancelado correctamente.'),
    })

    await loadOrders()
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
    cancellingOrderId.value = null
  }
}

function normalizeStatus(value) {
  return String(value || '').trim().toLowerCase()
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
  let normalizedStatus = normalizeStatus(order?.status)

  if (normalizedStatus === 'confirmed' || normalizedStatus === 'paid') normalizedStatus = 'processing'
  if (normalizedStatus === 'completed') normalizedStatus = 'delivered'

  return normalizedStatus
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

  if (currentIndex < 0 || stepIndex < 0) {
    return stepKey === 'pending'
  }

  return stepIndex <= currentIndex
}
</script>

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
