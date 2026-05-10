import { computed, ref } from 'vue'
import { catalogHttp, notificationHttp, orderHttp } from '../../../services/http'
import {
  buildInventoryTargetRoute,
  buildInventoryVariantLabel,
  normalizeInventoryStatus,
  resolveInventoryThreshold,
} from '../utils/inventoryPresentation'

const ORDER_NOTIFICATIONS_REFRESH_MS = 20000
const ORDER_NOTIFICATIONS_LIMIT = 25
const ORDER_BOOTSTRAP_LOOKBACK_HOURS = 72
const MAX_BOOTSTRAP_NOTIFICATIONS = 24
const MAX_BOOTSTRAP_INVENTORY_NOTIFICATIONS = 12
const MAX_VISIBLE_NOTIFICATIONS = 60

const MODULE_ROUTES = {
  orders: '/admin/ordenes',
  payments: '/admin/pagos',
  invoices: '/admin/facturas',
  inventory: '/admin/inventario',
}

const MODULE_LABELS = {
  orders: 'Órdenes',
  payments: 'Pagos',
  invoices: 'Facturas',
  inventory: 'Inventario',
}

const ORDER_STATUS_LABELS = {
  created: 'Creada',
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revisión',
  en_revision: 'En revisión',
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
  in_review: 'En revisión',
  en_revision: 'En revisión',
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
const dismissedNotificationReadAt = ref({})
const unreadCount = computed(() => notifications.value.filter((item) => !item?.read_at).length)

const unreadByModule = computed(() => {
  const counters = {
    orders: 0,
    payments: 0,
    invoices: 0,
    inventory: 0,
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
let inventorySnapshot = new Map()
let orderNotificationsTimer = null
let activeSubscribers = 0
let lastVisitedRoutePath = null
let dismissedNotificationsLoaded = false
let dismissedNotificationsPromise = null

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

  orderSnapshot = new Map()
  inventorySnapshot = new Map()
  lastVisitedRoutePath = null
  notifications.value = []
  dismissedNotificationReadAt.value = {}
  dismissedNotificationsLoaded = false
  dismissedNotificationsPromise = null
}

async function loadNotifications({ initialize = false } = {}) {
  if (loadingNotifications) {
    return
  }

  loadingNotifications = true

  try {
    await ensureDismissedNotificationsLoaded()

    const [ordersResult, inventoryResult] = await Promise.allSettled([
      fetchRecentOrdersForNotifications(),
      fetchInventoryRowsForNotifications(),
    ])

    const rows = ordersResult.status === 'fulfilled' ? ordersResult.value : []
    const inventoryRows = inventoryResult.status === 'fulfilled' ? inventoryResult.value : []

    if (rows.length === 0 && inventoryRows.length === 0) {
      if (initialize) {
        orderSnapshot = new Map()
        inventorySnapshot = new Map()
      }
      return
    }

    const currentOrderSnapshot = buildOrderSnapshot(rows)
    const currentInventorySnapshot = buildInventorySnapshot(inventoryRows)

    if (initialize || orderSnapshot.size === 0) {
      const bootstrapEvents = buildBootstrapOrderEvents(rows)
      if (bootstrapEvents.length > 0) {
        mergeNotifications(bootstrapEvents)
      }
    } else {
      const freshNotifications = buildOrderEvents(rows)
      if (freshNotifications.length > 0) {
        mergeNotifications(freshNotifications)
      }
    }

    if (initialize || inventorySnapshot.size === 0) {
      const bootstrapInventoryEvents = buildBootstrapInventoryEvents(inventoryRows)
      if (bootstrapInventoryEvents.length > 0) {
        mergeNotifications(bootstrapInventoryEvents)
      }
    } else {
      const freshInventoryNotifications = buildInventoryEvents(inventoryRows)
      if (freshInventoryNotifications.length > 0) {
        mergeNotifications(freshInventoryNotifications)
      }
    }

    orderSnapshot = currentOrderSnapshot
    inventorySnapshot = currentInventorySnapshot

    if (lastVisitedRoutePath) {
      markRouteNotificationsAsRead(lastVisitedRoutePath, { force: true })
    }
  } catch {
    // El panel debe continuar operativo aunque falle el polling.
  } finally {
    loadingNotifications = false
  }
}

async function ensureDismissedNotificationsLoaded() {
  if (dismissedNotificationsLoaded) {
    return
  }

  if (dismissedNotificationsPromise) {
    await dismissedNotificationsPromise
    return
  }

  dismissedNotificationsPromise = (async () => {
    try {
      const response = await notificationHttp.get('/admin/notification-dismissals')
      const rows = Array.isArray(response?.data?.data) ? response.data.data : []
      const nextState = {}

      rows.forEach((row) => {
        const notificationKey = String(row?.notification_key || '').trim()
        if (!notificationKey) {
          return
        }

        nextState[notificationKey] = row?.dismissed_at || new Date().toISOString()
      })

      dismissedNotificationReadAt.value = nextState
    } catch {
      dismissedNotificationReadAt.value = {}
    } finally {
      dismissedNotificationsLoaded = true
      dismissedNotificationsPromise = null
    }
  })()

  await dismissedNotificationsPromise
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

    const persistedReadAt = dismissedNotificationReadAt.value[notificationId] || null
    const previous = mergedById.get(notificationId)
    if (previous) {
      mergedById.set(notificationId, {
        ...notification,
        read_at: previous.read_at || notification.read_at || persistedReadAt,
      })
      return
    }

    mergedById.set(notificationId, {
      ...notification,
      read_at: notification.read_at || persistedReadAt,
    })
  })

  notifications.value = sortNotifications(Array.from(mergedById.values())).slice(0, MAX_VISIBLE_NOTIFICATIONS)
}

function normalizeNotificationKey(notificationKey) {
  return String(notificationKey || '').trim()
}

function rememberDismissedNotifications(notificationKeys, dismissedAt) {
  if (!Array.isArray(notificationKeys) || notificationKeys.length === 0) {
    return
  }

  const nextState = {
    ...dismissedNotificationReadAt.value,
  }

  notificationKeys.forEach((notificationKey) => {
    const normalizedKey = normalizeNotificationKey(notificationKey)
    if (!normalizedKey) {
      return
    }

    nextState[normalizedKey] = nextState[normalizedKey] || dismissedAt
  })

  dismissedNotificationReadAt.value = nextState
}

async function persistDismissedNotifications(notificationKeys, dismissedAt = new Date().toISOString()) {
  const uniqueKeys = Array.from(new Set(
    (Array.isArray(notificationKeys) ? notificationKeys : [])
      .map((notificationKey) => normalizeNotificationKey(notificationKey))
      .filter(Boolean),
  ))

  if (uniqueKeys.length === 0) {
    return
  }

  rememberDismissedNotifications(uniqueKeys, dismissedAt)

  try {
    await notificationHttp.patch('/admin/notification-dismissals', {
      notification_keys: uniqueKeys,
    })
  } catch {
    // Se conserva el estado local para no bloquear la navegación del admin.
  }
}

function markNotificationAsRead(notificationId) {
  const normalizedId = normalizeNotificationKey(notificationId)
  if (!normalizedId) {
    return
  }

  const now = new Date().toISOString()

  notifications.value = notifications.value.map((item) => {
    if (String(item?.id || '') !== normalizedId) {
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

  void persistDismissedNotifications([normalizedId], now)
}

function markAllNotificationsAsRead() {
  const now = new Date().toISOString()
  const unreadKeys = []

  notifications.value = notifications.value.map((item) => ({
    ...item,
    ...(item?.read_at
      ? {}
      : (() => {
          const notificationKey = normalizeNotificationKey(item?.id)
          if (notificationKey) {
            unreadKeys.push(notificationKey)
          }

          return {}
        })()),
    read_at: item?.read_at || now,
  }))

  void persistDismissedNotifications(unreadKeys, now)
}

function markModuleAsRead(moduleKey) {
  const normalizedModule = String(moduleKey || '').trim().toLowerCase()
  if (!normalizedModule) {
    return
  }

  const now = new Date().toISOString()
  const unreadKeys = []

  notifications.value = notifications.value.map((item) => {
    const itemModule = String(item?.module_key || '').trim().toLowerCase()
    if (itemModule !== normalizedModule || item?.read_at) {
      return item
    }

    const notificationKey = normalizeNotificationKey(item?.id)
    if (notificationKey) {
      unreadKeys.push(notificationKey)
    }

    return {
      ...item,
      read_at: now,
    }
  })

  void persistDismissedNotifications(unreadKeys, now)
}

function resolveModuleFromRoute(routePath) {
  if (routePath.startsWith('/admin/ordenes')) {
    return 'orders'
  }

  if (routePath.startsWith('/admin/pagos')) {
    return 'payments'
  }

  if (routePath.startsWith('/admin/facturas')) {
    return 'invoices'
  }

  if (routePath.startsWith('/admin/inventario')) {
    return 'inventory'
  }

  return ''
}

function markRouteNotificationsAsRead(routePath, options = {}) {
  const normalizedPath = String(routePath || '').trim().toLowerCase()
  if (!normalizedPath) {
    return
  }

  const force = Boolean(options?.force)
  const routeChanged = lastVisitedRoutePath !== normalizedPath
  lastVisitedRoutePath = normalizedPath

  if (!force && !routeChanged) {
    return
  }

  const moduleKey = resolveModuleFromRoute(normalizedPath)
  if (moduleKey) {
    markModuleAsRead(moduleKey)
  }
}

function resolveNotificationRoute(notification) {
  const rawRoute = notification?.route
  if (typeof rawRoute === 'string') {
    const normalizedRoute = rawRoute.trim()
    if (normalizedRoute) {
      return normalizedRoute
    }
  }

  if (rawRoute && typeof rawRoute === 'object') {
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

function buildInventoryKey(row) {
  return `variant:${String(row?.id || '').trim()}`
}

function buildInventorySnapshot(rows) {
  const snapshot = new Map()
  rows.forEach((row) => {
    snapshot.set(buildInventoryKey(row), row)
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

function buildBootstrapInventoryEvents(rows) {
  return sortNotifications(
    rows.map((row) => buildInventoryEvent(row, buildInventoryKey(row))),
  ).slice(0, MAX_BOOTSTRAP_INVENTORY_NOTIFICATIONS)
}

function buildInventoryEvents(rows) {
  const events = []

  rows.forEach((row) => {
    const key = buildInventoryKey(row)
    const previous = inventorySnapshot.get(key)
    const currentStatus = resolveInventoryRowStatus(row)

    if (!previous) {
      events.push(buildInventoryEvent(row, key))
      return
    }

    const previousStatus = resolveInventoryRowStatus(previous)
    if (previousStatus === currentStatus) {
      return
    }

    events.push(buildInventoryEvent(row, key, previousStatus))
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
    return `La orden ${formatOrderLabel(order)} tiene un pago en revisión.`
  }

  return `La orden ${formatOrderLabel(order)} tiene un pago pendiente de validación.`
}

function resolveInventoryRowStatus(row) {
  return normalizeInventoryStatus(row?.stock, row)
}

function buildInventoryEvent(row, key, previousStatus = '') {
  const currentStatus = resolveInventoryRowStatus(row)
  const productLabel = formatInventoryProductLabel(row)
  const variantLabel = buildInventoryVariantLabel(row)
  const createdAt = row.updated_at || row.created_at || new Date().toISOString()
  const availableUnits = Number(row.stock || 0)
  const lowStockThreshold = resolveInventoryThreshold(row)

  let message = ''

  if (currentStatus === 'out') {
    message = previousStatus === 'low'
      ? `${productLabel} agotó ${variantLabel}.`
      : `${productLabel} quedó sin stock en ${variantLabel}.`
  } else {
    message = `${productLabel} tiene stock bajo en ${variantLabel} (${availableUnits} de ${lowStockThreshold} unidades).`
  }

  return {
    id: `inventory-${key}-${currentStatus}-${String(createdAt)}`,
    type: 'inventory',
    module_key: 'inventory',
    route: buildInventoryTargetRoute(row),
    message,
    created_at: createdAt,
    read_at: null,
  }
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

async function fetchInventoryRowsForNotifications() {
  const response = await catalogHttp.get('/admin/inventory')
  const payload = response?.data?.data || response?.data || []
  const rows = Array.isArray(payload) ? payload : (Array.isArray(payload.data) ? payload.data : [])

  return rows
    .map((row) => ({
      ...row,
      id: Number(row.id || 0),
      product_id: Number(row.product_id || 0),
      product_name: String(row.product_name || row.name || 'Producto').trim() || 'Producto',
      color_name: String(row.color_name || 'Sin color').trim() || 'Sin color',
      size_label: String(row.size_label || row.size_name || 'Sin talla').trim() || 'Sin talla',
      sku: String(row.sku || '').trim(),
      stock: Number(row.stock || row.quantity || 0),
      low_stock_threshold: resolveInventoryThreshold(row),
      inventory_status: String(row.inventory_status || '').trim().toLowerCase(),
      updated_at: row.updated_at || row.created_at || null,
      created_at: row.created_at || row.updated_at || null,
    }))
    .filter((row) => resolveInventoryRowStatus(row) !== 'active')
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

function formatInventoryProductLabel(row) {
  return String(row?.product_name || row?.name || 'Producto').trim() || 'Producto'
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
