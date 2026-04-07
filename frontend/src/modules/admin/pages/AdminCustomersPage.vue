<template>
  <div class="admin-customers-page">
    <AdminPageHeader
      icon="fas fa-users"
      title="Clientes"
      subtitle="Replica el flujo operativo de Angelow para consultar perfil, pedidos y estado del cliente desde microservicios."
      :breadcrumbs="[{ label: 'Clientes' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="hubStatsFormatted" />

    <!-- Filtros de búsqueda y segmentación -->
    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Busqueda y segmentacion"
      placeholder="Buscar por nombre, email o telefono..."
      @update:model-value="debouncedLoadCustomers"
      @search="loadCustomers()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="customer-state"><i class="fas fa-user-check"></i> Estado</label>
            <select id="customer-state" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="blocked">Bloqueados</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="customer-segment"><i class="fas fa-layer-group"></i> Segmento</label>
            <select id="customer-segment" v-model="filters.segment">
              <option value="all">Todos</option>
              <option value="repeat">Recurrentes</option>
              <option value="new">Nuevos 30 dias</option>
              <option value="without-orders">Sin pedidos</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <button type="button" class="admin-filters__clear" @click="clearAllFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar todo
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${customers.length} clientes`">
      <template #actions>
        <button class="btn-icon" type="button" @click="exportCustomers">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="customers.length === 0"
        icon="fas fa-users"
        title="Sin clientes"
        description="No se encontraron clientes con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table customers-table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Contacto</th>
              <th>Registro</th>
              <th>Pedidos</th>
              <th>Valor acumulado</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in customers" :key="customer.id">
              <td>
                <div class="customer-cell">
                  <img class="admin-avatar" :src="avatarUrl(customer)" :alt="customer.name" @error="onAvatarError($event, customer.image)">
                  <div class="admin-entity-name">
                    <strong>{{ customer.name }}</strong>
                    <span>{{ customer.email }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ customer.phone || 'Sin telefono' }}</strong>
                  <span>Ultimo acceso: {{ formatDateTime(customer.last_access) }}</span>
                </div>
              </td>
              <td>{{ formatDate(customer.created_at) }}</td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ customer.orders_count }}</strong>
                  <span>{{ customer.last_order_date ? `Ultimo pedido: ${formatDate(customer.last_order_date)}` : 'Sin pedidos' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(customer.total_spent) }}</strong></td>
              <td>
                <span class="status-badge" :class="customer.is_blocked ? 'cancelled' : 'active'">
                  {{ customer.is_blocked ? 'Bloqueado' : 'Activo' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver cliente" @click="openCustomerModal(customer)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button
                    class="action-btn"
                    :class="customer.is_blocked ? 'edit' : 'delete'"
                    type="button"
                    :title="customer.is_blocked ? 'Desbloquear cliente' : 'Bloquear cliente'"
                    @click="toggleCustomerBlock(customer)"
                  >
                    <i :class="customer.is_blocked ? 'fas fa-unlock' : 'fas fa-ban'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="selectedCustomer ? selectedCustomer.name : 'Detalle de cliente'" max-width="1080px" @close="closeCustomerModal">
      <template v-if="selectedCustomer">
        <div class="customer-detail-grid">
          <div>
            <AdminCard title="Perfil del cliente" icon="fas fa-id-card">
              <div class="customer-profile">
                <img class="admin-avatar admin-avatar--lg" :src="avatarUrl(selectedCustomer)" :alt="selectedCustomer.name" @error="onAvatarError($event, selectedCustomer.image)">
                <div class="customer-profile__body">
                  <h3>{{ selectedCustomer.name }}</h3>
                  <p>{{ selectedCustomer.email }}</p>
                  <span class="status-badge" :class="selectedCustomer.is_blocked ? 'cancelled' : 'active'">
                    {{ selectedCustomer.is_blocked ? 'Bloqueado' : 'Activo' }}
                  </span>
                </div>
              </div>

              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Telefono</span><strong>{{ selectedCustomer.phone || 'Sin telefono' }}</strong></div>
                <div class="admin-detail-row"><span>Registro</span><strong>{{ formatDate(selectedCustomer.created_at) }}</strong></div>
                <div class="admin-detail-row"><span>Ultimo acceso</span><strong>{{ formatDateTime(selectedCustomer.last_access) }}</strong></div>
                <div class="admin-detail-row"><span>Ultimo pedido</span><strong>{{ selectedCustomer.last_order_date ? formatDateTime(selectedCustomer.last_order_date) : 'Sin pedidos' }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Pedidos recientes" icon="fas fa-box" style="margin-top: 1.2rem;" :flush="true">
              <div v-if="selectedCustomer.recent_orders.length === 0" class="detail-empty">Este cliente aun no registra pedidos.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Orden</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Pago</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in selectedCustomer.recent_orders" :key="order.id">
                    <td>{{ order.order_number }}</td>
                    <td>{{ formatDate(order.created_at) }}</td>
                    <td>{{ formatCurrency(order.total) }}</td>
                    <td><span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                    <td><span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span></td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen comercial" icon="fas fa-chart-line">
              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Pedidos totales</span><strong>{{ selectedCustomer.orders_count }}</strong></div>
                <div class="admin-detail-row"><span>Pedidos completados</span><strong>{{ selectedCustomer.completed_orders }}</strong></div>
                <div class="admin-detail-row"><span>Pedidos pendientes</span><strong>{{ selectedCustomer.pending_orders }}</strong></div>
                <div class="admin-detail-divider"></div>
                <div class="admin-detail-row admin-detail-row--total"><span>Valor acumulado</span><strong>{{ formatCurrency(selectedCustomer.total_spent) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Segmentacion" icon="fas fa-bullseye" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Tipo</span><strong>{{ customerSegmentLabel(selectedCustomer) }}</strong></div>
                <div class="admin-detail-row"><span>Recompra</span><strong>{{ selectedCustomer.orders_count > 1 ? 'Si' : 'No' }}</strong></div>
                <div class="admin-detail-row"><span>Ticket promedio</span><strong>{{ formatCurrency(selectedCustomer.average_ticket) }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>

      <template #footer>
        <button
          v-if="selectedCustomer"
          class="btn"
          :class="selectedCustomer.is_blocked ? 'btn-primary' : 'btn-danger'"
          type="button"
          @click="toggleCustomerBlock(selectedCustomer)"
        >
          <i :class="selectedCustomer.is_blocked ? 'fas fa-unlock' : 'fas fa-ban'"></i>
          {{ selectedCustomer.is_blocked ? 'Desbloquear' : 'Bloquear' }} cliente
        </button>
        <button class="btn btn-secondary" type="button" @click="closeCustomerModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { authHttp, orderHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const showDetailModal = ref(false)
const orderRowsLoaded = ref(false)
const rawCustomers = ref([])
const rawOrders = ref([])
const selectedCustomerId = ref(null)

const filters = reactive({
  search: '',
  state: 'all',
  segment: 'all',
})

const customerMetricsMap = computed(() => {
  const metrics = new Map()

  rawOrders.value.forEach((order) => {
    const emailKey = normalizeEmail(order.user_email || order.customer_email)
    const idKey = normalizeIdentity(order.user_id)
    const keys = [emailKey ? `email:${emailKey}` : null, idKey ? `id:${idKey}` : null].filter(Boolean)

    if (keys.length === 0) {
      return
    }

    keys.forEach((key) => {
      if (!metrics.has(key)) {
        metrics.set(key, [])
      }
      metrics.get(key).push(order)
    })
  })

  return metrics
})

const enrichedCustomers = computed(() => rawCustomers.value.map(enrichCustomer))

const customers = computed(() => enrichedCustomers.value.filter((customer) => {
  if (filters.state === 'active' && customer.is_blocked) {
    return false
  }

  if (filters.state === 'blocked' && !customer.is_blocked) {
    return false
  }

  if (filters.segment === 'repeat' && customer.orders_count <= 1) {
    return false
  }

  if (filters.segment === 'new' && !isWithinLastDays(customer.created_at, 30)) {
    return false
  }

  if (filters.segment === 'without-orders' && customer.orders_count > 0) {
    return false
  }

  return true
}))

const selectedCustomer = computed(() => customers.value.find((customer) => customer.id === selectedCustomerId.value)
  || enrichedCustomers.value.find((customer) => customer.id === selectedCustomerId.value)
  || null)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.search.trim()) count++
  if (filters.state !== 'all') count++
  if (filters.segment !== 'all') count++
  return count
})

const hubStatsFormatted = computed(() => {
  const visibleCustomers = customers.value
  const buyers = visibleCustomers.filter((customer) => customer.orders_count > 0)
  const repeatCustomers = buyers.filter((customer) => customer.orders_count > 1)
  const repeatRate = buyers.length > 0 ? Math.round((repeatCustomers.length / buyers.length) * 100) : 0
  const ltvAverage = buyers.length > 0
    ? buyers.reduce((sum, customer) => sum + customer.total_spent, 0) / buyers.length
    : 0

  return [
    { key: 'total', label: 'Total clientes', value: String(visibleCustomers.length), icon: 'fas fa-users', color: 'primary' },
    { key: 'new', label: 'Nuevos (30 dias)', value: String(visibleCustomers.filter((customer) => isWithinLastDays(customer.created_at, 30)).length), icon: 'fas fa-user-plus', color: 'info' },
    { key: 'repeat', label: 'Tasa de recompra', value: `${repeatRate}%`, icon: 'fas fa-arrows-rotate', color: 'warning' },
    { key: 'ltv', label: 'LTV promedio', value: formatCurrency(ltvAverage), icon: 'fas fa-sack-dollar', color: 'success' },
    { key: 'active', label: 'Activos', value: String(visibleCustomers.filter((customer) => !customer.is_blocked).length), icon: 'fas fa-user-check', color: 'primary' },
  ]
})

function normalizeIdentity(value) {
  const normalized = String(value || '').trim()
  return normalized || null
}

function normalizeEmail(value) {
  const normalized = String(value || '').trim().toLowerCase()
  return normalized || null
}

function normalizeCustomer(customer) {
  return {
    ...customer,
    id: String(customer.id),
    name: customer.name || 'Cliente',
    email: customer.email || 'Sin email',
    phone: customer.phone || '',
    image: customer.image || '',
    is_blocked: Boolean(customer.is_blocked),
    created_at: customer.created_at || null,
    last_access: customer.last_access || null,
  }
}

function normalizeOrder(order) {
  return {
    ...order,
    id: Number(order.id),
    user_id: normalizeIdentity(order.user_id),
    user_email: order.user_email || order.customer_email || order.billing_email || '',
    order_number: order.order_number || `#${order.id}`,
    total: Number(order.total || 0),
    status: order.status || order.order_status || 'pending',
    payment_status: order.payment_status || 'pending',
    created_at: order.created_at || null,
  }
}

