<template>
  <div class="admin-reports-page">
    <AdminPageHeader
      icon="fas fa-chart-bar"
      title="Informes"
      subtitle="Analiza ventas, productos populares y clientes recurrentes con filtros operativos, detalle y exportación centralizada."
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="resetFilters">
          <i class="fas fa-rotate-left"></i>
          Restablecer
        </button>
        <button class="btn btn-secondary" type="button" @click="exportReport">
          <i class="fas fa-file-export"></i>
          Exportar CSV
        </button>
        <button class="btn btn-primary" type="button" @click="printReport">
          <i class="fas fa-print"></i>
          Imprimir
        </button>
      </template>
    </AdminPageHeader>

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-sliders-h"></i>
            <h3>Filtros e informe activo</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="report-tabs" role="tablist" aria-label="Secciones de informes">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="report-tab"
          :class="{ active: activeTab === tab.id }"
          @click="goToTab(tab.id)"
        >
          <i :class="tab.icon"></i>
          {{ tab.label }}
        </button>
      </div>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input
            v-model.trim="activeSearchModel"
            type="text"
            class="search-input"
            :placeholder="searchPlaceholder"
          >
          <button v-if="activeSearchModel" type="button" class="search-clear" @click="clearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--reports">
          <template v-if="activeTab === 'sales'">
            <div class="filter-group">
              <label for="report-sales-from"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
              <input id="report-sales-from" v-model="filters.sales.from" type="date" class="form-control">
            </div>
            <div class="filter-group">
              <label for="report-sales-to"><i class="fas fa-calendar-check"></i> Fecha fin</label>
              <input id="report-sales-to" v-model="filters.sales.to" type="date" class="form-control">
            </div>
            <div class="filter-group">
              <label for="report-sales-status"><i class="fas fa-signal"></i> Estado</label>
              <select id="report-sales-status" v-model="filters.sales.status" class="form-control">
                <option value="">Todos</option>
                <option value="pending">Pendiente</option>
                <option value="processing">En proceso</option>
                <option value="shipped">Enviado</option>
                <option value="delivered">Entregado</option>
                <option value="cancelled">Cancelado</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="report-sales-group"><i class="fas fa-layer-group"></i> Agrupar por</label>
              <select id="report-sales-group" v-model="filters.sales.groupBy" class="form-control" @change="renderCharts()">
                <option value="day">Día</option>
                <option value="week">Semana</option>
                <option value="month">Mes</option>
                <option value="year">Año</option>
              </select>
            </div>
          </template>

          <template v-else-if="activeTab === 'products'">
            <div class="filter-group">
              <label for="report-products-from"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
              <input id="report-products-from" v-model="filters.products.from" type="date" class="form-control">
            </div>
            <div class="filter-group">
              <label for="report-products-to"><i class="fas fa-calendar-check"></i> Fecha fin</label>
              <input id="report-products-to" v-model="filters.products.to" type="date" class="form-control">
            </div>
            <div class="filter-group">
              <label for="report-products-limit"><i class="fas fa-list-ol"></i> Top</label>
              <select id="report-products-limit" v-model.number="filters.products.limit" class="form-control">
                <option :value="10">Top 10</option>
                <option :value="20">Top 20</option>
                <option :value="50">Top 50</option>
                <option :value="100">Top 100</option>
              </select>
            </div>
          </template>

          <template v-else>
            <div class="filter-group">
              <label for="report-customers-min"><i class="fas fa-repeat"></i> Mínimo de órdenes</label>
              <select id="report-customers-min" v-model.number="filters.customers.minOrders" class="form-control">
                <option :value="2">2+ órdenes</option>
                <option :value="3">3+ órdenes</option>
                <option :value="5">5+ órdenes</option>
                <option :value="10">10+ órdenes</option>
              </select>
            </div>
          </template>
        </div>

        <div class="filters-actions-bar">
          <div class="active-filters">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="resetFilters">
              <i class="fas fa-rotate-left"></i>
              Restablecer filtros
            </button>
            <button type="button" class="btn btn-primary" @click="loadCurrentReport">
              <i class="fas fa-magnifying-glass"></i>
              Aplicar
            </button>
          </div>
        </div>
      </div>
    </AdminCard>

    <AdminStatsGrid :loading="loading" :count="4" :stats="activeStats" />

    <div class="results-summary">
      <div class="results-info">
        <i :class="activeTabConfig.icon"></i>
        <p>{{ resultsLabel }}</p>
      </div>
      <div class="quick-actions">
        <span class="results-note">{{ activeTabConfig.note }}</span>
      </div>
    </div>

    <section v-if="activeTab === 'sales'" class="report-section">
      <div class="report-grid report-grid--sales-charts">
        <AdminCard title="Evolución de ventas" icon="fas fa-chart-line" :flush="true">
          <div class="chart-card__body">
            <canvas ref="salesEvolutionCanvas"></canvas>
          </div>
        </AdminCard>

        <AdminCard title="Comparativa mensual" icon="fas fa-chart-column" :flush="true">
          <div class="chart-card__body chart-card__body--compact">
            <canvas ref="monthlyComparisonCanvas"></canvas>
          </div>
        </AdminCard>
      </div>

      <AdminCard title="Detalle de ventas por período" icon="fas fa-table" :flush="true">
        <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'line', 'line', 'btn']" />
        <AdminEmptyState
          v-else-if="groupedSalesRows.length === 0"
          icon="fas fa-chart-line"
          title="Sin datos de ventas"
          description="Ajusta los filtros para ver resultados del período seleccionado."
        />
        <div v-else class="table-responsive">
          <table class="dashboard-table reports-table">
            <thead>
              <tr>
                <th>Período</th>
                <th>Órdenes</th>
                <th>Subtotal</th>
                <th>Envío</th>
                <th>Descuentos</th>
                <th>Total</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in groupedSalesRows" :key="row.period">
                <td>
                  <div class="entity-name-cell">
                    <strong>{{ formatPeriodLabel(row.period, filters.sales.groupBy) }}</strong>
                    <span>Ticket promedio {{ formatCurrency(row.avg_order_value) }}</span>
                  </div>
                </td>
                <td>{{ row.orders }}</td>
                <td>{{ formatCurrency(row.subtotal) }}</td>
                <td>{{ formatCurrency(row.shipping) }}</td>
                <td>{{ formatCurrency(row.discount) }}</td>
                <td><strong>{{ formatCurrency(row.revenue) }}</strong></td>
                <td>
                  <div class="entity-actions">
                    <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal('sales', row)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </AdminCard>
    </section>

    <section v-else-if="activeTab === 'products'" class="report-section">
      <div class="report-grid report-grid--products-charts">
        <AdminCard title="Top productos por ingresos" icon="fas fa-trophy" :flush="true">
          <div class="chart-card__body chart-card__body--large">
            <canvas ref="topProductsCanvas"></canvas>
          </div>
        </AdminCard>

        <AdminCard title="Ventas por categoría" icon="fas fa-chart-pie" :flush="true">
          <div class="chart-card__body chart-card__body--compact">
            <canvas ref="categoriesCanvas"></canvas>
          </div>
        </AdminCard>

        <AdminCard title="Más vendidos por cantidad" icon="fas fa-boxes" :flush="true">
          <div class="chart-card__body chart-card__body--compact">
            <canvas ref="quantityProductsCanvas"></canvas>
          </div>
        </AdminCard>
      </div>

      <AdminCard title="Ranking de productos" icon="fas fa-box" :flush="true">
        <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'line', 'line', 'btn']" />
        <AdminEmptyState
          v-else-if="filteredProductsRows.length === 0"
          icon="fas fa-box"
          title="Sin datos de productos"
          description="No hay productos vendidos para los filtros seleccionados."
        />
        <div v-else class="table-responsive">
          <table class="dashboard-table reports-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Veces vendido</th>
                <th>Cantidad</th>
                <th>Precio promedio</th>
                <th>Ingresos</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in filteredProductsRows" :key="row.product_id">
                <td>
                  <div class="report-product-cell">
                    <img
                      class="report-product-cell__image"
                      :src="resolveMediaUrl(row.image, 'product')"
                      :alt="row.name"
                      @error="handleMediaError($event, row.image, 'product')"
                    >
                    <div class="entity-name-cell">
                      <strong>{{ row.name }}</strong>
                      <span>{{ row.slug || `Producto #${row.product_id}` }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ row.category_name || 'Sin categoría' }}</td>
                <td>{{ row.times_sold }}</td>
                <td>{{ row.total_quantity }}</td>
                <td>{{ formatCurrency(row.avg_price) }}</td>
                <td><strong>{{ formatCurrency(row.total_revenue) }}</strong></td>
                <td>
                  <div class="entity-actions">
                    <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal('products', row)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </AdminCard>
    </section>

    <section v-else class="report-section">
      <div class="report-grid report-grid--customers-charts">
        <AdminCard title="Distribución de clientes" icon="fas fa-chart-pie" :flush="true">
          <div class="chart-card__body chart-card__body--compact">
            <canvas ref="customerDistributionCanvas"></canvas>
          </div>
        </AdminCard>

        <AdminCard title="Top clientes por valor" icon="fas fa-ranking-star" :flush="true">
          <div class="chart-card__body chart-card__body--large">
            <canvas ref="topCustomersCanvas"></canvas>
          </div>
        </AdminCard>
      </div>

      <AdminCard title="Ranking de clientes recurrentes" icon="fas fa-users" :flush="true">
        <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'line', 'line', 'btn']" />
        <AdminEmptyState
          v-else-if="filteredCustomerRows.length === 0"
          icon="fas fa-users"
          title="Sin clientes recurrentes"
          description="No se encontraron clientes que cumplan el mínimo de órdenes."
        />
        <div v-else class="table-responsive">
          <table class="dashboard-table reports-table">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Email</th>
                <th>Órdenes</th>
                <th>Total gastado</th>
                <th>Promedio</th>
                <th>Última compra</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in filteredCustomerRows" :key="row.id">
                <td>
                  <div class="report-customer-cell">
                    <img
                      class="report-customer-cell__image"
                      :src="resolveMediaUrl(row.image, 'avatar')"
                      :alt="row.name"
                      @error="handleMediaError($event, row.image, 'avatar')"
                    >
                    <div class="entity-name-cell">
                      <strong>{{ row.name }}</strong>
                      <span>{{ row.phone || 'Sin teléfono registrado' }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ row.email || 'Sin correo' }}</td>
                <td>{{ row.orders_count }}</td>
                <td><strong>{{ formatCurrency(row.total_spent) }}</strong></td>
                <td>{{ formatCurrency(row.avg_order_value) }}</td>
                <td>{{ formatDateTime(row.last_order) }}</td>
                <td>
                  <div class="entity-actions">
                    <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal('customers', row)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </AdminCard>
    </section>

    <AdminModal :show="showDetailModal" :title="detailTitle" max-width="980px" @close="closeDetailModal">
      <template v-if="detailContext && detailContext.type === 'sales'">
        <div class="admin-detail-grid">
          <AdminCard title="Resumen del período" icon="fas fa-chart-line">
            <div class="admin-surface-card">
              <p class="admin-surface-card__label">Período</p>
              <h3>{{ formatPeriodLabel(detailContext.row.period, filters.sales.groupBy) }}</h3>
              <p class="admin-surface-card__value">{{ detailContext.row.orders }} órdenes</p>
              <span class="status-badge info">Ticket promedio {{ formatCurrency(detailContext.row.avg_order_value) }}</span>
            </div>
          </AdminCard>
          <AdminCard title="Desglose financiero" icon="fas fa-wallet">
            <div class="summary-stack">
              <div class="summary-row"><span>Subtotal</span><strong>{{ formatCurrency(detailContext.row.subtotal) }}</strong></div>
              <div class="summary-row"><span>Envío</span><strong>{{ formatCurrency(detailContext.row.shipping) }}</strong></div>
              <div class="summary-row"><span>Descuentos</span><strong>{{ formatCurrency(detailContext.row.discount) }}</strong></div>
              <div class="summary-row"><span>Total</span><strong>{{ formatCurrency(detailContext.row.revenue) }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>

      <template v-else-if="detailContext && detailContext.type === 'products'">
        <div class="admin-detail-grid">
          <AdminCard title="Ficha del producto" icon="fas fa-box-open">
            <div class="report-product-detail">
              <img
                class="report-product-detail__image"
                :src="resolveMediaUrl(detailContext.row.image, 'product')"
                :alt="detailContext.row.name"
                @error="handleMediaError($event, detailContext.row.image, 'product')"
              >
              <div class="admin-surface-card">
                <p class="admin-surface-card__label">Producto</p>
                <h3>{{ detailContext.row.name }}</h3>
                <p>{{ detailContext.row.category_name || 'Sin categoría' }}</p>
                <span class="status-badge info">{{ detailContext.row.times_sold }} ventas registradas</span>
              </div>
            </div>
          </AdminCard>
          <AdminCard title="Rendimiento" icon="fas fa-chart-column">
            <div class="summary-stack">
              <div class="summary-row"><span>Cantidad total</span><strong>{{ detailContext.row.total_quantity }}</strong></div>
              <div class="summary-row"><span>Precio promedio</span><strong>{{ formatCurrency(detailContext.row.avg_price) }}</strong></div>
              <div class="summary-row"><span>Ingresos</span><strong>{{ formatCurrency(detailContext.row.total_revenue) }}</strong></div>
              <div class="summary-row"><span>Primera venta</span><strong>{{ formatDateTime(detailContext.row.first_order_at) }}</strong></div>
              <div class="summary-row"><span>Última venta</span><strong>{{ formatDateTime(detailContext.row.last_order_at) }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>

      <template v-else-if="detailContext && detailContext.type === 'customers'">
        <div class="admin-detail-grid">
          <AdminCard title="Perfil del cliente" icon="fas fa-user-circle">
            <div class="report-customer-detail">
              <img
                class="report-customer-detail__image"
                :src="resolveMediaUrl(detailContext.row.image, 'avatar')"
                :alt="detailContext.row.name"
                @error="handleMediaError($event, detailContext.row.image, 'avatar')"
              >
              <div class="admin-surface-card">
                <p class="admin-surface-card__label">Cliente</p>
                <h3>{{ detailContext.row.name }}</h3>
                <p>{{ detailContext.row.email || 'Sin correo' }}</p>
                <p>{{ detailContext.row.phone || 'Sin teléfono registrado' }}</p>
              </div>
            </div>
          </AdminCard>
          <AdminCard title="Actividad comercial" icon="fas fa-bag-shopping">
            <div class="summary-stack">
              <div class="summary-row"><span>Órdenes</span><strong>{{ detailContext.row.orders_count }}</strong></div>
              <div class="summary-row"><span>Total gastado</span><strong>{{ formatCurrency(detailContext.row.total_spent) }}</strong></div>
              <div class="summary-row"><span>Valor promedio</span><strong>{{ formatCurrency(detailContext.row.avg_order_value) }}</strong></div>
              <div class="summary-row"><span>Primera compra</span><strong>{{ formatDateTime(detailContext.row.first_order) }}</strong></div>
              <div class="summary-row"><span>Última compra</span><strong>{{ formatDateTime(detailContext.row.last_order) }}</strong></div>
              <div class="summary-row"><span>Días desde la última compra</span><strong>{{ detailContext.row.customer_age_days ?? 'Sin dato' }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>
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
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authHttp, catalogHttp, orderHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

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

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const tabs = [
  { id: 'sales', label: 'Ventas', icon: 'fas fa-chart-line', path: '/admin/informes/ventas', note: 'Se excluyen órdenes canceladas por defecto.' },
  { id: 'products', label: 'Productos populares', icon: 'fas fa-fire', path: '/admin/informes/productos', note: 'El ranking usa ingresos y cantidad vendidos en el período.' },
  { id: 'customers', label: 'Clientes recurrentes', icon: 'fas fa-users', path: '/admin/informes/clientes', note: 'El mínimo de órdenes se aplica sobre compras reales registradas.' },
]

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const showAdvanced = ref(true)
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

const productCategoryBreakdown = computed(() => {
  const totals = new Map()
  for (const row of filteredProductsRows.value) {
    const category = row.category_name || 'Sin categoría'
    totals.set(category, (totals.get(category) || 0) + Number(row.total_revenue || 0))
  }
  return [...totals.entries()].map(([name, revenue]) => ({ name, revenue }))
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

const filteredCustomerRows = computed(() => {
  const term = filters.customers.search.trim().toLowerCase()
  return customerRows.value
    .filter((row) => {
      if (!term) return true
      return [row.name, row.email, row.phone].join(' ').toLowerCase().includes(term)
    })
    .sort((a, b) => Number(b.total_spent || 0) - Number(a.total_spent || 0))
})

const salesStats = computed(() => [
  { key: 'revenue', label: 'Ingresos totales', value: formatCurrency(salesReport.value.total_revenue || salesReport.value.totalRevenue || 0), icon: 'fas fa-dollar-sign', color: 'success' },
  { key: 'orders', label: 'Total órdenes', value: Number(salesReport.value.total_orders || salesReport.value.totalOrders || 0), icon: 'fas fa-shopping-cart', color: 'primary' },
  { key: 'shipping', label: 'Costos de envío', value: formatCurrency(salesReport.value.total_shipping || salesReport.value.totalShipping || 0), icon: 'fas fa-truck', color: 'info' },
  { key: 'discount', label: 'Descuentos', value: formatCurrency(salesReport.value.total_discount || salesReport.value.totalDiscount || 0), icon: 'fas fa-percent', color: 'warning' },
])

const productStats = computed(() => {
  const totalRevenue = filteredProductsRows.value.reduce((acc, row) => acc + Number(row.total_revenue || 0), 0)
  const totalQuantity = filteredProductsRows.value.reduce((acc, row) => acc + Number(row.total_quantity || 0), 0)
  const topCategory = [...productCategoryBreakdown.value].sort((a, b) => b.revenue - a.revenue)[0]?.name || 'Sin categoría'

  return [
    { key: 'products', label: 'Productos listados', value: filteredProductsRows.value.length, icon: 'fas fa-box', color: 'primary' },
    { key: 'units', label: 'Unidades vendidas', value: totalQuantity, icon: 'fas fa-boxes-stacked', color: 'success' },
    { key: 'revenue', label: 'Ingresos del ranking', value: formatCurrency(totalRevenue), icon: 'fas fa-chart-line', color: 'info' },
    { key: 'category', label: 'Categoría líder', value: topCategory, icon: 'fas fa-tags', color: 'warning' },
  ]
})

const recurringCustomerCount = computed(() => filteredCustomerRows.value.filter((row) => Number(row.orders_count || 0) >= 2).length)

const customerStatsFormatted = computed(() => [
  { key: 'total', label: 'Total clientes', value: Number(customerStats.value.totalCustomers || 0), icon: 'fas fa-users', color: 'primary' },
  { key: 'with-orders', label: 'Con compras', value: Number(customerStats.value.customersWithOrders || 0), icon: 'fas fa-bag-shopping', color: 'success' },
  { key: 'returning', label: 'Recurrentes', value: recurringCustomerCount.value, icon: 'fas fa-repeat', color: 'warning' },
  { key: 'avg', label: 'Promedio órdenes', value: customerStats.value.avgOrdersPerCustomer || 0, icon: 'fas fa-chart-bar', color: 'info' },
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
  if (activeTab.value === 'sales') return 'Buscar por período...'
  if (activeTab.value === 'products') return 'Buscar por producto o categoría...'
  return 'Buscar por cliente, correo o teléfono...'
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
  if (activeTab.value === 'sales') return `Mostrando ${groupedSalesRows.value.length} períodos agrupados`
  if (activeTab.value === 'products') return `Mostrando ${filteredProductsRows.value.length} productos del ranking`
  return `Mostrando ${filteredCustomerRows.value.length} clientes recurrentes`
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

function initializeFilters() {
  const today = new Date()
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
  const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate())

  filters.sales.from = toInputDate(startOfMonth)
  filters.sales.to = toInputDate(today)
  filters.products.from = toInputDate(threeMonthsAgo)
  filters.products.to = toInputDate(today)
}

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
              label: 'Órdenes',
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
              ticks: {
                callback(value) {
                  return formatCurrency(value)
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
            data: [monthlyComparison.value.previous, monthlyComparison.value.current],
            backgroundColor: ['#8fa8bf', '#0f7abf'],
            borderRadius: 8,
            barThickness: 42,
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
      topCustomersChart = new Chart(topCustomersCanvas.value, {
        type: 'bar',
        data: {
          labels: filteredCustomerRows.value.slice(0, 10).map((row) => truncateText(row.name, 18)),
          datasets: [{
            label: 'Valor acumulado',
            data: filteredCustomerRows.value.slice(0, 10).map((row) => Number(row.total_spent || 0)),
            backgroundColor: '#0f7abf',
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
  const ids = orderRows.map((row) => row.user_id).filter(Boolean)
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

  customerDistribution.value = Array.isArray(orderPayload.distribution) ? orderPayload.distribution : []
  customerStats.value = {
    totalCustomers: Number(authSummary.totalCustomers || 0),
    customersWithOrders: Number(orderPayload.stats?.customers_with_orders || 0),
    returningCustomers: Number(orderPayload.stats?.returning_customers || 0),
    avgOrdersPerCustomer: Number(orderPayload.stats?.avg_orders_per_customer || 0),
  }
}

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
      title: 'Rango inválido',
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

function clearSearch() {
  activeSearchModel.value = ''
  renderCharts()
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

function exportReport() {
  let filename = ''
  let headers = []
  let rows = []

  if (activeTab.value === 'sales') {
    filename = 'informe-ventas.csv'
    headers = ['Periodo', 'Ordenes', 'Subtotal', 'Envio', 'Descuentos', 'Total', 'Ticket promedio']
    rows = groupedSalesRows.value.map((row) => [formatPeriodLabel(row.period, filters.sales.groupBy), row.orders, row.subtotal, row.shipping, row.discount, row.revenue, row.avg_order_value])
  } else if (activeTab.value === 'products') {
    filename = 'productos-populares.csv'
    headers = ['Producto', 'Categoria', 'Veces vendido', 'Cantidad total', 'Precio promedio', 'Ingresos']
    rows = filteredProductsRows.value.map((row) => [row.name, row.category_name || 'Sin categoría', row.times_sold, row.total_quantity, row.avg_price, row.total_revenue])
  } else {
    filename = 'clientes-recurrentes.csv'
    headers = ['Cliente', 'Email', 'Telefono', 'Ordenes', 'Total gastado', 'Valor promedio', 'Primera compra', 'Ultima compra']
    rows = filteredCustomerRows.value.map((row) => [row.name, row.email || '', row.phone || '', row.orders_count, row.total_spent, row.avg_order_value, row.first_order || '', row.last_order || ''])
  }

  if (rows.length === 0) {
    showSnackbar({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  const csv = [headers.join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv(filename, csv)
  showSnackbar({ type: 'success', message: 'CSV generado correctamente.' })
}

function printReport() {
  const hasData = activeTab.value === 'sales'
    ? groupedSalesRows.value.length > 0
    : activeTab.value === 'products'
      ? filteredProductsRows.value.length > 0
      : filteredCustomerRows.value.length > 0

  if (!hasData) {
    showSnackbar({ type: 'warning', message: 'No hay datos para imprimir.' })
    return
  }

  window.print()
}

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
  if (!period) return 'Sin período'
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

function csvSafe(value) {
  return `"${String(value ?? '').replaceAll('"', '""')}"`
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
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
</script>

<style scoped>
.report-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  padding: 1.6rem 1.6rem 0;
}

.report-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
  border: 1px solid rgba(15, 122, 191, 0.16);
  background: #f8fbfe;
  color: var(--admin-text);
  border-radius: 999px;
  padding: 0.9rem 1.4rem;
  cursor: pointer;
  transition: 0.2s ease;
}

.report-tab.active {
  background: var(--admin-primary);
  color: #fff;
  border-color: var(--admin-primary);
}

.filters-row--reports {
  grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
}

.report-section {
  display: grid;
  gap: 1.6rem;
}

.report-grid {
  display: grid;
  gap: 1.6rem;
}

.report-grid--sales-charts,
.report-grid--customers-charts {
  grid-template-columns: 1.25fr 0.95fr;
}

.report-grid--products-charts {
  grid-template-columns: 1.2fr 0.9fr 0.9fr;
}

.chart-card__body {
  height: 32rem;
  padding: 1.4rem;
}

.chart-card__body--compact {
  height: 30rem;
}

.chart-card__body--large {
  height: 34rem;
}

.reports-table th,
.reports-table td {
  vertical-align: middle;
}

.report-product-cell,
.report-customer-cell,
.report-product-detail,
.report-customer-detail {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.report-product-cell__image,
.report-product-detail__image {
  width: 5.4rem;
  height: 5.4rem;
  border-radius: 1rem;
  object-fit: cover;
  background: #f3f7fb;
  border: 1px solid rgba(15, 122, 191, 0.12);
}

.report-customer-cell__image,
.report-customer-detail__image {
  width: 4.8rem;
  height: 4.8rem;
  border-radius: 999px;
  object-fit: cover;
  background: #f3f7fb;
  border: 1px solid rgba(15, 122, 191, 0.12);
}

.report-product-detail,
.report-customer-detail {
  align-items: stretch;
}

.report-product-detail__image {
  width: 12rem;
  height: 12rem;
}

.report-customer-detail__image {
  width: 8.4rem;
  height: 8.4rem;
}

@media (max-width: 1100px) {
  .report-grid--sales-charts,
  .report-grid--customers-charts,
  .report-grid--products-charts {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 720px) {
  .report-tabs {
    padding: 1.2rem 1.2rem 0;
  }

  .report-tab {
    width: 100%;
    justify-content: center;
  }

  .report-product-cell,
  .report-customer-cell,
  .report-product-detail,
  .report-customer-detail {
    align-items: flex-start;
  }

  .chart-card__body,
  .chart-card__body--compact,
  .chart-card__body--large {
    height: 28rem;
  }
}
</style>