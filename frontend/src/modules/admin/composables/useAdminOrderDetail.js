import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { catalogHttp, orderHttp, paymentHttp, shippingHttp } from '../../../services/http'
import { resolveUploadUrl } from '../../../utils/media'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import {
  buildCheckoutAddressLine,
  labelCheckoutAddressType,
  normalizeCheckoutAddress,
} from '../../checkout/utils/checkoutHelpers'
import {
  ADMIN_EDITABLE_ORDER_STATUSES,
  getHistoryFieldLabel,
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  normalizeAdminOrderStatus,
  translateHistoryValue,
} from '../utils/orderPresentation'

// =====================================================
// Dependencias y composables reutilizados
// =====================================================
const HISTORY_TYPE_COLORS = Object.freeze({
  status: '#0077b6',
  payment_status: '#10b981',
  shipping: '#f59e0b',
  notes: '#6366f1',
  items: '#ec4899',
  other: '#64748b',
})

const HISTORY_TYPE_ICONS = Object.freeze({
  status: 'fa-rotate',
  payment_status: 'fa-credit-card',
  shipping: 'fa-truck',
  notes: 'fa-note-sticky',
  items: 'fa-box-open',
  other: 'fa-pen',
})

function normalizeText(value) {
  return String(value || '').trim()
}

function resolvePaymentProofUrl(value) {
  try {
    return resolveUploadUrl(value)
  } catch {
    const raw = String(value || '').trim()
    return raw ? (raw.startsWith('/') ? raw : `/${raw}`) : ''
  }
}

function normalizeProofPath(value) {
  const proofPath = String(value || '').trim()
  if (!proofPath) return ''

  const normalized = proofPath.replace(/\\/g, '/')

  if (/^https?:\/\//i.test(normalized)) return normalized
  if (normalized.startsWith('/uploads/') || normalized.startsWith('uploads/')) return normalized

  if (normalized.startsWith('/payment_proofs/') || normalized.startsWith('payment_proofs/')) {
    return normalized.replace(/^\/?payment_proofs\/?/, 'uploads/payment_proofs/')
  }

  return `uploads/payment_proofs/${normalized.replace(/^\/+/, '')}`
}

function extractPaymentRows(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload)) return payload
  return []
}

function extractAddressRows(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload)) return payload
  return []
}

function textMatches(leftValue, rightValue) {
  const left = normalizeText(leftValue).toLowerCase()
  const right = normalizeText(rightValue).toLowerCase()

  if (!left || !right) return false
  return left === right || left.includes(right) || right.includes(left)
}

function normalizeHistoryToken(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/-/g, '_')
}

