<template>
  <div class="admin-entity-page admin-refunds-page">
    <AdminPageHeader
      icon="fas fa-rotate-left"
      title="Reembolsos"
      subtitle="Revisa solicitudes de clientes, valida evidencias y sincroniza el estado de pago de cada orden."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Reembolsos' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="stats" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Búsqueda y control de reembolsos"
      placeholder="Buscar por orden, cliente, correo o motivo..."
      @search="applyFilters"
      @update:model-value="applyFilters"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--2">
          <div class="admin-filters__group">
            <label for="refund-status-filter"><i class="fas fa-signal"></i> Estado</label>
            <select id="refund-status-filter" v-model="filters.status" @change="applyFilters">
              <option value="">Todos</option>
              <option value="requested">Solicitado</option>
              <option value="approved">Aceptado</option>
              <option value="processing">Reembolso en proceso</option>
              <option value="rejected">Rechazado</option>
              <option value="completed">Reembolsado</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reembolsos`" />

    <AdminCard title="Solicitudes de reembolso" icon="fas fa-list" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="refunds.length === 0"
        icon="fas fa-rotate-left"
        title="Sin reembolsos"
        description="No se encontraron solicitudes con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table admin-refunds-table">
          <thead>
            <tr>
              <th>Solicitud</th>
              <th>Orden</th>
              <th>Cliente</th>
              <th>Motivo</th>
              <th>Pago</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="refund in pagination.paginatedItems" :key="`${refund.source}-${refund.id}`">
              <td>
                <div class="admin-entity-name">
                  <strong>#{{ refund.id }}</strong>
                  <span>{{ formatDateTime(refund.requested_at || refund.created_at) }}</span>
                </div>
              </td>
              <td>
                <RouterLink :to="{ name: 'admin-order-detail', params: { id: refund.order_id }, query: refund.source === 'legacy' ? { vista: 'archivo' } : {} }">
                  {{ refund.order_number || `#${refund.order_id}` }}
                </RouterLink>
                <span class="admin-refunds-table__amount">{{ formatCurrency(refund.total) }}</span>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ refund.customer_name }}</strong>
                  <span>{{ refund.customer_email }}</span>
                </div>
              </td>
              <td>
                <span class="admin-refunds-table__reason">{{ refundReasonLabel(refund.reason) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="paymentStatusBadgeClass(refund.payment_status)">
                  {{ paymentStatusLabel(refund.payment_status) }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="refundStatusBadgeClass(refund.status)">
                  {{ refundStatusLabel(refund.status) }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(refund)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button
                    class="action-btn edit"
                    type="button"
                    title="Aceptar reembolso"
                    :class="{ 'is-loading': isActionLoading(refund, 'approve') }"
                    :disabled="savingAction || !canUseAction(refund, 'approve')"
                    @click="openActionModal(refund, 'approve')"
                  >
                    <i :class="isActionLoading(refund, 'approve') ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                  </button>
                  <button
                    class="action-btn edit"
                    type="button"
                    title="Marcar en proceso"
                    :class="{ 'is-loading': isActionLoading(refund, 'process') }"
                    :disabled="savingAction || !canUseAction(refund, 'process')"
                    @click="openActionModal(refund, 'process')"
                  >
                    <i :class="isActionLoading(refund, 'process') ? 'fas fa-spinner fa-spin' : 'fas fa-rotate'"></i>
                  </button>
                  <button
                    class="action-btn edit action-btn--complete"
                    type="button"
                    title="Completar reembolso"
                    :class="{ 'is-loading': isActionLoading(refund, 'complete') }"
                    :disabled="savingAction || !canUseAction(refund, 'complete')"
                    @click="openActionModal(refund, 'complete')"
                  >
                    <i :class="isActionLoading(refund, 'complete') ? 'fas fa-spinner fa-spin' : 'fas fa-hand-holding-usd'"></i>
                  </button>
                  <button
                    class="action-btn delete"
                    type="button"
                    title="Rechazar solicitud"
                    :class="{ 'is-loading': isActionLoading(refund, 'reject') }"
                    :disabled="savingAction || !canUseAction(refund, 'reject')"
                    @click="openActionModal(refund, 'reject')"
                  >
                    <i :class="isActionLoading(refund, 'reject') ? 'fas fa-spinner fa-spin' : 'fas fa-times'"></i>
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

    <AdminModal :show="showDetailModal" title="Detalle de reembolso" max-width="760px" @close="closeDetailModal">
      <div v-if="selectedRefund" class="admin-refunds-page admin-refunds-page--modal">
        <div class="refund-detail-grid">
          <div class="refund-detail-card">
            <span>Orden</span>
            <strong>{{ selectedRefund.order_number || `#${selectedRefund.order_id}` }}</strong>
          </div>
          <div class="refund-detail-card">
            <span>Total</span>
            <strong>{{ formatCurrency(selectedRefund.total) }}</strong>
          </div>
          <div class="refund-detail-card">
            <span>Pago</span>
            <strong>{{ paymentStatusLabel(selectedRefund.payment_status) }}</strong>
          </div>
          <div class="refund-detail-card">
            <span>Estado</span>
            <strong>{{ refundStatusLabel(selectedRefund.status) }}</strong>
          </div>
        </div>

        <div class="refund-detail-section">
          <h4>Cliente</h4>
          <p>{{ selectedRefund.customer_name }} · {{ selectedRefund.customer_email }}</p>
        </div>

        <div class="refund-detail-section">
          <h4>Motivo</h4>
          <p>{{ refundReasonLabel(selectedRefund.reason) }}</p>
          <p v-if="selectedRefund.details" class="refund-detail-section__details">{{ refundDetailsLabel(selectedRefund.details) }}</p>
        </div>

        <div class="refund-detail-section">
          <h4>Evidencia</h4>
          <button v-if="selectedRefund.evidence_url" class="btn btn-secondary" type="button" @click="openEvidenceModal(selectedRefund)">
            <i class="fas fa-paperclip"></i>
            {{ selectedRefund.evidence_original_name || 'Previsualizar evidencia' }}
          </button>
          <p v-else>No se adjuntó evidencia.</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
      </template>
    </AdminModal>

    <AdminModal :show="showActionModal" :title="selectedAction?.title || 'Actualizar reembolso'" max-width="560px" @close="closeActionModal">
      <div v-if="selectedRefund && selectedAction" class="admin-refunds-page admin-refunds-page--modal">
        <div class="refund-action-summary">
          <strong>{{ selectedRefund.order_number || `#${selectedRefund.order_id}` }}</strong>
          <span>{{ selectedAction.paymentMessage }}</span>
        </div>

        <div class="form-group">
          <label for="refund-action-description">Nota operativa</label>
          <textarea
            id="refund-action-description"
            v-model="actionForm.description"
            class="form-control"
            rows="4"
            :class="{ 'is-invalid': actionErrors.description }"
            :disabled="savingAction"
            @input="validateActionField('description')"
          ></textarea>
          <p v-if="actionErrors.description" class="form-error">{{ actionErrors.description }}</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="savingAction" @click="closeActionModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': savingAction }" :disabled="savingAction" @click="submitAction">
          <i :class="savingAction ? 'fas fa-spinner fa-spin' : selectedAction?.icon"></i>
          {{ savingAction ? 'Guardando...' : (selectedAction?.confirm || 'Guardar') }}
        </button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal
      :show="showEvidenceModal"
      :attachment="selectedRefund ? {
        url: selectedRefund.evidence_url,
        name: selectedRefund.evidence_original_name || 'Evidencia de reembolso',
        exists: Boolean(selectedRefund.evidence_url),
      } : null"
      title="Evidencia de reembolso"
      icon="fas fa-paperclip"
      image-alt="Evidencia de reembolso"
      file-preview-text="Este archivo no puede previsualizarse aquí. Usa el botón para abrirlo."
      missing-title="Evidencia no disponible."
      missing-text="No pudimos mostrar el archivo en este momento."
      empty-title="Sin evidencia adjunta"
      empty-text="Esta solicitud no tiene evidencia disponible para revisar."
      open-label="Abrir evidencia"
      :meta="selectedRefund ? [
        { label: 'Orden', value: selectedRefund.order_number || `#${selectedRefund.order_id}` },
        { label: 'Estado', value: refundStatusLabel(selectedRefund.status) },
      ] : []"
      @close="closeEvidenceModal"
    />
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import { useAdminRefunds } from '../composables/useAdminRefunds'
import '../views/AdminRefundsPage.css'

const {
  activeFilterCount,
  actionErrors,
  actionForm,
  applyFilters,
  canUseAction,
  clearFilters,
  closeActionModal,
  closeDetailModal,
  closeEvidenceModal,
  filters,
  formatCurrency,
  formatDateTime,
  isActionLoading,
  loading,
  openActionModal,
  openDetailModal,
  openEvidenceModal,
  pagination,
  paymentStatusBadgeClass,
  paymentStatusLabel,
  refundDetailsLabel,
  refundReasonLabel,
  refundStatusBadgeClass,
  refundStatusLabel,
  refunds,
  savingAction,
  selectedAction,
  selectedRefund,
  showActionModal,
  showDetailModal,
  showEvidenceModal,
  stats,
  submitAction,
  validateActionField,
} = useAdminRefunds()
</script>
