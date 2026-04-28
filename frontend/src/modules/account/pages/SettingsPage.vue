<template>
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
            <small>
              Para cambiar tu correo, haz clic
              <button type="button" class="change-email-link" @click="setTab('change-email')">aquí</button>
            </small>
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

      <section id="change-email" class="settings-section" :class="{ active: activeTab === 'change-email' }">
        <h2><i class="fas fa-envelope" /> Cambiar correo electrónico</h2>

        <form class="settings-form" @submit.prevent="submitEmailChangeRequest">
          <div class="form-group">
            <label for="current_email">Correo actual</label>
            <input id="current_email" :value="profileForm.email" type="email" disabled />
          </div>

          <div class="form-group">
            <label for="new_email">Nuevo correo electrónico</label>
            <input id="new_email" v-model.trim="emailChangeForm.new_email" type="email" required />
          </div>

          <div class="form-group">
            <label for="confirm_email">Confirmar nuevo correo</label>
            <input id="confirm_email" v-model.trim="emailChangeForm.confirm_email" type="email" required />
          </div>

          <div class="form-group">
            <label for="password_email">Contraseña actual</label>
            <input id="password_email" v-model="emailChangeForm.current_password" type="password" required />
          </div>

          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="setTab('profile')">Cancelar</button>
            <button type="submit" class="btn-save">Guardar cambios</button>
          </div>
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

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { getProfile, updatePassword, updateProfile } from '../../../services/authApi'
import {
  getNotificationPreferences,
  updateNotificationPreferences,
} from '../../../services/notificationApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import '../views/SettingsView.css'

const route = useRoute()
const router = useRouter()
const { user, token, saveSession, clearSession } = useSession()
const { showAlert } = useAlertSystem()

const activeTab = ref(resolveTabFromHash(route.hash))
const imageInputRef = ref(null)
const selectedAvatarFile = ref(null)
const localAvatarPreview = ref('')

const savingProfile = ref(false)
const savingPassword = ref(false)
const savingNotifications = ref(false)
const showPasswordForm = ref(false)

const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
})

const emailChangeForm = reactive({
  new_email: '',
  confirm_email: '',
  current_password: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const notificationsForm = reactive({
  email_notifications: true,
  product_notifications: true,
  promotion_notifications: true,
  cart_reminders: true,
})

const avatarPreviewUrl = computed(() => {
  if (localAvatarPreview.value) return localAvatarPreview.value
  return resolveMediaUrl(user.value?.image, 'avatar')
})

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

watch(
  () => route.hash,
  (hash) => {
    activeTab.value = resolveTabFromHash(hash)
  },
)

onMounted(async () => {
  hydrateProfileForm()

  await Promise.all([
    refreshProfile(),
    loadNotificationSettings(),
  ])
})

onBeforeUnmount(() => {
  if (localAvatarPreview.value) {
    URL.revokeObjectURL(localAvatarPreview.value)
    localAvatarPreview.value = ''
  }
})

function resolveTabFromHash(hash) {
  const cleanHash = String(hash || '').replace('#', '')
  const allowedTabs = new Set(['profile', 'change-email', 'security', 'notifications'])
  return allowedTabs.has(cleanHash) ? cleanHash : 'profile'
}

function setTab(tab) {
  activeTab.value = tab
  router.replace({ hash: `#${tab}` })
}

function hydrateProfileForm() {
  profileForm.name = String(user.value?.name || '')
  profileForm.email = String(user.value?.email || '')
  profileForm.phone = String(user.value?.phone || '')
}

async function refreshProfile() {
  try {
    const response = await getProfile()
    const profile = response?.data || null
    if (!profile) return

    saveSession(token.value, {
      ...user.value,
      ...profile,
    })

    hydrateProfileForm()
  } catch {
    // Mantiene la sesión local si falla la sincronización.
  }
}

async function loadNotificationSettings() {
  try {
    const response = await getNotificationPreferences(currentUserId(), currentUserEmail())
    const data = response?.data || {}

    notificationsForm.email_notifications = Boolean(data.email_notifications)
    notificationsForm.product_notifications = Boolean(data.product_notifications)
    notificationsForm.promotion_notifications = Boolean(data.promotion_notifications)
    notificationsForm.cart_reminders = Boolean(data.cart_reminders)
  } catch {
    // Conserva valores locales por defecto.
  }
}

function triggerImageInput() {
  imageInputRef.value?.click()
}

function onSelectAvatar(event) {
  const file = event?.target?.files?.[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    showAlert({
      type: 'warning',
      title: 'Imagen muy grande',
      message: 'La foto debe pesar máximo 2MB.',
    })
    return
  }

  selectedAvatarFile.value = file

  if (localAvatarPreview.value) {
    URL.revokeObjectURL(localAvatarPreview.value)
  }

  localAvatarPreview.value = URL.createObjectURL(file)
}

function onAvatarPreviewError(event) {
  handleMediaError(event, user.value?.image, 'avatar')
}

async function saveProfileData() {
  if (savingProfile.value) return

  savingProfile.value = true

  try {
    const payload = new FormData()
    payload.append('name', profileForm.name)
    payload.append('phone', profileForm.phone)

    if (selectedAvatarFile.value) {
      payload.append('image', selectedAvatarFile.value)
    }

    const response = await updateProfile(payload)
    const updatedUser = response?.data || {}

    saveSession(token.value, {
      ...user.value,
      ...updatedUser,
    })

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
    savingProfile.value = false
  }
}

async function submitEmailChangeRequest() {
  if (emailChangeForm.new_email.trim() !== emailChangeForm.confirm_email.trim()) {
    showAlert({
      type: 'warning',
      title: 'Verifica el correo',
      message: 'La confirmación del nuevo correo no coincide.',
    })
    return
  }

  showAlert({
    type: 'info',
    title: 'Cambio de correo en migración',
    message: 'Esta funcionalidad se está terminando de migrar. Por ahora usa soporte para cambiar tu correo.',
  })
}

async function saveNewPassword() {
  if (savingPassword.value) return

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
    const response = await updatePassword({ ...passwordForm })

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

function logoutFromAccount() {
  clearSession()
  router.push({ name: 'home' })
}

function extractApiMessage(error, fallbackMessage) {
  const message = String(
    error?.response?.data?.message
      || error?.response?.data?.error
      || fallbackMessage,
  ).trim()

  return message || fallbackMessage
}

function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}
</script>
