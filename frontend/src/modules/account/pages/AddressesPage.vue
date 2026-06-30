
<!-- Página de gestión de direcciones de envío del usuario. Permite listar, crear, editar, eliminar y establecer direcciones principales. -->
<template>
  <!-- Muestra un shimmer de carga mientras se obtienen las direcciones en modo lista -->
  <AccountShimmer v-if="loading && viewMode === 'list'" variant="addresses" />

  <!-- Contenido principal cuando ya no está cargando -->
  <template v-else>
    <!-- Encabezado del dashboard con título y descripción de la página -->
    <section class="dashboard-header">
      <h1>Mis Direcciones</h1>
      <p>Administra tus direcciones de envío para una experiencia de compra más rápida.</p>
    </section>

    <!-- Sección de lista de direcciones: solo se muestra cuando viewMode es 'list' -->
    <section v-if="viewMode === 'list'" class="addresses-list-container account-card">
      <!-- Encabezado con título y botón para crear nueva dirección -->
      <header class="addresses-header">
        <h2>
          <i class="fas fa-map-marked-alt" />
          Mis Direcciones Guardadas
        </h2>

        <!-- Botón que abre el formulario para crear una nueva dirección -->
        <button type="button" class="btn-primary-small btn-add-address" @click="openCreateForm">
          <i class="fas fa-plus-circle" />
          Agregar Nueva Dirección
        </button>
      </header>

      <!-- Mensaje de error si falla la carga de direcciones -->
      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <!-- Estado vacío: muestra un mensaje y botón cuando no hay direcciones registradas -->
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

      <!-- Grid de tarjetas de direcciones cuando existen direcciones guardadas -->
      <div v-else class="addresses-grid">
        <!-- Itera sobre cada dirección y renderiza una tarjeta con sus detalles -->
        <article
          v-for="address in addresses"
          :key="address.id"
          class="address-card"
          :class="{ 'default-address': address.is_default }"
        >
          <!-- Encabezado de la tarjeta: ícono según tipo, alias y badge si es dirección principal -->
          <header class="address-header">
            <div class="address-icon">
              <!-- Determina el ícono CSS según el tipo de dirección (casa, apartamento, etc.) -->
              <i :class="addressTypeIcon(address.address_type)" />
            </div>

            <div class="address-title">
              <h3>{{ address.alias }}</h3>
              <!-- Muestra la etiqueta legible del tipo de dirección -->
              <span class="address-type">{{ labelAddressType(address.address_type) }}</span>
            </div>

            <!-- Badge de estrella que indica si esta dirección es la predeterminada -->
            <span v-if="address.is_default" class="default-badge">
              <i class="fas fa-star" />
              Principal
            </span>
          </header>

          <!-- Detalles de la dirección: destinatario, dirección, barrio, etc. -->
          <div class="address-details">
            <div class="detail-item">
              <i class="fas fa-user" />
              <p>{{ address.recipient_name }} ({{ address.recipient_phone }})</p>
            </div>

            <div class="detail-item">
              <i class="fas fa-map-marker-alt" />
              <p class="detail-address-text">{{ address.address }}</p>
            </div>

            <!-- Complemento: solo se muestra si existe (ej: torre, portería) -->
            <div v-if="address.complement" class="detail-item">
              <i class="fas fa-plus-circle" />
              <p>{{ address.complement }}</p>
            </div>

            <div class="detail-item">
              <i class="fas fa-city" />
              <p>{{ address.neighborhood }}</p>
            </div>

            <!-- Tipo de edificación con nombre del edificio si está disponible -->
            <div class="detail-item">
              <i class="fas fa-building" />
              <p>{{ labelBuilding(address) }}</p>
            </div>

            <!-- Número de apartamento: solo se muestra si está definido -->
            <div v-if="address.apartment_number" class="detail-item">
              <i class="fas fa-door-open" />
              <p>{{ address.apartment_number }}</p>
            </div>

            <!-- Instrucciones de entrega: solo se muestra si existen indicaciones especiales -->
            <div v-if="address.delivery_instructions" class="detail-item">
              <i class="fas fa-info-circle" />
              <p>{{ address.delivery_instructions }}</p>
            </div>
          </div>

          <!-- Acciones de la tarjeta: establecer como principal, editar, eliminar -->
          <footer class="address-actions">
            <!-- Botón para marcar como dirección principal (solo si no lo es ya) -->
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

            <!-- Botón para editar la dirección actual -->
            <button
              type="button"
              class="btn-outline-small"
              :disabled="savingAddressId === address.id"
              @click="openEditForm(address)"
            >
              <i class="fas fa-edit" />
              Editar
            </button>

            <!-- Botón para eliminar la dirección (abre confirmación) -->
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

    <!-- Sección del formulario: se muestra cuando viewMode es 'form' -->
    <section v-else class="address-form-container account-card">
    <!-- Encabezado del formulario: cambia título e ícono según modo (crear/editar) -->
    <header class="form-header">
      <h2>
        <!-- Ícono dinámico: editar si es modo edición, crear si es nuevo -->
        <i :class="isEditMode ? 'fas fa-edit' : 'fas fa-plus-circle'" />
        {{ isEditMode ? 'Editar Dirección' : 'Agregar Nueva Dirección' }}
      </h2>

      <!-- Botón para volver a la lista de direcciones -->
      <button type="button" class="btn-back" @click="backToList">
        <i class="fas fa-arrow-left" />
        Volver
      </button>
    </header>

    <!-- Formulario de 3 pasos para crear/editar una dirección -->
    <form @submit.prevent="submitAddress">
      <!-- Paso 1: Identificación de la dirección (alias y tipo) -->
      <div class="form-step" :class="{ active: formStep === 1 }">
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">1</span>
            <h3>Identifica tu dirección</h3>
          </div>
          <p>Así podrás reconocerla fácilmente</p>
        </div>

        <div class="form-row">
          <!-- Campo alias: nombre descriptivo de la dirección -->
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

          <!-- Campo tipo de dirección: selección del tipo de domicilio -->
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

        <!-- Botón para avanzar al paso 2 -->
        <div class="step-actions">
          <button type="button" class="btn-primary-small" @click="goToStep(2)">
            Siguiente
            <i class="fas fa-arrow-right" />
          </button>
        </div>
      </div>

      <!-- Paso 2: Información del destinatario (nombre y teléfono) -->
      <div class="form-step" :class="{ active: formStep === 2 }">
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">2</span>
            <h3>Información del destinatario</h3>
          </div>
          <p>¿Quién recibirá tus paquetes?</p>
        </div>

        <div class="form-row">
          <!-- Campo nombre del destinatario -->
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

          <!-- Campo teléfono del destinatario: validado con regex (7-15 dígitos) -->
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

        <!-- Botones de navegación entre pasos -->
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

      <!-- Paso 3: Dirección física, barrio, tipo de edificación y detalles de entrega -->
      <div class="form-step" :class="{ active: formStep === 3 }">
        <!-- Encabezado del paso 3 -->
        <div class="step-header">
          <div class="step-title-line">
            <span class="step-current-circle">3</span>
            <h3>Dirección y detalles de entrega</h3>
          </div>
          <p>Completa los datos de ubicación para entregar correctamente</p>
        </div>

        <!-- Botón para abrir el modal de selección GPS en mapa -->
        <div class="form-group full-width">
          <button type="button" class="btn-gps" @click="openGpsModal">
            <i class="fas fa-crosshairs" />
            Seleccionar ubicación con mapa
          </button>
          <p class="form-help">
            Puedes buscar una dirección o mover el marcador para mayor precisión.
          </p>
        </div>

        <!-- Campo dirección física (obligatorio) -->
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

        <!-- Campo complemento: información adicional (torre, portería, etc.) -->
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

        <!-- Campos de barrio/zona y tipo de edificación -->
        <div class="form-row">
          <!-- Campo barrio o zona (obligatorio) -->
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

          <!-- Campo tipo de edificación (obligatorio) -->
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

        <!-- Campos de edificio/constructora y número de apartamento -->
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

        <!-- Campo de indicaciones especiales de entrega -->
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

        <!-- Muestra coordenadas GPS solo si son válidas -->
        <div v-if="hasGpsCoordinates" class="form-group full-width">
          <p class="form-help">
            Coordenadas GPS: {{ form.gps_latitude }}, {{ form.gps_longitude }}
          </p>
        </div>

        <!-- Checkbox para marcar como dirección principal -->
        <label class="checkbox-group">
          <input v-model="form.is_default" type="checkbox" />
          Establecer como dirección principal
        </label>

        <!-- Botones de navegación del paso 3: volver o enviar formulario -->
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

    <!-- Modal para selección de ubicación GPS con mapa interactivo -->
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
</template>

