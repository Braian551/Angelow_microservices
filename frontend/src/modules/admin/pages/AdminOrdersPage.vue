<!--
  AdminOrdersPage.vue
  ===================
  Página principal de gestión de órdenes dentro del módulo administrativo.

  Propósito:
  - Listar todas las órdenes del sistema con paginación, filtros avanzados
    (estado de orden, estado de pago, rango de fechas) y búsqueda por texto.
  - Permitir al administrador ver un resumen rápido (stats), exportar datos
    a Excel / PDF, aplicar acciones masivas sobre múltiples órdenes y
    gestionar el estado individual de cada orden (estado, pago, completar,
    desactivar).
  - Mostrar un modal de vista previa con items, historial, resumen y datos
    del cliente sin salir de la lista.

  Funcionalidades principales:
  • Tabla paginada con selección múltiple (checkbox).
  • Filtros de búsqueda: número de orden, cliente, email, estado, pago y fechas.
  • Exportación a Excel y PDF.
  • Modal de detalle rápido (vista previa de orden).
  • Modal de cambio de estado de orden.
  • Modal de cambio de estado de pago.
  • Modal de acciones masivas (cambiar estado, pago o desactivar en lote).
  • Acciones de completar y desactivar orden con confirmación.

  Composable utilizado: useAdminOrders() — centraliza toda la lógica de estado,
  filtros, paginación, modales, formularios, validaciones y llamadas a la API.
