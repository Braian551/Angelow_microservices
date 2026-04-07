<template>
  <div class="admin-bulk-discounts-page">
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Descuentos por cantidad"
      subtitle="Gestiona reglas globales por volumen con detalle, validacion inmediata y confirmaciones consistentes."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Descuentos por cantidad' }]"
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
              <option value="open-range">Sin maximo</option>
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
    <AdminResultsBar :text="`Mostrando ${filteredRules.length} reglas`" />

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
            <tr v-for="rule in filteredRules" :key="rule.id">
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

    <AdminModal :show="showDetailModal" :title="selectedRule ? quantityLabel(selectedRule) : 'Detalle de la regla'" max-width="920px" @close="closeDetailModal">
      <template v-if="selectedRule">
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
              <div class="admin-detail-summary__row"><span>Cantidad minima</span><strong>{{ selectedRule.min_quantity }}</strong></div>
              <div class="admin-detail-summary__row"><span>Cantidad maxima</span><strong>{{ selectedRule.max_quantity || 'Sin limite' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Descuento</span><strong>{{ Number(selectedRule.discount_percent || selectedRule.discount_percentage || 0) }}%</strong></div>
              <div class="admin-detail-summary__row"><span>Aplicacion</span><strong>Tienda completa</strong></div>
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

    <AdminModal :show="showEditorModal" :title="editingRuleId ? 'Editar descuento por cantidad' : 'Nuevo descuento por cantidad'" max-width="760px" @close="closeEditorModal">
      <div class="editor-grid editor-grid--bulk admin-editor-grid">
        <div>
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="bulk-min-quantity">Cantidad minima *</label>
              <input id="bulk-min-quantity" v-model.number="form.min_quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.min_quantity }" @input="validateField('min_quantity')">
              <p v-if="formErrors.min_quantity" class="form-error">{{ formErrors.min_quantity }}</p>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="bulk-max-quantity">Cantidad maxima</label>
              <input id="bulk-max-quantity" v-model.number="form.max_quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.max_quantity }" @input="validateField('max_quantity')">
              <p v-if="formErrors.max_quantity" class="form-error">{{ formErrors.max_quantity }}</p>
            </div>
          </div>

          <div class="form-group">
            <label for="bulk-discount-percent">Descuento (%) *</label>
            <input id="bulk-discount-percent" v-model.number="form.discount_percent" type="number" min="1" max="100" class="form-control" :class="{ 'is-invalid': formErrors.discount_percent }" @input="validateField('discount_percent')">
            <p v-if="formErrors.discount_percent" class="form-error">{{ formErrors.discount_percent }}</p>
          </div>

          <div class="form-group form-group--toggle">
            <label class="toggle-label">
              <input v-model="form.active" type="checkbox">
              Regla activa para el checkout
            </label>
          </div>
        </div>

        <div>
          <div class="bulk-preview-card admin-surface-card">
            <p class="bulk-preview-card__label admin-surface-card__label">Previsualizacion</p>
            <h3>{{ previewQuantityLabel }}</h3>
            <p>{{ Number(form.discount_percent || 0) }}% de descuento automatico</p>
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
import { discountHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const rules = ref([])
const selectedRule = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingRuleId = ref(null)

const filters = reactive({ search: '', state: 'all' })
const form = reactive({ min_quantity: 2, max_quantity: null, discount_percent: 10, active: true })
const formErrors = reactive({ min_quantity: '', max_quantity: '', discount_percent: '' })

const filteredRules = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return rules.value.filter((rule) => {
    if (filters.state === 'active' && !rule.active) return false
    if (filters.state === 'inactive' && rule.active) return false
    if (filters.state === 'open-range' && rule.max_quantity) return false
    if (!term) return true
    return [quantityLabel(rule), quantityNarrative(rule), rule.discount_percent, rule.discount_percentage].join(' ').toLowerCase().includes(term)
  })
})

const activeFilterCount = computed(() => [filters.search, filters.state !== 'all'].filter(Boolean).length)
const previewQuantityLabel = computed(() => quantityLabel(form))
const bulkStats = computed(() => [
  { key: 'total', label: 'Total reglas', value: rules.value.length, icon: 'fas fa-layer-group', color: 'primary' },
  { key: 'active', label: 'Activas', value: rules.value.filter((rule) => rule.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'open', label: 'Sin maximo', value: rules.value.filter((rule) => !rule.max_quantity).length, icon: 'fas fa-infinity', color: 'warning' },
  { key: 'top', label: 'Mayor descuento', value: `${Math.max(0, ...rules.value.map((rule) => Number(rule.discount_percent || rule.discount_percentage || 0)))}%`, icon: 'fas fa-percent', color: 'info' },
])

function resetForm() {
  form.min_quantity = 2
  form.max_quantity = null
  form.discount_percent = 10
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
  form.min_quantity = Number(rule.min_quantity || 1)
  form.max_quantity = rule.max_quantity ?? null
  form.discount_percent = Number(rule.discount_percent || rule.discount_percentage || 0)
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
    case 'min_quantity':
      formErrors.min_quantity = Number(form.min_quantity) >= 1 ? '' : 'La cantidad minima debe ser mayor que cero.'
      break
    case 'max_quantity':
      formErrors.max_quantity = ''
      if (form.max_quantity !== null && form.max_quantity !== '' && Number(form.max_quantity) < 1) formErrors.max_quantity = 'La cantidad maxima debe ser mayor que cero.'
      if (!formErrors.max_quantity && form.max_quantity !== null && form.max_quantity !== '' && Number(form.max_quantity) < Number(form.min_quantity || 1)) {
        formErrors.max_quantity = 'La cantidad maxima debe ser mayor o igual a la minima.'
      }
      break
    case 'discount_percent':
      formErrors.discount_percent = Number(form.discount_percent) >= 1 && Number(form.discount_percent) <= 100
        ? ''
        : 'El descuento debe estar entre 1% y 100%.'
      break
    default:
      break
  }
}

function validateForm() {
  validateField('min_quantity')
  validateField('max_quantity')
  validateField('discount_percent')
  return Object.values(formErrors).every((value) => !value)
}

async function loadRules() {
  loading.value = true
  try {
    const { data } = await discountHttp.get('/admin/bulk-discounts')
    rules.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    rules.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar las reglas por cantidad.') })
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
    min_quantity: Number(form.min_quantity || 1),
    max_quantity: form.max_quantity === null || form.max_quantity === '' ? null : Number(form.max_quantity),
    discount_percent: Number(form.discount_percent || 0),
    active: form.active,
  }

  try {
    if (editingRuleId.value) {
      await discountHttp.put(`/admin/bulk-discounts/${editingRuleId.value}`, payload)
      showSnackbar({ type: 'success', message: 'Regla actualizada correctamente.' })
    } else {
      await discountHttp.post('/admin/bulk-discounts', payload)
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
    message: `Vas a eliminar la regla ${quantityLabel(rule)}. Esta accion no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await discountHttp.delete(`/admin/bulk-discounts/${rule.id}`)
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
  const rows = filteredRules.value.map((rule) => [quantityLabel(rule), `${Number(rule.discount_percent || rule.discount_percentage || 0)}%`, quantityNarrative(rule), rule.active ? 'Activo' : 'Inactivo'])
  const csv = [['Escala', 'Descuento', 'Descripcion', 'Estado'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('descuentos-por-cantidad.csv', csv)
}

function quantityLabel(rule) {
  const min = Number(rule.min_quantity || 0)
  if (!rule.max_quantity) return `Desde ${min} unidades`
  return `${min} a ${Number(rule.max_quantity)} unidades`
}

function quantityNarrative(rule) {
  return `El checkout aplicara ${Number(rule.discount_percent || rule.discount_percentage || 0)}% al llegar a ${quantityLabel(rule).toLowerCase()}.`
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
.bulk-hero-card h3,
.bulk-preview-card h3 {
  font-size: 2.2rem;
}
</style>
