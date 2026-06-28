<template>
  <AccountShimmer v-if="loading" variant="settings" />

  <template v-else>
    <section class="dashboard-header">
      <h1>Ajustes de tu cuenta</h1>
      <p>Administra tu información personal, seguridad y preferencias.</p>
    </section>

    <section class="settings-container account-card">
      <aside class="settings-sidebar">
        <ul>
          <li :class="{ active: activeTab === 'profile' }">
            <button type="button" @click="setTab('profile')">
              <i class="fas fa-user" />
              Perfil
            </button>
          </li>
          <li :class="{ active: activeTab === 'security' }">
            <button type="button" @click="setTab('security')">
              <i class="fas fa-lock" />
              Seguridad
            </button>
          </li>
          <li :class="{ active: activeTab === 'notifications' }">
            <button type="button" @click="setTab('notifications')">
              <i class="fas fa-bell" />
              Notificaciones
            </button>
          </li>
        </ul>
      </aside>

      <div class="settings-content">
      <section id="profile" class="settings-section" :class="{ active: activeTab === 'profile' }">
        <h2><i class="fas fa-user" /> Información del Perfil</h2>

        <form class="settings-form" @submit.prevent="saveProfileData">
          <div class="form-group">
            <label for="name">Nombre completo</label>
            <input id="name" v-model.trim="profileForm.name" type="text" maxlength="100" required />
          </div>

          <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" :value="profileForm.email" type="email" disabled />
          </div>

          <div class="form-group">
            <label for="phone">Teléfono</label>
            <input id="phone" v-model.trim="profileForm.phone" type="text" maxlength="20" />
          </div>

          <div class="form-group">
            <label>Foto de perfil</label>
            <div class="profile-picture-upload">
              <img :src="avatarPreviewUrl" alt="Foto de perfil" @error="onAvatarPreviewError" />
              <div class="upload-actions">
                <button type="button" class="btn-change-photo" @click="triggerImageInput">Cambiar foto</button>
                <input
                  ref="imageInputRef"
                  class="hidden-input"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  @change="onSelectAvatar"
                />
                <small>JPG, PNG o WEBP. Máximo 2MB.</small>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-save" :disabled="savingProfile">
            {{ savingProfile ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </form>
      </section>

      <section id="security" class="settings-section" :class="{ active: activeTab === 'security' }">
        <h2><i class="fas fa-lock" /> Seguridad y acceso</h2>

        <div class="security-item-father">
          <article class="security-item">
            <div class="security-info">
              <h3>Cambiar contraseña</h3>
              <p>Actualiza tu contraseña regularmente para mantener tu cuenta segura.</p>
            </div>

            <button type="button" class="btn-edit" @click="showPasswordForm = !showPasswordForm">
              {{ showPasswordForm ? 'Ocultar' : 'Cambiar' }}
            </button>
          </article>

          <form v-if="showPasswordForm" class="settings-form" @submit.prevent="saveNewPassword">
            <div class="form-group">
              <label for="current_password">Contraseña actual</label>
              <input id="current_password" v-model="passwordForm.current_password" type="password" required />
            </div>

            <div class="form-group">
              <label for="new_password">Nueva contraseña</label>
              <input
                id="new_password"
                v-model="passwordForm.password"
                type="password"
                minlength="8"
                maxlength="64"
                required
              />
              <small>Mínimo 8 caracteres, incluyendo números y letras.</small>
            </div>

            <div class="form-group">
              <label for="confirm_password">Confirmar nueva contraseña</label>
              <input
                id="confirm_password"
                v-model="passwordForm.password_confirmation"
                type="password"
                minlength="8"
                maxlength="64"
                required
              />
            </div>

            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="showPasswordForm = false">Cancelar</button>
              <button type="submit" class="btn-save" :disabled="savingPassword">
                {{ savingPassword ? 'Actualizando...' : 'Guardar cambios' }}
              </button>
            </div>
          </form>

          <article class="security-item">
            <div class="security-info">
              <h3>Sesión actual</h3>
              <p>Activa desde {{ sessionStartLabel }}</p>
            </div>

            <button type="button" class="btn-logout" @click="logoutFromAccount">Cerrar sesión</button>
          </article>
        </div>
      </section>

      <section id="notifications" class="settings-section" :class="{ active: activeTab === 'notifications' }">
        <h2><i class="fas fa-bell" /> Preferencias de notificaciones</h2>

        <form class="settings-form" @submit.prevent="saveNotificationSettings">
          <div class="form-group toggle-group">
            <label>Notificaciones por correo electrónico</label>
            <label class="switch">
              <input v-model="notificationsForm.email_notifications" type="checkbox" />
              <span class="slider round" />
            </label>
          </div>

          <div class="form-group toggle-group">
            <label>Notificaciones de nuevos productos</label>
            <label class="switch">
              <input v-model="notificationsForm.product_notifications" type="checkbox" />
              <span class="slider round" />
            </label>
          </div>

          <div class="form-group toggle-group">
            <label>Notificaciones de ofertas especiales</label>
            <label class="switch">
              <input v-model="notificationsForm.promotion_notifications" type="checkbox" />
              <span class="slider round" />
            </label>
          </div>

          <div class="form-group toggle-group">
            <label>Recordatorios de carrito abandonado</label>
            <label class="switch">
              <input v-model="notificationsForm.cart_reminders" type="checkbox" />
              <span class="slider round" />
            </label>
          </div>

          <button type="submit" class="btn-save" :disabled="savingNotifications">
            {{ savingNotifications ? 'Guardando...' : 'Guardar preferencias' }}
          </button>
        </form>
      </section>
      </div>
    </section>
  </template>
</template>

<script setup>
// ─── Imports de Vue ──────────────────────────────────────────────────────────
// Funciones reactivas y de ciclo de vida del Composition API de Vue 3.
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

// Herramientas de enrutamiento para navegación y lectura del hash de la URL.
import { useRoute, useRouter } from 'vue-router'

// Componente de efecto shimmer que se muestra mientras se cargan los datos iniciales.
import AccountShimmer from '../components/AccountShimmer.vue'

// Composable que gestiona la sesión del usuario (datos, token, persistencia).
import { useSession } from '../../../composables/useSession'

// Composable que expone showAlert para mostrar notificaciones toast/alert al usuario.
import { useAlertSystem } from '../../../composables/useAlertSystem'

// Servicios de la API de autenticación: obtener perfil, actualizar contraseña y perfil.
import { getProfile, updatePassword, updateProfile } from '../../../services/authApi'

// Servicios de la API de notificaciones: obtener y actualizar preferencias del usuario.
import {
  getNotificationPreferences,
  updateNotificationPreferences,
} from '../../../services/notificationApi'

// Utilidades para manejar errores de imágenes y resolver URLs de medios (avatars, etc.).
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

// Estilos CSS específicos de la vista de ajustes.
import '../views/SettingsView.css'

// ─── Inyección de dependencias de composables y router ────────────────────────
// Se obtienen las instancias de route (lectura) y router (navegación programática).
const route = useRoute()
const router = useRouter()

// Se extraen propiedades y métodos del composable de sesión: usuario actual, token,
// función para guardar sesión actualizada y función para cerrar sesión (limpiar almacenamiento).
const { user, token, saveSession, clearSession } = useSession()

// Se obtiene showAlert para mostrar mensajes de éxito, error o advertencia al usuario.
const { showAlert } = useAlertSystem()

// ─── Estado reactivo (refs) ──────────────────────────────────────────────────
// Tab activo actualmente visible. Se inicializa a partir del hash de la URL
// para que el tab coincida con la navegación del navegador (botón atrás/adelante).
const activeTab = ref(resolveTabFromHash(route.hash))

// Referencia al input oculto de tipo file que permite seleccionar una imagen de avatar.
const imageInputRef = ref(null)

// Archivo de avatar seleccionado localmente por el usuario (aún no subido al servidor).
const selectedAvatarFile = ref(null)

// URL.createObjectURL generada para la vista previa local del avatar antes de subirlo.
// Se usa para mostrar la imagen sin hacer una petición al servidor.
const localAvatarPreview = ref('')

// Indica si los datos iniciales (perfil y notificaciones) se están cargando.
// Muestra el shimmer mientras es true.
const loading = ref(true)

// Indicadores de estado de guardado (evita envíos múltiples mientras la petición está en curso).
const savingProfile = ref(false)
const savingPassword = ref(false)
const savingNotifications = ref(false)

// Controla la visibilidad del formulario de cambio de contraseña dentro de la sección de seguridad.
const showPasswordForm = ref(false)

// ─── Formularios reactivos ───────────────────────────────────────────────────
// Formulario de perfil: almacena nombre, email y teléfono del usuario.
// Se usa reactive() para que cada propiedad sea reactivamente vinculada (v-model) en el template.
const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
})

