<template>
  <div class="admin-announcements-page">
    <AdminPageHeader
      icon="fas fa-bullhorn"
      title="Anuncios"
      subtitle="Gestiona anuncios con búsqueda, validación, previsualización y control centralizado de estados."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Anuncios' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportAnnouncements">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo anuncio
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="announcementStats" />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-filter"></i>
            <h3>Búsqueda y estado</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input
            v-model.trim="filters.search"
            type="text"
            class="search-input"
            placeholder="Buscar por título, mensaje o botón..."
          >
          <button v-if="filters.search" type="button" class="search-clear" @click="filters.search = ''">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--announcements">
          <div class="filter-group">
            <label for="announcement-state"><i class="fas fa-signal"></i> Estado</label>
            <select id="announcement-state" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="scheduled">Programados</option>
              <option value="expired">Vencidos</option>
              <option value="inactive">Inactivos</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="announcement-type"><i class="fas fa-shapes"></i> Tipo</label>
            <select id="announcement-type" v-model="filters.type">
              <option value="all">Todos</option>
              <option value="top_bar">Barra superior</option>
              <option value="promo_banner">Banner promocional</option>
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
        <i class="fas fa-list-ul"></i>
        <p>Mostrando {{ filteredAnnouncements.length }} anuncios</p>
      </div>
      <div class="quick-actions">
        <span class="results-note" :class="{ 'results-note--warning': !canCreateAnnouncement }">
          {{ canCreateAnnouncement ? 'Aún puedes crear anuncios adicionales.' : 'Límite alcanzado: 2 anuncios.' }}
        </span>
      </div>
    </div>

    <AdminCard title="Bandeja de anuncios" icon="fas fa-bullhorn" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="4" :columns="['thumb', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredAnnouncements.length === 0"
        icon="fas fa-bullhorn"
        title="Sin anuncios"
        description="No hay anuncios que coincidan con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table announcements-table">
          <thead>
            <tr>
              <th>Vista</th>
              <th>Detalles</th>
              <th>Tipo</th>
              <th>Prioridad</th>
              <th>Fechas</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="announcement in filteredAnnouncements" :key="announcement.id">
              <td>
                <button type="button" class="announcement-thumb" @click="openDetailModal(announcement)">
                  <img
                    v-if="announcement.image"
                    :src="resolveMediaUrl(announcement.image, 'banner')"
                    :alt="announcement.title"
                    @error="handleMediaError($event, announcement.image, 'banner')"
                  >
                  <i v-else :class="['fas', announcement.icon || 'fa-bullhorn']"></i>
                </button>
              </td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ announcement.title }}</strong>
                  <span>{{ truncateText(announcement.message || announcement.content, 78) }}</span>
                </div>
              </td>
              <td>
                <span class="status-badge info">{{ typeLabel(announcement.type) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="priorityClass(announcement.priority)">
                  {{ priorityLabel(announcement.priority) }}
                </span>
              </td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ announcement.start_date ? formatDateTime(announcement.start_date) : 'Inmediato' }}</strong>
                  <span>{{ announcement.end_date ? `Finaliza ${formatDateTime(announcement.end_date)}` : 'Sin fecha de cierre' }}</span>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="announcementStatusClass(announcement)">
                  {{ announcementStatusLabel(announcement) }}
                </span>
              </td>
              <td>
                <div class="entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(announcement)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar anuncio" @click="openEditModal(announcement)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar anuncio" @click="confirmDeleteAnnouncement(announcement)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="selectedAnnouncement ? selectedAnnouncement.title : 'Detalle del anuncio'" max-width="1080px" @close="closeDetailModal">
      <template v-if="selectedAnnouncement">
        <div class="announcement-detail-grid admin-detail-grid admin-detail-grid--featured">
          <div class="announcement-preview-card">
            <div class="announcement-preview-card__visual" :style="previewCardStyle(selectedAnnouncement)">
              <img
                v-if="selectedAnnouncement.image"
                :src="resolveMediaUrl(selectedAnnouncement.image, 'banner')"
                :alt="selectedAnnouncement.title"
                @error="handleMediaError($event, selectedAnnouncement.image, 'banner')"
              >
              <div class="announcement-preview-card__content">
                <span class="announcement-preview-card__type">
                  <i :class="['fas', selectedAnnouncement.icon || 'fa-bullhorn']"></i>
                  {{ typeLabel(selectedAnnouncement.type) }}
                </span>
                <h3>{{ selectedAnnouncement.title }}</h3>
                <p>{{ selectedAnnouncement.message || 'Sin mensaje configurado.' }}</p>
                <p v-if="selectedAnnouncement.subtitle" class="announcement-preview-card__subtitle">{{ selectedAnnouncement.subtitle }}</p>
                <a v-if="selectedAnnouncement.button_text" class="announcement-preview-card__button" href="#" @click.prevent>
                  {{ selectedAnnouncement.button_text }}
                </a>
              </div>
            </div>
          </div>

          <div>
            <AdminCard title="Configuración" icon="fas fa-cogs">
              <div class="summary-stack">
                <div class="summary-row"><span>Estado</span><strong>{{ announcementStatusLabel(selectedAnnouncement) }}</strong></div>
                <div class="summary-row"><span>Prioridad</span><strong>{{ priorityLabel(selectedAnnouncement.priority) }}</strong></div>
                <div class="summary-row"><span>Inicio</span><strong>{{ selectedAnnouncement.start_date ? formatDateTime(selectedAnnouncement.start_date) : 'Inmediato' }}</strong></div>
                <div class="summary-row"><span>Fin</span><strong>{{ selectedAnnouncement.end_date ? formatDateTime(selectedAnnouncement.end_date) : 'Sin cierre' }}</strong></div>
                <div class="summary-row"><span>Botón</span><strong>{{ selectedAnnouncement.button_text || 'No configurado' }}</strong></div>
                <div class="summary-row summary-row--stack"><span>Enlace</span><strong>{{ selectedAnnouncement.button_link || 'Sin enlace' }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Mensaje completo" icon="fas fa-align-left" style="margin-top: 1.2rem;">
              <div class="detail-copy-block">
                <p>{{ selectedAnnouncement.message || 'Sin mensaje.' }}</p>
                <p v-if="selectedAnnouncement.subtitle"><strong>Subtítulo:</strong> {{ selectedAnnouncement.subtitle }}</p>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedAnnouncement" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar anuncio
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingAnnouncementId ? 'Editar anuncio' : 'Nuevo anuncio'" max-width="920px" @close="closeEditorModal">
      <div class="editor-grid admin-editor-grid">
        <div>
          <div class="form-group">
            <label for="announcement-type-editor">Tipo *</label>
            <select id="announcement-type-editor" v-model="form.type" class="form-control" @change="validateField('type')">
              <option value="top_bar">Barra superior</option>
              <option value="promo_banner">Banner promocional</option>
            </select>
            <p v-if="formErrors.type" class="form-error">{{ formErrors.type }}</p>
          </div>

          <div class="form-group">
            <label for="announcement-title">Título *</label>
            <input id="announcement-title" v-model.trim="form.title" type="text" class="form-control" :class="{ 'is-invalid': formErrors.title }" @input="validateField('title')">
            <p v-if="formErrors.title" class="form-error">{{ formErrors.title }}</p>
          </div>

          <div class="form-group">
            <label for="announcement-message">Mensaje *</label>
            <textarea id="announcement-message" v-model.trim="form.message" rows="4" class="form-control" :class="{ 'is-invalid': formErrors.message }" @input="validateField('message')"></textarea>
            <p v-if="formErrors.message" class="form-error">{{ formErrors.message }}</p>
          </div>

          <div class="form-group">
            <label for="announcement-subtitle">Subtítulo</label>
            <input id="announcement-subtitle" v-model.trim="form.subtitle" type="text" class="form-control" @input="validateField('subtitle')">
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="announcement-button-text">Texto del botón</label>
              <input id="announcement-button-text" v-model.trim="form.button_text" type="text" class="form-control" @input="validateField('button_text')">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="announcement-link">URL o ruta</label>
              <input id="announcement-link" v-model.trim="form.button_link" type="text" class="form-control" :class="{ 'is-invalid': formErrors.button_link }" @input="validateField('button_link')">
              <p v-if="formErrors.button_link" class="form-error">{{ formErrors.button_link }}</p>
            </div>
          </div>
        </div>

        <div>
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="announcement-icon">Icono *</label>
              <select id="announcement-icon" v-model="form.icon" class="form-control" :class="{ 'is-invalid': formErrors.icon }" @change="validateField('icon')">
                <option value="fa-bullhorn">Megáfono</option>
                <option value="fa-tags">Ofertas</option>
                <option value="fa-percent">Descuento</option>
                <option value="fa-truck">Envío</option>
                <option value="fa-shipping-fast">Envío rápido</option>
                <option value="fa-star">Destacado</option>
                <option value="fa-clock">Urgencia</option>
              </select>
              <p v-if="formErrors.icon" class="form-error">{{ formErrors.icon }}</p>
            </div>

            <div class="form-group" style="flex: 1;">
              <label for="announcement-priority">Prioridad *</label>
              <input id="announcement-priority" v-model.number="form.priority" type="number" min="0" max="100" class="form-control" :class="{ 'is-invalid': formErrors.priority }" @input="validateField('priority')">
              <p v-if="formErrors.priority" class="form-error">{{ formErrors.priority }}</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="announcement-start">Inicio</label>
              <input id="announcement-start" v-model="form.start_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.start_date }" @change="validateField('start_date')">
              <p v-if="formErrors.start_date" class="form-error">{{ formErrors.start_date }}</p>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="announcement-end">Fin</label>
              <input id="announcement-end" v-model="form.end_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.end_date }" @change="validateField('end_date')">
              <p v-if="formErrors.end_date" class="form-error">{{ formErrors.end_date }}</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="announcement-bg">Color de fondo</label>
              <input id="announcement-bg" v-model.trim="form.background_color" type="text" class="form-control" placeholder="#0f7abf">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="announcement-text-color">Color de texto</label>
              <input id="announcement-text-color" v-model.trim="form.text_color" type="text" class="form-control" placeholder="#ffffff">
            </div>
          </div>

          <div class="form-group">
            <label>Imagen</label>
            <div class="upload-box" @click="openImagePicker">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>{{ imagePreviewUrl ? 'Cambiar imagen del anuncio' : 'Selecciona una imagen opcional para el anuncio' }}</p>
              <small>JPG, PNG o WEBP. Máximo 3 MB.</small>
            </div>
            <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
            <div v-if="imagePreviewUrl" class="editor-preview-image">
              <img :src="imagePreviewUrl" alt="Vista previa del anuncio">
              <button type="button" class="btn btn-secondary btn-sm" @click="clearSelectedImage">Quitar imagen</button>
            </div>
          </div>

          <div class="form-group form-group--toggle">
            <label class="toggle-label">
              <input v-model="form.is_active" type="checkbox">
              Publicar anuncio inmediatamente
            </label>
          </div>
        </div>
      </div>

      <div class="editor-live-preview">
        <p class="editor-live-preview__label">Previsualización</p>
        <div class="announcement-preview-card__visual" :style="previewCardStyle(formPreview)">
          <img v-if="imagePreviewUrl" :src="imagePreviewUrl" alt="Vista previa">
          <div class="announcement-preview-card__content">
            <span class="announcement-preview-card__type">
              <i :class="['fas', form.icon || 'fa-bullhorn']"></i>
              {{ typeLabel(form.type) }}
            </span>
            <h3>{{ form.title || 'Título del anuncio' }}</h3>
            <p>{{ form.message || 'Aquí verás el mensaje principal del anuncio.' }}</p>
            <p v-if="form.subtitle" class="announcement-preview-card__subtitle">{{ form.subtitle }}</p>
            <a v-if="form.button_text" class="announcement-preview-card__button" href="#" @click.prevent>
              {{ form.button_text }}
            </a>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveAnnouncement">
          <i class="fas fa-save"></i>
          {{ editingAnnouncementId ? 'Guardar cambios' : 'Crear anuncio' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { notificationHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl, handleMediaError } from '../../../utils/media'
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
const announcements = ref([])
const selectedAnnouncement = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingAnnouncementId = ref(null)
const imageInputRef = ref(null)
const selectedImageFile = ref(null)
const imagePreviewUrl = ref('')

const filters = reactive({
  search: '',
  state: 'all',
  type: 'all',
})

const form = reactive({
  type: 'top_bar',
  title: '',
  message: '',
  subtitle: '',
  button_text: '',
  button_link: '',
  icon: 'fa-bullhorn',
  priority: 0,
  background_color: '#0f7abf',
  text_color: '#ffffff',
  start_date: '',
  end_date: '',
  is_active: true,
  image: '',
})

const formErrors = reactive({
  type: '',
  title: '',
  message: '',
  icon: '',
  priority: '',
  button_link: '',
  start_date: '',
  end_date: '',
})

const filteredAnnouncements = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return announcements.value.filter((announcement) => {
    if (filters.type !== 'all' && announcement.type !== filters.type) return false
    if (filters.state !== 'all' && announcementStatusKey(announcement) !== filters.state) return false

    if (!term) return true

    const haystack = [announcement.title, announcement.message, announcement.subtitle, announcement.button_text, announcement.button_link]
      .join(' ')
      .toLowerCase()

    return haystack.includes(term)
  })
})

const canCreateAnnouncement = computed(() => announcements.value.length < 2)
const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.type !== 'all'].filter(Boolean).length)

