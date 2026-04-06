<template>
  <div class="admin-discount-codes-page">
    <AdminPageHeader
      icon="fas fa-tags"
      title="Códigos de descuento"
      subtitle="Gestiona promociones con detalle operativo, control de vigencia y administración centralizada."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Códigos de descuento' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportCodes">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo código
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="discountStats" />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-filter"></i>
            <h3>Filtros y control</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model.trim="filters.search" type="text" class="search-input" placeholder="Buscar por código o tipo...">
          <button v-if="filters.search" type="button" class="search-clear" @click="filters.search = ''">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--discount-codes">
          <div class="filter-group">
            <label for="discount-code-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="discount-code-status" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="expired">Vencidos</option>
              <option value="single-use">Uso único</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="discount-code-type"><i class="fas fa-percent"></i> Tipo</label>
            <select id="discount-code-type" v-model="filters.type">
              <option value="all">Todos</option>
              <option value="percent">Porcentaje</option>
              <option value="fixed">Monto fijo</option>
            </select>
          </div>
        </div>

        <div class="filters-actions-bar">
          <div class="active-filters">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="clearFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </div>
    </AdminCard>

    <div class="results-summary">
      <div class="results-info">
        <i class="fas fa-ticket-alt"></i>
        <p>Mostrando {{ filteredCodes.length }} códigos</p>
      </div>
    </div>

    <AdminCard title="Bandeja de códigos" icon="fas fa-tags" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'pill', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCodes.length === 0"
        icon="fas fa-tags"
        title="Sin códigos"
        description="Aún no hay códigos de descuento o ninguno coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table discount-codes-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Tipo</th>
              <th>Valor</th>
              <th>Usos</th>
              <th>Vigencia</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="code in filteredCodes" :key="code.id">
              <td>
                <div class="entity-name-cell">
                  <strong class="discount-code-pill">{{ code.code }}</strong>
                  <span>{{ code.is_single_use ? 'Uso único' : 'Uso múltiple' }}</span>
                </div>
              </td>
              <td><span class="status-badge info">{{ code.type_label }}</span></td>
              <td><strong>{{ formatDiscountValue(code) }}</strong></td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ code.times_used }} / {{ code.max_uses || '∞' }}</strong>
                  <span>{{ code.max_uses ? remainingUsesLabel(code) : 'Sin límite de uso' }}</span>
                </div>
              </td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ code.start_date ? formatDateTime(code.start_date) : 'Inmediato' }}</strong>
                  <span>{{ code.expires_at ? `Vence ${formatDateTime(code.expires_at)}` : 'Sin expiración' }}</span>
                </div>
              </td>
              <td><span class="status-badge" :class="codeStatusClass(code)">{{ codeStatusLabel(code) }}</span></td>
              <td>
                <div class="entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(code)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar código" @click="openEditModal(code)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar código" @click="confirmDeleteCode(code)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="selectedCode ? `Código ${selectedCode.code}` : 'Detalle del código'" max-width="960px" @close="closeDetailModal">
      <template v-if="selectedCode">
        <div class="discount-detail-grid admin-detail-grid">
          <div>
            <AdminCard title="Resumen promocional" icon="fas fa-ticket-alt">
              <div class="discount-hero-card admin-surface-card">
                <p class="discount-hero-card__label admin-surface-card__label">Código</p>
                <h3>{{ selectedCode.code }}</h3>
                <p class="discount-hero-card__value admin-surface-card__value">{{ formatDiscountValue(selectedCode) }}</p>
                <span class="status-badge" :class="codeStatusClass(selectedCode)">{{ codeStatusLabel(selectedCode) }}</span>
              </div>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Configuración" icon="fas fa-cogs">
              <div class="summary-stack">
                <div class="summary-row"><span>Tipo</span><strong>{{ selectedCode.type_label }}</strong></div>
                <div class="summary-row"><span>Usos máximos</span><strong>{{ selectedCode.max_uses || 'Ilimitados' }}</strong></div>
                <div class="summary-row"><span>Usos realizados</span><strong>{{ selectedCode.times_used }}</strong></div>
                <div class="summary-row"><span>Inicio</span><strong>{{ selectedCode.start_date ? formatDateTime(selectedCode.start_date) : 'Inmediato' }}</strong></div>
                <div class="summary-row"><span>Expira</span><strong>{{ selectedCode.expires_at ? formatDateTime(selectedCode.expires_at) : 'Sin fecha' }}</strong></div>
                <div class="summary-row"><span>Modo</span><strong>{{ selectedCode.is_single_use ? 'Uso único' : 'Uso repetible' }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedCode" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar código
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingCodeId ? 'Editar código' : 'Nuevo código'" max-width="760px" @close="closeEditorModal">
      <div class="editor-grid editor-grid--discounts admin-editor-grid">
        <div>
          <div class="form-group">
            <label for="discount-code-field">Código *</label>
            <input id="discount-code-field" v-model.trim="form.code" type="text" class="form-control" :class="{ 'is-invalid': formErrors.code }" @input="handleCodeInput">
            <p v-if="formErrors.code" class="form-error">{{ formErrors.code }}</p>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="discount-type-field">Tipo *</label>
              <select id="discount-type-field" v-model="form.type" class="form-control" @change="validateField('type')">
                <option value="percent">Porcentaje</option>
                <option value="fixed">Monto fijo</option>
              </select>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="discount-value-field">Valor *</label>
              <input id="discount-value-field" v-model.number="form.value" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.value }" @input="validateField('value')">
              <p v-if="formErrors.value" class="form-error">{{ formErrors.value }}</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="discount-max-uses">Usos máximos</label>
              <input id="discount-max-uses" v-model.number="form.max_uses" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.max_uses }" @input="validateField('max_uses')">
              <p v-if="formErrors.max_uses" class="form-error">{{ formErrors.max_uses }}</p>
            </div>
            <div class="form-group form-group--toggle" style="flex: 1; justify-content: flex-end;">
              <label class="toggle-label">
                <input v-model="form.is_single_use" type="checkbox">
                Uso único por cliente
              </label>
            </div>
          </div>
        </div>

        <div>
          <div class="form-group">
            <label for="discount-start-date">Fecha de inicio</label>
            <input id="discount-start-date" v-model="form.start_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.start_date }" @change="validateField('start_date')">
            <p v-if="formErrors.start_date" class="form-error">{{ formErrors.start_date }}</p>
          </div>

          <div class="form-group">
            <label for="discount-end-date">Fecha de expiración</label>
            <input id="discount-end-date" v-model="form.expires_at" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.expires_at }" @change="validateField('expires_at')">
            <p v-if="formErrors.expires_at" class="form-error">{{ formErrors.expires_at }}</p>
          </div>

          <div class="form-group form-group--toggle">
            <label class="toggle-label">
              <input v-model="form.active" type="checkbox">
              Código activo
            </label>
          </div>

          <div class="discount-preview-card admin-surface-card">
            <p class="discount-preview-card__label admin-surface-card__label">Previsualización</p>
            <h3>{{ form.code || 'PROMO' }}</h3>
            <p>{{ form.type === 'percent' ? `${Number(form.value || 0)}% de descuento` : `${formatCurrency(form.value || 0)} de descuento` }}</p>
            <span class="status-badge" :class="form.active ? 'active' : 'rejected'">{{ form.active ? 'Activo' : 'Inactivo' }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCode">
          <i class="fas fa-save"></i>
          {{ editingCodeId ? 'Guardar cambios' : 'Crear código' }}
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
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const showAdvanced = ref(true)
const codes = ref([])
const selectedCode = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingCodeId = ref(null)

const filters = reactive({ search: '', state: 'all', type: 'all' })

const form = reactive({
  code: '',
  type: 'percent',
  value: 10,
  max_uses: null,
  start_date: '',
  expires_at: '',
  active: true,
  is_single_use: false,
})

const formErrors = reactive({ code: '', type: '', value: '', max_uses: '', start_date: '', expires_at: '' })

const filteredCodes = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return codes.value.filter((code) => {
    if (filters.type !== 'all' && code.type !== filters.type) return false
    if (filters.state !== 'all' && codeStatusKey(code) !== filters.state) return false
    if (!term) return true

    return [code.code, code.type_label, code.discount_type_name].join(' ').toLowerCase().includes(term)
  })
})

