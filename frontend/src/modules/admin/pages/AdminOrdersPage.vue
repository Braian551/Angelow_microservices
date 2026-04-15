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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} órdenes`">
      <template #actions>
        <div class="orders-results-actions">
          <div v-if="selectedOrdersCount > 0" class="orders-results-actions__selection">
            <i class="fas fa-check-double"></i>
            <span>{{ selectedOrdersCount }} seleccionada<span v-if="selectedOrdersCount !== 1">s</span></span>
          </div>
          <button class="results-action-btn results-action-btn--neutral" type="button" @click="exportOrders">
            <span class="results-action-btn__icon"><i class="fas fa-file-export"></i></span>
            <span>Exportar CSV</span>
          </button>
          <button class="results-action-btn results-action-btn--primary" type="button" :disabled="selectedOrdersCount === 0" @click="openBulkActionsModal">
            <span class="results-action-btn__icon"><i class="fas fa-tasks"></i></span>
            <span>Acciones masivas</span>
          </button>
        </div>
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
              <th class="selection-cell">
                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll($event.target.checked)">
              </th>
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
            <tr v-for="order in pagination.paginatedItems" :key="`${order.order_source}-${order.id}`">
              <td class="selection-cell">
                <input type="checkbox" :checked="isOrderSelected(order)" @change="toggleOrderSelection(order, $event.target.checked)">
              </td>
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
                  <button class="action-btn edit" type="button" title="Cambiar estado" @click="openStatusModal(order)">
                    <i class="fas fa-retweet"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Cambiar estado de pago" @click="openPaymentStatusModal(order)">
                    <i class="fas fa-credit-card"></i>
                  </button>
                  <button class="action-btn delete" type="button" :disabled="order.status === 'cancelled'" :title="order.status === 'cancelled' ? 'Orden ya desactivada' : 'Desactivar orden'" @click="confirmDeactivateOrder(order)">
                    <i class="fas fa-power-off"></i>
                  </button>
                  <RouterLink :to="buildOrderDetailRoute(order)" class="action-btn edit" title="Ir al detalle completo">
                    <i class="fas fa-arrow-right"></i>
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

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
                    <td>{{ translateHistoryValue(entry.old_value, entry.field_changed) }} -> {{ translateHistoryValue(entry.new_value, entry.field_changed) }}</td>
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
        <RouterLink v-if="selectedOrder" :to="buildOrderDetailRoute(selectedOrder)" class="btn btn-primary" @click="closeDetailModal">
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

    <AdminModal :show="showPaymentStatusModal" title="Actualizar estado de pago" max-width="560px" @close="closePaymentStatusModal">
      <div class="status-form-grid">
        <div class="form-group status-form-grid__full">
          <label>Orden seleccionada</label>
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selección' }}</div>
        </div>

        <div class="form-group">
          <label for="payment-status">Estado de pago *</label>
          <select id="payment-status" v-model="paymentForm.payment_status" class="form-control" @change="validatePaymentField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="paymentErrors.payment_status" class="form-error">{{ paymentErrors.payment_status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="payment-description">Descripción del cambio *</label>
          <textarea id="payment-description" v-model="paymentForm.description" class="form-control" rows="4" :class="{ 'is-invalid': paymentErrors.description }" @input="validatePaymentField('description')"></textarea>
          <p v-if="paymentErrors.description" class="form-error">{{ paymentErrors.description }}</p>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closePaymentStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="submitPaymentStatusChange">Guardar cambio</button>
      </template>
    </AdminModal>

    <AdminModal :show="showBulkModal" title="Acciones masivas" max-width="560px" @close="closeBulkModal">
      <div class="bulk-form-grid">
        <div class="bulk-modal-summary">
          <div class="bulk-modal-summary__count">
            <strong>{{ selectedOrdersCount }}</strong>
            <span>{{ selectedOrdersCount === 1 ? 'orden seleccionada' : 'órdenes seleccionadas' }}</span>
          </div>
          <div class="bulk-modal-summary__chips">
            <span v-for="order in selectedOrdersPreview" :key="order.id" class="bulk-modal-summary__chip">{{ order.order_number }}</span>
            <span v-if="selectedOrdersCount > selectedOrdersPreview.length" class="bulk-modal-summary__chip bulk-modal-summary__chip--muted">+{{ selectedOrdersCount - selectedOrdersPreview.length }} más</span>
          </div>
          <p class="bulk-modal-summary__helper">La acción elegida se confirmará antes de ejecutarse y mostrará feedback centralizado con alerta y snackbar.</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label>Órdenes seleccionadas</label>
          <div class="status-preview">{{ selectedOrdersCount }} seleccionada(s)</div>
        </div>

        <div class="form-group">
          <label for="bulk-action">Acción *</label>
          <select id="bulk-action" v-model="bulkForm.action" class="form-control" @change="validateBulkField('action')">
            <option value="">Seleccionar acción</option>
            <option value="change_status">Cambiar estado</option>
            <option value="change_payment_status">Cambiar estado de pago</option>
            <option value="deactivate">Desactivar</option>
          </select>
          <p v-if="bulkErrors.action" class="form-error">{{ bulkErrors.action }}</p>
        </div>

        <div v-if="bulkForm.action === 'change_status'" class="form-group">
          <label for="bulk-status">Estado *</label>
          <select id="bulk-status" v-model="bulkForm.status" class="form-control" @change="validateBulkField('status')">
            <option value="pending">Pendiente</option>
            <option value="processing">En proceso</option>
            <option value="shipped">Enviado</option>
            <option value="delivered">Entregado</option>
            <option value="cancelled">Cancelado</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="bulkErrors.status" class="form-error">{{ bulkErrors.status }}</p>
        </div>

        <div v-if="bulkForm.action === 'change_payment_status'" class="form-group">
          <label for="bulk-payment-status">Estado de pago *</label>
          <select id="bulk-payment-status" v-model="bulkForm.payment_status" class="form-control" @change="validateBulkField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="bulkErrors.payment_status" class="form-error">{{ bulkErrors.payment_status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="bulk-description">Descripción del cambio *</label>
          <textarea id="bulk-description" v-model="bulkForm.description" class="form-control" rows="4" :class="{ 'is-invalid': bulkErrors.description }" @input="validateBulkField('description')"></textarea>
          <p v-if="bulkErrors.description" class="form-error">{{ bulkErrors.description }}</p>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeBulkModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="bulkSaving" @click="submitBulkAction">Aplicar cambios</button>
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
import { useAdminPagination } from '../composables/useAdminPagination'
import {
  getBulkActionLabel,
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  translateHistoryValue,
} from '../utils/orderPresentation'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
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
const selectedOrderKeys = ref([])
const showDetailModal = ref(false)
const showStatusModal = ref(false)
const showPaymentStatusModal = ref(false)
const showBulkModal = ref(false)
const bulkSaving = ref(false)
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

const paymentForm = reactive({
  payment_status: 'pending',
  description: '',
})

const paymentErrors = reactive({
  payment_status: '',
  description: '',
})

const bulkForm = reactive({
  action: '',
  status: 'pending',
  payment_status: 'pending',
  description: '',
})

const bulkErrors = reactive({
  action: '',
  status: '',
  payment_status: '',
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

const pagination = useAdminPagination(orders, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const selectedOrdersCount = computed(() => selectedOrderKeys.value.length)
const allSelected = computed(() => {
  const visibleKeys = pagination.paginatedItems.map((order) => buildOrderSelectionKey(order))
  return visibleKeys.length > 0 && visibleKeys.every((selectionKey) => selectedOrderKeys.value.includes(selectionKey))
})
const selectedOrdersPreview = computed(() => {
  return selectedOrderKeys.value
    .map((selectionKey) => findOrderBySelectionKey(selectionKey))
    .filter(Boolean)
    .slice(0, 3)
})

function normalizeOrderSource(source) {
  return String(source || '').toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
}

function buildOrderDetailRoute(order) {
  const isLegacy = normalizeOrderSource(order?.order_source) === 'legacy'

  return {
    name: 'admin-order-detail',
    params: { id: order?.id },
    query: isLegacy ? { vista: 'archivo' } : {},
  }
}

function buildOrderSelectionKey(orderOrId, source = null) {
  if (typeof orderOrId === 'object' && orderOrId !== null) {
    return `${normalizeOrderSource(orderOrId.order_source)}:${Number(orderOrId.id || 0)}`
  }

  return `${normalizeOrderSource(source)}:${Number(orderOrId || 0)}`
}

function findOrderBySelectionKey(selectionKey) {
  return orders.value.find((order) => buildOrderSelectionKey(order) === selectionKey) || null
}

function normalizeOrder(rawOrder) {
  return {
    ...rawOrder,
    id: Number(rawOrder.id),
    order_source: normalizeOrderSource(rawOrder.order_source),
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
  return getOrderStatusLabel(status)
}

function paymentLabel(status) {
  return getPaymentStatusLabel(status)
}

function statusBadgeClass(status) {
  return getOrderStatusBadgeClass(status)
}

function paymentBadgeClass(status) {
  return getPaymentStatusBadgeClass(status)
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
    const currentKeys = new Set(orders.value.map((order) => buildOrderSelectionKey(order)))
    selectedOrderKeys.value = selectedOrderKeys.value.filter((selectionKey) => currentKeys.has(selectionKey))
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
    const response = await orderHttp.get(`/orders/${order.id}`, { params: { source: normalizeOrderSource(order.order_source) } })
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

function validatePaymentField(field) {
  if (field === 'payment_status') {
    paymentErrors.payment_status = paymentForm.payment_status ? '' : 'Debes seleccionar un estado de pago.'
  }
  if (field === 'description') {
    paymentErrors.description = paymentForm.description.trim().length >= 5 ? '' : 'La descripción debe tener al menos 5 caracteres.'
  }
}

function openPaymentStatusModal(order) {
  selectedOrder.value = order
  paymentForm.payment_status = order.payment_status || 'pending'
  paymentForm.description = ''
  paymentErrors.payment_status = ''
  paymentErrors.description = ''
  showPaymentStatusModal.value = true
}

function closePaymentStatusModal() {
  showPaymentStatusModal.value = false
}

async function submitPaymentStatusChange() {
  validatePaymentField('payment_status')
  validatePaymentField('description')
  if (paymentErrors.payment_status || paymentErrors.description || !selectedOrder.value) return

  const targetOrder = selectedOrder.value
  showAlert({
    type: 'warning',
    title: 'Confirmar cambio de pago',
    message: `¿Deseas actualizar el pago de ${targetOrder.order_number || `#${targetOrder.id}`} a ${paymentLabel(paymentForm.payment_status)}?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Guardar',
        style: 'primary',
        callback: async () => {
          try {
            await orderHttp.patch(`/orders/${targetOrder.id}/payment-status`, {
              source: normalizeOrderSource(targetOrder.order_source),
              payment_status: paymentForm.payment_status,
              description: paymentForm.description.trim(),
            })
            showSnackbar({ type: 'success', message: 'Estado de pago actualizado' })
            closePaymentStatusModal()
            if (showDetailModal.value && detailOrder.value) {
              await openDetailModal(targetOrder)
            }
            await loadOrders()
          } catch {
            showSnackbar({ type: 'error', message: 'Error actualizando estado de pago' })
          }
        },
      },
    ],
  })
}