function enrichCustomer(customer) {
  const emailKey = normalizeEmail(customer.email)
  const idKey = normalizeIdentity(customer.id)
  const candidates = [
    emailKey ? `email:${emailKey}` : null,
    idKey ? `id:${idKey}` : null,
  ].filter(Boolean)

  const mergedOrders = []
  const seenOrderIds = new Set()

  candidates.forEach((key) => {
    const orders = customerMetricsMap.value.get(key) || []
    orders.forEach((order) => {
      if (seenOrderIds.has(order.id)) {
        return
      }
      seenOrderIds.add(order.id)
      mergedOrders.push(order)
    })
  })

  mergedOrders.sort((left, right) => new Date(right.created_at || 0) - new Date(left.created_at || 0))

  const totalSpent = mergedOrders.reduce((sum, order) => sum + Number(order.total || 0), 0)
  const completedOrders = mergedOrders.filter((order) => ['delivered', 'completed'].includes(order.status)).length
  const pendingOrders = mergedOrders.filter((order) => ['pending', 'processing', 'shipped'].includes(order.status)).length

  return {
    ...customer,
    orders_count: mergedOrders.length,
    total_spent: totalSpent,
    completed_orders: completedOrders,
    pending_orders: pendingOrders,
    average_ticket: mergedOrders.length > 0 ? totalSpent / mergedOrders.length : 0,
    last_order_date: mergedOrders[0]?.created_at || null,
    recent_orders: mergedOrders.slice(0, 5),
  }
}