const announcementStats = computed(() => [
  { key: 'total', label: 'Total anuncios', value: announcements.value.length, icon: 'fas fa-bullhorn', color: 'primary' },
  { key: 'active', label: 'Activos', value: announcements.value.filter((item) => announcementStatusKey(item) === 'active').length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'scheduled', label: 'Programados', value: announcements.value.filter((item) => announcementStatusKey(item) === 'scheduled').length, icon: 'fas fa-clock', color: 'warning' },
  { key: 'banners', label: 'Banners promo', value: announcements.value.filter((item) => item.type === 'promo_banner').length, icon: 'fas fa-image', color: 'info' },
])

const formPreview = computed(() => ({ ...form, image: imagePreviewUrl.value || form.image }))

function resetForm() {
  form.type = 'top_bar'
  form.title = ''
  form.message = ''
  form.subtitle = ''
  form.button_text = ''
  form.button_link = ''
  form.icon = 'fa-bullhorn'
  form.priority = 0
  form.background_color = '#0f7abf'
  form.text_color = '#ffffff'
  form.start_date = ''
  form.end_date = ''
  form.is_active = true
  form.image = ''
  clearSelectedImage()
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
  if (!canCreateAnnouncement.value) {
    showAlert({ type: 'warning', title: 'Límite alcanzado', message: 'Solo puedes tener hasta 2 anuncios activos. Elimina uno antes de crear otro.' })
    return
  }

  editingAnnouncementId.value = null
  resetForm()
  showEditorModal.value = true
}

