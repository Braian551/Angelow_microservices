<template>
  <div class="admin-shipping-rules-page">
    <AdminPageHeader
      icon="fas fa-dollar-sign"
      title="Recargos por rango"
      subtitle="Define cargos adicionales por subtotal. Estos valores se suman al costo base del método de envío."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Recargos por rango' }]"
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

    <AdminStatsGrid :loading="loading" :count="4" :stats="ruleStats" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Filtros de vigencia"
      placeholder="Buscar por rango o recargo..."
      @search="() => {}"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="shipping-rule-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="shipping-rule-status" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="free">Sin recargo</option>
              <option value="paid">Con recargo</option>
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
              <i class="fas fa-times-circle"></i> Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reglas`" />

    <AdminCard title="Bandeja de reglas" icon="fas fa-shipping-fast" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredRules.length === 0"
        icon="fas fa-shipping-fast"
        title="Sin reglas por precio"
        description="No hay reglas configuradas o ninguna coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table shipping-rules-table">
          <thead>
            <tr>
              <th>Rango</th>
              <th>Lectura comercial</th>
              <th>Cargo adicional</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rule in pagination.paginatedItems" :key="rule.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ rangeLabel(rule) }}</strong>
                  <span>{{ rule.max_price ? 'Rango cerrado' : 'Sin tope máximo' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ isFreeRule(rule) ? 'Sin recargo adicional' : `+${formatCurrency(rule.shipping_cost)}` }}</strong>
                  <span>{{ pricingNarrative(rule) }}</span>
                </div>
              </td>
              <td><strong>{{ isFreeRule(rule) ? 'Sin recargo' : `+${formatCurrency(rule.shipping_cost)}` }}</strong></td>
              <td>
                <span class="status-badge" :class="ruleStatusClass(rule)">{{ ruleStatusLabel(rule) }}</span>
              </td>
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

    <AdminModal :show="showDetailModal" :title="selectedRule ? rangeLabel(selectedRule) : 'Detalle de la regla'" max-width="920px" @close="closeDetailModal">
      <template v-if="selectedRule">
        <div class="admin-shipping-rules-page admin-shipping-rules-page--modal">
          <div class="shipping-rule-detail-grid admin-detail-grid">
            <AdminCard title="Resumen del recargo" icon="fas fa-money-bill-wave">
              <div class="shipping-rule-hero admin-surface-card">
                <p class="shipping-rule-hero__label admin-surface-card__label">Rango aplicado</p>
                <h3>{{ rangeLabel(selectedRule) }}</h3>
                <p>{{ pricingNarrative(selectedRule) }}</p>
                <span class="status-badge" :class="ruleStatusClass(selectedRule)">{{ ruleStatusLabel(selectedRule) }}</span>
              </div>
            </AdminCard>

            <AdminCard title="Configuración" icon="fas fa-cogs">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Mínimo</span><strong>{{ formatCurrency(selectedRule.min_price) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Máximo</span><strong>{{ selectedRule.max_price ? formatCurrency(selectedRule.max_price) : 'Sin límite' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Cargo adicional</span><strong>{{ isFreeRule(selectedRule) ? 'Sin recargo' : `+${formatCurrency(selectedRule.shipping_cost)}` }}</strong></div>
                <div class="admin-detail-summary__row"><span>Estado</span><strong>{{ ruleStatusLabel(selectedRule) }}</strong></div>
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

    <AdminModal :show="showEditorModal" :title="editingRuleId ? 'Editar regla por precio' : 'Nueva regla por precio'" max-width="760px" @close="closeEditorModal">
      <div class="admin-shipping-rules-page admin-shipping-rules-page--modal">
        <div class="editor-grid editor-grid--shipping-rules admin-editor-grid">
          <div>
            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="shipping-rule-min">
                  Precio mínimo *
                  <AdminInfoTooltip text="Monto mínimo del pedido que activa esta regla de envío." />
                </label>
                <input id="shipping-rule-min" v-model.number="form.min_price" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.min_price }" @input="validateField('min_price')">
                <p v-if="formErrors.min_price" class="form-error">{{ formErrors.min_price }}</p>
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="shipping-rule-max">
                  Precio máximo
                  <AdminInfoTooltip text="Monto máximo del pedido cubierto por esta regla. Dejar vacío si no hay límite superior." />
                </label>
                <input id="shipping-rule-max" v-model.number="form.max_price" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.max_price }" @input="validateField('max_price')">
                <p v-if="formErrors.max_price" class="form-error">{{ formErrors.max_price }}</p>
              </div>
            </div>

            <div class="form-group">
              <label for="shipping-rule-cost">
                Cargo adicional por rango *
                <AdminInfoTooltip text="Valor adicional que se suma al costo base del método cuando el pedido cae en este rango." />
              </label>
              <input id="shipping-rule-cost" v-model.number="form.shipping_cost" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.shipping_cost }" @input="validateField('shipping_cost')">
              <p v-if="formErrors.shipping_cost" class="form-error">{{ formErrors.shipping_cost }}</p>
            </div>

            <AdminToggleSwitch
              id="shipping-rule-active"
              v-model="form.active"
              title="Regla activa"
              description="Si está activa, este recargo se suma al costo base del método de envío durante el checkout."
            />
          </div>

          <div>
            <div class="shipping-rule-preview-card admin-surface-card">
              <p class="shipping-rule-preview-card__label admin-surface-card__label">Vista previa del cliente</p>
              <div class="shipping-rule-mock-summary">
                <div class="shipping-rule-mock-row">
                  <span>Envío base (método)</span>
                  <strong>según método</strong>
                </div>
                <div :class="['shipping-rule-mock-row', isFreeRule(form) ? 'shipping-rule-mock-row--free' : 'shipping-rule-mock-row--highlight']">
                  <span>{{ isFreeRule(form) ? 'Sin recargo adicional' : `Ajuste por rango (${previewRangeLabel})` }}</span>
                  <strong>{{ isFreeRule(form) ? '—' : `+${formatCurrency(form.shipping_cost)}` }}</strong>
                </div>
                <div class="shipping-rule-mock-row shipping-rule-mock-row--total">
                  <span>Envío total</span>
                  <strong>{{ isFreeRule(form) ? 'base del método' : `base + ${formatCurrency(form.shipping_cost)}` }}</strong>
                </div>
              </div>
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
// =====================================================
// Imports de la vista y componentes compartidos
// =====================================================
import '../views/AdminShippingRulesPage.css'
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
import { useAdminShippingRules } from '../composables/useAdminShippingRules'

// =====================================================
// Orquestación de la lógica de reglas de envío
// =====================================================
const {
  activeFilterCount,
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
  formatCurrency,
  isFreeRule,
  loading,
  openCreateModal,
  openDetailModal,
  openEditFromDetail,
  openEditModal,
  pagination,
  previewRangeLabel,
  pricingNarrative,
  rangeLabel,
  ruleStats,
  ruleStatusClass,
  ruleStatusLabel,
  saveRule,
  selectedRule,
  showDetailModal,
  showEditorModal,
  validateField,
} = useAdminShippingRules()
</script>
