<template>
  <div class="admin-settings-page">
    <AdminPageHeader
      icon="fas fa-cog"
      title="General"
      subtitle="Configura el nombre, identidad, contacto y operaciones de la tienda."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Configuración' }, { label: 'General' }]"
    >
      <template #actions>
        <transition name="settings-fade">
          <span v-if="isDirty && !saving" class="settings-dirty-badge">
            <i class="fas fa-circle"></i>
            Sin guardar
          </span>
        </transition>
        <button class="btn btn-primary btn-lg" type="button" :disabled="loading || saving" @click="saveSettings">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardando…' : 'Guardar cambios' }}
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="settingsStats" />

    <!-- Esqueleto de carga -->
    <div v-if="loading" class="settings-skeleton">
      <div class="settings-skeleton__tabs">
        <AdminShimmer v-for="n in 4" :key="n" type="pill" width="11rem" height="4rem" />
      </div>
      <div class="settings-brand-grid">
        <div class="admin-card" style="grid-column:1/3;">
          <div class="admin-card__body" style="display:grid;gap:1.4rem;">
            <div v-for="f in 5" :key="f" style="display:grid;gap:0.5rem;">
              <AdminShimmer type="line" width="8rem" height="1.2rem" />
              <AdminShimmer type="rect" height="3.8rem" />
            </div>
          </div>
        </div>
        <div class="admin-card">
          <div class="admin-card__body" style="display:grid;gap:1.2rem;">
            <AdminShimmer v-for="n in 3" :key="n" type="rect" height="11rem" />
          </div>
        </div>
      </div>
    </div>

    <AdminEmptyState
      v-else-if="categorySections.length === 0"
      icon="fas fa-cog"
      title="Sin configuración disponible"
      description="No se encontraron definiciones para construir el formulario general."
    />

    <div v-else class="settings-layout">
      <!-- Navegación por pestañas -->
      <div class="settings-tabs" role="tablist">
        <button
          v-for="section in categorySections"
          :key="section.key"
          role="tab"
          type="button"
          class="settings-tab"
          :class="{ 'is-active': activeSection === section.key, 'has-errors': sectionErrorCount(section) > 0 }"
          :aria-selected="activeSection === section.key"
          @click="activeSection = section.key"
        >
          <i :class="section.icon"></i>
          <span>{{ section.title }}</span>
          <span v-if="sectionErrorCount(section) > 0" class="settings-tab__errorcount">{{ sectionErrorCount(section) }}</span>
        </button>
      </div>

      <!-- Contenido animado por sección -->
      <transition name="settings-section" mode="out-in">
        <div :key="activeSection" class="settings-section-body" role="tabpanel">

          <!-- ══════ MARCA E IDENTIDAD ══════ -->
          <template v-if="activeSection === 'brand'">
            <div class="settings-brand-grid">
              <!-- Columna izquierda: campos de texto y colores -->
              <AdminCard title="Datos de la tienda" icon="fas fa-store" class="settings-brand-text-card">
                <div class="settings-fields-grid">
                  <div v-for="field in brandTextFields" :key="field.key" class="form-group">
                    <label :for="`setting-${field.key}`" class="settings-field-label">{{ field.label }}</label>
                    <template v-if="field.type === 'color'">
                      <div class="color-input-group">
                        <button
                          type="button"
                          class="settings-color-swatch"
                          :style="{ background: settings[field.key] || field.default || '#0077b6' }"
                          :title="`Color: ${settings[field.key] || field.default}`"
                          @click="() => colorPickerRefs[field.key]?.click()"
                        ></button>
                        <input :ref="(el) => (colorPickerRefs[field.key] = el)" :id="`setting-${field.key}`" v-model="settings[field.key]" type="color" class="settings-color-picker-hidden" @input="validateField(field.key)">
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
                        :maxlength="field.max_length ? Number(field.max_length) : undefined"
                        @input="validateField(field.key)"
                      >
                      <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">
                        {{ String(settings[field.key] || '').length }} / {{ field.max_length }}
                      </div>
                    </template>
                    <small v-if="field.hint" class="form-hint">{{ field.hint }}</small>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </AdminCard>

              <!-- Columna derecha: galería de imágenes (logo, logo alterno, favicon) -->
              <AdminCard title="Identidad visual" icon="fas fa-images" class="settings-brand-images-card">
                <div class="settings-image-gallery">
                  <div v-for="field in brandImageFields" :key="field.key" class="settings-image-gallery__item">
                    <p class="settings-image-gallery__label">
                      <i class="fas fa-image"></i>
                      {{ field.label }}
                    </p>
                    <div v-if="!imagePreviews[field.key]" class="settings-image-upload settings-image-upload--compact" @click="openImagePicker(field.key)">
                      <i class="fas fa-cloud-upload-alt"></i>
                      <small>{{ field.hint || 'Subir imagen' }}</small>
                    </div>
                    <div v-else class="settings-image-card settings-image-card--compact">
                      <div class="settings-image-card__visual">
                        <img :src="imagePreviews[field.key]" :alt="field.label" @error="(event) => onImagePreviewError(field.key, event)">
                        <div class="settings-image-card__overlay">
                          <button type="button" class="settings-image-action settings-image-action--edit" title="Cambiar" @click="openImagePicker(field.key)">
                            <i class="fas fa-pencil-alt"></i>
                          </button>
                          <button type="button" class="settings-image-action settings-image-action--remove" title="Quitar" @click="clearImage(field.key)">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </div>
                      </div>
                      <transition name="settings-fade">
                        <div v-if="imageFiles[field.key]" class="settings-image-file-pill">
                          <i class="fas fa-check-circle"></i>
                          {{ imageFiles[field.key].name }}
                        </div>
                      </transition>
                    </div>
                    <input :ref="(element) => setImageInputRef(field.key, element)" type="file" accept="image/*" style="display:none;" @change="(event) => onImageSelected(field.key, event)">
                  </div>
                </div>
              </AdminCard>
            </div>
          </template>

          <!-- ══════ REDES SOCIALES ══════ -->
          <template v-else-if="activeSection === 'social'">
            <AdminCard title="Redes sociales" subtitle="Vincula los perfiles de la tienda para el footer y la tienda pública." icon="fas fa-share-alt">
              <div class="settings-social-list">
                <div v-for="field in socialFields" :key="field.key" class="settings-social-row">
                  <div class="settings-social-icon" :class="`settings-social-icon--${socialNetworkKey(field.key)}`">
                    <i :class="socialNetworkIcon(field.key)"></i>
                  </div>
                  <div class="settings-social-input">
                    <label :for="`setting-${field.key}`">{{ field.label }}</label>
                    <input
                      :id="`setting-${field.key}`"
                      v-model="settings[field.key]"
                      type="url"
                      class="form-control"
                      :class="{ 'is-invalid': errors[field.key] }"
                      placeholder="https://"
                      @input="validateField(field.key)"
                    >
                    <small class="form-hint">{{ field.hint }}</small>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </div>
            </AdminCard>
          </template>

          <!-- ══════ OPERACIONES ══════ -->
          <template v-else-if="activeSection === 'operations'">
            <div class="settings-operations-grid">
              <AdminCard v-for="field in operationsFields" :key="field.key" class="settings-op-card">
                <div class="settings-op-row">
                  <div class="settings-op-icon">
                    <i :class="'fas ' + (field.icon || 'fa-cog')"></i>
                  </div>
                  <div class="settings-op-content">
                    <label :for="field.type !== 'bool' ? `setting-${field.key}` : undefined" class="settings-op-label">{{ field.label }}</label>
                    <p class="settings-op-hint">{{ field.hint }}</p>
                    <template v-if="field.type === 'bool'">
                      <div class="settings-toggle-row">
                        <label class="toggle-switch">
                          <input :id="`setting-${field.key}`" v-model="settings[field.key]" type="checkbox" @change="validateField(field.key)">
                          <span class="toggle-slider"></span>
                        </label>
                        <span class="settings-toggle-text" :class="{ 'is-on': settings[field.key] }">
                          {{ settings[field.key] ? 'Activado' : 'Desactivado' }}
                        </span>
                      </div>
                    </template>
                    <template v-else>
                      <input
                        :id="`setting-${field.key}`"
                        v-model="settings[field.key]"
                        :type="inputType(field)"
                        class="form-control settings-op-input"
                        :class="{ 'is-invalid': errors[field.key] }"
                        :min="field.min"
                        :max="field.max"
                        @input="validateField(field.key)"
                      >
                    </template>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </AdminCard>
            </div>
          </template>

          <!-- ══════ SOPORTE, SISTEMA Y OTRAS ══════ -->
          <template v-else>
            <AdminCard :title="currentSectionData?.title" :icon="currentSectionData?.icon">
              <div class="settings-generic-2col">
                <div v-for="field in (currentSectionData?.fields || [])" :key="field.key" class="form-group">
                  <label :for="field.type !== 'bool' ? `setting-${field.key}` : undefined" class="settings-field-label">{{ field.label }}</label>
                  <template v-if="field.type === 'textarea'">
                    <textarea :id="`setting-${field.key}`" v-model="settings[field.key]" rows="2" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.hint || ''" :maxlength="field.max_length ? Number(field.max_length) : undefined" @input="validateField(field.key)"></textarea>
                    <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">{{ String(settings[field.key] || '').length }} / {{ field.max_length }}</div>
                  </template>
                  <template v-else-if="field.type === 'bool'">
                    <div class="settings-toggle-row">
                      <label class="toggle-switch">
                        <input :id="`setting-${field.key}`" v-model="settings[field.key]" type="checkbox" @change="validateField(field.key)">
                        <span class="toggle-slider"></span>
                      </label>
                      <span class="settings-toggle-text" :class="{ 'is-on': settings[field.key] }">{{ settings[field.key] ? 'Activado' : 'Desactivado' }}</span>
                    </div>
                  </template>
                  <template v-else-if="field.type === 'color'">
                    <div class="color-input-group">
                      <button type="button" class="settings-color-swatch" :style="{ background: settings[field.key] || field.default || '#0077b6' }" @click="() => colorPickerRefs[field.key]?.click()"></button>
                      <input :ref="(el) => (colorPickerRefs[field.key] = el)" :id="`setting-${field.key}`" v-model="settings[field.key]" type="color" class="settings-color-picker-hidden" @input="validateField(field.key)">
                      <input v-model.trim="settings[field.key]" type="text" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.default || '#000000'" @input="validateField(field.key)">
                    </div>
                  </template>
                  <template v-else>
                    <input :id="`setting-${field.key}`" v-model="settings[field.key]" :type="inputType(field)" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.hint || ''" :maxlength="field.max_length ? Number(field.max_length) : undefined" @input="validateField(field.key)">
                    <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">{{ String(settings[field.key] || '').length }} / {{ field.max_length }}</div>
                  </template>
                  <small v-if="field.hint && field.type !== 'bool'" class="form-hint">{{ field.hint }}</small>
                  <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                </div>
              </div>
            </AdminCard>
          </template>

        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import '../views/AdminSettingsPage.css'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { SITE_SETTINGS_UPDATED_EVENT } from '../../../constants/siteSettingsEvents'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const saving = ref(false)
