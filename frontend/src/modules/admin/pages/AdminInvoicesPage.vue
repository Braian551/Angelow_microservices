<template>
  <div class="admin-invoices-page">
    <AdminPageHeader
      icon="fas fa-file-invoice-dollar"
      title="Facturas automáticas"
      subtitle="Revisa facturas emitidas por entrega y pago verificado, descarga el PDF y reenvía al correo del cliente."
      :breadcrumbs="[{ label: 'Facturas' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="5" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Filtros de facturación"
      placeholder="Buscar por factura, orden, cliente o correo..."
      @search="applyFilters"
      @update:model-value="debouncedLoad"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--5">
          <div class="admin-filters__group">
            <label for="invoice-source-filter"><i class="fas fa-database"></i> Origen</label>
            <select id="invoice-source-filter" v-model="filters.source" @change="applyFilters">
              <option value="">Todas</option>
              <option value="microservice">Principal</option>
              <option value="legacy">Respaldo</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-status-filter"><i class="fas fa-tag"></i> Estado de orden</label>
            <select id="invoice-status-filter" v-model="filters.status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="processing">En proceso</option>
              <option value="shipped">Enviado</option>
              <option value="delivered">Entregado</option>
              <option value="completed">Completado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-payment-filter"><i class="fas fa-credit-card"></i> Estado de pago</label>
            <select id="invoice-payment-filter" v-model="filters.payment_status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="paid">Pagado</option>
              <option value="verified">Verificado</option>
              <option value="approved">Aprobado</option>
              <option value="failed">Fallido</option>
              <option value="rejected">Rechazado</option>
              <option value="refunded">Reembolsado</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="invoice-from-date"><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input id="invoice-from-date" v-model="filters.from_date" type="date" @change="validateDateRangeAndApply">
          </div>

          <div class="admin-filters__group">
            <label for="invoice-to-date"><i class="fas fa-calendar-check"></i> Fecha hasta</label>
            <input id="invoice-to-date" v-model="filters.to_date" type="date" @change="validateDateRangeAndApply">
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i> Limpiar todo
            </button>
            <button type="button" class="admin-filters__apply" @click="applyFilters">
              <i class="fas fa-check-circle"></i> Aplicar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} facturas`">
      <template #actions>
        <div class="invoices-results-actions">
          <button class="results-action-btn results-action-btn--neutral" type="button" @click="loadInvoices">
            <span class="results-action-btn__icon"><i class="fas fa-rotate"></i></span>
            <span>Actualizar</span>
          </button>
        </div>
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="invoices.length === 0"
        icon="fas fa-file-invoice"
        title="Sin facturas"
        description="No encontramos facturas con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table invoices-table">
          <thead>
            <tr>
              <th>N° Factura</th>
              <th>N° Orden</th>
              <th>Cliente</th>
              <th>Emisión</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in pagination.paginatedItems" :key="`${invoice.order_source}-${invoice.id}`">
              <td>
                <div class="invoice-number-cell">
                  <strong>{{ invoice.invoice_number }}</strong>
                  <span>Pedido {{ invoice.order_number || `#${invoice.id}` }}</span>
                </div>
              </td>
              <td>
                <div class="invoice-order-cell">
                  <strong>{{ invoice.order_number || `#${invoice.id}` }}</strong>
                  <span>ID interno: {{ invoice.id }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ invoice.customer_name }}</strong>
                  <span>{{ invoice.customer_email || 'Sin correo' }}</span>
                </div>
              </td>
              <td>{{ formatDateTime(invoice.invoice_date || invoice.created_at) }}</td>
              <td><strong>{{ formatCurrency(invoice.total) }}</strong></td>
              <td>
                <span class="status-badge" :class="statusBadgeClass(invoice.status)">{{ statusLabel(invoice.status) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="paymentBadgeClass(invoice.payment_status)">{{ paymentLabel(invoice.payment_status) }}</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Vista rápida" @click="openDetailModal(invoice)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Descargar PDF" @click="downloadInvoice(invoice)">
                    <i class="fas fa-file-pdf"></i>
                  </button>
                  <button class="action-btn edit" type="button" :disabled="isResending(invoice)" title="Reenviar por correo" @click="confirmResendInvoice(invoice)">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                  <RouterLink :to="buildOrderDetailRoute(invoice)" class="action-btn edit" title="Ver detalle completo de orden">
                    <i class="fas fa-arrow-right"></i>
                  </RouterLink>
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

    <AdminModal :show="showDetailModal" :title="selectedInvoice ? `Factura ${selectedInvoice.invoice_number}` : 'Detalle de factura'" max-width="1080px" @close="closeDetailModal">
      <div class="admin-invoices-page admin-invoices-page--modal">
        <div v-if="detailLoading" class="detail-loading">
          <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
        </div>
        <div v-else-if="detailOrder" class="order-detail-grid">
          <div>
            <AdminCard title="Items facturados" icon="fas fa-box" :flush="true">
              <div v-if="detailOrder.items.length === 0" class="detail-empty">Sin items registrados.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in detailOrder.items" :key="item.id">
                    <td>{{ item.product_name || item.name || 'Producto' }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ formatCurrency(item.unit_price || item.price || 0) }}</td>
                    <td>{{ formatCurrency((item.unit_price || item.price || 0) * Number(item.quantity || 0)) }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen factura" icon="fas fa-calculator">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Factura:</span><strong>{{ selectedInvoice?.invoice_number || '-' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Subtotal:</span><strong>{{ formatCurrency(detailOrder.order.subtotal || 0) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Envío:</span><strong>{{ formatCurrency(detailOrder.order.shipping_cost || 0) }}</strong></div>
                <div v-if="Number(detailOrder.order.discount_amount || 0) > 0" class="admin-detail-summary__row admin-detail-summary__row--success"><span>Descuento:</span><strong>-{{ formatCurrency(detailOrder.order.discount_amount || 0) }}</strong></div>
                <div class="admin-detail-summary__divider"></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--total"><span>Total:</span><strong>{{ formatCurrency(detailOrder.order.total || 0) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Cliente y envío" icon="fas fa-user" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Cliente:</span><strong>{{ detailOrder.customer_name || 'Sin nombre' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Email:</span><strong>{{ detailOrder.customer_email || 'Sin correo' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Pago:</span><strong>{{ paymentLabel(detailOrder.order.payment_status) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Método:</span><strong>{{ paymentMethodLabel(detailOrder.order.payment_method) }}</strong></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--stack"><span>Dirección:</span><strong>{{ detailOrder.order.shipping_address || detailOrder.order.billing_address || 'Sin dirección' }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </div>
      <template #footer>
        <button v-if="selectedInvoice" class="btn btn-primary" type="button" @click="downloadInvoice(selectedInvoice)">
          <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import { useAdminInvoices } from '../composables/useAdminInvoices'
import '../views/AdminInvoicesPage.css'

// =====================================================
// Orquestación de la vista
// =====================================================
const {
  activeFilterCount,
  applyFilters,
  buildOrderDetailRoute,
  clearAllFilters,
  closeDetailModal,
  debouncedLoad,
  detailLoading,
  detailOrder,
  downloadInvoice,
  filters,
  formatCurrency,
  formatDateTime,
  invoices,
  isResending,
  loadInvoices,
  loading,
  openDetailModal,
  pagination,
  paymentBadgeClass,
  paymentLabel,
  paymentMethodLabel,
  confirmResendInvoice,
  selectedInvoice,
  showDetailModal,
  stats,
  statusBadgeClass,
  statusLabel,
  validateDateRangeAndApply,
} = useAdminInvoices()
</script>
