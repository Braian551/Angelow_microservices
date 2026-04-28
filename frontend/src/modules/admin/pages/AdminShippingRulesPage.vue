<template>
  <div class="admin-shipping-rules-page">
    <AdminPageHeader
      icon="fas fa-dollar-sign"
      title="Recargos por rango"
      subtitle="Define cargos adicionales por subtotal. Estos valores se suman al costo base del método de envío."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Recargos por rango' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportRules">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
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
import { computed, onMounted, reactive, ref } from 'vue'
import { shippingHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const rules = ref([])
const selectedRule = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingRuleId = ref(null)

const filters = reactive({ search: '', state: 'all' })
const form = reactive({ min_price: 0, max_price: null, shipping_cost: 0, active: true })
const formErrors = reactive({ min_price: '', max_price: '', shipping_cost: '' })

const filteredRules = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return rules.value.filter((rule) => {
    if (filters.state === 'active' && !rule.active) return false
    if (filters.state === 'inactive' && rule.active) return false
    if (filters.state === 'free' && !isFreeRule(rule)) return false
    if (filters.state === 'paid' && isFreeRule(rule)) return false
    if (!term) return true
    return [rangeLabel(rule), pricingNarrative(rule), rule.shipping_cost, rule.min_price, rule.max_price].join(' ').toLowerCase().includes(term)
  })
})

const pagination = useAdminPagination(filteredRules, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [filters.search, filters.state !== 'all'].filter(Boolean).length)
const previewRangeLabel = computed(() => rangeLabel(form))
const ruleStats = computed(() => [
  { key: 'total', label: 'Total reglas', value: rules.value.length, icon: 'fas fa-sliders-h', color: 'primary' },
  { key: 'active', label: 'Activas', value: rules.value.filter((rule) => rule.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'free', label: 'Sin recargo', value: rules.value.filter((rule) => isFreeRule(rule)).length, icon: 'fas fa-gift', color: 'warning' },
  { key: 'open', label: 'Sin tope', value: rules.value.filter((rule) => !rule.max_price).length, icon: 'fas fa-infinity', color: 'info' },
])

function resetForm() {
  form.min_price = 0
  form.max_price = null
  form.shipping_cost = 0
  form.active = true
  clearErrors()
}

function clearErrors() {
  Object.keys(formErrors).forEach((key) => {
    formErrors[key] = ''
  })
}

function clearFilters() {
  filters.search = ''
  filters.state = 'all'
}

function openCreateModal() {
  editingRuleId.value = null
  resetForm()
  showEditorModal.value = true
}

function openEditModal(rule) {
  editingRuleId.value = rule.id
  clearErrors()
  form.min_price = Number(rule.min_price || 0)
  form.max_price = rule.max_price ?? null
  form.shipping_cost = Number(rule.shipping_cost || 0)
  form.active = Boolean(rule.active)
  showEditorModal.value = true
}

function closeEditorModal() {
  showEditorModal.value = false
  editingRuleId.value = null
  resetForm()
}

function openDetailModal(rule) {
  selectedRule.value = rule
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedRule.value = null
  showDetailModal.value = false
}

function openEditFromDetail() {
  if (!selectedRule.value) return
  const current = selectedRule.value
  closeDetailModal()
  openEditModal(current)
}

function validateField(field) {
  switch (field) {
    case 'min_price':
      formErrors.min_price = Number(form.min_price) >= 0 ? '' : 'El mínimo no puede ser negativo.'
      break
    case 'max_price':
      formErrors.max_price = ''
      if (form.max_price !== null && form.max_price !== '' && Number(form.max_price) < 0) formErrors.max_price = 'El máximo no puede ser negativo.'
      if (!formErrors.max_price && form.max_price !== null && form.max_price !== '' && Number(form.max_price) < Number(form.min_price || 0)) {
        formErrors.max_price = 'El máximo debe ser mayor o igual al mínimo.'
      }
      break
    case 'shipping_cost':
      formErrors.shipping_cost = Number(form.shipping_cost) >= 0 ? '' : 'El recargo no puede ser negativo.'
      break
    default:
      break
  }
}

function validateForm() {
  validateField('min_price')
  validateField('max_price')
  validateField('shipping_cost')
  return Object.values(formErrors).every((value) => !value)
}

async function loadRules() {
  loading.value = true
  try {
    const { data } = await shippingHttp.get('/admin/shipping-rules')
    rules.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    rules.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar las reglas por precio.') })
  } finally {
    loading.value = false
  }
}

async function saveRule() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = {
    min_price: Number(form.min_price || 0),
    max_price: form.max_price === null || form.max_price === '' ? null : Number(form.max_price),
    shipping_cost: Number(form.shipping_cost || 0),
    active: form.active,
  }

  try {
    if (editingRuleId.value) {
      await shippingHttp.put(`/admin/shipping-rules/${editingRuleId.value}`, payload)
      showSnackbar({ type: 'success', message: 'Regla actualizada correctamente.' })
    } else {
      await shippingHttp.post('/admin/shipping-rules', payload)
      showSnackbar({ type: 'success', message: 'Regla creada correctamente.' })
    }
    closeEditorModal()
    await loadRules()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar la regla.') })
  }
}

