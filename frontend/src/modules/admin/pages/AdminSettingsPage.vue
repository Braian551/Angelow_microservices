<template>
  <div class="admin-settings-page">
    <AdminPageHeader
      icon="fas fa-cog"
      title="General"
      subtitle="Ajusta la información global de la tienda con secciones, validación inmediata y componentes reutilizables del dashboard."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Configuración' }, { label: 'General' }]"
    >
      <template #actions>
        <button class="btn btn-primary btn-lg" type="button" :disabled="loading || saving" @click="saveSettings">
          <i class="fas fa-save"></i>
          Guardar cambios
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="settingsStats" />

    <AdminEmptyState
      v-if="!loading && categorySections.length === 0"
      icon="fas fa-cog"
      title="Sin configuración disponible"
      description="No se encontraron definiciones para construir el formulario general."
    />

    <div v-else class="settings-grid">
      <AdminCard v-for="section in categorySections" :key="section.key" :title="section.title" :icon="section.icon">
        <div class="settings-fields-grid">
          <div v-for="field in section.fields" :key="field.key" class="form-group">
            <label :for="`setting-${field.key}`">{{ field.label }}</label>

            <template v-if="field.type === 'textarea'">
              <textarea
                :id="`setting-${field.key}`"
                v-model="settings[field.key]"
                rows="3"
                class="form-control"
                :class="{ 'is-invalid': errors[field.key] }"
                :placeholder="field.hint || ''"
                @input="validateField(field.key)"
              ></textarea>
            </template>

            <template v-else-if="field.type === 'image'">
              <div class="upload-box" @click="openImagePicker(field.key)">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>{{ imagePreviews[field.key] ? 'Cambiar imagen' : field.hint || 'Selecciona una imagen' }}</p>
                <small>PNG, JPG o WEBP.</small>
              </div>
              <input :ref="(element) => setImageInputRef(field.key, element)" type="file" accept="image/*" style="display: none;" @change="(event) => onImageSelected(field.key, event)">
              <div v-if="imagePreviews[field.key]" class="setting-image-preview">
                <img :src="imagePreviews[field.key]" :alt="field.label">
                <button class="btn btn-secondary btn-sm" type="button" @click="clearImage(field.key)">Quitar imagen</button>
              </div>
            </template>

            <template v-else-if="field.type === 'bool'">
              <select :id="`setting-${field.key}`" v-model="settings[field.key]" class="form-control" @change="validateField(field.key)">
                <option :value="true">Sí</option>
                <option :value="false">No</option>
              </select>
            </template>

            <template v-else-if="field.type === 'color'">
              <div class="color-input-group">
                <input :id="`setting-${field.key}`" v-model="settings[field.key]" type="color" class="settings-color-picker" @input="validateField(field.key)">
                <input v-model.trim="settings[field.key]" type="text" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.default || '#000000'" @input="validateField(field.key)">
              </div>
            </template>

            <template v-else>
              <input
                :id="`setting-${field.key}`"
                v-model="settings[field.key]"
                :type="inputType(field)"
                class="form-control"
                :class="{ 'is-invalid': errors[field.key] }"
                :placeholder="field.hint || ''"
                @input="validateField(field.key)"
              >
            </template>

            <small v-if="field.hint" class="form-hint">{{ field.hint }}</small>
            <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
          </div>
        </div>
      </AdminCard>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'

const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const saving = ref(false)
const definitions = ref({})
const settings = reactive({})
const errors = reactive({})
const imageFiles = reactive({})
const imagePreviews = reactive({})
const imageInputRefs = reactive({})

const categoryMeta = {
  brand: { title: 'Marca e identidad', icon: 'fas fa-id-card' },
  support: { title: 'Soporte y contacto', icon: 'fas fa-headset' },
  operations: { title: 'Operaciones', icon: 'fas fa-cogs' },
  social: { title: 'Redes sociales', icon: 'fas fa-share-alt' },
}

const categorySections = computed(() => {
  const sections = []
  for (const [categoryKey, meta] of Object.entries(categoryMeta)) {
    const fields = Object.entries(definitions.value)
      .filter(([, field]) => field.category === categoryKey)
      .map(([key, field]) => ({ key, ...field }))

    if (fields.length > 0) {
      sections.push({ key: categoryKey, title: meta.title, icon: meta.icon, fields })
    }
  }
  return sections
})

const settingsStats = computed(() => {
  const totalFields = Object.keys(definitions.value).length
  const socialCount = Object.keys(definitions.value)
    .filter((key) => definitions.value[key]?.category === 'social' && String(settings[key] || '').trim())
    .length
  const validCount = Object.keys(definitions.value).filter((key) => !errors[key]).length

  return [
    { key: 'sections', label: 'Secciones', value: categorySections.value.length, icon: 'fas fa-th-large', color: 'primary' },
    { key: 'fields', label: 'Campos cargados', value: totalFields, icon: 'fas fa-list-check', color: 'info' },
    { key: 'social', label: 'Redes completas', value: socialCount, icon: 'fas fa-share-alt', color: 'success' },
    { key: 'valid', label: 'Campos sin error', value: validCount, icon: 'fas fa-shield-check', color: 'warning' },
  ]
})

function inputType(field) {
  if (field.type === 'email') return 'email'
  if (field.type === 'int') return 'number'
  return 'text'
}

function setImageInputRef(key, element) {
  imageInputRefs[key] = element
}

