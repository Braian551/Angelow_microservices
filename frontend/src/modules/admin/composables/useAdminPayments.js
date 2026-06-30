/**
 * Composable para la gestión de pagos del panel administrativo.
 * Administra listado de transacciones, verificación de comprobantes,
 * configuración de cuentas bancarias y filtros por estado/fechas.
 * Reutiliza useAdminPagination para paginación.
 */
import { computed, onMounted, ref } from 'vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { orderHttp, paymentHttp } from '../../../services/http'
import {
  getAdminPaymentAccountConfig,
  getBanks,
  getPaymentAccount,
  saveAdminPaymentAccountConfig,
} from '../../../services/paymentApi'
import { useAdminPagination } from './useAdminPagination'
import { getPaymentMethodLabel, getPaymentStatusBadgeClass, getPaymentStatusLabel } from '../utils/orderPresentation'
import { resolvePaymentProofUrl } from '../utils/paymentProofs'

function buildEmptyAccountForm() {
  return {
    id: null,
    bank_code: '',
    account_number: '',
    account_type: 'ahorros',
    account_holder: '',
    identification_type: 'cc',
    identification_number: '',
    email: '',
    phone: '',
    is_active: true,
  }
}

function buildEmptyAccountErrors() {
  return {
    bank_code: '',
    account_number: '',
    account_type: '',
    account_holder: '',
    identification_type: '',
    identification_number: '',
    email: '',
    phone: '',
  }
}

function normalizePaymentStatus(status) {
  const normalized = String(status ?? '').toLowerCase().trim()

  if (!normalized) return 'pending'
  if (['approved', 'verified', 'paid'].includes(normalized)) return 'approved'
  if (['rejected', 'failed', 'cancelled', 'canceled'].includes(normalized)) return 'rejected'

  return 'pending'
}

function normalizePayment(rawPayment = {}) {
  const normalizedStatus = normalizePaymentStatus(rawPayment.status)

  return {
    ...rawPayment,
    id: Number(rawPayment.id || 0),
    order_id: Number(rawPayment.order_id || 0),
    amount: Number(rawPayment.amount || 0),
    status: normalizedStatus,
    method: String(rawPayment.payment_method || rawPayment.method || 'transfer').toLowerCase().trim(),
    customer_name: rawPayment.customer_name || rawPayment.billing_name || rawPayment.user_name || '',
    customer_email: rawPayment.customer_email || rawPayment.billing_email || rawPayment.user_email || '',
    reference_number: rawPayment.reference_number || '',
    proof_url: resolvePaymentProofUrl(rawPayment.proof_url || rawPayment.payment_proof || ''),
    proof_name: rawPayment.proof_name || '',
    proof_exists: rawPayment.proof_exists !== false,
  }
}

function mapTransactionStatusToOrderStatus(status) {
  return status === 'approved' ? 'verified' : (status === 'rejected' ? 'failed' : 'pending')
}

function buildOrderPaymentDescription(status) {
  return status === 'approved'
    ? 'Pago verificado desde administración de pagos.'
    : 'Pago rechazado desde administración de pagos.'
}

function extractApiErrorMessage(error, fallback) {
  const apiMessage = String(error?.response?.data?.message || '').trim()
  return apiMessage || fallback
}

function shouldRejectPaymentAfterOrderSyncFailure(error) {
  const httpStatus = Number(error?.response?.status || 0)
  const apiCode = String(error?.response?.data?.code || '').trim().toLowerCase()
  return httpStatus === 409 && (Boolean(error?.response?.data?.order_cancelled) || apiCode === 'insufficient_stock')
}

function resolveRollbackStatusAfterOrderSyncFailure(error, previousStatus) {
  return shouldRejectPaymentAfterOrderSyncFailure(error) ? 'rejected' : normalizePaymentStatus(previousStatus)
}