function openEditModal(announcement) {
  editingAnnouncementId.value = announcement.id
  clearErrors()
  selectedImageFile.value = null
  imagePreviewUrl.value = announcement.image ? resolveMediaUrl(announcement.image, 'banner') : ''
  form.type = announcement.type || 'top_bar'
  form.title = announcement.title || ''
  form.message = announcement.message || announcement.content || ''
  form.subtitle = announcement.subtitle || ''
  form.button_text = announcement.button_text || ''
  form.button_link = announcement.button_link || announcement.url || ''
  form.icon = announcement.icon || 'fa-bullhorn'
  form.priority = Number(announcement.priority || 0)
  form.background_color = announcement.background_color || '#0f7abf'
  form.text_color = announcement.text_color || '#ffffff'
  form.start_date = normalizeDateTimeInput(announcement.start_date)
  form.end_date = normalizeDateTimeInput(announcement.end_date)
  form.is_active = Boolean(announcement.is_active ?? announcement.active)
  form.image = announcement.image || ''
  showEditorModal.value = true
}

function closeEditorModal() {
  showEditorModal.value = false
  editingAnnouncementId.value = null
  resetForm()
}

function openDetailModal(announcement) {
  selectedAnnouncement.value = announcement
  showDetailModal.value = true
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedAnnouncement.value = null
}