function openImagePicker(key) {
  imageInputRefs[key]?.click()
}

function resetState() {
  Object.keys(settings).forEach((key) => delete settings[key])
  Object.keys(errors).forEach((key) => delete errors[key])
}

function populateSettings(payload) {
  resetState()
  definitions.value = payload?.definitions || {}

  Object.entries(definitions.value).forEach(([key, field]) => {
    const incomingValue = payload?.settings?.[key]
    settings[key] = normalizeValue(incomingValue ?? field.default ?? '', field)
    errors[key] = ''
    if (field.type === 'image') {
      imageFiles[key] = null
      imagePreviews[key] = settings[key] ? resolveMediaUrl(settings[key], 'brand') : ''
    }
  })
}

function normalizeValue(value, field) {
  if (field.type === 'bool') {
    return value === true || value === '1' || value === 1 || value === 'true'
  }
  if (field.type === 'int') {
    return value === null || value === '' ? field.default ?? 0 : Number(value)
  }
  return String(value ?? '')
}

function onImageSelected(key, event) {
  const file = event.target.files?.[0]
  if (!file) return

  imageFiles[key] = file
  if (imagePreviews[key]?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviews[key])
  }
  imagePreviews[key] = URL.createObjectURL(file)
  validateField(key)
}

function clearImage(key) {
  imageFiles[key] = null
  if (imagePreviews[key]?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviews[key])
  }
  imagePreviews[key] = settings[key] ? resolveMediaUrl(settings[key], 'brand') : ''
  if (imageInputRefs[key]) {
    imageInputRefs[key].value = ''
  }
}

function validateField(key) {
  const field = definitions.value[key]
  if (!field) return

  const value = settings[key]
  errors[key] = ''

  if (field.type === 'image') {
    return
  }

  if (field.type === 'email') {
    const clean = String(value || '').trim()
    errors[key] = !clean || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean) ? '' : 'Ingresa un correo válido.'
    return
  }

  if (field.type === 'int') {
    const numericValue = Number(value)
    if (!Number.isFinite(numericValue)) {
      errors[key] = 'Ingresa un número válido.'
      return
    }
    if (field.min !== undefined && numericValue < Number(field.min)) {
      errors[key] = `El valor mínimo permitido es ${field.min}.`
      return
    }
    if (field.max !== undefined && numericValue > Number(field.max)) {
      errors[key] = `El valor máximo permitido es ${field.max}.`
    }
    return
  }

  const clean = String(value || '').trim()
  if (field.pattern) {
    const regex = new RegExp(field.pattern.slice(1, field.pattern.lastIndexOf('/')), field.pattern.slice(field.pattern.lastIndexOf('/') + 1))
    errors[key] = !clean || regex.test(clean) ? '' : 'El valor no cumple el formato esperado.'
    return
  }

  if (field.max_length && clean.length > Number(field.max_length)) {
    errors[key] = `Máximo ${field.max_length} caracteres.`
  }
}

function validateForm() {
  Object.keys(definitions.value).forEach((key) => validateField(key))
  return Object.values(errors).every((value) => !value)
}

async function loadSettings() {
  loading.value = true
  try {
    const { data } = await catalogHttp.get('/admin/settings')
    populateSettings(data?.data || {})
  } catch (error) {
    definitions.value = {}
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo cargar la configuración general.') })
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = new FormData()
  Object.entries(definitions.value).forEach(([key, field]) => {
    if (field.type === 'image') {
      if (imageFiles[key]) payload.append(key, imageFiles[key])
      return
    }
    if (field.type === 'bool') {
      payload.append(key, settings[key] ? '1' : '0')
      return
    }
    payload.append(key, String(settings[key] ?? ''))
  })

  saving.value = true
  try {
    await catalogHttp.put('/admin/settings', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
    showSnackbar({ type: 'success', message: 'Configuración guardada correctamente.' })
    await loadSettings()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar la configuración.') })
  } finally {
    saving.value = false
  }
}

function extractErrorMessage(error, fallback) {
  return error?.response?.data?.message || fallback
}

onMounted(loadSettings)

onBeforeUnmount(() => {
  Object.values(imagePreviews).forEach((preview) => {
    if (typeof preview === 'string' && preview.startsWith('blob:')) {
      URL.revokeObjectURL(preview)
    }
  })
})
</script>

<style scoped>
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(32rem, 1fr));
  gap: 1.5rem;
}

.settings-fields-grid {
  display: grid;
  gap: 1.25rem;
}

.upload-box {
  border: 1px dashed rgba(0, 119, 182, 0.32);
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
.upload-box small,
.form-hint {
  margin: 0;
}

.setting-image-preview {
  margin-top: 1rem;
  display: grid;
  gap: 0.8rem;
}

.setting-image-preview img {
  width: 100%;
  max-height: 14rem;
  object-fit: contain;
  border-radius: 1rem;
  background: #f8fbfe;
  border: 1px solid rgba(0, 119, 182, 0.12);
}

.color-input-group {
  display: flex;
  gap: 0.8rem;
  align-items: center;
}

.settings-color-picker {
  width: 4.8rem;
  min-width: 4.8rem;
  height: 4rem;
  border: 1px solid var(--admin-border);
  border-radius: 0.8rem;
  background: #fff;
  cursor: pointer;
}

@media (max-width: 768px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }

  .color-input-group {
    align-items: stretch;
  }
}
</style>
