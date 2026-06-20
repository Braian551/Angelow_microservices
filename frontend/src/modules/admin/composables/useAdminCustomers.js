import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from './useAdminCustomerProfiles'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'
import { authHttp, orderHttp } from '../../../services/http'

export function useAdminCustomers() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()
  const route = useRoute()

  const loading = ref(true)
  const showDetailModal = ref(false)
  const orderRowsLoaded = ref(false)
  const rawCustomers = ref([])
  const rawOrders = ref([])
  const selectedCustomerId = ref(null)
  const customerProfiles = ref({})

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
    // Conserva los mismos filtros visuales de estado actuales.
    if (filters.state === 'active' && customer.is_blocked) {
      return false
    }

    if (filters.state === 'blocked' && !customer.is_blocked) {
      return false
    }

    // Mantiene la misma segmentación derivada usada por la vista existente.
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

  const pagination = useAdminPagination(customers, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const selectedCustomer = computed(() => customers.value.find((customer) => customer.id === selectedCustomerId.value)
    || enrichedCustomers.value.find((customer) => customer.id === selectedCustomerId.value)
    || null)

  const activeFilterCount = computed(() => {
    let count = 0
    if (filters.search.trim()) count += 1
    if (filters.state !== 'all') count += 1
    if (filters.segment !== 'all') count += 1
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
      { key: 'new', label: 'Nuevos (30 días)', value: String(visibleCustomers.filter((customer) => isWithinLastDays(customer.created_at, 30)).length), icon: 'fas fa-user-plus', color: 'info' },
      { key: 'repeat', label: 'Tasa de recompra', value: `${repeatRate}%`, icon: 'fas fa-arrows-rotate', color: 'warning' },
      { key: 'ltv', label: 'LTV promedio', value: formatCurrency(ltvAverage), icon: 'fas fa-sack-dollar', color: 'success' },
      { key: 'active', label: 'Activos', value: String(visibleCustomers.filter((customer) => !customer.is_blocked).length), icon: 'fas fa-user-check', color: 'primary' },
    ]
  })

  function normalizeIdentity(value) {
    const normalized = String(value || '').trim()
    return normalized || null
  }

  function readRouteQueryValue(key) {
    return typeof route.query?.[key] === 'string' ? route.query[key].trim() : ''
  }

  function syncFiltersFromRoute() {
    filters.search = readRouteQueryValue('search')
  }

  function normalizeEmail(value) {
    const normalized = String(value || '').trim().toLowerCase()
    return normalized || null
  }

  // Reutiliza el helper de perfiles del módulo para completar datos faltantes sin duplicar su resolución.
  function applyProfileFallback(customer) {
    const profile = resolveAdminCustomerProfile(customerProfiles.value, customer.id)

    if (!profile) {
      return customer
    }

    return {
      ...customer,
      name: customer.name || profile.name || 'Cliente',
      email: customer.email || profile.email || 'Sin email',
      image: customer.image || profile.image || '',
    }
  }

  function normalizeCustomer(customer) {
    const normalized = {
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

    return applyProfileFallback(normalized)
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

    // Une métricas por id y correo como ya hacía la vista, evitando duplicar pedidos.
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

  async function loadCustomerProfiles(customersSource) {
    const profileIds = customersSource
      .map((customer) => customer?.id)
      .map((value) => String(value || '').trim())
      .filter(Boolean)

    customerProfiles.value = await loadAdminCustomerProfiles(profileIds)
  }

  // Coordina clientes y pedidos preservando los mismos endpoints y payloads existentes.
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

      const customerPayload = customerResponse.data?.data || customerResponse.data || []
      const customerRows = Array.isArray(customerPayload) ? customerPayload : customerPayload.data || []

      await loadCustomerProfiles(customerRows)
      rawCustomers.value = customerRows.map(normalizeCustomer)

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

  function buildCustomerExportColumns() {
    return [
      {
        header: 'Avatar',
        includeInExcel: false,
        pdfImage: (customer) => customer.image,
        fallbackType: 'avatar',
        pdfWidth: 18,
        pdfImageSize: 11,
      },
      { header: 'Cliente', value: (customer) => customer.name },
      { header: 'Correo', value: (customer) => customer.email },
      { header: 'Teléfono', value: (customer) => customer.phone || 'Sin teléfono' },
      { header: 'Registro', value: (customer) => formatDate(customer.created_at), width: 16 },
      { header: 'Pedidos', value: (customer) => Number(customer.orders_count || 0), excelType: 'number', align: 'center' },
      {
        header: 'Valor acumulado',
        value: (customer) => formatCurrency(customer.total_spent),
        excelValue: (customer) => Number(customer.total_spent || 0),
        excelType: 'currency',
        align: 'right',
        width: 16,
      },
      { header: 'Estado', value: (customer) => (customer.is_blocked ? 'Bloqueado' : 'Activo'), width: 14 },
    ]
  }

  // Exporta usando la infraestructura compartida, sin recrear otra implementación por vista.
  function exportCustomers(format) {
    return exportData({
      format,
      fileBaseName: 'clientes-admin',
      sheetName: 'Clientes',
      title: 'Clientes',
      subtitle: 'Resumen exportado desde la bandeja de clientes del panel administrativo.',
      columns: buildCustomerExportColumns(),
      rows: customers.value,
      landscape: true,
      emptyMessage: 'No hay clientes para exportar.',
    })
  }

  async function applyRouteState() {
    // Permite llegar desde el buscador con el perfil correcto ya enfocado.
    syncFiltersFromRoute()

    const focusedCustomerId = readRouteQueryValue('customer')
    await loadCustomers(Boolean(focusedCustomerId) || !orderRowsLoaded.value)

    if (!focusedCustomerId) {
      if (showDetailModal.value) {
        closeCustomerModal()
      }
      return
    }

    const targetCustomer = enrichedCustomers.value.find((customer) => customer.id === focusedCustomerId)
    if (!targetCustomer) {
      return
    }

    if (showDetailModal.value && selectedCustomerId.value === targetCustomer.id) {
      return
    }

    selectedCustomerId.value = targetCustomer.id
    showDetailModal.value = true
  }

  watch(() => route.fullPath, async () => {
    await applyRouteState()
  }, { immediate: true })

  onBeforeUnmount(() => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
  })

  return {
    activeFilterCount,
    clearAllFilters,
    closeCustomerModal,
    customerSegmentLabel,
    customers,
    debouncedLoadCustomers,
    exportCustomers,
    exportingFormat,
    filters,
    formatCurrency,
    formatDate,
    formatDateTime,
    hubStatsFormatted,
    loadCustomers,
    loading,
    openCustomerModal,
    pagination,
    paymentBadgeClass,
    paymentLabel,
    selectedCustomer,
    showDetailModal,
    statusBadgeClass,
    statusLabel,
    toggleCustomerBlock,
  }
}