<script setup>
// Imports de Vue: herramientas reactivas y ciclo de vida
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'

// Componentes hijos: shimmer de carga y modal de selección GPS
import AccountShimmer from '../components/AccountShimmer.vue'
import AddressLocationPickerModal from '../components/AddressLocationPickerModal.vue'

// Composables personalizados para sesión, alertas y notificaciones
import { useSession } from '../../../composables/useSession'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

// Funciones de la API de envíos para CRUD de direcciones de usuario
import {
  createUserAddress,
  deleteUserAddress,
  getUserAddresses,
  setDefaultUserAddress,
  updateUserAddress,
} from '../../../services/shippingApi'

// Estilos CSS de la vista de direcciones
import '../views/AddressesView.css'

// Composables de sesión y notificaciones
const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

// Constantes para sincronización en tiempo real entre pestañas/navegadores
// Evento personalizado para notificar cambios de direcciones entre ventanas
const ADDRESS_SYNC_EVENT = 'angelow:account-addresses-updated'
// Clave de localStorage para detectar cambios en otras pestañas
const ADDRESS_SYNC_STORAGE_KEY = 'angelow:account-addresses-sync'
// Intervalo de polling en milisegundos para refresco automático de direcciones
const ADDRESS_POLL_INTERVAL_MS = 12000

