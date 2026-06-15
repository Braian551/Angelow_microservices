import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { notificationHttp, catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import { buildStoreLinkGroups, CUSTOM_STORE_LINK_VALUE, detectStoreLinkOption, loadStoreLinkCatalogs } from '../utils/storeLinkOptions'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

export function useAdminAnnouncements() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

  const loading = ref(true)
  const announcements = ref([])
  const selectedAnnouncement = ref(null)
  const showDetailModal = ref(false)
  const showEditorModal = ref(false)
  const editingAnnouncementId = ref(null)
  const imageInputRef = ref(null)
  const selectedImageFile = ref(null)
  const imagePreviewUrl = ref('')
  const availableColors = ref([])
  const selectedLinkOption = ref('')
  const linkCategories = ref([])
  const linkCollections = ref([])

  const TEXT_COLOR_OPTIONS = [
    { label: 'Blanco', value: '#ffffff' },
    { label: 'Negro', value: '#000000' },
    { label: 'Gris oscuro', value: '#333333' },
    { label: 'Azul oscuro', value: '#102236' },
  ]

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

      const haystack = [
        announcement.title,
        announcement.message,
        announcement.subtitle,
        announcement.button_text,
        announcement.button_link,
      ].join(' ').toLowerCase()

      return haystack.includes(term)
    })
  })

  const pagination = useAdminPagination(filteredAnnouncements, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
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
  const isTopBarType = computed(() => form.type === 'top_bar')
  const isPromoBannerType = computed(() => form.type === 'promo_banner')

  const linkOptionGroups = computed(() => buildStoreLinkGroups({
    categories: linkCategories.value,
    collections: linkCollections.value,
    includeEmptyOption: true,
  }))

  const selectedColorInfo = computed(() => availableColors.value.find((color) => color.hex_code === form.background_color) || null)

  const previewBarStyle = computed(() => ({
    backgroundColor: form.background_color || '#6a96cf',
    color: form.text_color || '#ffffff',
  }))

  const previewBannerStyle = computed(() => {
    const style = {
      backgroundColor: form.background_color || '#6a96cf',
      color: form.text_color || '#ffffff',
    }

    const imgSrc = imagePreviewUrl.value || form.image
    if (imgSrc) {
      const url = imgSrc.startsWith('blob:') ? imgSrc : resolveMediaUrl(imgSrc, 'banner')
      style.backgroundImage = `url('${url}')`
      style.backgroundSize = 'cover'
      style.backgroundPosition = 'center'
    }

    return style
  })

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
    selectedLinkOption.value = ''
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

  async function loadColors() {
    try {
      const { data } = await catalogHttp.get('/admin/colors')
      availableColors.value = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
    } catch {
      availableColors.value = []
    }
  }

  function detectLinkOption(link) {
    selectedLinkOption.value = detectStoreLinkOption(link, linkOptionGroups.value, '')
  }

  function onLinkOptionChange(value) {
    selectedLinkOption.value = value
    if (value !== CUSTOM_STORE_LINK_VALUE) {
      form.button_link = value
      validateField('button_link')
    }
  }

  function handleAnnouncementTypeChange() {
    validateField('type')
    if (isTopBarType.value) {
      formErrors.button_link = ''
      return
    }

    detectLinkOption(form.button_link)
  }

  async function loadLinkOptions() {
    const { categories, collections } = await loadStoreLinkCatalogs()
    linkCategories.value = categories
    linkCollections.value = collections
  }

  function openCreateModal() {
    if (!canCreateAnnouncement.value) {
      showAlert({
        type: 'warning',
        title: 'Límite alcanzado',
        message: 'Solo puedes tener hasta 2 anuncios activos. Elimina uno antes de crear otro.',
      })
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
    detectLinkOption(form.button_link)
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
    const currentAnnouncement = selectedAnnouncement.value
    closeDetailModal()
    openEditModal(currentAnnouncement)
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
        formErrors.priority = Number.isFinite(Number(form.priority)) && Number(form.priority) >= 0 && Number(form.priority) <= 100
          ? ''
          : 'La prioridad debe estar entre 0 y 100.'
        break
      case 'button_link':
        if (isTopBarType.value) {
          formErrors.button_link = ''
          break
        }
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

  function buildAnnouncementPayload() {
    const payload = new FormData()

    payload.append('type', form.type)
    payload.append('title', form.title.trim())
    payload.append('message', form.message.trim())
    payload.append('subtitle', isPromoBannerType.value ? form.subtitle.trim() : '')
    payload.append('button_text', isPromoBannerType.value ? form.button_text.trim() : '')
    payload.append('button_link', isPromoBannerType.value ? form.button_link.trim() : '')
    payload.append('image', isPromoBannerType.value ? form.image.trim() : '')
    payload.append('icon', form.icon)
    payload.append('priority', String(Number(form.priority || 0)))
    payload.append('background_color', form.background_color.trim())
    payload.append('text_color', form.text_color.trim())
    payload.append('is_active', form.is_active ? '1' : '0')
    if (form.start_date) payload.append('start_date', form.start_date)
    if (form.end_date) payload.append('end_date', form.end_date)
    if (isPromoBannerType.value && selectedImageFile.value) payload.append('image_file', selectedImageFile.value)

    return payload
  }

  async function saveAnnouncement() {
    if (!validateForm()) {
      showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
      return
    }

    try {
      const payload = buildAnnouncementPayload()

      if (editingAnnouncementId.value) {
        payload.append('_method', 'PUT')
        await notificationHttp.post(`/admin/announcements/${editingAnnouncementId.value}`, payload, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        showSnackbar({ type: 'success', message: 'Anuncio actualizado correctamente.' })
      } else {
        await notificationHttp.post('/admin/announcements', payload, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
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

  function buildAnnouncementExportColumns() {
    return [
      {
        header: 'Vista',
        includeInExcel: false,
        pdfImage: (announcement) => announcement.image,
        fallbackType: 'banner',
        pdfWidth: 18,
        pdfImageSize: 12,
      },
      { header: 'Título', value: (announcement) => announcement.title },
      { header: 'Mensaje', value: (announcement) => announcement.message || announcement.content || 'Sin mensaje', width: 30 },
      { header: 'Tipo', value: (announcement) => typeLabel(announcement.type), width: 16 },
      { header: 'Prioridad', value: (announcement) => priorityLabel(announcement.priority), width: 14 },
      { header: 'Estado', value: (announcement) => announcementStatusLabel(announcement), width: 14 },
      { header: 'Inicio', value: (announcement) => (announcement.start_date ? formatDateTime(announcement.start_date) : 'Inmediato'), width: 18 },
      { header: 'Fin', value: (announcement) => (announcement.end_date ? formatDateTime(announcement.end_date) : 'Sin fecha de cierre'), width: 18 },
      { header: 'Enlace', value: (announcement) => announcement.button_link || 'Sin enlace', width: 22 },
    ]
  }

  function exportAnnouncements(format) {
    return exportData({
      format,
      fileBaseName: 'anuncios-admin',
      sheetName: 'Anuncios',
      title: 'Anuncios',
      subtitle: 'Resumen exportado desde la gestión administrativa de anuncios.',
      columns: buildAnnouncementExportColumns(),
      rows: filteredAnnouncements.value,
      landscape: true,
      emptyMessage: 'No hay anuncios para exportar.',
    })
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
    return {
      backgroundColor: source.background_color || '#0f7abf',
      color: source.text_color || '#ffffff',
    }
  }

  function truncateText(value, maxLength = 80) {
    const text = String(value || '').trim()
    if (text.length <= maxLength) return text || 'Sin mensaje'
    return `${text.slice(0, maxLength)}...`
  }

  function formatDateTime(value) {
    if (!value) return 'Sin fecha'
    return new Date(value).toLocaleString('es-CO', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
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

  onMounted(() => {
    loadAnnouncements()
    loadColors()
    loadLinkOptions()
  })

  onBeforeUnmount(() => {
    if (imagePreviewUrl.value.startsWith('blob:')) URL.revokeObjectURL(imagePreviewUrl.value)
  })

  return {
    CUSTOM_STORE_LINK_VALUE,
    TEXT_COLOR_OPTIONS,
    activeFilterCount,
    announcementStats,
    announcements,
    availableColors,
    canCreateAnnouncement,
    clearFilters,
    clearSelectedImage,
    closeDetailModal,
    closeEditorModal,
    confirmDeleteAnnouncement,
    editingAnnouncementId,
    exportAnnouncements,
    exportingFormat,
    filteredAnnouncements,
    filters,
    form,
    formErrors,
    formPreview,
    formatDateTime,
    handleAnnouncementTypeChange,
    imageInputRef,
    imagePreviewUrl,
    isPromoBannerType,
    isTopBarType,
    linkOptionGroups,
    loading,
    onImageSelected,
    onLinkOptionChange,
    openCreateModal,
    openDetailModal,
    openEditFromDetail,
    openEditModal,
    openImagePicker,
    pagination,
    previewBannerStyle,
    previewBarStyle,
    previewCardStyle,
    priorityClass,
    priorityLabel,
    resolveMediaUrl,
    saveAnnouncement,
    selectedAnnouncement,
    selectedColorInfo,
    selectedLinkOption,
    showDetailModal,
    showEditorModal,
    truncateText,
    typeLabel,
    validateField,
    announcementStatusClass,
    announcementStatusLabel,
  }
}
