import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'
import {
  getBulkActionLabel,
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  normalizeAdminOrderStatus,
} from '../utils/orderPresentation'

export function useAdminOrders() {
  const router = useRouter()
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

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
  const savingStatusChange = ref(false)
  const savingPaymentStatusChange = ref(false)
  const savingOrderActionKey = ref('')
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
    if (filters.status) count += 1
    if (filters.payment_status) count += 1
    if (filters.from_date) count += 1
    if (filters.to_date) count += 1
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

  // Mantiene la misma distinción legacy/microservice que ya usan las rutas y payloads actuales.
  function normalizeOrderSource(source) {
    return String(source || '').toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
  }

  // Conserva la navegación exacta al detalle y el query especial para órdenes legacy.
  function buildOrderDetailRoute(order) {
    const isLegacy = normalizeOrderSource(order?.order_source) === 'legacy'

    return {
      name: 'admin-order-detail',
      params: { id: order?.id },
      query: isLegacy ? { vista: 'archivo' } : {},
    }
  }

  function goToOrderDetail(order, event = null) {
    const interactiveTarget = event?.target?.closest?.('a, button, input, select, textarea, label')
    if (!order || interactiveTarget) {
      return
    }

    // Reutiliza la ruta de detalle existente para que fila y botón mantengan el mismo contrato de navegación.
    router.push(buildOrderDetailRoute(order))
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

  // Normaliza la respuesta del servicio sin alterar nombres ni transiciones funcionales.
  function normalizeOrder(rawOrder) {
    const resolvedCustomerName = rawOrder.user_name || rawOrder.customer_name || rawOrder.billing_name || ''
    const resolvedCustomerEmail = rawOrder.user_email || rawOrder.customer_email || rawOrder.billing_email || ''

    return {
      ...rawOrder,
      id: Number(rawOrder.id),
      order_source: normalizeOrderSource(rawOrder.order_source),
      order_number: rawOrder.order_number || `#${rawOrder.id}`,
      status: normalizeAdminOrderStatus(rawOrder.status || rawOrder.order_status || 'pending'),
      payment_status: rawOrder.payment_status || 'pending',
      customer_name: resolvedCustomerName || (rawOrder.user_id ? `Cliente ${rawOrder.user_id}` : 'Cliente'),
      customer_email: resolvedCustomerEmail,
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

  // Reutiliza la misma búsqueda diferida para no disparar más peticiones que la vista original.
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
        pending_orders: orders.value.filter((order) => ['pending', 'in_review', 'processing'].includes(normalizeAdminOrderStatus(order.status))).length,
        completed_orders: orders.value.filter((order) => ['delivered', 'completed'].includes(normalizeAdminOrderStatus(order.status))).length,
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
      const response = await orderHttp.get(`/orders/${order.id}`, {
        params: { source: normalizeOrderSource(order.order_source) },
      })
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
      statusErrors.description = ''
    }
  }

  function openStatusModal(order) {
    selectedOrder.value = order
    statusForm.status = normalizeAdminOrderStatus(order.status) || 'pending'
    statusForm.description = ''
    statusErrors.status = ''
    statusErrors.description = ''
    showStatusModal.value = true
  }

  function closeStatusModal() {
    if (savingStatusChange.value) return
    showStatusModal.value = false
  }

  function validatePaymentField(field) {
    if (field === 'payment_status') {
      paymentErrors.payment_status = paymentForm.payment_status ? '' : 'Debes seleccionar un estado de pago.'
    }

    if (field === 'description') {
      paymentErrors.description = ''
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
    if (savingPaymentStatusChange.value) return
    showPaymentStatusModal.value = false
  }

  async function submitPaymentStatusChange() {
    if (savingPaymentStatusChange.value) return

    validatePaymentField('payment_status')
    if (paymentErrors.payment_status || !selectedOrder.value) return

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
            savingPaymentStatusChange.value = true

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
            } finally {
              savingPaymentStatusChange.value = false
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
    const actionKey = buildOrderActionKey(order, 'deactivate')
    if (savingOrderActionKey.value) return

    savingOrderActionKey.value = actionKey

    try {
      await orderHttp.patch(`/orders/${order.id}/deactivate`, {
        source: normalizeOrderSource(order.order_source),
        description,
      })
      showSnackbar({ type: 'success', message: 'Orden desactivada correctamente' })
      await loadOrders()
    } catch {
      showSnackbar({ type: 'error', message: 'No se pudo desactivar la orden' })
    } finally {
      savingOrderActionKey.value = ''
    }
  }

  function confirmCompleteOrder(order) {
    if (!canCompleteOrder(order) || savingOrderActionKey.value) return

    showAlert({
      type: 'warning',
      title: 'Completar orden',
      message: `¿Deseas marcar la orden ${order.order_number || `#${order.id}`} como completada?`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Completar',
          style: 'primary',
          callback: async () => {
            await completeOrder(order)
          },
        },
      ],
    })
  }

  async function completeOrder(order) {
    const actionKey = buildOrderActionKey(order, 'complete')
    if (savingOrderActionKey.value) return

    savingOrderActionKey.value = actionKey

    try {
      await orderHttp.patch(`/orders/${order.id}/status`, {
        source: normalizeOrderSource(order.order_source),
        status: 'completed',
        description: 'Orden completada desde acción rápida administrativa.',
      })
      showSnackbar({ type: 'success', message: 'Orden completada correctamente' })
      await loadOrders()
    } catch {
      showSnackbar({ type: 'error', message: 'No se pudo completar la orden' })
    } finally {
      savingOrderActionKey.value = ''
    }
  }

  function canCompleteOrder(order) {
    const status = normalizeAdminOrderStatus(order?.status)
    return !['completed', 'delivered', 'cancelled', 'canceled', 'refunded'].includes(status)
  }

  function buildOrderActionKey(order, action) {
    return `${buildOrderSelectionKey(order)}:${action}`
  }

  function isOrderActionLoading(order, action) {
    return savingOrderActionKey.value === buildOrderActionKey(order, action)
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
    if (bulkSaving.value) return
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
      bulkErrors.description = ''
    }
  }

  async function submitBulkAction() {
    if (bulkSaving.value) return

    validateBulkField('action')
    validateBulkField('status')
    validateBulkField('payment_status')

    if (bulkErrors.action || bulkErrors.status || bulkErrors.payment_status) {
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
                  successCount += 1
                  continue
                }

                if (bulkForm.action === 'change_payment_status') {
                  await orderHttp.patch(`/orders/${targetOrder.id}/payment-status`, {
                    source: normalizeOrderSource(targetOrder.order_source),
                    payment_status: bulkForm.payment_status,
                    description: bulkForm.description.trim(),
                  })
                  successCount += 1
                  continue
                }

                if (bulkForm.action === 'deactivate') {
                  await orderHttp.patch(`/orders/${targetOrder.id}/deactivate`, {
                    source: normalizeOrderSource(targetOrder.order_source),
                    description: bulkForm.description.trim(),
                  })
                  successCount += 1
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
    if (savingStatusChange.value) return

    validateStatusField('status')
    if (statusErrors.status || !selectedOrder.value) return

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
            savingStatusChange.value = true

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
            } finally {
              savingStatusChange.value = false
            }
          },
        },
      ],
    })
  }

  // Reutiliza la exportación compartida del admin y conserva columnas, textos y formatos previos.
  function buildOrderExportColumns() {
    return [
      { header: 'Orden', value: (order) => order.order_number || `#${order.id}` },
      { header: 'Cliente', value: (order) => order.customer_name || 'Cliente' },
      { header: 'Correo', value: (order) => order.customer_email || 'Sin correo' },
      { header: 'Fecha', value: (order) => formatDate(order.created_at), width: 16 },
      {
        header: 'Total',
        value: (order) => formatCurrency(order.total),
        excelValue: (order) => Number(order.total || 0),
        excelType: 'currency',
        align: 'right',
        width: 15,
      },
      { header: 'Estado', value: (order) => statusLabel(order.status), width: 15 },
      { header: 'Pago', value: (order) => paymentLabel(order.payment_status), width: 15 },
    ]
  }

  function exportOrders(format) {
    return exportData({
      format,
      fileBaseName: 'ordenes-admin',
      sheetName: 'Órdenes',
      title: 'Órdenes',
      subtitle: 'Resumen exportado desde la gestión administrativa de órdenes.',
      columns: buildOrderExportColumns(),
      rows: orders.value,
      landscape: true,
      emptyMessage: 'No hay órdenes para exportar.',
    })
  }

  onMounted(loadOrders)

  onBeforeUnmount(() => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
  })

  return {
    activeFilterCount,
    allSelected,
    applyFilters,
    buildOrderDetailRoute,
    bulkErrors,
    bulkForm,
    bulkSaving,
    clearAllFilters,
    closeBulkModal,
    closeDetailModal,
    closePaymentStatusModal,
    closeStatusModal,
    canCompleteOrder,
    confirmCompleteOrder,
    confirmDeactivateOrder,
    debouncedLoad,
    detailLoading,
    detailOrder,
    exportingFormat,
    exportOrders,
    filters,
    formatCurrency,
    formatDate,
    formatDateTime,
    goToOrderDetail,
    isOrderActionLoading,
    isOrderSelected,
    loading,
    openBulkActionsModal,
    openDetailModal,
    openPaymentStatusModal,
    openStatusModal,
    orders,
    pagination,
    paymentBadgeClass,
    paymentErrors,
    paymentForm,
    paymentLabel,
    savingOrderActionKey,
    savingPaymentStatusChange,
    savingStatusChange,
    selectedOrder,
    selectedOrdersCount,
    selectedOrdersPreview,
    showBulkModal,
    showDetailModal,
    showPaymentStatusModal,
    showStatusModal,
    stats,
    statusBadgeClass,
    statusErrors,
    statusForm,
    statusLabel,
    submitBulkAction,
    submitPaymentStatusChange,
    submitStatusChange,
    toggleOrderSelection,
    toggleSelectAll,
    validateBulkField,
    validateDateRangeAndApply,
    validatePaymentField,
    validateStatusField,
  }
}
