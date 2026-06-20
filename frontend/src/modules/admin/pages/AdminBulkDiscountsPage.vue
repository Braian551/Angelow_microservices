<template>
  <div class="admin-bulk-discounts-page">
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Descuentos por cantidad"
      subtitle="Gestiona reglas globales por volumen con detalle, validación inmediata y confirmaciones consistentes."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Descuentos por cantidad' }]"
    >
      <template #actions>
        <AdminExportActions
          tone="header"
          :disabled="filteredRules.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportRules('excel')"
          @pdf="exportRules('pdf')"
        />
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nueva regla
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="bulkStats" />

    <!-- Filtros de reglas -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Lectura de reglas"
      placeholder="Buscar por cantidad o porcentaje..."
      :modelValue="filters.search"
      @update:modelValue="filters.search = $event"
      @search="loadRules"
    >
      <template #advanced>
        <div class="filters-row filters-row--bulk-discounts">
          <div class="filter-group">
            <label for="bulk-discount-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="bulk-discount-status" v-model="filters.state" class="form-control">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="open-range">Sin máximo</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__count">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <button type="button" class="admin-filters__clear" @click="clearFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar filtros
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reglas`" />

    <AdminCard title="Bandeja de descuentos por cantidad" icon="fas fa-layer-group" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredRules.length === 0"
        icon="fas fa-layer-group"
        title="Sin reglas de cantidad"
        description="No hay reglas configuradas o ninguna coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table bulk-discounts-table">
          <thead>
            <tr>
              <th>Escala</th>
              <th>Descuento</th>
              <th>Lectura comercial</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rule in pagination.paginatedItems" :key="rule.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ quantityLabel(rule) }}</strong>
                  <span>{{ rule.max_quantity ? 'Tramo cerrado' : 'Escala abierta' }}</span>
                </div>
              </td>
              <td><strong>{{ Number(rule.discount_percent || rule.discount_percentage || 0) }}%</strong></td>
              <td>
                <div class="admin-entity-name">
                  <strong>Aplica a toda la tienda</strong>
                  <span>{{ quantityNarrative(rule) }}</span>
                </div>
              </td>
              <td><span class="status-badge" :class="rule.active ? 'active' : 'rejected'">{{ rule.active ? 'Activo' : 'Inactivo' }}</span></td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(rule)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar regla" @click="openEditModal(rule)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar regla" @click="confirmDeleteRule(rule)">
                    <i class="fas fa-trash"></i>
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

    <AdminModal :show="showDetailModal" :title="selectedRule ? quantityLabel(selectedRule) : 'Detalle de la regla'" max-width="920px" @close="closeDetailModal">
      <template v-if="selectedRule">
        <div class="admin-bulk-discounts-page admin-bulk-discounts-page--modal">
          <div class="bulk-detail-grid admin-detail-grid">
            <AdminCard title="Resumen de volumen" icon="fas fa-boxes">
              <div class="bulk-hero-card admin-surface-card">
                <p class="bulk-hero-card__label admin-surface-card__label">Escala</p>
                <h3>{{ quantityLabel(selectedRule) }}</h3>
                <p>{{ Number(selectedRule.discount_percent || selectedRule.discount_percentage || 0) }}% de descuento</p>
                <span class="status-badge" :class="selectedRule.active ? 'active' : 'rejected'">{{ selectedRule.active ? 'Activo' : 'Inactivo' }}</span>
              </div>
            </AdminCard>

            <AdminCard title="Detalle comercial" icon="fas fa-cogs">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Cantidad mínima</span><strong>{{ selectedRule.min_quantity }}</strong></div>
                <div class="admin-detail-summary__row"><span>Cantidad máxima</span><strong>{{ selectedRule.max_quantity || 'Sin límite' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Descuento</span><strong>{{ Number(selectedRule.discount_percent || selectedRule.discount_percentage || 0) }}%</strong></div>
                <div class="admin-detail-summary__row"><span>Aplicación</span><strong>Tienda completa</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedRule" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar regla
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingRuleId ? 'Editar descuento por cantidad' : 'Nuevo descuento por cantidad'" max-width="760px" @close="closeEditorModal">
      <div class="admin-bulk-discounts-page admin-bulk-discounts-page--modal">
        <div class="editor-grid editor-grid--bulk admin-editor-grid">
          <div>
            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="bulk-min-quantity">
                  Cantidad mínima *
                  <AdminInfoTooltip text="Número mínimo de artículos en el carrito para que se active el descuento automáticamente." />
                </label>
                <input id="bulk-min-quantity" v-model.number="form.min_quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.min_quantity }" @input="validateField('min_quantity')">
                <p v-if="formErrors.min_quantity" class="form-error">{{ formErrors.min_quantity }}</p>
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="bulk-max-quantity">
                  Cantidad máxima
                  <AdminInfoTooltip text="Número máximo de artículos. Dejar vacío para que aplique desde el mínimo sin límite superior." />
                </label>
                <input id="bulk-max-quantity" v-model.number="form.max_quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.max_quantity }" @input="validateField('max_quantity')">
                <p v-if="formErrors.max_quantity" class="form-error">{{ formErrors.max_quantity }}</p>
              </div>
            </div>

            <div class="form-group">
              <label for="bulk-discount-percent">
                Descuento (%) *
                <AdminInfoTooltip text="Porcentaje de descuento sobre el subtotal cuando el carrito cumple la cantidad requerida." />
              </label>
              <input id="bulk-discount-percent" v-model.number="form.discount_percent" type="number" min="1" max="100" class="form-control" :class="{ 'is-invalid': formErrors.discount_percent }" @input="validateField('discount_percent')">
              <p v-if="formErrors.discount_percent" class="form-error">{{ formErrors.discount_percent }}</p>
            </div>

            <div class="form-group">
              <AdminToggleSwitch
                id="bulk-rule-active"
                v-model="form.active"
                layout="inline"
                label="Regla activa durante la compra"
              />
            </div>
          </div>

          <div>
            <div class="bulk-preview-card admin-surface-card">
              <p class="bulk-preview-card__label admin-surface-card__label">Vista previa</p>
              <h3>{{ previewQuantityLabel }}</h3>
              <p>{{ Number(form.discount_percent || 0) }}% de descuento automático</p>
              <span class="status-badge" :class="form.active ? 'active' : 'rejected'">{{ form.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveRule">
          <i class="fas fa-save"></i>
          {{ editingRuleId ? 'Guardar cambios' : 'Crear regla' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
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
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminBulkDiscounts } from '../composables/useAdminBulkDiscounts'
import '../views/AdminBulkDiscountsPage.css'

// =====================================================
// Orquestación de la vista
// =====================================================
const {
  activeFilterCount,
  bulkStats,
  clearFilters,
  closeDetailModal,
  closeEditorModal,
  confirmDeleteRule,
  editingRuleId,
  exportRules,
  exportingFormat,
  filteredRules,
  filters,
  form,
  formErrors,
  loadRules,
  loading,
  openCreateModal,
  openDetailModal,
  openEditFromDetail,
  openEditModal,
  pagination,
  previewQuantityLabel,
  quantityLabel,
  quantityNarrative,
  saveRule,
  selectedRule,
  showDetailModal,
  showEditorModal,
  validateField,
} = useAdminBulkDiscounts()
</script>
