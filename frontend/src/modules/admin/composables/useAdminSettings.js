import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { SITE_SETTINGS_UPDATED_EVENT } from '../../../constants/siteSettingsEvents'

/**
 * Composable para la configuración general del sitio (branding, contactos, SEO, etc.).
 * Carga definiciones de secciones desde el backend, gestiona imágenes,
 * colores, validaciones y guarda los cambios con soporte para eventos de actualización.
 */
export function useAdminSettings() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado de configuración y referencias base
  // =====================================================
  const loading = ref(true)
  const saving = ref(false)
  const activeSection = ref('brand')
  const definitions = ref({})
  const settings = reactive({})
  const errors = reactive({})
  const imageFiles = reactive({})
  const imagePreviews = reactive({})
  const removedImages = reactive({})
  const imageInputRefs = reactive({})
  const colorPickerRefs = reactive({})
  const originalSettings = ref({})

  // =====================================================
  // Constantes internas de presentación
  // =====================================================
  const categoryMeta = {
    brand: { title: 'Marca e identidad', icon: 'fas fa-id-card' },
    support: { title: 'Soporte y contacto', icon: 'fas fa-headset' },
    operations: { title: 'Operaciones', icon: 'fas fa-cogs' },
    social: { title: 'Redes sociales', icon: 'fas fa-share-alt' },
  }

  const socialIconMap = {
    social_instagram: { icon: 'fab fa-instagram', key: 'instagram' },
    social_facebook: { icon: 'fab fa-facebook-f', key: 'facebook' },
    social_tiktok: { icon: 'fab fa-tiktok', key: 'tiktok' },
    social_whatsapp: { icon: 'fab fa-whatsapp', key: 'whatsapp' },
    social_twitter: { icon: 'fab fa-twitter', key: 'twitter' },
    social_youtube: { icon: 'fab fa-youtube', key: 'youtube' },
  }

  // =====================================================
  // Valores computados y navegación por secciones
  // =====================================================
  const isDirty = computed(() => {
    return Object.keys(settings).some((key) => {
      const def = definitions.value[key]
      if (def?.type === 'image') return imageFiles[key] != null || removedImages[key]
      return String(settings[key] ?? '') !== String(originalSettings.value[key] ?? '')
    })
  })

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

  const currentSectionData = computed(() =>
    categorySections.value.find((section) => section.key === activeSection.value) || { fields: [], title: '', icon: '' }
  )

  const brandTextFields = computed(() =>
    (categorySections.value.find((section) => section.key === 'brand')?.fields || []).filter((field) => field.type !== 'image')
  )

  const brandImageFields = computed(() =>
    (categorySections.value.find((section) => section.key === 'brand')?.fields || []).filter((field) => field.type === 'image')
  )

  const socialFields = computed(() => categorySections.value.find((section) => section.key === 'social')?.fields || [])
  const operationsFields = computed(() => categorySections.value.find((section) => section.key === 'operations')?.fields || [])

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

  // =====================================================
  // Helpers internos y normalización
  // =====================================================
  function charPercent(key, field) {
    const len = String(settings[key] || '').length
    return Math.round((len / Number(field.max_length)) * 100)
  }

  function inputType(field) {
    if (field.type === 'email') return 'email'
    if (field.type === 'int') return 'number'
    return 'text'
  }

  function sectionErrorCount(section) {
    return section.fields.filter((field) => errors[field.key]).length
  }

  function socialNetworkIcon(key) {
    return socialIconMap[key]?.icon || 'fas fa-link'
  }

  function socialNetworkKey(key) {
    return socialIconMap[key]?.key || 'default'
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
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

  // =====================================================
  // Gestión de formularios y recursos multimedia
  // =====================================================
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

    originalSettings.value = { ...settings }
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
    const originalPath = imageFiles[key] ? imagePreviews[key] : (settings[key] || '')
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

  // =====================================================
  // Carga y guardado de configuración
  // =====================================================
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

  async function persistSettings() {
    if (saving.value) return

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
      const emittedSettings = data?.data?.settings || { ...settings }

      if (data?.data) {
        populateSettings(data.data)
      } else {
        await loadSettings()
      }

      window.dispatchEvent(new CustomEvent(SITE_SETTINGS_UPDATED_EVENT, {
        detail: {
          settings: emittedSettings,
          refreshedAt: Date.now(),
        },
      }))

      showSnackbar({ type: 'success', message: 'Configuración guardada correctamente.' })
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar la configuración.') })
    } finally {
      saving.value = false
    }
  }

  function saveSettings() {
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

  // =====================================================
  // Ciclo de vida y limpieza
  // =====================================================
  onMounted(loadSettings)

  onBeforeUnmount(() => {
    Object.values(imagePreviews).forEach((preview) => {
      if (typeof preview === 'string' && preview.startsWith('blob:')) {
        URL.revokeObjectURL(preview)
      }
    })
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeSection,
    brandImageFields,
    brandTextFields,
    categorySections,
    charPercent,
    clearImage,
    colorPickerRefs,
    currentSectionData,
    errors,
    imageFiles,
    imagePreviews,
    inputType,
    isDirty,
    loading,
    onImagePreviewError,
    onImageSelected,
    openImagePicker,
    operationsFields,
    saveSettings,
    sectionErrorCount,
    setImageInputRef,
    settings,
    settingsStats,
    saving,
    socialFields,
    socialNetworkIcon,
    socialNetworkKey,
    validateField,
  }
}