const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.type !== 'all'].filter(Boolean).length)

const discountStats = computed(() => [
  { key: 'total', label: 'Total códigos', value: codes.value.length, icon: 'fas fa-tags', color: 'primary' },
  { key: 'active', label: 'Activos', value: codes.value.filter((code) => codeStatusKey(code) === 'active').length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'expired', label: 'Vencidos', value: codes.value.filter((code) => codeStatusKey(code) === 'expired').length, icon: 'fas fa-calendar-times', color: 'warning' },
  { key: 'single', label: 'Uso único', value: codes.value.filter((code) => code.is_single_use).length, icon: 'fas fa-user-shield', color: 'info' },
])

function resetForm() {
  form.code = ''
  form.type = 'percent'
  form.value = 10
  form.max_uses = null
  form.start_date = ''
  form.expires_at = ''
  form.active = true
  form.is_single_use = false
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
  filters.type = 'all'
}

function openCreateModal() {
  editingCodeId.value = null
  resetForm()
  showEditorModal.value = true
}

function openEditModal(code) {
  editingCodeId.value = code.id
  clearErrors()
  form.code = code.code || ''
  form.type = code.type || 'percent'
  form.value = Number(code.value || 0)
  form.max_uses = code.max_uses ?? null
  form.start_date = normalizeDateTimeInput(code.start_date)
  form.expires_at = normalizeDateTimeInput(code.expires_at)
  form.active = Boolean(code.active)
  form.is_single_use = Boolean(code.is_single_use)
  showEditorModal.value = true
}

