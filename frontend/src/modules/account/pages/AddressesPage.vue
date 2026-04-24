
<template>
  <section class="dashboard-header">
    <h1>Mis Direcciones</h1>
    <p>Administra tus direcciones de envío para una experiencia de compra más rápida.</p>
  </section>

  <section v-if="viewMode === 'list'" class="addresses-list-container account-card">
    <header class="addresses-header">
      <h2>
        <i class="fas fa-map-marked-alt" />
        Mis Direcciones Guardadas
      </h2>

      <button type="button" class="btn-primary-small btn-add-address" @click="openCreateForm">
        <i class="fas fa-plus-circle" />
        Agregar Nueva Dirección
      </button>
    </header>

    <p v-if="loading" class="loading-box">Cargando direcciones...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <div v-else-if="addresses.length === 0" class="no-addresses">
      <div class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-map-marker-alt" />
        </div>
        <h3>Aún no tienes direcciones guardadas</h3>
        <p>Agrega tu primera dirección para recibir tus pedidos</p>
        <button type="button" class="btn-primary-small" @click="openCreateForm">
          <i class="fas fa-plus" />
          Agregar mi primera dirección
        </button>
      </div>
    </div>

    <div v-else class="addresses-grid">
      <article
        v-for="address in addresses"
        :key="address.id"
        class="address-card"
        :class="{ 'default-address': address.is_default }"
      >
        <header class="address-header">
          <div class="address-icon">
            <i :class="addressTypeIcon(address.address_type)" />
          </div>

          <div class="address-title">
            <h3>{{ address.alias }}</h3>
            <span class="address-type">{{ labelAddressType(address.address_type) }}</span>
          </div>

          <span v-if="address.is_default" class="default-badge">
            <i class="fas fa-star" />
            Principal
          </span>
        </header>

        <div class="address-details">
          <div class="detail-item">
            <i class="fas fa-user" />
            <p>{{ address.recipient_name }} ({{ address.recipient_phone }})</p>
          </div>

          <div class="detail-item">
            <i class="fas fa-map-marker-alt" />
            <p class="detail-address-text">{{ address.address }}</p>
          </div>

          <div v-if="address.complement" class="detail-item">
            <i class="fas fa-plus-circle" />
            <p>{{ address.complement }}</p>
          </div>

          <div class="detail-item">
            <i class="fas fa-city" />
            <p>{{ address.neighborhood }}</p>
          </div>

          <div class="detail-item">
            <i class="fas fa-building" />
            <p>{{ labelBuilding(address) }}</p>
          </div>

          <div v-if="address.apartment_number" class="detail-item">
            <i class="fas fa-door-open" />
            <p>{{ address.apartment_number }}</p>
          </div>

          <div v-if="address.delivery_instructions" class="detail-item">
            <i class="fas fa-info-circle" />
            <p>{{ address.delivery_instructions }}</p>
          </div>
        </div>

        <footer class="address-actions">
          <button
            v-if="!address.is_default"
            type="button"
            class="btn-outline-small btn-set-default"
            :disabled="savingAddressId === address.id"
            @click="setAsDefault(address)"
          >
            <i class="fas fa-star" />
            Establecer como principal
          </button>

          <button
            type="button"
            class="btn-outline-small"
            :disabled="savingAddressId === address.id"
            @click="openEditForm(address)"
          >
            <i class="fas fa-edit" />
            Editar
          </button>

          <button
            type="button"
            class="btn-outline-small btn-danger-outline"
            :disabled="savingAddressId === address.id"
            @click="confirmDelete(address)"
          >
            <i class="fas fa-trash" />
            Eliminar
          </button>
        </footer>
      </article>
    </div>
  </section>

  <section v-else class="address-form-container account-card">
    <header class="form-header">
      <h2>
        <i :class="isEditMode ? 'fas fa-edit' : 'fas fa-plus-circle'" />
        {{ isEditMode ? 'Editar Dirección' : 'Agregar Nueva Dirección' }}
      </h2>

      <button type="button" class="btn-back" @click="backToList">
        <i class="fas fa-arrow-left" />
        Volver
      </button>
    </header>

    <form @submit.prevent="submitAddress">
      <div class="form-step" :class="{ active: formStep === 1 }">
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">1</span>
            <h3>Identifica tu dirección</h3>
          </div>
          <p>Así podrás reconocerla fácilmente</p>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="alias">
              <i class="fas fa-tag" />
              Nombre descriptivo *
            </label>
            <input
              id="alias"
              v-model.trim="form.alias"
              type="text"
              maxlength="80"
              placeholder="Ej: Casa, Oficina, Mi mamá"
              :class="{ error: !!fieldErrors.alias }"
              @input="validateField('alias')"
            />
            <div v-if="fieldErrors.alias" class="field-error">{{ fieldErrors.alias }}</div>
          </div>

          <div class="form-group">
            <label for="address_type">
              <i class="fas fa-home" />
              Tipo de domicilio *
            </label>
            <select id="address_type" v-model="form.address_type" :class="{ error: !!fieldErrors.address_type }" @change="validateField('address_type')">
              <option value="casa">Casa</option>
              <option value="apartamento">Apartamento</option>
              <option value="oficina">Oficina</option>
              <option value="otro">Otro</option>
            </select>
            <div v-if="fieldErrors.address_type" class="field-error">{{ fieldErrors.address_type }}</div>
          </div>
        </div>

        <div class="step-actions">
          <button type="button" class="btn-primary-small" @click="goToStep(2)">
            Siguiente
            <i class="fas fa-arrow-right" />
          </button>
        </div>
      </div>
      <div class="form-step" :class="{ active: formStep === 2 }">
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">2</span>
            <h3>Información del destinatario</h3>
          </div>
          <p>¿Quién recibirá tus paquetes?</p>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="recipient_name">
              <i class="fas fa-user" />
              Nombre del destinatario *
            </label>
            <input
              id="recipient_name"
              v-model.trim="form.recipient_name"
              type="text"
              maxlength="120"
              placeholder="Nombre completo"
              :class="{ error: !!fieldErrors.recipient_name }"
              @input="validateField('recipient_name')"
            />
            <div v-if="fieldErrors.recipient_name" class="field-error">{{ fieldErrors.recipient_name }}</div>
          </div>

          <div class="form-group">
            <label for="recipient_phone">
              <i class="fas fa-phone" />
              Teléfono del destinatario *
            </label>
            <input
              id="recipient_phone"
              v-model.trim="form.recipient_phone"
              type="text"
              maxlength="25"
              placeholder="Ej: 3001234567"
              :class="{ error: !!fieldErrors.recipient_phone }"
              @input="validateField('recipient_phone')"
            />
            <div v-if="fieldErrors.recipient_phone" class="field-error">{{ fieldErrors.recipient_phone }}</div>
          </div>
        </div>

        <div class="step-actions">
          <button type="button" class="btn-outline-small" @click="goToStep(1)">
            <i class="fas fa-arrow-left" />
            Anterior
          </button>
          <button type="button" class="btn-primary-small" @click="goToStep(3)">
            Siguiente
            <i class="fas fa-arrow-right" />
          </button>
        </div>
      </div>

      <div class="form-step" :class="{ active: formStep === 3 }">
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">3</span>
            <h3>Dirección y detalles de entrega</h3>
          </div>
          <p>Completa los datos de ubicación para entregar correctamente</p>
        </div>

        <div class="form-group full-width">
          <button type="button" class="btn-gps" @click="openGpsModal">
            <i class="fas fa-crosshairs" />
            Seleccionar ubicación con mapa
          </button>
          <p class="form-help">
            Puedes buscar una dirección o mover el marcador para mayor precisión.
          </p>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="address">
              <i class="fas fa-map-marker-alt" />
              Dirección *
            </label>
            <input
              id="address"
              v-model.trim="form.address"
              type="text"
              maxlength="255"
              placeholder="Ej: Calle 63A #10-20"
              :class="{ error: !!fieldErrors.address }"
              @input="validateField('address')"
            />
            <div v-if="fieldErrors.address" class="field-error">{{ fieldErrors.address }}</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="complement">
              <i class="fas fa-plus-circle" />
              Complemento
            </label>
            <input
              id="complement"
              v-model.trim="form.complement"
              type="text"
              maxlength="255"
              placeholder="Ej: Torre 2, portería azul"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="neighborhood">
              <i class="fas fa-city" />
              Barrio / zona *
            </label>
            <input
              id="neighborhood"
              v-model.trim="form.neighborhood"
              type="text"
              maxlength="120"
              placeholder="Ej: Comuna 8 - Villa Hermosa"
              :class="{ error: !!fieldErrors.neighborhood }"
              @input="validateField('neighborhood')"
            />
            <div v-if="fieldErrors.neighborhood" class="field-error">{{ fieldErrors.neighborhood }}</div>
          </div>

          <div class="form-group">
            <label for="building_type">
              <i class="fas fa-building" />
              Tipo de edificación *
            </label>
            <select id="building_type" v-model="form.building_type" :class="{ error: !!fieldErrors.building_type }" @change="validateField('building_type')">
              <option value="casa">Casa</option>
              <option value="apartamento">Apartamento</option>
              <option value="oficina">Oficina</option>
              <option value="otro">Otro</option>
            </select>
            <div v-if="fieldErrors.building_type" class="field-error">{{ fieldErrors.building_type }}</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="building_name">
              <i class="fas fa-building" />
              Edificio / conjunto
            </label>
            <input
              id="building_name"
              v-model.trim="form.building_name"
              type="text"
              maxlength="120"
              placeholder="Ej: Mirador del Faro"
            />
          </div>

          <div class="form-group">
            <label for="apartment_number">
              <i class="fas fa-door-open" />
              Apto / oficina
            </label>
            <input
              id="apartment_number"
              v-model.trim="form.apartment_number"
              type="text"
              maxlength="40"
              placeholder="Ej: 210"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="delivery_instructions">
              <i class="fas fa-info-circle" />
              Indicaciones de entrega
            </label>
            <textarea
              id="delivery_instructions"
              v-model.trim="form.delivery_instructions"
              maxlength="255"
              placeholder="Ej: Llamar antes de llegar"
            />
          </div>
        </div>

        <div v-if="hasGpsCoordinates" class="form-group full-width">
          <p class="form-help">
            Coordenadas GPS: {{ form.gps_latitude }}, {{ form.gps_longitude }}
          </p>
        </div>

        <label class="checkbox-group">
          <input v-model="form.is_default" type="checkbox" />
          Establecer como dirección principal
        </label>

        <div class="step-actions">
          <button type="button" class="btn-outline-small" @click="goToStep(2)">
            <i class="fas fa-arrow-left" />
            Anterior
          </button>
          <button type="submit" class="btn-primary-small" :disabled="isSaving">
            <i class="fas fa-save" />
            {{ isSaving ? 'Guardando...' : isEditMode ? 'Actualizar dirección' : 'Guardar dirección' }}
          </button>
        </div>
      </div>
    </form>
  </section>

  <AddressLocationPickerModal
    v-model="isGpsModalOpen"
    :initial-address="form.address"
    :initial-neighborhood="form.neighborhood"
    :initial-gps-address="form.gps_address"
    :initial-latitude="form.gps_latitude"
    :initial-longitude="form.gps_longitude"
    @confirm="applyGpsSelection"
  />
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import AddressLocationPickerModal from '../components/AddressLocationPickerModal.vue'
import { useSession } from '../../../composables/useSession'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import {
  createUserAddress,
  deleteUserAddress,
  getUserAddresses,
  setDefaultUserAddress,
  updateUserAddress,
} from '../../../services/shippingApi'
import '../views/AddressesView.css'

