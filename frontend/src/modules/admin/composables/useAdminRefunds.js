/**
 * Composable para la gestión de reembolsos del panel administrativo.
 * Administra listado de solicitudes, aprobación/rechazo, filtros por estado
 * y fechas, comprobantes de pago y paginación.
 * Reutiliza useAdminPagination para paginación.
 */
import { orderHttp } from '../../../services/http'
import { resolveUploadUrl } from '../../../utils/media'
import { getPaymentStatusBadgeClass, getPaymentStatusLabel, translateDbText } from '../utils/orderPresentation'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from './useAdminPagination'

const REFUND_STATUS_LABELS = {
  requested: 'Solicitado',
  approved: 'Reembolso en proceso',
  processing: 'Reembolso en proceso',
  rejected: 'Rechazado',
  completed: 'Reembolsado',
}

const REFUND_STATUS_BADGES = {
  requested: 'pending',
  approved: 'processing',
  processing: 'processing',
  rejected: 'cancelled',
  completed: 'active',
}

const REFUND_REASON_LABELS = {
  producto_defectuoso: 'Producto defectuoso',
  producto_danado: 'Producto dañado',
  producto_dañado: 'Producto dañado',
  producto_incorrecto: 'Producto incorrecto',
  talla_incorrecta: 'Talla incorrecta',
  no_recibido: 'Producto no recibido',
  pedido_incompleto: 'Pedido incompleto',
  arrepentimiento_compra: 'Cambio de decisión',
  otro: 'Otro motivo',
}

const REFUND_ACTIONS = {
  approve: {
    status: 'processing',
    label: 'Aceptar reembolso',
    confirm: 'Aceptar',
    title: 'Aceptar reembolso',
    icon: 'fas fa-check',
    tone: 'edit',
    paymentMessage: 'El pago pasará a reembolso en proceso.',
  },
  reject: {
    status: 'rejected',
    label: 'Rechazar',
    confirm: 'Rechazar',
    title: 'Rechazar solicitud',
    icon: 'fas fa-times',
    tone: 'delete',
    paymentMessage: 'El pago volverá a verificado.',
  },
  complete: {
    status: 'completed',
    label: 'Completar reembolso',
    confirm: 'Completar',
    title: 'Completar reembolso',
    icon: 'fas fa-hand-holding-usd',
    tone: 'edit action-btn--complete',
    paymentMessage: 'El pago quedará como reembolsado.',
  },
}

function normalizeStatus(value, fallback = 'requested') {
  const normalized = String(value || '').trim().toLowerCase()
  // Reutiliza la compatibilidad de estados antiguos: "approved" ahora se representa como proceso activo.
  if (normalized === 'approved') return 'processing'
  return normalized || fallback
}

function normalizePresentationKey(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[\s-]+/g, '_')
}

function normalizeRefundRequest(rawRefund = {}) {
  const evidencePath = String(rawRefund.evidence_url || rawRefund.evidence_path || '').trim()

  return {
    ...rawRefund,
    id: Number(rawRefund.id || 0),
    order_id: Number(rawRefund.order_id || 0),
    total: Number(rawRefund.total || 0),
    source: String(rawRefund.source || rawRefund.order_source || 'microservice').toLowerCase() === 'legacy' ? 'legacy' : 'microservice',
    status: normalizeStatus(rawRefund.status),
    payment_status: normalizeStatus(rawRefund.payment_status, 'pending'),
    order_status: normalizeStatus(rawRefund.order_status, 'completed'),
    customer_name: rawRefund.customer_name || 'Cliente',
    customer_email: rawRefund.customer_email || rawRefund.user_email || 'Sin correo',
    evidence_url: evidencePath ? resolveUploadUrl(evidencePath) : '',
    evidence_original_name: rawRefund.evidence_original_name || '',
    reason: rawRefund.reason || 'Sin motivo',
    details: rawRefund.details || '',
  }
}