function confirmDeleteRule(rule) {
  showAlert({
    type: 'warning',
    title: 'Eliminar regla',
    message: `Vas a eliminar el recargo del rango ${rangeLabel(rule)}. Esta accion no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await shippingHttp.delete(`/admin/shipping-rules/${rule.id}`)
            showSnackbar({ type: 'success', message: 'Regla eliminada correctamente.' })
            if (selectedRule.value?.id === rule.id) closeDetailModal()
            await loadRules()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar la regla.') })
          }
        },
      },
    ],
  })
}

function exportRules() {
  const rows = filteredRules.value.map((rule) => [
    rangeLabel(rule),
    pricingNarrative(rule),
    isFreeRule(rule) ? 'Sin recargo' : `+${formatCurrency(rule.shipping_cost)}`,
    rule.active ? 'Activo' : 'Inactivo',
  ])
  const csv = [['Rango', 'Descripción', 'Cargo adicional', 'Estado'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('reglas-envio-precio.csv', csv)
}

function isFreeRule(rule) {
  return Number(rule.shipping_cost || 0) === 0
}

function rangeLabel(rule) {
  const min = formatCurrency(rule.min_price || 0)
  if (rule.max_price === null || rule.max_price === undefined || rule.max_price === '') return `Desde ${min}`
  return `${min} a ${formatCurrency(rule.max_price)}`
}

function pricingNarrative(rule) {
  return isFreeRule(rule)
    ? 'En este rango no se suma recargo adicional al costo base del método.'
    : `En este rango se suma un recargo adicional de ${formatCurrency(rule.shipping_cost)} sobre el costo base.`
}

function ruleStatusLabel(rule) {
  if (!rule.active) return 'Inactiva'
  return isFreeRule(rule) ? 'Sin recargo' : 'Con recargo'
}

function ruleStatusClass(rule) {
  if (!rule.active) return 'rejected'
  return isFreeRule(rule) ? 'info' : 'active'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
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

onMounted(loadRules)
</script>

<style scoped>
.shipping-rule-hero h3 {
  font-size: 2.2rem;
}

/* Mock resumen: réplica de las filas de envío del resumen de pago en checkout */
.shipping-rule-mock-summary {
  display: grid;
  gap: 0;
  margin-bottom: 1.2rem;
  border: 1px solid #e0e0e0;
  border-radius: 1.2rem;
  overflow: hidden;
}

.shipping-rule-mock-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.8rem 1.2rem;
  border-bottom: 1px solid #f0f0f0;
  font-size: 1.28rem;
}

.shipping-rule-mock-row:last-child {
  border-bottom: none;
}

.shipping-rule-mock-row span {
  color: #5c6773;
}

.shipping-rule-mock-row strong {
  color: #1a1a2e;
  font-weight: 600;
}

.shipping-rule-mock-row--highlight {
  background: #f0f8ff;
}

.shipping-rule-mock-row--highlight span,
.shipping-rule-mock-row--highlight strong {
  color: #005b8c !important;
  font-weight: 700;
}

.shipping-rule-mock-row--free span,
.shipping-rule-mock-row--free strong {
  color: #4bb543 !important;
  font-weight: 600;
}

.shipping-rule-mock-row--total {
  background: #f8f8f8;
}

.shipping-rule-mock-row--total span {
  color: #1a1a2e;
  font-weight: 700;
}

.shipping-rule-mock-row--total strong {
  color: #0077b6;
  font-weight: 700;
}
</style>