export function useAdminPayments() {
  const { showSnackbar } = useSnackbarSystem()

  const payments = ref([])
  const paymentAccount = ref(null)
  const accountBanks = ref([])
  const loading = ref(true)
  const syncingPaymentId = ref(null)
  const syncingPaymentStatus = ref('')
  const loadingAccountConfig = ref(false)
  const savingAccountConfig = ref(false)
  const showAccountModal = ref(false)
  const showProofModal = ref(false)
  const selectedProofPayment = ref(null)
  const search = ref('')
  const statusFilter = ref('')
  const methodFilter = ref('')
  const accountForm = ref(buildEmptyAccountForm())
  const accountErrors = ref(buildEmptyAccountErrors())

  const paymentStats = computed(() => [
    { key: 'total', label: 'Total pagos', value: String(payments.value.length), icon: 'fas fa-credit-card', color: 'primary' },
    { key: 'pending', label: 'Pendientes', value: String(payments.value.filter((payment) => payment.status === 'pending').length), icon: 'fas fa-clock', color: 'warning' },
    { key: 'approved', label: 'Verificados', value: String(payments.value.filter((payment) => payment.status === 'approved').length), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'rejected', label: 'Rechazados', value: String(payments.value.filter((payment) => payment.status === 'rejected').length), icon: 'fas fa-times-circle', color: 'danger' },
  ])

  const filtered = computed(() => {
    const term = search.value.trim().toLowerCase()
    let list = payments.value

    if (statusFilter.value) list = list.filter((payment) => payment.status === statusFilter.value)
    if (methodFilter.value) list = list.filter((payment) => payment.method === methodFilter.value)

    if (term) {
      list = list.filter((payment) => [
        payment.id,
        payment.order_id,
        payment.customer_name,
        payment.customer_email,
        payment.reference_number,
        payment.method,
      ].some((value) => String(value || '').toLowerCase().includes(term)))
    }

    return list
  })

  const pagination = useAdminPagination(filtered, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value, methodFilter.value].filter(Boolean).length)
  const accountPreview = computed(() => {
    const values = accountForm.value || buildEmptyAccountForm()
    const bankCode = String(values.bank_code || '').trim()
    const selectedBank = accountBanks.value.find((bank) => String(bank.bank_code || bank.id || '').trim() === bankCode)
    const fallbackAccount = paymentAccount.value || {}

    return {
      ...fallbackAccount,
      ...values,
      bank_name: selectedBank?.bank_name || fallbackAccount.bank_name || '',
    }
  })

  function statusLabel(status) {
    const normalized = String(status || '').toLowerCase().trim()
    if (normalized === 'approved') return 'Verificado'
    if (normalized === 'rejected') return 'Rechazado'
    return getPaymentStatusLabel(normalized)
  }

  function statusBadgeClass(status) {
    return getPaymentStatusBadgeClass(String(status || '').toLowerCase().trim())
  }

  function methodLabel(method) {
    return getPaymentMethodLabel(method)
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      maximumFractionDigits: 0,
    }).format(Number(value || 0))
  }

  function clearFilters() {
    search.value = ''
    statusFilter.value = ''
    methodFilter.value = ''
  }

  async function openAccountModal() {
    showAccountModal.value = true
    await loadAdminPaymentAccountConfig()
  }

  function closeAccountModal() {
    if (savingAccountConfig.value) return
    showAccountModal.value = false
  }

  function applyAccountToForm(account = null) {
    accountForm.value = {
      id: account?.id || null,
      bank_code: account?.bank_code || '',
      account_number: account?.account_number || '',
      account_type: account?.account_type || 'ahorros',
      account_holder: account?.account_holder || '',
      identification_type: account?.identification_type || 'cc',
      identification_number: account?.identification_number || '',
      email: account?.email || '',
      phone: account?.phone || '',
      is_active: account?.is_active !== false,
    }
  }

  // Mantiene la validación en tiempo real del modal de cuenta sin alterar reglas existentes.
  function validateAccountField(fieldName) {
    const values = accountForm.value

    if (fieldName === 'bank_code') {
      accountErrors.value.bank_code = values.bank_code ? '' : 'Selecciona un banco para continuar.'
      return !accountErrors.value.bank_code
    }

    if (fieldName === 'account_number') {
      accountErrors.value.account_number = values.account_number.trim().length >= 4
        ? ''
        : 'Ingresa un número de cuenta válido.'
      return !accountErrors.value.account_number
    }

    if (fieldName === 'account_type') {
      accountErrors.value.account_type = ['ahorros', 'corriente'].includes(values.account_type)
        ? ''
        : 'Selecciona un tipo de cuenta.'
      return !accountErrors.value.account_type
    }

    if (fieldName === 'account_holder') {
      accountErrors.value.account_holder = values.account_holder.trim().length >= 3
        ? ''
        : 'Ingresa el nombre del titular.'
      return !accountErrors.value.account_holder
    }

    if (fieldName === 'identification_type') {
      accountErrors.value.identification_type = ['cc', 'ce', 'nit'].includes(values.identification_type)
        ? ''
        : 'Selecciona un tipo de documento.'
      return !accountErrors.value.identification_type
    }

    if (fieldName === 'identification_number') {
      accountErrors.value.identification_number = values.identification_number.trim().length >= 5
        ? ''
        : 'Ingresa un número de documento válido.'
      return !accountErrors.value.identification_number
    }

    if (fieldName === 'email') {
      if (!values.email.trim()) {
        accountErrors.value.email = ''
        return true
      }

      accountErrors.value.email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email.trim())
        ? ''
        : 'El correo electrónico no tiene un formato válido.'
      return !accountErrors.value.email
    }

    if (fieldName === 'phone') {
      const cleanPhone = values.phone.replace(/\s+/g, '')

      if (!cleanPhone) {
        accountErrors.value.phone = ''
        return true
      }

      accountErrors.value.phone = /^[0-9+()-]{7,15}$/.test(cleanPhone)
        ? ''
        : 'El teléfono debe tener entre 7 y 15 caracteres numéricos.'
      return !accountErrors.value.phone
    }

    return true
  }

  function validateAccountForm() {
    const fields = [
      'bank_code',
      'account_number',
      'account_type',
      'account_holder',
      'identification_type',
      'identification_number',
      'email',
      'phone',
    ]

    return fields.every((field) => validateAccountField(field))
  }

  function openProofModal(payment) {
    selectedProofPayment.value = payment
    showProofModal.value = true
  }

  function closeProofModal() {
    showProofModal.value = false
    selectedProofPayment.value = null
  }

  async function loadPayments() {
    loading.value = true

    try {
      const { data } = await paymentHttp.get('/admin/payments')
      const rows = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
      payments.value = rows.map(normalizePayment)
    } catch {
      payments.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadPaymentAccount() {
    try {
      const response = await getPaymentAccount()
      paymentAccount.value = response?.data || null
    } catch {
      paymentAccount.value = null
    }
  }

  async function loadAdminPaymentAccountConfig() {
    loadingAccountConfig.value = true

    try {
      const response = await getAdminPaymentAccountConfig()
      const payload = response?.data || {}

      paymentAccount.value = payload.account || null
      accountBanks.value = Array.isArray(payload.banks) ? payload.banks : []
      applyAccountToForm(payload.account || null)

      if (accountBanks.value.length === 0) {
        const banksFallback = await getBanks()
        accountBanks.value = Array.isArray(banksFallback?.data) ? banksFallback.data : []
      }
    } catch {
      accountBanks.value = []
      applyAccountToForm(null)
      showSnackbar({ type: 'error', message: 'No se pudo cargar la configuración de la cuenta.' })
    } finally {
      loadingAccountConfig.value = false
      accountErrors.value = buildEmptyAccountErrors()
    }
  }

  async function submitAccountConfig() {
    if (savingAccountConfig.value) return

    if (!validateAccountForm()) {
      showSnackbar({ type: 'warning', message: 'Revisa los campos de la cuenta antes de guardar.' })
      return
    }

    savingAccountConfig.value = true

    try {
      const payload = {
        ...accountForm.value,
        bank_code: accountForm.value.bank_code,
        account_number: accountForm.value.account_number.trim(),
        account_type: accountForm.value.account_type,
        account_holder: accountForm.value.account_holder.trim(),
        identification_type: accountForm.value.identification_type,
        identification_number: accountForm.value.identification_number.trim(),
        email: accountForm.value.email.trim() || null,
        phone: accountForm.value.phone.trim() || null,
        is_active: Boolean(accountForm.value.is_active),
      }

      const response = await saveAdminPaymentAccountConfig(payload)
      paymentAccount.value = response?.data || null
      applyAccountToForm(paymentAccount.value)
      showSnackbar({ type: 'success', message: response?.message || 'Configuración de cuenta guardada.' })
      await loadPaymentAccount()
    } catch {
      showSnackbar({ type: 'error', message: 'No se pudo guardar la cuenta visible al cliente.' })
    } finally {
      savingAccountConfig.value = false
    }
  }

  // Orquesta la aprobación/rechazo conservando la sincronización actual con la orden asociada.
  async function updatePayment(payment, status) {
    if (syncingPaymentId.value !== null) return

    syncingPaymentId.value = payment.id
    syncingPaymentStatus.value = status
    const previousStatus = normalizePaymentStatus(payment.status)

    try {
      await paymentHttp.patch(`/admin/payments/${payment.id}`, { status })

      if (payment.order_id > 0) {
        try {
          await orderHttp.patch(`/orders/${payment.order_id}/payment-status`, {
            source: payment.source === 'legacy' ? 'legacy' : 'microservice',
            payment_status: mapTransactionStatusToOrderStatus(status),
            description: buildOrderPaymentDescription(status),
          })
        } catch (error) {
          const syncMessage = extractApiErrorMessage(error, 'No se pudo actualizar la orden.')
          const rollbackStatus = resolveRollbackStatusAfterOrderSyncFailure(error, previousStatus)
          let paymentRecovered = rollbackStatus === status

          if (!paymentRecovered) {
            try {
              await paymentHttp.patch(`/admin/payments/${payment.id}`, { status: rollbackStatus })
              paymentRecovered = true
            } catch {
              paymentRecovered = false
            }
          }

          if (shouldRejectPaymentAfterOrderSyncFailure(error)) {
            showSnackbar({
              type: paymentRecovered ? 'warning' : 'error',
              message: paymentRecovered
                ? `${syncMessage} El pago quedó rechazado para mantener consistencia con la orden.`
                : `${syncMessage} Además, no se pudo corregir el estado del pago automáticamente.`,
            })
          } else {
            showSnackbar({
              type: paymentRecovered ? 'warning' : 'error',
              message: paymentRecovered
                ? `${syncMessage} El pago volvió a su estado anterior.`
                : `${syncMessage} Además, no se pudo restaurar el estado anterior del pago.`,
            })
          }

          await loadPayments()
          return
        }
      }

      if (payment.order_id > 0) {
        showSnackbar({ type: 'success', message: status === 'approved' ? 'Pago verificado y pedido actualizado.' : 'Pago rechazado y pedido actualizado.' })
      } else {
        showSnackbar({ type: 'success', message: status === 'approved' ? 'Pago verificado.' : 'Pago rechazado.' })
      }

      await loadPayments()
    } catch (error) {
      showSnackbar({
        type: 'error',
        message: extractApiErrorMessage(error, 'No se pudo actualizar el pago.'),
      })
    } finally {
      syncingPaymentId.value = null
      syncingPaymentStatus.value = ''
    }
  }

  function isPaymentActionLoading(payment, status) {
    return syncingPaymentId.value === payment.id && syncingPaymentStatus.value === status
  }

  onMounted(async () => {
    await Promise.all([loadPayments(), loadPaymentAccount()])
  })

  return {
    accountBanks,
    accountErrors,
    accountForm,
    accountPreview,
    activeFilterCount,
    clearFilters,
    closeAccountModal,
    closeProofModal,
    filtered,
    formatCurrency,
    isPaymentActionLoading,
    loading,
    loadingAccountConfig,
    methodFilter,
    methodLabel,
    openAccountModal,
    openProofModal,
    pagination,
    paymentStats,
    savingAccountConfig,
    search,
    selectedProofPayment,
    showAccountModal,
    showProofModal,
    statusBadgeClass,
    statusFilter,
    statusLabel,
    submitAccountConfig,
    syncingPaymentId,
    updatePayment,
    validateAccountField,
  }
}