const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const ADDRESS_SYNC_EVENT = 'angelow:account-addresses-updated'
const ADDRESS_SYNC_STORAGE_KEY = 'angelow:account-addresses-sync'
const ADDRESS_POLL_INTERVAL_MS = 12000

const viewMode = ref('list')
const formStep = ref(1)

const loading = ref(true)
const isSaving = ref(false)
const savingAddressId = ref(null)
const errorMessage = ref('')
const addresses = ref([])

const isEditMode = ref(false)
const editingId = ref(null)

const form = reactive(initialFormState())
const fieldErrors = reactive(initialFieldErrors())

const isGpsModalOpen = ref(false)
const syncingAddresses = ref(false)

let addressRefreshTimer = null

const hasGpsCoordinates = computed(() => isValidCoordinatePair(form.gps_latitude, form.gps_longitude))

onMounted(async () => {
  await loadAddresses()
  startAddressRealtimeRefresh()
})

onUnmounted(() => {
  stopAddressRealtimeRefresh()
})

async function loadAddresses(options = {}) {
  const { silent = false } = options

  if (!silent) {
    loading.value = true
  }

  errorMessage.value = ''

  try {
    if (!isLoggedIn.value) {
      addresses.value = []
      return
    }

    const response = await getUserAddresses(currentUserId(), currentUserEmail())
    const rawItems = Array.isArray(response?.data) ? response.data : []

    addresses.value = rawItems
      .map(normalizeAddress)
      .sort((a, b) => Number(b.is_default) - Number(a.is_default))
  } catch {
    errorMessage.value = 'No se pudieron cargar tus direcciones.'
  } finally {
    if (!silent) {
      loading.value = false
    }
  }
}