function openEditFromDetail() {
  if (!selectedAnnouncement.value) return
  const current = selectedAnnouncement.value
  closeDetailModal()
  openEditModal(current)
}

function validateField(field) {
  switch (field) {
    case 'type':
      formErrors.type = form.type ? '' : 'Selecciona el tipo de anuncio.'
      break
    case 'title':
      formErrors.title = form.title.trim().length >= 4 ? '' : 'El título debe tener al menos 4 caracteres.'
      break
    case 'message':
      formErrors.message = form.message.trim().length >= 10 ? '' : 'El mensaje debe tener al menos 10 caracteres.'
      break
    case 'icon':
      formErrors.icon = form.icon ? '' : 'Selecciona un icono.'
      break
    case 'priority':
      formErrors.priority = Number.isFinite(Number(form.priority)) && Number(form.priority) >= 0 && Number(form.priority) <= 100 ? '' : 'La prioridad debe estar entre 0 y 100.'
      break
    case 'button_link':
      formErrors.button_link = isValidLink(form.button_link) ? '' : 'Usa una ruta interna o una URL válida.'
      break
    case 'start_date':
    case 'end_date':
      formErrors.start_date = ''
      formErrors.end_date = ''
      if (form.start_date && form.end_date && new Date(form.end_date) < new Date(form.start_date)) {
        formErrors.end_date = 'La fecha final debe ser posterior a la fecha inicial.'
      }
      break
    default:
      break
  }
}