-->
<template>
  <div class="admin-orders-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      title="Gesti&oacute;n de &Oacute;rdenes"
      subtitle="Administra pedidos, revisa el historial y actualiza estados con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: '&Oacute;rdenes' }]"
    />

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-sliders-h"
      title="Filtros de búsqueda"
      placeholder="Buscar por N.° orden, cliente o email..."
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
              <th>N.° Orden</th>
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
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selecci&oacute;n' }}</div>
        </div>
        <div class="form-group">
          <label for="order-status">
            Estado *
            <AdminInfoTooltip text="Nuevo estado de la orden. Ejemplo: &laquo;En proceso&raquo; al confirmar el pago o &laquo;Enviado&raquo; al despachar." />
          </label>
          <select id="order-status" v-model="statusForm.status" class="form-control" @change="validateStatusField('status')">
            <option v-for="option in ADMIN_EDITABLE_ORDER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="statusErrors.status" class="form-error">{{ statusErrors.status }}</p>
        </div>
        <div class="form-group status-form-grid__full">
          <label for="status-description">
            Descripci&oacute;n del cambio
            <AdminInfoTooltip text="Raz&oacute;n interna del cambio de estado. Queda registrada en el historial de la orden. (opcional)" />
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
          <div class="status-preview">{{ selectedOrder ? `${selectedOrder.order_number || `#${selectedOrder.id}`} | ${selectedOrder.customer_name}` : 'Sin selecci&oacute;n' }}</div>
        </div>
        <div class="form-group">
          <label for="payment-status">
            Estado de pago *
            <AdminInfoTooltip text="Estado actual del pago. Cambia a &laquo;Pagado&raquo; cuando el pago es confirmado, a &laquo;Verificado&raquo; una vez revisado el comprobante." />
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
            Descripci&oacute;n del cambio
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
            <span>{{ selectedOrdersCount === 1 ? 'orden seleccionada' : '&oacute;rdenes seleccionadas' }}</span>
          </div>
          <div class="bulk-modal-summary__chips">
            <span v-for="order in selectedOrdersPreview" :key="order.id" class="bulk-modal-summary__chip">{{ order.order_number }}</span>
            <span v-if="selectedOrdersCount > selectedOrdersPreview.length" class="bulk-modal-summary__chip bulk-modal-summary__chip--muted">+{{ selectedOrdersCount - selectedOrdersPreview.length }} m&aacute;s</span>
          </div>
          <p class="bulk-modal-summary__helper">La acci&oacute;n elegida se confirmar&aacute; antes de aplicarse y luego ver&aacute;s un mensaje con el resultado.</p>
        </div>
        <div class="form-group status-form-grid__full">
          <label>&Oacute;rdenes seleccionadas</label>
          <div class="status-preview">{{ selectedOrdersCount }} seleccionada(s)</div>
        </div>
        <div class="form-group">
          <label for="bulk-action">
            Acci&oacute;n *
            <AdminInfoTooltip text="Operaci&oacute;n a aplicar en bloque sobre todas las &oacute;rdenes seleccionadas." />
          </label>
          <select id="bulk-action" v-model="bulkForm.action" class="form-control" @change="validateBulkField('action')">
            <option value="">Seleccionar acci&oacute;n</option>
            <option value="change_status">Cambiar estado</option>
            <option value="change_payment_status">Cambiar estado de pago</option>
            <option value="deactivate">Desactivar</option>
          </select>
          <p v-if="bulkErrors.action" class="form-error">{{ bulkErrors.action }}</p>
        </div>
        <div v-if="bulkForm.action === 'change_status'" class="form-group">
          <label for="bulk-status">
            Estado *
            <AdminInfoTooltip text="Estado que se aplicar&aacute; a todas las &oacute;rdenes seleccionadas." />
          </label>
          <select id="bulk-status" v-model="bulkForm.status" class="form-control" @change="validateBulkField('status')">
            <option v-for="option in ADMIN_EDITABLE_ORDER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="bulkErrors.status" class="form-error">{{ bulkErrors.status }}</p>
        </div>
        <div v-if="bulkForm.action === 'change_payment_status'" class="form-group">
          <label for="bulk-payment-status">
            Estado de pago *
            <AdminInfoTooltip text="Estado de pago que se aplicar&aacute; a todas las &oacute;rdenes seleccionadas." />
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
            Descripci&oacute;n del cambio
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

<!--
  Sección <script setup>
  ======================
  Responsabilidades de este bloque:
  - Importar las dependencias necesarias: enrutador, constantes de presentación
    de órdenes, el composable useAdminOrders y todos los componentes compartidos
    del panel administrativo.
  - Consumir el composable useAdminOrders() para obtener el estado reactivo
    completo (filtros, paginación, órdenes, modales, formularios, validaciones,
    funciones de formato y handlers de eventos).
  - No contiene lógica adicional; toda la lógica de negocio reside en el
    composable. Solo se realizan las importaciones y la desestructuración.
-->
<script setup>
// ── Imports de vue-router ──────────────────────────────────────────
import { RouterLink } from 'vue-router'

// ── Imports de utilidades y constantes de presentación ─────────────
import {
  ADMIN_EDITABLE_ORDER_STATUSES,
  ADMIN_ORDER_FILTER_STATUSES,
  translateHistoryValue,
} from '../utils/orderPresentation'

// ── Imports del composable de órdenes ──────────────────────────────
import { useAdminOrders } from '../composables/useAdminOrders'

// ── Imports de componentes compartidos del panel administrativo ─────
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

// ── Imports de estilos ─────────────────────────────────────────────
import '../views/AdminOrdersPage.css'

// ── Desestructuración del composable useAdminOrders ────────────────
// Todas las propiedades y funciones que controlan la página de órdenes.
const {
  activeFilterCount,          // Número de filtros actualmente activos
  allSelected,                // Boolean: true si todas las órdenes visibles están seleccionadas
  applyFilters,               // Función que aplica los filtros activos y recarga la lista
  buildOrderDetailRoute,      // Función que genera la ruta al detalle completo de una orden
  bulkErrors,                 // Objeto con errores de validación del formulario de acciones masivas
  bulkForm,                   // Objeto reactivo del formulario de acciones masivas
  bulkSaving,                 // Boolean: true mientras se está procesando una acción masiva
  clearAllFilters,            // Función que limpia todos los filtros y recarga la lista
  closeBulkModal,             // Función que cierra el modal de acciones masivas
  closeDetailModal,           // Función que cierra el modal de vista previa de orden
  closePaymentStatusModal,    // Función que cierra el modal de cambio de estado de pago
  closeStatusModal,           // Función que cierra el modal de cambio de estado de orden
  canCompleteOrder,           // Función que verifica si una orden puede marcarse como completada
  confirmCompleteOrder,       // Función que muestra confirmación antes de completar una orden
  confirmDeactivateOrder,     // Función que muestra confirmación antes de desactivar una orden
  debouncedLoad,              // Función con debounce que recarga órdenes al cambiar el texto de búsqueda
  detailLoading,              // Boolean: true mientras se cargan los datos del detalle en el modal
  detailOrder,                // Objeto con los datos completos de la orden seleccionada (items, historial, etc.)
  exportingFormat,            // String con el formato de exportación en curso ('excel', 'pdf') o null
  exportOrders,               // Función que exporta las órdenes filtradas a Excel o PDF
  filters,                    // Objeto reactivo que contiene los valores de todos los filtros de búsqueda
  formatCurrency,             // Función que formatea un número como moneda local (ej: $1.234,56)
  formatDate,                 // Función que formatea una fecha ISO a formato legible (dd/mm/aaaa)
  formatDateTime,             // Función que formatea una fecha ISO con hora (dd/mm/aaaa hh:mm)
  goToOrderDetail,            // Función que navega al detalle completo de una orden
  isOrderActionLoading,       // Función que verifica si una acción específica está en curso para una orden
  isOrderSelected,            // Función que verifica si una orden está en la lista de seleccionadas
  loading,                    // Boolean: true mientras se cargan las órdenes desde la API
  openBulkActionsModal,       // Función que abre el modal de acciones masivas
  openDetailModal,            // Función que abre el modal de vista previa con el detalle de una orden
  openPaymentStatusModal,     // Función que abre el modal de cambio de estado de pago
  openStatusModal,            // Función que abre el modal de cambio de estado de orden
  orders,                     // Array con todas las órdenes cargadas de la API (antes de paginación)
  pagination,                 // Objeto reactivo de paginación (página actual, total, items por página, etc.)
  paymentBadgeClass,          // Función que retorna la clase CSS del badge según el estado de pago
  paymentErrors,              // Objeto con errores de validación del formulario de estado de pago
  paymentForm,                // Objeto reactivo del formulario de cambio de estado de pago
  paymentLabel,               // Función que retorna la etiqueta legible de un estado de pago
  savingOrderActionKey,       // String con la clave de la acción en curso para bloquear otras acciones
  savingPaymentStatusChange,  // Boolean: true mientras se guarda un cambio de estado de pago
  savingStatusChange,         // Boolean: true mientras se guarda un cambio de estado de orden
  selectedOrder,              // Objeto de la orden actualmente seleccionada para ver/editar
  selectedOrdersCount,        // Número total de órdenes seleccionadas por el usuario
  selectedOrdersPreview,      // Array con las primeras órdenes seleccionadas (para vista previa en el modal)
  showBulkModal,              // Boolean: controla la visibilidad del modal de acciones masivas
  showDetailModal,            // Boolean: controla la visibilidad del modal de vista previa
  showPaymentStatusModal,     // Boolean: controla la visibilidad del modal de cambio de pago
  showStatusModal,            // Boolean: controla la visibilidad del modal de cambio de estado
  stats,                      // Array con las estadísticas resumidas (total órdenes, ingresos, etc.)
  statusBadgeClass,           // Función que retorna la clase CSS del badge según el estado de la orden
  statusErrors,               // Objeto con errores de validación del formulario de estado de orden
  statusForm,                 // Objeto reactivo del formulario de cambio de estado de orden
  statusLabel,                // Función que retorna la etiqueta legible de un estado de orden
  submitBulkAction,           // Función que envía la acción masiva seleccionada a la API
  submitPaymentStatusChange,  // Función que envía el cambio de estado de pago a la API
  submitStatusChange,         // Función que envía el cambio de estado de orden a la API
  toggleOrderSelection,       // Función que alterna la selección de una orden individual
  toggleSelectAll,            // Función que selecciona o deselecciona todas las órdenes visibles
  validateBulkField,          // Función que valida un campo específico del formulario de acciones masivas
  validateDateRangeAndApply,  // Función que valida que la fecha desde sea menor que la fecha hasta
  validatePaymentField,       // Función que valida un campo del formulario de estado de pago
  validateStatusField,        // Función que valida un campo del formulario de estado de orden
} = useAdminOrders()
</script>