function confirmDeactivateOrder(order) {
  if (!order || order.status === 'cancelled') return

  showAlert({
    type: 'warning',
    title: 'Desactivar orden',
    message: `¿Deseas desactivar la orden ${order.order_number || `#${order.id}`}?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Desactivar',
        style: 'warning',
        callback: async () => {
          await deactivateOrder(order, 'Orden desactivada desde acciones rápidas')
        },
      },
    ],
  })
}

async function deactivateOrder(order, description) {
  try {
    await orderHttp.patch(`/orders/${order.id}/deactivate`, {
      source: normalizeOrderSource(order.order_source),
      description,
    })
    showSnackbar({ type: 'success', message: 'Orden desactivada correctamente' })
    await loadOrders()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo desactivar la orden' })
  }
}

function isOrderSelected(order) {
  return selectedOrderKeys.value.includes(buildOrderSelectionKey(order))
}

function toggleOrderSelection(order, checked) {
  const selectionKey = buildOrderSelectionKey(order)

  if (checked) {
    if (!selectedOrderKeys.value.includes(selectionKey)) {
      selectedOrderKeys.value.push(selectionKey)
    }
    return
  }

  selectedOrderKeys.value = selectedOrderKeys.value.filter((key) => key !== selectionKey)
}

function toggleSelectAll(checked) {
  const visibleKeys = pagination.paginatedItems.map((order) => buildOrderSelectionKey(order))

  if (!checked) {
    selectedOrderKeys.value = selectedOrderKeys.value.filter((selectionKey) => !visibleKeys.includes(selectionKey))
    return
  }

  selectedOrderKeys.value = [...new Set([...selectedOrderKeys.value, ...visibleKeys])]
}

