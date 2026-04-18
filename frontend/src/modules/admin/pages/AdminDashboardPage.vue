<template>
  <div class="admin-dashboard-page">
    <!-- Cabecera: usa el componente global AdminPageHeader -->
    <AdminPageHeader
      icon="fas fa-chart-line"
      :title="dashboardWelcome"
      :subtitle="`${dashboardStoreName} · Ventas, órdenes, clientes e inventario en tiempo real.`"
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Resumen' }]"
    >
      <template #actions>
        <RouterLink to="/admin/ordenes" class="btn btn-secondary btn-sm-icon">
          <i class="fas fa-receipt"></i> <span class="btn-label">Órdenes</span>
        </RouterLink>
        <RouterLink to="/admin/productos" class="btn btn-secondary btn-sm-icon">
          <i class="fas fa-boxes"></i> <span class="btn-label">Productos</span>
        </RouterLink>
        <button class="btn btn-primary btn-sm-icon" type="button" @click="loadDashboard">
          <i class="fas fa-rotate"></i> <span class="btn-label">Actualizar</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Tarjetas de estadísticas: componente compartido AdminStatsGrid -->
    <AdminStatsGrid :stats="stats" :loading="loading" :count="4" />

    <!-- Métricas secundarias: componente AdminCard reutilizable -->
    <AdminCard :flush="false" class="dashboard-metrics-card">
      <div class="dashboard-metrics-grid">
        <div v-for="metric in metrics" :key="metric.key" class="dashboard-metric">
          <p class="dashboard-metric__label">{{ metric.label }}</p>
          <h3 class="dashboard-metric__value">{{ metric.value }}</h3>
          <div class="dashboard-metric__foot">
            <span class="dashboard-metric__helper">{{ metric.helper }}</span>
            <span v-if="metric.change" class="dashboard-metric__change" :class="metric.changeClass">{{ metric.change }}</span>
          </div>
        </div>
      </div>
    </AdminCard>

    <!-- Gráficos -->
    <section class="dashboard-charts-row">
      <AdminCard :flush="false" class="dashboard-chart-main">
        <div class="dashboard-chart-header">
          <div>
            <h3 class="dashboard-chart-title"><i class="fas fa-chart-area"></i> Rendimiento de ventas</h3>
            <p class="dashboard-chart-subtitle">Ingresos y órdenes del período seleccionado.</p>
          </div>
          <div class="dashboard-chart-controls">
            <button
              v-for="r in [7, 14, 30]"
              :key="r"
              class="dashboard-range-btn"
              :class="{ active: chartRange === r }"
              type="button"
              @click="chartRange = r"
            >{{ r }}D</button>
          </div>
        </div>
        <div class="dashboard-chart-body">
          <canvas ref="salesChartRef"></canvas>
        </div>
      </AdminCard>

      <AdminCard :flush="false" class="dashboard-chart-side">
        <div class="dashboard-chart-header">
          <div>
            <h3 class="dashboard-chart-title"><i class="fas fa-tags"></i> Estado de órdenes</h3>
            <p class="dashboard-chart-subtitle">Distribución actual por estado.</p>
          </div>
        </div>
        <div class="dashboard-chart-body doughnut">
          <canvas ref="statusChartRef"></canvas>
        </div>
        <div v-if="orderStatuses.length" class="dashboard-status-list">
          <div v-for="s in orderStatuses" :key="s.label" class="dashboard-status-row">
            <div class="dashboard-status-info">
              <span class="dashboard-status-dot" :style="{ backgroundColor: s.color }"></span>
              <span>{{ s.label }}</span>
            </div>
            <div class="dashboard-status-values">
              <strong>{{ s.count }}</strong>
              <span>{{ statusPercentage(s.count) }}</span>
            </div>
            <div class="dashboard-status-bar">
              <div class="dashboard-status-bar__fill" :style="{ width: statusPercentage(s.count), backgroundColor: s.color }"></div>
            </div>
          </div>
        </div>
      </AdminCard>
    </section>

    <!-- Últimas órdenes -->
    <AdminCard :flush="true">
      <template #header>
        <div class="dashboard-section-head">
          <div>
            <h3 class="dashboard-section-title"><i class="fas fa-list-ul"></i> Órdenes recientes</h3>
            <p class="dashboard-section-subtitle">Últimas actualizaciones registradas.</p>
          </div>
          <RouterLink to="/admin/ordenes" class="btn-link">Ver todas</RouterLink>
        </div>
      </template>

      <AdminEmptyState
        v-if="!loading && recentOrders.length === 0"
        icon="fas fa-inbox"
        title="Sin órdenes recientes"
        description="Las nuevas órdenes aparecen aquí en tiempo real."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Cliente</th>
              <th class="hide-xs">Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th class="hide-sm">Pago</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6"><AdminTableShimmer :rows="5" :columns="['line','line','line','line','pill','pill']" /></td>
            </tr>
            <tr v-for="order in recentOrders" v-else :key="order.id">
              <td><strong>#{{ order.id }}</strong></td>
              <td>{{ order.customer }}</td>
              <td class="hide-xs">{{ order.date }}</td>
              <td>$ {{ order.total }}</td>
              <td><span class="status-badge" :class="order.status">{{ order.statusLabel }}</span></td>
              <td class="hide-sm"><span class="status-badge" :class="order.paymentStatus">{{ order.paymentLabel }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <!-- Fila inferior: inventario + top productos + actividad -->
    <div class="dashboard-bottom-row">
      <!-- Inventario en riesgo -->
      <AdminCard :flush="false" class="dashboard-bottom-card">
        <template #header>
          <div class="dashboard-section-head">
            <div>
              <h3 class="dashboard-section-title"><i class="fas fa-warehouse"></i> Inventario en riesgo</h3>
              <p class="dashboard-section-subtitle">Productos activos con stock crítico.</p>
            </div>
            <RouterLink to="/admin/inventario" class="btn-link">Ver inventario</RouterLink>
          </div>
        </template>

        <div class="dashboard-inventory-pills">
          <div class="dashboard-inventory-pill">
            <p>Total de productos</p>
            <strong>{{ inventoryTotal }}</strong>
          </div>
          <div class="dashboard-inventory-pill">
            <p>Sin stock</p>
            <strong>{{ inventoryZero }}</strong>
          </div>
        </div>

        <AdminEmptyState
          v-if="!loading && lowStockItems.length === 0"
          icon="fas fa-check-circle"
          title="Sin productos en riesgo"
          description="El inventario está en buen estado."
        />
        <div v-else class="dashboard-low-stock">
          <div v-for="item in lowStockItems" :key="item.id" class="dashboard-low-stock__item">
            <AdminTableImage :src="item.image" :alt="item.name" type="product" size="sm" />
            <div class="dashboard-low-stock__info">
              <strong>{{ item.name }}</strong>
              <span>{{ item.sku || 'Sin SKU' }}</span>
            </div>
            <span class="admin-stat-card__pill pill-warning">{{ item.stock }} uds</span>
          </div>
        </div>
      </AdminCard>

      <!-- Top productos -->
      <AdminCard :flush="false" class="dashboard-bottom-card">
        <template #header>
          <div class="dashboard-section-head">
            <div>
              <h3 class="dashboard-section-title"><i class="fas fa-trophy"></i> Productos destacados</h3>
              <p class="dashboard-section-subtitle">Más vendidos en los últimos 30 días.</p>
            </div>
            <RouterLink to="/admin/informes/ventas" class="btn-link">Ver informe</RouterLink>
          </div>
        </template>

        <AdminEmptyState
          v-if="!loading && topProducts.length === 0"
          icon="fas fa-chart-bar"
          title="Sin datos de ventas"
          description="Los productos con más ventas aparecerán aquí."
        />
        <div v-else class="dashboard-top-products">
          <div v-for="(product, index) in topProducts" :key="product.id" class="dashboard-top-product">
            <span class="dashboard-top-product__rank">{{ index + 1 }}</span>
            <div class="dashboard-top-product__info">
              <strong>{{ product.name }}</strong>
              <span>{{ product.units }} vendidos</span>
            </div>
            <span class="dashboard-top-product__revenue">$ {{ product.revenue }}</span>
          </div>
        </div>
      </AdminCard>

      <!-- Actividad reciente -->
      <AdminCard :flush="false" class="dashboard-bottom-card">
        <template #header>
          <div class="dashboard-section-head">
            <div>
              <h3 class="dashboard-section-title"><i class="fas fa-bolt"></i> Actividad reciente</h3>
              <p class="dashboard-section-subtitle">Últimos eventos del sistema.</p>
            </div>
          </div>
        </template>

        <AdminEmptyState
          v-if="!loading && activities.length === 0"
          icon="fas fa-history"
          title="Sin actividad reciente"
          description="Los eventos del sistema apareceran aqui."
        />
        <div v-else class="dashboard-activity">
          <div v-for="a in activities" :key="a.id" class="dashboard-activity__item">
            <div class="dashboard-activity__icon" :class="a.type"><i :class="a.icon"></i></div>
            <div class="dashboard-activity__content">
              <p>{{ a.title }}</p>
              <span v-if="a.description">{{ a.description }}</span>
              <span class="dashboard-activity__time">{{ a.time }}</span>
            </div>
          </div>
        </div>
      </AdminCard>
    </div>
  </div>
</template>

<script setup>
import {  ArcElement,
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
import { ref, onBeforeUnmount, onMounted, computed, watch, nextTick } from 'vue'
import { RouterLink } from 'vue-router'
import { authHttp, orderHttp, catalogHttp } from '../../../services/http'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAppShell } from '../../../composables/useAppShell'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminTableImage from '../components/AdminTableImage.vue'

const { settings: shellSettings } = useAppShell()
// Mensaje de bienvenida y nombre de tienda desde configuración
const dashboardWelcome = computed(() => shellSettings.value?.dashboard_welcome || 'Panel de control')
const dashboardStoreName = computed(() => shellSettings.value?.store_name || 'Angelow')

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

const salesChartRef = ref(null)
const statusChartRef = ref(null)
const salesSeries = ref([])

let salesChartInstance = null
let statusChartInstance = null

const chartRange = ref(7)
const loading = ref(true)

// Formato compatible con AdminStatsGrid: { key, label, value, icon, color, meta?, pills? }
const stats = ref([
  { key: 'orders', icon: 'fas fa-receipt', color: 'primary', label: 'Órdenes hoy', value: '0', meta: { change: '0%', changeClass: '', helper: 'vs. ayer' } },
  { key: 'revenue', icon: 'fas fa-dollar-sign', color: 'success', label: 'Ingresos hoy', value: '$ 0', meta: { change: '0%', changeClass: '', helper: 'vs. ayer' } },
  { key: 'customers', icon: 'fas fa-user-plus', color: 'warning', label: 'Nuevos clientes', value: '0', meta: { change: '0%', changeClass: '', helper: 'vs. últimos 7 días' } },
  { key: 'inventory', icon: 'fas fa-boxes-stacked', color: 'info', label: 'Inventario activo', value: '0', pills: [
    { label: 'Activos', value: '0', class: 'pill-success' },
    { label: 'Bajo stock', value: '0', class: 'pill-warning' },
  ] },
])

const metrics = ref([
  { key: 'avg_ticket', label: 'Valor promedio por pedido (30 días)', value: '$ 0', helper: 'Órdenes completadas' },
  { key: 'pending', label: 'Órdenes pendientes', value: '0', helper: 'Pendiente / En proceso' },
  { key: 'revenue_month', label: 'Ingresos del mes', value: '$ 0', helper: 'Variación vs. mes anterior', change: '0%', changeClass: '' },
])

const orderStatuses = ref([])
const recentOrders = ref([])
const lowStockItems = ref([])
const topProducts = ref([])
const activities = ref([])
const inventoryTotal = ref(0)
const inventoryZero = ref(0)

const statusMap = {
  pending: 'Pendiente',
  processing: 'En proceso',
  shipped: 'Enviado',
  delivered: 'Entregado',
  cancelled: 'Cancelado',
}

const paymentMap = {
  pending: 'Pendiente',
  paid: 'Pagado',
  verified: 'Verificado',
  rejected: 'Rechazado',
}

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

function onLowStockImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'product')
}

