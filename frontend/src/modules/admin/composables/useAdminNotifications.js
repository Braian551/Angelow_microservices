import { computed, ref } from 'vue'
import { orderHttp } from '../../../services/http'

const ORDER_NOTIFICATIONS_REFRESH_MS = 20000
const ORDER_NOTIFICATIONS_LIMIT = 25
const ORDER_BOOTSTRAP_LOOKBACK_HOURS = 72
const MAX_BOOTSTRAP_NOTIFICATIONS = 24
const MAX_VISIBLE_NOTIFICATIONS = 60

const MODULE_ROUTES = {
  orders: '/admin/ordenes',
  payments: '/admin/pagos',
  invoices: '/admin/facturas',
}

const MODULE_LABELS = {
  orders: 'Órdenes',
  payments: 'Pagos',
  invoices: 'Facturas',
}

const ORDER_STATUS_LABELS = {
  created: 'Creada',
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revision',
  en_revision: 'En revision',
  processing: 'En proceso',
  shipped: 'Enviado',
  delivered: 'Entregado',
  completed: 'Completado',
  cancelled: 'Cancelado',
  canceled: 'Cancelado',
  refunded: 'Reembolsado',
}

const PAYMENT_STATUS_LABELS = {
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revision',
  en_revision: 'En revision',
  paid: 'Pagado',
  verified: 'Verificado',
  pending_refund: 'Reembolso en proceso',
  failed: 'Fallido',
  refunded: 'Reembolsado',
  canceled: 'Cancelado',
  cancelled: 'Cancelado',
  transfer: 'Transferencia',
}

const PAYMENT_REVIEW_STATUSES = new Set(['pending', 'pending_payment', 'in_review', 'en_revision', 'created'])

const notifications = ref([])
const unreadCount = computed(() => notifications.value.filter((item) => !item?.read_at).length)

const unreadByModule = computed(() => {
  const counters = {
    orders: 0,
    payments: 0,
    invoices: 0,
  }

  notifications.value.forEach((item) => {
    if (item?.read_at) return

    const moduleKey = String(item?.module_key || '').trim().toLowerCase()
    if (Object.prototype.hasOwnProperty.call(counters, moduleKey)) {
      counters[moduleKey] += 1
    }
  })

  return counters
})

let loadingNotifications = false
let orderSnapshot = new Map()
let orderNotificationsTimer = null
let activeSubscribers = 0
let lastVisitedRoutePath = null

function startAdminNotifications() {
  activeSubscribers += 1

  if (orderNotificationsTimer !== null) {
    return
  }

  loadNotifications({ initialize: true })

  orderNotificationsTimer = window.setInterval(() => {
    if (document.visibilityState === 'hidden') {
      return
    }

    loadNotifications()
  }, ORDER_NOTIFICATIONS_REFRESH_MS)
}

function stopAdminNotifications() {
  activeSubscribers = Math.max(0, activeSubscribers - 1)

  if (activeSubscribers > 0) {
    return
  }

  if (orderNotificationsTimer !== null) {
    window.clearInterval(orderNotificationsTimer)
    orderNotificationsTimer = null
  }

  lastVisitedRoutePath = null
}

async function loadNotifications({ initialize = false } = {}) {
  if (loadingNotifications) {
    return
  }

  loadingNotifications = true

  try {
    const rows = await fetchRecentOrdersForNotifications()

    if (rows.length === 0) {
      if (initialize) {
        orderSnapshot = new Map()
      }
      return
    }

    const currentSnapshot = buildOrderSnapshot(rows)

    if (initialize || orderSnapshot.size === 0) {
      const bootstrapEvents = buildBootstrapOrderEvents(rows)
      if (bootstrapEvents.length > 0) {
        mergeNotifications(bootstrapEvents)
      }

      orderSnapshot = currentSnapshot
      return
    }

    const freshNotifications = buildOrderEvents(rows)

    if (freshNotifications.length > 0) {
      mergeNotifications(freshNotifications)
    }

    orderSnapshot = currentSnapshot
  } catch {
    // El panel debe continuar operativo aunque falle el polling.
  } finally {
    loadingNotifications = false
  }
}

