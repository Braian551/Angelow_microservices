import { computed, onMounted, reactive, ref } from 'vue'
import { discountHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de descuentos por volumen (bulk discounts).
 * Permite crear reglas de descuento basadas en rangos de cantidad de productos.
 * Incluye CRUD, validación, paginación y exportación de reglas.
 */
export function useAdminBulkDiscounts() {
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
  const filters = reactive({
    search: '',
    state: 'all',
  })

  const filteredRules = computed(() => {
    const term = filters.search.trim().toLowerCase()

    return rules.value.filter((rule) => {
      if (filters.state === 'active' && !rule.active) return false
      if (filters.state === 'inactive' && rule.active) return false
      if (filters.state === 'open-range' && rule.max_quantity) return false
      if (!term) return true

      return [
        quantityLabel(rule),
        quantityNarrative(rule),
        rule.discount_percent,
        rule.discount_percentage,
      ].join(' ').toLowerCase().includes(term)
    })
  })

  const pagination = useAdminPagination(filteredRules, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [filters.search, filters.state !== 'all'].filter(Boolean).length)

  // =====================================================
  // Gestión del formulario
  // =====================================================
  const form = reactive({
    min_quantity: 2,
    max_quantity: null,
    discount_percent: 10,
    active: true,
  })

  const formErrors = reactive({
    min_quantity: '',
    max_quantity: '',
    discount_percent: '',
  })

  // =====================================================
  // Rangos y niveles
  // =====================================================
  const previewQuantityLabel = computed(() => quantityLabel(form))

  /** Genera una etiqueta legible del rango de cantidad (ej: "Desde 5 unidades"). */
  function quantityLabel(rule) {
    const min = Number(rule.min_quantity || 0)

    if (!rule.max_quantity) return `Desde ${min} unidades`

    return `${min} a ${Number(rule.max_quantity)} unidades`
  }

  /** Genera una descripción narrativa de la regla de descuento por cantidad. */
  function quantityNarrative(rule) {
    return `Durante la compra se aplicará ${Number(rule.discount_percent || rule.discount_percentage || 0)}% al llegar a ${quantityLabel(rule).toLowerCase()}.`
  }

  // =====================================================
  // Tipo y valor
  // =====================================================
  /** Valida un campo específico del formulario de descuentos por volumen. */
  function validateField(field) {
    switch (field) {
      case 'min_quantity':
        formErrors.min_quantity = Number(form.min_quantity) >= 1 ? '' : 'La cantidad mínima debe ser mayor que cero.'
        break
      case 'max_quantity':
        formErrors.max_quantity = ''
        if (form.max_quantity !== null && form.max_quantity !== '' && Number(form.max_quantity) < 1) {
          formErrors.max_quantity = 'La cantidad máxima debe ser mayor que cero.'
        }
        if (
          !formErrors.max_quantity
          && form.max_quantity !== null
          && form.max_quantity !== ''
          && Number(form.max_quantity) < Number(form.min_quantity || 1)
        ) {
          formErrors.max_quantity = 'La cantidad máxima debe ser mayor o igual a la mínima.'
        }
        break
      case 'discount_percent':
        formErrors.discount_percent = Number(form.discount_percent) >= 1 && Number(form.discount_percent) <= 100
          ? ''
          : 'El descuento debe estar entre 1% y 100%.'
        break
      default:
        break
    }
  }

  /** Valida todos los campos del formulario. Retorna true si son válidos. */
  function validateForm() {
    validateField('min_quantity')
    validateField('max_quantity')
    validateField('discount_percent')
    return Object.values(formErrors).every((value) => !value)
  }

  // =====================================================
  // Asociaciones
  // =====================================================
  const bulkStats = computed(() => [
    { key: 'total', label: 'Total reglas', value: rules.value.length, icon: 'fas fa-layer-group', color: 'primary' },
    { key: 'active', label: 'Activas', value: rules.value.filter((rule) => rule.active).length, icon: 'fas fa-check-circle', color: 'success' },
    { key: 'open', label: 'Sin máximo', value: rules.value.filter((rule) => !rule.max_quantity).length, icon: 'fas fa-infinity', color: 'warning' },
    { key: 'top', label: 'Mayor descuento', value: `${Math.max(0, ...rules.value.map((rule) => Number(rule.discount_percent || rule.discount_percentage || 0)))}%`, icon: 'fas fa-percent', color: 'info' },
  ])

  // =====================================================
  // Fechas y prioridad
  // =====================================================
  /** Restablece el formulario a valores por defecto y limpia errores. */
  function resetForm() {
    form.min_quantity = 2
    form.max_quantity = null
    form.discount_percent = 10
    form.active = true
    clearErrors()
  }

  /** Limpia todos los errores de validación del formulario. */
  function clearErrors() {
    Object.keys(formErrors).forEach((key) => {
      formErrors[key] = ''
    })
  }

  /** Restablece los filtros de búsqueda y estado a valores iniciales. */
  function clearFilters() {
    filters.search = ''
    filters.state = 'all'
  }

  /** Extrae el mensaje de error de una respuesta HTTP con valor por defecto. */
  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  /** Obtiene todas las reglas de descuento por volumen desde el backend. */
  async function loadRules() {
    loading.value = true
    try {
      const { data } = await discountHttp.get('/admin/bulk-discounts')
      rules.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      rules.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar las reglas por cantidad.') })
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Acciones CRUD
  // =====================================================
  /** Valida y guarda una regla de descuento (creación o actualización). */
  async function saveRule() {
    if (saving.value) return

    if (!validateForm()) {
      showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
      return
    }

    const payload = {
      min_quantity: Number(form.min_quantity || 1),
      max_quantity: form.max_quantity === null || form.max_quantity === '' ? null : Number(form.max_quantity),
      discount_percent: Number(form.discount_percent || 0),
      active: form.active,
    }

    saving.value = true
    try {
      if (editingRuleId.value) {
        await discountHttp.put(`/admin/bulk-discounts/${editingRuleId.value}`, payload)
        showSnackbar({ type: 'success', message: 'Regla actualizada correctamente.' })
      } else {
        await discountHttp.post('/admin/bulk-discounts', payload)
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

  /** Muestra confirmación y elimina una regla de descuento por su ID. */
  function confirmDeleteRule(rule) {
    showAlert({
      type: 'warning',
      title: 'Eliminar regla',
      message: `Vas a eliminar la regla ${quantityLabel(rule)}. Esta acción no se puede deshacer.`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Eliminar',
          style: 'danger',
          callback: async () => {
            try {
              await discountHttp.delete(`/admin/bulk-discounts/${rule.id}`)
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
  /** Abre el modal de creación con formulario limpio. */
  function openCreateModal() {
    editingRuleId.value = null
    resetForm()
    showEditorModal.value = true
  }

  /** Carga los datos de una regla existente en el formulario para edición. */
  function openEditModal(rule) {
    editingRuleId.value = rule.id
    clearErrors()
    form.min_quantity = Number(rule.min_quantity || 1)
    form.max_quantity = rule.max_quantity ?? null
    form.discount_percent = Number(rule.discount_percent || rule.discount_percentage || 0)
    form.active = Boolean(rule.active)
    showEditorModal.value = true
  }

  /** Cierra el modal del editor y reinicia el formulario. */
  function closeEditorModal() {
    showEditorModal.value = false
    editingRuleId.value = null
    resetForm()
  }

  /** Abre el modal de detalle con la información de la regla seleccionada. */
  function openDetailModal(rule) {
    selectedRule.value = rule
    showDetailModal.value = true
  }

  /** Cierra el modal de detalle y limpia la selección. */
  function closeDetailModal() {
    selectedRule.value = null
    showDetailModal.value = false
  }

  /** Transiciona del modal de detalle al de edición con la regla actual. */
  function openEditFromDetail() {
    if (!selectedRule.value) return
    const currentRule = selectedRule.value
    closeDetailModal()
    openEditModal(currentRule)
  }

  // =====================================================
  // Exportación
  // =====================================================
  /** Define las columnas de exportación Excel/PDF de las reglas de descuento. */
  function buildBulkDiscountExportColumns() {
    return [
      { header: 'Escala', value: (rule) => quantityLabel(rule), width: 18 },
      { header: 'Descuento', value: (rule) => `${Number(rule.discount_percent || rule.discount_percentage || 0)}%`, width: 14 },
      { header: 'Descripción', value: (rule) => quantityNarrative(rule), width: 34 },
      { header: 'Estado', value: (rule) => (rule.active ? 'Activo' : 'Inactivo'), width: 14 },
    ]
  }

  /** Exporta las reglas filtradas en el formato indicado (excel o pdf). */
  function exportRules(format) {
    return exportData({
      format,
      fileBaseName: 'descuentos-por-cantidad-admin',
      sheetName: 'Descuentos por cantidad',
      title: 'Descuentos por cantidad',
      subtitle: 'Resumen exportado desde la gestión de descuentos por volumen.',
      columns: buildBulkDiscountExportColumns(),
      rows: filteredRules.value,
      emptyMessage: 'No hay reglas de descuento por cantidad para exportar.',
    })
  }

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
  onMounted(loadRules)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    bulkStats,
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
    loadRules,
    loading,
    openCreateModal,
    openDetailModal,
    openEditFromDetail,
    openEditModal,
    pagination,
    previewQuantityLabel,
    quantityLabel,
    quantityNarrative,
    rules,
    saveRule,
    selectedRule,
    showDetailModal,
    showEditorModal,
    validateField,
  }
}
