<template>
  <div class="admin-orders-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      title="Gestión de Órdenes"
      subtitle="Administra pedidos, revisa el historial y cambia estados con el mismo flujo operativo de Angelow."
      :breadcrumbs="[{ label: 'Órdenes' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-sliders-h"
      title="Filtros de búsqueda"
      placeholder="Buscar por N° orden, cliente o email..."
      @search="applyFilters"
      @update:model-value="debouncedLoad"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--4">
          <div class="admin-filters__group">
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

          <div class="admin-filters__group">
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

          <div class="admin-filters__group">
            <label for="from-date"><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input id="from-date" v-model="filters.from_date" type="date" @change="validateDateRangeAndApply">
          </div>

          <div class="admin-filters__group">
            <label for="to-date"><i class="fas fa-calendar-check"></i> Fecha hasta</label>
            <input id="to-date" v-model="filters.to_date" type="date" @change="validateDateRangeAndApply">
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i> Limpiar todo
            </button>
            <button type="button" class="admin-filters__apply" @click="applyFilters">
              <i class="fas fa-check-circle"></i> Aplicar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${orders.length} órdenes`">
      <template #actions>
        <button class="btn-icon" type="button" @click="exportOrders">
          <i class="fas fa-file-export"></i> Exportar
        </button>
      </template>
    </AdminResultsBar>

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
                <div class="admin-entity-name">
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
                <div class="admin-entity-actions">
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
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Subtotal</span><strong>{{ formatCurrency(detailOrder.order.subtotal || 0) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Envío</span><strong>{{ formatCurrency(detailOrder.order.shipping_cost || 0) }}</strong></div>
                <div v-if="Number(detailOrder.order.discount_amount || 0) > 0" class="admin-detail-summary__row admin-detail-summary__row--success"><span>Descuento</span><strong>-{{ formatCurrency(detailOrder.order.discount_amount || 0) }}</strong></div>
                <div class="admin-detail-summary__divider"></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--total"><span>Total</span><strong>{{ formatCurrency(detailOrder.order.total || 0) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Cliente" icon="fas fa-user" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row admin-detail-summary__row--stack"><span>{{ detailOrder.customer_name }}</span><strong>{{ detailOrder.customer_email || 'Sin email' }}</strong></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--stack"><span>Dirección</span><strong>{{ detailOrder.order.shipping_address || detailOrder.order.billing_address || 'Sin dirección' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Pago</span><strong>{{ paymentLabel(detailOrder.order.payment_status) }}</strong></div>
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
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const detailLoading = ref(false)
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
/* Estilos específicos de Órdenes — los comunes están en admin.css */

.order-number-cell {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.order-number-cell span {
  color: var(--admin-text-light);
  font-size: 1.2rem;
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

.status-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.2rem;
}

.status-preview {
  width: 100%;
  padding: 1rem 1.2rem;
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius-lg);
  background: var(--admin-bg-dark);
}

.detail-loading {
  min-height: 18rem;
}

@media (max-width: 900px) {
  .order-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
