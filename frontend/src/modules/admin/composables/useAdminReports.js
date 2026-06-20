import {
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  DoughnutController,
  Filler,
  Legend,
  LineController,
  LineElement,
  LinearScale,
  PointElement,
  Tooltip,
} from 'chart.js'
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authHttp, catalogHttp, orderHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  BarElement,
  LineController,
  BarController,
  DoughnutController,
  Filler,
  Tooltip,
  Legend,
)

export function useAdminReports() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

  const tabs = [
    { id: 'sales', label: 'Ventas', icon: 'fas fa-chart-line', path: '/admin/informes/ventas', note: 'Se excluyen \u00f3rdenes canceladas por defecto.' },
    { id: 'products', label: 'Productos populares', icon: 'fas fa-fire', path: '/admin/informes/productos', note: 'El ranking usa ingresos y cantidad vendidos en el per\u00edodo.' },
    { id: 'customers', label: 'Clientes recurrentes', icon: 'fas fa-users', path: '/admin/informes/clientes', note: 'El m\u00ednimo de \u00f3rdenes se aplica sobre compras reales registradas.' },
  ]

  const route = useRoute()
  const router = useRouter()

  const loading = ref(false)
  const activeTab = ref('sales')
  const showDetailModal = ref(false)
  const detailContext = ref(null)

  const filters = reactive({
    sales: { search: '', from: '', to: '', status: '', groupBy: 'month' },
    products: { search: '', from: '', to: '', limit: 50 },
    customers: { search: '', minOrders: 2 },
  })

  const salesReport = ref({})
  const productRows = ref([])
  const customerRows = ref([])
  const topCustomerRows = ref([])
  const customerStats = ref({ totalCustomers: 0, customersWithOrders: 0, returningCustomers: 0, avgOrdersPerCustomer: 0 })
  const customerDistribution = ref([])

  const salesEvolutionCanvas = ref(null)
  const monthlyComparisonCanvas = ref(null)
  const topProductsCanvas = ref(null)
  const categoriesCanvas = ref(null)
  const quantityProductsCanvas = ref(null)
  const customerDistributionCanvas = ref(null)
  const topCustomersCanvas = ref(null)

  let salesEvolutionChart = null
  let monthlyComparisonChart = null
  let topProductsChart = null
  let categoriesChart = null
  let quantityProductsChart = null
  let customerDistributionChart = null
  let topCustomersChart = null

  const activeTabConfig = computed(() => tabs.find((tab) => tab.id === activeTab.value) || tabs[0])

  const breadcrumbs = computed(() => [
    { label: 'Dashboard', to: '/admin' },
    { label: 'Informes', to: '/admin/informes/ventas' },
    { label: activeTabConfig.value.label },
  ])

  const salesRowsRaw = computed(() => {
    const rows = salesReport.value?.rows
    return Array.isArray(rows) ? rows : []
  })

  function getBucketKey(date, period) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')

    if (period === 'day') return `${year}-${month}-${day}`
    if (period === 'week') {
      const firstDate = new Date(date.getFullYear(), 0, 1)
      const dayOfYear = Math.floor((date - firstDate) / 86400000) + 1
      const week = `${Math.ceil(dayOfYear / 7)}`.padStart(2, '0')
      return `${year}-S${week}`
    }
    if (period === 'year') return String(year)
    return `${year}-${month}`
  }

  function formatPeriodLabel(period, groupBy) {
    if (!period) return 'Sin per\u00edodo'
    if (groupBy === 'week') return period.replace('-S', ' / Semana ')
    return period
  }

  function truncateText(value, maxLength = 30) {
    const text = String(value || '')
    return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text
  }

  function toInputDate(value) {
    return new Date(value).toISOString().slice(0, 10)
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
  }

  function formatDateTime(value) {
    if (!value) return 'Sin dato'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return 'Sin dato'
    return date.toLocaleString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' })
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  const groupedSalesRows = computed(() => {
    const buckets = new Map()

    for (const row of salesRowsRaw.value) {
      const sourceDate = String(row.date || '')
      const parsedDate = new Date(sourceDate)
      if (Number.isNaN(parsedDate.getTime())) continue

      const bucketKey = getBucketKey(parsedDate, filters.sales.groupBy)
      if (!buckets.has(bucketKey)) {
        buckets.set(bucketKey, {
          period: bucketKey,
          orders: 0,
          subtotal: 0,
          shipping: 0,
          discount: 0,
          revenue: 0,
          avgOrderValueAccumulator: 0,
        })
      }

      const bucket = buckets.get(bucketKey)
      bucket.orders += Number(row.orders || 0)
      bucket.subtotal += Number(row.subtotal || 0)
      bucket.shipping += Number(row.shipping || 0)
      bucket.discount += Number(row.discount || 0)
      bucket.revenue += Number(row.revenue || 0)
      bucket.avgOrderValueAccumulator += Number(row.avg_order_value || 0) * Number(row.orders || 0)
    }

    return [...buckets.values()]
      .map((row) => ({
        ...row,
        avg_order_value: row.orders > 0 ? row.avgOrderValueAccumulator / row.orders : 0,
      }))
      .filter((row) => formatPeriodLabel(row.period, filters.sales.groupBy).toLowerCase().includes(filters.sales.search.trim().toLowerCase()))
      .sort((a, b) => a.period.localeCompare(b.period))
  })

  const filteredProductsRows = computed(() => {
    const term = filters.products.search.trim().toLowerCase()
    return productRows.value
      .filter((row) => {
        if (!term) return true
        return [row.name, row.category_name, row.slug].join(' ').toLowerCase().includes(term)
      })
      .sort((a, b) => Number(b.total_revenue || 0) - Number(a.total_revenue || 0))
  })

  const productCategoryBreakdown = computed(() => {
    const totals = new Map()
    for (const row of filteredProductsRows.value) {
      const category = row.category_name || 'Sin categor\u00eda'
      totals.set(category, (totals.get(category) || 0) + Number(row.total_revenue || 0))
    }
    return [...totals.entries()].map(([name, revenue]) => ({ name, revenue }))
  })

  const filteredCustomerRows = computed(() => {
    const term = filters.customers.search.trim().toLowerCase()
    return customerRows.value
      .filter((row) => {
        if (!term) return true
        return [row.name, row.email, row.phone].join(' ').toLowerCase().includes(term)
      })
      .sort((a, b) => Number(b.total_spent || 0) - Number(a.total_spent || 0))
  })

  const filteredTopCustomerRows = computed(() => {
    const term = filters.customers.search.trim().toLowerCase()
    return topCustomerRows.value
      .filter((row) => {
        if (!term) return true
        return [row.name, row.email, row.phone].join(' ').toLowerCase().includes(term)
      })
      .sort((a, b) => Number(b.total_spent || 0) - Number(a.total_spent || 0))
  })

  const salesPagination = useAdminPagination(groupedSalesRows, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const productsPagination = useAdminPagination(filteredProductsRows, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const customersPagination = useAdminPagination(filteredCustomerRows, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const salesStats = computed(() => [
    { key: 'revenue', label: 'Ingresos totales', value: formatCurrency(salesReport.value.total_revenue || salesReport.value.totalRevenue || 0), icon: 'fas fa-dollar-sign', color: 'success' },
    { key: 'orders', label: 'Total \u00f3rdenes', value: Number(salesReport.value.total_orders || salesReport.value.totalOrders || 0), icon: 'fas fa-shopping-cart', color: 'primary' },
    { key: 'shipping', label: 'Costos de env\u00edo', value: formatCurrency(salesReport.value.total_shipping || salesReport.value.totalShipping || 0), icon: 'fas fa-truck', color: 'info' },
    { key: 'discount', label: 'Descuentos', value: formatCurrency(salesReport.value.total_discount || salesReport.value.totalDiscount || 0), icon: 'fas fa-percent', color: 'warning' },
  ])

  const productStats = computed(() => {
    const totalRevenue = filteredProductsRows.value.reduce((acc, row) => acc + Number(row.total_revenue || 0), 0)
    const totalQuantity = filteredProductsRows.value.reduce((acc, row) => acc + Number(row.total_quantity || 0), 0)
    const topCategory = [...productCategoryBreakdown.value].sort((a, b) => b.revenue - a.revenue)[0]?.name || 'Sin categor\u00eda'

    return [
      { key: 'products', label: 'Productos listados', value: filteredProductsRows.value.length, icon: 'fas fa-box', color: 'primary' },
      { key: 'units', label: 'Unidades vendidas', value: totalQuantity, icon: 'fas fa-boxes-stacked', color: 'success' },
      { key: 'revenue', label: 'Ingresos del ranking', value: formatCurrency(totalRevenue), icon: 'fas fa-chart-line', color: 'info' },
      { key: 'category', label: 'Categor\u00eda l\u00edder', value: topCategory, icon: 'fas fa-tags', color: 'warning' },
    ]
  })

  const recurringCustomerCount = computed(() => filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) >= 2).length)

  const customerStatsFormatted = computed(() => [
    { key: 'total', label: 'Total clientes', value: Number(customerStats.value.totalCustomers || 0), icon: 'fas fa-users', color: 'primary' },
    { key: 'with-orders', label: 'Con compras', value: Number(customerStats.value.customersWithOrders || 0), icon: 'fas fa-bag-shopping', color: 'success' },
    { key: 'returning', label: 'Recurrentes', value: recurringCustomerCount.value, icon: 'fas fa-repeat', color: 'warning' },
    { key: 'avg', label: 'Promedio \u00f3rdenes', value: customerStats.value.avgOrdersPerCustomer || 0, icon: 'fas fa-chart-bar', color: 'info' },
  ])

  const activeStats = computed(() => {
    if (activeTab.value === 'sales') return salesStats.value
    if (activeTab.value === 'products') return productStats.value
    return customerStatsFormatted.value
  })

  const activeSearchModel = computed({
    get() {
      if (activeTab.value === 'sales') return filters.sales.search
      if (activeTab.value === 'products') return filters.products.search
      return filters.customers.search
    },
    set(value) {
      if (activeTab.value === 'sales') {
        filters.sales.search = value
        return
      }
      if (activeTab.value === 'products') {
        filters.products.search = value
        return
      }
      filters.customers.search = value
    },
  })

  const searchPlaceholder = computed(() => {
    if (activeTab.value === 'sales') return 'Buscar por per\u00edodo...'
    if (activeTab.value === 'products') return 'Buscar por producto o categor\u00eda...'
    return 'Buscar por cliente, correo o tel\u00e9fono...'
  })

  const activeFilterCount = computed(() => {
    if (activeTab.value === 'sales') {
      return [filters.sales.search, filters.sales.from, filters.sales.to, filters.sales.status, filters.sales.groupBy !== 'month'].filter(Boolean).length
    }
    if (activeTab.value === 'products') {
      return [filters.products.search, filters.products.from, filters.products.to, filters.products.limit !== 50].filter(Boolean).length
    }
    return [filters.customers.search, filters.customers.minOrders !== 2].filter(Boolean).length
  })

  const resultsLabel = computed(() => {
    if (activeTab.value === 'sales') return `Mostrando ${salesPagination.visibleCount} de ${salesPagination.totalItems} per\u00edodos agrupados`
    if (activeTab.value === 'products') return `Mostrando ${productsPagination.visibleCount} de ${productsPagination.totalItems} productos del ranking`
    return `Mostrando ${customersPagination.visibleCount} de ${customersPagination.totalItems} clientes recurrentes`
  })

  const activeReportRows = computed(() => {
    if (activeTab.value === 'sales') return groupedSalesRows.value
    if (activeTab.value === 'products') return filteredProductsRows.value
    return filteredCustomerRows.value
  })

  const detailTitle = computed(() => {
    if (!detailContext.value) return 'Detalle del informe'
    if (detailContext.value.type === 'sales') return `Detalle de ${formatPeriodLabel(detailContext.value.row.period, filters.sales.groupBy)}`
    return detailContext.value.row.name
  })

  const monthlyComparison = computed(() => {
    const rows = salesRowsRaw.value
      .map((row) => ({
        month: getBucketKey(new Date(row.date), 'month'),
        revenue: Number(row.revenue || 0),
      }))
      .reduce((acc, row) => {
        acc[row.month] = (acc[row.month] || 0) + row.revenue
        return acc
      }, {})

    const sorted = Object.entries(rows).sort((a, b) => a[0].localeCompare(b[0]))
    const previous = Number(sorted[sorted.length - 2]?.[1] || 0)
    const current = Number(sorted[sorted.length - 1]?.[1] || 0)
    const growth = previous > 0 ? ((current - previous) / previous) * 100 : 0

    return { previous, current, growth }
  })

  // Inicializa los rangos por defecto sin tocar la semántica vigente de cada tab.
  function initializeFilters() {
    const today = new Date()
    const salesWindowStart = new Date(today.getFullYear(), today.getMonth() - 3, 1)
    const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate())

    filters.sales.from = toInputDate(salesWindowStart)
    filters.sales.to = toInputDate(today)
    filters.products.from = toInputDate(threeMonthsAgo)
    filters.products.to = toInputDate(today)
  }

  // Destruye las instancias previas para conservar el mismo ciclo de vida de Chart.js.
  function destroyCharts() {
    const charts = [salesEvolutionChart, monthlyComparisonChart, topProductsChart, categoriesChart, quantityProductsChart, customerDistributionChart, topCustomersChart]
    charts.forEach((chart) => {
      if (chart) chart.destroy()
    })

    salesEvolutionChart = null
    monthlyComparisonChart = null
    topProductsChart = null
    categoriesChart = null
    quantityProductsChart = null
    customerDistributionChart = null
    topCustomersChart = null
  }

  // Reproduce las gráficas actuales a partir del tab activo y de los mismos datasets ya usados por la vista.
  async function renderCharts() {
    await nextTick()
    destroyCharts()

    if (activeTab.value === 'sales') {
      if (salesEvolutionCanvas.value) {
        salesEvolutionChart = new Chart(salesEvolutionCanvas.value, {
          type: 'line',
          data: {
            labels: groupedSalesRows.value.map((row) => formatPeriodLabel(row.period, filters.sales.groupBy)),
            datasets: [
              {
                label: 'Ingresos',
                data: groupedSalesRows.value.map((row) => Number(row.revenue || 0)),
                borderColor: '#0f7abf',
                backgroundColor: 'rgba(15, 122, 191, 0.14)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
              },
              {
                label: '\u00d3rdenes',
                data: groupedSalesRows.value.map((row) => Number(row.orders || 0)),
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.16)',
                fill: false,
                tension: 0.25,
                yAxisID: 'y1',
              },
            ],
          },
          options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback(value) {
                    return formatCurrency(value)
                  },
                },
              },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { precision: 0 },
              },
            },
          },
        })
      }

      if (monthlyComparisonCanvas.value) {
        monthlyComparisonChart = new Chart(monthlyComparisonCanvas.value, {
          type: 'bar',
          data: {
            labels: ['Mes anterior', 'Mes actual'],
            datasets: [{
              label: 'Ingresos',
              data: [monthlyComparison.value.previous, monthlyComparison.value.current],
              backgroundColor: ['#8fa8bf', '#0f7abf'],
              borderRadius: 8,
              barThickness: 42,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback(value) {
                    return formatCurrency(value)
                  },
                },
              },
            },
          },
        })
      }
    }

    if (activeTab.value === 'products') {
      const topTen = filteredProductsRows.value.slice(0, 10)

      if (topProductsCanvas.value) {
        topProductsChart = new Chart(topProductsCanvas.value, {
          type: 'bar',
          data: {
            labels: topTen.map((row) => truncateText(row.name, 24)),
            datasets: [{
              label: 'Ingresos',
              data: topTen.map((row) => Number(row.total_revenue || 0)),
              backgroundColor: '#0f7abf',
              borderRadius: 8,
            }],
          },
          options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
          },
        })
      }

      if (categoriesCanvas.value) {
        categoriesChart = new Chart(categoriesCanvas.value, {
          type: 'doughnut',
          data: {
            labels: productCategoryBreakdown.value.map((row) => row.name),
            datasets: [{
              data: productCategoryBreakdown.value.map((row) => Number(row.revenue || 0)),
              backgroundColor: ['#0f7abf', '#1f9d8b', '#e67e22', '#d35454', '#7f8c8d', '#16a085'],
              borderWidth: 0,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: { usePointStyle: true },
              },
            },
          },
        })
      }

      if (quantityProductsCanvas.value) {
        quantityProductsChart = new Chart(quantityProductsCanvas.value, {
          type: 'bar',
          data: {
            labels: topTen.map((row) => truncateText(row.name, 20)),
            datasets: [{
              label: 'Cantidad vendida',
              data: topTen.map((row) => Number(row.total_quantity || 0)),
              backgroundColor: '#1f9d8b',
              borderRadius: 8,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
          },
        })
      }
    }

    if (activeTab.value === 'customers') {
      if (customerDistributionCanvas.value) {
        customerDistributionChart = new Chart(customerDistributionCanvas.value, {
          type: 'doughnut',
          data: {
            labels: customerDistribution.value.map((row) => row.segment),
            datasets: [{
              data: customerDistribution.value.map((row) => Number(row.customer_count || 0)),
              backgroundColor: ['#0f7abf', '#f39c12', '#1f9d8b', '#d35454'],
              borderWidth: 0,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: { usePointStyle: true },
              },
            },
          },
        })
      }

      if (topCustomersCanvas.value) {
        // La gráfica de valor usa top_customers del backend; la tabla conserva el filtro de recurrentes.
        const topCustomers = filteredTopCustomerRows.value.slice(0, 10)

        topCustomersChart = new Chart(topCustomersCanvas.value, {
          type: 'bar',
          data: {
            labels: topCustomers.map((row) => truncateText(row.name, 18)),
            datasets: [{
              label: 'Valor acumulado',
              data: topCustomers.map((row) => Number(row.total_spent || 0)),
              backgroundColor: '#0f7abf',
              borderRadius: 8,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback(value) {
                    return formatCurrency(value)
                  },
                },
              },
            },
          },
        })
      }
    }
  }

  async function loadSalesReport() {
    const params = {
      from: filters.sales.from,
      to: filters.sales.to,
    }

    if (filters.sales.status) params.status = filters.sales.status

    const { data } = await orderHttp.get('/admin/reports/sales', { params })
    salesReport.value = data?.data || data || {}
  }

  async function loadProductsReport() {
    const params = {
      from: filters.products.from,
      to: filters.products.to,
      limit: filters.products.limit,
    }

    const { data } = await orderHttp.get('/admin/reports/products', { params })
    const rows = Array.isArray(data?.data) ? data.data : []
    const productIds = rows.map((row) => row.product_id).filter(Boolean)
    let productsById = new Map()

    if (productIds.length) {
      const metadataResponse = await catalogHttp.get('/admin/products', {
        params: { ids: productIds.join(',') },
      })
      const metadataRows = Array.isArray(metadataResponse.data?.data) ? metadataResponse.data.data : []
      productsById = new Map(metadataRows.map((row) => [Number(row.id), row]))
    }

    productRows.value = rows.map((row) => {
      const metadata = productsById.get(Number(row.product_id)) || {}
      return {
        ...row,
        image: metadata.primary_image || metadata.product_image || metadata.image || metadata.image_url || null,
        slug: metadata.slug || null,
        category_name: metadata.category_name || null,
      }
    })
  }

  async function loadCustomersReport() {
    const [authSummaryResponse, orderCustomersResponse] = await Promise.all([
      authHttp.get('/admin/reports/customers'),
      orderHttp.get('/admin/reports/customers', {
        params: { min_orders: filters.customers.minOrders },
      }),
    ])

    const authSummary = authSummaryResponse.data?.data || {}
    const orderPayload = orderCustomersResponse.data?.data || {}
    const orderRows = Array.isArray(orderPayload.rows) ? orderPayload.rows : []
    const topRows = Array.isArray(orderPayload.top_customers) ? orderPayload.top_customers : orderRows
    const ids = [...orderRows, ...topRows].map((row) => row.user_id).filter(Boolean)
    let profilesById = new Map()

    if (ids.length) {
      const profilesResponse = await authHttp.get('/admin/customers', {
        params: { ids: ids.join(',') },
      })
      const profiles = Array.isArray(profilesResponse.data?.data) ? profilesResponse.data.data : []
      profilesById = new Map(profiles.map((row) => [String(row.id), row]))
    }

    customerRows.value = orderRows.map((row) => {
      const profile = row.user_id ? profilesById.get(String(row.user_id)) : null
      return {
        ...row,
        name: profile?.name || row.name,
        email: profile?.email || row.email,
        phone: profile?.phone || null,
        image: profile?.image || null,
      }
    })

    topCustomerRows.value = topRows.map((row) => {
      const profile = row.user_id ? profilesById.get(String(row.user_id)) : null
      return {
        ...row,
        name: profile?.name || row.name,
        email: profile?.email || row.email,
        phone: profile?.phone || row.phone || null,
        image: profile?.image || null,
      }
    })

    customerDistribution.value = Array.isArray(orderPayload.distribution) ? orderPayload.distribution : []
    customerStats.value = {
      totalCustomers: Number(authSummary.totalCustomers || 0),
      customersWithOrders: Number(orderPayload.stats?.customers_with_orders || 0),
      returningCustomers: Number(orderPayload.stats?.returning_customers || 0),
      avgOrdersPerCustomer: Number(orderPayload.stats?.avg_orders_per_customer || 0),
    }
  }

  // Centraliza la carga por tab sin alterar endpoints, payloads ni notificaciones actuales.
  async function loadCurrentReport() {
    if (!validateFilters()) return

    loading.value = true
    try {
      if (activeTab.value === 'sales') {
        await loadSalesReport()
      } else if (activeTab.value === 'products') {
        await loadProductsReport()
      } else {
        await loadCustomersReport()
      }

      await renderCharts()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo cargar el informe.') })
    } finally {
      loading.value = false
    }
  }

  function validateFilters() {
    if (activeTab.value === 'customers') return true

    const current = activeTab.value === 'sales' ? filters.sales : filters.products
    if (current.from && current.to && new Date(current.to) < new Date(current.from)) {
      showAlert({
        type: 'warning',
        title: 'Rango inv\u00e1lido',
        message: 'La fecha final debe ser posterior o igual a la fecha inicial.',
      })
      return false
    }

    return true
  }

  function resetFilters() {
    if (activeTab.value === 'sales') {
      const today = new Date()
      const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
      filters.sales.search = ''
      filters.sales.from = toInputDate(startOfMonth)
      filters.sales.to = toInputDate(today)
      filters.sales.status = ''
      filters.sales.groupBy = 'month'
    } else if (activeTab.value === 'products') {
      const today = new Date()
      const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate())
      filters.products.search = ''
      filters.products.from = toInputDate(threeMonthsAgo)
      filters.products.to = toInputDate(today)
      filters.products.limit = 50
    } else {
      filters.customers.search = ''
      filters.customers.minOrders = 2
    }

    loadCurrentReport()
  }

  function goToTab(tabId) {
    const tab = tabs.find((item) => item.id === tabId)
    if (!tab) return
    router.push(tab.path)
  }

  function syncTabWithRoute(path) {
    if (path.includes('/informes/productos')) {
      activeTab.value = 'products'
      return
    }
    if (path.includes('/informes/clientes')) {
      activeTab.value = 'customers'
      return
    }
    activeTab.value = 'sales'
  }

  function openDetailModal(type, row) {
    detailContext.value = { type, row }
    showDetailModal.value = true
  }

  function closeDetailModal() {
    showDetailModal.value = false
    detailContext.value = null
  }

  // Define el payload exportable del informe activo para que todos los tabs usen la misma plantilla.
  function buildReportExportPayload() {
    if (activeTab.value === 'sales') {
      return {
        fileBaseName: 'informe-ventas',
        sheetName: 'Informe ventas',
        title: 'Informe de ventas',
        subtitle: activeTabConfig.value.note,
        columns: [
          { header: 'Per\u00edodo', value: (row) => formatPeriodLabel(row.period, filters.sales.groupBy), width: 18 },
          { header: '\u00d3rdenes', value: (row) => Number(row.orders || 0), excelType: 'number', align: 'center', width: 12 },
          { header: 'Subtotal', value: (row) => formatCurrency(row.subtotal), excelValue: (row) => Number(row.subtotal || 0), excelType: 'currency', align: 'right', width: 15 },
          { header: 'Env\u00edo', value: (row) => formatCurrency(row.shipping), excelValue: (row) => Number(row.shipping || 0), excelType: 'currency', align: 'right', width: 15 },
          { header: 'Descuentos', value: (row) => formatCurrency(row.discount), excelValue: (row) => Number(row.discount || 0), excelType: 'currency', align: 'right', width: 15 },
          { header: 'Total', value: (row) => formatCurrency(row.revenue), excelValue: (row) => Number(row.revenue || 0), excelType: 'currency', align: 'right', width: 15 },
          { header: 'Ticket promedio', value: (row) => formatCurrency(row.avg_order_value), excelValue: (row) => Number(row.avg_order_value || 0), excelType: 'currency', align: 'right', width: 18 },
        ],
        rows: groupedSalesRows.value,
        landscape: true,
      }
    }

    if (activeTab.value === 'products') {
      return {
        fileBaseName: 'informe-productos-populares',
        sheetName: 'Informe productos',
        title: 'Informe de productos populares',
        subtitle: activeTabConfig.value.note,
        columns: [
          { header: 'Vista', includeInExcel: false, pdfImage: (row) => row.image, fallbackType: 'product', pdfWidth: 18, pdfImageSize: 12 },
          { header: 'Producto', value: (row) => row.name },
          { header: 'Categor\u00eda', value: (row) => row.category_name || 'Sin categor\u00eda', width: 18 },
          { header: 'Veces vendido', value: (row) => Number(row.times_sold || 0), excelType: 'number', align: 'center', width: 12 },
          { header: 'Cantidad total', value: (row) => Number(row.total_quantity || 0), excelType: 'number', align: 'center', width: 12 },
          { header: 'Precio promedio', value: (row) => formatCurrency(row.avg_price), excelValue: (row) => Number(row.avg_price || 0), excelType: 'currency', align: 'right', width: 16 },
          { header: 'Ingresos', value: (row) => formatCurrency(row.total_revenue), excelValue: (row) => Number(row.total_revenue || 0), excelType: 'currency', align: 'right', width: 16 },
        ],
        rows: filteredProductsRows.value,
        landscape: true,
      }
    }

    return {
      fileBaseName: 'informe-clientes-recurrentes',
      sheetName: 'Informe clientes',
      title: 'Informe de clientes recurrentes',
      subtitle: activeTabConfig.value.note,
      columns: [
        { header: 'Avatar', includeInExcel: false, pdfImage: (row) => row.image, fallbackType: 'avatar', pdfWidth: 18, pdfImageSize: 11 },
        { header: 'Cliente', value: (row) => row.name },
        { header: 'Correo', value: (row) => row.email || 'Sin correo', width: 20 },
        { header: 'Tel\u00e9fono', value: (row) => row.phone || 'Sin tel\u00e9fono', width: 16 },
        { header: '\u00d3rdenes', value: (row) => Number(row.orders_count || 0), excelType: 'number', align: 'center', width: 12 },
        { header: 'Total gastado', value: (row) => formatCurrency(row.total_spent), excelValue: (row) => Number(row.total_spent || 0), excelType: 'currency', align: 'right', width: 16 },
        { header: 'Promedio', value: (row) => formatCurrency(row.avg_order_value), excelValue: (row) => Number(row.avg_order_value || 0), excelType: 'currency', align: 'right', width: 16 },
        { header: '\u00daltima compra', value: (row) => formatDateTime(row.last_order), width: 18 },
      ],
      rows: filteredCustomerRows.value,
      landscape: true,
    }
  }

  // Exporta el informe activo usando el servicio compartido y evita mantener un CSV distinto por tab.
  function exportReport(format) {
    const payload = buildReportExportPayload()

    return exportData({
      format,
      ...payload,
      emptyMessage: 'No hay datos para exportar.',
    })
  }

  initializeFilters()

  watch(() => route.path, async (path) => {
    syncTabWithRoute(path)
    await loadCurrentReport()
  }, { immediate: true })

  watch(() => [filters.sales.search, filters.products.search, filters.customers.search], async () => {
    await renderCharts()
  })

  onBeforeUnmount(() => {
    destroyCharts()
  })

  return {
    activeFilterCount,
    activeReportRows,
    activeSearchModel,
    activeStats,
    activeTab,
    activeTabConfig,
    breadcrumbs,
    categoriesCanvas,
    closeDetailModal,
    customerDistributionCanvas,
    customersPagination,
    detailContext,
    detailTitle,
    exportReport,
    exportingFormat,
    filteredCustomerRows,
    filteredProductsRows,
    filters,
    formatCurrency,
    formatDateTime,
    formatPeriodLabel,
    goToTab,
    groupedSalesRows,
    loadCurrentReport,
    loading,
    monthlyComparisonCanvas,
    openDetailModal,
    productsPagination,
    quantityProductsCanvas,
    renderCharts,
    resetFilters,
    resultsLabel,
    salesEvolutionCanvas,
    salesPagination,
    searchPlaceholder,
    showDetailModal,
    tabs,
    topCustomersCanvas,
    topProductsCanvas,
  }
}
