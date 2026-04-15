<template>
  <div class="admin-sliders-page">
    <AdminPageHeader
      icon="fas fa-images"
      title="Sliders"
      subtitle="Administra el carrusel principal con el mismo flujo operativo del panel y feedback centralizado."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Sliders' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo slider
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="sliderStats" />

    <AdminCard title="Bandeja de sliders" icon="fas fa-images" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="4" :columns="['line', 'thumb', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="sliders.length === 0"
        icon="fas fa-images"
        title="Sin sliders"
        description="Agrega el primer slide del carrusel para mostrar promociones, colecciones o accesos directos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table sliders-table">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Vista previa</th>
              <th>Contenido</th>
              <th>Enlace</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="slider in sliders"
              :key="slider.id"
              draggable="true"
              class="slider-row"
              :class="{
                'slider-row--dragging': dragState.draggingId === slider.id,
                'slider-row--over': dragState.overId === slider.id && dragState.draggingId !== slider.id,
              }"
              @dragstart="onRowDragStart(slider, $event)"
              @dragenter.prevent="onRowDragEnter(slider)"
              @dragover.prevent="onRowDragOver(slider)"
              @drop.prevent="onRowDrop(slider)"
              @dragend="onRowDragEnd"
            >
              <td class="slider-td-order">
                <div class="slider-order-cell">
                  <button class="slider-drag-handle" type="button" title="Arrastra para mover" aria-label="Arrastra para mover">
                    <i class="fas fa-grip-vertical"></i>
                  </button>
                  <span class="slider-order-num">{{ slider.sort_order }}</span>
                </div>
              </td>
              <td>
                <div class="admin-slider-thumb">
                  <img :src="resolveMediaUrl(slider.image, 'slider')" :alt="slider.title" @error="handleMediaError($event, slider.image, 'slider')">
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ slider.title }}</strong>
                  <span>{{ slider.subtitle || 'Sin subtítulo configurado' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ slider.link || '/tienda' }}</strong>
                  <span>{{ slider.link ? 'Destino configurado' : 'Redirige al catálogo general' }}</span>
                </div>
              </td>
              <td>
                <button class="status-badge status-badge--button" :class="slider.active ? 'active' : 'rejected'" type="button" @click="toggleSliderStatus(slider)">
                  {{ slider.active ? 'Activo' : 'Inactivo' }}
                </button>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Editar slider" @click="openEditModal(slider)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar slider" @click="confirmDeleteSlider(slider)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showModal" :title="editingSliderId ? 'Editar slider' : 'Nuevo slider'" max-width="920px" @close="closeModal">
      <div class="admin-editor-grid slider-editor-grid">
        <div>
          <div class="form-group">
            <label for="slider-title">Título *</label>
            <input id="slider-title" v-model.trim="form.title" type="text" class="form-control" :class="{ 'is-invalid': formErrors.title }" @input="validateField('title')">
            <p v-if="formErrors.title" class="form-error">{{ formErrors.title }}</p>
          </div>

          <div class="form-group">
            <label for="slider-subtitle">Subtítulo</label>
            <input id="slider-subtitle" v-model.trim="form.subtitle" type="text" class="form-control" @input="validateField('subtitle')">
          </div>

          <div class="form-group">
            <label for="slider-link">URL o ruta</label>
            <input id="slider-link" v-model.trim="form.link" type="text" class="form-control" :class="{ 'is-invalid': formErrors.link }" placeholder="/tienda o https://..." @input="validateField('link')">
            <p v-if="formErrors.link" class="form-error">{{ formErrors.link }}</p>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="slider-order">Orden *</label>
              <input id="slider-order" v-model.number="form.sort_order" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.sort_order }" @input="validateField('sort_order')">
              <p v-if="formErrors.sort_order" class="form-error">{{ formErrors.sort_order }}</p>
            </div>
            <div class="form-group form-group--toggle" style="flex: 1; justify-content: flex-end;">
              <label class="toggle-label">
                <input v-model="form.active" type="checkbox">
                Slider activo en el carrusel
              </label>
            </div>
          </div>
        </div>

        <div>
          <div class="form-group">
            <label>Imagen *</label>
            <div class="admin-upload-box" @click="openImagePicker">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>{{ imagePreviewUrl ? 'Cambiar imagen del slider' : 'Selecciona la imagen principal del slide' }}</p>
              <small>JPG, PNG o WEBP. Máximo 4 MB.</small>
            </div>
            <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
            <p v-if="formErrors.image" class="form-error">{{ formErrors.image }}</p>
            <div v-if="imagePreviewUrl" class="slider-preview-image">
              <img :src="imagePreviewUrl" alt="Vista previa del slider">
              <button class="btn btn-secondary btn-sm" type="button" @click="clearSelectedImage">Quitar imagen</button>
            </div>
          </div>

          <div class="slider-preview-card">
            <p class="slider-preview-card__label">Vista previa</p>
            <div class="slider-preview-card__surface">
              <img v-if="imagePreviewUrl" :src="imagePreviewUrl" alt="Preview">
              <div class="slider-preview-card__overlay">
                <h3>{{ form.title || 'Título del slider' }}</h3>
                <p>{{ form.subtitle || 'Añade un subtítulo para complementar el mensaje principal.' }}</p>
                <span class="slider-preview-card__cta">{{ form.link || '/tienda' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveSlider">
          <i class="fas fa-save"></i>
          {{ editingSliderId ? 'Guardar cambios' : 'Crear slider' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const processingOrder = ref(false)
const sliders = ref([])
const showModal = ref(false)
const editingSliderId = ref(null)
const imageInputRef = ref(null)
const selectedImageFile = ref(null)
const imagePreviewUrl = ref('')
const dragState = reactive({
  draggingId: null,
  overId: null,
})

const form = reactive({
  title: '',
  subtitle: '',
  link: '',
  image: '',
  sort_order: 0,
  active: true,
})

const formErrors = reactive({
  title: '',
  link: '',
  image: '',
  sort_order: '',
})

const sliderStats = computed(() => [
  { key: 'total', label: 'Total sliders', value: sliders.value.length, icon: 'fas fa-images', color: 'primary' },
  { key: 'active', label: 'Activos', value: sliders.value.filter((slider) => slider.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'inactive', label: 'Inactivos', value: sliders.value.filter((slider) => !slider.active).length, icon: 'fas fa-eye-slash', color: 'warning' },
  { key: 'last-order', label: 'Último orden', value: sliders.value.length ? Math.max(...sliders.value.map((slider) => Number(slider.sort_order || 0))) : 0, icon: 'fas fa-sort-numeric-down', color: 'info' },
])

function normalizeSlider(slider) {
  return {
    id: Number(slider?.id || 0),
    title: slider?.title || '',
    subtitle: slider?.subtitle || '',
    image: slider?.image_url || slider?.image || '',
    link: slider?.link_url || slider?.link || '',
    sort_order: Number(slider?.sort_order ?? slider?.order_position ?? 0),
    active: Boolean(slider?.active ?? slider?.is_active),
  }
}

function resetForm() {
  form.title = ''
  form.subtitle = ''
  form.link = ''
  form.image = ''
  form.sort_order = sliders.value.length
  form.active = true
  clearErrors()
  clearSelectedImage(false)
}

function clearErrors() {
  Object.keys(formErrors).forEach((key) => {
    formErrors[key] = ''
  })
}

function openCreateModal() {
  editingSliderId.value = null
  resetForm()
  showModal.value = true
}

function openEditModal(slider) {
  editingSliderId.value = slider.id
  clearErrors()
  form.title = slider.title
  form.subtitle = slider.subtitle
  form.link = slider.link
  form.image = slider.image
  form.sort_order = slider.sort_order
  form.active = slider.active
  selectedImageFile.value = null
  imagePreviewUrl.value = slider.image ? resolveMediaUrl(slider.image, 'slider') : ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingSliderId.value = null
  resetForm()
}

function openImagePicker() {
  imageInputRef.value?.click()
}

function onImageSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  selectedImageFile.value = file
  updatePreviewUrl(URL.createObjectURL(file))
  validateField('image')
}

function updatePreviewUrl(nextUrl) {
  if (imagePreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value)
  }
  imagePreviewUrl.value = nextUrl || ''
}

function clearSelectedImage(keepExisting = true) {
  selectedImageFile.value = null
  if (imageInputRef.value) {
    imageInputRef.value.value = ''
  }
  updatePreviewUrl(keepExisting && form.image ? resolveMediaUrl(form.image, 'slider') : '')
}

function validateField(field) {
  switch (field) {
    case 'title':
      formErrors.title = form.title.trim().length >= 3 ? '' : 'El título debe tener al menos 3 caracteres.'
      break
    case 'link':
      formErrors.link = isValidLink(form.link) ? '' : 'Usa una ruta interna o una URL válida.'
      break
    case 'image':
      formErrors.image = imagePreviewUrl.value ? '' : 'La imagen del slider es obligatoria.'
      break
    case 'sort_order':
      formErrors.sort_order = Number.isInteger(Number(form.sort_order)) && Number(form.sort_order) >= 0
        ? ''
        : 'El orden debe ser un número igual o mayor que cero.'
      break
    default:
      break
  }
}

function validateForm() {
  validateField('title')
  validateField('link')
  validateField('image')
  validateField('sort_order')
  return Object.values(formErrors).every((value) => !value)
}

async function loadSliders() {
  loading.value = true
  try {
    const { data } = await catalogHttp.get('/admin/sliders')
    const rows = Array.isArray(data?.data) ? data.data : []
    sliders.value = rows.map(normalizeSlider).sort((left, right) => left.sort_order - right.sort_order)
  } catch (error) {
    sliders.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los sliders.') })
  } finally {
    loading.value = false
  }
}

async function saveSlider() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = new FormData()
  payload.append('title', form.title.trim())
  payload.append('subtitle', form.subtitle.trim())
  payload.append('link_url', form.link.trim())
  payload.append('sort_order', String(Number(form.sort_order || 0)))
  payload.append('active', form.active ? '1' : '0')
  if (selectedImageFile.value) {
    payload.append('image_file', selectedImageFile.value)
  } else if (form.image) {
    payload.append('image_url', form.image)
  }

  try {
    if (editingSliderId.value) {
      await catalogHttp.put(`/admin/sliders/${editingSliderId.value}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })
      showSnackbar({ type: 'success', message: 'Slider actualizado correctamente.' })
    } else {
      await catalogHttp.post('/admin/sliders', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
      showSnackbar({ type: 'success', message: 'Slider creado correctamente.' })
    }

    closeModal()
    await loadSliders()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el slider.') })
  }
}

function createReorderPayload(orderedRows) {
  return orderedRows.map((slider, position) => ({
    id: slider.id,
    sort_order: position,
  }))
}

async function persistSliderOrder(orderedRows) {
  const payload = createReorderPayload(orderedRows)

  processingOrder.value = true
  try {
    await catalogHttp.post('/admin/sliders/reorder', { items: payload })
    sliders.value = orderedRows.map((slider, position) => ({ ...slider, sort_order: position }))
    showSnackbar({ type: 'success', message: 'Orden de sliders actualizado.' })
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo reordenar el carrusel.') })
    await loadSliders()
  } finally {
    processingOrder.value = false
  }
}

function reorderLocalRows(sourceId, targetId) {
  const sourceIndex = sliders.value.findIndex((item) => Number(item.id) === Number(sourceId))
  const targetIndex = sliders.value.findIndex((item) => Number(item.id) === Number(targetId))

  if (sourceIndex < 0 || targetIndex < 0 || sourceIndex === targetIndex) {
    return null
  }

  const reordered = [...sliders.value]
  const [current] = reordered.splice(sourceIndex, 1)
  reordered.splice(targetIndex, 0, current)

  return reordered
}

function onRowDragStart(slider, event) {
  if (processingOrder.value) {
    event.preventDefault()
    return
  }

  dragState.draggingId = slider.id
  dragState.overId = slider.id

  if (event?.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(slider.id))
  }
}

function onRowDragEnter(slider) {
  if (dragState.draggingId === null) return
  dragState.overId = slider.id
}

function onRowDragOver(slider) {
  if (dragState.draggingId === null) return
  dragState.overId = slider.id
}

async function onRowDrop(slider) {
  if (dragState.draggingId === null) return

  const reordered = reorderLocalRows(dragState.draggingId, slider.id)
  onRowDragEnd()
  if (!reordered) return

  await persistSliderOrder(reordered)
}

function onRowDragEnd() {
  dragState.draggingId = null
  dragState.overId = null
}

async function toggleSliderStatus(slider) {
  try {
    await catalogHttp.patch(`/admin/sliders/${slider.id}/status`, { active: !slider.active })
    slider.active = !slider.active
    showSnackbar({ type: 'success', message: slider.active ? 'Slider activado.' : 'Slider desactivado.' })
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo actualizar el estado del slider.') })
  }
}

function confirmDeleteSlider(slider) {
  showAlert({
    type: 'warning',
    title: 'Eliminar slider',
    message: `Vas a eliminar "${slider.title}". Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await catalogHttp.delete(`/admin/sliders/${slider.id}`)
            showSnackbar({ type: 'success', message: 'Slider eliminado correctamente.' })
            await loadSliders()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el slider.') })
          }
        },
      },
    ],
  })
}