function validateForm() {
  validateField('type')
  validateField('title')
  validateField('message')
  validateField('icon')
  validateField('priority')
  validateField('button_link')
  validateField('start_date')
  return Object.values(formErrors).every((value) => !value)
}

async function loadAnnouncements() {
  loading.value = true
  try {
    const { data } = await notificationHttp.get('/admin/announcements')
    announcements.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    announcements.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los anuncios.') })
  } finally {
    loading.value = false
  }
}

async function saveAnnouncement() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = new FormData()
  payload.append('type', form.type)
  payload.append('title', form.title.trim())
  payload.append('message', form.message.trim())
  payload.append('subtitle', form.subtitle.trim())
  payload.append('button_text', form.button_text.trim())
  payload.append('button_link', form.button_link.trim())
  payload.append('icon', form.icon)
  payload.append('priority', String(Number(form.priority || 0)))
  payload.append('background_color', form.background_color.trim())
  payload.append('text_color', form.text_color.trim())
  payload.append('is_active', form.is_active ? '1' : '0')
  if (form.start_date) payload.append('start_date', form.start_date)
  if (form.end_date) payload.append('end_date', form.end_date)
  if (selectedImageFile.value) payload.append('image_file', selectedImageFile.value)

  try {
    if (editingAnnouncementId.value) {
      payload.append('_method', 'PUT')
      await notificationHttp.post(`/admin/announcements/${editingAnnouncementId.value}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })
      showSnackbar({ type: 'success', message: 'Anuncio actualizado correctamente.' })
    } else {
      await notificationHttp.post('/admin/announcements', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
      showSnackbar({ type: 'success', message: 'Anuncio creado correctamente.' })
    }

    closeEditorModal()
    await loadAnnouncements()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el anuncio.') })
  }
}

function confirmDeleteAnnouncement(announcement) {
  showAlert({
    type: 'warning',
    title: 'Eliminar anuncio',
    message: `Vas a eliminar "${announcement.title}". Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await notificationHttp.delete(`/admin/announcements/${announcement.id}`)
            showSnackbar({ type: 'success', message: 'Anuncio eliminado correctamente.' })
            if (selectedAnnouncement.value?.id === announcement.id) closeDetailModal()
            await loadAnnouncements()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el anuncio.') })
          }
        },
      },
    ],
  })
}

