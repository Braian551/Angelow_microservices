import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import { useAdminPagination } from './useAdminPagination'
import { slugifyText } from '../utils/productSlug'

/**
 * Composable para la gestión de categorías de productos.
 * Administra CRUD, imágenes, slugs, filtros y paginación de categorías.
 * Reutiliza useAdminPagination para la paginación de la lista.
 */
export function useAdminCategories() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado general y referencias principales
  // =====================================================
  const categories = ref([])
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
    is_active: true,
  })

  const errors = reactive({
    name: '',
    image: '',
  })

  // =====================================================
  // Filtros, paginación y métricas
  // =====================================================
  const filteredCategories = computed(() => {
    const term = search.value.trim().toLowerCase()

    return categories.value.filter((category) => {
      const matchesSearch = !term || [category.name, category.slug, category.description]
        .some((value) => String(value || '').toLowerCase().includes(term))

      const matchesStatus = !statusFilter.value
        || (statusFilter.value === 'active' && category.is_active)
        || (statusFilter.value === 'inactive' && !category.is_active)

      return matchesSearch && matchesStatus
    })
  })

  const pagination = useAdminPagination(filteredCategories, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

  const stats = computed(() => {
    const total = categories.value.length
    const active = categories.value.filter((category) => category.is_active).length
    const inactive = total - active
    const linkedProducts = categories.value.reduce((sum, category) => sum + Number(category.product_count || 0), 0)

    return [
      { key: 'total', label: 'Categorías totales', value: String(total), icon: 'fas fa-tags', color: 'primary' },
      { key: 'active', label: 'Categorías activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
      { key: 'inactive', label: 'Categorías inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
      { key: 'products', label: 'Productos asociados', value: String(linkedProducts), icon: 'fas fa-box-open', color: 'info' },
    ]
  })

  // =====================================================
  // Helpers internos y presentación
  // =====================================================
  /** Normaliza los datos de una categoría del backend a un formato consistente. */
  function normalizeCategory(item) {
    return {
      ...item,
      id: Number(item.id),
      name: item.name || item.nombre || 'Sin nombre',
      slug: item.slug || '',
      description: item.description || item.descripcion || '',
      image: item.image || item.imagen || null,
      product_count: Number(item.product_count || 0),
      is_active: typeof item.is_active === 'boolean' ? item.is_active : Boolean(Number(item.activo ?? 1)),
    }
  }

  /** Resuelve la URL completa de la imagen de una categoría. */
  function resolveCategoryImage(category) {
    return resolveMediaUrl(category.image, 'category')
  }

  /** Trunca un texto a la longitud máxima con puntos suspensivos. */
  function excerpt(value, max = 100) {
    const text = String(value || '').trim()
    if (!text) return 'Sin descripción'
    return text.length > max ? `${text.slice(0, max).trim()}...` : text
  }

  /** Extrae el mensaje de error de una respuesta HTTP con valor por defecto. */
  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  // =====================================================
  // Gestión del formulario e imagen
  // =====================================================
  /** Valida el campo nombre: debe tener al menos 2 caracteres. */
  function validateField(field) {
    if (field === 'name') {
      errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
    }
  }

  /** Genera el slug automáticamente al escribir el nombre, si no fue editado manualmente. */
  function onNameInput() {
    validateField('name')
    if (!slugManuallyEdited.value) {
      form.slug = slugifyText(form.name)
    }
  }

  /** Marca el slug como editado manualmente y lo normaliza. */
  function onSlugInput() {
    slugManuallyEdited.value = form.slug.trim() !== ''
    form.slug = slugifyText(form.slug)
  }

  /** Abre el selector de archivos de imagen oculto. */
  function openImagePicker() {
    imageInputRef.value?.click()
  }

  /** Maneja la selección de archivo: genera URL de previsualización temporal. */
  function onImageSelected(event) {
    const file = event.target.files?.[0]
    if (!file) return

    clearSelectedImage(false)
    selectedImageFile.value = file
    imagePreviewUrl.value = URL.createObjectURL(file)
    errors.image = ''
  }

  /** Limpia la imagen seleccionada y revierte la previsualización. */
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

  /** Reinicia el formulario a valores por defecto y limpia imagen y errores. */
  function resetForm() {
    form.name = ''
    form.slug = ''
    form.description = ''
    form.is_active = true
    errors.name = ''
    errors.image = ''
    slugManuallyEdited.value = false
    clearSelectedImage(false)
  }

  /** Abre el modal en modo creación o edición con los datos de la categoría. */
  function openModal(category = null) {
    editing.value = category
    resetForm()

    if (category) {
      form.name = category.name
      form.description = category.description
      form.is_active = category.is_active

      if (category.slug) {
        form.slug = category.slug
        slugManuallyEdited.value = true
      }

      if (category.image) {
        imagePreviewUrl.value = resolveCategoryImage(category)
      }
    }

    showModal.value = true
  }

  /** Cierra el modal y limpia el estado de edición. */
  function closeModal() {
    clearSelectedImage()
    showModal.value = false
    editing.value = null
  }

  // =====================================================
  // Carga de datos y refresco
  // =====================================================
  /** Obtiene la lista de categorías desde el backend. */
  async function loadCategories() {
    loading.value = true
    try {
      const response = await catalogHttp.get('/admin/categories')
      const data = response.data?.data || response.data || []
      const rows = Array.isArray(data) ? data : (data.data || [])
      categories.value = rows.map(normalizeCategory)
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error cargando categorías') })
    } finally {
      loading.value = false
    }
  }

  /** Restablece los filtros de búsqueda y estado a valores iniciales. */
  function clearFilters() {
    search.value = ''
    statusFilter.value = ''
  }

  // =====================================================
  // Acciones CRUD y estado
  // =====================================================
  /** Valida y guarda una categoría (creación o actualización) con FormData. */
  async function saveCategory() {
    validateField('name')
    if (errors.name) return

    const payload = new FormData()
    payload.append('nombre', form.name.trim())
    payload.append('slug', form.slug?.trim() || '')
    payload.append('descripcion', form.description?.trim() || '')
    payload.append('activo', form.is_active ? '1' : '0')

    if (selectedImageFile.value) {
      payload.append('image_file', selectedImageFile.value)
    }

    const headers = { 'Content-Type': 'multipart/form-data' }

    try {
      if (editing.value?.id) {
        await catalogHttp.put(`/admin/categories/${editing.value.id}`, payload, { headers })
        showSnackbar({ type: 'success', message: 'Categoría actualizada' })
      } else {
        await catalogHttp.post('/admin/categories', payload, { headers })
        showSnackbar({ type: 'success', message: 'Categoría creada' })
      }

      closeModal()
      await loadCategories()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error guardando categoría') })
    }
  }

  /** Muestra confirmación y elimina una categoría (bloqueada si tiene productos). */
  function confirmDelete(category) {
    showAlert({
      type: 'warning',
      title: 'Eliminar categoría',
      message: category.product_count > 0
        ? `La categoría ${category.name} tiene productos asociados y no se puede eliminar.`
        : `¿Deseas eliminar la categoría ${category.name}?`,
      actions: category.product_count > 0
        ? [{ text: 'Entendido', style: 'primary' }]
        : [
            { text: 'Cancelar', style: 'secondary' },
            {
              text: 'Eliminar',
              style: 'danger',
              callback: async () => {
                try {
                  await catalogHttp.delete(`/admin/categories/${category.id}`)
                  showSnackbar({ type: 'success', message: 'Categoría eliminada' })
                  await loadCategories()
                } catch (error) {
                  showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error eliminando categoría') })
                }
              },
            },
          ],
    })
  }

  /** Cambia el estado activo/inactivo de una categoría. */
  async function toggleStatus(category) {
    try {
      await catalogHttp.put(`/admin/categories/${category.id}`, {
        nombre: category.name,
        slug: category.slug || null,
        descripcion: category.description || null,
        activo: !category.is_active,
      })
      showSnackbar({
        type: 'success',
        message: !category.is_active ? 'Categoría activada' : 'Categoría desactivada',
      })
      await loadCategories()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error actualizando estado') })
    }
  }

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
  watch(showModal, (isOpen) => {
    if (!isOpen) {
      slugManuallyEdited.value = false
    }
  })

  onMounted(loadCategories)

  onBeforeUnmount(() => {
    clearSelectedImage(false)
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    categories,
    clearFilters,
    clearSelectedImage,
    closeModal,
    confirmDelete,
    editing,
    errors,
    excerpt,
    filteredCategories,
    form,
    imageInputRef,
    imagePreviewUrl,
    loadCategories,
    loading,
    onImageSelected,
    onNameInput,
    onSlugInput,
    openImagePicker,
    openModal,
    pagination,
    resolveCategoryImage,
    saveCategory,
    search,
    showModal,
    stats,
    statusFilter,
    toggleStatus,
  }
}