function startAddressRealtimeRefresh() {
  window.addEventListener('focus', handleWindowFocusRefresh)
  document.addEventListener('visibilitychange', handleVisibilityRefresh)
  window.addEventListener('storage', handleAddressStorageEvent)
  window.addEventListener(ADDRESS_SYNC_EVENT, handleAddressSyncEvent)

  addressRefreshTimer = window.setInterval(() => {
    refreshAddressesInBackground()
  }, ADDRESS_POLL_INTERVAL_MS)
}

function stopAddressRealtimeRefresh() {
  window.removeEventListener('focus', handleWindowFocusRefresh)
  document.removeEventListener('visibilitychange', handleVisibilityRefresh)
  window.removeEventListener('storage', handleAddressStorageEvent)
  window.removeEventListener(ADDRESS_SYNC_EVENT, handleAddressSyncEvent)

  if (addressRefreshTimer !== null) {
    window.clearInterval(addressRefreshTimer)
    addressRefreshTimer = null
  }
}

function handleWindowFocusRefresh() {
  refreshAddressesInBackground()
}

function handleVisibilityRefresh() {
  if (document.visibilityState === 'visible') {
    refreshAddressesInBackground()
  }
}

function handleAddressStorageEvent(event) {
  if (event?.key !== ADDRESS_SYNC_STORAGE_KEY) return
  refreshAddressesInBackground()
}