function avatarUrl(customer) {
  return resolveMediaUrl(customer.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
}

function formatCurrency(value) {
  return `$ ${Number(value || 0).toLocaleString('es-CO')}`
}

function formatDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
}

function formatDateTime(value) {
  if (!value) return 'Sin registro'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin registro' : date.toLocaleString('es-CO')
}

function statusLabel(status) {
  const labels = {
    pending: 'Pendiente',
    processing: 'En proceso',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado',
    refunded: 'Reembolsado',
    completed: 'Completado',
  }
  return labels[status] || 'Pendiente'
}

function paymentLabel(status) {
  const labels = {
    pending: 'Pendiente',
    paid: 'Pagado',
    verified: 'Verificado',
    failed: 'Fallido',
    refunded: 'Reembolsado',
    rejected: 'Rechazado',
  }
  return labels[status] || 'Pendiente'
}

function statusBadgeClass(status) {
  if (['delivered', 'completed'].includes(status)) return 'active'
  if (['cancelled', 'refunded'].includes(status)) return 'cancelled'
  return 'pending'
}

function paymentBadgeClass(status) {
  if (['paid', 'verified'].includes(status)) return 'active'
  if (['failed', 'refunded', 'rejected'].includes(status)) return 'cancelled'
  return 'pending'
}

function isWithinLastDays(value, days) {
  if (!value) {
    return false
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return false
  }

  const threshold = new Date()
  threshold.setDate(threshold.getDate() - days)
  return date >= threshold
}