// Estado de la interfaz: 'list' muestra la lista, 'form' muestra el formulario
const viewMode = ref('list')
// Paso actual del formulario multi-paso (1, 2 o 3)
const formStep = ref(1)

// Estado de carga inicial y de guardado
const loading = ref(true)
const isSaving = ref(false)
// ID de la dirección que se está guardando actualmente (para deshabilitar botones)
const savingAddressId = ref(null)
const errorMessage = ref('')
// Array de direcciones del usuario obtenidas de la API
const addresses = ref([])

// Modo de edición: true = editar, false = crear nueva dirección
const isEditMode = ref(false)
// ID de la dirección que se está editando actualmente
const editingId = ref(null)

// Objeto reactivo que contiene todos los campos del formulario
const form = reactive(initialFormState())
// Objeto reactivo que contiene los mensajes de error de validación por campo
const fieldErrors = reactive(initialFieldErrors())

// Estado del modal de selección GPS
const isGpsModalOpen = ref(false)
// Bandera para evitar solicitudes concurrentes de sincronización
const syncingAddresses = ref(false)

// Temporizador para el polling de refresco automático de direcciones
let addressRefreshTimer = null

// Computed que verifica si las coordenadas GPS son válidas (lat/lng en rango y no cero)
const hasGpsCoordinates = computed(() => isValidCoordinatePair(form.gps_latitude, form.gps_longitude))

// Al montar el componente: carga las direcciones y activa el refresco en tiempo real
onMounted(async () => {
  await loadAddresses()
  startAddressRealtimeRefresh()
})

// Al desmontar el componente: detiene el refresco y limpia event listeners
onUnmounted(() => {
  stopAddressRealtimeRefresh()
})

