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
        </div>

        <div class="order-v2-progress">
          <div class="progress-line" />
          <div
            v-for="step in orderSteps"
            :key="`${order.id}-${step.key}`"
            class="progress-step"
            :class="{ active: isStepActive(order.status, step.key) }"
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
import { getOrders } from '../../../services/orderApi'
import { useSession } from '../../../composables/useSession'

const route = useRoute()
const { user, isLoggedIn } = useSession()

const loading = ref(true)
const errorMessage = ref('')
const orders = ref([])

const orderSteps = Object.freeze([
  { key: 'pending', label: 'Pendiente', icon: 'fas fa-clock' },
  { key: 'processing', label: 'En proceso', icon: 'fas fa-cog' },
  { key: 'shipped', label: 'Enviado', icon: 'fas fa-truck' },
  { key: 'delivered', label: 'Entregado', icon: 'fas fa-check' },
])

const selectedOrderId = computed(() => String(route.query.order || '').trim())

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isLoggedIn.value) {
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
})

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
  if (paymentStatus === 'paid') return 'Pagado'
  if (paymentStatus === 'rejected') return 'Rechazado'
  if (paymentStatus === 'pending') return 'Pendiente de pago'

  const status = String(order?.status || '').toLowerCase().trim()
  if (['delivered', 'completed', 'paid', 'confirmed'].includes(status)) {
    return 'Pagado'
  }

  return 'Pendiente de pago'
}

function isStepActive(currentStatus, stepKey) {
  const flow = ['pending', 'processing', 'shipped', 'delivered']
  const status = String(currentStatus || '').toLowerCase()

  let normalizedStatus = status
  if (normalizedStatus === 'confirmed' || normalizedStatus === 'paid') normalizedStatus = 'processing'
  if (normalizedStatus === 'completed') normalizedStatus = 'delivered'

  const currentIndex = flow.indexOf(normalizedStatus)
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