function handleAddressSyncEvent() {
  refreshAddressesInBackground()
}

async function refreshAddressesInBackground() {
  if (viewMode.value !== 'list') return
  if (!isLoggedIn.value) return
  if (syncingAddresses.value || isSaving.value || Boolean(savingAddressId.value)) return

  syncingAddresses.value = true
  try {
    await loadAddresses({ silent: true })
  } finally {
    syncingAddresses.value = false
  }
}

function emitAddressSyncSignal() {
  try {
    localStorage.setItem(ADDRESS_SYNC_STORAGE_KEY, String(Date.now()))
  } catch {
    // Sincronización best-effort para navegadores con storage restringido.
  }

  window.dispatchEvent(new CustomEvent(ADDRESS_SYNC_EVENT))
}

function initialFormState() {
  return {
    alias: '',
    address_type: 'casa',
    recipient_name: '',
    recipient_phone: '',
    address: '',
    complement: '',
    neighborhood: '',
    building_type: 'casa',
    building_name: '',
    apartment_number: '',
    delivery_instructions: '',
    is_default: false,
    gps_latitude: null,
    gps_longitude: null,
    gps_accuracy: null,
    gps_address: '',
  }
}

function resetForm() {
  Object.assign(form, initialFormState())
  resetFieldErrors()
  formStep.value = 1
}

