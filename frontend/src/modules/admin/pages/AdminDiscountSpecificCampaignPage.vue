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
      <AdminCard class="specific-campaign-config-card" title="Configuración de campaña" icon="fas fa-paper-plane">
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

        <div v-else class="specific-campaign-config">

          <!-- Paso 1: Código de descuento -->
          <div class="specific-campaign-step">
            <div class="specific-campaign-step__header">
              <span class="specific-campaign-step__num">1</span>
              <span class="specific-campaign-step__title">
                <i class="fas fa-tag"></i>
                Código de descuento
                <AdminInfoTooltip text="Selecciona el código que se enviará a los clientes elegidos." />
              </span>
            </div>
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

            <transition name="campaign-preview-fade">
              <div v-if="selectedSpecificCode" class="campaign-code-summary">
                <span class="campaign-code-summary__pill" :class="selectedSpecificCode.type === 'percent' ? 'is-percent' : 'is-fixed'">
                  <i :class="selectedSpecificCode.type === 'percent' ? 'fas fa-percent' : 'fas fa-tag'"></i>
                  {{ formatDiscountValue(selectedSpecificCode) }}
                </span>
                <span class="campaign-code-summary__meta">
                  <i class="fas fa-calendar-alt"></i>
                  {{ selectedSpecificCode.expires_at ? formatShortDate(selectedSpecificCode.expires_at) : 'Sin expiración' }}
                </span>
                <span class="campaign-code-summary__meta">
                  <i class="fas fa-chart-bar"></i>
                  {{ selectedSpecificCode.times_used || 0 }}{{ selectedSpecificCode.max_uses ? `/${selectedSpecificCode.max_uses}` : '' }}
                  uso{{ (selectedSpecificCode.times_used || 0) !== 1 ? 's' : '' }}
                  {{ !selectedSpecificCode.max_uses ? '(sin límite)' : '' }}
                </span>
              </div>
            </transition>
          </div>

          <!-- Paso 2: Canales de envío -->
          <div class="specific-campaign-step">
            <div class="specific-campaign-step__header">
              <span class="specific-campaign-step__num">2</span>
              <span class="specific-campaign-step__title">
                <i class="fas fa-share-square"></i>
                Canales de envío
                <AdminInfoTooltip text="Activa al menos un canal para enviar la campaña. Puedes usar ambos al mismo tiempo." />
              </span>
            </div>
            <div class="campaign-channels-grid">
              <AdminToggleSwitch
                id="specific-campaign-send-notification"
                v-model="specificCampaignForm.send_notification"
                class="campaign-channel-toggle"
                :class="{ 'is-active': specificCampaignForm.send_notification }"
                title="Notificación interna"
                description="Panel del cliente"
                @change="validateSpecificCampaignField('channels')"
              />
              <AdminToggleSwitch
                id="specific-campaign-send-email"
                v-model="specificCampaignForm.send_email"
                class="campaign-channel-toggle"
                :class="{ 'is-active': specificCampaignForm.send_email }"
                title="Correo electrónico"
                description="Código con detalle"
                @change="validateSpecificCampaignField('channels')"
              />
            </div>
            <p v-if="specificCampaignErrors.channels" class="form-error">{{ specificCampaignErrors.channels }}</p>
          </div>
        </div>
      </AdminCard>

      <div class="specific-campaign-customers-panel">
        <AdminCard class="specific-campaign-customers-card" title="Clientes disponibles" icon="fas fa-users" :flush="true">
          <AdminFilterCard
            v-model="specificCampaignSearch"
            icon="fas fa-search"
            title="Buscar destinatarios"
            placeholder="Buscar por nombre o correo..."
            :initially-expanded="true"
            :hide-toggle="true"
            @search="() => {}"
          />

          <AdminResultsBar :text="customerResultsText">
            <template #actions>
              <div class="campaign-results-actions">
                <span
                  class="specific-campaign-badge"
                  :class="{ 'is-filled': specificCampaignForm.user_ids.length > 0 }"
                >
                  {{ specificCampaignForm.user_ids.length }}
                  {{ specificCampaignForm.user_ids.length === 1 ? 'seleccionado' : 'seleccionados' }}
                </span>
                <button
                  type="button"
                  class="results-action-btn results-action-btn--neutral"
                  :disabled="filteredCampaignCustomers.length === 0"
                  @click="selectAllFilteredCustomers"
                >
                  <span class="results-action-btn__icon"><i class="fas fa-check-double"></i></span>
                  Todos los visibles
                </button>
                <button
                  type="button"
                  class="results-action-btn results-action-btn--neutral campaign-results-btn--clear"
                  :disabled="specificCampaignForm.user_ids.length === 0"
                  @click="clearSpecificCustomerSelection"
                >
                  <span class="results-action-btn__icon"><i class="fas fa-ban"></i></span>
                  Limpiar
                </button>
              </div>
            </template>
          </AdminResultsBar>

          <AdminTableShimmer
            v-if="campaignCustomersLoading"
            :rows="6"
            :columns="customerTableShimmerColumns"
          />

          <AdminEmptyState
            v-else-if="filteredCampaignCustomers.length === 0"
            icon="fas fa-user-slash"
            title="Sin resultados"
            description="No hay clientes para mostrar con el filtro actual."
          />

          <div v-else class="table-responsive">
            <table class="dashboard-table campaign-customers-table">
              <thead>
                <tr>
                  <th class="selection-cell">
                    <input
                      type="checkbox"
                      :checked="allFilteredCustomersSelected"
                      @change="toggleFilteredCustomersSelection($event.target.checked)"
                    >
                  </th>
                  <th>Cliente</th>
                  <th>Contacto</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="customer in campaignCustomersPagination.paginatedItems"
                  :key="customer.id"
                  :class="{ 'campaign-customer-row--selected': isCustomerSelected(customer) }"
                >
                  <td class="selection-cell">
                    <input
                      v-model="specificCampaignForm.user_ids"
                      type="checkbox"
                      :value="String(customer.id)"
                      @change="validateSpecificCampaignField('user_ids')"
                    >
                  </td>
                  <td>
                    <div class="campaign-customer-cell">
                      <div class="campaign-user-avatar" :class="{ 'is-selected': isCustomerSelected(customer) }">{{ userInitials(customer) }}</div>
                      <div class="admin-entity-name">
                        <strong>{{ customer.name || 'Cliente' }}</strong>
                        <span>ID {{ customer.id }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="admin-entity-name">
                      <strong>{{ customer.email || 'Sin correo registrado' }}</strong>
                      <span>{{ isCustomerSelected(customer) ? 'Listo para recibir la campaña' : 'Disponible para selección' }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="campaign-selection-badge" :class="{ 'is-selected': isCustomerSelected(customer) }">
                      {{ isCustomerSelected(customer) ? 'Seleccionado' : 'Disponible' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="specificCampaignErrors.user_ids" class="form-error specific-campaign-error">{{ specificCampaignErrors.user_ids }}</p>
        </AdminCard>

        <AdminPagination
          v-model:page="campaignCustomersPagination.currentPage"
          v-model:page-size="campaignCustomersPagination.pageSize"
          :total-items="campaignCustomersPagination.totalItems"
          :page-size-options="campaignCustomersPagination.pageSizeOptions"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { discountHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const router = useRouter()
const { showSnackbar } = useSnackbarSystem()

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

// Filtra localmente los destinatarios para reutilizar la búsqueda compartida sin duplicar consultas al servicio.
const filteredCampaignCustomers = computed(() => {
  const term = specificCampaignSearch.value.trim().toLowerCase()

  if (!term) return campaignCustomers.value

  return campaignCustomers.value.filter((customer) => [customer.name, customer.email].join(' ').toLowerCase().includes(term))
})

// Pagina la tabla de clientes sobre el resultado filtrado usando la paginación global del admin.
const campaignCustomersPagination = useAdminPagination(filteredCampaignCustomers, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

// Detecta si la selección maestra ya cubre todos los clientes visibles del filtro actual.
const allFilteredCustomersSelected = computed(() =>
  filteredCampaignCustomers.value.length > 0
  && filteredCampaignCustomers.value.every((customer) => specificCampaignForm.user_ids.includes(String(customer.id))),
)

// Resume el estado visible de la tabla y la selección actual dentro de la barra reutilizable de resultados.
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

// Evita repetir la validación de selección en cada celda y badge de la tabla.
function isCustomerSelected(customer) {
  return specificCampaignForm.user_ids.includes(String(customer.id))
}

// Sincroniza la casilla maestra con los clientes visibles, agregando o quitando solo el subconjunto filtrado.
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

// Limpia toda la selección actual sin modificar el filtro de búsqueda aplicado por el admin.
function clearSpecificCustomerSelection() {
  specificCampaignForm.user_ids = []
  validateSpecificCampaignField('user_ids')
}

// Reutiliza la selección masiva sobre el subconjunto filtrado para evitar marcar clientes fuera de contexto.
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

/* ── Grid principal: configuración arriba | clientes abajo ── */
.specific-campaign-page-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  align-items: start;
  gap: 1.8rem;
}

.specific-campaign-config-card,
.specific-campaign-customers-card,
.specific-campaign-customers-panel {
  margin-bottom: 0;
  min-width: 0;
}

.specific-campaign-customers-panel {
  display: grid;
  gap: 1.4rem;
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

/* ── Contenedor de configuración: pasos verticales ── */
.specific-campaign-config {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.8rem;
}

/* ── Cada paso de configuración ── */
.specific-campaign-step {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  min-width: 0;
}

/* ── Cabecera de paso: número + título ── */
.specific-campaign-step__header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.specific-campaign-step__num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 50%;
  background: var(--admin-primary, #0077b6);
  color: #fff;
  font-size: 1.15rem;
  font-weight: 700;
  flex-shrink: 0;
}

.specific-campaign-step__title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--admin-text-heading, #24364b);
  text-transform: uppercase;
  letter-spacing: 0.055em;
}

/* ── Resumen compacto del código seleccionado ── */
.campaign-code-summary {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.65rem;
  padding: 0.8rem 1rem;
  border-radius: var(--admin-radius-md, 10px);
  background: rgba(0, 119, 182, 0.05);
  border: 1px solid rgba(0, 119, 182, 0.15);
}

.campaign-code-summary__pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.9rem;
  border-radius: 999px;
  background: var(--admin-primary, #0077b6);
  color: #fff;
  font-size: 1.25rem;
  font-weight: 700;
}

.campaign-code-summary__pill.is-fixed {
  background: #16a34a;
}

.campaign-code-summary__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 1.2rem;
  color: var(--admin-text-light, #777);
}

.campaign-code-summary__meta i {
  font-size: 1.05rem;
  color: var(--admin-text-soft);
}

/* ── Grid de canales: 2 columnas compactas ── */
.campaign-channels-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.campaign-channel-toggle {
  border-radius: var(--admin-radius-lg, 12px);
  transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.campaign-channel-toggle.is-active {
  border-color: rgba(0, 119, 182, 0.3);
  background: rgba(0, 119, 182, 0.05);
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.08);
}

.campaign-channel-toggle :deep(.admin-toggle-switch__copy strong) {
  font-size: 1.45rem;
}

.campaign-channel-toggle :deep(.admin-toggle-switch__copy p) {
  font-size: 1.16rem;
}

/* ── Acciones compactas dentro de la barra de resultados compartida ── */
.campaign-results-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.7rem;
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

.campaign-results-btn--clear {
  color: #c0392b;
  border-color: rgba(192, 57, 43, 0.18);
}

.campaign-results-btn--clear .results-action-btn__icon {
  background: rgba(192, 57, 43, 0.1);
  color: #c0392b;
}

/* ── Tabla de destinatarios reutilizando la infraestructura del admin ── */
.campaign-customers-table .selection-cell {
  width: 4.2rem;
  text-align: center;
}

/* ── La tabla de clientes hereda el card blanco y evita el glass gris del wrapper global ── */
.specific-campaign-customers-card :deep(.table-responsive) {
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
}

.campaign-customers-table .selection-cell input[type="checkbox"] {
  width: 1.5rem;
  height: 1.5rem;
  cursor: pointer;
  accent-color: var(--admin-primary, #0077b6);
}

.campaign-customer-row--selected {
  background: rgba(0, 119, 182, 0.05);
}

.campaign-customer-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* ── Avatar de usuario (color sólido suave, sin degradado) ── */
.campaign-user-avatar {
  width: 3.2rem;
  height: 3.2rem;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(0, 119, 182, 0.14);
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

.campaign-user-avatar.is-selected {
  background: var(--admin-primary, #0077b6);
  color: #fff;
  border-color: transparent;
}

.campaign-selection-badge {
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

.campaign-selection-badge.is-selected {
  color: #fff;
  border-color: var(--admin-primary, #0077b6);
  background: var(--admin-primary, #0077b6);
}

.specific-campaign-error {
  margin: 1rem 1.6rem 1.4rem;
}

/* ── Transición del resumen del código ── */
.campaign-preview-fade-enter-active,
.campaign-preview-fade-leave-active {
  transition: opacity 0.24s ease, transform 0.24s ease;
}

.campaign-preview-fade-enter-from,
.campaign-preview-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Responsive: canales en 1 columna en móvil pequeño ── */
@media (max-width: 480px) {
  .campaign-channels-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Responsive: tablet ── */
@media (max-width: 900px) {
  .specific-campaign-config {
    grid-template-columns: 1fr;
  }

  .campaign-channels-grid {
    grid-template-columns: 1fr;
  }

  .campaign-results-actions {
    width: 100%;
    justify-content: flex-start;
  }
}

/* ── Responsive: móvil ── */
@media (max-width: 640px) {
  .campaign-user-avatar {
    width: 2.8rem;
    height: 2.8rem;
    font-size: 1.05rem;
  }

  .campaign-customer-cell {
    align-items: flex-start;
  }
}
</style>
