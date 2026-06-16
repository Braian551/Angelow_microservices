<template>
  <div class="admin-dashboard-page">
    <!-- Cabecera: usa el componente global AdminPageHeader -->
    <AdminPageHeader icon="fas fa-chart-line" :title="dashboardWelcome"
      :subtitle="`${dashboardStoreName} · Ventas, órdenes, clientes e inventario en tiempo real.`"
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Resumen' }]">
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
            <span v-if="metric.change" class="dashboard-metric__change" :class="metric.changeClass">{{ metric.change
              }}</span>
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
            <button v-for="r in [7, 14, 30]" :key="r" class="dashboard-range-btn"
              :class="{ active: salesChartRange === r }" type="button" @click="salesChartRange = r">{{ r }}D</button>
          </div>
        </div>
        <div class="dashboard-chart-body">
          <canvas v-if="hasSalesChartData" ref="salesChartRef"></canvas>
          <AdminEmptyState v-else icon="fas fa-chart-column" title="Aún no hay datos"
            description="Todavía no hay ingresos ni órdenes registradas en este período."
            class="dashboard-chart-empty-state" />
        </div>
      </AdminCard>

      <AdminCard :flush="false" class="dashboard-chart-side">
        <div class="dashboard-chart-header">
          <div>
            <h3 class="dashboard-chart-title"><i class="fas fa-tags"></i> Estado de órdenes</h3>
            <p class="dashboard-chart-subtitle">Distribución del período seleccionado.</p>
          </div>
          <div class="dashboard-chart-controls">
            <button v-for="r in [7, 14, 30]" :key="`status-${r}`" class="dashboard-range-btn"
              :class="{ active: statusChartRange === r }" type="button" @click="statusChartRange = r">{{ r }}D</button>
          </div>
        </div>
        <div class="dashboard-chart-body doughnut">
          <canvas v-if="hasStatusChartData" ref="statusChartRef"></canvas>
          <AdminEmptyState v-else icon="fas fa-circle-notch" title="Aún no hay datos"
            description="Todavía no hay estados de órdenes para este período." class="dashboard-chart-empty-state" />
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
              <div class="dashboard-status-bar__fill"
                :style="{ width: statusPercentage(s.count), backgroundColor: s.color }"></div>
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

      <AdminEmptyState v-if="!loading && recentOrders.length === 0" icon="fas fa-inbox" title="Sin órdenes recientes"
        description="Las nuevas órdenes aparecen aquí en tiempo real." />
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
              <td colspan="6">
                <AdminTableShimmer :rows="5" :columns="['line', 'line', 'line', 'line', 'pill', 'pill']" />
              </td>
            </tr>
            <tr v-for="order in recentOrders" v-else :key="order.id"
              class="dashboard-recent-order-row dashboard-recent-order-row--interactive" role="link" tabindex="0"
              @click="openRecentOrder(order)" @keydown.enter.prevent="openRecentOrder(order)"
              @keydown.space.prevent="openRecentOrder(order)">
              <td><strong>#{{ order.id }}</strong></td>
              <td>{{ order.customer }}</td>
              <td class="hide-xs">{{ order.date }}</td>
              <td>$ {{ order.total }}</td>
              <td><span class="status-badge" :class="order.status">{{ order.statusLabel }}</span></td>
              <td class="hide-sm"><span class="status-badge" :class="order.paymentStatus">{{ order.paymentLabel
                  }}</span>
              </td>
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
              <p class="dashboard-section-subtitle">Productos y variantes con stock crítico o agotado.</p>
            </div>
            <RouterLink to="/admin/inventario" class="btn-link">Ver inventario</RouterLink>
          </div>
        </template>

        <div class="dashboard-inventory-pills">
          <div class="dashboard-inventory-pill">
            <p>Total de variantes</p>
            <strong>{{ inventoryTotal }}</strong>
          </div>
          <div class="dashboard-inventory-pill">
            <p>Bajo stock</p>
            <strong>{{ inventoryLow }}</strong>
          </div>
          <div class="dashboard-inventory-pill">
            <p>Sin stock</p>
            <strong>{{ inventoryZero }}</strong>
          </div>
        </div>

        <AdminEmptyState v-if="!loading && inventoryAlerts.length === 0" icon="fas fa-check-circle"
          title="Sin productos en riesgo" description="No hay variantes agotadas ni con stock crítico." />
        <div v-else class="dashboard-low-stock">
          <button v-for="item in inventoryAlerts" :key="item.id" type="button"
            class="dashboard-low-stock__item dashboard-low-stock__item--interactive" @click="openInventoryAlert(item)">
            <AdminTableImage :src="item.image" :alt="item.name" type="product" size="sm" />
            <div class="dashboard-low-stock__info">
              <strong>{{ item.name }}</strong>
              <span>{{ item.variantLabel }}</span>
            </div>
            <span class="status-badge" :class="item.status === 'out' ? 'cancelled' : 'pending'">{{ item.alertLabel
              }}</span>
          </button>
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

        <AdminEmptyState v-if="!loading && topProducts.length === 0" icon="fas fa-chart-bar" title="Sin datos de ventas"
          description="Los productos con más ventas aparecerán aquí." />
        <div v-else class="dashboard-top-products">
          <button v-for="(product, index) in topProducts" :key="product.id" type="button"
            class="dashboard-top-product dashboard-top-product--interactive" @click="openTopProduct(product)">
            <span class="dashboard-top-product__rank">{{ index + 1 }}</span>
            <div class="dashboard-top-product__info">
              <strong>{{ product.name }}</strong>
              <span>{{ product.units }} vendidos</span>
            </div>
            <span class="dashboard-top-product__revenue">$ {{ product.revenue }}</span>
          </button>
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

        <AdminEmptyState v-if="!loading && activities.length === 0" icon="fas fa-history" title="Sin actividad reciente"
          description="Los eventos del sistema aparecerán aquí." />
        <div v-else class="dashboard-activity">
          <button v-for="a in activities" :key="a.id" type="button"
            class="dashboard-activity__item dashboard-activity__item--interactive" @click="openDashboardActivity(a)">
            <div class="dashboard-activity__icon" :class="a.type"><i :class="a.icon"></i></div>
            <div class="dashboard-activity__content">
              <p>{{ a.title }}</p>
              <span v-if="a.description">{{ a.description }}</span>
              <span class="dashboard-activity__time">{{ a.time }}</span>
            </div>
          </button>
        </div>
      </AdminCard>
    </div>
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
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useAdminDashboard } from '../composables/useAdminDashboard'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import '../views/AdminDashboardPage.css'

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