function exportAnnouncements() {
  const rows = filteredAnnouncements.value.map((announcement) => [announcement.id, announcement.title, typeLabel(announcement.type), priorityLabel(announcement.priority), announcementStatusLabel(announcement), announcement.start_date || '', announcement.end_date || '', announcement.button_link || ''])
  const csv = [['ID', 'Título', 'Tipo', 'Prioridad', 'Estado', 'Inicio', 'Fin', 'Enlace'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('anuncios-admin.csv', csv)
}

function openImagePicker() {
  imageInputRef.value?.click()
}

function onImageSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  selectedImageFile.value = file
  if (imagePreviewUrl.value.startsWith('blob:')) URL.revokeObjectURL(imagePreviewUrl.value)
  imagePreviewUrl.value = URL.createObjectURL(file)
}

function clearSelectedImage() {
  selectedImageFile.value = null
  if (imagePreviewUrl.value.startsWith('blob:')) URL.revokeObjectURL(imagePreviewUrl.value)
  imagePreviewUrl.value = form.image ? resolveMediaUrl(form.image, 'banner') : ''
  if (imageInputRef.value) imageInputRef.value.value = ''
}

function typeLabel(type) {
  return type === 'promo_banner' ? 'Banner promocional' : 'Barra superior'
}

function priorityLabel(priority) {
  const numericPriority = Number(priority || 0)
  if (numericPriority >= 8) return 'Alta'
  if (numericPriority >= 4) return 'Media'
  return 'Normal'
}

function priorityClass(priority) {
  const numericPriority = Number(priority || 0)
  if (numericPriority >= 8) return 'cancelled'
  if (numericPriority >= 4) return 'pending'
  return 'active'
}

function announcementStatusKey(announcement) {
  if (!announcement.is_active && !announcement.active) return 'inactive'
  const now = Date.now()
  const startsAt = announcement.start_date ? new Date(announcement.start_date).getTime() : null
  const endsAt = announcement.end_date ? new Date(announcement.end_date).getTime() : null
  if (startsAt && startsAt > now) return 'scheduled'
  if (endsAt && endsAt < now) return 'expired'
  return 'active'
}

function announcementStatusLabel(announcement) {
  return { active: 'Activo', scheduled: 'Programado', expired: 'Vencido', inactive: 'Inactivo' }[announcementStatusKey(announcement)]
}

function announcementStatusClass(announcement) {
  return { active: 'active', scheduled: 'pending', expired: 'cancelled', inactive: 'rejected' }[announcementStatusKey(announcement)]
}

function previewCardStyle(source) {
  return { backgroundColor: source.background_color || '#0f7abf', color: source.text_color || '#ffffff' }
}

function truncateText(value, maxLength = 80) {
  const text = String(value || '').trim()
  if (text.length <= maxLength) return text || 'Sin mensaje'
  return `${text.slice(0, maxLength)}...`
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

function isValidLink(value) {
  const clean = String(value || '').trim()
  if (!clean) return true
  return clean.startsWith('/') || /^https?:\/\//i.test(clean)
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

onMounted(loadAnnouncements)

onBeforeUnmount(() => {
  if (imagePreviewUrl.value.startsWith('blob:')) URL.revokeObjectURL(imagePreviewUrl.value)
})
</script>

<style scoped>
.announcement-thumb {
  width: 6rem;
  height: 6rem;
  border: 1px solid rgba(15, 122, 191, 0.16);
  border-radius: 1rem;
  background: #f3f7fb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: pointer;
}

.announcement-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.announcement-thumb i {
  font-size: 1.8rem;
  color: var(--admin-primary);
}

.announcement-preview-card__visual {
  min-height: 30rem;
  border-radius: 1.6rem;
  padding: 1.8rem;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: flex-end;
}

.announcement-preview-card__visual img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.22;
}

.announcement-preview-card__content {
  position: relative;
  display: grid;
  gap: 0.8rem;
  max-width: 48rem;
}

.announcement-preview-card__type {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.2rem;
  font-weight: 700;
  padding: 0.45rem 0.9rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.15);
}

.announcement-preview-card__content h3 {
  margin: 0;
  font-size: 2.2rem;
}

.announcement-preview-card__content p {
  margin: 0;
  font-size: 1.45rem;
  line-height: 1.6;
}

.announcement-preview-card__subtitle {
  opacity: 0.85;
}

.announcement-preview-card__button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 16rem;
  padding: 1rem 1.5rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  color: #102236;
  font-weight: 700;
  text-decoration: none;
}

.editor-live-preview {
  margin-top: 1.2rem;
}

.editor-live-preview__label {
  margin: 0 0 0.8rem;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--admin-text-soft);
}

.upload-box {
  border: 1px dashed rgba(15, 122, 191, 0.34);
  border-radius: 1.2rem;
  padding: 1.6rem;
  text-align: center;
  cursor: pointer;
  background: #f8fbfe;
  display: grid;
  gap: 0.4rem;
}

.upload-box i {
  font-size: 2rem;
  color: var(--admin-primary);
}

.upload-box p,
.upload-box small {
  margin: 0;
}

.editor-preview-image {
  margin-top: 1rem;
  display: grid;
  gap: 0.8rem;
}

.editor-preview-image img {
  width: 100%;
  max-height: 18rem;
  border-radius: 1rem;
  object-fit: cover;
}

.detail-copy-block {
  display: grid;
  gap: 0.8rem;
}

.detail-copy-block p {
  margin: 0;
  font-size: 1.4rem;
  line-height: 1.6;
}

.results-note {
  font-size: 1.3rem;
  color: var(--admin-text-soft);
}

.results-note--warning {
  color: var(--admin-danger);
  font-weight: 700;
}

</style>