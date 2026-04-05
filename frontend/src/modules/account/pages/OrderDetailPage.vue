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
      </section>

      <section class="account-card">
        <header class="section-header">
          <h2>Productos del pedido</h2>
        </header>

        <article v-for="item in orderItems" :key="item.id" class="order-item-row">
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

        <div class="order-v2-progress">
          <div class="progress-line" />
          <div
            v-for="step in orderSteps"
            :key="step.key"
            class="progress-step"
            :class="{ active: isStepActive(orderDetail.status, step.key) }"
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
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getOrderById } from '../../../services/orderApi'

const route = useRoute()

const loading = ref(true)
const errorMessage = ref('')
const orderDetail = ref(null)
const orderItems = ref([])
const orderHistory = ref([])

const orderSteps = Object.freeze([
  { key: 'pending', label: 'Pendiente', icon: 'fas fa-clock' },
  { key: 'processing', label: 'En proceso', icon: 'fas fa-cog' },
  { key: 'shipped', label: 'Enviado', icon: 'fas fa-truck' },
  { key: 'delivered', label: 'Entregado', icon: 'fas fa-check' },
])

onMounted(async () => {
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
})

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
  if (value === 'paid') return 'Pagado'
  if (value === 'rejected') return 'Rechazado'
  return 'Pendiente de pago'
}

function paymentBadgeClass(paymentStatus) {
  const value = String(paymentStatus || '').toLowerCase()
  if (value === 'paid') return 'status-delivered'
  if (value === 'rejected') return 'status-cancelled'
  return 'status-pending'
}

function isStepActive(currentStatus, stepKey) {
  const flow = ['pending', 'processing', 'shipped', 'delivered']
  let status = String(currentStatus || '').toLowerCase()

  if (status === 'confirmed' || status === 'paid') status = 'processing'
  if (status === 'completed') status = 'delivered'

  const currentIndex = flow.indexOf(status)
  const stepIndex = flow.indexOf(stepKey)

  if (currentIndex < 0 || stepIndex < 0) return stepKey === 'pending'
  return stepIndex <= currentIndex
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

.order-item-row {
  border: 1px solid #dbe3ed;
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
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