// Formulario de cambio de contraseña: contraseña actual, nueva contraseña y confirmación.
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Formulario de preferencias de notificaciones. Cada propiedad corresponde a un interruptor
// (toggle) en la interfaz. Los valores por defecto son true (notificaciones activadas).
const notificationsForm = reactive({
  email_notifications: true,
  product_notifications: true,
  promotion_notifications: true,
  cart_reminders: true,
})

// ─── Propiedades computadas ──────────────────────────────────────────────────
// Determina la URL de la imagen de avatar a mostrar.
// Si el usuario seleccionó un archivo localmente, usa la vista previa (.createObjectURL).
// Si no, resuelve la URL del avatar almacenado en el servidor o usa un avatar por defecto.
const avatarPreviewUrl = computed(() => {
  if (localAvatarPreview.value) return localAvatarPreview.value
  return resolveMediaUrl(user.value?.image, 'avatar')
})

// Genera una etiqueta con la fecha de inicio de sesión formateada en español (formato colombiano).
// Usa created_at o updated_at como fuente. Si no hay fecha válida, devuelve 'hace poco'.
const sessionStartLabel = computed(() => {
  const sourceDate = user.value?.created_at || user.value?.updated_at
  if (!sourceDate) return 'hace poco'

  const parsedDate = new Date(sourceDate)
  if (Number.isNaN(parsedDate.getTime())) return 'hace poco'

  return parsedDate.toLocaleString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
})