function closeEditorModal() {
  showEditorModal.value = false
  editingCodeId.value = null
  resetForm()
}

function openDetailModal(code) {
  selectedCode.value = code
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedCode.value = null
  showDetailModal.value = false
}

function openEditFromDetail() {
  if (!selectedCode.value) return
  const current = selectedCode.value
  closeDetailModal()
  openEditModal(current)
}

function handleCodeInput() {
  form.code = form.code.toUpperCase().replace(/\s+/g, '')
  validateField('code')
}

function validateField(field) {
  switch (field) {
    case 'code':
      formErrors.code = /^[A-Z0-9_-]{4,20}$/.test(form.code) ? '' : 'Usa entre 4 y 20 caracteres en mayúsculas, números o guiones.'
      break
    case 'type':
      formErrors.type = form.type ? '' : 'Selecciona el tipo de descuento.'
      break
    case 'value':
      formErrors.value = Number(form.value) > 0 ? '' : 'El valor del descuento debe ser mayor que cero.'
      if (!formErrors.value && form.type === 'percent' && Number(form.value) > 100) formErrors.value = 'El porcentaje no puede superar el 100%.'
      break
    case 'max_uses':
      formErrors.max_uses = form.max_uses === null || form.max_uses === '' || Number(form.max_uses) > 0 ? '' : 'El máximo de usos debe ser mayor que cero.'
      break
    case 'start_date':
    case 'expires_at':
      formErrors.start_date = ''
      formErrors.expires_at = ''
      if (form.start_date && form.expires_at && new Date(form.expires_at) <= new Date(form.start_date)) {
        formErrors.expires_at = 'La expiración debe ser posterior al inicio.'
      }
      break
    default:
      break
  }
}

