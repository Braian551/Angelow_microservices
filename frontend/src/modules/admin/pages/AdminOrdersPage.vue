<template>
  <div class="admin-orders-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      title="Gestión de Órdenes"
      subtitle="Administra pedidos, revisa el historial y cambia estados con el mismo flujo operativo de Angelow."
      :breadcrumbs="[{ label: 'Órdenes' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-sliders-h"></i>
            <h3>Filtros de búsqueda</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="filters.search" type="text" class="search-input" placeholder="Buscar por N° orden, cliente o email..." @input="debouncedLoad">
          <button v-if="filters.search" type="button" class="search-clear" @click="clearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button type="button" class="search-submit-btn" @click="applyFilters">
          <i class="fas fa-search"></i>
          <span>Buscar</span>
        </button>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--orders">
          <div class="filter-group">
            <label for="status-filter"><i class="fas fa-tag"></i> Estado de orden</label>
            <select id="status-filter" v-model="filters.status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="processing">En proceso</option>
              <option value="shipped">Enviado</option>
              <option value="delivered">Entregado</option>
              <option value="cancelled">Cancelado</option>
              <option value="refunded">Reembolsado</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="payment-filter"><i class="fas fa-credit-card"></i> Estado de pago</label>
            <select id="payment-filter" v-model="filters.payment_status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="paid">Pagado</option>
              <option value="verified">Verificado</option>
              <option value="failed">Fallido</option>
              <option value="refunded">Reembolso</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="from-date"><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input id="from-date" v-model="filters.from_date" type="date" class="filter-input" @change="validateDateRangeAndApply">
          </div>

          <div class="filter-group">
            <label for="to-date"><i class="fas fa-calendar-check"></i> Fecha hasta</label>
            <input id="to-date" v-model="filters.to_date" type="date" class="filter-input" @change="validateDateRangeAndApply">
          </div>
        </div>

        <div class="filters-actions-bar">
          <div class="active-filters">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar todo
            </button>
            <button type="button" class="btn-apply-filters" @click="applyFilters">
              <i class="fas fa-check-circle"></i>
              Aplicar filtros
            </button>
          </div>
        </div>
      </div>
    </AdminCard>

    <div class="results-summary">
      <div class="results-info">
        <i class="fas fa-list-ul"></i>
        <p>Mostrando {{ orders.length }} órdenes</p>
      </div>
      <div class="quick-actions">
        <button class="btn btn-icon" type="button" @click="exportOrders">
          <i class="fas fa-file-export"></i> Exportar
        </button>
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="orders.length === 0"
        icon="fas fa-inbox"
        title="Sin órdenes"
        description="No se encontraron órdenes con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table orders-table">
          <thead>
            <tr>
              <th>N° Orden</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id">
              <td>
                <div class="order-number-cell">
                  <strong>{{ order.order_number || `#${order.id}` }}</strong>
                  <span>ID interno: {{ order.id }}</span>
                </div>
              </td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ order.customer_name }}</strong>
                  <span>{{ order.customer_email || 'Sin email' }}</span>
                </div>
              </td>
              <td>{{ formatDate(order.created_at) }}</td>
              <td><strong>{{ formatCurrency(order.total) }}</strong></td>
              <td>
                <button type="button" class="status-chip-button" @click="openStatusModal(order)">
                  <span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span>
                </button>
              </td>
              <td>
                <span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span>
              </td>
              <td>
                <div class="entity-actions">
                  <button class="action-btn view" type="button" title="Vista rápida" @click="openDetailModal(order)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <RouterLink :to="{ name: 'admin-order-detail', params: { id: order.id } }" class="action-btn edit" title="Ir al detalle completo">
                    <i class="fas fa-arrow-right"></i>
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="selectedOrder ? `Orden ${selectedOrder.order_number || `#${selectedOrder.id}`}` : 'Detalle de orden'" max-width="1120px" @close="closeDetailModal">
      <div v-if="detailLoading" class="detail-loading">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </div>
      <template v-else-if="detailOrder">
        <div class="order-detail-grid">
          <div>
            <AdminCard title="Items del pedido" icon="fas fa-box" :flush="true">
              <div v-if="detailOrder.items.length === 0" class="detail-empty">Sin items registrados.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in detailOrder.items" :key="item.id">
                    <td>{{ item.product_name || item.name || 'Producto' }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ formatCurrency(item.unit_price || item.price || 0) }}</td>
                    <td>{{ formatCurrency((item.unit_price || item.price || 0) * item.quantity) }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>

            <AdminCard title="Historial" icon="fas fa-clock-rotate-left" style="margin-top: 1.2rem;" :flush="true">
              <div v-if="detailOrder.history.length === 0" class="detail-empty">Sin movimientos registrados.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Cambio</th>
                    <th>Responsable</th>
                    <th>Detalle</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="entry in detailOrder.history" :key="entry.id">
                    <td>{{ formatDateTime(entry.created_at) }}</td>
                    <td>{{ entry.old_value || '-' }} -> {{ entry.new_value || '-' }}</td>
                    <td>{{ entry.changed_by_name || entry.changed_by || 'Sistema' }}</td>
                    <td>{{ entry.description || 'Sin descripción' }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen" icon="fas fa-calculator">
              <div class="summary-stack">
                <div class="summary-row"><span>Subtotal</span><strong>{{ formatCurrency(detailOrder.order.subtotal || 0) }}</strong></div>
                <div class="summary-row"><span>Envío</span><strong>{{ formatCurrency(detailOrder.order.shipping_cost || 0) }}</strong></div>
                <div v-if="Number(detailOrder.order.discount_amount || 0) > 0" class="summary-row summary-row--success"><span>Descuento</span><strong>-{{ formatCurrency(detailOrder.order.discount_amount || 0) }}</strong></div>
                <div class="summary-divider"></div>
                <div class="summary-row summary-row--total"><span>Total</span><strong>{{ formatCurrency(detailOrder.order.total || 0) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Cliente" icon="fas fa-user" style="margin-top: 1.2rem;">
              <div class="summary-stack">
                <div class="summary-row summary-row--stack"><span>{{ detailOrder.customer_name }}</span><strong>{{ detailOrder.customer_email || 'Sin email' }}</strong></div>
                <div class="summary-row summary-row--stack"><span>Dirección</span><strong>{{ detailOrder.order.shipping_address || detailOrder.order.billing_address || 'Sin dirección' }}</strong></div>
                <div class="summary-row"><span>Pago</span><strong>{{ paymentLabel(detailOrder.order.payment_status) }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <RouterLink v-if="selectedOrder" :to="{ name: 'admin-order-detail', params: { id: selectedOrder.id } }" class="btn btn-primary" @click="closeDetailModal">
          <i class="fas fa-arrow-right"></i> Ver detalle completo
        </RouterLink>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>

    <AdminModal :show="showStatusModal" title="Actualizar estado de la orden" max-width="560px" @close="closeStatusModal">
      <div class="status-form-grid">
        <div class="form-group status-form-grid__full">
          <label>Orden seleccionada</label>
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selección' }}</div>
        </div>

        <div class="form-group">
          <label for="order-status">Estado *</label>
          <select id="order-status" v-model="statusForm.status" class="form-control" @change="validateStatusField('status')">
            <option value="pending">Pendiente</option>
            <option value="processing">En proceso</option>
            <option value="shipped">Enviado</option>
            <option value="delivered">Entregado</option>
            <option value="cancelled">Cancelado</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="statusErrors.status" class="form-error">{{ statusErrors.status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="status-description">Descripción del cambio *</label>
          <textarea id="status-description" v-model="statusForm.description" class="form-control" rows="4" :class="{ 'is-invalid': statusErrors.description }" @input="validateStatusField('description')"></textarea>
          <p v-if="statusErrors.description" class="form-error">{{ statusErrors.description }}</p>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="submitStatusChange">Guardar cambio</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const detailLoading = ref(false)
const showAdvanced = ref(false)
const orders = ref([])
const orderStats = ref({ total_orders: 0, total_revenue: 0, pending_orders: 0, completed_orders: 0 })
const showDetailModal = ref(false)
const showStatusModal = ref(false)
const selectedOrder = ref(null)
const detailOrder = ref(null)

const filters = reactive({
  search: '',
  status: '',
  payment_status: '',
  from_date: '',
  to_date: '',
})

const statusForm = reactive({
  status: 'pending',
  description: '',
})

const statusErrors = reactive({
  status: '',
  description: '',
})

const stats = computed(() => [
  { key: 'total', label: 'Órdenes totales', value: String(orderStats.value.total_orders || 0), icon: 'fas fa-shopping-bag', color: 'primary' },
  { key: 'revenue', label: 'Ingresos filtrados', value: formatCurrency(orderStats.value.total_revenue || 0), icon: 'fas fa-sack-dollar', color: 'success' },
  { key: 'pending', label: 'Pendientes', value: String(orderStats.value.pending_orders || 0), icon: 'fas fa-hourglass-half', color: 'warning' },
  { key: 'completed', label: 'Completadas', value: String(orderStats.value.completed_orders || 0), icon: 'fas fa-circle-check', color: 'info' },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.status) count++
  if (filters.payment_status) count++
  if (filters.from_date) count++
  if (filters.to_date) count++
  return count
})

function normalizeOrder(rawOrder) {
  return {
    ...rawOrder,
    id: Number(rawOrder.id),
    order_number: rawOrder.order_number || `#${rawOrder.id}`,
    status: rawOrder.status || rawOrder.order_status || 'pending',
    payment_status: rawOrder.payment_status || 'pending',
    customer_name: rawOrder.user_name || rawOrder.customer_name || rawOrder.billing_name || 'Cliente',
    customer_email: rawOrder.user_email || rawOrder.customer_email || rawOrder.billing_email || '',
    total: Number(rawOrder.total || 0),
  }
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
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function statusLabel(status) {
  const labels = {
    pending: 'Pendiente',
    processing: 'En proceso',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado',
    refunded: 'Reembolsado',
  }
  return labels[status] || 'Pendiente'
}

function paymentLabel(status) {
  const labels = {
    pending: 'Pendiente',
    paid: 'Pagado',
    verified: 'Verificado',
    failed: 'Fallido',
    refunded: 'Reembolso',
  }
  return labels[status] || 'Pendiente'
}

function statusBadgeClass(status) {
  if (['delivered', 'completed'].includes(status)) return 'active'
  if (['processing', 'shipped'].includes(status)) return 'pending'
  if (['cancelled', 'refunded'].includes(status)) return 'cancelled'
  return 'pending'
}

function paymentBadgeClass(status) {
  if (['paid', 'verified'].includes(status)) return 'active'
  if (['failed', 'refunded'].includes(status)) return 'cancelled'
  return 'pending'
}

function clearSearch() {
  filters.search = ''
  loadOrders()
}

function validateDateRangeAndApply() {
  if (filters.from_date && filters.to_date && filters.from_date > filters.to_date) {
    filters.to_date = filters.from_date
    showSnackbar({ type: 'warning', message: 'La fecha final no puede ser anterior a la fecha inicial.' })
  }
  applyFilters()
}

function applyFilters() {
  loadOrders()
}

function clearAllFilters() {
  filters.search = ''
  filters.status = ''
  filters.payment_status = ''
  filters.from_date = ''
  filters.to_date = ''
  loadOrders()
}

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadOrders()
  }, 450)
}

async function loadOrders() {
  loading.value = true
  try {
    const params = { limit: 200 }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date

    const response = await orderHttp.get('/admin/orders', { params })
    const payload = response.data?.data || {}
    const rows = Array.isArray(payload) ? payload : (payload.rows || [])
    orders.value = rows.map(normalizeOrder)
    orderStats.value = payload.stats || {
      total_orders: orders.value.length,
      total_revenue: orders.value.reduce((sum, order) => sum + Number(order.total || 0), 0),
      pending_orders: orders.value.filter((order) => order.status === 'pending').length,
      completed_orders: orders.value.filter((order) => ['delivered', 'completed'].includes(order.status)).length,
    }
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando órdenes' })
  } finally {
    loading.value = false
  }
}

async function openDetailModal(order) {
  showDetailModal.value = true
  detailLoading.value = true
  selectedOrder.value = order
  try {
    const response = await orderHttp.get(`/orders/${order.id}`)
    const payload = response.data || {}
    const orderData = payload.order || {}
    detailOrder.value = {
      order: orderData,
      items: Array.isArray(payload.items) ? payload.items : [],
      history: Array.isArray(payload.history) ? payload.history : [],
      customer_name: order.customer_name,
      customer_email: order.customer_email,
    }
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando detalle de la orden' })
  } finally {
    detailLoading.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  detailLoading.value = false
  detailOrder.value = null
}

function validateStatusField(field) {
  if (field === 'status') {
    statusErrors.status = statusForm.status ? '' : 'Debes seleccionar un estado.'
  }
  if (field === 'description') {
    statusErrors.description = statusForm.description.trim().length >= 5 ? '' : 'La descripción debe tener al menos 5 caracteres.'
  }
}

function openStatusModal(order) {
  selectedOrder.value = order
  statusForm.status = order.status || 'pending'
  statusForm.description = ''
  statusErrors.status = ''
  statusErrors.description = ''
  showStatusModal.value = true
}

function closeStatusModal() {
  showStatusModal.value = false
}

async function submitStatusChange() {
  validateStatusField('status')
  validateStatusField('description')
  if (statusErrors.status || statusErrors.description || !selectedOrder.value) return

  const targetOrder = selectedOrder.value
  showAlert({
    type: 'warning',
    title: 'Confirmar cambio de estado',
    message: `¿Deseas cambiar la orden ${targetOrder.order_number || `#${targetOrder.id}`} a ${statusLabel(statusForm.status)}?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Guardar',
        style: 'primary',
        callback: async () => {
          try {
            await orderHttp.patch(`/orders/${targetOrder.id}/status`, {
              status: statusForm.status,
              description: statusForm.description.trim(),
            })
            showSnackbar({ type: 'success', message: 'Estado de la orden actualizado' })
            closeStatusModal()
            if (showDetailModal.value && detailOrder.value) {
              await openDetailModal(targetOrder)
            }
            await loadOrders()
          } catch {
            showSnackbar({ type: 'error', message: 'Error actualizando estado de la orden' })
          }
        },
      },
    ],
  })
}

function exportOrders() {
  if (orders.value.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay órdenes para exportar' })
    return
  }

  const header = ['Orden', 'Cliente', 'Email', 'Fecha', 'Total', 'Estado', 'Pago']
  const rows = [header.join(',')]

  orders.value.forEach((order) => {
    rows.push([
      `"${order.order_number || `#${order.id}`}"`,
      `"${(order.customer_name || '').replace(/"/g, '""')}"`,
      `"${(order.customer_email || '').replace(/"/g, '""')}"`,
      `"${formatDate(order.created_at)}"`,
      order.total,
      `"${statusLabel(order.status)}"`,
      `"${paymentLabel(order.payment_status)}"`,
    ].join(','))
  })

  const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'ordenes.csv'
  link.click()
  URL.revokeObjectURL(url)
  showSnackbar({ type: 'success', message: 'Exportación generada correctamente' })
}

onMounted(loadOrders)
</script>

<style scoped>
.filters-card {
  margin-bottom: 2rem;
  border: 1px solid #d9e8f4;
  border-radius: 26px;
  box-shadow: 0 14px 32px rgba(15, 55, 96, 0.08);
  overflow: hidden;
}

.filters-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-bottom: 1px solid #edf3f8;
}

.filters-title,
.entity-actions,
.results-info,
.quick-actions,
.filters-buttons,
.active-filters,
.entity-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.entity-name-cell {
  flex-direction: column;
  align-items: flex-start;
}

.entity-name-cell span,
.order-number-cell span {
  color: var(--admin-text-light);
  font-size: 1.2rem;
}

.filters-title i {
  width: 3rem;
  height: 3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  background: #eef7ff;
  color: #0077b6;
}

.filters-title h3,
.results-summary p {
  margin: 0;
}

.filters-toggle {
  width: 3.25rem;
  height: 3.25rem;
  border: 1px solid #cfe2f2;
  background: #fff;
  color: #45617d;
  border-radius: 1.1rem;
  cursor: pointer;
}

.filters-toggle.collapsed {
  transform: rotate(180deg);
}

.search-bar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 1rem;
  padding: 1.75rem 2rem;
}

.search-input-wrapper {
  position: relative;
}

.search-input,
.filter-input,
.filter-group select {
  width: 100%;
  height: 4.25rem;
  padding: 0 3.25rem 0 1rem;
  border: 1px solid #cfe2f2;
  border-radius: 1.4rem;
  font-size: 1.08rem;
  color: #24364b;
}

.filter-input,
.filter-group select {
  height: 3.3rem;
  padding: 0 0.95rem;
  border-radius: 1rem;
}

.search-icon {
  position: absolute;
  left: 1.15rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6a96cf;
}

.search-input {
  padding-left: 3.25rem;
}

.search-clear {
  position: absolute;
  right: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #90a4b7;
  cursor: pointer;
}

.search-submit-btn,
.btn-apply-filters,
.quick-actions .btn,
.quick-actions button {
  min-height: 3.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0 1.2rem;
  border-radius: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.search-submit-btn {
  min-width: 9.5rem;
  height: 4.25rem;
  border: 1px solid #8bc7f0;
  background: #f3fbff;
  color: #0077b6;
}

.filters-advanced {
  padding: 0 2rem 2rem;
}

.filters-row {
  display: grid;
  gap: 1rem;
}

.filters-row--orders {
  grid-template-columns: repeat(4, minmax(180px, 1fr));
}

.filter-group label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.filters-actions-bar,
.results-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.filters-actions-bar {
  margin-top: 1.5rem;
  padding-top: 1.3rem;
  border-top: 1px solid #edf2f7;
}

.active-filters {
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.btn-clear-filters {
  border: none;
  background: transparent;
  color: #0077b6;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.95rem;
  font-weight: 600;
}

.btn-apply-filters,
.quick-actions .btn-primary {
  border: 1px solid #0077b6;
  background: #0077b6;
  color: #fff;
}

.results-summary {
  margin-bottom: 1.25rem;
  padding: 1.25rem 1.6rem;
  background: #fff;
  border: 1px solid #d9e8f4;
  border-radius: 1.65rem;
  box-shadow: 0 14px 28px rgba(15, 55, 96, 0.08);
}

.results-summary p {
  color: #0077b6;
  font-size: 1.12rem;
  font-weight: 700;
}

.btn.btn-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid #cde0ef;
  background: #fff;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  cursor: pointer;
  color: #0e5f99;
  font-weight: 600;
}

.order-number-cell {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.status-chip-button {
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
}

.order-detail-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.6rem;
}

.detail-empty {
  padding: 1.6rem;
  color: var(--admin-text-light);
}

.summary-stack {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
  font-size: 1.35rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.summary-row--stack {
  flex-direction: column;
  align-items: flex-start;
}

.summary-row--success {
  color: var(--admin-success);
}

.summary-row--total {
  font-size: 1.55rem;
}

.summary-divider {
  border-top: 1px solid var(--admin-border);
}

.status-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.2rem;
}

.status-preview {
  width: 100%;
  padding: 1rem 1.2rem;
  border: 1px solid var(--admin-border);
  border-radius: 12px;
  background: var(--admin-bg-dark);
}

.detail-loading {
  min-height: 18rem;
}

@media (max-width: 900px) {
  .filters-row--orders,
  .order-detail-grid,
  .search-bar {
    grid-template-columns: 1fr;
  }

  .filters-actions-bar,
  .results-summary {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