// ─── Watcher ─────────────────────────────────────────────────────────────────
// Observa cambios en el hash de la URL (#profile, #security, etc.) y actualiza
// el tab activo. Esto permite que la navegación del navegador (atrás/adelante)
// sincronice correctamente el contenido visible.
watch(
  () => route.hash,
  (hash) => {
    activeTab.value = resolveTabFromHash(hash)
  },
)

// ─── Ciclos de vida ──────────────────────────────────────────────────────────
// Al montar el componente: muestra el shimmer, hidrata el formulario con datos locales,
// luego carga en paralelo el perfil actualizado y las preferencias de notificación desde la API.
onMounted(async () => {
  loading.value = true
  hydrateProfileForm()

  try {
    await Promise.all([
      refreshProfile(),
      loadNotificationSettings(),
    ])
  } finally {
    // Siempre oculta el shimmer al terminar (éxito o error).
    loading.value = false
  }
})

// Al desmontar el componente: libera la URL.createObjectURL del avatar local para
// evitar memory leaks en el navegador.
onBeforeUnmount(() => {
  if (localAvatarPreview.value) {
    URL.revokeObjectURL(localAvatarPreview.value)
    localAvatarPreview.value = ''
  }
})

// ─── Funciones auxiliares ────────────────────────────────────────────────────
// Extrae el hash de la URL (sin el '#') y valida que pertenezca a uno de los tabs permitidos.
// Si el hash no es válido o está vacío, devuelve 'profile' como tab por defecto.
function resolveTabFromHash(hash) {
  const cleanHash = String(hash || '').replace('#', '')
  const allowedTabs = new Set(['profile', 'security', 'notifications'])
  return allowedTabs.has(cleanHash) ? cleanHash : 'profile'
}

