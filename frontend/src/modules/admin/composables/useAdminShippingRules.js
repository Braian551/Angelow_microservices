import { computed, onMounted, reactive, ref } from 'vue'
import { shippingHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de reglas de envío.
 * Administra reglas basadas en peso, precio o zona geográfica con CRUD,
 * filtros y exportación. Reutiliza useAdminPagination y useAdminDataExport.
 */
export function useAdminShippingRules() {
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
  const rules = ref([])
  const selectedRule = ref(null)
  const showDetailModal = ref(false)
  const showEditorModal = ref(false)
  const editingRuleId = ref(null)

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const filters = reactive({ search: '', state: 'all' })

  const filteredRules = computed(() => {
    const term = filters.search.trim().toLowerCase()

    return rules.value.filter((rule) => {
      if (filters.state === 'active' && !rule.active) return false
      if (filters.state === 'inactive' && rule.active) return false
      if (filters.state === 'free' && !isFreeRule(rule)) return false
      if (filters.state === 'paid' && isFreeRule(rule)) return false
      if (!term) return true
      return [rangeLabel(rule), pricingNarrative(rule), rule.shipping_cost, rule.min_price, rule.max_price].join(' ').toLowerCase().includes(term)
    })
  })

  const pagination = useAdminPagination(filteredRules, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [filters.search, filters.state !== 'all'].filter(Boolean).length)

  // =====================================================
  // Estado del formulario
  // =====================================================
  const form = reactive({ min_price: 0, max_price: null, shipping_cost: 0, active: true })
  const formErrors = reactive({ min_price: '', max_price: '', shipping_cost: '' })

  // =====================================================
  // Valores derivados
  // =====================================================
  const previewRangeLabel = computed(() => rangeLabel(form))

  const ruleStats = computed(() => [
    { key: 'total', label: 'Total reglas', value: rules.value.length, icon: 'fas fa-sliders-h', color: 'primary' },
    { key: 'active', label: 'Activas', value: rules.value.filter((rule) => rule.active).length, icon: 'fas fa-check-circle', color: 'success' },
    { key: 'free', label: 'Sin recargo', value: rules.value.filter((rule) => isFreeRule(rule)).length, icon: 'fas fa-gift', color: 'warning' },
    { key: 'open', label: 'Sin tope', value: rules.value.filter((rule) => !rule.max_price).length, icon: 'fas fa-infinity', color: 'info' },
  ])

  // =====================================================
  // Helpers internos
  // =====================================================
  function resetForm() {
    form.min_price = 0
    form.max_price = null
    form.shipping_cost = 0
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
  }

  function isFreeRule(rule) {
    return Number(rule.shipping_cost || 0) === 0
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
  }

  function rangeLabel(rule) {
    const min = formatCurrency(rule.min_price || 0)
    if (rule.max_price === null || rule.max_price === undefined || rule.max_price === '') return `Desde ${min}`
    return `${min} a ${formatCurrency(rule.max_price)}`
  }

  function pricingNarrative(rule) {
    return isFreeRule(rule)
      ? 'En este rango no se suma recargo adicional al costo base del método.'
      : `En este rango se suma un recargo adicional de ${formatCurrency(rule.shipping_cost)} sobre el costo base.`
  }

  function ruleStatusLabel(rule) {
    if (!rule.active) return 'Inactiva'
    return isFreeRule(rule) ? 'Sin recargo' : 'Con recargo'
  }

  function ruleStatusClass(rule) {
    if (!rule.active) return 'rejected'
    return isFreeRule(rule) ? 'info' : 'active'
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  function validateField(field) {
    switch (field) {
      case 'min_price':
        formErrors.min_price = Number(form.min_price) >= 0 ? '' : 'El mínimo no puede ser negativo.'
        break
      case 'max_price':
        formErrors.max_price = ''
        if (form.max_price !== null && form.max_price !== '' && Number(form.max_price) < 0) formErrors.max_price = 'El máximo no puede ser negativo.'
        if (!formErrors.max_price && form.max_price !== null && form.max_price !== '' && Number(form.max_price) < Number(form.min_price || 0)) {
          formErrors.max_price = 'El máximo debe ser mayor o igual al mínimo.'
        }
        break
      case 'shipping_cost':
        formErrors.shipping_cost = Number(form.shipping_cost) >= 0 ? '' : 'El recargo no puede ser negativo.'
        break
      default:
        break
    }
  }

  function validateForm() {
    validateField('min_price')
    validateField('max_price')
    validateField('shipping_cost')
    return Object.values(formErrors).every((value) => !value)
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadRules() {
    loading.value = true
    try {
      const { data } = await shippingHttp.get('/admin/shipping-rules')
      rules.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      rules.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar las reglas por precio.') })
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Acciones CRUD
  // =====================================================
  async function saveRule() {
    if (saving.value) return

    if (!validateForm()) {
      showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
      return
    }

    const payload = {
      min_price: Number(form.min_price || 0),
      max_price: form.max_price === null || form.max_price === '' ? null : Number(form.max_price),
      shipping_cost: Number(form.shipping_cost || 0),
      active: form.active,
    }

    saving.value = true
    try {
      if (editingRuleId.value) {
        await shippingHttp.put(`/admin/shipping-rules/${editingRuleId.value}`, payload)
        showSnackbar({ type: 'success', message: 'Regla actualizada correctamente.' })
      } else {
        await shippingHttp.post('/admin/shipping-rules', payload)
        showSnackbar({ type: 'success', message: 'Regla creada correctamente.' })
      }
      closeEditorModal()
      await loadRules()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar la regla.') })
    } finally {
      saving.value = false
    }
  }

  function confirmDeleteRule(rule) {
    showAlert({
      type: 'warning',
      title: 'Eliminar regla',
      message: `Vas a eliminar el recargo del rango ${rangeLabel(rule)}. Esta accion no se puede deshacer.`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Eliminar',
          style: 'danger',
          callback: async () => {
            try {
              await shippingHttp.delete(`/admin/shipping-rules/${rule.id}`)
              showSnackbar({ type: 'success', message: 'Regla eliminada correctamente.' })
              if (selectedRule.value?.id === rule.id) closeDetailModal()
              await loadRules()
            } catch (error) {
              showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar la regla.') })
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
    editingRuleId.value = null
    resetForm()
    showEditorModal.value = true
  }

  function openEditModal(rule) {
    editingRuleId.value = rule.id
    clearErrors()
    form.min_price = Number(rule.min_price || 0)
    form.max_price = rule.max_price ?? null
    form.shipping_cost = Number(rule.shipping_cost || 0)
    form.active = Boolean(rule.active)
    showEditorModal.value = true
  }

  function closeEditorModal() {
    showEditorModal.value = false
    editingRuleId.value = null
    resetForm()
  }

  function openDetailModal(rule) {
    selectedRule.value = rule
    showDetailModal.value = true
  }

  function closeDetailModal() {
    selectedRule.value = null
    showDetailModal.value = false
  }

  function openEditFromDetail() {
    if (!selectedRule.value) return
    const current = selectedRule.value
    closeDetailModal()
    openEditModal(current)
  }

  // =====================================================
  // Exportación y presentación
  // =====================================================
  function buildShippingRuleExportColumns() {
    return [
      { header: 'Rango', value: (rule) => rangeLabel(rule), width: 18 },
      { header: 'Descripción', value: (rule) => pricingNarrative(rule), width: 34 },
      { header: 'Cargo adicional', value: (rule) => (isFreeRule(rule) ? 'Sin recargo' : `+${formatCurrency(rule.shipping_cost)}`), width: 18 },
      { header: 'Estado', value: (rule) => (rule.active ? 'Activo' : 'Inactivo'), width: 14 },
    ]
  }

  function exportRules(format) {
    return exportData({
      format,
      fileBaseName: 'recargos-por-rango-admin',
      sheetName: 'Recargos por rango',
      title: 'Recargos por rango',
      subtitle: 'Resumen exportado desde la gestión de recargos por rango.',
      columns: buildShippingRuleExportColumns(),
      rows: filteredRules.value,
      emptyMessage: 'No hay reglas de recargo para exportar.',
    })
  }

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(loadRules)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    clearFilters,
    closeDetailModal,
    closeEditorModal,
    confirmDeleteRule,
    editingRuleId,
    exportRules,
    exportingFormat,
    filteredRules,
    filters,
    form,
    formErrors,
    formatCurrency,
    isFreeRule,
    loading,
    openCreateModal,
    openDetailModal,
    openEditFromDetail,
    openEditModal,
    pagination,
    previewRangeLabel,
    pricingNarrative,
    rangeLabel,
    ruleStats,
    ruleStatusClass,
    ruleStatusLabel,
    saveRule,
    selectedRule,
    showDetailModal,
    showEditorModal,
    validateField,
  }
}