const definitions = ref({})
const settings = reactive({})
const errors = reactive({})
const imageFiles = reactive({})
const imagePreviews = reactive({})
const removedImages = reactive({})
const imageInputRefs = reactive({})
const colorPickerRefs = reactive({})

// Snapshot del último estado guardado/cargado para detectar cambios
const originalSettings = ref({})

const isDirty = computed(() => {
  return Object.keys(settings).some((key) => {
    const def = definitions.value[key]
    if (def?.type === 'image') return imageFiles[key] != null || removedImages[key]
    return String(settings[key] ?? '') !== String(originalSettings.value[key] ?? '')
  })
})

function charPercent(key, field) {
  const len = String(settings[key] || '').length
  return Math.round((len / Number(field.max_length)) * 100)
}

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

const visibleFieldKeys = computed(() => categorySections.value.flatMap((section) => section.fields.map((field) => field.key)))

// ── Navegación de tabs ──
const activeSection = ref('brand')

const currentSectionData = computed(() =>
  categorySections.value.find((s) => s.key === activeSection.value) || { fields: [], title: '', icon: '' }
)

const brandTextFields = computed(() =>
  (categorySections.value.find((s) => s.key === 'brand')?.fields || []).filter((f) => f.type !== 'image')
)

const brandImageFields = computed(() =>
  (categorySections.value.find((s) => s.key === 'brand')?.fields || []).filter((f) => f.type === 'image')
)

