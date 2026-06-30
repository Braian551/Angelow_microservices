<template>
  <!--
    Componente principal de informes del panel de administración.
    Permite al administrador analizar ventas, productos populares y clientes recurrentes.
    Incluye filtros por fecha, estado y agrupación, visualización con gráficas y tablas,
    y exportación de resultados en formatos Excel y PDF.
  -->
  <div class="admin-reports-page">
    <AdminPageHeader
      icon="fas fa-chart-bar"
      title="Informes"
      subtitle="Analiza ventas, productos populares y clientes recurrentes con filtros claros, detalle y exportación sencilla."
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="resetFilters">
          <i class="fas fa-rotate-left"></i>
          Restablecer
        </button>
        <AdminExportActions
          tone="header"
          :disabled="loading || activeReportRows.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportReport('excel')"
          @pdf="exportReport('pdf')"
        />
      </template>
    </AdminPageHeader>

    <AdminFilterCard
      v-model="activeSearchModel"
      icon="fas fa-sliders-h"
      title="Filtros e informe activo"
      :placeholder="searchPlaceholder"
      @search="loadCurrentReport"
    >
      <!-- Pestañas de sección: siempre visibles al expandir -->
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

      <template #advanced>
        <div class="admin-filters__row admin-filters__row--4">
          <template v-if="activeTab === 'sales'">
            <div class="admin-filters__group">
              <label for="report-sales-from"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
              <input id="report-sales-from" v-model="filters.sales.from" type="date">
            </div>
            <div class="admin-filters__group">
              <label for="report-sales-to"><i class="fas fa-calendar-check"></i> Fecha fin</label>
              <input id="report-sales-to" v-model="filters.sales.to" type="date">
            </div>
            <div class="admin-filters__group">
              <label for="report-sales-status"><i class="fas fa-signal"></i> Estado</label>
              <select id="report-sales-status" v-model="filters.sales.status">
                <option value="">Todos</option>
                <option value="pending">Pendiente</option>
                <option value="processing">En proceso</option>
                <option value="shipped">Enviado</option>
                <option value="delivered">Entregado</option>
                <option value="cancelled">Cancelado</option>
              </select>
            </div>
            <div class="admin-filters__group">
              <label for="report-sales-group"><i class="fas fa-layer-group"></i> Agrupar por</label>
              <select id="report-sales-group" v-model="filters.sales.groupBy" @change="renderCharts()">
                <option value="day">Día</option>
                <option value="week">Semana</option>
                <option value="month">Mes</option>
                <option value="year">Año</option>
              </select>
            </div>
          </template>

          <template v-else-if="activeTab === 'products'">
            <div class="admin-filters__group">
              <label for="report-products-from"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
              <input id="report-products-from" v-model="filters.products.from" type="date">
            </div>
            <div class="admin-filters__group">
              <label for="report-products-to"><i class="fas fa-calendar-check"></i> Fecha fin</label>
              <input id="report-products-to" v-model="filters.products.to" type="date">
            </div>
            <div class="admin-filters__group">
              <label for="report-products-limit"><i class="fas fa-list-ol"></i> Top</label>
              <select id="report-products-limit" v-model.number="filters.products.limit">
                <option :value="10">Top 10</option>
                <option :value="20">Top 20</option>
                <option :value="50">Top 50</option>
                <option :value="100">Top 100</option>
              </select>
            </div>
          </template>

          <template v-else>
            <div class="admin-filters__group">
              <label for="report-customers-min"><i class="fas fa-repeat"></i> Mínimo de órdenes</label>
              <select id="report-customers-min" v-model.number="filters.customers.minOrders">
                <option :value="2">2+ órdenes</option>
                <option :value="3">3+ órdenes</option>
                <option :value="5">5+ órdenes</option>
                <option :value="10">10+ órdenes</option>
              </select>
            </div>
          </template>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="resetFilters">
              <i class="fas fa-rotate-left"></i>
              Restablecer filtros
            </button>
            <button type="button" class="admin-filters__apply" @click="loadCurrentReport">
              <i class="fas fa-magnifying-glass"></i>
              Aplicar
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminStatsGrid :loading="loading" :count="4" :stats="activeStats" />

    <AdminResultsBar :text="resultsLabel">
      <template #actions>
        <span class="results-note">{{ activeTabConfig.note }}</span>
      </template>
    </AdminResultsBar>

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
              <tr v-for="row in salesPagination.paginatedItems" :key="row.period">
                <td>
                  <div class="admin-entity-name">
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
                  <div class="admin-entity-actions">
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

      <AdminPagination
        v-model:page="salesPagination.currentPage"
        v-model:page-size="salesPagination.pageSize"
        :total-items="salesPagination.totalItems"
        :page-size-options="salesPagination.pageSizeOptions"
      />
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
              <tr v-for="row in productsPagination.paginatedItems" :key="row.product_id">
                <td>
                  <div class="report-product-cell">
                    <img
                      class="report-product-cell__image"
                      :src="resolveMediaUrl(row.image, 'product')"
                      :alt="row.name"
                      @error="handleMediaError($event, row.image, 'product')"
                    >
                    <div class="admin-entity-name">
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
                  <div class="admin-entity-actions">
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

      <AdminPagination
        v-model:page="productsPagination.currentPage"
        v-model:page-size="productsPagination.pageSize"
        :total-items="productsPagination.totalItems"
        :page-size-options="productsPagination.pageSizeOptions"
      />
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
              <tr v-for="row in customersPagination.paginatedItems" :key="row.id">
                <td>
                  <div class="report-customer-cell">
                    <img
                      class="report-customer-cell__image"
                      :src="resolveMediaUrl(row.image, 'avatar')"
                      :alt="row.name"
                      @error="handleMediaError($event, row.image, 'avatar')"
                    >
                    <div class="admin-entity-name">
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
                  <div class="admin-entity-actions">
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

      <AdminPagination
        v-model:page="customersPagination.currentPage"
        v-model:page-size="customersPagination.pageSize"
        :total-items="customersPagination.totalItems"
        :page-size-options="customersPagination.pageSizeOptions"
      />
    </section>

    <AdminModal :show="showDetailModal" :title="detailTitle" max-width="980px" @close="closeDetailModal">
      <div v-if="detailContext" class="admin-reports-page admin-reports-page--modal">
      <template v-if="detailContext.type === 'sales'">
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
            <div class="admin-detail-summary">
              <div class="admin-detail-summary__row"><span>Subtotal</span><strong>{{ formatCurrency(detailContext.row.subtotal) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Envío</span><strong>{{ formatCurrency(detailContext.row.shipping) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Descuentos</span><strong>{{ formatCurrency(detailContext.row.discount) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Total</span><strong>{{ formatCurrency(detailContext.row.revenue) }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>

      <template v-else-if="detailContext.type === 'products'">
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
            <div class="admin-detail-summary">
              <div class="admin-detail-summary__row"><span>Cantidad total</span><strong>{{ detailContext.row.total_quantity }}</strong></div>
              <div class="admin-detail-summary__row"><span>Precio promedio</span><strong>{{ formatCurrency(detailContext.row.avg_price) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Ingresos</span><strong>{{ formatCurrency(detailContext.row.total_revenue) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Primera venta</span><strong>{{ formatDateTime(detailContext.row.first_order_at) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Última venta</span><strong>{{ formatDateTime(detailContext.row.last_order_at) }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>

      <template v-else-if="detailContext.type === 'customers'">
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
            <div class="admin-detail-summary">
              <div class="admin-detail-summary__row"><span>Órdenes</span><strong>{{ detailContext.row.orders_count }}</strong></div>
              <div class="admin-detail-summary__row"><span>Total gastado</span><strong>{{ formatCurrency(detailContext.row.total_spent) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Valor promedio</span><strong>{{ formatCurrency(detailContext.row.avg_order_value) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Primera compra</span><strong>{{ formatDateTime(detailContext.row.first_order) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Última compra</span><strong>{{ formatDateTime(detailContext.row.last_order) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Días desde la última compra</span><strong>{{ detailContext.row.customer_age_days ?? 'Sin dato' }}</strong></div>
            </div>
          </AdminCard>
        </div>
      </template>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// Script del componente de informes de administración.
// Delega la lógica reactiva en useAdminReports y conserva aquí la composición de la vista.
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

import { useAdminReports } from '../composables/useAdminReports'

import AdminCard from '../components/AdminCard.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

import '../views/AdminReportsPage.css'

const {
  activeFilterCount,       // Número de filtros actualmente activos en la sección avanzada
  activeReportRows,        // Filas de datos del reporte activo (para habilitar/deshabilitar exportación)
  activeSearchModel,       // Modelo reactivo del campo de búsqueda vinculado al filtro
  activeStats,             // Estadísticas resumidas del reporte activo (ventas, productos o clientes)
  activeTab,               // Identificador de la pestaña/sección actualmente seleccionada
  activeTabConfig,         // Configuración de la pestaña activa (título, nota informativa, etc.)
  breadcrumbs,             // Ruta de navegación para el encabezado de la página
  categoriesCanvas,        // Referencia al canvas donde se renderiza el gráfico de categorías
  closeDetailModal,        // Función para cerrar el modal de detalle
  customerDistributionCanvas, // Referencia al canvas del gráfico de distribución de clientes
  customersPagination,     // Estado de paginación de la tabla de clientes recurrentes
  detailContext,           // Contexto del registro seleccionado para mostrar en el modal de detalle
  detailTitle,             // Título dinámico del modal de detalle según el tipo de reporte
  exportReport,            // Función para exportar el reporte actual en formato Excel o PDF
  exportingFormat,         // Formato de exportación en progreso ('excel', 'pdf' o null)
  filteredCustomerRows,    // Filas de clientes filtradas según los criterios del reporte
  filteredProductsRows,    // Filas de productos filtradas según los criterios del reporte
  filters,                 // Objeto reactivo con los filtros de cada sección (ventas, productos, clientes)
  formatCurrency,          // Función auxiliar para formatear valores numéricos como moneda
  formatDateTime,          // Función auxiliar para formatear fechas y horas legibles
  formatPeriodLabel,       // Función para formatear etiquetas de período (día, semana, mes, año)
  goToTab,                 // Función para cambiar a una pestaña de reporte específica
  groupedSalesRows,        // Filas de ventas agrupadas por el período seleccionado
  loadCurrentReport,       // Función para cargar los datos del reporte según la pestaña y filtros activos
  loading,                 // Estado de carga general (true mientras se obtienen datos del servidor)
  monthlyComparisonCanvas, // Referencia al canvas del gráfico de comparativa mensual de ventas
  openDetailModal,         // Función para abrir el modal de detalle con el registro seleccionado
  productsPagination,      // Estado de paginación de la tabla de productos más vendidos
  quantityProductsCanvas,  // Referencia al canvas del gráfico de más vendidos por cantidad
  renderCharts,            // Función para re-renderizar los gráficas tras cambiar filtros o datos
  resetFilters,            // Función para restablecer todos los filtros a sus valores predeterminados
  resultsLabel,            // Texto con el conteo de resultados del reporte activo
  salesEvolutionCanvas,    // Referencia al canvas del gráfico de evolución de ventas
  salesPagination,         // Estado de paginación de la tabla de ventas por período
  searchPlaceholder,       // Placeholder dinámico del campo de búsqueda según la pestaña activa
  showDetailModal,         // Estado booleano que controla la visibilidad del modal de detalle
  tabs,                    // Arreglo de objetos con la configuración de las pestañas de reporte
  topCustomersCanvas,      // Referencia al canvas del gráfico de top clientes por valor
  topProductsCanvas,       // Referencia al canvas del gráfico de top productos por ingresos
} = useAdminReports()
</script>
