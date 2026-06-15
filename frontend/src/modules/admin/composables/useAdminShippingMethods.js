import { computed, onMounted, reactive, ref } from 'vue'
import { shippingHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

export function useAdminShippingMethods() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

  // =====================================================
  // Estado principal
  // =====================================================
  const loading = ref(true)
  const saving = ref(false)
  const methods = ref([])
  const selectedMethod = ref(null)
  const showDetailModal = ref(false)
  const showEditorModal = ref(false)
  const editingMethodId = ref(null)

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const filters = reactive({ search: '', state: 'all', city: 'all' })

  const availableCities = computed(() => [...new Set(methods.value.map((method) => String(method.city || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b)))

  const filteredMethods = computed(() => {
    const term = filters.search.trim().toLowerCase()

    return methods.value.filter((method) => {
      if (filters.state === 'active' && !method.active) return false
      if (filters.state === 'inactive' && method.active) return false
      if (filters.state === 'free-threshold' && !method.free_shipping_minimum) return false
      if (filters.city === 'national' && method.city) return false
      if (filters.city !== 'all' && filters.city !== 'national' && method.city !== filters.city) return false
      if (!term) return true

      return [method.name, method.description, method.city, method.delivery_time].join(' ').toLowerCase().includes(term)
    })
  })

  const pagination = useAdminPagination(filteredMethods, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.city !== 'all'].filter(Boolean).length)

  // =====================================================
  // Estado del formulario
  // =====================================================
  const form = reactive({
    name: '',
    description: '',
    base_cost: 0,
    delivery_time: '',
    estimated_days_min: null,
    estimated_days_max: null,
    free_shipping_minimum: null,
    city: '',
    icon: 'fa-truck',
    active: true,
  })

  const formErrors = reactive({
    name: '',
    base_cost: '',
    free_shipping_minimum: '',
    estimated_days: '',
    delivery_time: '',
    city: '',
  })

  // =====================================================
  // Valores derivados
  // =====================================================
  const shippingStats = computed(() => [
    { key: 'total', label: 'Total métodos', value: methods.value.length, icon: 'fas fa-truck', color: 'primary' },
    { key: 'active', label: 'Activos', value: methods.value.filter((method) => method.active).length, icon: 'fas fa-check-circle', color: 'success' },
    { key: 'cities', label: 'Ciudades fijas', value: availableCities.value.length, icon: 'fas fa-map-marker-alt', color: 'info' },
    { key: 'free', label: 'Con envío gratis', value: methods.value.filter((method) => Number(method.free_shipping_minimum || 0) > 0).length, icon: 'fas fa-gift', color: 'warning' },
  ])

  // =====================================================
  // Helpers internos
  // =====================================================
  function resetForm() {
    form.name = ''
    form.description = ''
    form.base_cost = 0
    form.delivery_time = ''
    form.estimated_days_min = null
    form.estimated_days_max = null
    form.free_shipping_minimum = null
    form.city = ''
    form.icon = 'fa-truck'
    form.active = true
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
    filters.city = 'all'
  }

  function deliveryWindowLabel(method) {
    const min = Number(method.estimated_days_min || 0)
    const max = Number(method.estimated_days_max || 0)
    if (min && max) return `${min} a ${max} días`
    if (max) return `Hasta ${max} días`
    if (min) return `${min} días`
    return 'Sin rango definido'
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  function validateField(field) {
    switch (field) {
      case 'name':
        formErrors.name = form.name.trim().length >= 3 ? '' : 'El nombre debe tener al menos 3 caracteres.'
        break
      case 'base_cost':
        formErrors.base_cost = Number(form.base_cost) >= 0 ? '' : 'El costo base no puede ser negativo.'
        break
      case 'free_shipping_minimum':
        formErrors.free_shipping_minimum = form.free_shipping_minimum === null || form.free_shipping_minimum === '' || Number(form.free_shipping_minimum) >= 0
          ? ''
          : 'El umbral de envío gratis no puede ser negativo.'
        break
      case 'estimated_days':
        formErrors.estimated_days = ''
        if (form.estimated_days_min && Number(form.estimated_days_min) < 1) formErrors.estimated_days = 'Los días mínimos deben ser mayores que cero.'
        if (!formErrors.estimated_days && form.estimated_days_max && Number(form.estimated_days_max) < 1) formErrors.estimated_days = 'Los días máximos deben ser mayores que cero.'
        if (!formErrors.estimated_days && form.estimated_days_min && form.estimated_days_max && Number(form.estimated_days_max) < Number(form.estimated_days_min)) {
          formErrors.estimated_days = 'El máximo debe ser mayor o igual al mínimo.'
        }
        break
      case 'delivery_time':
        formErrors.delivery_time = form.delivery_time && form.delivery_time.trim().length < 4 ? 'Describe mejor la promesa de entrega.' : ''
        break
      case 'city':
        formErrors.city = form.city && form.city.trim().length < 3 ? 'La ciudad debe tener al menos 3 caracteres.' : ''
        break
      default:
        break
    }
  }

  function validateForm() {
    validateField('name')
    validateField('base_cost')
    validateField('free_shipping_minimum')
    validateField('estimated_days')
    validateField('delivery_time')
    validateField('city')
    return Object.values(formErrors).every((value) => !value)
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadMethods() {
    loading.value = true
    try {
      const { data } = await shippingHttp.get('/admin/shipping-methods')
      methods.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      methods.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los métodos de envío.') })
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Acciones CRUD
  // =====================================================
  async function saveMethod() {
    if (saving.value) return

    if (!validateForm()) {
      showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
      return
    }

    const payload = {
      name: form.name.trim(),
      description: form.description.trim() || null,
      base_cost: Number(form.base_cost || 0),
      delivery_time: form.delivery_time.trim() || null,
      estimated_days_min: form.estimated_days_min || null,
      estimated_days_max: form.estimated_days_max || null,
      free_shipping_minimum: form.free_shipping_minimum || null,
      city: form.city.trim() || null,
      icon: form.icon,
      active: form.active,
    }

    saving.value = true
    try {
      if (editingMethodId.value) {
        await shippingHttp.put(`/admin/shipping-methods/${editingMethodId.value}`, payload)
        showSnackbar({ type: 'success', message: 'Método actualizado correctamente.' })
      } else {
        await shippingHttp.post('/admin/shipping-methods', payload)
        showSnackbar({ type: 'success', message: 'Método creado correctamente.' })
      }

      closeEditorModal()
      await loadMethods()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el método.') })
    } finally {
      saving.value = false
    }
  }

  function confirmDeleteMethod(method) {
    showAlert({
      type: 'warning',
      title: 'Eliminar método',
      message: `Vas a eliminar ${method.name}. Esta acción no se puede deshacer.`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Eliminar',
          style: 'danger',
          callback: async () => {
            try {
              await shippingHttp.delete(`/admin/shipping-methods/${method.id}`)
              showSnackbar({ type: 'success', message: 'Método eliminado correctamente.' })
              if (selectedMethod.value?.id === method.id) closeDetailModal()
              await loadMethods()
            } catch (error) {
              showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el método.') })
            }
          },
        },
      ],
    })
  }

  // =====================================================
  // Gestión de modales
  // =====================================================
  function openCreateModal() {
    editingMethodId.value = null
    resetForm()
    showEditorModal.value = true
  }

  function openEditModal(method) {
    editingMethodId.value = method.id
    clearErrors()
    form.name = method.name || ''
    form.description = method.description || ''
    form.base_cost = Number(method.base_cost || 0)
    form.delivery_time = method.delivery_time || ''
    form.estimated_days_min = method.estimated_days_min ?? null
    form.estimated_days_max = method.estimated_days_max ?? null
    form.free_shipping_minimum = method.free_shipping_minimum ?? null
    form.city = method.city || ''
    form.icon = method.icon || 'fa-truck'
    form.active = Boolean(method.active)
    showEditorModal.value = true
  }

  function closeEditorModal() {
    showEditorModal.value = false
    editingMethodId.value = null
    resetForm()
  }

  function openDetailModal(method) {
    selectedMethod.value = method
    showDetailModal.value = true
  }

  function closeDetailModal() {
    selectedMethod.value = null
    showDetailModal.value = false
  }

  function openEditFromDetail() {
    if (!selectedMethod.value) return
    const current = selectedMethod.value
    closeDetailModal()
    openEditModal(current)
  }

  // =====================================================
  // Exportación y utilidades de presentación
  // =====================================================
  function buildShippingMethodExportColumns() {
    return [
      { header: 'Método', value: (method) => method.name },
      { header: 'Cobertura', value: (method) => method.city || 'Cobertura general', width: 18 },
      { header: 'Descripción', value: (method) => method.description || 'Sin descripción operativa', width: 30 },
      {
        header: 'Costo base',
        value: (method) => formatCurrency(method.base_cost),
        excelValue: (method) => Number(method.base_cost || 0),
        excelType: 'currency',
        align: 'right',
        width: 15,
      },
      { header: 'Gratis desde', value: (method) => (method.free_shipping_minimum ? formatCurrency(method.free_shipping_minimum) : 'No aplica'), width: 16 },
      { header: 'Promesa', value: (method) => deliveryWindowLabel(method), width: 16 },
      { header: 'Estado', value: (method) => (method.active ? 'Activo' : 'Inactivo'), width: 14 },
    ]
  }

  function exportMethods(format) {
    return exportData({
      format,
      fileBaseName: 'metodos-envio-admin',
      sheetName: 'Definir envíos',
      title: 'Definir envíos',
      subtitle: 'Resumen exportado desde la gestión de métodos de envío.',
      columns: buildShippingMethodExportColumns(),
      rows: filteredMethods.value,
      landscape: true,
      emptyMessage: 'No hay métodos de envío para exportar.',
    })
  }

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(loadMethods)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    availableCities,
    clearFilters,
    closeDetailModal,
    closeEditorModal,
    confirmDeleteMethod,
    deliveryWindowLabel,
    editingMethodId,
    exportMethods,
    exportingFormat,
    filteredMethods,
    filters,
    form,
    formErrors,
    formatCurrency,
    loadMethods,
    loading,
    openCreateModal,
    openDetailModal,
    openEditFromDetail,
    openEditModal,
    pagination,
    saveMethod,
    selectedMethod,
    shippingStats,
    showDetailModal,
    showEditorModal,
    validateField,
  }
}