export function useAdminRefunds() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()

  const loading = ref(true)
  const refunds = ref([])
  const refundStats = ref({ total: 0, requested: 0, in_process: 0, completed: 0 })
  const selectedRefund = ref(null)
  const showDetailModal = ref(false)
  const showEvidenceModal = ref(false)
  const showActionModal = ref(false)
  const savingAction = ref(false)
  const actionKey = ref('')
  const selectedAction = ref(null)
  const filters = reactive({
    search: '',
    status: '',
  })
  const actionForm = reactive({
    description: '',
  })
  const actionErrors = reactive({
    description: '',
  })

  const stats = computed(() => [
    { key: 'total', label: 'Solicitudes', value: String(refundStats.value.total || 0), icon: 'fas fa-rotate-left', color: 'primary' },
    { key: 'requested', label: 'Por revisar', value: String(refundStats.value.requested || 0), icon: 'fas fa-clock', color: 'warning' },
    { key: 'in_process', label: 'En proceso', value: String(refundStats.value.in_process || 0), icon: 'fas fa-rotate', color: 'info' },
    { key: 'completed', label: 'Completadas', value: String(refundStats.value.completed || 0), icon: 'fas fa-circle-check', color: 'success' },
  ])

  const activeFilterCount = computed(() => [filters.search.trim(), filters.status].filter(Boolean).length)
  const pagination = useAdminPagination(refunds, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  function refundStatusLabel(status) {
    return REFUND_STATUS_LABELS[normalizeStatus(status)] || 'Solicitado'
  }

  function refundStatusBadgeClass(status) {
    return REFUND_STATUS_BADGES[normalizeStatus(status)] || 'pending'
  }

  function paymentStatusLabel(status) {
    return getPaymentStatusLabel(status)
  }

  // Ajusta los colores de pago al contexto de reembolso para que "Reembolsado" no se muestre como error.
  function paymentStatusBadgeClass(status) {
    const normalized = normalizeStatus(status, '')
    if (normalized === 'refunded') return 'active'
    if (normalized === 'pending_refund') return 'processing'
    if (normalized === 'refund_requested') return 'pending'

    return getPaymentStatusBadgeClass(status)
  }

  // Reutiliza el estado de la solicitud como fuente principal para mantener pago y reembolso conectados.
  function refundPaymentStatus(refund) {
    const status = normalizeStatus(refund?.status)
    if (status === 'requested') return 'refund_requested'
    if (status === 'processing') return 'pending_refund'
    if (status === 'completed') return 'refunded'
    if (status === 'rejected') return 'verified'

    return normalizeStatus(refund?.payment_status, 'pending')
  }

  function refundReasonLabel(reason) {
    const key = normalizePresentationKey(reason)
    return REFUND_REASON_LABELS[key] || translateDbText(reason) || 'Sin motivo'
  }

  function refundDetailsLabel(details) {
    return translateDbText(details) || ''
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      maximumFractionDigits: 0,
    }).format(Number(value || 0))
  }

  function formatDateTime(value) {
    if (!value) return 'Sin fecha'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
  }

  function clearFilters() {
    filters.search = ''
    filters.status = ''
    loadRefunds()
  }

  function applyFilters() {
    loadRefunds()
  }

  async function loadRefunds() {
    loading.value = true

    try {
      const params = { limit: 300 }
      if (filters.search.trim()) params.search = filters.search.trim()
      if (filters.status) params.status = filters.status

      const response = await orderHttp.get('/admin/refund-requests', { params })
      const payload = response.data?.data || {}
      const rows = Array.isArray(payload.rows) ? payload.rows : []

      refunds.value = rows.map(normalizeRefundRequest)
      refundStats.value = payload.stats || {
        total: refunds.value.length,
        requested: refunds.value.filter((refund) => refund.status === 'requested').length,
        in_process: refunds.value.filter((refund) => refund.status === 'processing').length,
        completed: refunds.value.filter((refund) => refund.status === 'completed').length,
      }
    } catch {
      refunds.value = []
      showSnackbar({ type: 'error', message: 'No se pudieron cargar los reembolsos.' })
    } finally {
      loading.value = false
    }
  }

  function openDetailModal(refund) {
    selectedRefund.value = refund
    showDetailModal.value = true
  }

  function closeDetailModal() {
    showDetailModal.value = false
    selectedRefund.value = null
  }

  function openEvidenceModal(refund) {
    selectedRefund.value = refund
    showEvidenceModal.value = true
  }

  function closeEvidenceModal() {
    showEvidenceModal.value = false
  }

  function validateActionField(field) {
    if (field === 'description') {
      actionErrors.description = actionForm.description.length <= 1000
        ? ''
        : 'La nota no puede superar 1000 caracteres.'
    }
  }

  function openActionModal(refund, actionName) {
    const action = REFUND_ACTIONS[actionName]
    if (!action || savingAction.value) return

    selectedRefund.value = refund
    selectedAction.value = { ...action, name: actionName }
    actionForm.description = ''
    actionErrors.description = ''
    showActionModal.value = true
  }

  function closeActionModal() {
    if (savingAction.value) return
    showActionModal.value = false
    selectedAction.value = null
  }

  function canUseAction(refund, actionName) {
    const status = normalizeStatus(refund?.status)
    // Aceptar pasa directo a proceso; por eso no existe una acción separada para "Marcar en proceso".
    if (actionName === 'approve') return status === 'requested'
    if (actionName === 'reject') return ['requested', 'processing'].includes(status)
    if (actionName === 'complete') return status === 'processing'
    return false
  }

  function isActionLoading(refund, actionName) {
    return actionKey.value === `${refund?.id}:${actionName}`
  }

  function submitAction() {
    validateActionField('description')
    if (actionErrors.description || !selectedRefund.value || !selectedAction.value || savingAction.value) return

    const refund = selectedRefund.value
    const action = selectedAction.value

    showAlert({
      type: action.name === 'reject' ? 'warning' : 'info',
      title: action.title,
      message: `${action.paymentMessage} ¿Deseas continuar con la solicitud #${refund.id}?`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: action.confirm,
          style: action.name === 'reject' ? 'warning' : 'primary',
          callback: async () => {
            await updateRefundStatus(refund, action)
          },
        },
      ],
    })
  }

  async function updateRefundStatus(refund, action) {
    actionKey.value = `${refund.id}:${action.name}`
    savingAction.value = true

    try {
      await orderHttp.patch(`/admin/refund-requests/${refund.id}`, {
        source: refund.source,
        status: action.status,
        description: actionForm.description.trim(),
      })
      showSnackbar({ type: 'success', message: 'Reembolso actualizado correctamente.' })
      showActionModal.value = false
      selectedAction.value = null
      showDetailModal.value = false
      showEvidenceModal.value = false
      await loadRefunds()
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo actualizar el reembolso.'
      showSnackbar({ type: 'error', message })
    } finally {
      savingAction.value = false
      actionKey.value = ''
    }
  }

  onMounted(loadRefunds)

  return {
    activeFilterCount,
    actionErrors,
    actionForm,
    applyFilters,
    canUseAction,
    clearFilters,
    closeActionModal,
    closeDetailModal,
    closeEvidenceModal,
    filters,
    formatCurrency,
    formatDateTime,
    isActionLoading,
    loading,
    openActionModal,
    openDetailModal,
    openEvidenceModal,
    pagination,
    paymentStatusBadgeClass,
    paymentStatusLabel,
    refundPaymentStatus,
    refundDetailsLabel,
    refundReasonLabel,
    refundStatusBadgeClass,
    refundStatusLabel,
    refunds,
    savingAction,
    selectedAction,
    selectedRefund,
    showActionModal,
    showDetailModal,
    showEvidenceModal,
    stats,
    submitAction,
    validateActionField,
  }
}
