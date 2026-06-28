<template>
  <!--
    Página principal del Dashboard de Administración (AdminDashboardPage).
    Responsabilidad: Vista de resumen ejecutivo con KPIs, gráficos, órdenes recientes,
    alertas de inventario, top productos y actividad del sistema.
    Orquesta datos desde useAdminDashboard() y renderiza usando componentes compartidos:
    AdminPageHeader, AdminStatsGrid, AdminCard, AdminEmptyState, AdminTableShimmer, AdminTableImage.
    Incluye gráficos Chart.js (barras+línea para ventas, doughnut para estados de órdenes).
  -->
  <div class="admin-dashboard-page">
    <!-- Cabecera de página: título, bienvenida, breadcrumbs y acciones rápidas (Órdenes, Productos, Actualizar) -->
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

    <!-- Tarjetas de estadísticas principales (KPIs): ventas, órdenes, clientes, productos -->
    <AdminStatsGrid :stats="stats" :loading="loading" :count="4" />

    <!-- Métricas secundarias en grid: tickets promedio, conversión, etc. -->
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

    <!-- Sección de gráficos: ventas (barras+línea) y estados de órdenes (doughnut) -->
    <section class="dashboard-charts-row">
      <!-- Gráfico principal: rendimiento de ventas (ingresos + órdenes) con selector de rango 7/14/30 días -->
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

      <!-- Gráfico lateral: distribución de estados de órdenes (doughnut) con leyenda y barras de porcentaje -->
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
        <!-- Lista de estados con conteo, porcentaje y barra visual -->
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

    <!-- Tabla de últimas órdenes con shimmer de carga y filas navegables (click/enter/space) -->
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

    <!-- Fila inferior de 3 tarjetas: inventario en riesgo, top productos, actividad reciente -->
    <div class="dashboard-bottom-row">
      <!-- Tarjeta: Inventario en riesgo (resumen + lista de variantes con stock crítico/agotado) -->
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

        <!-- Píldoras de resumen: total variantes, bajo stock, sin stock -->
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

      <!-- Tarjeta: Top productos (más vendidos en 30 días) con ranking, unidades e ingresos -->
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

      <!-- Tarjeta: Actividad reciente (eventos del sistema con icono, título, descripción, tiempo) -->
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
/**
 * AdminDashboardPage - Lógica del Dashboard de Administración
 * 
 * Este archivo orquesta la vista principal del dashboard administrativo.
 * Responsabilidades:
 * - Registrar componentes Chart.js necesarios para gráficos de ventas y estados.
 * - Exponer refs para los elementos <canvas> de los gráficos.
 * - Consumir el composable useAdminDashboard() que centraliza toda la lógica de datos,
 *   carga, formateo y navegación del dashboard.
 * - Renderizar y destruir instancias de Chart.js reactivamente ante cambios de datos/rango.
 * - Limpiar recursos al desmontar el componente.
 */

// Módulos de Chart.js: registramos solo controladores, escalas, elementos y plugins que usamos
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

// Composable principal del dashboard: expone estado reactivo, cargas, formateo y navegación
import { useAdminDashboard } from '../composables/useAdminDashboard'

// Componentes compartidos del módulo admin (reutilizables, no duplicar)
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminTableImage from '../components/AdminTableImage.vue'

// Estilos propios de esta vista (scoped via CSS Modules o clase única)
import '../views/AdminDashboardPage.css'

// Registro global de componentes Chart.js que usaremos (bar, line, doughnut)
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

// Refs a los elementos <canvas> del template para instanciar Chart.js
const salesChartRef = ref(null)      // Canvas del gráfico de ventas (barras + línea)
const statusChartRef = ref(null)     // Canvas del gráfico de estados (doughnut)

// Instancias de Chart.js (se guardan en variables mutables para poder destruirlas y recrearlas)
let salesChartInstance = null
let statusChartInstance = null

// =====================================================
// Estado reactivo y API del dashboard (desde useAdminDashboard)
// =====================================================
// Desestructuramos todo lo que expone el composable para uso directo en template y script
const {
  // Listas de datos para renderizado
  activities,              // Actividad reciente del sistema (eventos)
  inventoryAlerts,         // Variantes con stock crítico o agotado
  orderStatuses,           // Estados de órdenes agregados para el doughnut
  recentOrders,            // Últimas órdenes para la tabla
  salesSeries,             // Serie temporal de ventas (ingresos + órdenes por período)
  topProducts,             // Productos más vendidos (ranking)
  // Métricas y KPIs
  stats,                   // 4 tarjetas principales: ventas, órdenes, clientes, productos
  metrics,                 // Métricas secundarias (ticket medio, conversión, etc.)
  inventoryTotal,          // Total de variantes
  inventoryLow,            // Variantes con bajo stock
  inventoryZero,           // Variantes sin stock
  // Texto y labels
  dashboardStoreName,      // Nombre de la tienda para subtitle
  dashboardWelcome,        // Saludo personalizado para el header
  // Flags de estado vacío para gráficos
  hasSalesChartData,       // true si hay datos para el gráfico de ventas
  hasStatusChartData,      // true si hay datos para el doughnut de estados
  // Funciones de carga y navegación
  loadDashboard,           // Recarga completa de datos del dashboard
  openDashboardActivity,   // Navega a detalle de actividad
  openInventoryAlert,      // Navega a ficha de inventario en alerta
  openRecentOrder,         // Navega a detalle de orden reciente
  openTopProduct,          // Navega a ficha de producto top
  // Formateo y helpers
  statusPercentage,        // Calcula % de un conteo sobre total de órdenes
  formatCurrency,          // (disponible via composable, usado en template)
  // Estado de UI
  loading,                 // true mientras se cargan datos iniciales
  // Rangos de fecha para gráficos (reactivos, disparan watch)
  salesChartRange,         // 7, 14 o 30 días para gráfico de ventas
  statusChartRange,        // 7, 14 o 30 días para gráfico de estados
} = useAdminDashboard()