// Cambia el tab activo y actualiza el hash de la URL usando router.replace (sin crear
// nueva entrada en el historial). Esto permite compartir enlaces directos a cada sección.
function setTab(tab) {
  activeTab.value = tab
  router.replace({ hash: `#${tab}` })
}

// Hidrata (rellena) el formulario de perfil con los datos actuales del usuario en sesión.
// Se llama al montar y después de cada actualización exitosa desde la API.
function hydrateProfileForm() {
  profileForm.name = String(user.value?.name || '')
  profileForm.email = String(user.value?.email || '')
  profileForm.phone = String(user.value?.phone || '')
}

// Refresca el perfil del usuario desde la API y actualiza la sesión local con los datos más
// recientes. Si la petición falla, se conserva la sesión local (modo offline/resiliente).
async function refreshProfile() {
  try {
    const response = await getProfile()
    const profile = response?.data || null
    if (!profile) return

    // Mezcla los datos existentes de la sesión con los nuevos del servidor.
    saveSession(token.value, {
      ...user.value,
      ...profile,
    })

    // Actualiza el formulario con los nuevos datos.
    hydrateProfileForm()
  } catch {
    // Mantiene la sesión local si falla la sincronización.
  }
}

// Carga las preferencias de notificación del usuario desde la API y las aplica al formulario.
// Si la petición falla, se conservan los valores por defecto (todos activados).
async function loadNotificationSettings() {
  try {
    const response = await getNotificationPreferences(currentUserId(), currentUserEmail())
    const data = response?.data || {}

    // Se usa Boolean() para asegurar que los valores sean true/false y no null/undefined.
    notificationsForm.email_notifications = Boolean(data.email_notifications)
    notificationsForm.product_notifications = Boolean(data.product_notifications)
    notificationsForm.promotion_notifications = Boolean(data.promotion_notifications)
    notificationsForm.cart_reminders = Boolean(data.cart_reminders)
  } catch {
    // Conserva valores locales por defecto.
  }
}

// Dispara el click programático en el input de archivo oculto, abriendo el explorador
// de archivos del sistema operativo para que el usuario seleccione una imagen.
function triggerImageInput() {
  imageInputRef.value?.click()
}

// Maneja la selección de un archivo de avatar por parte del usuario.
// Valida el tamaño (máximo 2MB), guarda el archivo y genera una URL de vista previa local.
function onSelectAvatar(event) {
  const file = event?.target?.files?.[0]
  if (!file) return

  // Validación del tamaño del archivo: 2MB = 2 * 1024 * 1024 bytes.
  if (file.size > 2 * 1024 * 1024) {
    showAlert({
      type: 'warning',
      title: 'Imagen muy grande',
      message: 'La foto debe pesar máximo 2MB.',
    })
    return
  }

  selectedAvatarFile.value = file

  // Libera la URL de vista previa anterior para evitar memory leaks.
  if (localAvatarPreview.value) {
    URL.revokeObjectURL(localAvatarPreview.value)
  }

  // Genera una URL temporal para mostrar la vista previa de la imagen seleccionada.
  localAvatarPreview.value = URL.createObjectURL(file)
}

// Maneja errores de carga de la imagen de avatar (si la URL del servidor falla).
// Redirige a un avatar por defecto usando la utilidad handleMediaError.
function onAvatarPreviewError(event) {
  handleMediaError(event, user.value?.image, 'avatar')
}

