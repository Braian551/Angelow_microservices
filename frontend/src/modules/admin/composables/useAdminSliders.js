import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import {
  buildStoreLinkGroups,
  CUSTOM_STORE_LINK_VALUE,
  detectStoreLinkOption,
  loadStoreLinkCatalogs,
} from '../utils/storeLinkOptions'

/**
 * Composable para la gestión de sliders/banner del carrusel principal.
 * Administra CRUD, reordenamiento, imágenes, enlaces internos/externos
 * y previsualización de estilos. Reutiliza useAdminPagination.
 */
export function useAdminSliders() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado general y referencias principales
  // =====================================================
  const loading = ref(true)
  const processingOrder = ref(false)
  const savingSlider = ref(false)
  const sliders = ref([])
  const showModal = ref(false)
  const editingSliderId = ref(null)
  const imageInputRef = ref(null)
  const selectedImageFile = ref(null)
  const imagePreviewUrl = ref('')
  const selectedLinkOption = ref('/tienda')
  const linkCategories = ref([])
  const linkCollections = ref([])
  const dragState = reactive({
    draggingId: null,
    overId: null,
  })

  // =====================================================
  // Estado del formulario
  // =====================================================
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

  // =====================================================
  // Valores computados y soporte de preview
  // =====================================================
  const sliderStats = computed(() => [
    { key: 'total', label: 'Total sliders', value: sliders.value.length, icon: 'fas fa-images', color: 'primary' },
    { key: 'active', label: 'Activos', value: sliders.value.filter((slider) => slider.active).length, icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Inactivos', value: sliders.value.filter((slider) => !slider.active).length, icon: 'fas fa-eye-slash', color: 'warning' },
    { key: 'last-order', label: 'Último orden', value: sliders.value.length ? Math.max(...sliders.value.map((slider) => Number(slider.sort_order || 0))) : 0, icon: 'fas fa-sort-numeric-down', color: 'info' },
  ])

  const sliderPreviewSlides = computed(() => [
    {
      title: form.title || 'Bienvenido a Angelow',
      subtitle: form.subtitle || 'Moda infantil de calidad',
      image: imagePreviewUrl.value || form.image || '',
      link: form.link || '/tienda',
      button_text: 'Ver más',
    },
  ])

  const sliderPreviewKey = computed(() => [form.title, form.subtitle, imagePreviewUrl.value || form.image || '', form.link].join('|'))

  const linkOptionGroups = computed(() => buildStoreLinkGroups({
    categories: linkCategories.value,
    collections: linkCollections.value,
  }))

  // =====================================================
  // Helpers internos y normalización
  // =====================================================
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

  function isValidLink(value) {
    const clean = String(value || '').trim()
    if (!clean) return true
    return clean.startsWith('/') || /^https?:\/\//i.test(clean)
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  function updatePreviewUrl(nextUrl) {
    if (imagePreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreviewUrl.value)
    }
    imagePreviewUrl.value = nextUrl || ''
  }

  function createReorderPayload(orderedRows) {
    return orderedRows.map((slider, position) => ({
      id: slider.id,
      sort_order: position,
    }))
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

  // =====================================================
  // Gestión del formulario y validaciones
  // =====================================================
  function clearErrors() {
    Object.keys(formErrors).forEach((key) => {
      formErrors[key] = ''
    })
  }

  function clearSelectedImage(keepExisting = true) {
    selectedImageFile.value = null
    if (imageInputRef.value) {
      imageInputRef.value.value = ''
    }
    updatePreviewUrl(keepExisting && form.image ? resolveMediaUrl(form.image, 'slider') : '')
  }

  function resetForm() {
    form.title = ''
    form.subtitle = ''
    form.link = '/tienda'
    form.image = ''
    form.sort_order = sliders.value.length
    form.active = true
    selectedLinkOption.value = '/tienda'
    clearErrors()
    clearSelectedImage(false)
  }

  function detectLinkOption(link) {
    selectedLinkOption.value = detectStoreLinkOption(link, linkOptionGroups.value, '/tienda')
  }

  function onLinkOptionChange(value) {
    selectedLinkOption.value = value
    if (value !== CUSTOM_STORE_LINK_VALUE) {
      form.link = value
      validateField('link')
    }
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

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadLinkOptions() {
    const { categories, collections } = await loadStoreLinkCatalogs()
    linkCategories.value = categories
    linkCollections.value = collections
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

  // =====================================================
  // Acciones CRUD y cambios de estado
  // =====================================================
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
    form.link = slider.link || '/tienda'
    detectLinkOption(form.link)
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

  async function saveSlider() {
    if (savingSlider.value) return

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

    savingSlider.value = true
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
    } finally {
      savingSlider.value = false
    }
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

  // =====================================================
  // Gestión de modales y orden visual
  // =====================================================
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

  // =====================================================
  // Ciclo de vida y limpieza
  // =====================================================
  onMounted(async () => {
    await loadLinkOptions()
    await loadSliders()
  })

  onBeforeUnmount(() => {
    if (imagePreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreviewUrl.value)
    }
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    CUSTOM_STORE_LINK_VALUE,
    clearSelectedImage,
    closeModal,
    confirmDeleteSlider,
    dragState,
    editingSliderId,
    form,
    formErrors,
    imageInputRef,
    imagePreviewUrl,
    linkOptionGroups,
    loading,
    onImageSelected,
    onLinkOptionChange,
    onRowDragEnd,
    onRowDragEnter,
    onRowDragOver,
    onRowDragStart,
    onRowDrop,
    openCreateModal,
    openEditModal,
    openImagePicker,
    processingOrder,
    saveSlider,
    selectedLinkOption,
    showModal,
    sliderPreviewKey,
    sliderPreviewSlides,
    sliderStats,
    sliders,
    toggleSliderStatus,
    validateField,
  }
}
