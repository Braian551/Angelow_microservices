import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { authHttp, catalogHttp, orderHttp } from '../../../services/http'
import { resolveMediaUrl } from '../../../utils/media'
import {
  getOrderStatusLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  normalizeOrderStatus,
} from '../../../utils/orderPresentation'
import { useAppShell } from '../../../composables/useAppShell'
import {
  buildInventoryTargetRoute,
  buildInventoryVariantLabel as formatInventoryVariantLabel,
  normalizeInventoryStatus as resolveInventoryStatus,
  resolveInventoryThreshold,
} from '../utils/inventoryPresentation'

/**
 * Composable principal del dashboard administrativo.
 * Carga y gestiona estadísticas del día, métricas históricas, gráficos de pedidos
 * e inventario con actualización en tiempo real vía eventos del shell de la app.
 */

// =====================================================
// Constantes internas
// =====================================================
const ORDER_STATUS_CHART_COLORS = Object.freeze({
  pending: '#f59e0b',
  in_review: '#d97706',
  processing: '#0077b6',
  shipped: '#17a2b8',
  delivered: '#4bb543',
  completed: '#2f855a',
  cancelled: '#ff3333',
  expired: '#64748b',
})

const INITIAL_STATS = () => ([
  { key: 'orders', icon: 'fas fa-receipt', color: 'primary', label: 'Órdenes hoy', value: '0', meta: { change: '0%', changeClass: '', helper: 'vs. ayer' } },
  { key: 'revenue', icon: 'fas fa-dollar-sign', color: 'success', label: 'Ingresos hoy', value: '$ 0', meta: { change: '0%', changeClass: '', helper: 'vs. ayer' } },
  { key: 'customers', icon: 'fas fa-user-plus', color: 'warning', label: 'Nuevos clientes', value: '0', meta: { change: '0%', changeClass: '', helper: 'vs. últimos 7 días' } },
  {
    key: 'inventory',
    icon: 'fas fa-boxes-stacked',
    color: 'info',
    label: 'Variantes activas',
    value: '0',
    pills: [
      { label: 'Activas', value: '0', class: 'pill-success' },
      { label: 'Bajo stock', value: '0', class: 'pill-warning' },
      { label: 'Sin stock', value: '0', class: 'pill-danger' },
    ],
  },
])

const INITIAL_METRICS = () => ([
  { key: 'avg_ticket', label: 'Valor promedio por pedido (30 días)', value: '$ 0', helper: 'Órdenes completadas' },
  { key: 'pending', label: 'Órdenes pendientes', value: '0', helper: 'Pendiente / En proceso' },
  { key: 'revenue_month', label: 'Ingresos del mes', value: '$ 0', helper: 'Variación vs. mes anterior', change: '0%', changeClass: '' },
])

// =====================================================
// Helpers internos
// =====================================================
function parseDate(value) {
  if (!value) return null

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value
  }

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function productImagePath(product) {
  return product.primary_image || product.image || product.product_image || product.imagen || product.image_url || null
}

function normalizeInventoryRow(row) {
  return {
    ...row,
    id: Number(row.id || 0),
    product_id: Number(row.product_id || 0),
    product_name: row.product_name || row.name || 'Sin nombre',
    color_name: row.color_name || 'Sin color',
    size_label: row.size_label || row.size_name || 'Sin talla',
    sku: row.sku || '',
    stock: Number(row.stock || row.quantity || 0),
    low_stock_threshold: resolveInventoryThreshold(row),
    updated_at: row.updated_at || row.created_at || null,
    image: productImagePath(row),
  }
}

function formatIsoDate(date) {
  return date.toISOString().slice(0, 10)
}

function daysAgo(days) {
  const date = new Date()
  date.setDate(date.getDate() - days)
  return date
}

function startOfDay(date) {
  const value = parseDate(date) || new Date()
  value.setHours(0, 0, 0, 0)
  return value
}

function endOfDay(date) {
  const value = parseDate(date) || new Date()
  value.setHours(23, 59, 59, 999)
  return value
}

function countRowsBetween(rows, start, end) {
  return rows.filter((row) => {
    const createdAt = parseDate(row.created_at)
    return createdAt && createdAt >= start && createdAt <= end
  }).length
}