function isValidLink(value) {
  const clean = String(value || '').trim()
  if (!clean) return true
  return clean.startsWith('/') || /^https?:\/\//i.test(clean)
}

function extractErrorMessage(error, fallback) {
  return error?.response?.data?.message || fallback
}

onMounted(loadSliders)

onBeforeUnmount(() => {
  if (imagePreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value)
  }
})
</script>

<style scoped>
/* Estilos propios de sliders */
.sliders-table th,
.sliders-table td {
  vertical-align: middle;
}

/* Columna de orden: ancho mínimo y centrado */
.slider-td-order {
  width: 7rem;
  text-align: center;
}

/* Celda compacta: handle + número alineados horizontalmente */
.slider-order-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  justify-content: center;
  width: 100%;
}

.slider-order-num {
  font-weight: 700;
  font-size: 1.4rem;
  color: var(--admin-text-dark);
  min-width: 1.4rem;
  text-align: center;
}

.slider-drag-handle {
  width: 3.2rem;
  height: 3.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  border: 1px solid rgba(0, 119, 182, 0.2);
  background: rgba(0, 119, 182, 0.1);
  color: var(--admin-primary);
  cursor: grab;
}

.slider-row--dragging {
  opacity: 0.55;
}

.slider-row--over td {
  background: rgba(0, 119, 182, 0.08);
}