const socialFields = computed(() =>
  categorySections.value.find((s) => s.key === 'social')?.fields || []
)

const operationsFields = computed(() =>
  categorySections.value.find((s) => s.key === 'operations')?.fields || []
)

function sectionErrorCount(section) {
  return section.fields.filter((f) => errors[f.key]).length
}

const socialIconMap = {
  social_instagram: { icon: 'fab fa-instagram', key: 'instagram' },
  social_facebook:  { icon: 'fab fa-facebook-f', key: 'facebook' },
  social_tiktok:    { icon: 'fab fa-tiktok',     key: 'tiktok' },
  social_whatsapp:  { icon: 'fab fa-whatsapp',   key: 'whatsapp' },
  social_twitter:   { icon: 'fab fa-twitter',    key: 'twitter' },
  social_youtube:   { icon: 'fab fa-youtube',    key: 'youtube' },
}

function socialNetworkIcon(key) {
  return socialIconMap[key]?.icon || 'fas fa-link'
}

function socialNetworkKey(key) {
  return socialIconMap[key]?.key || 'default'
}

const settingsStats = computed(() => {
  const totalFields = visibleFieldKeys.value.length
  const socialCount = visibleFieldKeys.value
    .filter((key) => definitions.value[key]?.category === 'social' && String(settings[key] || '').trim())
    .length
  const validCount = visibleFieldKeys.value.filter((key) => !errors[key]).length

  return [
    { key: 'sections', label: 'Secciones', value: categorySections.value.length, icon: 'fas fa-th-large', color: 'primary' },
    { key: 'fields', label: 'Campos cargados', value: totalFields, icon: 'fas fa-list-check', color: 'info' },
    { key: 'social', label: 'Redes completas', value: socialCount, icon: 'fas fa-share-alt', color: 'success' },
    { key: 'valid', label: 'Campos sin error', value: validCount, icon: 'fas fa-check-circle', color: 'warning' },
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
      removedImages[key] = false
      imagePreviews[key] = settings[key] ? resolveMediaUrl(settings[key], 'brand') : ''
    }
  })

  // Guardar snapshot para poder calcular isDirty
  originalSettings.value = Object.assign({}, settings)
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
  removedImages[key] = false
  if (imagePreviews[key]?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviews[key])
  }
  imagePreviews[key] = URL.createObjectURL(file)
  validateField(key)
}