function mergeNotifications(incomingNotifications) {
  if (!Array.isArray(incomingNotifications) || incomingNotifications.length === 0) {
    return
  }

  const mergedById = new Map(
    notifications.value.map((item) => [String(item?.id || ''), item]),
  )

  incomingNotifications.forEach((notification) => {
    const notificationId = String(notification?.id || '')
    if (!notificationId) {
      return
    }

    const previous = mergedById.get(notificationId)
    if (previous) {
      mergedById.set(notificationId, {
        ...notification,
        read_at: previous.read_at || notification.read_at || null,
      })
      return
    }

    mergedById.set(notificationId, notification)
  })

  notifications.value = sortNotifications(Array.from(mergedById.values())).slice(0, MAX_VISIBLE_NOTIFICATIONS)
}

function markNotificationAsRead(notificationId) {
  const now = new Date().toISOString()

  notifications.value = notifications.value.map((item) => {
    if (String(item?.id || '') !== String(notificationId)) {
      return item
    }

    if (item?.read_at) {
      return item
    }

    return {
      ...item,
      read_at: now,
    }
  })
}

function markAllNotificationsAsRead() {
  const now = new Date().toISOString()
  notifications.value = notifications.value.map((item) => ({
    ...item,
    read_at: item?.read_at || now,
  }))
}

function markModuleAsRead(moduleKey) {
  const normalizedModule = String(moduleKey || '').trim().toLowerCase()
  if (!normalizedModule) {
    return
  }

  const now = new Date().toISOString()

  notifications.value = notifications.value.map((item) => {
    const itemModule = String(item?.module_key || '').trim().toLowerCase()
    if (itemModule !== normalizedModule || item?.read_at) {
      return item
    }

    return {
      ...item,
      read_at: now,
    }
  })
}

function markRouteNotificationsAsRead(routePath) {
  const normalizedPath = String(routePath || '').trim().toLowerCase()
  if (!normalizedPath) {
    return
  }

  if (lastVisitedRoutePath === null) {
    lastVisitedRoutePath = normalizedPath
    return
  }

  if (lastVisitedRoutePath === normalizedPath) {
    return
  }

  lastVisitedRoutePath = normalizedPath

  if (normalizedPath.startsWith('/admin/ordenes')) {
    markModuleAsRead('orders')
  }

  if (normalizedPath.startsWith('/admin/pagos')) {
    markModuleAsRead('payments')
  }

  if (normalizedPath.startsWith('/admin/facturas')) {
    markModuleAsRead('invoices')
  }
}

function resolveNotificationRoute(notification) {
  const rawRoute = String(notification?.route || '').trim()
  if (rawRoute) {
    return rawRoute
  }

  const moduleKey = String(notification?.module_key || '').trim().toLowerCase()
  return MODULE_ROUTES[moduleKey] || MODULE_ROUTES.orders
}

function resolveNotificationModuleLabel(moduleKey) {
  const normalized = String(moduleKey || '').trim().toLowerCase()
  return MODULE_LABELS[normalized] || 'Sistema'
}

function normalizeStatus(value) {
  return String(value || '').trim().toLowerCase()
}

function isCancelledStatus(status) {
  const normalizedStatus = normalizeStatus(status)
  return normalizedStatus === 'cancelled' || normalizedStatus === 'canceled'
}

function buildOrderKey(order) {
  const source = String(order.order_source || order.source || 'microservice').trim().toLowerCase()
  return `${source}:${String(order.id || '').trim()}`
}

function buildOrderSnapshot(rows) {
  const snapshot = new Map()
  rows.forEach((row) => {
    snapshot.set(buildOrderKey(row), row)
  })
  return snapshot
}

function buildBootstrapOrderEvents(rows) {
  const bootstrapEvents = []

  rows.forEach((row) => {
    const key = buildOrderKey(row)
    const referenceDate = row.created_at || row.updated_at

    if (!isRecentTimestamp(referenceDate, ORDER_BOOTSTRAP_LOOKBACK_HOURS)) {
      return
    }

    bootstrapEvents.push(buildNewOrderEvent(row, key))

    const paymentReviewEvent = buildPaymentReviewEvent(row, key)
    if (paymentReviewEvent) {
      bootstrapEvents.push(paymentReviewEvent)
    }
  })

  return sortNotifications(bootstrapEvents).slice(0, MAX_BOOTSTRAP_NOTIFICATIONS)
}

