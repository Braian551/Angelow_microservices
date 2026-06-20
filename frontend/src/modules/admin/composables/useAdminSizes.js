import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from './useAdminPagination'

export function useAdminSizes() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado principal
  // =====================================================
  const sizes = ref([])
  const loading = ref(true)
  const saving = ref(false)
  const showModal = ref(false)
  const editing = ref(null)

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const search = ref('')
  const statusFilter = ref('')

  const filteredSizes = computed(() => {
    const term = search.value.trim().toLowerCase()

    return sizes.value.filter((size) => {
      const matchesSearch = !term || [size.name, size.description]
        .some((value) => String(value || '').toLowerCase().includes(term))

      const matchesStatus = !statusFilter.value
        || (statusFilter.value === 'active' && size.is_active)
        || (statusFilter.value === 'inactive' && !size.is_active)

      return matchesSearch && matchesStatus
    })
  })

  const pagination = useAdminPagination(filteredSizes, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

  // =====================================================
  // Estado del formulario
  // =====================================================
  const form = reactive({
    name: '',
    description: '',
    sort_order: '',
    is_active: true,
  })

  const errors = reactive({
    name: '',
    sort_order: '',
  })

  // =====================================================
  // Valores derivados
  // =====================================================
  const stats = computed(() => {
    const total = sizes.value.length
    const active = sizes.value.filter((size) => size.is_active).length
    const inactive = total - active
    const linkedVariants = sizes.value.reduce((sum, size) => sum + Number(size.product_count || 0), 0)

    return [
      { key: 'total', label: 'Tallas totales', value: String(total), icon: 'fas fa-ruler-combined', color: 'primary' },
      { key: 'active', label: 'Tallas activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
      { key: 'inactive', label: 'Tallas inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
      { key: 'variants', label: 'Variantes asociadas', value: String(linkedVariants), icon: 'fas fa-boxes', color: 'info' },
    ]
  })

  // =====================================================
  // Helpers internos
  // =====================================================
  function normalizeSize(rawSize) {
    return {
      ...rawSize,
      id: Number(rawSize.id),
      name: rawSize.name || rawSize.nombre || rawSize.size_label || 'Sin nombre',
      description: rawSize.description || rawSize.descripcion || '',
      sort_order: rawSize.sort_order ?? rawSize.order_position ?? rawSize.orden ?? null,
      product_count: Number(rawSize.product_count || 0),
      is_active: typeof rawSize.is_active === 'boolean' ? rawSize.is_active : Boolean(Number(rawSize.activo ?? 1)),
    }
  }

  function excerpt(value, max = 100) {
    const text = String(value || '').trim()
    if (!text) return 'Sin descripción'
    return text.length > max ? `${text.slice(0, max).trim()}...` : text
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  function validateField(field) {
    if (field === 'name') {
      errors.name = form.name.trim().length >= 1 ? '' : 'El nombre es obligatorio.'
    }

    if (field === 'sort_order') {
      errors.sort_order = form.sort_order === '' || Number(form.sort_order) >= 0
        ? ''
        : 'El orden no puede ser negativo.'
    }
  }

  function clearFilters() {
    search.value = ''
    statusFilter.value = ''
  }

  function resetForm() {
    form.name = ''
    form.description = ''
    form.sort_order = ''
    form.is_active = true
    errors.name = ''
    errors.sort_order = ''
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadSizes() {
    loading.value = true
    try {
      const response = await catalogHttp.get('/admin/sizes', { params: { include_inactive: true } })
      const data = response.data?.data || response.data || []
      const rows = Array.isArray(data) ? data : (data.data || [])
      sizes.value = rows.map(normalizeSize)
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error cargando tallas') })
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Acciones CRUD y activación
  // =====================================================
  function openModal(size = null) {
    editing.value = size
    resetForm()

    if (size) {
      form.name = size.name
      form.description = size.description
      form.sort_order = size.sort_order ?? ''
      form.is_active = size.is_active
    }

    showModal.value = true
  }

  function closeModal() {
    showModal.value = false
    editing.value = null
  }

  async function saveSize() {
    if (saving.value) return

    validateField('name')
    validateField('sort_order')

    if (errors.name || errors.sort_order) return

    const payload = {
      name: form.name.trim(),
      description: form.description?.trim() || null,
      sort_order: form.sort_order === '' ? null : Number(form.sort_order),
      is_active: form.is_active,
    }

    saving.value = true
    try {
      if (editing.value?.id) {
        await catalogHttp.put(`/admin/sizes/${editing.value.id}`, payload)
        showSnackbar({ type: 'success', message: 'Talla actualizada' })
      } else {
        await catalogHttp.post('/admin/sizes', payload)
        showSnackbar({ type: 'success', message: 'Talla creada' })
      }

      closeModal()
      await loadSizes()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error guardando talla') })
    } finally {
      saving.value = false
    }
  }

  function confirmDelete(size) {
    showAlert({
      type: 'warning',
      title: 'Eliminar talla',
      message: size.product_count > 0
        ? `La talla ${size.name} ya esta asociada a variantes y no se puede eliminar.`
        : `¿Deseas eliminar la talla ${size.name}?`,
      actions: size.product_count > 0
        ? [{ text: 'Entendido', style: 'primary' }]
        : [
            { text: 'Cancelar', style: 'secondary' },
            {
              text: 'Eliminar',
              style: 'danger',
              callback: async () => {
                try {
                  await catalogHttp.delete(`/admin/sizes/${size.id}`)
                  showSnackbar({ type: 'success', message: 'Talla eliminada' })
                  await loadSizes()
                } catch (error) {
                  showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error eliminando talla') })
                }
              },
            },
          ],
    })
  }

  async function toggleStatus(size) {
    try {
      await catalogHttp.put(`/admin/sizes/${size.id}`, {
        name: size.name,
        description: size.description || null,
        sort_order: size.sort_order,
        is_active: !size.is_active,
      })
      showSnackbar({ type: 'success', message: !size.is_active ? 'Talla activada' : 'Talla desactivada' })
      await loadSizes()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'Error actualizando estado') })
    }
  }

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
  onMounted(loadSizes)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    clearFilters,
    closeModal,
    confirmDelete,
    editing,
    errors,
    excerpt,
    filteredSizes,
    form,
    loading,
    openModal,
    pagination,
    saveSize,
    search,
    showModal,
    sizes,
    stats,
    statusFilter,
    toggleStatus,
    validateField,
  }
}
