<template>
  <div>
    <AdminPageHeader icon="fas fa-chart-bar" title="Informe de gestion" subtitle="Paridad Angelow: ventas, productos y clientes en una vista unificada." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Informes' }]">
      <template #actions>
        <button class="btn btn-secondary" @click="resetFilters"><i class="fas fa-rotate-left"></i> Restablecer</button>
        <button class="btn btn-secondary" @click="exportReport"><i class="fas fa-download"></i> Exportar CSV</button>
        <button class="btn btn-primary" @click="printReport"><i class="fas fa-print"></i> Imprimir</button>
      </template>
    </AdminPageHeader>

    <div class="filters-bar" style="margin-bottom: 1.5rem;">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="btn"
        :class="activeTab === tab.id ? 'btn-primary' : 'btn-secondary'"
        @click="activeTab = tab.id"
      >
        <i :class="tab.icon"></i> {{ tab.label }}
      </button>
    </div>

    <section class="dashboard-card">
      <div class="report-filter-grid">
        <div class="filter-group">
          <label for="admin-reports-from">Fecha inicio</label>
          <input id="admin-reports-from" name="admin-reports-from" v-model="dateFrom" type="date" class="form-input">
        </div>
        <div class="filter-group">
          <label for="admin-reports-to">Fecha fin</label>
          <input id="admin-reports-to" name="admin-reports-to" v-model="dateTo" type="date" class="form-input">
        </div>

        <div v-if="activeTab === 'sales'" class="filter-group">
          <label for="admin-reports-sales-status">Estado orden</label>
          <select id="admin-reports-sales-status" name="admin-reports-sales-status" v-model="salesStatus" class="form-select">
            <option value="">Todos</option>
            <option value="pending">Pendiente</option>
            <option value="processing">En proceso</option>
            <option value="shipped">Enviado</option>
            <option value="delivered">Entregado</option>
            <option value="cancelled">Cancelado</option>
          </select>
        </div>

        <div v-if="activeTab === 'sales'" class="filter-group">
          <label for="admin-reports-group-by">Agrupar por</label>
          <select id="admin-reports-group-by" name="admin-reports-group-by" v-model="groupBy" class="form-select">
            <option value="day">Dia</option>
            <option value="week">Semana</option>
            <option value="month">Mes</option>
            <option value="year">Anio</option>
          </select>
        </div>

        <div v-if="activeTab === 'products'" class="filter-group">
          <label for="admin-reports-product-limit">Top</label>
          <select id="admin-reports-product-limit" name="admin-reports-product-limit" v-model.number="productLimit" class="form-select">
            <option :value="10">Top 10</option>
            <option :value="20">Top 20</option>
            <option :value="50">Top 50</option>
            <option :value="100">Top 100</option>
          </select>
        </div>

        <div v-if="activeTab === 'customers'" class="filter-group">
          <label for="admin-reports-customer-min">Minimo ordenes</label>
          <select id="admin-reports-customer-min" name="admin-reports-customer-min" v-model.number="minCustomerOrders" class="form-select">
            <option :value="1">1+</option>
            <option :value="2">2+</option>
            <option :value="3">3+</option>
            <option :value="5">5+</option>
            <option :value="10">10+</option>
          </select>
        </div>

        <div class="filter-group report-filter-actions">
          <label>&nbsp;</label>
          <button class="btn btn-primary" @click="loadReport"><i class="fas fa-magnifying-glass"></i> Aplicar</button>
        </div>
      </div>
    </section>

    <section v-if="activeTab === 'sales'" class="dashboard-card">
      <AdminStatsGrid :stats="salesStatsFormatted" :loading="loading" />

      <div class="report-grid report-grid-sales">
        <article class="chart-card chart-card-large">
          <div class="chart-header">
            <div>
              <h3><i class="fas fa-chart-line"></i> Evolucion de ventas</h3>
              <p class="chart-subtitle">Ingresos y ordenes por periodo.</p>
            </div>
          </div>
          <div class="chart-body"><canvas ref="salesEvolutionCanvas"></canvas></div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <div>
              <h3><i class="fas fa-chart-column"></i> Comparativa mensual</h3>
              <p class="chart-subtitle">Mes anterior vs mes actual.</p>
            </div>
          </div>
          <div class="chart-body"><canvas ref="monthlyComparisonCanvas"></canvas></div>
        </article>
      </div>

      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','line','line','line']" />
      <div v-else-if="groupedSalesRows.length" class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr><th>Periodo</th><th>Ordenes</th><th>Subtotal</th><th>Envio</th><th>Descuentos</th><th>Total</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in groupedSalesRows" :key="row.period">
              <td>{{ row.period }}</td>
              <td>{{ row.orders }}</td>
              <td>$ {{ Number(row.subtotal).toLocaleString('es-CO') }}</td>
              <td>$ {{ Number(row.shipping).toLocaleString('es-CO') }}</td>
              <td>$ {{ Number(row.discount).toLocaleString('es-CO') }}</td>
              <td>$ {{ Number(row.revenue).toLocaleString('es-CO') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AdminEmptyState v-else icon="fas fa-chart-line" title="Sin datos de ventas" description="Ajusta los filtros para ver resultados." />
    </section>

    <section v-if="activeTab === 'products'" class="dashboard-card">
      <div class="report-grid report-grid-products">
        <article class="chart-card chart-card-large">
          <div class="chart-header"><div><h3><i class="fas fa-trophy"></i> Top productos por ingresos</h3><p class="chart-subtitle">Ranking de productos mas vendidos.</p></div></div>
          <div class="chart-body"><canvas ref="topProductsCanvas"></canvas></div>
        </article>
        <article class="chart-card">
          <div class="chart-header"><div><h3><i class="fas fa-box"></i> Cantidad vendida</h3><p class="chart-subtitle">Unidades por producto.</p></div></div>
          <div class="chart-body"><canvas ref="quantityProductsCanvas"></canvas></div>
        </article>
      </div>

      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','line','line']" />
      <div v-else-if="filteredProductsRows.length" class="table-responsive">
        <table class="dashboard-table">
          <thead><tr><th>#</th><th>Producto</th><th>Ingresos</th><th>Cantidad</th><th>Rating promedio</th></tr></thead>
          <tbody>
            <tr v-for="(row, index) in filteredProductsRows" :key="row.id">
              <td>{{ index + 1 }}</td>
              <td>{{ row.name }}</td>
              <td>$ {{ Number(row.revenue).toLocaleString('es-CO') }}</td>
              <td>{{ row.units_sold }}</td>
              <td>{{ row.avg_rating || 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AdminEmptyState v-else icon="fas fa-box" title="Sin datos de productos" description="Ajusta los filtros para ver resultados." />
    </section>

    <section v-if="activeTab === 'customers'" class="dashboard-card">
      <AdminStatsGrid :stats="customerStatsFormatted" :loading="loading" />

      <div class="report-grid report-grid-customers">
        <article class="chart-card">
          <div class="chart-header"><div><h3><i class="fas fa-chart-pie"></i> Distribucion de clientes</h3><p class="chart-subtitle">Segmentacion por frecuencia de compra.</p></div></div>
          <div class="chart-body"><canvas ref="customerDistributionCanvas"></canvas></div>
        </article>
        <article class="chart-card chart-card-large">
          <div class="chart-header"><div><h3><i class="fas fa-ranking-star"></i> Top clientes por valor</h3><p class="chart-subtitle">Clientes con mayor gasto acumulado.</p></div></div>
          <div class="chart-body"><canvas ref="topCustomersCanvas"></canvas></div>
        </article>
      </div>

      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','line','line','line']" />
      <div v-else-if="filteredCustomerRows.length" class="table-responsive">
        <table class="dashboard-table">
          <thead><tr><th>#</th><th>Cliente</th><th>Email</th><th>Ordenes</th><th>Gastado</th><th>Ultima compra</th></tr></thead>
          <tbody>
            <tr v-for="(row, index) in filteredCustomerRows" :key="row.id">
              <td>{{ index + 1 }}</td>
              <td>{{ row.name }}</td>
              <td>{{ row.email }}</td>
              <td>{{ row.orders_count || 0 }}</td>
              <td>$ {{ Number(row.total_spent || 0).toLocaleString('es-CO') }}</td>
              <td>{{ row.last_order_date || 'Sin dato' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AdminEmptyState v-else icon="fas fa-users" title="Sin datos de clientes" description="Ajusta los filtros para ver resultados." />
    </section>
  </div>
</template>

<script setup>
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { authHttp, catalogHttp, orderHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

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

const { showSnackbar } = useSnackbarSystem()

const tabs = [
  { id: 'sales', icon: 'fas fa-chart-line', label: 'Ventas' },
  { id: 'products', icon: 'fas fa-box', label: 'Productos' },
  { id: 'customers', icon: 'fas fa-users', label: 'Clientes' },
]

const route = useRoute()
const loading = ref(false)
const activeTab = ref('sales')
const dateFrom = ref('')
const dateTo = ref('')
const salesStatus = ref('')
const groupBy = ref('month')
const productLimit = ref(10)
const minCustomerOrders = ref(2)

const salesReport = ref({})
const productsReport = ref([])
const customersReport = ref({})

const salesEvolutionCanvas = ref(null)
const monthlyComparisonCanvas = ref(null)
const topProductsCanvas = ref(null)
const quantityProductsCanvas = ref(null)
const customerDistributionCanvas = ref(null)
const topCustomersCanvas = ref(null)

let salesEvolutionChart = null
let monthlyComparisonChart = null
let topProductsChart = null
let quantityProductsChart = null
let customerDistributionChart = null
let topCustomersChart = null

const salesRowsRaw = computed(() => {
  const rows = salesReport.value?.rows
  return Array.isArray(rows) ? rows : []
})

const filteredProductsRows = computed(() => {
  return [...productsReport.value]
    .sort((a, b) => Number(b.revenue || 0) - Number(a.revenue || 0))
    .slice(0, productLimit.value)
})

const filteredCustomerRows = computed(() => {
  const rows = Array.isArray(customersReport.value?.rows) ? customersReport.value.rows : []
  return rows
    .filter((row) => Number(row.orders_count || 0) >= minCustomerOrders.value)
    .sort((a, b) => Number(b.total_spent || 0) - Number(a.total_spent || 0))
})

const salesTotals = computed(() => {
  const rows = groupedSalesRows.value
  const totalOrders = rows.reduce((acc, row) => acc + Number(row.orders || 0), 0)
  const totalRevenue = rows.reduce((acc, row) => acc + Number(row.revenue || 0), 0)
  return {
    totalOrders,
    totalRevenue,
    avgOrderValue: totalOrders > 0 ? totalRevenue / totalOrders : 0,
  }
})

const customersTotals = computed(() => {
  const rows = filteredCustomerRows.value
  const customersWithOrders = rows.filter((row) => Number(row.orders_count || 0) > 0).length
  const returningCustomers = rows.filter((row) => Number(row.orders_count || 0) >= 2).length
  return {
    totalCustomers: Number(customersReport.value?.totalCustomers || rows.length || 0),
    customersWithOrders,
    returningCustomers,
    avgOrdersPerCustomer: rows.length ? (rows.reduce((acc, row) => acc + Number(row.orders_count || 0), 0) / rows.length).toFixed(1) : '0.0',
  }
})

const salesStatsFormatted = computed(() => [
  { key: 'revenue', label: 'Ingresos totales', value: `$ ${Number(salesTotals.value.totalRevenue).toLocaleString('es-CO')}`, icon: 'fas fa-dollar-sign', color: 'success' },
  { key: 'orders', label: 'Total ordenes', value: salesTotals.value.totalOrders, icon: 'fas fa-receipt', color: 'primary' },
  { key: 'avg', label: 'Ticket promedio', value: `$ ${Number(salesTotals.value.avgOrderValue).toLocaleString('es-CO')}`, icon: 'fas fa-chart-line', color: 'info' },
  { key: 'growth', label: 'Crecimiento mensual', value: monthlyComparison.value.growthText, icon: 'fas fa-arrow-trend-up', color: 'warning' },
])

const customerStatsFormatted = computed(() => [
  { key: 'total', label: 'Total clientes', value: customersTotals.value.totalCustomers, icon: 'fas fa-users', color: 'primary' },
  { key: 'withOrders', label: 'Con compras', value: customersTotals.value.customersWithOrders, icon: 'fas fa-shopping-bag', color: 'success' },
  { key: 'returning', label: 'Recurrentes', value: customersTotals.value.returningCustomers, icon: 'fas fa-redo', color: 'warning' },
  { key: 'avg', label: 'Promedio ordenes', value: customersTotals.value.avgOrdersPerCustomer, icon: 'fas fa-chart-bar', color: 'info' },
])

const groupedSalesRows = computed(() => {
  const buckets = new Map()

  // Se agrupa en frontend para replicar legacy aunque el endpoint no traiga periodizacion configurable.
  for (const row of salesRowsRaw.value) {
    const sourceDate = String(row.date || '')
    const parsedDate = new Date(sourceDate)
    if (Number.isNaN(parsedDate.getTime())) {
      continue
    }

    const bucketKey = getBucketKey(parsedDate, groupBy.value)
    if (!buckets.has(bucketKey)) {
      buckets.set(bucketKey, {
        period: bucketKey,
        orders: 0,
        subtotal: 0,
        shipping: 0,
        discount: 0,
        revenue: 0,
        products: 0,
      })
    }

    const bucket = buckets.get(bucketKey)
    bucket.orders += Number(row.orders || 0)
    bucket.subtotal += Number(row.subtotal || row.revenue || 0)
    bucket.shipping += Number(row.shipping || 0)
    bucket.discount += Number(row.discount || 0)
    bucket.revenue += Number(row.revenue || 0)
    bucket.products += Number(row.products || 0)
  }

  return [...buckets.values()].sort((a, b) => a.period.localeCompare(b.period))
})

const monthlyComparison = computed(() => {
  const groupedByMonth = groupedSalesRows.value
    .filter((row) => /^\d{4}-\d{2}$/.test(row.period))
    .sort((a, b) => a.period.localeCompare(b.period))

  const current = groupedByMonth[groupedByMonth.length - 1] || { revenue: 0 }
  const previous = groupedByMonth[groupedByMonth.length - 2] || { revenue: 0 }
  const currentValue = Number(current.revenue || 0)
  const previousValue = Number(previous.revenue || 0)
  const growth = previousValue > 0 ? ((currentValue - previousValue) / previousValue) * 100 : 0

  return {
    previousValue,
    currentValue,
    growth,
    growthText: `${growth >= 0 ? '+' : ''}${growth.toFixed(1)}%`,
  }
})

function getBucketKey(date, period) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  if (period === 'day') {
    return `${year}-${month}-${day}`
  }

  if (period === 'week') {
    const firstDate = new Date(date.getFullYear(), 0, 1)
    const dayOfYear = Math.floor((date - firstDate) / 86400000) + 1
    const week = String(Math.ceil(dayOfYear / 7)).padStart(2, '0')
    return `${year}-S${week}`
  }

  if (period === 'year') {
    return String(year)
  }

  return `${year}-${month}`
}

function getDefaultDateRange() {
  const today = new Date()
  const start = new Date(today)
  start.setDate(today.getDate() - 30)
  return {
    from: start.toISOString().slice(0, 10),
    to: today.toISOString().slice(0, 10),
  }
}

function resolveTabByRoutePath(path) {
  if (path.includes('/informes/productos')) return 'products'
  if (path.includes('/informes/clientes')) return 'customers'
  return 'sales'
}

function destroyCharts() {
  const charts = [
    salesEvolutionChart,
    monthlyComparisonChart,
    topProductsChart,
    quantityProductsChart,
    customerDistributionChart,
    topCustomersChart,
  ]
  charts.forEach((chart) => {
    if (chart) chart.destroy()
  })

  salesEvolutionChart = null
  monthlyComparisonChart = null
  topProductsChart = null
  quantityProductsChart = null
  customerDistributionChart = null
  topCustomersChart = null
}

async function renderCharts() {
  await nextTick()
  destroyCharts()

  if (activeTab.value === 'sales') {
    if (salesEvolutionCanvas.value) {
      salesEvolutionChart = new Chart(salesEvolutionCanvas.value, {
        type: 'line',
        data: {
          labels: groupedSalesRows.value.map((row) => row.period),
          datasets: [
            {
              label: 'Ingresos',
              data: groupedSalesRows.value.map((row) => Number(row.revenue || 0)),
              borderColor: '#667eea',
              backgroundColor: 'rgba(102, 126, 234, 0.18)',
              yAxisID: 'y',
              fill: true,
              tension: 0.25,
            },
            {
              label: 'Ordenes',
              data: groupedSalesRows.value.map((row) => Number(row.orders || 0)),
              borderColor: '#f59e0b',
              backgroundColor: 'rgba(245, 158, 11, 0.2)',
              yAxisID: 'y1',
              fill: false,
              tension: 0.25,
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              position: 'left',
              ticks: {
                callback(value) {
                  return `$ ${Number(value).toLocaleString('es-CO')}`
                },
              },
            },
            y1: {
              position: 'right',
              grid: { drawOnChartArea: false },
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
            data: [monthlyComparison.value.previousValue, monthlyComparison.value.currentValue],
            backgroundColor: ['#94a3b8', '#667eea'],
            borderRadius: 6,
            barThickness: 40,
          }],
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
        },
      })
    }
  }

  if (activeTab.value === 'products') {
    if (topProductsCanvas.value) {
      topProductsChart = new Chart(topProductsCanvas.value, {
        type: 'bar',
        data: {
          labels: filteredProductsRows.value.map((row) => row.name),
          datasets: [{
            label: 'Ingresos',
            data: filteredProductsRows.value.map((row) => Number(row.revenue || 0)),
            backgroundColor: '#667eea',
            borderRadius: 6,
          }],
        },
        options: {
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: { legend: { display: false } },
        },
      })
    }

    if (quantityProductsCanvas.value) {
      quantityProductsChart = new Chart(quantityProductsCanvas.value, {
        type: 'bar',
        data: {
          labels: filteredProductsRows.value.map((row) => row.name),
          datasets: [{
            label: 'Cantidad vendida',
            data: filteredProductsRows.value.map((row) => Number(row.units_sold || 0)),
            backgroundColor: '#10b981',
            borderRadius: 6,
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
    const distribution = [
      filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) <= 1).length,
      filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) >= 2 && Number(row.orders_count || 0) <= 5).length,
      filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) >= 6 && Number(row.orders_count || 0) <= 10).length,
      filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) > 10).length,
    ]

    if (customerDistributionCanvas.value) {
      customerDistributionChart = new Chart(customerDistributionCanvas.value, {
        type: 'doughnut',
        data: {
          labels: ['1 orden', '2-5 ordenes', '6-10 ordenes', '10+ ordenes'],
          datasets: [{
            data: distribution,
            backgroundColor: ['#667eea', '#f59e0b', '#10b981', '#ef4444'],
            borderWidth: 0,
          }],
        },
        options: {
          maintainAspectRatio: false,
          cutout: '60%',
        },
      })
    }

    if (topCustomersCanvas.value) {
      topCustomersChart = new Chart(topCustomersCanvas.value, {
        type: 'bar',
        data: {
          labels: filteredCustomerRows.value.slice(0, 10).map((row) => row.name),
          datasets: [{
            label: 'Total gastado',
            data: filteredCustomerRows.value.slice(0, 10).map((row) => Number(row.total_spent || 0)),
            backgroundColor: '#667eea',
            borderRadius: 6,
          }],
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
        },
      })
    }
  }
}

async function loadReport() {
  loading.value = true

  try {
    const params = {}
    if (dateFrom.value) params.from = dateFrom.value
    if (dateTo.value) params.to = dateTo.value

    if (activeTab.value === 'sales') {
      if (salesStatus.value) params.status = salesStatus.value
      const { data } = await orderHttp.get('/admin/reports/sales', { params })
      salesReport.value = data?.data || data || {}
    } else if (activeTab.value === 'products') {
      const { data } = await catalogHttp.get('/admin/reports/products', { params })
      const rows = data?.data || data || []
      productsReport.value = Array.isArray(rows) ? rows : []
    } else {
      const { data } = await authHttp.get('/admin/reports/customers', { params })
      customersReport.value = data?.data || data || {}
    }

    await renderCharts()
  } catch {
    showSnackbar({ type: 'error', message: 'Error al cargar informe.' })
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  const range = getDefaultDateRange()
  dateFrom.value = range.from
  dateTo.value = range.to
  salesStatus.value = ''
  groupBy.value = 'month'
  productLimit.value = 10
  minCustomerOrders.value = 2
  loadReport()
}

function exportReport() {
  let csv = ''
  let rows = []

  if (activeTab.value === 'sales') {
    csv = 'Periodo,Ordenes,Subtotal,Envio,Descuentos,Total\n'
    rows = groupedSalesRows.value.map((row) => `${row.period},${row.orders},${row.subtotal},${row.shipping},${row.discount},${row.revenue}`)
  } else if (activeTab.value === 'products') {
    csv = 'Producto,Ingresos,Cantidad,Rating\n'
    rows = filteredProductsRows.value.map((row) => `${row.name},${row.revenue},${row.units_sold},${row.avg_rating || 0}`)
  } else {
    csv = 'Cliente,Email,Ordenes,Total Gastado,Ultima Compra\n'
    rows = filteredCustomerRows.value.map((row) => `${row.name},${row.email},${row.orders_count || 0},${row.total_spent || 0},${row.last_order_date || ''}`)
  }

  if (!rows.length) {
    showSnackbar({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  const blob = new Blob([csv + rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `informe-${activeTab.value}-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()

  showSnackbar({ type: 'success', message: 'CSV generado correctamente.' })
}

function printReport() {
  window.print()
}

watch(() => route.path, (path) => {
  const nextTab = resolveTabByRoutePath(path)
  if (activeTab.value !== nextTab) {
    activeTab.value = nextTab
  }
}, { immediate: true })

watch([activeTab, groupBy, productLimit, minCustomerOrders], async () => {
  await loadReport()
})

onBeforeUnmount(() => {
  destroyCharts()
})

onMounted(() => {
  const range = getDefaultDateRange()
  dateFrom.value = range.from
  dateTo.value = range.to
  loadReport()
})
</script>
