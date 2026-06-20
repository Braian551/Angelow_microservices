import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import { useAdminPagination } from './useAdminPagination'
import { slugifyText } from '../utils/productSlug'

export function useAdminCollections() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado principal y referencias
  // =====================================================
  const collections = ref([])
  const loading = ref(true)
  const search = ref('')
  const statusFilter = ref('')
  const showModal = ref(false)
  const editing = ref(null)
  const imageInputRef = ref(null)
  const selectedImageFile = ref(null)
  const imagePreviewUrl = ref('')
  const slugManuallyEdited = ref(false)

  // =====================================================
  // Estado del formulario
  // =====================================================
  const form = reactive({
    name: '',
    slug: '',
    description: '',
    launch_date: '',
    is_active: true,
  })

  const errors = reactive({
    name: '',
    image: '',
  })

  // =====================================================
  // Filtros, paginación y métricas
  // =====================================================
  const filteredCollections = computed(() => {
    const term = search.value.trim().toLowerCase()

    return collections.value.filter((collection) => {
      const matchesSearch = !term || [collection.name, collection.slug, collection.description, formatDate(collection.launch_date)]
        .some((value) => String(value || '').toLowerCase().includes(term))

      const matchesStatus = !statusFilter.value
        || (statusFilter.value === 'active' && collection.is_active)
        || (statusFilter.value === 'inactive' && !collection.is_active)

      return matchesSearch && matchesStatus
    })
  })

  const pagination = useAdminPagination(filteredCollections, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

  const stats = computed(() => {
    const total = collections.value.length
    const active = collections.value.filter((collection) => collection.is_active).length
    const inactive = total - active
    const linkedProducts = collections.value.reduce((sum, collection) => sum + Number(collection.product_count || 0), 0)

    return [
      { key: 'total', label: 'Colecciones totales', value: String(total), icon: 'fas fa-layer-group', color: 'primary' },
      { key: 'active', label: 'Colecciones activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
      { key: 'inactive', label: 'Colecciones inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
      { key: 'products', label: 'Productos asociados', value: String(linkedProducts), icon: 'fas fa-box-open', color: 'info' },
    ]
  })

  // =====================================================
  // Helpers internos y presentación
  // =====================================================
  function normalizeCollection(row) {
    return {
      ...row,
      id: Number(row.id),
      name: row.name || row.nombre || 'Sin nombre',
      slug: row.slug || '',
      description: row.description || row.descripcion || '',
      image: row.image || row.imagen || null,
      launch_date: row.launch_date || '',
      product_count: Number(row.product_count || 0),
      is_active: typeof row.is_active === 'boolean' ? row.is_active : Boolean(Number(row.activo ?? 1)),
    }
  }

  function resolveCollectionImage(collection) {
    return resolveMediaUrl(collection.image, 'collection')
  }

  function excerpt(value, max = 100) {
    const text = String(value || '').trim()
    if (!text) return 'Sin descripción'
    return text.length > max ? `${text.slice(0, max).trim()}...` : text
  }

  function formatDate(value) {
    if (!value) return 'Sin fecha'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  // =====================================================
  // Gestión del formulario, slug e imagen
  // =====================================================
  function validateField(field) {
    if (field === 'name') {
      errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
    }
  }

  function onNameInput() {
    validateField('name')
    if (!slugManuallyEdited.value) {
      form.slug = slugifyText(form.name)
    }
  }

  function onSlugInput() {
    slugManuallyEdited.value = form.slug.trim() !== ''
    form.slug = slugifyText(form.slug)
  }

  function openImagePicker() {
    imageInputRef.value?.click()
  }

  function onImageSelected(event) {
    const file = event.target.files?.[0]
    if (!file) return

    clearSelectedImage(false)
    selectedImageFile.value = file
    imagePreviewUrl.value = URL.createObjectURL(file)
    errors.image = ''
  }

  function clearSelectedImage(resetInput = true) {
    if (imagePreviewUrl.value?.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreviewUrl.value)
    }
    imagePreviewUrl.value = ''
    selectedImageFile.value = null
    if (resetInput && imageInputRef.value) {
      imageInputRef.value.value = ''
    }
  }

  function resetForm() {
    form.name = ''
    form.slug = ''
    form.description = ''
    form.launch_date = ''
    form.is_active = true
    errors.name = ''
    errors.image = ''
    slugManuallyEdited.value = false
    clearSelectedImage(false)
  }

  function openModal(collection = null) {
    editing.value = collection
    resetForm()

    if (collection) {
      form.name = collection.name
      form.description = collection.description
      form.launch_date = collection.launch_date || ''
      form.is_active = collection.is_active

      if (collection.slug) {
        form.slug = collection.slug
        slugManuallyEdited.value = true
      }

      if (collection.image) {
        imagePreviewUrl.value = resolveCollectionImage(collection)
      }
    }

    showModal.value = true
  }

  function closeModal() {
    clearSelectedImage()
    showModal.value = false
    editing.value = null
  }

  // =====================================================
  // Carga de datos y refresco
  // =====================================================
  async function loadCollections() {
    loading.value = true
    try {
      const response = await catalogHttp.get('/admin/collections')
      const data = response.data?.data || response.data || []
      const rows = Array.isArray(data) ? data : (data.data || [])
      collections.value = rows.map(normalizeCollection)
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error cargando colecciones') })
    } finally {
      loading.value = false
    }
  }

  function clearFilters() {
    search.value = ''
    statusFilter.value = ''
  }

  // =====================================================
  // Acciones CRUD y cambios de estado
  // =====================================================
  async function saveCollection() {
    validateField('name')
    if (errors.name) return

    const payload = new FormData()
    payload.append('nombre', form.name.trim())
    payload.append('slug', form.slug?.trim() || '')
    payload.append('descripcion', form.description?.trim() || '')
    payload.append('activo', form.is_active ? '1' : '0')
    if (form.launch_date) {
      payload.append('launch_date', form.launch_date)
    }
    if (selectedImageFile.value) {
      payload.append('image_file', selectedImageFile.value)
    }

    const headers = { 'Content-Type': 'multipart/form-data' }

    try {
      if (editing.value?.id) {
        await catalogHttp.put(`/admin/collections/${editing.value.id}`, payload, { headers })
        showSnackbar({ type: 'success', message: 'Colección actualizada' })
      } else {
        await catalogHttp.post('/admin/collections', payload, { headers })
        showSnackbar({ type: 'success', message: 'Colección creada' })
      }

      closeModal()
      await loadCollections()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error guardando colección') })
    }
  }

  function confirmDelete(collection) {
    showAlert({
      type: 'warning',
      title: 'Eliminar colección',
      message: collection.product_count > 0
        ? `La colección ${collection.name} tiene productos asociados y no se puede eliminar.`
        : `¿Deseas eliminar la colección ${collection.name}?`,
      actions: collection.product_count > 0
        ? [{ text: 'Entendido', style: 'primary' }]
        : [
            { text: 'Cancelar', style: 'secondary' },
            {
              text: 'Eliminar',
              style: 'danger',
              callback: async () => {
                try {
                  await catalogHttp.delete(`/admin/collections/${collection.id}`)
                  showSnackbar({ type: 'success', message: 'Colección eliminada' })
                  await loadCollections()
                } catch (error) {
                  showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error eliminando colección') })
                }
              },
            },
          ],
    })
  }

  async function toggleStatus(collection) {
    try {
      await catalogHttp.put(`/admin/collections/${collection.id}`, {
        nombre: collection.name,
        slug: collection.slug || null,
        descripcion: collection.description || null,
        launch_date: collection.launch_date || null,
        activo: !collection.is_active,
      })
      showSnackbar({ type: 'success', message: !collection.is_active ? 'Colección activada' : 'Colección desactivada' })
      await loadCollections()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error actualizando estado') })
    }
  }

  // =====================================================
  // Ciclo de vida y limpieza
  // =====================================================
  onMounted(loadCollections)

  onBeforeUnmount(() => {
    clearSelectedImage(false)
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    clearFilters,
    clearSelectedImage,
    closeModal,
    collections,
    confirmDelete,
    editing,
    errors,
    excerpt,
    filteredCollections,
    form,
    formatDate,
    imageInputRef,
    imagePreviewUrl,
    loading,
    onImageSelected,
    onNameInput,
    onSlugInput,
    openImagePicker,
    openModal,
    pagination,
    resolveCollectionImage,
    saveCollection,
    search,
    showModal,
    stats,
    statusFilter,
    toggleStatus,
  }
}