function buildOrderEvents(rows) {
  const events = []

  rows.forEach((row) => {
    const key = buildOrderKey(row)
    const previous = orderSnapshot.get(key)

    if (!previous) {
      events.push(buildNewOrderEvent(row, key))

      const paymentReviewEvent = buildPaymentReviewEvent(row, key)
      if (paymentReviewEvent) {
        events.push(paymentReviewEvent)
      }

      return
    }

    const previousStatus = normalizeStatus(previous.status || previous.order_status)
    const currentStatus = normalizeStatus(row.status || row.order_status)

    if (!isCancelledStatus(previousStatus) && isCancelledStatus(currentStatus)) {
      events.push(buildOrderCancellationEvent(row, key))
    }

    const previousPaymentStatus = normalizeStatus(previous.payment_status)
    const currentPaymentStatus = normalizeStatus(row.payment_status)

    if (previousPaymentStatus !== currentPaymentStatus) {
      events.push({
        id: `payment-${key}-${String(row.updated_at || Date.now())}-${currentPaymentStatus}`,
        type: 'payment',
        module_key: 'payments',
        route: MODULE_ROUTES.payments,
        message: buildPaymentChangeMessage(row, previousPaymentStatus, currentPaymentStatus),
        created_at: row.updated_at || row.created_at || new Date().toISOString(),
        read_at: null,
      })
    }

    if (invoiceBecameAvailable(previous, row)) {
      const invoiceNumber = normalizeInvoiceValue(row.invoice_number)
      const invoiceLabel = invoiceNumber ? ` ${invoiceNumber}` : ''
      events.push({
        id: `invoice-${key}-${String(row.updated_at || Date.now())}-${invoiceNumber || 'new'}`,
        type: 'invoice',
        module_key: 'invoices',
        route: MODULE_ROUTES.invoices,
        message: `Factura${invoiceLabel} generada para la orden ${formatOrderLabel(row)}.`,
        created_at: row.updated_at || row.created_at || new Date().toISOString(),
        read_at: null,
      })
    }
  })

  return sortNotifications(events)
}

function buildNewOrderEvent(row, key) {
  return {
    id: `new-${key}-${String(row.created_at || row.updated_at || Date.now())}`,
    type: 'order',
    module_key: 'orders',
    route: resolveOrderRoute(row),
    message: `Nueva orden ${formatOrderLabel(row)} de ${formatCustomerName(row)} por ${formatOrderTotal(row.total)}.`,
    created_at: row.created_at || row.updated_at || new Date().toISOString(),
    read_at: null,
  }
}

function buildOrderCancellationEvent(row, key) {
  return {
    id: `cancel-${key}-${String(row.updated_at || Date.now())}`,
    type: 'system',
    module_key: 'orders',
    route: resolveOrderRoute(row),
    message: `La orden ${formatOrderLabel(row)} fue cancelada.`,
    created_at: row.updated_at || row.created_at || new Date().toISOString(),
    read_at: null,
  }
}

function buildPaymentReviewEvent(row, key) {
  const currentPaymentStatus = normalizeStatus(row.payment_status)
  if (!requiresPaymentReview(currentPaymentStatus)) {
    return null
  }

  return {
    id: `payment-review-${key}-${currentPaymentStatus}-${String(row.created_at || row.updated_at || Date.now())}`,
    type: 'payment',
    module_key: 'payments',
    route: MODULE_ROUTES.payments,
    message: buildPaymentReviewMessage(row, currentPaymentStatus),
    created_at: row.updated_at || row.created_at || new Date().toISOString(),
    read_at: null,
  }
}

function requiresPaymentReview(status) {
  return PAYMENT_REVIEW_STATUSES.has(normalizeStatus(status))
}