function openBulkActionsModal() {
  if (selectedOrderKeys.value.length === 0) {
    showSnackbar({ type: 'info', message: 'Selecciona al menos una orden para acciones masivas' })
    return
  }

  bulkForm.action = ''
  bulkForm.status = 'pending'
  bulkForm.payment_status = 'pending'
  bulkForm.description = ''
  bulkErrors.action = ''
  bulkErrors.status = ''
  bulkErrors.payment_status = ''
  bulkErrors.description = ''
  showBulkModal.value = true
}

function closeBulkModal() {
  showBulkModal.value = false
}

function validateBulkField(field) {
  if (field === 'action') {
    bulkErrors.action = bulkForm.action ? '' : 'Debes seleccionar una acción.'
    return
  }

  if (field === 'status') {
    if (bulkForm.action !== 'change_status') {
      bulkErrors.status = ''
      return
    }
    bulkErrors.status = bulkForm.status ? '' : 'Debes seleccionar un estado.'
    return
  }

  if (field === 'payment_status') {
    if (bulkForm.action !== 'change_payment_status') {
      bulkErrors.payment_status = ''
      return
    }
    bulkErrors.payment_status = bulkForm.payment_status ? '' : 'Debes seleccionar un estado de pago.'
    return
  }

  if (field === 'description') {
    bulkErrors.description = bulkForm.description.trim().length >= 5 ? '' : 'La descripción debe tener al menos 5 caracteres.'
  }
}

