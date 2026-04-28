<template>
  <div class="admin-discount-specific-page">
    <AdminPageHeader
      icon="fas fa-user-check"
      title="Campaña a usuarios específicos"
      subtitle="Selecciona un código, define canales y envía la campaña a clientes concretos sin usar modal."
      :breadcrumbs="[
        { label: 'Dashboard', to: '/admin' },
        { label: 'Códigos de descuento', to: '/admin/descuentos/codigos' },
        { label: 'Usuarios específicos' },
      ]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="goBackToDiscountCodes">
          <i class="fas fa-arrow-left"></i>
          Volver a códigos
        </button>
        <button
          class="btn btn-primary"
          type="button"
          :disabled="campaignSubmitting || specificCampaignForm.user_ids.length === 0"
          @click="submitSpecificCampaign"
        >
          <i :class="campaignSubmitting ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
          {{ campaignSubmitting
            ? 'Enviando...'
            : specificCampaignForm.user_ids.length > 0
              ? `Enviar a ${specificCampaignForm.user_ids.length} usuario${specificCampaignForm.user_ids.length !== 1 ? 's' : ''}`
              : 'Enviar campaña'
          }}
        </button>
      </template>
    </AdminPageHeader>

    <div class="specific-campaign-page-grid">
      <AdminCard title="Configuración de campaña" icon="fas fa-paper-plane">
        <div v-if="loadingCodes" class="specific-campaign-loading">
          <div class="specific-campaign-loading__item">
            <AdminShimmer type="line" width="36%" height="0.95rem" />
            <AdminShimmer type="rect" width="100%" height="2.8rem" radius="12px" />
          </div>
          <div class="specific-campaign-loading__item">
            <AdminShimmer type="line" width="42%" height="0.95rem" />
            <AdminShimmer type="rect" width="100%" height="5.4rem" radius="12px" />
          </div>
        </div>

        <AdminEmptyState
          v-else-if="campaignCodeOptions.length === 0"
          icon="fas fa-tags"
          title="Sin códigos disponibles"
          description="Crea al menos un código de descuento para usar esta campaña."
        />

        <div v-else class="specific-campaign-top-grid">
          <div class="specific-campaign-section">
            <div class="form-group">
              <label for="specific-campaign-code" class="specific-campaign-label">
                <i class="fas fa-tag"></i>
                Código de descuento *
                <AdminInfoTooltip text="Selecciona el código que se enviará a los clientes elegidos." />
              </label>
              <select
                id="specific-campaign-code"
                v-model="specificCampaignForm.discount_code_id"
                class="form-control"
                :class="{ 'is-invalid': specificCampaignErrors.discount_code_id }"
                @change="validateSpecificCampaignField('discount_code_id')"
              >
                <option value="">Selecciona un código</option>
                <option v-for="code in campaignCodeOptions" :key="`specific-${code.id}`" :value="String(code.id)">
                  {{ code.code }} — {{ formatDiscountValue(code) }}
                </option>
              </select>
              <p v-if="specificCampaignErrors.discount_code_id" class="form-error">{{ specificCampaignErrors.discount_code_id }}</p>
            </div>

            <transition name="campaign-preview-fade">
              <div v-if="selectedSpecificCode" class="campaign-code-preview">
                <div class="campaign-code-preview__icon" :class="selectedSpecificCode.type === 'percent' ? 'is-percent' : 'is-fixed'">
                  <i :class="selectedSpecificCode.type === 'percent' ? 'fas fa-percent' : 'fas fa-tag'"></i>
                </div>
                <div class="campaign-code-preview__body">
                  <strong class="campaign-code-preview__code">{{ selectedSpecificCode.code }}</strong>
                  <span class="campaign-code-preview__value">{{ formatDiscountValue(selectedSpecificCode) }} de descuento</span>
                  <span class="campaign-code-preview__meta">
                    <i class="fas fa-calendar-alt"></i>
                    {{ selectedSpecificCode.expires_at ? `Expira el ${formatShortDate(selectedSpecificCode.expires_at)}` : 'Sin fecha de expiración' }}
                  </span>
                  <span class="campaign-code-preview__meta">
                    <i class="fas fa-chart-bar"></i>
                    {{ selectedSpecificCode.times_used || 0 }} uso{{ selectedSpecificCode.times_used !== 1 ? 's' : '' }}
                    {{ selectedSpecificCode.max_uses ? `/ ${selectedSpecificCode.max_uses} máx.` : '(sin límite)' }}
                  </span>
                </div>
              </div>
            </transition>
          </div>

          <div class="specific-campaign-section">
            <p class="specific-campaign-label">
              <i class="fas fa-share-square"></i>
              Canales de envío
              <AdminInfoTooltip text="Activa al menos un canal para enviar la campaña. Puedes usar ambos al mismo tiempo." />
            </p>

            <div
              class="campaign-channel-card"
              :class="{ 'is-active': specificCampaignForm.send_notification }"
              @click="toggleSpecificChannel('notification')"
            >
              <div class="campaign-channel-card__icon campaign-channel-card__icon--notif">
                <i class="fas fa-bell"></i>
              </div>
              <div class="campaign-channel-card__info">
                <strong>Notificación interna</strong>
                <span>Aparece en el panel del cliente de forma inmediata.</span>
              </div>
              <div class="campaign-channel-card__toggle">
                <input
                  v-model="specificCampaignForm.send_notification"
                  type="checkbox"
                  @change="validateSpecificCampaignField('channels')"
                  @click.stop
                >
              </div>
            </div>

            <div
              class="campaign-channel-card"
              :class="{ 'is-active': specificCampaignForm.send_email }"
              @click="toggleSpecificChannel('email')"
            >
              <div class="campaign-channel-card__icon campaign-channel-card__icon--email">
                <i class="fas fa-envelope"></i>
              </div>
              <div class="campaign-channel-card__info">
                <strong>Correo electrónico</strong>
                <span>Envía el código al correo del cliente con detalle de la promoción.</span>
              </div>
              <div class="campaign-channel-card__toggle">
                <input
                  v-model="specificCampaignForm.send_email"
                  type="checkbox"
                  @change="validateSpecificCampaignField('channels')"
                  @click.stop
                >
              </div>
            </div>

            <p v-if="specificCampaignErrors.channels" class="form-error">{{ specificCampaignErrors.channels }}</p>
          </div>
        </div>
      </AdminCard>

      <AdminCard title="Clientes disponibles" icon="fas fa-users" :flush="true">
        <AdminFilterCard
          v-model="specificCampaignSearch"
          icon="fas fa-search"
          title="Buscar destinatarios"
          placeholder="Buscar por nombre o correo..."
          :initially-expanded="true"
          :hide-toggle="true"
          @search="() => {}"
        >
          <div class="specific-campaign-users-panel">
            <div class="specific-campaign-users-header__title">
              <span>Selección de destinatarios</span>
              <AdminInfoTooltip text="Marca los clientes que recibirán el descuento. Puedes seleccionar todos los visibles según el filtro actual." />
              <span
                class="specific-campaign-badge"
                :class="{ 'is-filled': specificCampaignForm.user_ids.length > 0 }"
              >
                {{ specificCampaignForm.user_ids.length }}
                {{ specificCampaignForm.user_ids.length === 1 ? 'seleccionado' : 'seleccionados' }}
              </span>
            </div>
            <div class="specific-campaign-users-header__actions">
              <button
                type="button"
                class="campaign-action-chip"
                title="Seleccionar todos los visibles"
                @click="selectAllFilteredCustomers"
              >
                <i class="fas fa-check-double"></i>
                <span>Todos</span>
              </button>
              <button
                type="button"
                class="campaign-action-chip campaign-action-chip--clear"
                title="Limpiar selección"
                :disabled="specificCampaignForm.user_ids.length === 0"
                @click="clearSpecificCustomerSelection"
              >
                <i class="fas fa-ban"></i>
                <span>Limpiar</span>
              </button>
            </div>
          </div>
        </AdminFilterCard>

        <AdminResultsBar :text="customerResultsText" />

        <div class="campaign-users-list campaign-users-list--page">
          <div v-if="campaignCustomersLoading" class="campaign-users-list__state campaign-users-list__state--loading">
            <div v-for="n in 6" :key="`campaign-shimmer-${n}`" class="campaign-user-shimmer">
              <AdminShimmer type="circle" width="2.35rem" height="2.35rem" />
              <div class="campaign-user-shimmer__body">
                <AdminShimmer type="line" width="52%" height="0.86rem" />
                <AdminShimmer type="line" width="68%" height="0.78rem" />
              </div>
            </div>
          </div>

          <AdminEmptyState
            v-else-if="filteredCampaignCustomers.length === 0"
            icon="fas fa-user-slash"
            title="Sin resultados"
            description="No hay clientes para mostrar con el filtro actual."
          />

          <label
            v-for="customer in filteredCampaignCustomers"
            v-else
            :key="customer.id"
            class="campaign-user-item"
            :class="{ 'is-selected': specificCampaignForm.user_ids.includes(String(customer.id)) }"
          >
            <input
              v-model="specificCampaignForm.user_ids"
              type="checkbox"
              :value="String(customer.id)"
              @change="validateSpecificCampaignField('user_ids')"
            >
            <div class="campaign-user-avatar">{{ userInitials(customer) }}</div>
            <div class="campaign-user-item__meta">
              <strong>{{ customer.name || 'Cliente' }}</strong>
              <span>{{ customer.email || 'Sin correo registrado' }}</span>
            </div>
            <span class="campaign-user-item__status" :class="{ 'is-selected': specificCampaignForm.user_ids.includes(String(customer.id)) }">
              {{ specificCampaignForm.user_ids.includes(String(customer.id)) ? 'Seleccionado' : 'Disponible' }}
            </span>
          </label>
        </div>
        <p v-if="specificCampaignErrors.user_ids" class="form-error">{{ specificCampaignErrors.user_ids }}</p>
      </AdminCard>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { discountHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminShimmer from '../components/AdminShimmer.vue'

const router = useRouter()
const { showSnackbar } = useSnackbarSystem()

const loadingCodes = ref(true)
const campaignSubmitting = ref(false)
const campaignCustomersLoading = ref(false)
const codes = ref([])
const campaignCustomers = ref([])
const specificCampaignSearch = ref('')

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

const filteredCampaignCustomers = computed(() => {
  const term = specificCampaignSearch.value.trim().toLowerCase()

  if (!term) return campaignCustomers.value

  return campaignCustomers.value.filter((customer) => [customer.name, customer.email].join(' ').toLowerCase().includes(term))
})

const customerResultsText = computed(() => {
  const visible = filteredCampaignCustomers.value.length
  const total = campaignCustomers.value.length
  const selected = specificCampaignForm.user_ids.length

  return `Mostrando ${visible} de ${total} cliente${total === 1 ? '' : 's'} · ${selected} seleccionado${selected === 1 ? '' : 's'}`
})

onMounted(async () => {
  await loadCodes()
  resetSpecificCampaignForm()
  await loadCampaignCustomers()
})

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

function toggleSpecificChannel(channel) {
  if (channel === 'notification') {
    specificCampaignForm.send_notification = !specificCampaignForm.send_notification
  } else if (channel === 'email') {
    specificCampaignForm.send_email = !specificCampaignForm.send_email
  }

  validateSpecificCampaignField('channels')
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

function campaignSummaryMessage(summary) {
  if (!summary) {
    return 'Campaña enviada correctamente.'
  }

  const notifications = summary.notifications || { sent: 0, failed: 0 }
  const emails = summary.emails || { sent: 0, failed: 0 }

  return `Notificaciones: ${notifications.sent} enviadas / ${notifications.failed} fallidas. Correos: ${emails.sent} enviados / ${emails.failed} fallidos.`
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

    resetSpecificCampaignForm()
    await loadCampaignCustomers()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío a usuarios específicos.') })
  } finally {
    campaignSubmitting.value = false
  }
}

function goBackToDiscountCodes() {
  router.push({ name: 'admin-discount-codes' })
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
</script>

<style scoped>
/* ── Contenedor principal de la página ── */
.admin-discount-specific-page {
  display: grid;
  gap: 1.8rem;
}

/* ── Grid de dos columnas en escritorio grande ── */
.specific-campaign-page-grid {
  display: grid;
  gap: 1.8rem;
}

@media (min-width: 1200px) {
  .specific-campaign-page-grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
    align-items: start;
  }
}

/* ── Shimmer de carga ── */
.specific-campaign-loading {
  display: grid;
  gap: 1.2rem;
}

.specific-campaign-loading__item {
  display: grid;
  gap: 0.7rem;
}

/* ── Grid interno: selector de código + canales ── */
.specific-campaign-top-grid {
  display: grid;
  gap: 1.4rem;
  grid-template-columns: minmax(0, 1fr);
}

.specific-campaign-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ── Label de sección (uppercase, estilo admin) ── */
.specific-campaign-label {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-weight: 700;
  font-size: 1.2rem;
  color: var(--admin-text-soft);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.4rem;
}

/* ── Vista previa del código seleccionado ── */
.campaign-code-preview {
  display: flex;
  align-items: flex-start;
  gap: 1.2rem;
  padding: 1.4rem 1.5rem;
  border-radius: var(--admin-radius-lg, 12px);
  border: 1px solid rgba(0, 119, 182, 0.22);
  background: linear-gradient(135deg, rgba(0, 119, 182, 0.06) 0%, rgba(0, 180, 216, 0.04) 100%);
  box-shadow: 0 2px 8px rgba(0, 119, 182, 0.06);
}

.campaign-code-preview__icon {
  width: 4rem;
  height: 4rem;
  flex-shrink: 0;
  border-radius: var(--admin-radius-md, 10px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  background: rgba(0, 119, 182, 0.12);
  color: var(--admin-primary, #0077b6);
  border: 1px solid rgba(0, 119, 182, 0.15);
}

.campaign-code-preview__icon.is-fixed {
  background: rgba(34, 197, 94, 0.12);
  color: #16a34a;
  border-color: rgba(34, 197, 94, 0.2);
}

.campaign-code-preview__body {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  min-width: 0;
}

.campaign-code-preview__code {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--admin-text-heading, #24364b);
  word-break: break-all;
  letter-spacing: 0.02em;
}

.campaign-code-preview__value {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--admin-primary, #0077b6);
}

.campaign-code-preview__meta {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 1.2rem;
  color: var(--admin-text-light, #777);
}

.campaign-code-preview__meta i {
  font-size: 1.1rem;
  color: var(--admin-text-soft);
}

/* ── Tarjetas de canal de envío ── */
.campaign-channel-card {
  display: flex;
  align-items: center;
  gap: 1.1rem;
  padding: 1.25rem 1.4rem;
  border-radius: var(--admin-radius-lg, 12px);
  border: 1.5px solid var(--admin-border-light, #d9e8f4);
  background: var(--admin-bg, #fff);
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  user-select: none;
}

.campaign-channel-card:hover {
  border-color: rgba(0, 119, 182, 0.35);
  background: rgba(0, 119, 182, 0.03);
  box-shadow: 0 2px 10px rgba(0, 119, 182, 0.07);
}

.campaign-channel-card.is-active {
  border-color: var(--admin-primary, #0077b6);
  background: rgba(0, 119, 182, 0.06);
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.1), 0 2px 8px rgba(0, 119, 182, 0.08);
}

.campaign-channel-card__icon {
  width: 3.4rem;
  height: 3.4rem;
  flex-shrink: 0;
  border-radius: var(--admin-radius-md, 10px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  background: rgba(0, 119, 182, 0.1);
  color: var(--admin-primary, #0077b6);
  border: 1px solid rgba(0, 119, 182, 0.14);
}

.campaign-channel-card__icon--email {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border-color: rgba(34, 197, 94, 0.18);
}

.campaign-channel-card__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.campaign-channel-card__info strong {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--admin-text, #333);
}

.campaign-channel-card__info span {
  font-size: 1.2rem;
  color: var(--admin-text-light, #777);
  line-height: 1.4;
}

/* ── Toggle switch (reemplaza checkbox nativo) ── */
.campaign-channel-card__toggle {
  flex-shrink: 0;
}

.campaign-channel-card__toggle input[type="checkbox"] {
  appearance: none;
  -webkit-appearance: none;
  width: 4rem;
  height: 2.2rem;
  border-radius: 999px;
  background: #d1d5db;
  position: relative;
  cursor: pointer;
  transition: background 0.22s;
  vertical-align: middle;
  flex-shrink: 0;
}

.campaign-channel-card__toggle input[type="checkbox"]::after {
  content: '';
  position: absolute;
  left: 0.25rem;
  top: 0.25rem;
  width: 1.7rem;
  height: 1.7rem;
  border-radius: 50%;
  background: #fff;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.22);
}

.campaign-channel-card__toggle input[type="checkbox"]:checked {
  background: var(--admin-primary, #0077b6);
}

.campaign-channel-card__toggle input[type="checkbox"]:checked::after {
  transform: translateX(1.8rem);
}

/* ── Panel de usuarios: cabecera ── */
.specific-campaign-users-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.9rem;
  flex-wrap: wrap;
  padding: 0.65rem 0.7rem;
  border-radius: 10px;
  border: 1px solid var(--admin-border-light, #d9e8f4);
  background: #fbfdff;
}

.specific-campaign-users-header__title {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.55rem;
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--admin-text-heading, #24364b);
}

.specific-campaign-users-header__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* ── Badge de seleccionados ── */
.specific-campaign-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.9rem;
  border-radius: 999px;
  font-size: 1.2rem;
  font-weight: 700;
  background: rgba(0, 119, 182, 0.1);
  color: var(--admin-text-soft, #4f657b);
  transition: background 0.2s, color 0.2s;
  border: 1px solid rgba(0, 119, 182, 0.15);
}

.specific-campaign-badge.is-filled {
  background: var(--admin-primary, #0077b6);
  color: #fff;
  border-color: transparent;
}

/* ── Chips de acción (Todos / Limpiar) ── */
.campaign-action-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.1rem;
  border-radius: 999px;
  font-size: 1.25rem;
  font-weight: 700;
  background: rgba(0, 119, 182, 0.07);
  color: var(--admin-primary, #0077b6);
  border: 1.5px solid rgba(0, 119, 182, 0.2);
  cursor: pointer;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
  white-space: nowrap;
  line-height: 1;
}

.campaign-action-chip:hover {
  background: rgba(0, 119, 182, 0.14);
  border-color: rgba(0, 119, 182, 0.4);
}

.campaign-action-chip--clear {
  background: rgba(220, 38, 38, 0.06);
  color: #dc2626;
  border-color: rgba(220, 38, 38, 0.2);
}

.campaign-action-chip--clear:hover:not(:disabled) {
  background: rgba(220, 38, 38, 0.12);
  border-color: rgba(220, 38, 38, 0.38);
}

.campaign-action-chip:disabled {
  opacity: 0.38;
  cursor: not-allowed;
}

/* ── Lista de usuarios ── */
.campaign-users-list {
  max-height: min(58vh, 580px);
  overflow-y: auto;
  border: 1px solid var(--admin-border-light, #d9e8f4);
  border-radius: var(--admin-radius-lg, 12px);
  padding: 0.6rem;
  background: var(--admin-bg-soft, #f8fbfe);
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 119, 182, 0.2) transparent;
}

.campaign-users-list--page {
  min-height: 280px;
}

.campaign-users-list__state {
  display: grid;
  gap: 0.7rem;
}

.campaign-users-list__state--loading {
  padding: 0.5rem 0.3rem;
}

/* ── Shimmer de carga en lista de usuarios ── */
.campaign-user-shimmer {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.7rem 0.6rem;
}

.campaign-user-shimmer__body {
  flex: 1;
  display: grid;
  gap: 0.5rem;
}

/* ── Ítem de usuario en la lista ── */
.campaign-user-item {
  display: grid;
  grid-template-columns: auto auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.1rem;
  border-radius: var(--admin-radius-md, 10px);
  border: 1px solid transparent;
  cursor: pointer;
  transition: background 0.16s, border-color 0.16s, box-shadow 0.16s;
}

.campaign-user-item:hover {
  background: rgba(0, 119, 182, 0.04);
  border-color: rgba(0, 119, 182, 0.18);
}

.campaign-user-item.is-selected {
  background: rgba(0, 119, 182, 0.07);
  border-color: rgba(0, 119, 182, 0.3);
  box-shadow: 0 1px 4px rgba(0, 119, 182, 0.07);
}

.campaign-user-item input[type="checkbox"] {
  width: 1.5rem;
  height: 1.5rem;
  flex-shrink: 0;
  cursor: pointer;
  accent-color: var(--admin-primary, #0077b6);
}

/* ── Avatar de usuario ── */
.campaign-user-avatar {
  width: 3.2rem;
  height: 3.2rem;
  flex-shrink: 0;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(0, 119, 182, 0.18) 0%, rgba(0, 180, 216, 0.12) 100%);
  color: var(--admin-primary, #0077b6);
  font-size: 1.15rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
  border: 1px solid rgba(0, 119, 182, 0.2);
  letter-spacing: 0.02em;
}

.campaign-user-item.is-selected .campaign-user-avatar {
  background: var(--admin-primary, #0077b6);
  color: #fff;
  border-color: transparent;
}

/* ── Metadatos del usuario ── */
.campaign-user-item__meta {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.campaign-user-item__meta strong {
  font-size: 1.35rem;
  font-weight: 600;
  color: var(--admin-text, #333);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.campaign-user-item__meta span {
  color: var(--admin-text-light, #777);
  font-size: 1.2rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Badge de estado del ítem ── */
.campaign-user-item__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.3rem 0.8rem;
  border-radius: 999px;
  border: 1px solid var(--admin-border, #e0e0e0);
  color: var(--admin-text-soft, #4f657b);
  font-size: 1.15rem;
  font-weight: 600;
  background: var(--admin-bg, #fff);
  transition: background 0.18s, color 0.18s, border-color 0.18s;
  white-space: nowrap;
}

.campaign-user-item__status.is-selected {
  color: #fff;
  border-color: var(--admin-primary, #0077b6);
  background: var(--admin-primary, #0077b6);
}

/* ── Transición del preview de código ── */
.campaign-preview-fade-enter-active,
.campaign-preview-fade-leave-active {
  transition: opacity 0.24s ease, transform 0.24s ease;
}

.campaign-preview-fade-enter-from,
.campaign-preview-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Responsive: grid interno del selector + canales ── */
@media (min-width: 940px) {
  .specific-campaign-top-grid {
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    align-items: start;
  }
}

/* ── Responsive: tablet ── */
@media (max-width: 860px) {
  .specific-campaign-users-panel {
    flex-direction: column;
    align-items: stretch;
  }

  .specific-campaign-users-header__actions {
    width: 100%;
  }

  .specific-campaign-users-header__actions .campaign-action-chip {
    flex: 1;
    justify-content: center;
  }

  .campaign-user-item {
    grid-template-columns: auto auto minmax(0, 1fr);
  }

  .campaign-user-item__status {
    grid-column: 1 / -1;
    width: fit-content;
    margin-left: calc(1.5rem + 3.2rem + 1rem);
  }
}

/* ── Responsive: móvil ── */
@media (max-width: 640px) {
  .campaign-user-item {
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.75rem;
    padding: 0.85rem 0.9rem;
  }

  .campaign-user-avatar {
    width: 2.8rem;
    height: 2.8rem;
    font-size: 1.05rem;
  }

  .campaign-user-item input[type="checkbox"] {
    grid-row: span 2;
  }

  .campaign-user-item__meta strong {
    font-size: 1.3rem;
  }

  .campaign-user-item__meta span {
    font-size: 1.15rem;
  }

  .campaign-user-item__status {
    margin-left: 0;
    font-size: 1.1rem;
  }

  .campaign-code-preview {
    gap: 1rem;
    padding: 1.2rem;
  }

  .campaign-channel-card {
    padding: 1rem 1.1rem;
  }
}
</style>
