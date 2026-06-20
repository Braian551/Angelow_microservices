import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { discountHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

export function useAdminDiscountCodes() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()
  const router = useRouter()

  // =====================================================
  // Estado principal
  // =====================================================
  const loading = ref(true)
  const codes = ref([])
  const selectedCode = ref(null)
  const showDetailModal = ref(false)
  const showEditorModal = ref(false)
  const editingCodeId = ref(null)
  const showMassCampaignModal = ref(false)
  const showSpecificCampaignModal = ref(false)
  const campaignSubmitting = ref(false)
  const campaignCustomersLoading = ref(false)
  const campaignCustomers = ref([])
  const specificCampaignSearch = ref('')
  const autoGenerateCode = ref(true)

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const filters = reactive({ search: '', state: 'all', type: 'all' })

  const filteredCodes = computed(() => {
    const term = filters.search.trim().toLowerCase()

    return codes.value.filter((code) => {
      if (filters.type !== 'all' && code.type !== filters.type) return false
      if (filters.state !== 'all' && codeStatusKey(code) !== filters.state) return false
      if (!term) return true

      return [code.code, code.type_label, code.discount_type_name].join(' ').toLowerCase().includes(term)
    })
  })

  const pagination = useAdminPagination(filteredCodes, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.type !== 'all'].filter(Boolean).length)

  // =====================================================
  // Estado del formulario
  // =====================================================
  const form = reactive({
    code: '',
    type: 'percent',
    value: 10,
    max_uses: null,
    start_date: '',
    expires_at: '',
    active: true,
    is_single_use: false,
  })

  const formErrors = reactive({ code: '', type: '', value: '', max_uses: '', start_date: '', expires_at: '' })

  // =====================================================
  // Estado de campañas y destinatarios
  // =====================================================
  const massCampaignForm = reactive({
    discount_code_id: '',
    send_notification: true,
    send_email: true,
  })

  const massCampaignErrors = reactive({
    discount_code_id: '',
    channels: '',
  })

  const specificCampaignForm = reactive({
    discount_code_id: '',
    send_notification: true,
    send_email: true,
    user_ids: [],
  })

  const specificCampaignErrors = reactive({
    discount_code_id: '',
    channels: '',
    user_ids: '',
  })

  // =====================================================
  // Valores derivados
  // =====================================================
  const discountStats = computed(() => [
    { key: 'total', label: 'Total códigos', value: codes.value.length, icon: 'fas fa-tags', color: 'primary' },
    { key: 'active', label: 'Activos', value: codes.value.filter((code) => codeStatusKey(code) === 'active').length, icon: 'fas fa-check-circle', color: 'success' },
    { key: 'expired', label: 'Vencidos', value: codes.value.filter((code) => codeStatusKey(code) === 'expired').length, icon: 'fas fa-calendar-times', color: 'warning' },
    { key: 'single', label: 'Uso único', value: codes.value.filter((code) => code.is_single_use).length, icon: 'fas fa-user-shield', color: 'info' },
  ])

  const campaignCodeOptions = computed(() => codes.value.map((code) => ({
    id: code.id,
    code: code.code,
    type: code.type,
    value: code.value,
  })))

  const massCampaignHasRecipients = computed(() => campaignCustomers.value.length > 0)

  const massCampaignAvailabilityTitle = computed(() => {
    if (campaignCustomersLoading.value) {
      return 'Validando clientes disponibles'
    }

    return massCampaignHasRecipients.value
      ? 'Clientes listos para la campaña'
      : 'No hay clientes disponibles para este envío'
  })

  const massCampaignAvailabilityMessage = computed(() => {
    if (campaignCustomersLoading.value) {
      return 'Estamos consultando la base de clientes antes de habilitar el envío masivo.'
    }

    if (!massCampaignHasRecipients.value) {
      return 'Registra o habilita clientes antes de lanzar esta campaña. Cuando existan destinatarios válidos, el envío masivo se activará automáticamente.'
    }

    if (campaignCustomers.value.length >= 200) {
      return 'Se detectaron al menos 200 clientes disponibles para la campaña.'
    }

    return `Se detectaron ${campaignCustomers.value.length} clientes disponibles para esta campaña.`
  })

  const selectedSpecificCode = computed(() =>
    codes.value.find((code) => String(code.id) === String(specificCampaignForm.discount_code_id)) || null
  )

  const filteredCampaignCustomers = computed(() => {
    const term = specificCampaignSearch.value.trim().toLowerCase()

    if (!term) return campaignCustomers.value

    return campaignCustomers.value.filter((customer) => [customer.name, customer.email].join(' ').toLowerCase().includes(term))
  })

  // =====================================================
  // Helpers internos
  // =====================================================
  function buildAutomaticDiscountCode() {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    const segment = Array.from({ length: 6 }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('')
    return `PROMO-${segment}`
  }

  function regenerateAutomaticCode() {
    form.code = buildAutomaticDiscountCode()
    validateField('code')
  }

  function handleCodeGenerationToggle(nextValue) {
    autoGenerateCode.value = Boolean(nextValue)

    if (autoGenerateCode.value) {
      regenerateAutomaticCode()
      return
    }

    validateField('code')
  }

  function defaultCampaignCodeId() {
    const firstCode = campaignCodeOptions.value[0]
    return firstCode ? String(firstCode.id) : ''
  }

  function resetForm() {
    autoGenerateCode.value = true
    form.code = ''
    form.type = 'percent'
    form.value = 10
    form.max_uses = null
    form.start_date = ''
    form.expires_at = ''
    form.active = true
    form.is_single_use = false
    clearErrors()
    regenerateAutomaticCode()
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

  function resetMassCampaignForm() {
    massCampaignForm.discount_code_id = defaultCampaignCodeId()
    massCampaignForm.send_notification = true
    massCampaignForm.send_email = true
    massCampaignErrors.discount_code_id = ''
    massCampaignErrors.channels = ''
  }

  function resetSpecificCampaignForm() {
    specificCampaignForm.discount_code_id = defaultCampaignCodeId()
    specificCampaignForm.send_notification = true
    specificCampaignForm.send_email = true
    specificCampaignForm.user_ids = []
    specificCampaignErrors.discount_code_id = ''
    specificCampaignErrors.channels = ''
    specificCampaignErrors.user_ids = ''
    specificCampaignSearch.value = ''
  }

  function campaignSummaryMessage(summary) {
    if (!summary) {
      return 'Campaña enviada correctamente.'
    }

    const notifications = summary.notifications || { sent: 0, failed: 0 }
    const emails = summary.emails || { sent: 0, failed: 0 }

    return `Notificaciones: ${notifications.sent} enviadas / ${notifications.failed} fallidas. Correos: ${emails.sent} enviados / ${emails.failed} fallidos.`
  }

  function codeStatusKey(code) {
    if (!code.active) return 'inactive'
    if (code.expires_at && new Date(code.expires_at) < new Date()) return 'expired'
    if (code.is_single_use) return 'single-use'
    return 'active'
  }

  function codeStatusLabel(code) {
    return { active: 'Activo', inactive: 'Inactivo', expired: 'Vencido', 'single-use': 'Uso único' }[codeStatusKey(code)]
  }

  function codeStatusClass(code) {
    return { active: 'active', inactive: 'rejected', expired: 'cancelled', 'single-use': 'info' }[codeStatusKey(code)]
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
  }

  function formatDiscountValue(code) {
    return code.type === 'percent' ? `${Number(code.value || 0)}%` : formatCurrency(code.value || 0)
  }

  function remainingUsesLabel(code) {
    const remaining = Number(code.max_uses || 0) - Number(code.times_used || 0)
    return remaining > 0 ? `${remaining} usos disponibles` : 'Sin cupos disponibles'
  }

  function userInitials(customer) {
    const name = String(customer?.name || customer?.email || '?').trim()
    return name.split(/\s+/).slice(0, 2).map((word) => word[0]?.toUpperCase() || '').join('')
  }

  function formatShortDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return Number.isNaN(date.getTime()) ? dateStr : date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' })
  }

  function formatDateTime(value) {
    if (!value) return 'Sin fecha'
    return new Date(value).toLocaleString('es-CO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
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

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  // =====================================================
  // Carga de datos
  // =====================================================
  async function loadCodes() {
    loading.value = true
    try {
      const { data } = await discountHttp.get('/admin/discount-codes')
      codes.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      codes.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los códigos.') })
    } finally {
      loading.value = false
    }
  }

  async function loadCampaignCustomers() {
    campaignCustomersLoading.value = true
    try {
      const { data } = await discountHttp.get('/admin/discount-codes/campaign/customers')
      campaignCustomers.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      campaignCustomers.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los clientes para la campaña.') })
    } finally {
      campaignCustomersLoading.value = false
    }
  }

  // =====================================================
  // Validaciones
  // =====================================================
  function handleCodeInput() {
    form.code = form.code.toUpperCase().replace(/\s+/g, '')
    validateField('code')
  }

  function validateField(field) {
    switch (field) {
      case 'code':
        formErrors.code = /^[A-Z0-9_-]{4,20}$/.test(form.code) ? '' : 'Usa entre 4 y 20 caracteres en mayúsculas, números o guiones.'
        break
      case 'type':
        formErrors.type = form.type ? '' : 'Selecciona el tipo de descuento.'
        break
      case 'value':
        formErrors.value = Number(form.value) > 0 ? '' : 'El valor del descuento debe ser mayor que cero.'
        if (!formErrors.value && form.type === 'percent' && Number(form.value) > 100) formErrors.value = 'El porcentaje no puede superar el 100%.'
        break
      case 'max_uses':
        formErrors.max_uses = form.max_uses === null || form.max_uses === '' || Number(form.max_uses) > 0 ? '' : 'El máximo de usos debe ser mayor que cero.'
        break
      case 'start_date':
      case 'expires_at':
        formErrors.start_date = ''
        formErrors.expires_at = ''
        if (form.start_date && form.expires_at && new Date(form.expires_at) <= new Date(form.start_date)) {
          formErrors.expires_at = 'La expiración debe ser posterior al inicio.'
        }
        break
      default:
        break
    }
  }

  function validateForm() {
    validateField('code')
    validateField('type')
    validateField('value')
    validateField('max_uses')
    validateField('start_date')
    return Object.values(formErrors).every((value) => !value)
  }

  function validateMassCampaignField(field) {
    if (field === 'discount_code_id') {
      massCampaignErrors.discount_code_id = massCampaignForm.discount_code_id ? '' : 'Selecciona un código para el envío masivo.'
      return
    }

    if (field === 'channels') {
      massCampaignErrors.channels = massCampaignForm.send_notification || massCampaignForm.send_email
        ? ''
        : 'Activa al menos un canal de envío (notificación o correo).'
    }
  }

  function validateSpecificCampaignField(field) {
    if (field === 'discount_code_id') {
      specificCampaignErrors.discount_code_id = specificCampaignForm.discount_code_id ? '' : 'Selecciona un código para el envío.'
      return
    }

    if (field === 'channels') {
      specificCampaignErrors.channels = specificCampaignForm.send_notification || specificCampaignForm.send_email
        ? ''
        : 'Activa al menos un canal de envío (notificación o correo).'
      return
    }

    if (field === 'user_ids') {
      specificCampaignErrors.user_ids = specificCampaignForm.user_ids.length > 0
        ? ''
        : 'Selecciona al menos un usuario para continuar.'
    }
  }

  function validateMassCampaignForm() {
    validateMassCampaignField('discount_code_id')
    validateMassCampaignField('channels')

    return !massCampaignErrors.discount_code_id && !massCampaignErrors.channels
  }

  function validateSpecificCampaignForm() {
    validateSpecificCampaignField('discount_code_id')
    validateSpecificCampaignField('channels')
    validateSpecificCampaignField('user_ids')

    return !specificCampaignErrors.discount_code_id && !specificCampaignErrors.channels && !specificCampaignErrors.user_ids
  }

  // =====================================================
  // Acciones CRUD
  // =====================================================
  async function saveCode() {
    if (saving.value) return

    if (!validateForm()) {
      showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
      return
    }

    const payload = {
      code: form.code,
      type: form.type,
      value: Number(form.value),
      max_uses: form.max_uses || null,
      active: form.active,
      is_single_use: form.is_single_use,
      start_date: form.start_date || null,
      expires_at: form.expires_at || null,
    }

    saving.value = true
    try {
      if (editingCodeId.value) {
        await discountHttp.put(`/admin/discount-codes/${editingCodeId.value}`, payload)
        showSnackbar({ type: 'success', message: 'Código actualizado correctamente.' })
      } else {
        await discountHttp.post('/admin/discount-codes', payload)
        showSnackbar({ type: 'success', message: 'Código creado correctamente.' })
      }

      closeEditorModal()
      await loadCodes()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el código.') })
    } finally {
      saving.value = false
    }
  }

  function confirmDeleteCode(code) {
    showAlert({
      type: 'warning',
      title: 'Eliminar código',
      message: `Vas a eliminar el código ${code.code}. Esta acción no se puede deshacer.`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: 'Eliminar',
          style: 'danger',
          callback: async () => {
            try {
              await discountHttp.delete(`/admin/discount-codes/${code.id}`)
              showSnackbar({ type: 'success', message: 'Código eliminado correctamente.' })
              if (selectedCode.value?.id === code.id) closeDetailModal()
              await loadCodes()
            } catch (error) {
              showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el código.') })
            }
          },
        },
      ],
    })
  }

  // =====================================================
  // Exportación y campañas
  // =====================================================
  function buildDiscountCodeExportColumns() {
    return [
      { header: 'Código', value: (code) => code.code },
      { header: 'Tipo', value: (code) => code.type_label, width: 14 },
      { header: 'Valor', value: (code) => formatDiscountValue(code), width: 14 },
      { header: 'Usados', value: (code) => Number(code.times_used || 0), excelType: 'number', align: 'center', width: 12 },
      { header: 'Máximo', value: (code) => code.max_uses || '∞', width: 12 },
      { header: 'Estado', value: (code) => codeStatusLabel(code), width: 14 },
      { header: 'Inicio', value: (code) => (code.start_date ? formatDateTime(code.start_date) : 'Inmediato'), width: 18 },
      { header: 'Expira', value: (code) => (code.expires_at ? formatDateTime(code.expires_at) : 'Sin expiración'), width: 18 },
    ]
  }

  function exportCodes(format) {
    return exportData({
      format,
      fileBaseName: 'codigos-descuento-admin',
      sheetName: 'Códigos de descuento',
      title: 'Códigos de descuento',
      subtitle: 'Resumen exportado desde la gestión administrativa de códigos de descuento.',
      columns: buildDiscountCodeExportColumns(),
      rows: filteredCodes.value,
      landscape: true,
      emptyMessage: 'No hay códigos de descuento para exportar.',
    })
  }

  async function submitMassCampaign() {
    if (!validateMassCampaignForm()) {
      showSnackbar({ type: 'warning', message: 'Completa correctamente los campos del envío masivo.' })
      return
    }

    if (campaignCustomersLoading.value) {
      showSnackbar({ type: 'info', message: 'Todavía estamos validando los clientes disponibles para la campaña.' })
      return
    }

    if (!massCampaignHasRecipients.value) {
      showSnackbar({ type: 'warning', message: 'No hay clientes disponibles para el envío masivo. Registra al menos un cliente antes de continuar.' })
      return
    }

    campaignSubmitting.value = true
    try {
      const payload = {
        discount_code_id: Number(massCampaignForm.discount_code_id),
        send_notification: Boolean(massCampaignForm.send_notification),
        send_email: Boolean(massCampaignForm.send_email),
      }

      const { data } = await discountHttp.post('/admin/discount-codes/campaign/mass', payload)
      showSnackbar({ type: 'success', message: campaignSummaryMessage(data?.data?.summary) })
      closeMassCampaignModal()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío masivo.') })
    } finally {
      campaignSubmitting.value = false
    }
  }

  async function submitSpecificCampaign() {
    if (!validateSpecificCampaignForm()) {
      showSnackbar({ type: 'warning', message: 'Completa correctamente la selección de usuarios y canales.' })
      return
    }

    campaignSubmitting.value = true
    try {
      const payload = {
        discount_code_id: Number(specificCampaignForm.discount_code_id),
        user_ids: specificCampaignForm.user_ids,
        send_notification: Boolean(specificCampaignForm.send_notification),
        send_email: Boolean(specificCampaignForm.send_email),
      }

      const { data } = await discountHttp.post('/admin/discount-codes/campaign/specific', payload)
      showSnackbar({ type: 'success', message: campaignSummaryMessage(data?.data?.summary) })
      closeSpecificCampaignModal()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío a usuarios específicos.') })
    } finally {
      campaignSubmitting.value = false
    }
  }

  function clearSpecificCustomerSelection() {
    specificCampaignForm.user_ids = []
    validateSpecificCampaignField('user_ids')
  }

  function selectAllFilteredCustomers() {
    const visibleIds = filteredCampaignCustomers.value.map((customer) => String(customer.id))
    specificCampaignForm.user_ids = Array.from(new Set([...specificCampaignForm.user_ids, ...visibleIds]))
    validateSpecificCampaignField('user_ids')
  }

  // =====================================================
  // Gestión de modales y navegación
  // =====================================================
  function openCreateModal() {
    editingCodeId.value = null
    resetForm()
    showEditorModal.value = true
  }

  function navigateToSpecificCampaignPage() {
    router.push({ name: 'admin-discount-codes-specific-campaign' })
  }

  function openEditModal(code) {
    editingCodeId.value = code.id
    autoGenerateCode.value = false
    clearErrors()
    form.code = code.code || ''
    form.type = code.type || 'percent'
    form.value = Number(code.value || 0)
    form.max_uses = code.max_uses ?? null
    form.start_date = normalizeDateTimeInput(code.start_date)
    form.expires_at = normalizeDateTimeInput(code.expires_at)
    form.active = Boolean(code.active)
    form.is_single_use = Boolean(code.is_single_use)
    showEditorModal.value = true
  }

  function closeEditorModal() {
    showEditorModal.value = false
    editingCodeId.value = null
    resetForm()
  }

  function openDetailModal(code) {
    selectedCode.value = code
    showDetailModal.value = true
  }

  function closeDetailModal() {
    selectedCode.value = null
    showDetailModal.value = false
  }

  function openEditFromDetail() {
    if (!selectedCode.value) return
    const current = selectedCode.value
    closeDetailModal()
    openEditModal(current)
  }

  async function openMassCampaignModal() {
    resetMassCampaignForm()
    showMassCampaignModal.value = true
    await loadCampaignCustomers()
  }

  function closeMassCampaignModal() {
    showMassCampaignModal.value = false
    resetMassCampaignForm()
  }

  async function openSpecificCampaignModal() {
    resetSpecificCampaignForm()
    showSpecificCampaignModal.value = true
    await loadCampaignCustomers()
  }

  function closeSpecificCampaignModal() {
    showSpecificCampaignModal.value = false
    resetSpecificCampaignForm()
  }

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(loadCodes)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeFilterCount,
    autoGenerateCode,
    campaignCodeOptions,
    campaignCustomers,
    campaignCustomersLoading,
    campaignSubmitting,
    clearFilters,
    clearSpecificCustomerSelection,
    closeDetailModal,
    closeEditorModal,
    closeMassCampaignModal,
    closeSpecificCampaignModal,
    codeStatusClass,
    codeStatusLabel,
    confirmDeleteCode,
    discountStats,
    editingCodeId,
    exportCodes,
    exportingFormat,
    filteredCampaignCustomers,
    filteredCodes,
    filters,
    form,
    formErrors,
    formatCurrency,
    formatDateTime,
    formatDiscountValue,
    formatShortDate,
    handleCodeGenerationToggle,
    handleCodeInput,
    loadCodes,
    loading,
    massCampaignAvailabilityMessage,
    massCampaignAvailabilityTitle,
    massCampaignErrors,
    massCampaignForm,
    massCampaignHasRecipients,
    navigateToSpecificCampaignPage,
    openCreateModal,
    openDetailModal,
    openEditFromDetail,
    openEditModal,
    openMassCampaignModal,
    openSpecificCampaignModal,
    pagination,
    regenerateAutomaticCode,
    remainingUsesLabel,
    saveCode,
    selectedCode,
    selectedSpecificCode,
    selectAllFilteredCustomers,
    showDetailModal,
    showEditorModal,
    showMassCampaignModal,
    showSpecificCampaignModal,
    specificCampaignErrors,
    specificCampaignForm,
    specificCampaignSearch,
    submitMassCampaign,
    submitSpecificCampaign,
    userInitials,
    validateField,
    validateMassCampaignField,
    validateSpecificCampaignField,
  }
}