// Carga las direcciones del usuario desde la API
// options.silent = true evita mostrar el shimmer de carga (usado en refrescos en segundo plano)
async function loadAddresses(options = {}) {
  const { silent = false } = options

  // Solo muestra el shimmer de carga en la carga inicial, no en refrescos silenciosos
  if (!silent) {
    loading.value = true
  }

  errorMessage.value = ''

  try {
    // Si el usuario no está autenticado, limpia el array y retorna
    if (!isLoggedIn.value) {
      addresses.value = []
      return
    }

    // Obtiene las direcciones de la API usando ID y email del usuario actual
    const response = await getUserAddresses(currentUserId(), currentUserEmail())
    // Valida que la respuesta tenga un array de datos válido
    const rawItems = Array.isArray(response?.data) ? response.data : []

    // Normaliza cada dirección y ordena: las principales primero
    addresses.value = rawItems
      .map(normalizeAddress)
      .sort((a, b) => Number(b.is_default) - Number(a.is_default))
  } catch {
    // Muestra error genérico si falla la carga
    errorMessage.value = 'No se pudieron cargar tus direcciones.'
  } finally {
    // Oculta el shimmer solo si no es carga silenciosa
    if (!silent) {
      loading.value = false
    }
  }
}

// Inicia el sistema de refresco en tiempo real de direcciones
// Registra event listeners para detectar cuando la ventana vuelve a estar visible
// y activa un polling periódico para mantener las direcciones actualizadas
function startAddressRealtimeRefresh() {
  // Refresca cuando la ventana recibe foco
  window.addEventListener('focus', handleWindowFocusRefresh)
  // Refresca cuando la pestaña cambia de estado de visibilidad
  document.addEventListener('visibilitychange', handleVisibilityRefresh)
  // Refresca cuando localStorage cambia en otra pestaña (sincronización cross-tab)
  window.addEventListener('storage', handleAddressStorageEvent)
  // Refresca cuando se recibe un evento de sincronización personalizado
  window.addEventListener(ADDRESS_SYNC_EVENT, handleAddressSyncEvent)

  // Inicia polling periódico para refrescar direcciones automáticamente
  addressRefreshTimer = window.setInterval(() => {
    refreshAddressesInBackground()
  }, ADDRESS_POLL_INTERVAL_MS)
}

// Detiene el sistema de refresco en tiempo real
// Remueve todos los event listeners y limpia el temporizador de polling
function stopAddressRealtimeRefresh() {
  window.removeEventListener('focus', handleWindowFocusRefresh)
  document.removeEventListener('visibilitychange', handleVisibilityRefresh)
  window.removeEventListener('storage', handleAddressStorageEvent)
  window.removeEventListener(ADDRESS_SYNC_EVENT, handleAddressSyncEvent)

  // Limpia el intervalo de polling si está activo
  if (addressRefreshTimer !== null) {
    window.clearInterval(addressRefreshTimer)
    addressRefreshTimer = null
  }
}

// Manejador: refresca direcciones cuando la ventana recibe foco del sistema
function handleWindowFocusRefresh() {
  refreshAddressesInBackground()
}

// Manejador: refresca direcciones cuando la pestaña se hace visible
function handleVisibilityRefresh() {
  if (document.visibilityState === 'visible') {
    refreshAddressesInBackground()
  }
}

// Manejador: refresca direcciones cuando localStorage cambia en otra pestaña
// Solo reacciona al evento de sincronización de direcciones específico
function handleAddressStorageEvent(event) {
  if (event?.key !== ADDRESS_SYNC_STORAGE_KEY) return
  refreshAddressesInBackground()
}

// Manejador: refresca direcciones al recibir evento de sincronización personalizado
function handleAddressSyncEvent() {
  refreshAddressesInBackground()
}