function clearImage(key) {
  imageFiles[key] = null
  removedImages[key] = true
  settings[key] = ''
  if (imagePreviews[key]?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviews[key])
  }
  imagePreviews[key] = ''
  if (imageInputRefs[key]) {
    imageInputRefs[key].value = ''
  }
}

function onImagePreviewError(key, event) {
  const originalPath = imageFiles[key]
    ? imagePreviews[key]
    : (settings[key] || '')
  handleMediaError(event, originalPath, 'brand')
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

  showAlert({
    type: 'warning',
    title: 'Confirmar cambios',
    message: 'Vas a actualizar la configuración general de la tienda.',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      { text: 'Guardar cambios', style: 'primary', callback: persistSettings },
    ],
  })
}

async function persistSettings() {
  const payload = new FormData()
  Object.entries(definitions.value).forEach(([key, field]) => {
    if (field.type === 'image') {
      if (imageFiles[key]) payload.append(key, imageFiles[key])
      if (!imageFiles[key] && removedImages[key]) payload.append(`${key}_remove`, '1')
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
    const { data } = await catalogHttp.put('/admin/settings', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
    if (data?.data) {
      populateSettings(data.data)
      window.dispatchEvent(new CustomEvent(SITE_SETTINGS_UPDATED_EVENT, {
        detail: {
          settings: data.data.settings || {},
          refreshedAt: Date.now(),
        },
      }))
    } else {
      await loadSettings()
      window.dispatchEvent(new CustomEvent(SITE_SETTINGS_UPDATED_EVENT, {
        detail: {
          settings: { ...settings },
          refreshedAt: Date.now(),
        },
      }))
    }
    showSnackbar({ type: 'success', message: 'Configuración guardada correctamente.' })
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