function initialFieldErrors() {
  return {
    alias: '',
    address_type: '',
    recipient_name: '',
    recipient_phone: '',
    address: '',
    neighborhood: '',
    building_type: '',
  }
}

function resetFieldErrors() {
  Object.assign(fieldErrors, initialFieldErrors())
}

function openCreateForm() {
  isEditMode.value = false
  editingId.value = null
  resetForm()
  viewMode.value = 'form'
}

function openEditForm(address) {
  isEditMode.value = true
  editingId.value = address.id

  Object.assign(form, {
    alias: address.alias,
    address_type: address.address_type,
    recipient_name: address.recipient_name,
    recipient_phone: address.recipient_phone,
    address: address.address,
    complement: address.complement || '',
    neighborhood: address.neighborhood,
    building_type: address.building_type || 'casa',
    building_name: address.building_name || '',
    apartment_number: address.apartment_number || '',
    delivery_instructions: address.delivery_instructions || '',
    is_default: Boolean(address.is_default),
    gps_latitude: toNullableNumber(address.gps_latitude),
    gps_longitude: toNullableNumber(address.gps_longitude),
    gps_accuracy: toNullableNumber(address.gps_accuracy),
    gps_address: String(address.gps_address || address.address || ''),
  })

  formStep.value = 1
  viewMode.value = 'form'
}

function backToList() {
  if (isSaving.value) return

  viewMode.value = 'list'
  isEditMode.value = false
  editingId.value = null
  resetForm()
}

function goToStep(step) {
  if (step < formStep.value) {
    formStep.value = step
    return
  }

  if (!validateStep(formStep.value)) return
  formStep.value = step
}

function validateStep(step) {
  if (step === 1) {
    const isAliasValid = validateField('alias')
    const isAddressTypeValid = validateField('address_type')
    if (!isAliasValid) {
      showStepWarning('Debes ingresar un nombre descriptivo para la dirección.')
      return false
    }
    if (!isAddressTypeValid) {
      showStepWarning('Selecciona el tipo de domicilio.')
      return false
    }
    return true
  }

  if (step === 2) {
    const isRecipientNameValid = validateField('recipient_name')
    const isPhoneValid = validateField('recipient_phone')
    if (!isRecipientNameValid) {
      showStepWarning('Debes ingresar el nombre del destinatario.')
      return false
    }

    if (!isPhoneValid) {
      showStepWarning('El teléfono debe contener solo números (7 a 15 dígitos).')
      return false
    }

    return true
  }

  if (!validateField('address')) {
    showStepWarning('Debes ingresar la dirección de entrega.')
    return false
  }

  if (!validateField('neighborhood')) {
    showStepWarning('Debes ingresar el barrio o zona.')
    return false
  }

  if (!validateField('building_type')) {
    showStepWarning('Selecciona el tipo de edificación.')
    return false
  }

  return true
}

function showStepWarning(message) {
  showSnackbar({
    type: 'warning',
    title: 'Completa los datos requeridos',
    message,
  })
}

async function submitAddress() {
  if (isSaving.value) return
  if (!validateStep(3)) return

  isSaving.value = true

  try {
    const payload = buildPayloadFromForm()

    if (isEditMode.value && editingId.value !== null) {
      await updateUserAddress(editingId.value, payload, currentUserId(), currentUserEmail())
    } else {
      await createUserAddress(payload, currentUserId(), currentUserEmail())
    }

    await loadAddresses()
    emitAddressSyncSignal()
    backToList()

    showSnackbar({
      type: 'success',
      title: 'Dirección guardada',
      message: isEditMode.value
        ? 'La dirección fue actualizada correctamente.'
        : 'La dirección fue creada correctamente.',
      durationMs: 3000,
    })
  } catch (error) {
    showSnackbar({
      type: 'error',
      title: 'No se pudo guardar',
      message: extractApiMessage(error, 'Valida los datos e intenta nuevamente.'),
    })
  } finally {
    isSaving.value = false
  }
}