.admin-entity-actions--compact {
  gap: 0.4rem;
}

.slider-preview-image {
  margin-top: 1rem;
  display: grid;
  gap: 0.8rem;
}

.slider-preview-image img {
  width: 100%;
  max-height: 20rem;
  object-fit: cover;
  border-radius: var(--admin-radius-lg);
}

.slider-preview-card {
  margin-top: 1rem;
}

.slider-preview-card__label {
  margin: 0 0 0.8rem;
  font-size: 1.2rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--admin-text-light);
}

.slider-preview-card__surface {
  min-height: 24rem;
  position: relative;
  overflow: hidden;
  border-radius: var(--admin-radius-xl);
  background: #0f172a;
}

.slider-preview-card__surface img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.8;
}

.slider-preview-card__overlay {
  position: relative;
  z-index: 1;
  height: 100%;
  display: grid;
  align-content: end;
  gap: 0.8rem;
  padding: 1.8rem;
  color: #ffffff;
  background: linear-gradient(to top, rgba(12, 21, 35, 0.88), rgba(12, 21, 35, 0.12));
}

.slider-preview-card__overlay h3,
.slider-preview-card__overlay p {
  margin: 0;
}

.slider-preview-card__cta {
  display: inline-flex;
  width: fit-content;
  align-items: center;
  padding: 0.6rem 1rem;
  border-radius: var(--admin-radius-pill);
  background: rgba(255, 255, 255, 0.18);
}
</style>