// Refresca las direcciones en segundo plano sin mostrar spinner de carga
// Previene solicitudes concurrentes verificando banderas de estado
async function refreshAddressesInBackground() {
  // Solo refresca en modo lista
  if (viewMode.value !== 'list') return
  // No refresca si el usuario no está autenticado
  if (!isLoggedIn.value) return
  // No refresca si ya hay una sincronización o guardado en curso
  if (syncingAddresses.value || isSaving.value || Boolean(savingAddressId.value)) return

  syncingAddresses.value = true
  try {
    // Carga direcciones en modo silencioso (sin shimmer)
    await loadAddresses({ silent: true })
  } finally {
    syncingAddresses.value = false
  }
}

// Emite una señal de sincronización a otras pestañas/navegadores
// Usa localStorage y un evento custom para notificar cambios
function emitAddressSyncSignal() {
  try {
    // Escribe marca de tiempo en localStorage para triggerar event 'storage' en otras pestañas
    localStorage.setItem(ADDRESS_SYNC_STORAGE_KEY, String(Date.now()))
  } catch {
    // Sincronización best-effort para navegadores con storage restringido.
  }

  // Despacha evento custom para notificar a otras instancias del mismo componente
  window.dispatchEvent(new CustomEvent(ADDRESS_SYNC_EVENT))
}

// Retorna el estado inicial del formulario con valores por defecto
// Usado al crear una nueva dirección o al resetear el formulario
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

// Resetea el formulario a su estado inicial y limpia errores de validación
function resetForm() {
  Object.assign(form, initialFormState())
  resetFieldErrors()
  formStep.value = 1
}

// Retorna el objeto de errores de campo inicial (todos vacíos)
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

// Limpia todos los errores de validación de campos
function resetFieldErrors() {
  Object.assign(fieldErrors, initialFieldErrors())
}

// Abre el formulario en modo creación de nueva dirección
// Resetea el estado de edición y muestra el formulario
function openCreateForm() {
  isEditMode.value = false
  editingId.value = null
  resetForm()
  viewMode.value = 'form'
}

// Abre el formulario en modo edición con los datos de una dirección existente
// Carga todos los campos del formulario con los valores de la dirección seleccionada
function openEditForm(address) {
  isEditMode.value = true
  editingId.value = address.id

  // Carga los datos de la dirección en el formulario reactivo
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

// Vuelve a la lista de direcciones desde el formulario
// No permite volver si hay un guardado en curso
function backToList() {
  if (isSaving.value) return

  viewMode.value = 'list'
  isEditMode.value = false
  editingId.value = null
  resetForm()
}

// Navega entre pasos del formulario
// Si retrocede, cambia directamente; si avanza, valida el paso actual primero
function goToStep(step) {
  // Permitir retroceder sin validación
  if (step < formStep.value) {
    formStep.value = step
    return
  }

  // Solo permite avanzar si el paso actual es válido
  if (!validateStep(formStep.value)) return
  formStep.value = step
}

// Valida los campos requeridos de cada paso del formulario
// Retorna true si todos los campos del paso son válidos, false si hay errores
function validateStep(step) {
  // Paso 1: validar alias y tipo de dirección
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

  // Paso 2: validar nombre y teléfono del destinatario
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

  // Paso 3: validar dirección, barrio y tipo de edificación
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

// Muestra una advertencia de validación de paso usando el sistema de snackbar
function showStepWarning(message) {
  showSnackbar({
    type: 'warning',
    title: 'Completa los datos requeridos',
    message,
  })
}

// Envía el formulario de dirección (crear o actualizar)
// Valida el paso 3, construye el payload y realiza la llamada a la API
async function submitAddress() {
  // Evita envíos múltiples mientras se guarda
  if (isSaving.value) return
  // Valida todos los campos del paso 3 antes de enviar
  if (!validateStep(3)) return

  isSaving.value = true

  try {
    // Construye el objeto con los datos del formulario
    const payload = buildPayloadFromForm()

    // Decide si crear o actualizar según el modo de edición
    if (isEditMode.value && editingId.value !== null) {
      await updateUserAddress(editingId.value, payload, currentUserId(), currentUserEmail())
    } else {
      await createUserAddress(payload, currentUserId(), currentUserEmail())
    }

    // Recarga las direcciones y notifica a otras pestañas
    await loadAddresses()
    emitAddressSyncSignal()
    // Vuelve a la lista de direcciones
    backToList()

    // Muestra confirmación de éxito
    showSnackbar({
      type: 'success',
      title: 'Dirección guardada',
      message: isEditMode.value
        ? 'La dirección fue actualizada correctamente.'
        : 'La dirección fue creada correctamente.',
      durationMs: 3000,
    })
  } catch (error) {
    // Muestra error con mensaje descriptivo de la API
    showSnackbar({
      type: 'error',
      title: 'No se pudo guardar',
      message: extractApiMessage(error, 'Valida los datos e intenta nuevamente.'),
    })
  } finally {
    isSaving.value = false
  }
}

// Establece una dirección como principal (predeterminada)
// Deshabilita el botón mientras se procesa la solicitud
async function setAsDefault(address) {
  // Evita acciones múltiples si ya hay una operación en curso
  if (savingAddressId.value) return

  // Marca esta dirección como en proceso de guardado
  savingAddressId.value = address.id

  try {
    // Llama a la API para cambiar la dirección principal
    await setDefaultUserAddress(address.id, currentUserId(), currentUserEmail())
    // Recarga la lista y notifica el cambio
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

// Muestra un diálogo de confirmación antes de eliminar una dirección
// Usa el sistema de alertas para pedir confirmación al usuario
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
        // Callback asíncrono que se ejecuta solo si el usuario confirma
        callback: async () => {
          await removeAddress(address)
        },
      },
    ],
  })
}