let salesChartInstance = null
let statusChartInstance = null

// =====================================================
// Lógica principal del dashboard
// =====================================================
const {
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
} = useAdminDashboard()

function destroyCharts() {
  // Se destruyen ambas instancias para evitar gráficos duplicados al cambiar período o estado vacío.
  destroySalesChart()
  destroyStatusChart()
}

function destroySalesChart() {
  // La instancia del gráfico de ventas se recicla cada vez que cambian los datos del reporte.
  if (!salesChartInstance) {
    return
  }

  salesChartInstance.destroy()
  salesChartInstance = null
}

function destroyStatusChart() {
  // La instancia del doughnut se recicla cuando cambia el rango o entra/sale del estado vacío.
  if (!statusChartInstance) {
    return
  }

  statusChartInstance.destroy()
  statusChartInstance = null
}

function renderSalesChart() {
  // Si no hay datos o el canvas no está montado, se limpia el gráfico y se deja ver el empty state.
  if (!salesChartRef.value || !hasSalesChartData.value) {
    destroySalesChart()
    return
  }

  destroySalesChart()

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
}

function renderStatusChart() {
  // Si no hay estados agregados para el período actual, se limpia el doughnut y se muestra el estado vacío.
  if (!statusChartRef.value || !hasStatusChartData.value) {
    destroyStatusChart()
    return
  }

  destroyStatusChart()

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

function renderCharts() {
  // Cada gráfico se dibuja de forma independiente para soportar vacíos parciales sin bloquear al otro.
  renderSalesChart()
  renderStatusChart()
}

// =====================================================
// Integración con gráficas
// =====================================================
watch([salesSeries, orderStatuses], async () => {
  await nextTick()
  renderCharts()
})

onBeforeUnmount(() => {
  destroyCharts()
})
</script>