// Guarda los datos del perfil del usuario en el servidor.
// Construye un FormData para soportar tanto campos de texto como el archivo de imagen.
async function saveProfileData() {
  // Evita envíos múltiples si ya hay una petición en curso.
  if (savingProfile.value) return

  savingProfile.value = true

  try {
    // FormData permite enviar archivos junto con campos de texto (necesario para el avatar).
    const payload = new FormData()
    payload.append('name', profileForm.name)
    payload.append('phone', profileForm.phone)

    // Solo adjunta la imagen si el usuario seleccionó un archivo nuevo.
    if (selectedAvatarFile.value) {
      payload.append('image', selectedAvatarFile.value)
    }

    const response = await updateProfile(payload)
    const updatedUser = response?.data || {}

    // Actualiza la sesión con los datos nuevos recibidos del servidor.
    saveSession(token.value, {
      ...user.value,
      ...updatedUser,
    })

    // Limpia el estado local del avatar seleccionado después de un guardado exitoso.
    selectedAvatarFile.value = null

    if (localAvatarPreview.value) {
      URL.revokeObjectURL(localAvatarPreview.value)
      localAvatarPreview.value = ''
    }

    showAlert({
      type: 'success',
      title: 'Perfil actualizado',
      message: 'Tus datos fueron actualizados correctamente.',
      autoCloseSeconds: 3,
    })
  } catch (error) {
    showAlert({
      type: 'error',
      title: 'No se pudo guardar',
      message: extractApiMessage(error, 'No pudimos guardar los cambios del perfil.'),
    })
  } finally {
    // Siempre desactiva el estado de guardado al terminar.
    savingProfile.value = false
  }
}

// Guarda la nueva contraseña del usuario en el servidor.
// Valida que la nueva contraseña y la confirmación coincidan antes de enviar.
async function saveNewPassword() {
  // Evita envíos múltiples.
  if (savingPassword.value) return

  // Validación de coincidencia de contraseñas en el lado del cliente.
  if (passwordForm.password !== passwordForm.password_confirmation) {
    showAlert({
      type: 'warning',
      title: 'Verifica la confirmación',
      message: 'Las contraseñas no coinciden.',
    })
    return
  }

  savingPassword.value = true

  try {
    // Se envía una copia del formulario (spread) para no enviar referencias reactivas.
    const response = await updatePassword({ ...passwordForm })

    // Limpia todos los campos del formulario después de un cambio exitoso.
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    showPasswordForm.value = false

    showAlert({
      type: 'success',
      title: 'Contraseña actualizada',
      message: response?.message || 'Tu contraseña fue actualizada correctamente.',
      autoCloseSeconds: 3,
    })
  } catch (error) {
    showAlert({
      type: 'error',
      title: 'No se pudo actualizar',
      message: extractApiMessage(error, 'No pudimos cambiar la contraseña.'),
    })
  } finally {
    savingPassword.value = false
  }
}

// Guarda las preferencias de notificación del usuario en el servidor.
// Envía el objeto completo de preferencias junto con el ID y email del usuario.
async function saveNotificationSettings() {
  if (savingNotifications.value) return

  savingNotifications.value = true

  try {
    await updateNotificationPreferences(
      {
        email_notifications: notificationsForm.email_notifications,
        product_notifications: notificationsForm.product_notifications,
        promotion_notifications: notificationsForm.promotion_notifications,
        cart_reminders: notificationsForm.cart_reminders,
      },
      currentUserId(),
      currentUserEmail(),
    )

    showAlert({
      type: 'success',
      title: 'Preferencias guardadas',
      message: 'Tus notificaciones fueron actualizadas.',
      autoCloseSeconds: 3,
    })
  } catch (error) {
    showAlert({
      type: 'error',
      title: 'No se pudo guardar',
      message: extractApiMessage(error, 'No pudimos actualizar tus preferencias.'),
    })
  } finally {
    savingNotifications.value = false
  }
}

// Cierra la sesión del usuario: limpia los datos de sesión del almacenamiento local
// y redirige a la página de inicio.
function logoutFromAccount() {
  clearSession()
  router.push({ name: 'home' })
}

// Extrae un mensaje de error legible de la respuesta de la API.
// Intenta obtener el mensaje de differentes ubicaciones del objeto de error,
// y si ninguna está disponible, usa el mensaje de respaldo (fallback) proporcionado.
function extractApiMessage(error, fallbackMessage) {
  const message = String(
    error?.response?.data?.message
      || error?.response?.data?.error
      || fallbackMessage,
  ).trim()

  return message || fallbackMessage
}

// Obtiene el ID del usuario actual como string. Si no existe, devuelve undefined
// para que los parámetros opcionales de la API se omitan correctamente.
function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

// Obtiene el email del usuario actual como string. Se usa como parámetro
// para las llamadas a la API de notificaciones (identificación del usuario).
function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}
</script>