// Elimina una dirección de forma permanente
// Se llama solo después de la confirmación del usuario
async function removeAddress(address) {
  // Evita eliminaciones múltiples
  if (savingAddressId.value) return

  savingAddressId.value = address.id

  try {
    // Llama a la API para eliminar la dirección
    await deleteUserAddress(address.id, currentUserId(), currentUserEmail())
    // Recarga la lista y notifica el cambio
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

// Construye el payload (objeto) para enviar a la API a partir de los datos del formulario
// Limpia espacios en blanco de todos los campos de texto
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

// Normaliza un objeto de dirección crudo de la API a un formato consistente
// Maneja nombres de campo alternativos (ej: address_line_1 vs address)
// Asigna valores por defecto para campos faltantes
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

// Convierte el tipo de dirección interno a una etiqueta legible para el usuario
function labelAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'Apartamento'
  if (value === 'oficina') return 'Oficina'
  if (value === 'otro') return 'Otro'
  return 'Casa'
}

// Retorna la clase CSS del ícono según el tipo de dirección
// Usado para mostrar íconos visuales en las tarjetas de dirección
function addressTypeIcon(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'fas fa-building'
  if (value === 'oficina') return 'fas fa-briefcase'
  if (value === 'otro') return 'fas fa-map-marker-alt'
  return 'fas fa-home'
}

// Genera una etiqueta descriptiva del tipo de edificación con nombre si está disponible
// Ejemplo: "Apartamento (Mirador del Faro)" o solo "Apartamento"
function labelBuilding(address) {
  const buildingType = labelAddressType(address.building_type)
  if (address.building_name) {
    return `${buildingType} (${address.building_name})`
  }

  return buildingType
}

// Abre el modal de selección de ubicación GPS
function openGpsModal() {
  isGpsModalOpen.value = true
}

// Aplica la selección GPS del modal al formulario
// Actualiza coordenadas, dirección sugerida y barrio (solo si el campo está vacío)
function applyGpsSelection(payload) {
  // Actualiza las coordenadas GPS en el formulario
  form.gps_latitude = toNullableNumber(payload?.gps_latitude)
  form.gps_longitude = toNullableNumber(payload?.gps_longitude)
  form.gps_accuracy = toNullableNumber(payload?.gps_accuracy)
  form.gps_address = normalizeText(payload?.gps_address || payload?.suggested_address || '')

  // Auto-completa el campo dirección si se sugiere una nueva
  const suggestedAddress = normalizeText(payload?.suggested_address || '')
  if (suggestedAddress) {
    form.address = suggestedAddress
    validateField('address')
  }

  // Auto-completa el campo barrio solo si estaba vacío (no sobreescribe datos del usuario)
  const suggestedNeighborhood = normalizeText(payload?.suggested_neighborhood || '')
  if (suggestedNeighborhood && !form.neighborhood.trim()) {
    form.neighborhood = suggestedNeighborhood
    validateField('neighborhood')
  }

  // Confirma la aplicación de la ubicación al usuario
  showSnackbar({
    type: 'success',
    title: 'Ubicacion confirmada',
    message: 'La direccion GPS fue aplicada al formulario.',
  })
}

// Extrae el mensaje de error de una respuesta de API
// Busca en diferentes estructuras de error y retorna un mensaje de respaldo
function extractApiMessage(error, fallbackMessage) {
  const message = String(
    error?.response?.data?.message
      || error?.response?.data?.error
      || fallbackMessage,
  ).trim()

  return message || fallbackMessage
}

// Normaliza un valor a string limpio (trim), manejando null/undefined
function normalizeText(value) {
  return String(value || '').trim()
}

// Retorna el ID del usuario actual desde la sesión
function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

// Retorna el email del usuario actual desde la sesión
function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}

