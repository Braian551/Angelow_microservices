import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { discountHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de campañas de descuento específicas (por cliente).
 * Permite seleccionar clientes individuales para enviar códigos de descuento.
 * Encapsula carga de códigos, selección de destinatarios y envío de notificaciones.
 */
export function useAdminDiscountSpecificCampaign() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const router = useRouter()
  const { showSnackbar } = useSnackbarSystem()

  // =====================================================
  // Estado principal
  // =====================================================
  const loadingCodes = ref(true)
  const campaignSubmitting = ref(false)
  const campaignCustomersLoading = ref(false)
  const codes = ref([])
  const campaignCustomers = ref([])
  const specificCampaignSearch = ref('')

  const customerTableShimmerColumns = [
    { type: 'rect', width: '1.4rem', height: '1.4rem' },
    'line',
    'line',
    'pill',
  ]

  // =====================================================
  // Formulario de campaña
  // =====================================================
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
  // Códigos disponibles
  // =====================================================
  const campaignCodeOptions = computed(() => codes.value.map((code) => ({
    id: code.id,
    code: code.code,
    type: code.type,
    value: code.value,
    expires_at: code.expires_at,
    times_used: code.times_used,
    max_uses: code.max_uses,
  })))

  const selectedSpecificCode = computed(() =>
    codes.value.find((code) => String(code.id) === String(specificCampaignForm.discount_code_id)) || null,
  )

  // =====================================================
  // Destinatarios
  // =====================================================
  const filteredCampaignCustomers = computed(() => {
    const term = specificCampaignSearch.value.trim().toLowerCase()

    if (!term) return campaignCustomers.value

    return campaignCustomers.value.filter((customer) => [customer.name, customer.email].join(' ').toLowerCase().includes(term))
  })

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const campaignCustomersPagination = useAdminPagination(filteredCampaignCustomers, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const allFilteredCustomersSelected = computed(() =>
    filteredCampaignCustomers.value.length > 0
    && filteredCampaignCustomers.value.every((customer) => specificCampaignForm.user_ids.includes(String(customer.id))),
  )

  const customerResultsText = computed(() => {
    const visible = campaignCustomersPagination.visibleCount
    const filteredTotal = filteredCampaignCustomers.value.length
    const total = campaignCustomers.value.length
    const selected = specificCampaignForm.user_ids.length

    if (specificCampaignSearch.value.trim()) {
      return `Mostrando ${visible} de ${filteredTotal} cliente${filteredTotal === 1 ? '' : 's'} filtrado${filteredTotal === 1 ? '' : 's'} · ${selected} seleccionado${selected === 1 ? '' : 's'}`
    }

    return `Mostrando ${visible} de ${total} cliente${total === 1 ? '' : 's'} · ${selected} seleccionado${selected === 1 ? '' : 's'}`
  })

  // =====================================================
  // Canales y contenido
  // =====================================================
  function defaultCampaignCodeId() {
    const firstCode = campaignCodeOptions.value[0]
    return firstCode ? String(firstCode.id) : ''
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

  function validateSpecificCampaignForm() {
    validateSpecificCampaignField('discount_code_id')
    validateSpecificCampaignField('channels')
    validateSpecificCampaignField('user_ids')

    return !specificCampaignErrors.discount_code_id && !specificCampaignErrors.channels && !specificCampaignErrors.user_ids
  }

  // =====================================================
  // Helpers internos
  // =====================================================
  function isCustomerSelected(customer) {
    return specificCampaignForm.user_ids.includes(String(customer.id))
  }

  function toggleFilteredCustomersSelection(shouldSelect) {
    const visibleIds = filteredCampaignCustomers.value.map((customer) => String(customer.id))

    if (shouldSelect) {
      specificCampaignForm.user_ids = Array.from(new Set([...specificCampaignForm.user_ids, ...visibleIds]))
    } else {
      const visibleIdSet = new Set(visibleIds)
      specificCampaignForm.user_ids = specificCampaignForm.user_ids.filter((userId) => !visibleIdSet.has(String(userId)))
    }

    validateSpecificCampaignField('user_ids')
  }

  function clearSpecificCustomerSelection() {
    specificCampaignForm.user_ids = []
    validateSpecificCampaignField('user_ids')
  }

  function selectAllFilteredCustomers() {
    toggleFilteredCustomersSelection(true)
  }

  function campaignSummaryMessage(summary) {
    if (!summary) {
      return 'Campaña enviada correctamente.'
    }

    const notifications = summary.notifications || { sent: 0, failed: 0 }
    const emails = summary.emails || { sent: 0, failed: 0 }

    return `Notificaciones: ${notifications.sent} enviadas / ${notifications.failed} fallidas. Correos: ${emails.sent} enviados / ${emails.failed} fallidos.`
  }

  function formatDiscountValue(code) {
    return code.type === 'percent'
      ? `${Number(code.value || 0)}%`
      : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(code.value || 0))
  }

  function formatShortDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return Number.isNaN(date.getTime())
      ? dateStr
      : date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' })
  }

  function userInitials(customer) {
    const name = String(customer?.name || customer?.email || '?').trim()
    return name
      .split(/\s+/)
      .slice(0, 2)
      .map((word) => word[0]?.toUpperCase() || '')
      .join('')
  }

  function extractErrorMessage(error, fallback) {
    return error?.response?.data?.message || fallback
  }

  // =====================================================
  // Carga
  // =====================================================
  async function loadCodes() {
    loadingCodes.value = true
    try {
      const { data } = await discountHttp.get('/admin/discount-codes')
      codes.value = Array.isArray(data?.data) ? data.data : []
    } catch (error) {
      codes.value = []
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los códigos de descuento.') })
    } finally {
      loadingCodes.value = false
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
  // Envío
  // =====================================================
  async function submitSpecificCampaign() {
    if (campaignSubmitting.value) return

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

      resetSpecificCampaignForm()
      await loadCampaignCustomers()
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío a usuarios específicos.') })
    } finally {
      campaignSubmitting.value = false
    }
  }

  // =====================================================
  // Confirmaciones y navegación
  // =====================================================
  function goBackToDiscountCodes() {
    router.push({ name: 'admin-discount-codes' })
  }

  // =====================================================
  // Watchers y ciclo de vida
  // =====================================================
  onMounted(async () => {
    await loadCodes()
    resetSpecificCampaignForm()
    await loadCampaignCustomers()
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    allFilteredCustomersSelected,
    campaignCodeOptions,
    campaignCustomersLoading,
    campaignCustomersPagination,
    campaignSubmitting,
    clearSpecificCustomerSelection,
    customerResultsText,
    customerTableShimmerColumns,
    filteredCampaignCustomers,
    formatDiscountValue,
    formatShortDate,
    goBackToDiscountCodes,
    isCustomerSelected,
    loadCampaignCustomers,
    loadCodes,
    loadingCodes,
    selectAllFilteredCustomers,
    selectedSpecificCode,
    specificCampaignErrors,
    specificCampaignForm,
    specificCampaignSearch,
    submitSpecificCampaign,
    toggleFilteredCustomersSelection,
    userInitials,
    validateSpecificCampaignField,
  }
}
