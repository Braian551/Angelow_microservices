<template>
  <div class="admin-orders-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      title="Gestión de Órdenes"
      subtitle="Administra pedidos, revisa el historial y actualiza estados con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Órdenes' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-sliders-h"
      title="Filtros de búsqueda"
      placeholder="Buscar por N° orden, cliente o email..."
      @search="applyFilters"
      @update:model-value="debouncedLoad"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--4">
          <div class="admin-filters__group">
            <label for="status-filter"><i class="fas fa-tag"></i> Estado de orden</label>
            <select id="status-filter" v-model="filters.status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option v-for="option in ADMIN_ORDER_FILTER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="payment-filter"><i class="fas fa-credit-card"></i> Estado de pago</label>
            <select id="payment-filter" v-model="filters.payment_status" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="paid">Pagado</option>
              <option value="verified">Verificado</option>
              <option value="pending_refund">Reembolso en proceso</option>
              <option value="failed">Fallido</option>
              <option value="refunded">Reembolsado</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="from-date"><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input id="from-date" v-model="filters.from_date" type="date" @change="validateDateRangeAndApply">
          </div>

          <div class="admin-filters__group">
            <label for="to-date"><i class="fas fa-calendar-check"></i> Fecha hasta</label>
            <input id="to-date" v-model="filters.to_date" type="date" @change="validateDateRangeAndApply">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} órdenes`">
      <template #actions>
        <div class="orders-results-actions">
          <div v-if="selectedOrdersCount > 0" class="orders-results-actions__selection">
            <i class="fas fa-check-double"></i>
            <span>{{ selectedOrdersCount }} seleccionada<span v-if="selectedOrdersCount !== 1">s</span></span>
          </div>
          <AdminExportActions
            tone="results"
            :disabled="orders.length === 0"
            :excel-loading="exportingFormat === 'excel'"
            :pdf-loading="exportingFormat === 'pdf'"
            @excel="exportOrders('excel')"
            @pdf="exportOrders('pdf')"
          />
          <button class="results-action-btn results-action-btn--primary" type="button" :disabled="selectedOrdersCount === 0" @click="openBulkActionsModal">
            <span class="results-action-btn__icon"><i class="fas fa-tasks"></i></span>
            <span>Acciones masivas</span>
          </button>
        </div>
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="orders.length === 0"
        icon="fas fa-inbox"
        title="Sin órdenes"
        description="No se encontraron órdenes con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table orders-table">
          <thead>
            <tr>
              <th class="selection-cell">
                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll($event.target.checked)">
              </th>
              <th>N° Orden</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in pagination.paginatedItems"
              :key="`${order.order_source}-${order.id}`"
              class="orders-table__row"
              tabindex="0"
              role="button"
              :aria-label="`Abrir detalle de la orden ${order.order_number || `#${order.id}`}`"
              @click="goToOrderDetail(order, $event)"
              @keydown.enter.prevent="goToOrderDetail(order)"
              @keydown.space.prevent="goToOrderDetail(order)"
            >
              <td class="selection-cell">
                <input type="checkbox" :checked="isOrderSelected(order)" @change="toggleOrderSelection(order, $event.target.checked)">
              </td>
              <td>
                <div class="order-number-cell">
                  <strong>{{ order.order_number || `#${order.id}` }}</strong>
                  <span>ID interno: {{ order.id }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ order.customer_name }}</strong>
                  <span>{{ order.customer_email || 'Sin email' }}</span>
                </div>
              </td>
              <td>{{ formatDate(order.created_at) }}</td>
              <td><strong>{{ formatCurrency(order.total) }}</strong></td>
              <td>
                <button type="button" class="status-chip-button" @click="openStatusModal(order)">
                  <span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span>
                </button>
              </td>
              <td>
                <span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Vista rápida" @click="openDetailModal(order)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Cambiar estado" @click="openStatusModal(order)">
                    <i class="fas fa-retweet"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Cambiar estado de pago" @click="openPaymentStatusModal(order)">
                    <i class="fas fa-credit-card"></i>
                  </button>
                  <button
                    class="action-btn edit action-btn--complete"
                    type="button"
                    :class="{ 'is-loading': isOrderActionLoading(order, 'complete') }"
                    :disabled="!canCompleteOrder(order) || Boolean(savingOrderActionKey)"
                    :title="canCompleteOrder(order) ? 'Completar orden' : 'Orden ya cerrada'"
                    @click="confirmCompleteOrder(order)"
                  >
                    <i :class="isOrderActionLoading(order, 'complete') ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                  </button>
                  <button
                    class="action-btn delete"
                    type="button"
                    :class="{ 'is-loading': isOrderActionLoading(order, 'deactivate') }"
                    :disabled="order.status === 'cancelled' || Boolean(savingOrderActionKey)"
                    :title="order.status === 'cancelled' ? 'Orden ya desactivada' : 'Desactivar orden'"
                    @click="confirmDeactivateOrder(order)"
                  >
                    <i :class="isOrderActionLoading(order, 'deactivate') ? 'fas fa-spinner fa-spin' : 'fas fa-power-off'"></i>
                  </button>
                  <RouterLink :to="buildOrderDetailRoute(order)" class="action-btn edit" title="Ir al detalle completo">
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

    <AdminModal :show="showDetailModal" :title="selectedOrder ? `Orden ${selectedOrder.order_number || `#${selectedOrder.id}`}` : 'Detalle de orden'" max-width="1120px" @close="closeDetailModal">
      <div class="admin-orders-page admin-orders-page--modal">
        <div v-if="detailLoading" class="detail-loading">
          <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
        </div>
        <template v-else-if="detailOrder">
        <div class="order-detail-grid">
          <div>
            <AdminCard title="Items del pedido" icon="fas fa-box" :flush="true">
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
                    <td>{{ formatCurrency((item.unit_price || item.price || 0) * item.quantity) }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>

            <AdminCard title="Historial" icon="fas fa-clock-rotate-left" style="margin-top: 1.2rem;" :flush="true">
              <div v-if="detailOrder.history.length === 0" class="detail-empty">Sin movimientos registrados.</div>
              <table v-else class="dashboard-table nested-table">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Cambio</th>
                    <th>Responsable</th>
                    <th>Detalle</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="entry in detailOrder.history" :key="entry.id">
                    <td>{{ formatDateTime(entry.created_at) }}</td>
                    <td>{{ translateHistoryValue(entry.old_value, entry.field_changed) }} -> {{ translateHistoryValue(entry.new_value, entry.field_changed) }}</td>
                    <td>{{ entry.changed_by_name || entry.changed_by || 'Sistema' }}</td>
                    <td>{{ entry.description || 'Sin descripción' }}</td>
                  </tr>
                </tbody>
              </table>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen" icon="fas fa-calculator">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Subtotal:</span><strong>{{ formatCurrency(detailOrder.order.subtotal || 0) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Envío:</span><strong>{{ formatCurrency(detailOrder.order.shipping_cost || 0) }}</strong></div>
                <div v-if="Number(detailOrder.order.discount_amount || 0) > 0" class="admin-detail-summary__row admin-detail-summary__row--success"><span>Descuento:</span><strong>-{{ formatCurrency(detailOrder.order.discount_amount || 0) }}</strong></div>
                <div class="admin-detail-summary__divider"></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--total"><span>Total:</span><strong>{{ formatCurrency(detailOrder.order.total || 0) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Cliente" icon="fas fa-user" style="margin-top: 1.2rem;">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Cliente:</span><strong>{{ detailOrder.customer_name || 'Sin nombre' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Email:</span><strong>{{ detailOrder.customer_email || 'Sin email' }}</strong></div>
                <div class="admin-detail-summary__row admin-detail-summary__row--stack"><span>Dirección:</span><strong>{{ detailOrder.order.shipping_address || detailOrder.order.billing_address || 'Sin dirección' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Pago:</span><strong>{{ paymentLabel(detailOrder.order.payment_status) }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
        </template>
      </div>
      <template #footer>
        <RouterLink v-if="selectedOrder" :to="buildOrderDetailRoute(selectedOrder)" class="btn btn-primary" @click="closeDetailModal">
          <i class="fas fa-arrow-right"></i> Ver detalle completo
        </RouterLink>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>

    <AdminModal :show="showStatusModal" title="Actualizar estado de la orden" max-width="560px" @close="closeStatusModal">
      <div class="admin-orders-page admin-orders-page--modal">
      <div class="status-form-grid">
        <div class="form-group status-form-grid__full">
          <label>Orden seleccionada</label>
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selección' }}</div>
        </div>

        <div class="form-group">
          <label for="order-status">
            Estado *
            <AdminInfoTooltip text="Nuevo estado de la orden. Ejemplo: «En proceso» al confirmar el pago o «Enviado» al despachar." />
          </label>
          <select id="order-status" v-model="statusForm.status" class="form-control" @change="validateStatusField('status')">
            <option v-for="option in ADMIN_EDITABLE_ORDER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="statusErrors.status" class="form-error">{{ statusErrors.status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="status-description">
            Descripción del cambio
            <AdminInfoTooltip text="Razón interna del cambio de estado. Queda registrada en el historial de la orden. (opcional)" />
          </label>
          <textarea id="status-description" v-model="statusForm.description" class="form-control" rows="4" :class="{ 'is-invalid': statusErrors.description }" @input="validateStatusField('description')"></textarea>
          <p v-if="statusErrors.description" class="form-error">{{ statusErrors.description }}</p>
        </div>
      </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="savingStatusChange" @click="closeStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': savingStatusChange }" :disabled="savingStatusChange" @click="submitStatusChange">
          <i :class="savingStatusChange ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ savingStatusChange ? 'Guardando...' : 'Guardar cambio' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showPaymentStatusModal" title="Actualizar estado de pago" max-width="560px" @close="closePaymentStatusModal">
      <div class="admin-orders-page admin-orders-page--modal">
      <div class="status-form-grid">
        <div class="form-group status-form-grid__full">
          <label>Orden seleccionada</label>
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selección' }}</div>
        </div>

        <div class="form-group">
          <label for="payment-status">
            Estado de pago *
            <AdminInfoTooltip text="Estado actual del pago. Cambia a «Pagado» cuando el pago es confirmado, a «Verificado» una vez revisado el comprobante." />
          </label>
          <select id="payment-status" v-model="paymentForm.payment_status" class="form-control" @change="validatePaymentField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="pending_refund">Reembolso en proceso</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="paymentErrors.payment_status" class="form-error">{{ paymentErrors.payment_status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="payment-description">
            Descripción del cambio
            <AdminInfoTooltip text="Nota interna sobre el cambio de estado de pago. Queda registrada en el historial. (opcional)" />
          </label>
          <textarea id="payment-description" v-model="paymentForm.description" class="form-control" rows="4" :class="{ 'is-invalid': paymentErrors.description }" @input="validatePaymentField('description')"></textarea>
          <p v-if="paymentErrors.description" class="form-error">{{ paymentErrors.description }}</p>
        </div>
      </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="savingPaymentStatusChange" @click="closePaymentStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': savingPaymentStatusChange }" :disabled="savingPaymentStatusChange" @click="submitPaymentStatusChange">
          <i :class="savingPaymentStatusChange ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ savingPaymentStatusChange ? 'Guardando...' : 'Guardar cambio' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showBulkModal" title="Acciones masivas" max-width="560px" @close="closeBulkModal">
      <div class="admin-orders-page admin-orders-page--modal">
      <div class="bulk-form-grid">
        <div class="bulk-modal-summary">
          <div class="bulk-modal-summary__count">
            <strong>{{ selectedOrdersCount }}</strong>
            <span>{{ selectedOrdersCount === 1 ? 'orden seleccionada' : 'órdenes seleccionadas' }}</span>
          </div>
          <div class="bulk-modal-summary__chips">
            <span v-for="order in selectedOrdersPreview" :key="order.id" class="bulk-modal-summary__chip">{{ order.order_number }}</span>
            <span v-if="selectedOrdersCount > selectedOrdersPreview.length" class="bulk-modal-summary__chip bulk-modal-summary__chip--muted">+{{ selectedOrdersCount - selectedOrdersPreview.length }} más</span>
          </div>
          <p class="bulk-modal-summary__helper">La acción elegida se confirmará antes de aplicarse y luego verás un mensaje con el resultado.</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label>Órdenes seleccionadas</label>
          <div class="status-preview">{{ selectedOrdersCount }} seleccionada(s)</div>
        </div>

        <div class="form-group">
          <label for="bulk-action">
            Acción *
            <AdminInfoTooltip text="Operación a aplicar en bloque sobre todas las órdenes seleccionadas." />
          </label>
          <select id="bulk-action" v-model="bulkForm.action" class="form-control" @change="validateBulkField('action')">
            <option value="">Seleccionar acción</option>
            <option value="change_status">Cambiar estado</option>
            <option value="change_payment_status">Cambiar estado de pago</option>
            <option value="deactivate">Desactivar</option>
          </select>
          <p v-if="bulkErrors.action" class="form-error">{{ bulkErrors.action }}</p>
        </div>

        <div v-if="bulkForm.action === 'change_status'" class="form-group">
          <label for="bulk-status">
            Estado *
            <AdminInfoTooltip text="Estado que se aplicará a todas las órdenes seleccionadas." />
          </label>
          <select id="bulk-status" v-model="bulkForm.status" class="form-control" @change="validateBulkField('status')">
            <option v-for="option in ADMIN_EDITABLE_ORDER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="bulkErrors.status" class="form-error">{{ bulkErrors.status }}</p>
        </div>

        <div v-if="bulkForm.action === 'change_payment_status'" class="form-group">
          <label for="bulk-payment-status">
            Estado de pago *
            <AdminInfoTooltip text="Estado de pago que se aplicará a todas las órdenes seleccionadas." />
          </label>
          <select id="bulk-payment-status" v-model="bulkForm.payment_status" class="form-control" @change="validateBulkField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="pending_refund">Reembolso en proceso</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="bulkErrors.payment_status" class="form-error">{{ bulkErrors.payment_status }}</p>
        </div>

        <div class="form-group status-form-grid__full">
          <label for="bulk-description">
            Descripción del cambio
            <AdminInfoTooltip text="Nota sobre el cambio masivo. Se registra en el historial de cada orden afectada. (opcional)" />
          </label>
          <textarea id="bulk-description" v-model="bulkForm.description" class="form-control" rows="4" :class="{ 'is-invalid': bulkErrors.description }" @input="validateBulkField('description')"></textarea>
          <p v-if="bulkErrors.description" class="form-error">{{ bulkErrors.description }}</p>
        </div>
      </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="bulkSaving" @click="closeBulkModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': bulkSaving }" :disabled="bulkSaving" @click="submitBulkAction">
          <i :class="bulkSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ bulkSaving ? 'Aplicando...' : 'Aplicar cambios' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import {
  ADMIN_EDITABLE_ORDER_STATUSES,
  ADMIN_ORDER_FILTER_STATUSES,
  translateHistoryValue,
} from '../utils/orderPresentation'
import { useAdminOrders } from '../composables/useAdminOrders'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import '../views/AdminOrdersPage.css'

const {
  activeFilterCount,
  allSelected,
  applyFilters,
  buildOrderDetailRoute,
  bulkErrors,
  bulkForm,
  bulkSaving,
  clearAllFilters,
  closeBulkModal,
  closeDetailModal,
  closePaymentStatusModal,
  closeStatusModal,
  canCompleteOrder,
  confirmCompleteOrder,
  confirmDeactivateOrder,
  debouncedLoad,
  detailLoading,
  detailOrder,
  exportingFormat,
  exportOrders,
  filters,
  formatCurrency,
  formatDate,
  formatDateTime,
  goToOrderDetail,
  isOrderActionLoading,
  isOrderSelected,
  loading,
  openBulkActionsModal,
  openDetailModal,
  openPaymentStatusModal,
  openStatusModal,
  orders,
  pagination,
  paymentBadgeClass,
  paymentErrors,
  paymentForm,
  paymentLabel,
  savingOrderActionKey,
  savingPaymentStatusChange,
  savingStatusChange,
  selectedOrder,
  selectedOrdersCount,
  selectedOrdersPreview,
  showBulkModal,
  showDetailModal,
  showPaymentStatusModal,
  showStatusModal,
  stats,
  statusBadgeClass,
  statusErrors,
  statusForm,
  statusLabel,
  submitBulkAction,
  submitPaymentStatusChange,
  submitStatusChange,
  toggleOrderSelection,
  toggleSelectAll,
  validateBulkField,
  validateDateRangeAndApply,
  validatePaymentField,
  validateStatusField,
} = useAdminOrders()
</script>

