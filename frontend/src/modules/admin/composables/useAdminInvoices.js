import { computed, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { downloadAdminInvoice, getAdminInvoices, resendAdminInvoice } from '../../../services/invoiceApi'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from './useAdminPagination'
import {
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
} from '../utils/orderPresentation'

/**
 * Composable para la gestión de facturas del panel administrativo.
 * Carga lista de facturas con filtros, estadísticas, detalle de pedido asociado,
 * reenvío de facturas por email y descarga de PDF.
 * Reutiliza useAdminPagination para paginación.
 */
export function useAdminInvoices() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const route = useRoute()

  // =====================================================
  // Estado principal
  // =====================================================
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

  // =====================================================
  // Filtros y paginación
  // =====================================================
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

  const activeFilterCount = computed(() => {
    let count = 0
    if (filters.source) count += 1
    if (filters.status) count += 1
    if (filters.payment_status) count += 1
    if (filters.from_date) count += 1
    if (filters.to_date) count += 1
    return count
  })

  // =====================================================
  // Selección y detalle
  // =====================================================
  const stats = computed(() => [
    { key: 'total', label: 'Facturas', value: String(invoiceStats.value.total_invoices || 0), icon: 'fas fa-file-invoice', color: 'primary' },
    { key: 'amount', label: 'Monto facturado', value: formatCurrency(invoiceStats.value.total_amount || 0), icon: 'fas fa-sack-dollar', color: 'success' },
    { key: 'paid', label: 'Pagadas', value: String(invoiceStats.value.paid_invoices || 0), icon: 'fas fa-circle-check', color: 'info' },
    { key: 'delivered', label: 'Entregadas', value: String(invoiceStats.value.delivered_invoices || 0), icon: 'fas fa-truck', color: 'warning' },
    { key: 'customers', label: 'Clientes únicos', value: String(invoiceStats.value.unique_customers || 0), icon: 'fas fa-users', color: 'neutral' },
  ])

  // =====================================================
  // Datos fiscales y totales
  // =====================================================
  function normalizeSource(source) {
    return String(source || '').toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
  }

  function readRouteQueryValue(key) {
    return typeof route.query?.[key] === 'string' ? route.query[key].trim() : ''
  }

  function syncFiltersFromRoute() {
    filters.search = readRouteQueryValue('search')
    filters.source = readRouteQueryValue('source')
    filters.status = readRouteQueryValue('status')
    filters.payment_status = readRouteQueryValue('payment_status')
    filters.from_date = readRouteQueryValue('from_date')
    filters.to_date = readRouteQueryValue('to_date')
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

  // =====================================================
  // Carga
  // =====================================================
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

  // =====================================================
  // Descarga e impresión
  // =====================================================
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

  // =====================================================
  // Correo y reenvío
  // =====================================================
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

  // =====================================================
  // Modales
  // =====================================================
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

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
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

  async function applyRouteState() {
    // Reutiliza el query de la ruta para abrir la vista con filtros y foco sin duplicar contratos.
    syncFiltersFromRoute()
    await loadInvoices()

    const focusedInvoiceId = Number(readRouteQueryValue('invoice') || 0)
    if (!focusedInvoiceId) {
      if (showDetailModal.value) {
        closeDetailModal()
      }
      return
    }

    const focusedSource = readRouteQueryValue('source')
    const targetInvoice = invoices.value.find((invoice) => {
      if (invoice.id !== focusedInvoiceId) {
        return false
      }

      if (!focusedSource) {
        return true
      }

      return normalizeSource(invoice.order_source) === normalizeSource(focusedSource)
    })

    if (!targetInvoice) {
      return
    }

    if (
      showDetailModal.value
      && selectedInvoice.value?.id === targetInvoice.id
      && normalizeSource(selectedInvoice.value?.order_source) === normalizeSource(targetInvoice.order_source)
    ) {
      return
    }

    await openDetailModal(targetInvoice)
  }

  watch(() => route.fullPath, async () => {
    await applyRouteState()
  }, { immediate: true })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    applyFilters,
    buildOrderDetailRoute,
    clearAllFilters,
    closeDetailModal,
    debouncedLoad,
    detailLoading,
    detailOrder,
    downloadInvoice,
    filters,
    formatCurrency,
    formatDateTime,
    invoices,
    isResending,
    loadInvoices,
    loading,
    openDetailModal,
    pagination,
    paymentBadgeClass,
    paymentLabel,
    paymentMethodLabel,
    confirmResendInvoice,
    selectedInvoice,
    showDetailModal,
    stats,
    statusBadgeClass,
    statusLabel,
    validateDateRangeAndApply,
  }
}
