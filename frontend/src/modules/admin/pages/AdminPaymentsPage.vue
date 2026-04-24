<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-credit-card"
      title="Pagos"
      subtitle="Gestiona pagos, verifica comprobantes y revisa la cuenta activa para transferencias."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Pagos' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="openAccountModal">
          <i class="fas fa-cog"></i>
          Cuenta visible al cliente
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="paymentStats" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y control de pagos"
      placeholder="Buscar por orden, cliente o referencia..."
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--2">
          <div class="admin-filters__group">
            <label for="payment-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="payment-status" v-model="statusFilter">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Verificado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div class="admin-filters__group">
            <label for="payment-method"><i class="fas fa-credit-card"></i> Método</label>
            <select id="payment-method" v-model="methodFilter">
              <option value="">Todos</option>
              <option value="transfer">Transferencia</option>
              <option value="cash">Efectivo</option>
              <option value="card">Tarjeta</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} pagos`" />

    <AdminCard title="Listado de pagos" icon="fas fa-list" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'line', 'pill', 'btn', 'btn']" />
      <AdminEmptyState v-else-if="filtered.length === 0" icon="fas fa-credit-card" title="Sin pagos registrados" description="No se encontraron pagos con los filtros actuales." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Orden</th>
              <th>Cliente</th>
              <th>Monto</th>
              <th>Método</th>
              <th>Estado</th>
              <th>Comprobante</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in pagination.paginatedItems" :key="payment.id">
              <td>{{ payment.id }}</td>
              <td>
                <RouterLink v-if="payment.order_id" :to="`/admin/ordenes/${payment.order_id}`">#{{ payment.order_id }}</RouterLink>
                <span v-else>—</span>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ payment.customer_name || 'Cliente' }}</strong>
                  <span>{{ payment.customer_email || 'Sin correo' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(payment.amount) }}</strong></td>
              <td>{{ methodLabel(payment.method) }}</td>
              <td>
                <span class="status-badge" :class="statusBadgeClass(payment.status)">
                  {{ statusLabel(payment.status) }}
                </span>
              </td>
              <td>
                <button
                  v-if="payment.proof_url"
                  type="button"
                  class="btn btn-sm btn-secondary"
                  title="Ver comprobante"
                  @click="openProofModal(payment)"
                >
                  <i class="fas fa-search-plus"></i>
                </button>
                <span v-else>—</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <RouterLink
                    v-if="payment.order_id"
                    :to="`/admin/ordenes/${payment.order_id}`"
                    class="action-btn view"
                    title="Ir a la orden"
                  >
                    <i class="fas fa-external-link-alt"></i>
                  </RouterLink>

                  <button
                    v-if="payment.status === 'pending'"
                    class="action-btn edit"
                    type="button"
                    title="Verificar pago"
                    :disabled="syncingPaymentId === payment.id"
                    @click="updatePayment(payment, 'approved')"
                  >
                    <i class="fas fa-check"></i>
                  </button>
                  <button
                    v-if="payment.status === 'pending'"
                    class="action-btn delete"
                    type="button"
                    title="Rechazar pago"
                    :disabled="syncingPaymentId === payment.id"
                    @click="updatePayment(payment, 'rejected')"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal :show="showAccountModal" title="Cuenta visible al cliente" max-width="1040px" @close="closeAccountModal">
      <div class="payment-account-modal">
        <p class="payment-account-modal__intro">Esta es la cuenta activa que se muestra al cliente cuando va a registrar una transferencia en el paso de pagos.</p>

        <div class="payment-account-modal__grid">
          <div class="payment-account-modal__summary">
            <PaymentAccountSummaryCard
                :account="accountPreview"
              description="Información activa para recibir transferencias de clientes."
            />
          </div>

          <div class="payment-account-modal__editor">
            <div class="payment-account-modal__editor-header">
              <h4>Configurar datos de cuenta</h4>
              <p>Actualiza aquí la cuenta que verá el cliente al momento de reportar su transferencia.</p>
            </div>

            <p v-if="loadingAccountConfig" class="loading-box">Cargando configuración...</p>

            <div v-else class="payment-account-form-grid">
              <div class="form-group">
                <label for="payment-account-bank">Banco *</label>
                <select
                  id="payment-account-bank"
                  v-model="accountForm.bank_code"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('bank_code')"
                >
                  <option value="">Selecciona un banco</option>
                  <option v-for="bank in accountBanks" :key="bank.bank_code || bank.id" :value="bank.bank_code">
                    {{ bank.bank_name }}
                  </option>
                </select>
                <small v-if="accountErrors.bank_code" class="form-error">{{ accountErrors.bank_code }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-type">Tipo de cuenta *</label>
                <select
                  id="payment-account-type"
                  v-model="accountForm.account_type"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('account_type')"
                >
                  <option value="ahorros">Cuenta de ahorros</option>
                  <option value="corriente">Cuenta corriente</option>
                </select>
                <small v-if="accountErrors.account_type" class="form-error">{{ accountErrors.account_type }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-number">Número de cuenta *</label>
                <input
                  id="payment-account-number"
                  v-model.trim="accountForm.account_number"
                  type="text"
                  placeholder="Ejemplo: 1234567890"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('account_number')"
                >
                <small v-if="accountErrors.account_number" class="form-error">{{ accountErrors.account_number }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-holder">Titular *</label>
                <input
                  id="payment-account-holder"
                  v-model.trim="accountForm.account_holder"
                  type="text"
                  placeholder="Nombre del titular"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('account_holder')"
                >
                <small v-if="accountErrors.account_holder" class="form-error">{{ accountErrors.account_holder }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-id-type">Tipo de documento *</label>
                <select
                  id="payment-account-id-type"
                  v-model="accountForm.identification_type"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('identification_type')"
                >
                  <option value="cc">Cédula</option>
                  <option value="ce">Cédula de extranjería</option>
                  <option value="nit">NIT</option>
                </select>
                <small v-if="accountErrors.identification_type" class="form-error">{{ accountErrors.identification_type }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-id-number">Número de documento *</label>
                <input
                  id="payment-account-id-number"
                  v-model.trim="accountForm.identification_number"
                  type="text"
                  placeholder="Ejemplo: 900123456"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('identification_number')"
                >
                <small v-if="accountErrors.identification_number" class="form-error">{{ accountErrors.identification_number }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-email">Email de contacto</label>
                <input
                  id="payment-account-email"
                  v-model.trim="accountForm.email"
                  type="email"
                  placeholder="pagos@tuempresa.com"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('email')"
                >
                <small v-if="accountErrors.email" class="form-error">{{ accountErrors.email }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-phone">Teléfono de contacto</label>
                <input
                  id="payment-account-phone"
                  v-model.trim="accountForm.phone"
                  type="text"
                  placeholder="3001234567"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('phone')"
                >
                <small v-if="accountErrors.phone" class="form-error">{{ accountErrors.phone }}</small>
              </div>

              <AdminToggleSwitch
                id="payment-account-active"
                class="payment-account-form-grid__full payment-account-form-grid__toggle"
                v-model="accountForm.is_active"
                :disabled="savingAccountConfig"
                title="Cuenta activa"
                description="Activa esta cuenta para que los clientes la vean al registrar sus pagos."
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-primary" :disabled="savingAccountConfig || loadingAccountConfig" @click="submitAccountConfig">
          {{ savingAccountConfig ? 'Guardando...' : 'Guardar configuración' }}
        </button>
        <button type="button" class="btn btn-secondary" @click="closeAccountModal">Cerrar</button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal :show="showProofModal" :payment="selectedProofPayment" @close="closeProofModal" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import PaymentAccountSummaryCard from '../../../components/payments/PaymentAccountSummaryCard.vue'
import { orderHttp, paymentHttp } from '../../../services/http'
import {
  getAdminPaymentAccountConfig,
  getBanks,
  getPaymentAccount,
  saveAdminPaymentAccountConfig,
} from '../../../services/paymentApi'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminPagination } from '../composables/useAdminPagination'
import { getPaymentMethodLabel, getPaymentStatusBadgeClass, getPaymentStatusLabel } from '../utils/orderPresentation'

const { showSnackbar } = useSnackbarSystem()

const payments = ref([])
const paymentAccount = ref(null)
const accountBanks = ref([])
const loading = ref(true)
const syncingPaymentId = ref(null)
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
    proof_url: rawPayment.proof_url || '',
    proof_name: rawPayment.proof_name || '',
    proof_exists: rawPayment.proof_exists !== false,
  }
}

function normalizePaymentStatus(status) {
  const normalized = String(status ?? '').toLowerCase().trim()

  if (!normalized) return 'pending'
  if (['approved', 'verified', 'paid'].includes(normalized)) return 'approved'
  if (['rejected', 'failed', 'cancelled', 'canceled'].includes(normalized)) return 'rejected'

  return 'pending'
}

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

function mapTransactionStatusToOrderStatus(status) {
  return status === 'approved' ? 'verified' : (status === 'rejected' ? 'failed' : 'pending')
}

function buildOrderPaymentDescription(status) {
  return status === 'approved'
    ? 'Pago verificado desde administración de pagos.'
    : 'Pago rechazado desde administración de pagos.'
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
  showAccountModal.value = false
}

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

async function updatePayment(payment, status) {
  syncingPaymentId.value = payment.id

  try {
    await paymentHttp.patch(`/admin/payments/${payment.id}`, { status })

    let orderSyncFailed = false
    if (payment.order_id > 0) {
      try {
        await orderHttp.patch(`/orders/${payment.order_id}/payment-status`, {
          source: payment.source === 'legacy' ? 'legacy' : 'microservice',
          payment_status: mapTransactionStatusToOrderStatus(status),
          description: buildOrderPaymentDescription(status),
        })
      } catch {
        orderSyncFailed = true
      }
    }

    if (orderSyncFailed) {
      showSnackbar({ type: 'warning', message: `Pago ${status === 'approved' ? 'verificado' : 'rechazado'}, pero no se pudo actualizar el pedido.` })
    } else if (payment.order_id > 0) {
      showSnackbar({ type: 'success', message: status === 'approved' ? 'Pago verificado y pedido actualizado.' : 'Pago rechazado y pedido actualizado.' })
    } else {
      showSnackbar({ type: 'success', message: status === 'approved' ? 'Pago verificado.' : 'Pago rechazado.' })
    }

    await loadPayments()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo actualizar el pago.' })
  } finally {
    syncingPaymentId.value = null
  }
}

onMounted(async () => {
  await Promise.all([loadPayments(), loadPaymentAccount()])
})
</script>

<style scoped>
.payment-account-modal {
  display: grid;
  gap: 1rem;
}

.payment-account-modal__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
  gap: 1rem;
}

.payment-account-modal__editor {
  border: 1px solid #d8e5f2;
  border-radius: 1rem;
  padding: 1rem;
  background: #f9fcff;
  display: grid;
  gap: 1rem;
}

.payment-account-modal__editor-header {
  display: grid;
  gap: 0.35rem;
}

.payment-account-modal__editor-header h4 {
  margin: 0;
  font-size: 1.15rem;
  color: #20344a;
}

.payment-account-modal__editor-header p {
  margin: 0;
  color: #607289;
  line-height: 1.5;
}

.payment-account-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.payment-account-form-grid .form-group {
  display: grid;
  gap: 0.35rem;
}

.payment-account-form-grid .form-group label {
  font-weight: 700;
  color: #30475f;
}

.payment-account-form-grid .form-group input,
.payment-account-form-grid .form-group select {
  width: 100%;
  border: 1px solid #cfe0ec;
  border-radius: 0.7rem;
  padding: 0.65rem 0.8rem;
  font-size: 0.95rem;
  background: #fff;
}

.payment-account-form-grid .form-group input:focus,
.payment-account-form-grid .form-group select:focus {
  outline: none;
  border-color: #0f7abf;
  box-shadow: 0 0 0 3px rgba(15, 122, 191, 0.12);
}

.payment-account-form-grid__full {
  grid-column: 1 / -1;
}

.payment-account-form-grid__toggle {
  margin-top: 0.25rem;
}

.payment-account-form-grid__toggle strong {
  color: #20344a;
  font-size: 1rem;
}

.payment-account-form-grid__toggle p {
  margin: 0.25rem 0 0;
  color: #607289;
  line-height: 1.4;
  font-size: 0.92rem;
}

.form-error {
  color: #d14343;
  font-size: 0.85rem;
}

.payment-account-modal__intro {
  margin: 0;
  color: #526277;
  font-size: 1.05rem;
  line-height: 1.6;
}

@media (max-width: 960px) {
  .payment-account-modal__grid {
    grid-template-columns: 1fr;
  }

  .payment-account-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