async function setAsDefault(address) {
  if (savingAddressId.value) return

  savingAddressId.value = address.id

  try {
    await setDefaultUserAddress(address.id, currentUserId(), currentUserEmail())
    await loadAddresses()
    emitAddressSyncSignal()

    showSnackbar({
      type: 'success',
      title: 'Direccion principal actualizada',
      message: `Ahora ${address.alias} es tu direccion principal.`,
    })
  } catch (error) {
    showSnackbar({
      type: 'error',
      title: 'No se pudo actualizar',
      message: extractApiMessage(error, 'No pudimos establecer la dirección principal.'),
    })
  } finally {
    savingAddressId.value = null
  }
}

function confirmDelete(address) {
  showAlert({
    type: 'question',
    title: 'Eliminar dirección',
    message: `¿Deseas eliminar la dirección "${address.alias}"?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          await removeAddress(address)
        },
      },
    ],
  })
}

async function removeAddress(address) {
  if (savingAddressId.value) return

  savingAddressId.value = address.id

  try {
    await deleteUserAddress(address.id, currentUserId(), currentUserEmail())
    await loadAddresses()
    emitAddressSyncSignal()

    showSnackbar({
      type: 'success',
      title: 'Direccion eliminada',
      message: `Se elimino correctamente la direccion ${address.alias}.`,
    })
  } catch (error) {
    showSnackbar({
      type: 'error',
      title: 'No se pudo eliminar',
      message: extractApiMessage(error, 'No pudimos eliminar la dirección.'),
    })
  } finally {
    savingAddressId.value = null
  }
}

function buildPayloadFromForm() {
  return {
    alias: form.alias.trim(),
    address_type: form.address_type,
    recipient_name: form.recipient_name.trim(),
    recipient_phone: form.recipient_phone.trim(),
    address: form.address.trim(),
    complement: form.complement.trim(),
    neighborhood: form.neighborhood.trim(),
    building_type: form.building_type,
    building_name: form.building_name.trim(),
    apartment_number: form.apartment_number.trim(),
    delivery_instructions: form.delivery_instructions.trim(),
    is_default: Boolean(form.is_default),
    gps_latitude: toNullableNumber(form.gps_latitude),
    gps_longitude: toNullableNumber(form.gps_longitude),
    gps_accuracy: toNullableNumber(form.gps_accuracy),
  }
}

function normalizeAddress(item) {
  const addressType = normalizeText(item?.address_type || 'casa').toLowerCase()

  return {
    id: Number(item?.id || 0),
    alias: normalizeText(item?.alias || item?.address_type || 'Dirección'),
    address_type: addressType || 'casa',
    recipient_name: normalizeText(item?.recipient_name || 'Sin destinatario'),
    recipient_phone: normalizeText(item?.recipient_phone || item?.phone || 'Sin teléfono'),
    address: normalizeText(item?.address || item?.address_line_1 || 'Sin dirección'),
    complement: normalizeText(item?.complement || item?.address_line_2 || ''),
    neighborhood: normalizeText(item?.neighborhood || item?.city || 'Sin zona'),
    building_type: normalizeText(item?.building_type || addressType || 'casa').toLowerCase(),
    building_name: normalizeText(item?.building_name || ''),
    apartment_number: normalizeText(item?.apartment_number || ''),
    delivery_instructions: normalizeText(item?.delivery_instructions || item?.notes || ''),
    is_default: Boolean(item?.is_default),
    gps_latitude: toNullableNumber(item?.gps_latitude),
    gps_longitude: toNullableNumber(item?.gps_longitude),
    gps_accuracy: toNullableNumber(item?.gps_accuracy),
    gps_address: normalizeText(item?.gps_address || item?.address || item?.address_line_1 || ''),
  }
}

function labelAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'Apartamento'
  if (value === 'oficina') return 'Oficina'
  if (value === 'otro') return 'Otro'
  return 'Casa'
}

function addressTypeIcon(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'fas fa-building'
  if (value === 'oficina') return 'fas fa-briefcase'
  if (value === 'otro') return 'fas fa-map-marker-alt'
  return 'fas fa-home'
}

function labelBuilding(address) {
  const buildingType = labelAddressType(address.building_type)
  if (address.building_name) {
    return `${buildingType} (${address.building_name})`
  }

  return buildingType
}

function openGpsModal() {
  isGpsModalOpen.value = true
}

function applyGpsSelection(payload) {
  form.gps_latitude = toNullableNumber(payload?.gps_latitude)
  form.gps_longitude = toNullableNumber(payload?.gps_longitude)
  form.gps_accuracy = toNullableNumber(payload?.gps_accuracy)
  form.gps_address = normalizeText(payload?.gps_address || payload?.suggested_address || '')

  const suggestedAddress = normalizeText(payload?.suggested_address || '')
  if (suggestedAddress) {
    form.address = suggestedAddress
    validateField('address')
  }

  const suggestedNeighborhood = normalizeText(payload?.suggested_neighborhood || '')
  if (suggestedNeighborhood && !form.neighborhood.trim()) {
    form.neighborhood = suggestedNeighborhood
    validateField('neighborhood')
  }

  showSnackbar({
    type: 'success',
    title: 'Ubicacion confirmada',
    message: 'La direccion GPS fue aplicada al formulario.',
  })
}

function extractApiMessage(error, fallbackMessage) {
  const message = String(
    error?.response?.data?.message
      || error?.response?.data?.error
      || fallbackMessage,
  ).trim()

  return message || fallbackMessage
}

function normalizeText(value) {
  return String(value || '').trim()
}

function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}

function toNullableNumber(value) {
  if (value === null || value === undefined) {
    return null
  }

  const normalized = String(value).trim()
  if (!normalized) {
    return null
  }

  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

function isValidCoordinatePair(latitude, longitude) {
  const lat = toNullableNumber(latitude)
  const lng = toNullableNumber(longitude)

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return false
  }

  const isInRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  const isZeroed = Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001
  return isInRange && !isZeroed
}

function validateField(fieldName) {
  if (fieldName === 'alias') {
    fieldErrors.alias = form.alias.trim() ? '' : 'Debes ingresar un nombre descriptivo.'
    return !fieldErrors.alias
  }

  if (fieldName === 'address_type') {
    fieldErrors.address_type = form.address_type.trim() ? '' : 'Selecciona el tipo de domicilio.'
    return !fieldErrors.address_type
  }

  if (fieldName === 'recipient_name') {
    fieldErrors.recipient_name = form.recipient_name.trim() ? '' : 'Debes ingresar el nombre del destinatario.'
    return !fieldErrors.recipient_name
  }

  if (fieldName === 'recipient_phone') {
    fieldErrors.recipient_phone = /^\d{7,15}$/.test(form.recipient_phone.trim())
      ? ''
      : 'El teléfono debe contener solo números (7 a 15 dígitos).'
    return !fieldErrors.recipient_phone
  }

  if (fieldName === 'address') {
    fieldErrors.address = form.address.trim() ? '' : 'Debes ingresar la dirección de entrega.'
    return !fieldErrors.address
  }

  if (fieldName === 'neighborhood') {
    fieldErrors.neighborhood = form.neighborhood.trim() ? '' : 'Debes ingresar el barrio o zona.'
    return !fieldErrors.neighborhood
  }

  if (fieldName === 'building_type') {
    fieldErrors.building_type = form.building_type.trim() ? '' : 'Selecciona el tipo de edificación.'
    return !fieldErrors.building_type
  }

  return true
}
</script>