async function submitBulkAction() {
  validateBulkField('action')
  validateBulkField('status')
  validateBulkField('payment_status')
  validateBulkField('description')

  if (bulkErrors.action || bulkErrors.status || bulkErrors.payment_status || bulkErrors.description) {
    return
  }

  const targetOrders = selectedOrderKeys.value
    .map((selectionKey) => findOrderBySelectionKey(selectionKey))
    .filter(Boolean)

  if (targetOrders.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay órdenes seleccionadas.' })
    return
  }

  const bulkLabel = getBulkActionLabel(bulkForm.action)

  showAlert({
    type: 'warning',
    title: 'Confirmar acción masiva',
    message: `¿Deseas aplicar ${bulkLabel} a ${targetOrders.length} orden(es)?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Aplicar',
        style: 'primary',
        callback: async () => {
          bulkSaving.value = true
          let successCount = 0

          try {
            for (const targetOrder of targetOrders) {
              if (bulkForm.action === 'change_status') {
                await orderHttp.patch(`/orders/${targetOrder.id}/status`, {
                  source: normalizeOrderSource(targetOrder.order_source),
                  status: bulkForm.status,
                  description: bulkForm.description.trim(),
                })
                successCount++
                continue
              }

              if (bulkForm.action === 'change_payment_status') {
                await orderHttp.patch(`/orders/${targetOrder.id}/payment-status`, {
                  source: normalizeOrderSource(targetOrder.order_source),
                  payment_status: bulkForm.payment_status,
                  description: bulkForm.description.trim(),
                })
                successCount++
                continue
              }

              if (bulkForm.action === 'deactivate') {
                await orderHttp.patch(`/orders/${targetOrder.id}/deactivate`, {
                  source: normalizeOrderSource(targetOrder.order_source),
                  description: bulkForm.description.trim(),
                })
                successCount++
              }
            }

            const successLabel = bulkForm.action === 'change_status'
              ? 'Cambio de estado'
              : (bulkForm.action === 'change_payment_status' ? 'Cambio de estado de pago' : 'Desactivación')

            showSnackbar({ type: 'success', message: `${successLabel} aplicada en ${successCount} orden(es).` })
            selectedOrderKeys.value = []
            closeBulkModal()
            await loadOrders()
          } catch {
            showSnackbar({ type: 'error', message: 'No fue posible completar la acción masiva.' })
          } finally {
            bulkSaving.value = false
          }
        },
      },
    ],
  })
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
              source: normalizeOrderSource(targetOrder.order_source),
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

.selection-cell {
  width: 4.2rem;
  text-align: center;
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

.bulk-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.2rem;
}

.bulk-modal-summary {
  display: grid;
  gap: 0.9rem;
  padding: 1.2rem 1.25rem;
  border: 1px solid rgba(148, 184, 216, 0.24);
  border-radius: 1.6rem;
  background: rgba(247, 251, 255, 0.82);
}

.bulk-modal-summary__count {
  display: flex;
  align-items: baseline;
  gap: 0.55rem;
}

.bulk-modal-summary__count strong {
  font-size: 2.1rem;
  color: var(--admin-primary-dark);
}

.bulk-modal-summary__count span {
  font-size: 1.18rem;
  color: var(--admin-text-soft);
  font-weight: 600;
}

.bulk-modal-summary__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.bulk-modal-summary__chip {
  padding: 0.55rem 0.9rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.1);
  border: 1px solid rgba(0, 119, 182, 0.14);
  color: var(--admin-primary-dark);
  font-size: 1.08rem;
  font-weight: 700;
}

.bulk-modal-summary__chip--muted {
  background: rgba(138, 160, 184, 0.12);
  border-color: rgba(138, 160, 184, 0.18);
  color: var(--admin-text-soft);
}

.bulk-modal-summary__helper {
  margin: 0;
  color: var(--admin-text-light);
  font-size: 1.15rem;
}

.orders-results-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 0.75rem;
  width: 100%;
}

.orders-results-actions__selection {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 4.1rem;
  padding: 0.72rem 1.1rem;
  border-radius: 1.25rem;
  background: rgba(0, 119, 182, 0.08);
  border: 1px solid rgba(0, 119, 182, 0.14);
  color: var(--admin-primary-dark);
  font-weight: 700;
}

.results-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.72rem;
  min-height: 4.1rem;
  padding: 0.72rem 1.18rem;
  border-radius: 1.25rem;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.88);
  color: var(--admin-text-heading);
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 20px rgba(15, 55, 96, 0.06);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, opacity 0.2s ease;
}

.results-action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 14px 24px rgba(15, 55, 96, 0.1);
}

.results-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.results-action-btn__icon {
  width: 3rem;
  height: 3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.72);
}

.results-action-btn--neutral {
  border-color: rgba(148, 184, 216, 0.2);
  color: var(--admin-primary);
}

.results-action-btn--neutral .results-action-btn__icon {
  background: rgba(0, 119, 182, 0.1);
  color: var(--admin-primary);
}

.results-action-btn--primary {
  background: rgba(86, 191, 116, 0.14);
  border-color: rgba(86, 191, 116, 0.22);
  color: #1f6e33;
}

.results-action-btn--primary .results-action-btn__icon {
  background: rgba(86, 191, 116, 0.18);
  color: #1f6e33;
}

.action-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .orders-results-actions {
    justify-content: flex-start;
  }

  .order-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
