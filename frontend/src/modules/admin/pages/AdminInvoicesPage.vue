<template>
  <div class="admin-invoices-page">
    <AdminPageHeader
      icon="fas fa-file-invoice-dollar"
      title="Facturas automáticas"
      subtitle="Revisa facturas emitidas por entrega y pago verificado, descarga el PDF y reenvía al correo del cliente."
      :breadcrumbs="[{ label: 'Facturas' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="5" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Filtros de facturación"
      placeholder="Buscar por factura, orden, cliente o correo..."
      @search="applyFilters"
      @update:model-value="debouncedLoad"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--5">
          <div class="admin-filters__group">
            <label for="invoice-source-filter"><i class="fas fa-database"></i> Origen</label>
            <select id="invoice-source-filter" v-model="filters.source" @change="applyFilters">
              <option value="">Todas</option>
              <option value="microservice">Principal</option>
              <option value="legacy">Respaldo</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-status-filter"><i class="fas fa-tag"></i> Estado de orden</label>
            <select id="invoice-status-filter" v-model="filters.status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="processing">En proceso</option>
              <option value="shipped">Enviado</option>
              <option value="delivered">Entregado</option>
              <option value="completed">Completado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-payment-filter"><i class="fas fa-credit-card"></i> Estado de pago</label>
            <select id="invoice-payment-filter" v-model="filters.payment_status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="paid">Pagado</option>
              <option value="verified">Verificado</option>
              <option value="approved">Aprobado</option>
              <option value="failed">Fallido</option>
              <option value="rejected">Rechazado</option>
              <option value="refunded">Reembolsado</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-from-date"><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input id="invoice-from-date" v-model="filters.from_date" type="date" @change="validateDateRangeAndApply">
          </div>

          <div class="admin-filters__group">
            <label for="invoice-to-date"><i class="fas fa-calendar-check"></i> Fecha hasta</label>
            <input id="invoice-to-date" v-model="filters.to_date" type="date" @change="validateDateRangeAndApply">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} facturas`">
      <template #actions>
        <div class="invoices-results-actions">
          <button class="results-action-btn results-action-btn--neutral" type="button" @click="loadInvoices">
            <span class="results-action-btn__icon"><i class="fas fa-rotate"></i></span>
            <span>Actualizar</span>
          </button>
        </div>
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="invoices.length === 0"
        icon="fas fa-file-invoice"
        title="Sin facturas"
        description="No encontramos facturas con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table invoices-table">
          <thead>
            <tr>
              <th>N° Factura</th>
              <th>N° Orden</th>
              <th>Cliente</th>
              <th>Emisión</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in pagination.paginatedItems" :key="`${invoice.order_source}-${invoice.id}`">
              <td>
                <div class="invoice-number-cell">
                  <strong>{{ invoice.invoice_number }}</strong>
                  <span>Pedido {{ invoice.order_number || `#${invoice.id}` }}</span>
                </div>
              </td>
              <td>
                <div class="invoice-order-cell">
                  <strong>{{ invoice.order_number || `#${invoice.id}` }}</strong>
                  <span>ID interno: {{ invoice.id }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ invoice.customer_name }}</strong>
                  <span>{{ invoice.customer_email || 'Sin correo' }}</span>
                </div>
              </td>
              <td>{{ formatDateTime(invoice.invoice_date || invoice.created_at) }}</td>
              <td><strong>{{ formatCurrency(invoice.total) }}</strong></td>
              <td>
                <span class="status-badge" :class="statusBadgeClass(invoice.status)">{{ statusLabel(invoice.status) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="paymentBadgeClass(invoice.payment_status)">{{ paymentLabel(invoice.payment_status) }}</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Vista rápida" @click="openDetailModal(invoice)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Descargar PDF" @click="downloadInvoice(invoice)">
                    <i class="fas fa-file-pdf"></i>
                  </button>
                  <button class="action-btn edit" type="button" :disabled="isResending(invoice)" title="Reenviar por correo" @click="confirmResendInvoice(invoice)">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                  <RouterLink :to="buildOrderDetailRoute(invoice)" class="action-btn edit" title="Ver detalle completo de orden">
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

    <AdminModal :show="showDetailModal" :title="selectedInvoice ? `Factura ${selectedInvoice.invoice_number}` : 'Detalle de factura'" max-width="1080px" @close="closeDetailModal">
      <div v-if="detailLoading" class="detail-loading">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </div>
      <template v-else-if="detailOrder">
        <div class="order-detail-grid">
          <div>
            <AdminCard title="Items facturados" icon="fas fa-box" :flush="true">
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
                    <td>{{ formatCurrency((item.unit_price || item.price || 0) * Number(item.quantity || 0)) }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen factura" icon="fas fa-calculator">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Factura:</span><strong>{{ selectedInvoice?.invoice_number || '-' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Subtotal:</span><strong>{{ formatCurrency(detailOrder.order.subtotal || 0) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Envío:</span><strong>{{ formatCurrency(detailOrder.order.shipping_cost || 0) }}</strong></div>
                <div v-if="Number(detailOrder.order.discount_amount || 0) > 0" class="admin-detail-summary__row admin-detail-summary__row--success"><span>Descuento:</span><strong>-{{ formatCurrency(detailOrder.order.discount_amount || 0) }}</strong></div>
                <div class="admin-detail-summary__divider"></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--total"><span>Total:</span><strong>{{ formatCurrency(detailOrder.order.total || 0) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Cliente y envío" icon="fas fa-user" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Cliente:</span><strong>{{ detailOrder.customer_name || 'Sin nombre' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Email:</span><strong>{{ detailOrder.customer_email || 'Sin correo' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Pago:</span><strong>{{ paymentLabel(detailOrder.order.payment_status) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Método:</span><strong>{{ paymentMethodLabel(detailOrder.order.payment_method) }}</strong></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--stack"><span>Dirección:</span><strong>{{ detailOrder.order.shipping_address || detailOrder.order.billing_address || 'Sin dirección' }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <button v-if="selectedInvoice" class="btn btn-primary" type="button" @click="downloadInvoice(selectedInvoice)">
          <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { downloadAdminInvoice, getAdminInvoices, resendAdminInvoice } from '../../../services/invoiceApi'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import {
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
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
const invoices = ref([])
const invoiceStats = ref({
  total_invoices: 0,
  total_amount: 0,
  paid_invoices: 0,
  delivered_invoices: 0,
  unique_customers: 0,
})
const selectedInvoice = ref(null)
const detailOrder = ref(null)
const showDetailModal = ref(false)
const resendInProgress = ref({})

const filters = reactive({
  search: '',
  source: '',
  status: '',
  payment_status: '',
  from_date: '',
  to_date: '',
})

const pagination = useAdminPagination(invoices, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const stats = computed(() => [
  { key: 'total', label: 'Facturas', value: String(invoiceStats.value.total_invoices || 0), icon: 'fas fa-file-invoice', color: 'primary' },
  { key: 'amount', label: 'Monto facturado', value: formatCurrency(invoiceStats.value.total_amount || 0), icon: 'fas fa-sack-dollar', color: 'success' },
  { key: 'paid', label: 'Pagadas', value: String(invoiceStats.value.paid_invoices || 0), icon: 'fas fa-circle-check', color: 'info' },
  { key: 'delivered', label: 'Entregadas', value: String(invoiceStats.value.delivered_invoices || 0), icon: 'fas fa-truck', color: 'warning' },
  { key: 'customers', label: 'Clientes únicos', value: String(invoiceStats.value.unique_customers || 0), icon: 'fas fa-users', color: 'neutral' },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.source) count++
  if (filters.status) count++
  if (filters.payment_status) count++
  if (filters.from_date) count++
  if (filters.to_date) count++
  return count
})

function normalizeSource(source) {
  return String(source || '').toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
}

function normalizeInvoice(rawInvoice) {
  return {
    ...rawInvoice,
    id: Number(rawInvoice.id || 0),
    order_number: rawInvoice.order_number || `#${rawInvoice.id}`,
    invoice_number: rawInvoice.invoice_number || `FAC-${rawInvoice.order_number || rawInvoice.id}`,
    customer_name: rawInvoice.customer_name || rawInvoice.user_name || rawInvoice.billing_name || 'Cliente',
    customer_email: rawInvoice.customer_email || rawInvoice.user_email || rawInvoice.billing_email || '',
    status: rawInvoice.status || rawInvoice.order_status || 'pending',
    payment_status: rawInvoice.payment_status || 'pending',
    total: Number(rawInvoice.total || 0),
    order_source: normalizeSource(rawInvoice.order_source),
  }
}

function statusLabel(status) {
  return getOrderStatusLabel(status)
}

function paymentLabel(status) {
  return getPaymentStatusLabel(status)
}

function paymentMethodLabel(paymentMethod) {
  return getPaymentMethodLabel(paymentMethod)
}

function statusBadgeClass(status) {
  return getOrderStatusBadgeClass(status)
}

function paymentBadgeClass(status) {
  return getPaymentStatusBadgeClass(status)
}

function formatCurrency(value) {
  return `$ ${Number(value || 0).toLocaleString('es-CO')}`
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function validateDateRangeAndApply() {
  if (filters.from_date && filters.to_date && filters.from_date > filters.to_date) {
    filters.to_date = filters.from_date
    showSnackbar({ type: 'warning', message: 'La fecha final no puede ser anterior a la fecha inicial.' })
  }
  applyFilters()
}

function applyFilters() {
  loadInvoices()
}

function clearAllFilters() {
  filters.search = ''
  filters.source = ''
  filters.status = ''
  filters.payment_status = ''
  filters.from_date = ''
  filters.to_date = ''
  loadInvoices()
}

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadInvoices()
  }, 450)
}

async function loadInvoices() {
  loading.value = true
  try {
    const params = { limit: 300 }
    if (filters.search) params.search = filters.search
    if (filters.source) params.source = filters.source
    if (filters.status) params.status = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date

    const response = await getAdminInvoices(params)
    const payload = response?.data || {}
    const rows = Array.isArray(payload.rows) ? payload.rows : []

    invoices.value = rows.map(normalizeInvoice)
    invoiceStats.value = payload.stats || {
      total_invoices: invoices.value.length,
      total_amount: invoices.value.reduce((sum, invoice) => sum + Number(invoice.total || 0), 0),
      paid_invoices: invoices.value.filter((invoice) => ['paid', 'verified', 'approved'].includes(String(invoice.payment_status || '').toLowerCase())).length,
      delivered_invoices: invoices.value.filter((invoice) => ['delivered', 'completed'].includes(String(invoice.status || '').toLowerCase())).length,
      unique_customers: new Set(invoices.value.map((invoice) => invoice.customer_email || `id:${invoice.user_id || invoice.id}`)).size,
    }
  } catch {
    showSnackbar({ type: 'error', message: 'No pudimos cargar las facturas.' })
  } finally {
    loading.value = false
  }
}

function buildOrderDetailRoute(invoice) {
  const isLegacy = normalizeSource(invoice?.order_source) === 'legacy'

  return {
    name: 'admin-order-detail',
    params: { id: invoice?.id },
    query: isLegacy ? { vista: 'archivo' } : {},
  }
}

function parseFilenameFromHeaders(headers, fallbackName) {
  const disposition = headers?.['content-disposition'] || headers?.['Content-Disposition']
  if (!disposition) return fallbackName

  const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1].replace(/"/g, '').trim())
  }

  const regularMatch = disposition.match(/filename="?([^";]+)"?/i)
  if (regularMatch?.[1]) {
    return regularMatch[1].trim()
  }

  return fallbackName
}

async function downloadInvoice(invoice) {
  const fallbackName = `factura_${invoice.invoice_number || invoice.id}.pdf`
  try {
    const response = await downloadAdminInvoice(invoice.id, { source: normalizeSource(invoice.order_source) })
    const fileName = parseFilenameFromHeaders(response.headers, fallbackName)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const objectUrl = window.URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = objectUrl
    anchor.download = fileName
    document.body.appendChild(anchor)
    anchor.click()
    document.body.removeChild(anchor)
    window.URL.revokeObjectURL(objectUrl)
    showSnackbar({ type: 'success', message: 'Factura descargada correctamente.' })
  } catch {
    showSnackbar({ type: 'error', message: 'No pudimos descargar la factura.' })
  }
}

function buildResendKey(invoice) {
  return `${normalizeSource(invoice?.order_source)}:${Number(invoice?.id || 0)}`
}

function isResending(invoice) {
  return Boolean(resendInProgress.value[buildResendKey(invoice)])
}

function confirmResendInvoice(invoice) {
  showAlert({
    type: 'warning',
    title: 'Reenviar factura',
    message: `¿Deseas reenviar la factura ${invoice.invoice_number} al correo del cliente?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Reenviar',
        style: 'primary',
        callback: async () => {
          await resendInvoice(invoice)
        },
      },
    ],
  })
}

async function resendInvoice(invoice) {
  const resendKey = buildResendKey(invoice)

  resendInProgress.value = {
    ...resendInProgress.value,
    [resendKey]: true,
  }

  try {
    const response = await resendAdminInvoice(invoice.id, { source: normalizeSource(invoice.order_source) })
    showSnackbar({
      type: 'success',
      message: response?.message || 'Factura reenviada correctamente.',
    })
  } catch {
    showSnackbar({ type: 'error', message: 'No pudimos reenviar la factura.' })
  } finally {
    const nextState = { ...resendInProgress.value }
    delete nextState[resendKey]
    resendInProgress.value = nextState
  }
}

async function openDetailModal(invoice) {
  showDetailModal.value = true
  detailLoading.value = true
  selectedInvoice.value = invoice

  try {
    const response = await orderHttp.get(`/orders/${invoice.id}`, { params: { source: normalizeSource(invoice.order_source) } })
    const payload = response.data || {}
    detailOrder.value = {
      order: payload.order || {},
      items: Array.isArray(payload.items) ? payload.items : [],
      customer_name: invoice.customer_name,
      customer_email: invoice.customer_email,
    }
  } catch {
    showSnackbar({ type: 'error', message: 'No pudimos cargar el detalle de la factura.' })
  } finally {
    detailLoading.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  detailOrder.value = null
  selectedInvoice.value = null
  detailLoading.value = false
}

onMounted(() => {
  loadInvoices()
})
</script>

<style scoped>
.invoice-number-cell,
.invoice-order-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.invoice-number-cell span,
.invoice-order-cell span {
  color: #718096;
  font-size: 0.75rem;
}

.invoices-results-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}
</style>
