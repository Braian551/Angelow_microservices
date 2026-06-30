<template>
  <!--
    Componente: AdminCustomersPage.vue
    Propósito: Página administrativa para gestionar clientes del sistema.
    Funcionalidades principales:
    - Listado de clientes con filtros avanzados (búsqueda, estado, segmento)
    - Estadísticas generales del hub de clientes
    - Tabla de clientes con información de contacto, pedidos y valor acumulado
    - Modal de detalle del cliente con perfil, pedidos recientes y resumen comercial
    - Exportación de datos a Excel y PDF
    - Paginación de resultados
    Composable utilizado: useAdminCustomers
  -->
  <div class="admin-customers-page">
    <AdminPageHeader
      icon="fas fa-users"
      title="Clientes"
      subtitle="Consulta perfil, pedidos y estado del cliente con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Clientes' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="hubStatsFormatted" />

    <!-- Filtros de búsqueda y segmentación -->
    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Búsqueda y segmentación"
      placeholder="Buscar por nombre, email o teléfono..."
      @update:model-value="debouncedLoadCustomers"
      @search="loadCustomers()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="customer-state"><i class="fas fa-user-check"></i> Estado</label>
            <select id="customer-state" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="blocked">Bloqueados</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="customer-segment"><i class="fas fa-layer-group"></i> Segmento</label>
            <select id="customer-segment" v-model="filters.segment">
              <option value="all">Todos</option>
              <option value="repeat">Recurrentes</option>
              <option value="new">Nuevos 30 días</option>
              <option value="without-orders">Sin pedidos</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <button type="button" class="admin-filters__clear" @click="clearAllFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar todo
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} clientes`">
      <template #actions>
        <AdminExportActions
          tone="results"
          :disabled="customers.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportCustomers('excel')"
          @pdf="exportCustomers('pdf')"
        />
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="customers.length === 0"
        icon="fas fa-users"
        title="Sin clientes"
        description="No se encontraron clientes con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table customers-table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Contacto</th>
              <th>Registro</th>
              <th>Pedidos</th>
              <th>Valor acumulado</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in pagination.paginatedItems" :key="customer.id">
              <td>
                <div class="customer-cell">
                  <img class="admin-avatar" :src="avatarUrl(customer)" :alt="customer.name" @error="onAvatarError($event, customer.image)">
                  <div class="admin-entity-name">
                    <strong>{{ customer.name }}</strong>
                    <span>{{ customer.email }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ customer.phone || 'Sin teléfono' }}</strong>
                  <span>Último acceso: {{ formatDateTime(customer.last_access) }}</span>
                </div>
              </td>
              <td>{{ formatDate(customer.created_at) }}</td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ customer.orders_count }}</strong>
                  <span>{{ customer.last_order_date ? `Último pedido: ${formatDate(customer.last_order_date)}` : 'Sin pedidos' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(customer.total_spent) }}</strong></td>
              <td>
                <span class="status-badge" :class="customer.is_blocked ? 'cancelled' : 'active'">
                  {{ customer.is_blocked ? 'Bloqueado' : 'Activo' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver cliente" @click="openCustomerModal(customer)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button
                    class="action-btn"
                    :class="customer.is_blocked ? 'edit' : 'delete'"
                    type="button"
                    :title="customer.is_blocked ? 'Desbloquear cliente' : 'Bloquear cliente'"
                    @click="toggleCustomerBlock(customer)"
                  >
                    <i :class="customer.is_blocked ? 'fas fa-unlock' : 'fas fa-ban'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal :show="showDetailModal" :title="selectedCustomer ? selectedCustomer.name : 'Detalle de cliente'" max-width="1080px" @close="closeCustomerModal">
      <div v-if="selectedCustomer" class="admin-customers-page admin-customers-page--modal">
        <div class="customer-detail-grid">
          <div>
            <AdminCard title="Perfil del cliente" icon="fas fa-id-card">
              <div class="customer-profile">
                <img class="admin-avatar admin-avatar--lg" :src="avatarUrl(selectedCustomer)" :alt="selectedCustomer.name" @error="onAvatarError($event, selectedCustomer.image)">
                <div class="customer-profile__body">
                  <h3>{{ selectedCustomer.name }}</h3>
                  <p>{{ selectedCustomer.email }}</p>
                  <span class="status-badge" :class="selectedCustomer.is_blocked ? 'cancelled' : 'active'">
                    {{ selectedCustomer.is_blocked ? 'Bloqueado' : 'Activo' }}
                  </span>
                </div>
              </div>

              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Teléfono</span><strong>{{ selectedCustomer.phone || 'Sin teléfono' }}</strong></div>
                <div class="admin-detail-row"><span>Registro</span><strong>{{ formatDate(selectedCustomer.created_at) }}</strong></div>
                <div class="admin-detail-row"><span>Último acceso</span><strong>{{ formatDateTime(selectedCustomer.last_access) }}</strong></div>
                <div class="admin-detail-row"><span>Último pedido</span><strong>{{ selectedCustomer.last_order_date ? formatDateTime(selectedCustomer.last_order_date) : 'Sin pedidos' }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Pedidos recientes" icon="fas fa-box" style="margin-top: 1.2rem;" :flush="true">
              <div v-if="selectedCustomer.recent_orders.length === 0" class="detail-empty">Este cliente aún no registra pedidos.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Orden</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Pago</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in selectedCustomer.recent_orders" :key="order.id">
                    <td>{{ order.order_number }}</td>
                    <td>{{ formatDate(order.created_at) }}</td>
                    <td>{{ formatCurrency(order.total) }}</td>
                    <td><span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                    <td><span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span></td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen comercial" icon="fas fa-chart-line">
              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Pedidos totales</span><strong>{{ selectedCustomer.orders_count }}</strong></div>
                <div class="admin-detail-row"><span>Pedidos completados</span><strong>{{ selectedCustomer.completed_orders }}</strong></div>
                <div class="admin-detail-row"><span>Pedidos pendientes</span><strong>{{ selectedCustomer.pending_orders }}</strong></div>
                <div class="admin-detail-divider"></div>
                <div class="admin-detail-row admin-detail-row--total"><span>Valor acumulado</span><strong>{{ formatCurrency(selectedCustomer.total_spent) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Segmentación" icon="fas fa-bullseye" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-row"><span>Tipo</span><strong>{{ customerSegmentLabel(selectedCustomer) }}</strong></div>
                <div class="admin-detail-row"><span>Recompra</span><strong>{{ selectedCustomer.orders_count > 1 ? 'Sí' : 'No' }}</strong></div>
                <div class="admin-detail-row"><span>Ticket promedio</span><strong>{{ formatCurrency(selectedCustomer.average_ticket) }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </div>

      <template #footer>
        <button
          v-if="selectedCustomer"
          class="btn"
          :class="selectedCustomer.is_blocked ? 'btn-primary' : 'btn-danger'"
          type="button"
          @click="toggleCustomerBlock(selectedCustomer)"
        >
          <i :class="selectedCustomer.is_blocked ? 'fas fa-unlock' : 'fas fa-ban'"></i>
          {{ selectedCustomer.is_blocked ? 'Desbloquear' : 'Bloquear' }} cliente
        </button>
        <button class="btn btn-secondary" type="button" @click="closeCustomerModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// Script del componente AdminCustomersPage.vue.
// Importa utilidades, componentes y el composable que concentra la lógica de clientes.
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminCustomers } from '../composables/useAdminCustomers'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import '../views/AdminCustomersPage.css'

const {
  activeFilterCount, // Número de filtros activos actualmente aplicados
  clearAllFilters, // Función para limpiar todos los filtros de búsqueda
  closeCustomerModal, // Función para cerrar el modal de detalle del cliente
  customerSegmentLabel, // Función para obtener la etiqueta del segmento del cliente
  customers, // Lista completa de clientes cargados desde el servidor
  debouncedLoadCustomers, // Función con debounce para cargar clientes sin saturar el servidor
  exportCustomers, // Función para exportar la lista de clientes a Excel o PDF
  exportingFormat, // Formato de exportación en curso ('excel', 'pdf' o null)
  filters, // Objeto reactivo con los filtros de búsqueda y segmentación
  formatCurrency, // Función para formatear valores monetarios
  formatDate, // Función para formatear fechas
  formatDateTime, // Función para formatear fechas con hora
  hubStatsFormatted, // Estadísticas formateadas del hub para mostrar en el grid
  loadCustomers, // Función para cargar la lista de clientes desde la API
  loading, // Indicador de carga en curso (true/false)
  openCustomerModal, // Función para abrir el modal de detalle de un cliente específico
  pagination, // Objeto con propiedades de paginación (página actual, total, etc.)
  paymentBadgeClass, // Función para obtener la clase CSS del badge de estado de pago
  paymentLabel, // Función para obtener la etiqueta del estado de pago
  selectedCustomer, // Cliente seleccionado actualmente para ver en el modal de detalle
  showDetailModal, // Indicador de visibilidad del modal de detalle (true/false)
  statusBadgeClass, // Función para obtener la clase CSS del badge de estado del pedido
  statusLabel, // Función para obtener la etiqueta del estado del pedido
  toggleCustomerBlock, // Función para bloquear o desbloquear un cliente
} = useAdminCustomers()

function avatarUrl(customer) {
  return resolveMediaUrl(customer.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
}
</script>