export function useAdminOrderDetail() {
  const route = useRoute()
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Ruta e identificación
  // =====================================================
  const orderId = computed(() => Number(route.params.id))
  const orderSource = computed(() => String(route.query.vista || '').toLowerCase() === 'archivo' ? 'legacy' : 'microservice')

  // =====================================================
  // Estado principal
  // =====================================================
  const loading = ref(true)
  const saving = ref(false)
  const order = ref(null)
  const items = ref([])
  const history = ref([])
  const paymentRecord = ref(null)
  const productImageById = ref({})
  const shippingAddresses = ref([])
  const paymentProofUnavailable = ref(false)
  const expandedHistory = ref(false)

  // =====================================================
  // Gestión de modales
  // =====================================================
  const showProofModal = ref(false)
  const showEditModal = ref(false)
  const showStatusModal = ref(false)
  const showPaymentStatusModal = ref(false)

  // =====================================================
  // Formularios y selección
  // =====================================================
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

  const editForm = reactive({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address: '',
    notes: '',
  })

  const editErrors = reactive({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address: '',
    notes: '',
  })

  // =====================================================
  // Datos derivados
  // =====================================================
  const headerTitle = computed(() => {
    if (order.value?.order_number) {
      return `Detalle de Orden ${order.value.order_number}`
    }

    return `Detalle de Orden #${orderId.value}`
  })

  const breadcrumbOrderLabel = computed(() => {
    if (order.value?.order_number) {
      return order.value.order_number
    }

    return `#${orderId.value}`
  })

  const addressSourceLabel = computed(() => {
    if (!order.value) return 'N/A'
    if (Number(order.value.gps_used || 0) === 1) return 'GPS usado'
    if (order.value.gps_latitude && order.value.gps_longitude) return 'Con coordenadas'
    return 'Manual'
  })

  const selectedShippingAddress = computed(() => findBestShippingAddressMatch(shippingAddresses.value, order.value))

  const deliveryLocationLabel = computed(() => {
    const address = selectedShippingAddress.value

    if (address) {
      const zone = normalizeText(address.neighborhood)
      if (zone) return zone

      const city = normalizeText(address.city)
      if (city) return city
    }

    const fallbackCity = normalizeText(order.value?.shipping_city)
    return fallbackCity || 'Por definir'
  })

  const shippingAddressOriginLabel = computed(() => {
    if (selectedShippingAddress.value) return 'Dirección guardada del cliente'
    return addressSourceLabel.value
  })

  const shippingRecipientLabel = computed(() => {
    const address = selectedShippingAddress.value
    const recipientName = normalizeText(address?.recipient_name || order.value?.customer_name)
    const recipientPhone = normalizeText(address?.recipient_phone || order.value?.customer_phone)

    if (recipientName && recipientPhone) return `${recipientName} (${recipientPhone})`
    return recipientName || recipientPhone || 'Sin destinatario'
  })

  const shippingAddressLine = computed(() => {
    const fromProfile = selectedShippingAddress.value ? normalizeText(buildCheckoutAddressLine(selectedShippingAddress.value)) : ''
    if (fromProfile) return fromProfile

    const fallbackAddress = normalizeText(order.value?.shipping_address)
    return fallbackAddress || 'No se ha proporcionado dirección de envío'
  })

  const shippingStreetLabel = computed(() => {
    const fromProfile = normalizeText(selectedShippingAddress.value?.address)
    if (fromProfile) return fromProfile

    const fallbackAddress = normalizeText(order.value?.shipping_address)
    return fallbackAddress || 'No se ha proporcionado dirección de envío'
  })

  const shippingComplementLabel = computed(() => normalizeText(selectedShippingAddress.value?.complement))

  const shippingZoneLabel = computed(() => {
    const fromProfile = normalizeText(selectedShippingAddress.value?.neighborhood)
    if (fromProfile) return fromProfile

    const fallbackZone = normalizeText(order.value?.shipping_city)
    return fallbackZone || 'No disponible'
  })

  const shippingBuildingTypeLabel = computed(() => {
    if (!selectedShippingAddress.value) return ''

    return labelCheckoutAddressType(selectedShippingAddress.value.building_type || selectedShippingAddress.value.address_type)
  })

  const shippingBuildingNameLabel = computed(() => normalizeText(selectedShippingAddress.value?.building_name))

  const shippingMapCoords = computed(() => {
    const address = selectedShippingAddress.value

    if (address?.gps_latitude && address?.gps_longitude) {
      return { lat: address.gps_latitude, lng: address.gps_longitude }
    }

    if (order.value?.gps_latitude && order.value?.gps_longitude) {
      return { lat: order.value.gps_latitude, lng: order.value.gps_longitude }
    }

    return null
  })

  const paymentProofIsImage = computed(() => {
    return Boolean(paymentRecord.value?.proof_url && /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i.test(paymentRecord.value.proof_url))
  })

  const hiddenHistoryCount = computed(() => Math.max(history.value.length - 1, 0))
  const showHistoryToggle = computed(() => hiddenHistoryCount.value > 0)
  const visibleHistory = computed(() => (expandedHistory.value ? history.value : history.value.slice(0, 1)))

  // =====================================================
  // Helpers internos
  // =====================================================
  function findBestShippingAddressMatch(rows = [], currentOrder = null) {
    if (!Array.isArray(rows) || rows.length === 0 || !currentOrder) return null

    const shippingAddressId = Number(currentOrder.shipping_address_id || currentOrder.billing_address_id || 0)

    if (shippingAddressId > 0) {
      const byId = rows.find((row) => Number(row?.id || 0) === shippingAddressId)
      if (byId) return byId
    }

    const orderAddress = normalizeText(currentOrder.shipping_address)
    const orderZone = normalizeText(currentOrder.shipping_city)

    if (orderAddress || orderZone) {
      const byContent = rows.find((row) => {
        const addressLine = normalizeText(buildCheckoutAddressLine(row))
        const addressValue = normalizeText(row?.address)
        const zoneValue = normalizeText(row?.neighborhood)
        const cityValue = normalizeText(row?.city)

        const addressMatches = orderAddress
          ? textMatches(addressLine, orderAddress) || textMatches(addressValue, orderAddress)
          : true

        const zoneMatches = orderZone
          ? textMatches(zoneValue, orderZone) || textMatches(cityValue, orderZone)
          : true

        return addressMatches && zoneMatches
      })

      if (byContent) return byContent
    }

    return rows.find((row) => Boolean(row?.is_default)) || rows[0] || null
  }

  function pickPaymentForCurrentOrder(rows = []) {
    if (!Array.isArray(rows) || rows.length === 0) return null
    return rows.find((row) => Number(row?.order_id || 0) === orderId.value) || rows[0] || null
  }

  function normalizePaymentRecord(rawPayment = {}) {
    const proofPath = normalizeProofPath(rawPayment.proof_url || rawPayment.payment_proof || '')
    const rawStatus = String(rawPayment.status || rawPayment.payment_status || '').toLowerCase().trim()

    return {
      ...rawPayment,
      status: rawStatus === 'approved' ? 'verified' : (rawStatus === 'rejected' ? 'failed' : (rawStatus || 'pending')),
      proof_url: resolvePaymentProofUrl(proofPath),
      proof_name: rawPayment.proof_name || (proofPath ? proofPath.split('/').pop() : ''),
      proof_exists: rawPayment.proof_exists !== false,
    }
  }

  function normalizeOrder(rawOrder = {}) {
    return {
      ...rawOrder,
      id: Number(rawOrder.id || orderId.value || 0),
      user_id: rawOrder.user_id ? String(rawOrder.user_id) : '',
      order_source: rawOrder.order_source || orderSource.value,
      order_number: rawOrder.order_number || `#${rawOrder.id || orderId.value}`,
      created_at: rawOrder.created_at || null,
      status: normalizeAdminOrderStatus(rawOrder.status || rawOrder.order_status || 'pending'),
      payment_status: rawOrder.payment_status || 'pending',
      payment_method: rawOrder.payment_method || '',
      customer_name: rawOrder.user_name || rawOrder.customer_name || rawOrder.billing_name || 'Cliente no registrado',
      customer_email: rawOrder.user_email || rawOrder.customer_email || rawOrder.billing_email || '',
      customer_phone: rawOrder.user_phone || rawOrder.customer_phone || rawOrder.billing_phone || '',
      subtotal: Number(rawOrder.subtotal || 0),
      shipping_cost: Number(rawOrder.shipping_cost || rawOrder.shipping || 0),
      discount_amount: Number(rawOrder.discount_amount || 0),
      total: Number(rawOrder.total || 0),
      shipping_address: rawOrder.shipping_address || rawOrder.address_current || rawOrder.billing_address || '',
      shipping_address_id: Number(rawOrder.shipping_address_id || 0) || null,
      shipping_city: rawOrder.shipping_city || rawOrder.billing_city || '',
      billing_address_id: Number(rawOrder.billing_address_id || 0) || null,
      notes: rawOrder.notes || '',
      gps_used: rawOrder.gps_used || 0,
      gps_latitude: rawOrder.gps_latitude || null,
      gps_longitude: rawOrder.gps_longitude || null,
    }
  }

  function pickCatalogImage(payload = {}) {
    const images = Array.isArray(payload.images) ? payload.images : []
    const variantImages = Array.isArray(payload.variant_images) ? payload.variant_images : []
    const product = payload.product || {}

    return [
      ...images.map((row) => row?.image_path || row?.url || row?.image || ''),
      ...variantImages.map((row) => row?.image_path || row?.url || row?.image || ''),
      product?.main_image_path,
      product?.image,
      product?.imagen,
      product?.image_url,
    ].map((value) => normalizeText(value)).find(Boolean) || ''
  }

  function getHistoryType(entry) {
    const field = normalizeHistoryToken(entry?.field_changed)

    if (field === 'status' || field === 'order_status') return 'status'
    if (field === 'payment_status') return 'payment_status'
    if (field.includes('shipping') || field.includes('address') || field.includes('city')) return 'shipping'
    if (field.includes('note')) return 'notes'
    if (field.includes('item') || field.includes('product')) return 'items'
    return 'other'
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadProductImages(rows = []) {
    const productIds = [...new Set((Array.isArray(rows) ? rows : [])
      .map((row) => Number(row?.product_id || 0))
      .filter((id) => id > 0))]

    if (productIds.length === 0) {
      productImageById.value = {}
      return
    }

    const entries = await Promise.all(productIds.map(async (productId) => {
      try {
        const response = await catalogHttp.get(`/admin/products/${productId}`)
        const payload = response?.data?.data || {}
        return [productId, pickCatalogImage(payload)]
      } catch {
        return [productId, '']
      }
    }))

    const nextMap = {}

    entries.forEach(([productId, imagePath]) => {
      if (imagePath) {
        nextMap[productId] = imagePath
      }
    })

    productImageById.value = nextMap
  }

  async function loadShippingAddresses() {
    shippingAddresses.value = []

    const userId = normalizeText(order.value?.user_id)
    const userEmail = normalizeText(order.value?.customer_email)

    if (!userId && !userEmail) return

    try {
      const response = await shippingHttp.get('/shipping/addresses', {
        params: {
          user_id: userId || undefined,
          user_email: userEmail || undefined,
        },
      })

      shippingAddresses.value = extractAddressRows(response.data)
        .map((row) => normalizeCheckoutAddress(row))
        .filter((row) => Number(row?.id || 0) > 0)
    } catch {
      shippingAddresses.value = []
    }
  }

  async function loadPaymentRecord() {
    paymentProofUnavailable.value = false

    try {
      const response = await paymentHttp.get('/admin/payments', { params: { order_id: orderId.value, per_page: 1 } })
      let rows = extractPaymentRows(response.data)

      if (rows.length === 0) {
        const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
        rows = extractPaymentRows(fallbackResponse.data)
          .filter((row) => Number(row?.order_id || 0) === orderId.value)
      }

      const selectedPayment = pickPaymentForCurrentOrder(rows)
      paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
    } catch {
      try {
        const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
        const rows = extractPaymentRows(fallbackResponse.data)
          .filter((row) => Number(row?.order_id || 0) === orderId.value)

        const selectedPayment = pickPaymentForCurrentOrder(rows)
        paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
      } catch {
        paymentRecord.value = null
      }
    }
  }

  async function loadOrder() {
    loading.value = true

    try {
      const response = await orderHttp.get(`/orders/${orderId.value}`, { params: { source: orderSource.value } })
      const payload = response.data || {}
      const rawOrder = payload.order || payload.data?.order || payload.data || {}
      const rawItems = payload.items || payload.data?.items || []
      const rawHistory = payload.history || payload.data?.history || []

      order.value = normalizeOrder(rawOrder)
      items.value = Array.isArray(rawItems) ? rawItems : []
      void loadProductImages(items.value)
      history.value = Array.isArray(rawHistory) ? rawHistory : []
      expandedHistory.value = false
      await loadShippingAddresses()
      await loadPaymentRecord()
      hydrateEditForm()
      resetStatusForm()
      resetPaymentForm()
    } catch {
      showSnackbar({ type: 'error', message: 'Error cargando el detalle de la orden.' })
      order.value = null
      items.value = []
      productImageById.value = {}
      history.value = []
      shippingAddresses.value = []
      paymentRecord.value = null
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Edición
  // =====================================================
  function hydrateEditForm() {
    if (!order.value) return

    editForm.customer_name = order.value.customer_name || ''
    editForm.customer_email = order.value.customer_email || ''
    editForm.customer_phone = order.value.customer_phone || ''
    editForm.shipping_address = shippingAddressLine.value || order.value.shipping_address || ''
    editForm.notes = order.value.notes || ''
  }

  function resetEditErrors() {
    editErrors.customer_name = ''
    editErrors.customer_email = ''
    editErrors.customer_phone = ''
    editErrors.shipping_address = ''
    editErrors.notes = ''
  }

  function openEditModal() {
    hydrateEditForm()
    resetEditErrors()
    showEditModal.value = true
  }

  function closeEditModal() {
    showEditModal.value = false
  }

  function validateEditField(field) {
    const value = String(editForm[field] || '').trim()

    if (field === 'customer_name') {
      editErrors.customer_name = value.length >= 3 ? '' : 'El nombre debe tener al menos 3 caracteres.'
    }

    if (field === 'customer_email') {
      if (!value) {
        editErrors.customer_email = ''
        return
      }

      editErrors.customer_email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
        ? ''
        : 'Ingresa un email valido.'
    }

    if (field === 'customer_phone') {
      if (!value) {
        editErrors.customer_phone = ''
        return
      }

      editErrors.customer_phone = value.length >= 7 ? '' : 'El teléfono debe tener al menos 7 caracteres.'
    }

    if (field === 'shipping_address') {
      editErrors.shipping_address = value.length >= 5 ? '' : 'La dirección debe tener al menos 5 caracteres.'
    }

    if (field === 'notes') {
      if (!value) {
        editErrors.notes = ''
        return
      }

      editErrors.notes = value.length >= 4 ? '' : 'La nota debe tener al menos 4 caracteres.'
    }
  }

  async function submitEditOrder() {
    validateEditField('customer_name')
    validateEditField('customer_email')
    validateEditField('customer_phone')
    validateEditField('shipping_address')
    validateEditField('notes')

    const hasErrors = Object.values(editErrors).some(Boolean)
    if (hasErrors) return

    showAlert({
      type: 'warning',
      title: 'Confirmar cambios',
      message: 'Se actualizaran los datos principales de la orden.',
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Guardar',
          style: 'primary',
          callback: async () => {
            saving.value = true

            try {
              await orderHttp.patch(`/orders/${orderId.value}`, {
                source: orderSource.value,
                customer_name: editForm.customer_name.trim(),
                customer_email: editForm.customer_email.trim(),
                customer_phone: editForm.customer_phone.trim(),
                shipping_address: editForm.shipping_address.trim(),
                notes: editForm.notes.trim(),
              })
              showSnackbar({ type: 'success', message: 'Orden actualizada correctamente.' })
              closeEditModal()
              await loadOrder()
            } catch {
              showSnackbar({ type: 'error', message: 'No fue posible actualizar la orden.' })
            } finally {
              saving.value = false
            }
          },
        },
      ],
    })
  }

  // =====================================================
  // Estados del pedido
  // =====================================================
  function resetStatusForm() {
    statusForm.status = normalizeAdminOrderStatus(order.value?.status || 'pending')
    statusForm.description = ''
    statusErrors.status = ''
    statusErrors.description = ''
  }

  function openStatusModal() {
    resetStatusForm()
    showStatusModal.value = true
  }

  function closeStatusModal() {
    showStatusModal.value = false
  }

  function validateStatusField(field) {
    if (field === 'status') {
      statusErrors.status = statusForm.status ? '' : 'Selecciona un estado.'
    }

    if (field === 'description') {
      statusErrors.description = ''
    }
  }

  async function submitStatusChange() {
    validateStatusField('status')
    if (statusErrors.status) return

    saving.value = true

    try {
      await orderHttp.patch(`/orders/${orderId.value}/status`, {
        source: orderSource.value,
        status: statusForm.status,
        description: statusForm.description.trim(),
      })
      showSnackbar({ type: 'success', message: 'Estado de la orden actualizado.' })
      closeStatusModal()
      await loadOrder()
    } catch {
      showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de la orden.' })
    } finally {
      saving.value = false
    }
  }

  // =====================================================
  // Estados del pago
  // =====================================================
  function resetPaymentForm() {
    paymentForm.payment_status = order.value?.payment_status || 'pending'
    paymentForm.description = ''
    paymentErrors.payment_status = ''
    paymentErrors.description = ''
  }

  function openPaymentStatusModal() {
    resetPaymentForm()
    showPaymentStatusModal.value = true
  }

  function closePaymentStatusModal() {
    showPaymentStatusModal.value = false
  }

  function validatePaymentField(field) {
    if (field === 'payment_status') {
      paymentErrors.payment_status = paymentForm.payment_status ? '' : 'Selecciona un estado de pago.'
    }

    if (field === 'description') {
      paymentErrors.description = ''
    }
  }

  async function submitPaymentStatusChange() {
    validatePaymentField('payment_status')
    if (paymentErrors.payment_status) return

    saving.value = true

    try {
      await orderHttp.patch(`/orders/${orderId.value}/payment-status`, {
        source: orderSource.value,
        payment_status: paymentForm.payment_status,
        description: paymentForm.description.trim(),
      })
      showSnackbar({ type: 'success', message: 'Estado de pago actualizado.' })
      closePaymentStatusModal()
      await loadOrder()
    } catch {
      showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de pago.' })
    } finally {
      saving.value = false
    }
  }

  // =====================================================
  // Comprobante
  // =====================================================
  function handlePaymentProofError() {
    paymentProofUnavailable.value = true
  }

  function openProofModal() {
    showProofModal.value = true
  }

  function closeProofModal() {
    showProofModal.value = false
  }

  // =====================================================
  // Historial
  // =====================================================
  function toggleHistoryExpansion() {
    expandedHistory.value = !expandedHistory.value
  }

  function historyFieldLabel(field) {
    return getHistoryFieldLabel(field)
  }

  function getHistoryTypeColor(entry) {
    return HISTORY_TYPE_COLORS[getHistoryType(entry)] || HISTORY_TYPE_COLORS.other
  }

  function getHistoryTypeIcon(entry) {
    return HISTORY_TYPE_ICONS[getHistoryType(entry)] || HISTORY_TYPE_ICONS.other
  }

  function getHistoryActorName(entry) {
    return entry?.changed_by_name || entry?.changed_by || 'Sistema'
  }

  function getHistoryActorRole(entry) {
    const role = normalizeHistoryToken(entry?.changed_by_role || '')
    const actorName = normalizeHistoryToken(getHistoryActorName(entry))

    if (role === 'admin' || role === 'administrator') return { label: 'Administrador', variant: 'admin' }
    if (role === 'customer' || role === 'cliente') return { label: 'Cliente', variant: 'customer' }
    if (role === 'delivery' || role === 'repartidor') return { label: 'Repartidor', variant: 'delivery' }
    if (actorName && actorName !== 'sistema' && actorName !== 'system') return { label: 'Administrador', variant: 'admin' }
    return { label: 'Sistema', variant: 'system' }
  }

  // =====================================================
  // Navegación y presentación
  // =====================================================
  function resolveOrderItemImagePath(item = {}) {
    const directPath = normalizeText(
      item.product_image
        || item.image
        || item.image_path
        || item.variant_image
        || item.thumbnail,
    )

    if (directPath) return directPath

    const productId = Number(item.product_id || 0)
    if (productId > 0) {
      return normalizeText(productImageById.value[productId])
    }

    return ''
  }

  function resolveOrderItemImage(item = {}) {
    const imagePath = resolveOrderItemImagePath(item)
    return imagePath ? resolveUploadUrl(imagePath) : ''
  }

  function formatCurrency(value) {
    return `$ ${Number(value || 0).toLocaleString('es-CO')}`
  }

  function formatDateTime(value) {
    if (!value) return 'Sin fecha'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
  }

  function formatTimelineDate(value) {
    if (!value) return 'Sin fecha'

    const date = new Date(value)
    return Number.isNaN(date.getTime())
      ? 'Sin fecha'
      : date.toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      })
  }

  function statusLabel(status) {
    return getOrderStatusLabel(status)
  }

  function paymentLabel(status) {
    return getPaymentStatusLabel(status)
  }

  function paymentMethodLabel(method) {
    return getPaymentMethodLabel(method)
  }

  function statusBadgeClass(status) {
    return getOrderStatusBadgeClass(status)
  }

  function paymentBadgeClass(status) {
    return getPaymentStatusBadgeClass(status)
  }

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(loadOrder)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    ADMIN_EDITABLE_ORDER_STATUSES,
    breadcrumbOrderLabel,
    closeEditModal,
    closePaymentStatusModal,
    closeProofModal,
    closeStatusModal,
    deliveryLocationLabel,
    editErrors,
    editForm,
    formatCurrency,
    formatDateTime,
    formatTimelineDate,
    getHistoryActorName,
    getHistoryActorRole,
    getHistoryTypeColor,
    getHistoryTypeIcon,
    handlePaymentProofError,
    headerTitle,
    hiddenHistoryCount,
    history,
    historyFieldLabel,
    items,
    labelCheckoutAddressType,
    loadOrder,
    loading,
    openEditModal,
    openPaymentStatusModal,
    openProofModal,
    openStatusModal,
    order,
    orderId,
    paymentBadgeClass,
    paymentErrors,
    paymentForm,
    paymentLabel,
    paymentMethodLabel,
    paymentProofIsImage,
    paymentProofUnavailable,
    paymentRecord,
    resolveOrderItemImage,
    resolveOrderItemImagePath,
    saving,
    selectedShippingAddress,
    shippingAddressLine,
    shippingAddressOriginLabel,
    shippingBuildingNameLabel,
    shippingBuildingTypeLabel,
    shippingComplementLabel,
    shippingMapCoords,
    shippingRecipientLabel,
    shippingStreetLabel,
    shippingZoneLabel,
    showEditModal,
    showHistoryToggle,
    showPaymentStatusModal,
    showProofModal,
    showStatusModal,
    statusBadgeClass,
    statusErrors,
    statusForm,
    statusLabel,
    submitEditOrder,
    submitPaymentStatusChange,
    submitStatusChange,
    toggleHistoryExpansion,
    translateHistoryValue,
    validateEditField,
    validatePaymentField,
    validateStatusField,
    visibleHistory,
  }
}