function buildPaymentReviewMessage(order, status) {
  const normalizedStatus = normalizeStatus(status)

  if (['in_review', 'en_revision'].includes(normalizedStatus)) {
    return `La orden ${formatOrderLabel(order)} tiene un pago en revision.`
  }

  return `La orden ${formatOrderLabel(order)} tiene un pago pendiente de validacion.`
}

function sortNotifications(items) {
  return [...items].sort((leftItem, rightItem) => {
    return toTimestamp(rightItem?.created_at) - toTimestamp(leftItem?.created_at)
  })
}

function toTimestamp(value) {
  const timestamp = new Date(value || 0).getTime()
  return Number.isFinite(timestamp) ? timestamp : 0
}

function isRecentTimestamp(value, lookbackHours) {
  const timestamp = toTimestamp(value)
  if (timestamp <= 0) {
    return false
  }

  const windowMs = Number(lookbackHours || 0) * 60 * 60 * 1000
  if (windowMs <= 0) {
    return true
  }

  return timestamp >= Date.now() - windowMs
}

function buildPaymentChangeMessage(order, oldStatus, newStatus) {
  const oldLabel = PAYMENT_STATUS_LABELS[oldStatus] || (oldStatus ? toSentenceCase(oldStatus) : null)
  const newLabel = PAYMENT_STATUS_LABELS[newStatus] || (newStatus ? toSentenceCase(newStatus) : 'Actualizado')

  if (!oldLabel) {
    return `El estado de pago de ${formatOrderLabel(order)} ahora es ${newLabel}.`
  }

  return `El pago de ${formatOrderLabel(order)} cambió de ${oldLabel} a ${newLabel}.`
}

function invoiceBecameAvailable(previous, current) {
  const previousInvoice = normalizeInvoiceValue(previous.invoice_number)
  const currentInvoice = normalizeInvoiceValue(current.invoice_number)

  if (currentInvoice && currentInvoice !== previousInvoice) {
    return true
  }

  const previousInvoiceDate = normalizeInvoiceValue(previous.invoice_date)
  const currentInvoiceDate = normalizeInvoiceValue(current.invoice_date)

  return !previousInvoiceDate && Boolean(currentInvoiceDate)
}

function normalizeInvoiceValue(value) {
  return String(value || '').trim()
}

async function fetchRecentOrdersForNotifications() {
  const response = await orderHttp.get('/admin/orders', {
    params: { limit: ORDER_NOTIFICATIONS_LIMIT },
  })

  const payload = response?.data?.data || {}
  const rows = Array.isArray(payload) ? payload : (Array.isArray(payload.rows) ? payload.rows : [])

  return rows.map((row) => ({
    ...row,
    status: normalizeStatus(row.status || row.order_status || 'pending'),
    payment_status: normalizeStatus(row.payment_status || 'pending'),
  }))
}

function resolveOrderRoute(order) {
  const orderId = Number(order?.id || 0)
  if (!Number.isFinite(orderId) || orderId <= 0) {
    return MODULE_ROUTES.orders
  }

  return `${MODULE_ROUTES.orders}/${orderId}`
}

function formatOrderLabel(order) {
  const value = String(order?.order_number || '').trim()
  if (value) return value

  const orderId = String(order?.id || '').trim()
  return orderId ? `#${orderId}` : 'N/A'
}

function formatCustomerName(order) {
  return String(order?.user_name || order?.customer_name || order?.billing_name || 'Cliente').trim() || 'Cliente'
}

function formatOrderTotal(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function toSentenceCase(value) {
  const normalized = String(value || '').trim().toLowerCase().replace(/[_-]+/g, ' ')
  if (!normalized) {
    return ''
  }

  return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

export function useAdminNotifications() {
  return {
    notifications,
    unreadCount,
    unreadByModule,
    loadNotifications,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    markModuleAsRead,
    markRouteNotificationsAsRead,
    resolveNotificationRoute,
    resolveNotificationModuleLabel,
    startAdminNotifications,
    stopAdminNotifications,
    formatOrderLabel,
    formatCustomerName,
    formatOrderTotal,
    ORDER_STATUS_LABELS,
  }
}