function customerSegmentLabel(customer) {
  if (customer.orders_count > 1) return 'Recurrente'
  if (isWithinLastDays(customer.created_at, 30)) return 'Nuevo'
  if (customer.orders_count === 0) return 'Prospecto'
  return 'Ocasional'
}

function clearAllFilters() {
  filters.search = ''
  filters.state = 'all'
  filters.segment = 'all'
  loadCustomers()
}

let debounceTimer = null
function debouncedLoadCustomers() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadCustomers()
  }, 450)
}

async function loadCustomers(refreshOrders = false) {
  loading.value = true

  try {
    const customerRequest = authHttp.get('/admin/customers', {
      params: { search: filters.search.trim() || undefined },
    })

    const orderRequest = refreshOrders || !orderRowsLoaded.value
      ? orderHttp.get('/admin/orders', { params: { limit: 500 } })
      : Promise.resolve({ data: { data: { rows: rawOrders.value } } })

    const [customerResponse, orderResponse] = await Promise.all([customerRequest, orderRequest])

    const customerRows = customerResponse.data?.data || customerResponse.data || []
    rawCustomers.value = (Array.isArray(customerRows) ? customerRows : customerRows.data || []).map(normalizeCustomer)

    if (refreshOrders || !orderRowsLoaded.value) {
      const orderPayload = orderResponse.data?.data || {}
      const orderRows = Array.isArray(orderPayload) ? orderPayload : (orderPayload.rows || [])
      rawOrders.value = orderRows.map(normalizeOrder)
      orderRowsLoaded.value = true
    }
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando clientes' })
  } finally {
    loading.value = false
  }
}

function openCustomerModal(customer) {
  selectedCustomerId.value = customer.id
  showDetailModal.value = true
}

function closeCustomerModal() {
  showDetailModal.value = false
}

function syncSelectedCustomer() {
  if (!selectedCustomerId.value) {
    return
  }

  const match = enrichedCustomers.value.find((customer) => customer.id === selectedCustomerId.value)
  if (!match) {
    selectedCustomerId.value = null
    showDetailModal.value = false
  }
}

function toggleCustomerBlock(customer) {
  const actionLabel = customer.is_blocked ? 'desbloquear' : 'bloquear'

  showAlert({
    type: 'warning',
    title: `${customer.is_blocked ? 'Desbloquear' : 'Bloquear'} cliente`,
    message: `¿Deseas ${actionLabel} a ${customer.name}?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: customer.is_blocked ? 'Desbloquear' : 'Bloquear',
        style: 'primary',
        callback: async () => {
          try {
            await authHttp.patch(`/admin/customers/${customer.id}/block`)
            showSnackbar({ type: 'success', message: `Cliente ${customer.is_blocked ? 'desbloqueado' : 'bloqueado'} correctamente` })
            await loadCustomers()
            syncSelectedCustomer()
          } catch {
            showSnackbar({ type: 'error', message: 'Error actualizando el estado del cliente' })
          }
        },
      },
    ],
  })
}

function exportCustomers() {
  if (customers.value.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay clientes para exportar' })
    return
  }

  const rows = [['Cliente', 'Email', 'Telefono', 'Registro', 'Pedidos', 'Valor acumulado', 'Estado']]
  customers.value.forEach((customer) => {
    rows.push([
      `"${customer.name.replace(/"/g, '""')}"`,
      `"${customer.email.replace(/"/g, '""')}"`,
      `"${(customer.phone || '').replace(/"/g, '""')}"`,
      `"${formatDate(customer.created_at)}"`,
      customer.orders_count,
      customer.total_spent,
      `"${customer.is_blocked ? 'Bloqueado' : 'Activo'}"`,
    ])
  })

  const csv = rows.map((row) => row.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'clientes.csv'
  link.click()
  URL.revokeObjectURL(url)
  showSnackbar({ type: 'success', message: 'Clientes exportados correctamente' })
}

onMounted(() => {
  loadCustomers(true)
})
</script>

<style scoped>
/* Estilos específicos de Clientes — los comunes están en admin.css */

/* Celda de cliente con avatar + texto */
.customer-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Perfil dentro del modal de detalle */
.customer-profile {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.customer-profile__body {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.customer-profile__body h3 {
  margin: 0;
}

.customer-profile__body p {
  color: var(--admin-text-light);
  font-size: 1.2rem;
  margin: 0;
}

/* Grid del modal de detalle de cliente (2 columnas) */
.customer-detail-grid {
  display: grid;
  grid-template-columns: 1.8fr 1fr;
  gap: 1.6rem;
}

/* Celda vacía en detalle de pedidos */
.detail-empty {
  padding: 1.6rem;
  color: var(--admin-text-light);
}

@media (max-width: 900px) {
  .customer-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