// =====================================================
// Gestión de instancias Chart.js (ciclo de vida)
// =====================================================

/**
 * Destruye ambas instancias de gráficos para liberar memoria y evitar duplicados.
 * Se llama al cambiar rango, al recibir datos vacíos, y al desmontar el componente.
 */
function destroyCharts() {
  destroySalesChart()
  destroyStatusChart()
}

/**
 * Destruye la instancia del gráfico de ventas si existe.
 * Limpia la referencia para permitir nueva creación en renderSalesChart().
 */
function destroySalesChart() {
  if (!salesChartInstance) {
    return
  }
  salesChartInstance.destroy()
  salesChartInstance = null
}

/**
 * Destruye la instancia del gráfico de estados (doughnut) si existe.
 * Limpia la referencia para permitir nueva creación en renderStatusChart().
 */
function destroyStatusChart() {
  if (!statusChartInstance) {
    return
  }
  statusChartInstance.destroy()
  statusChartInstance = null
}

// =====================================================
// Renderizado del gráfico de ventas (barras + línea dual-axis)
// =====================================================
/**
 * Construye/actualiza el gráfico combinado de ingresos (línea) y órdenes (barras).
 * - Usa dos ejes Y: izquierdo para ingresos ($), derecho para órdenes (conteo).
 * - Formatea etiquetas de fecha en locale es-CO (ej: "15 ene").
 * - Si no hay datos o canvas no listo, destruye instancia y muestra empty state.
 * - Se destruye y recrea en cada render para simplicidad y evitar mutaciones complejas.
 */
function renderSalesChart() {
  // Validación: canvas montado y datos disponibles
  if (!salesChartRef.value || !hasSalesChartData.value) {
    destroySalesChart()
    return
  }

  // Limpieza previa por si hubo render anterior
  destroySalesChart()

  // Etiquetas del eje X: fechas formateadas en español (día + mes corto)
  const labels = salesSeries.value.map((item) => {
    const source = item.date || item.period || ''
    if (!source) return '-'
    const parsed = new Date(source)
    if (Number.isNaN(parsed.getTime())) return String(source)
    return parsed.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' })
  })

  // Datasets: ingresos (línea) y órdenes (barras)
  const revenueDataset = salesSeries.value.map((item) => Number(item.revenue || 0))
  const ordersDataset = salesSeries.value.map((item) => Number(item.orders || 0))

  // Instancia Chart.js: tipo 'bar' base + dataset mixto 'line' para ingresos
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
      maintainAspectRatio: false,           // Respeta contenedor CSS
      interaction: { mode: 'index', intersect: false }, // Tooltip compartido por índice
      plugins: {
        legend: { position: 'bottom' },     // Leyenda inferior
      },
      scales: {
        y: {                                // Eje Y izquierdo: ingresos en COP
          position: 'left',
          ticks: {
            callback(value) {
              return `$ ${Number(value).toLocaleString('es-CO')}`
            },
          },
        },
        y1: {                               // Eje Y derecho: conteo de órdenes
          position: 'right',
          grid: { drawOnChartArea: false }, // Sin grid duplicado
        },
      },
    },
  })
}

// =====================================================
// Renderizado del gráfico de estados (doughnut)
// =====================================================
/**
 * Construye/actualiza el gráfico doughnut de distribución de estados de órdenes.
 * - Sin leyenda (se usa lista lateral con barras de porcentaje).
 * - Colores y labels vienen de orderStatuses (reactivo del composable).
 * - cutout 62% para estilo anillo delgado.
 * - Si no hay datos, destruye instancia y muestra empty state.
 */
function renderStatusChart() {
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

/**
 * Orquesta el render de ambos gráficos de forma independiente.
 * Permite que uno esté vacío y el otro con datos sin bloquearse mutuamente.
 */
function renderCharts() {
  renderSalesChart()
  renderStatusChart()
}

// =====================================================
// Reactividad: re-render automático ante cambios de datos/rango
// =====================================================
// Observa salesSeries (cambia al cargar datos o cambiar salesChartRange)
// y orderStatuses (cambia al cargar datos o cambiar statusChartRange).
// Usa nextTick para asegurar que el DOM (canvas) esté listo tras cambio de rango.
watch([salesSeries, orderStatuses], async () => {
  await nextTick()
  renderCharts()
})

// =====================================================
// Limpieza al desmontar
// =====================================================
// Destruye instancias Chart.js para evitar memory leaks al salir del dashboard.
onBeforeUnmount(() => {
  destroyCharts()
})
</script>