function statusPercentage(count) {
  const total = orderStatuses.value.reduce((sum, status) => sum + Number(status.count || 0), 0)
  if (total <= 0) return '0%'
  return `${Math.round((Number(count || 0) / total) * 100)}%`
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
  if (!parsed) return 'Actualizado hace un momento'

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

function buildActivities({ orders = [], customers = [], lowStock = [] }) {
  const items = []

  orders.slice(0, 3).forEach((order) => {
    items.push({
      id: `order-${order.id}`,
      type: 'order',
      icon: 'fas fa-shopping-bag',
      title: `Nueva orden #${order.id}`,
      description: `${order.user_name || order.customer_name || 'Cliente'} · $ ${Number(order.total || 0).toLocaleString('es-CO')}`,
      sortAt: parseDate(order.created_at),
    })
  })

  customers.slice(0, 2).forEach((customer) => {
    items.push({
      id: `customer-${customer.id}`,
      type: 'customer',
      icon: 'fas fa-user',
      title: 'Nuevo cliente registrado',
      description: `${customer.name || 'Cliente'} · ${customer.email || 'Sin correo'}`,
      sortAt: parseDate(customer.created_at),
    })
  })

  lowStock.slice(0, 2).forEach((product) => {
    items.push({
      id: `inventory-${product.id}`,
      type: 'inventory',
      icon: 'fas fa-box-open',
      title: `Stock bajo en ${product.name || 'producto'}`,
      description: `Quedan ${Number(product.stock || 0)} unidades${product.sku ? ` · SKU ${product.sku}` : ''}`,
      sortAt: parseDate(product.updated_at || product.created_at),
    })
  })

  return items
    .sort((a, b) => (b.sortAt?.getTime() || 0) - (a.sortAt?.getTime() || 0))
    .slice(0, 6)
    .map((item) => ({
      ...item,
      time: item.sortAt ? timeAgo(item.sortAt) : 'Revisión actual',
    }))
}

function destroyCharts() {
  if (salesChartInstance) {
    salesChartInstance.destroy()
    salesChartInstance = null
  }
  if (statusChartInstance) {
    statusChartInstance.destroy()
    statusChartInstance = null
  }
}

function renderCharts() {
  if (!salesChartRef.value || !statusChartRef.value) {
    return
  }

  destroyCharts()

  const labels = salesSeries.value.map((item) => {
    const source = item.date || item.period || ''
    if (!source) return '-'
    const parsed = new Date(source)
    if (Number.isNaN(parsed.getTime())) return String(source)
    return parsed.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' })
  })

  const revenueDataset = salesSeries.value.map((item) => Number(item.revenue || 0))
  const ordersDataset = salesSeries.value.map((item) => Number(item.orders || 0))

  salesChartInstance = new Chart(salesChartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          type: 'line',
          label: 'Ingresos',
          data: revenueDataset,
          borderColor: '#0077b6',
          backgroundColor: 'rgba(0, 119, 182, 0.14)',
          borderWidth: 3,
          pointRadius: 3,
          yAxisID: 'y',
          tension: 0.35,
          fill: false,
        },
        {
          type: 'bar',
          label: 'Órdenes',
          data: ordersDataset,
          borderColor: '#48cae4',
          backgroundColor: '#48cae4',
          borderRadius: 6,
          yAxisID: 'y1',
          tension: 0.25,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom' },
      },
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

  statusChartInstance = new Chart(statusChartRef.value, {
    type: 'doughnut',
    data: {
      labels: orderStatuses.value.map((item) => item.label),
      datasets: [
        {
          data: orderStatuses.value.map((item) => Number(item.count || 0)),
          backgroundColor: orderStatuses.value.map((item) => item.color),
          borderWidth: 0,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      cutout: '62%',
    },
  })
}

async function loadSalesStats() {
  const today = new Date()
  const rangeFrom = formatIsoDate(daysAgo(chartRange.value - 1))
  const currentMonthFrom = formatIsoDate(new Date(today.getFullYear(), today.getMonth(), 1))
  const previousMonthFrom = formatIsoDate(new Date(today.getFullYear(), today.getMonth() - 1, 1))
  const previousMonthTo = formatIsoDate(new Date(today.getFullYear(), today.getMonth(), 0))

  // Cada llamada se maneja individualmente para que un fallo no afecte todas las métricas
  let rangedReport = {}
  let currentMonthReport = {}
  let previousMonthReport = {}

  const requests = [
    orderHttp.get('/admin/reports/sales', { params: { from: rangeFrom, to: formatIsoDate(today) } })
      .then(r => { rangedReport = r.data?.data || r.data || {} })
      .catch(e => console.warn('Error obteniendo reporte rango:', e.message)),
    orderHttp.get('/admin/reports/sales', { params: { from: currentMonthFrom, to: formatIsoDate(today) } })
      .then(r => { currentMonthReport = r.data?.data || r.data || {} })
      .catch(e => console.warn('Error obteniendo reporte mes actual:', e.message)),
    orderHttp.get('/admin/reports/sales', { params: { from: previousMonthFrom, to: previousMonthTo } })
      .then(r => { previousMonthReport = r.data?.data || r.data || {} })
      .catch(e => console.warn('Error obteniendo reporte mes anterior:', e.message)),
  ]

  await Promise.allSettled(requests)

  salesSeries.value = Array.isArray(rangedReport.rows) ? rangedReport.rows : []

  // Reusa el agregado por estado del endpoint admin para doughnut y lista.
  const statusColors = {
    pending: '#f59e0b',
    processing: '#0077b6',
    shipped: '#17a2b8',
    delivered: '#4bb543',
    cancelled: '#ff3333',
    refunded: '#64748b',
  }

  // Solo sobreescribir orderStatuses si la respuesta del reporte tiene datos
  const reportStatuses = Array.isArray(rangedReport.by_status) ? rangedReport.by_status : []
  if (reportStatuses.length > 0) {
    orderStatuses.value = reportStatuses.map((entry) => {
      const key = String(entry.status || 'pending')
      return {
        label: statusMap[key] || key,
        count: Number(entry.count || 0),
        color: statusColors[key] || '#64748b',
      }
    })
  }

  const monthRevenue = Number(currentMonthReport.totalRevenue || currentMonthReport.total_revenue || 0)
  const previousRevenue = Number(previousMonthReport.totalRevenue || previousMonthReport.total_revenue || 0)
  const monthDelta = previousRevenue > 0
    ? ((monthRevenue - previousRevenue) / previousRevenue) * 100
    : 0

  const avgTicket = Number(currentMonthReport.avgOrderValue || currentMonthReport.avg_order_value || 0)
  metrics.value[0].value = `$ ${avgTicket.toLocaleString('es-CO')}`

  // Órdenes pendientes: desde reporte o desde órdenes recientes como fallback
  const pendingFromReport = orderStatuses.value
    .filter((status) => ['Pendiente', 'En proceso'].includes(status.label))
    .reduce((acc, status) => acc + Number(status.count || 0), 0)
  if (pendingFromReport > 0) {
    metrics.value[1].value = String(pendingFromReport)
  }

  metrics.value[2].value = `$ ${monthRevenue.toLocaleString('es-CO')}`
  metrics.value[2].change = `${monthDelta >= 0 ? '+' : ''}${monthDelta.toFixed(1)}%`
  metrics.value[2].changeClass = monthDelta >= 0 ? 'text-success' : 'text-danger'

  await nextTick()
  renderCharts()
}

async function loadDashboard() {
  loading.value = true
  let orders = []
  let customerRows = []
  let lowStockProducts = []

  try {
    // Cargar órdenes recientes del endpoint admin global.
    const ordersRes = await orderHttp.get('/admin/orders', { params: { limit: 50 } })
    const ordersData = ordersRes.data?.data || ordersRes.data || []
    orders = Array.isArray(ordersData) ? ordersData : (ordersData.data || [])
    recentOrders.value = orders.slice(0, 8).map(o => ({
      id: o.id,
      customer: o.user_name || o.customer_name || 'Cliente',
      date: o.created_at ? new Date(o.created_at).toLocaleDateString('es-CO') : '-',
      total: Number(o.total || 0).toLocaleString('es-CO'),
      status: o.order_status || o.status || 'pending',
      statusLabel: statusMap[o.order_status || o.status] || o.order_status || 'Pendiente',
      paymentStatus: o.payment_status || 'pending',
      paymentLabel: paymentMap[o.payment_status] || o.payment_status || 'Pendiente',
    }))

    // Filtrar órdenes de hoy para las stat cards
    const todayStr = formatIsoDate(new Date())
    const todayOrders = orders.filter(o => {
      if (!o.created_at) return false
      return o.created_at.startsWith(todayStr)
    })
    stats.value[0].value = String(todayOrders.length)

    const todayRevenue = todayOrders.reduce((sum, o) => sum + Number(o.total || 0), 0)
    stats.value[1].value = `$ ${todayRevenue.toLocaleString('es-CO')}`

    // Comparar con ayer
    const yesterdayStr = formatIsoDate(daysAgo(1))
    const yesterdayOrders = orders.filter(o => o.created_at && o.created_at.startsWith(yesterdayStr))
    const yesterdayRevenue = yesterdayOrders.reduce((sum, o) => sum + Number(o.total || 0), 0)

    if (yesterdayOrders.length > 0) {
      const ordersDelta = Math.round(((todayOrders.length - yesterdayOrders.length) / yesterdayOrders.length) * 100)
      stats.value[0].meta.change = `${ordersDelta >= 0 ? '+' : ''}${ordersDelta}%`
      stats.value[0].meta.changeClass = ordersDelta >= 0 ? 'text-success' : 'text-danger'
    }
    if (yesterdayRevenue > 0) {
      const revDelta = Math.round(((todayRevenue - yesterdayRevenue) / yesterdayRevenue) * 100)
      stats.value[1].meta.change = `${revDelta >= 0 ? '+' : ''}${revDelta}%`
      stats.value[1].meta.changeClass = revDelta >= 0 ? 'text-success' : 'text-danger'
    }

    // Pendientes como cálculo inicial (loadSalesStats puede sobreescribir)
    const pendingCount = orders.filter(o => ['pending', 'processing'].includes(o.order_status || o.status)).length
    metrics.value[1].value = String(pendingCount)

    // Order statuses para gráfico (datos iniciales, el reporte los puede sobreescribir)
    const statusCounts = {}
    orders.forEach(o => {
      const s = o.order_status || o.status || 'pending'
      statusCounts[s] = (statusCounts[s] || 0) + 1
    })
    const colors = { pending: '#f59e0b', processing: '#0077b6', shipped: '#17a2b8', delivered: '#4bb543', cancelled: '#ff3333' }
    orderStatuses.value = Object.entries(statusCounts).map(([key, count]) => ({
      label: statusMap[key] || key,
      count,
      color: colors[key] || '#777',
    }))
  } catch (err) {
    console.warn('Error cargando dashboard:', err)
  }

  try {
    const customersRes = await authHttp.get('/admin/customers')
    const customersData = customersRes.data?.data || customersRes.data || []
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
  } catch (err) {
    console.warn('Error cargando clientes:', err)
  }

  try {
    // Cargar productos para inventario
    let productsData = []

    try {
      const productsRes = await catalogHttp.get('/admin/products', { params: { limit: 200 } })
      productsData = productsRes.data?.data || productsRes.data || []
    } catch (adminError) {
      if (adminError?.response?.status !== 401 && adminError?.response?.status !== 403) {
        throw adminError
      }

      const fallbackRes = await catalogHttp.get('/products', { params: { limit: 200 } })
      productsData = fallbackRes.data?.data || fallbackRes.data || []
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

    inventoryTotal.value = products.length
    const activeProducts = products.filter(p => p.is_active !== false)
    const lowStock = products.filter(p => p.stock > 0 && p.stock <= 5)
    const zeroStock = products.filter(p => p.stock === 0)
    lowStockProducts = lowStock

    inventoryZero.value = zeroStock.length
    stats.value[3].value = String(activeProducts.length)
    stats.value[3].pills[0].value = String(activeProducts.length)
    stats.value[3].pills[1].value = String(lowStock.length)

    lowStockItems.value = lowStock.slice(0, 5).map(p => ({
      id: p.id,
      name: p.name,
      sku: p.sku,
      stock: p.stock,
      rawImage: p.image,
      image: resolveMediaUrl(p.image, 'product'),
    }))

    try {
      const reportRes = await catalogHttp.get('/admin/reports/products')
      const reportData = reportRes.data?.data || reportRes.data || []
      const reportRows = Array.isArray(reportData) ? reportData : (reportData.data || [])

      topProducts.value = reportRows
        .filter((row) => Number(row.units_sold || 0) > 0)
        .sort((a, b) => Number(b.revenue || 0) - Number(a.revenue || 0))
        .slice(0, 5)
        .map((row) => ({
          id: row.id,
          name: row.name || 'Sin nombre',
          units: Number(row.units_sold || 0),
          revenue: Number(row.revenue || 0).toLocaleString('es-CO'),
        }))
    } catch {
      topProducts.value = products
        .filter((p) => p.sold_count > 0)
        .sort((a, b) => b.sold_count - a.sold_count)
        .slice(0, 5)
        .map((p) => ({
          id: p.id,
          name: p.name,
          units: p.sold_count,
          revenue: Number(p.revenue || (p.price * p.sold_count) || 0).toLocaleString('es-CO'),
        }))
    }
  } catch (err) {
    console.warn('Error cargando productos:', err)
  }

  activities.value = buildActivities({
    orders,
    customers: customerRows,
    lowStock: lowStockProducts,
  })

  // Cargar reportes de ventas (resiliente, no bloquea dashboard si falla)
  await loadSalesStats().catch((err) => console.warn('Error cargando reportes de ventas:', err))

  loading.value = false
}

watch(chartRange, () => {
  loadSalesStats().catch((err) => console.warn('Error actualizando rango de gráfico:', err))
})

onBeforeUnmount(() => {
  destroyCharts()
})

onMounted(loadDashboard)
</script>

<style scoped src="./AdminDashboardPage.css"></style>