function timeAgo(value) {
  const parsed = parseDate(value)
  if (!parsed) return 'Actualización actual'

  const diffMs = Math.max(0, Date.now() - parsed.getTime())
  const diffMinutes = Math.floor(diffMs / 60000)

  if (diffMinutes < 1) return 'Hace unos segundos'
  if (diffMinutes < 60) return `Hace ${diffMinutes} min`

  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) {
    return `Hace ${diffHours} h`
  }

  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) {
    return `Hace ${diffDays} día${diffDays === 1 ? '' : 's'}`
  }

  return parsed.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' })
}

export function useAdminDashboard() {
  const router = useRouter()
  const { settings: shellSettings } = useAppShell()

  // =====================================================
  // Estado principal
  // =====================================================
  const loading = ref(true)
  const salesChartRange = ref(7)
  const statusChartRange = ref(7)
  const salesSeries = ref([])
  const stats = ref(INITIAL_STATS())
  const metrics = ref(INITIAL_METRICS())
  const orderStatuses = ref([])
  const recentOrders = ref([])
  const inventoryAlerts = ref([])
  const topProducts = ref([])
  const activities = ref([])
  const inventoryTotal = ref(0)
  const inventoryLow = ref(0)
  const inventoryZero = ref(0)

  // =====================================================
  // Datos derivados
  // =====================================================
  const dashboardWelcome = computed(() => shellSettings.value?.dashboard_welcome || 'Panel de control')
  const dashboardStoreName = computed(() => shellSettings.value?.store_name || 'Angelow')
  const hasSalesChartData = computed(() => salesSeries.value.some((item) => Number(item.revenue || 0) > 0 || Number(item.orders || 0) > 0))
  const hasStatusChartData = computed(() => orderStatuses.value.some((item) => Number(item.count || 0) > 0))

  // =====================================================
  // Navegación
  // =====================================================
  function normalizeDashboardOrderSource(value) {
    return String(value || '').trim().toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
  }

  function buildDashboardOrderDetailRoute(order) {
    const orderId = Number(order?.id || 0)

    if (!orderId) {
      return '/admin/ordenes'
    }

    const orderSource = normalizeDashboardOrderSource(order?.order_source)

    return {
      name: 'admin-order-detail',
      params: { id: orderId },
      query: orderSource === 'legacy' ? { vista: 'archivo' } : {},
    }
  }

  function buildDashboardCustomerRoute(customer) {
    const customerId = String(customer?.id || '').trim()
    const searchSeed = customer?.email || customer?.name || customer?.phone || customerId

    if (!customerId) {
      return '/admin/clientes'
    }

    return {
      path: '/admin/clientes',
      query: {
        search: searchSeed,
        customer: customerId,
      },
    }
  }

  function buildDashboardProductRoute(product) {
    const productId = Number(product?.id || product?.product_id || 0)

    if (!productId) {
      return '/admin/productos'
    }

    return {
      name: 'admin-product-edit',
      params: { id: productId },
    }
  }

  function openInventoryAlert(item) {
    const targetRoute = item?.route || buildInventoryTargetRoute(item)
    router.push(targetRoute)
  }

  function openTopProduct(product) {
    const targetRoute = product?.route || buildDashboardProductRoute(product)
    router.push(targetRoute)
  }

  function openDashboardActivity(activity) {
    const targetRoute = activity?.route
    if (!targetRoute) return
    router.push(targetRoute)
  }

  function openRecentOrder(order) {
    const targetRoute = order?.route || buildDashboardOrderDetailRoute(order)
    if (!targetRoute) return
    router.push(targetRoute)
  }

  // =====================================================
  // Métricas y datasets
  // =====================================================
  function buildOrderStatusChartEntry(status, count) {
    const normalizedStatus = normalizeOrderStatus(status || 'pending')

    return {
      label: getOrderStatusLabel(normalizedStatus),
      count: Number(count || 0),
      color: ORDER_STATUS_CHART_COLORS[normalizedStatus] || '#777',
    }
  }

  function mapTopProductEntry(product) {
    const productId = Number(product?.product_id || product?.id || 0)
    const unitsSold = Number(product?.total_quantity || product?.units_sold || product?.times_sold || product?.sold_count || 0)
    const revenueValue = Number(product?.total_revenue || product?.revenue || (Number(product?.price || 0) * unitsSold) || 0)

    return {
      id: productId || String(product?.name || product?.product_name || 'producto'),
      name: product?.name || product?.product_name || 'Sin nombre',
      units: unitsSold,
      revenue: revenueValue.toLocaleString('es-CO'),
      route: buildDashboardProductRoute({ ...product, id: productId || product?.id, product_id: productId || product?.product_id }),
    }
  }

  function statusPercentage(count) {
    const total = orderStatuses.value.reduce((sum, status) => sum + Number(status.count || 0), 0)
    if (total <= 0) return '0%'
    return `${Math.round((Number(count || 0) / total) * 100)}%`
  }

  function buildActivities({ orders = [], customers = [], inventoryAlerts: currentInventoryAlerts = [] }) {
    const rows = []

    orders.slice(0, 3).forEach((order) => {
      rows.push({
        id: `order-${order.id}`,
        type: 'order',
        icon: 'fas fa-shopping-bag',
        title: `Nueva orden #${order.id}`,
        description: `${order.user_name || order.customer_name || 'Cliente'} · $ ${Number(order.total || 0).toLocaleString('es-CO')}`,
        sortAt: parseDate(order.created_at),
        route: buildDashboardOrderDetailRoute(order),
      })
    })

    customers.slice(0, 2).forEach((customer) => {
      rows.push({
        id: `customer-${customer.id}`,
        type: 'customer',
        icon: 'fas fa-user',
        title: 'Nuevo cliente registrado',
        description: `${customer.name || 'Cliente'} · ${customer.email || 'Sin correo'}`,
        sortAt: parseDate(customer.created_at),
        route: buildDashboardCustomerRoute(customer),
      })
    })

    currentInventoryAlerts.slice(0, 3).forEach((product) => {
      rows.push({
        id: `inventory-${product.id}`,
        type: 'inventory',
        icon: 'fas fa-box-open',
        title: product.status === 'out'
          ? `Sin stock en ${product.name || 'producto'}`
          : `Stock bajo en ${product.name || 'producto'}`,
        description: product.status === 'out'
          ? `${product.variantLabel} agotada.`
          : `${product.variantLabel} · Quedan ${Number(product.stock || 0)} unidades.`,
        sortAt: parseDate(product.updated_at || product.created_at),
        route: product.route || buildInventoryTargetRoute(product),
      })
    })

    return rows
      .sort((left, right) => (right.sortAt?.getTime() || 0) - (left.sortAt?.getTime() || 0))
      .slice(0, 6)
      .map((item) => ({
        ...item,
        time: item.sortAt ? timeAgo(item.sortAt) : 'Actualización actual',
      }))
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadSalesStats() {
    const today = new Date()
    const rangeFrom = formatIsoDate(daysAgo(salesChartRange.value - 1))
    const currentMonthFrom = formatIsoDate(new Date(today.getFullYear(), today.getMonth(), 1))
    const previousMonthFrom = formatIsoDate(new Date(today.getFullYear(), today.getMonth() - 1, 1))
    const previousMonthTo = formatIsoDate(new Date(today.getFullYear(), today.getMonth(), 0))

    let rangedReport = {}
    let currentMonthReport = {}
    let previousMonthReport = {}

    const requests = [
      orderHttp.get('/admin/reports/sales', { params: { from: rangeFrom, to: formatIsoDate(today) } })
        .then((response) => { rangedReport = response.data?.data || response.data || {} })
        .catch((error) => console.warn('Error obteniendo reporte rango:', error.message)),
      orderHttp.get('/admin/reports/sales', { params: { from: currentMonthFrom, to: formatIsoDate(today) } })
        .then((response) => { currentMonthReport = response.data?.data || response.data || {} })
        .catch((error) => console.warn('Error obteniendo reporte mes actual:', error.message)),
      orderHttp.get('/admin/reports/sales', { params: { from: previousMonthFrom, to: previousMonthTo } })
        .then((response) => { previousMonthReport = response.data?.data || response.data || {} })
        .catch((error) => console.warn('Error obteniendo reporte mes anterior:', error.message)),
    ]

    await Promise.allSettled(requests)

    salesSeries.value = Array.isArray(rangedReport.rows) ? rangedReport.rows : []

    const monthRevenue = Number(currentMonthReport.totalRevenue || currentMonthReport.total_revenue || 0)
    const previousRevenue = Number(previousMonthReport.totalRevenue || previousMonthReport.total_revenue || 0)
    const monthDelta = previousRevenue > 0
      ? ((monthRevenue - previousRevenue) / previousRevenue) * 100
      : 0

    const avgTicket = Number(currentMonthReport.avgOrderValue || currentMonthReport.avg_order_value || 0)
    metrics.value[0].value = `$ ${avgTicket.toLocaleString('es-CO')}`

    const pendingFromReport = (Array.isArray(rangedReport.by_status) ? rangedReport.by_status : [])
      .map((entry) => ({
        status: normalizeOrderStatus(entry.status || 'pending'),
        count: Number(entry.count || 0),
      }))
      .filter((entry) => ['pending', 'in_review', 'processing'].includes(entry.status))
      .reduce((accumulator, entry) => accumulator + entry.count, 0)

    if (pendingFromReport > 0) {
      metrics.value[1].value = String(pendingFromReport)
    }

    metrics.value[2].value = `$ ${monthRevenue.toLocaleString('es-CO')}`
    metrics.value[2].change = `${monthDelta >= 0 ? '+' : ''}${monthDelta.toFixed(1)}%`
    metrics.value[2].changeClass = monthDelta >= 0 ? 'text-success' : 'text-danger'
  }

  async function loadStatusChartStats() {
    const today = new Date()
    const rangeFrom = formatIsoDate(daysAgo(statusChartRange.value - 1))

    try {
      const response = await orderHttp.get('/admin/reports/sales', { params: { from: rangeFrom, to: formatIsoDate(today) } })
      const rangedReport = response.data?.data || response.data || {}
      const reportStatuses = Array.isArray(rangedReport.by_status) ? rangedReport.by_status : []

      orderStatuses.value = reportStatuses
        .map((entry) => buildOrderStatusChartEntry(entry.status, entry.count))
        .filter((entry) => entry.count > 0)
    } catch (error) {
      console.warn('Error obteniendo estados para el gráfico circular:', error.message)
    }
  }

  async function loadDashboard() {
    loading.value = true
    let orders = []
    let customerRows = []

    try {
      const ordersResponse = await orderHttp.get('/admin/orders', { params: { limit: 50 } })
      const ordersPayload = ordersResponse.data?.data || ordersResponse.data || {}
      orders = Array.isArray(ordersPayload)
        ? ordersPayload
        : (Array.isArray(ordersPayload.rows)
          ? ordersPayload.rows
          : (Array.isArray(ordersPayload.data) ? ordersPayload.data : []))

      recentOrders.value = orders.slice(0, 8).map((order) => ({
        id: order.id,
        customer: order.user_name || order.customer_name || 'Cliente',
        date: order.created_at ? new Date(order.created_at).toLocaleDateString('es-CO') : '-',
        total: Number(order.total || 0).toLocaleString('es-CO'),
        order_source: order.order_source,
        status: normalizeOrderStatus(order.order_status || order.status),
        statusLabel: getOrderStatusLabel(order.order_status || order.status),
        paymentStatus: getPaymentStatusBadgeClass(order.payment_status),
        paymentLabel: getPaymentStatusLabel(order.payment_status),
        route: buildDashboardOrderDetailRoute(order),
      }))

      const todayStr = formatIsoDate(new Date())
      const todayOrders = orders.filter((order) => order.created_at && order.created_at.startsWith(todayStr))
      stats.value[0].value = String(todayOrders.length)

      const todayRevenue = todayOrders.reduce((sum, order) => sum + Number(order.total || 0), 0)
      stats.value[1].value = `$ ${todayRevenue.toLocaleString('es-CO')}`

      const yesterdayStr = formatIsoDate(daysAgo(1))
      const yesterdayOrders = orders.filter((order) => order.created_at && order.created_at.startsWith(yesterdayStr))
      const yesterdayRevenue = yesterdayOrders.reduce((sum, order) => sum + Number(order.total || 0), 0)

      if (yesterdayOrders.length > 0) {
        const ordersDelta = Math.round(((todayOrders.length - yesterdayOrders.length) / yesterdayOrders.length) * 100)
        stats.value[0].meta.change = `${ordersDelta >= 0 ? '+' : ''}${ordersDelta}%`
        stats.value[0].meta.changeClass = ordersDelta >= 0 ? 'text-success' : 'text-danger'
      }

      if (yesterdayRevenue > 0) {
        const revenueDelta = Math.round(((todayRevenue - yesterdayRevenue) / yesterdayRevenue) * 100)
        stats.value[1].meta.change = `${revenueDelta >= 0 ? '+' : ''}${revenueDelta}%`
        stats.value[1].meta.changeClass = revenueDelta >= 0 ? 'text-success' : 'text-danger'
      }

      const pendingCount = orders.filter((order) => ['pending', 'processing', 'in_review'].includes(normalizeOrderStatus(order.order_status || order.status))).length
      metrics.value[1].value = String(pendingCount)

      const statusCounts = {}
      orders.forEach((order) => {
        const status = normalizeOrderStatus(order.order_status || order.status)
        statusCounts[status] = (statusCounts[status] || 0) + 1
      })
      orderStatuses.value = Object.entries(statusCounts).map(([key, count]) => buildOrderStatusChartEntry(key, count))
    } catch (error) {
      console.warn('Error cargando dashboard:', error)
    }

    try {
      const customersResponse = await authHttp.get('/admin/customers')
      const customersData = customersResponse.data?.data || customersResponse.data || []
      customerRows = Array.isArray(customersData) ? customersData : (customersData.data || [])

      const currentWindowStart = startOfDay(daysAgo(6))
      const currentWindowEnd = endOfDay(new Date())
      const previousWindowStart = startOfDay(daysAgo(13))
      const previousWindowEnd = endOfDay(daysAgo(7))

      const currentCustomers = countRowsBetween(customerRows, currentWindowStart, currentWindowEnd)
      const previousCustomers = countRowsBetween(customerRows, previousWindowStart, previousWindowEnd)

      stats.value[2].value = String(currentCustomers)

      if (previousCustomers > 0) {
        const customerDelta = Math.round(((currentCustomers - previousCustomers) / previousCustomers) * 100)
        stats.value[2].meta.change = `${customerDelta >= 0 ? '+' : ''}${customerDelta}%`
        stats.value[2].meta.changeClass = customerDelta >= 0 ? 'text-success' : 'text-danger'
      }
    } catch (error) {
      console.warn('Error cargando clientes:', error)
    }

    try {
      let productsData = []
      let inventoryData = []

      try {
        const productsResponse = await catalogHttp.get('/admin/products', { params: { limit: 200 } })
        productsData = productsResponse.data?.data || productsResponse.data || []
        const inventoryResponse = await catalogHttp.get('/admin/inventory', { params: { limit: 500 } })
        inventoryData = inventoryResponse.data?.data || inventoryResponse.data || []
      } catch (adminError) {
        if (adminError?.response?.status !== 401 && adminError?.response?.status !== 403) {
          throw adminError
        }

        const fallbackResponse = await catalogHttp.get('/products', { params: { limit: 200 } })
        productsData = fallbackResponse.data?.data || fallbackResponse.data || []
      }

      const productsRaw = Array.isArray(productsData) ? productsData : (productsData.data || [])
      const products = productsRaw.map((product) => ({
        ...product,
        name: product.name || product.nombre || 'Sin nombre',
        price: Number(product.price ?? product.precio ?? 0),
        stock: Number(product.stock ?? product.total_stock ?? 0),
        is_active: typeof product.is_active === 'boolean'
          ? product.is_active
          : Boolean(Number(product.activo ?? 1)),
        image: productImagePath(product),
        sold_count: Number(product.sold_count ?? product.total_sold ?? 0),
      }))

      const inventoryRowsRaw = Array.isArray(inventoryData) ? inventoryData : (inventoryData.data || [])
      const inventoryRows = inventoryRowsRaw.map(normalizeInventoryRow)
      const activeVariants = inventoryRows.filter((row) => resolveInventoryStatus(row.stock, row) === 'active')
      const lowStockVariants = inventoryRows.filter((row) => resolveInventoryStatus(row.stock, row) === 'low')
      const outOfStockVariants = inventoryRows.filter((row) => resolveInventoryStatus(row.stock, row) === 'out')

      inventoryTotal.value = inventoryRows.length
      inventoryLow.value = lowStockVariants.length
      inventoryZero.value = outOfStockVariants.length
      stats.value[3].value = String(activeVariants.length)
      stats.value[3].pills[0].value = String(activeVariants.length)
      stats.value[3].pills[1].value = String(lowStockVariants.length)
      stats.value[3].pills[2].value = String(outOfStockVariants.length)

      inventoryAlerts.value = inventoryRows
        .filter((row) => resolveInventoryStatus(row.stock, row) !== 'active')
        .sort((left, right) => {
          const statusWeight = (row) => (resolveInventoryStatus(row.stock, row) === 'out' ? 0 : 1)
          const statusDiff = statusWeight(left) - statusWeight(right)
          if (statusDiff !== 0) return statusDiff
          return (parseDate(right.updated_at)?.getTime() || 0) - (parseDate(left.updated_at)?.getTime() || 0)
        })
        .slice(0, 5)
        .map((row) => ({
          id: `${row.product_id}-${row.id}`,
          product_id: row.product_id,
          variant_id: row.id,
          name: row.product_name,
          stock: row.stock,
          status: resolveInventoryStatus(row.stock, row),
          variantLabel: formatInventoryVariantLabel(row),
          alertLabel: resolveInventoryStatus(row.stock, row) === 'out'
            ? 'Sin stock'
            : `${row.stock} de ${resolveInventoryThreshold(row)} uds`,
          route: buildInventoryTargetRoute(row),
          rawImage: row.image,
          image: resolveMediaUrl(row.image, 'product'),
          updated_at: row.updated_at,
        }))

      try {
        const reportFrom = formatIsoDate(daysAgo(30))
        const reportTo = formatIsoDate(new Date())
        const topProductsResponse = await orderHttp.get('/admin/reports/products', {
          params: {
            from: reportFrom,
            to: reportTo,
            limit: 20,
          },
        })

        const topProductsPayload = topProductsResponse.data?.data || topProductsResponse.data || []
        const topRows = Array.isArray(topProductsPayload)
          ? topProductsPayload
          : (Array.isArray(topProductsPayload.rows) ? topProductsPayload.rows : [])

        topProducts.value = topRows
          .filter((row) => Number(row.total_quantity || row.units_sold || row.times_sold || 0) > 0)
          .sort((left, right) => Number(right.total_revenue || right.revenue || 0) - Number(left.total_revenue || left.revenue || 0))
          .slice(0, 5)
          .map((row) => mapTopProductEntry(row))
      } catch {
        try {
          const reportResponse = await catalogHttp.get('/admin/reports/products')
          const reportData = reportResponse.data?.data || reportResponse.data || []
          const reportRows = Array.isArray(reportData) ? reportData : (reportData.data || [])

          topProducts.value = reportRows
            .filter((row) => Number(row.units_sold || row.total_quantity || 0) > 0)
            .sort((left, right) => Number(right.total_revenue || right.revenue || 0) - Number(left.total_revenue || left.revenue || 0))
            .slice(0, 5)
            .map((row) => mapTopProductEntry(row))
        } catch {
          topProducts.value = products
            .filter((product) => product.sold_count > 0)
            .sort((left, right) => right.sold_count - left.sold_count)
            .slice(0, 5)
            .map((product) => mapTopProductEntry(product))
        }
      }
    } catch (error) {
      console.warn('Error cargando productos:', error)
    }

    try {
      activities.value = buildActivities({
        orders,
        customers: customerRows,
        inventoryAlerts: inventoryAlerts.value,
      })

      await Promise.allSettled([
        loadSalesStats().catch((error) => console.warn('Error cargando gráfico de ventas:', error)),
        loadStatusChartStats().catch((error) => console.warn('Error cargando gráfico circular:', error)),
      ])
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
  watch(salesChartRange, () => {
    loadSalesStats().catch((error) => console.warn('Error actualizando rango del gráfico de ventas:', error))
  })

  watch(statusChartRange, () => {
    loadStatusChartStats().catch((error) => console.warn('Error actualizando rango del gráfico circular:', error))
  })

  onMounted(loadDashboard)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activities,
    dashboardStoreName,
    dashboardWelcome,
    hasSalesChartData,
    hasStatusChartData,
    inventoryAlerts,
    inventoryLow,
    inventoryTotal,
    inventoryZero,
    loadDashboard,
    loading,
    metrics,
    openDashboardActivity,
    openInventoryAlert,
    openRecentOrder,
    openTopProduct,
    orderStatuses,
    recentOrders,
    salesChartRange,
    salesSeries,
    stats,
    statusChartRange,
    statusPercentage,
    topProducts,
  }
}