// Convierte un valor a número nullable
// Retorna null si el valor es nulo, vacío o no es un número finito
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

// Valida que un par de coordenadas GPS sea válido
// Verifica que estén en rango geográfico y no sean (0,0)
function isValidCoordinatePair(latitude, longitude) {
  const lat = toNullableNumber(latitude)
  const lng = toNullableNumber(longitude)

  // Ambos deben ser números finitos
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return false
  }

  // Verifica rangos geográficos válidos y que no sean cero (punto nulo)
  const isInRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  const isZeroed = Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001
  return isInRange && !isZeroed
}

// Valida un campo específico del formulario
// Retorna true si es válido, false si tiene error
// También actualiza el objeto fieldErrors con el mensaje correspondiente
function validateField(fieldName) {
  // Validación del campo alias (obligatorio)
  if (fieldName === 'alias') {
    fieldErrors.alias = form.alias.trim() ? '' : 'Debes ingresar un nombre descriptivo.'
    return !fieldErrors.alias
  }

  // Validación del tipo de dirección (obligatorio)
  if (fieldName === 'address_type') {
    fieldErrors.address_type = form.address_type.trim() ? '' : 'Selecciona el tipo de domicilio.'
    return !fieldErrors.address_type
  }

  // Validación del nombre del destinatario (obligatorio)
  if (fieldName === 'recipient_name') {
    fieldErrors.recipient_name = form.recipient_name.trim() ? '' : 'Debes ingresar el nombre del destinatario.'
    return !fieldErrors.recipient_name
  }

  // Validación del teléfono: solo números, entre 7 y 15 dígitos
  if (fieldName === 'recipient_phone') {
    fieldErrors.recipient_phone = /^\d{7,15}$/.test(form.recipient_phone.trim())
      ? ''
      : 'El teléfono debe contener solo números (7 a 15 dígitos).'
    return !fieldErrors.recipient_phone
  }

  // Validación de la dirección física (obligatorio)
  if (fieldName === 'address') {
    fieldErrors.address = form.address.trim() ? '' : 'Debes ingresar la dirección de entrega.'
    return !fieldErrors.address
  }

  // Validación del barrio/zona (obligatorio)
  if (fieldName === 'neighborhood') {
    fieldErrors.neighborhood = form.neighborhood.trim() ? '' : 'Debes ingresar el barrio o zona.'
    return !fieldErrors.neighborhood
  }

  // Validación del tipo de edificación (obligatorio)
  if (fieldName === 'building_type') {
    fieldErrors.building_type = form.building_type.trim() ? '' : 'Selecciona el tipo de edificación.'
    return !fieldErrors.building_type
  }

  // Campo no reconocido: retorna válido por defecto
  return true
}
</script>