function validateForm() {
  validateField('code')
  validateField('type')
  validateField('value')
  validateField('max_uses')
  validateField('start_date')
  return Object.values(formErrors).every((value) => !value)
}

async function loadCodes() {
  loading.value = true
  try {
    const { data } = await discountHttp.get('/admin/discount-codes')
    codes.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    codes.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los códigos.') })
  } finally {
    loading.value = false
  }
}

async function saveCode() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = {
    code: form.code,
    type: form.type,
    value: Number(form.value),
    max_uses: form.max_uses || null,
    active: form.active,
    is_single_use: form.is_single_use,
    start_date: form.start_date || null,
    expires_at: form.expires_at || null,
  }

  try {
    if (editingCodeId.value) {
      await discountHttp.put(`/admin/discount-codes/${editingCodeId.value}`, payload)
      showSnackbar({ type: 'success', message: 'Código actualizado correctamente.' })
    } else {
      await discountHttp.post('/admin/discount-codes', payload)
      showSnackbar({ type: 'success', message: 'Código creado correctamente.' })
    }

    closeEditorModal()
    await loadCodes()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el código.') })
  }
}

function confirmDeleteCode(code) {
  showAlert({
    type: 'warning',
    title: 'Eliminar código',
    message: `Vas a eliminar el código ${code.code}. Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await discountHttp.delete(`/admin/discount-codes/${code.id}`)
            showSnackbar({ type: 'success', message: 'Código eliminado correctamente.' })
            if (selectedCode.value?.id === code.id) closeDetailModal()
            await loadCodes()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el código.') })
          }
        },
      },
    ],
  })
}

function exportCodes() {
  const rows = filteredCodes.value.map((code) => [code.code, code.type_label, formatDiscountValue(code), code.times_used, code.max_uses || '∞', codeStatusLabel(code), code.start_date || '', code.expires_at || ''])
  const csv = [['Código', 'Tipo', 'Valor', 'Usados', 'Máximo', 'Estado', 'Inicio', 'Expira'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('codigos-descuento.csv', csv)
}

function codeStatusKey(code) {
  if (!code.active) return 'inactive'
  if (code.expires_at && new Date(code.expires_at) < new Date()) return 'expired'
  if (code.is_single_use) return 'single-use'
  return 'active'
}

function codeStatusLabel(code) {
  return { active: 'Activo', inactive: 'Inactivo', expired: 'Vencido', 'single-use': 'Uso único' }[codeStatusKey(code)]
}

function codeStatusClass(code) {
  return { active: 'active', inactive: 'rejected', expired: 'cancelled', 'single-use': 'info' }[codeStatusKey(code)]
}

function formatDiscountValue(code) {
  return code.type === 'percent' ? `${Number(code.value || 0)}%` : formatCurrency(code.value || 0)
}

function remainingUsesLabel(code) {
  const remaining = Number(code.max_uses || 0) - Number(code.times_used || 0)
  return remaining > 0 ? `${remaining} usos disponibles` : 'Sin cupos disponibles'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  return new Date(value).toLocaleString('es-CO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function normalizeDateTimeInput(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const year = date.getFullYear()
  const month = `${date.getMonth() + 1}`.padStart(2, '0')
  const day = `${date.getDate()}`.padStart(2, '0')
  const hours = `${date.getHours()}`.padStart(2, '0')
  const minutes = `${date.getMinutes()}`.padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
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

onMounted(loadCodes)
</script>

<style scoped>
.discount-code-pill {
  display: inline-flex;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  background: rgba(15, 122, 191, 0.08);
  color: var(--admin-primary);
}

.discount-preview-card {
  margin-top: 1rem;
  border: 1px solid rgba(15, 122, 191, 0.12);
}

.discount-hero-card h3,
.discount-preview-card h3 {
  font-size: 2.6rem;
  letter-spacing: 0.08em;
}
</style>