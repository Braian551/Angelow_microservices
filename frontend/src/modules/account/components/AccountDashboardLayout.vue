<template>
  <main class="account-page">
    <section v-if="!isLoggedIn" class="section-container account-auth-required">
      <h2>Inicia sesión para acceder a tu cuenta</h2>
      <p>Debes iniciar sesión para gestionar pedidos, notificaciones, direcciones y favoritos.</p>
      <RouterLink :to="{ name: 'login', query: { redirect: currentPath } }" class="btn-primary-small">
        Iniciar sesión
      </RouterLink>
    </section>

    <div v-else class="user-dashboard-container">
      <aside class="user-sidebar">
        <div class="user-profile-summary">
          <button type="button" class="back-button" aria-label="Volver" @click="goBackOrHome">
            <i class="fas fa-arrow-left" />
          </button>

          <div class="user-avatar">
            <img :src="avatarUrl" alt="Foto de perfil" @error="onAvatarError" />
          </div>

          <div class="user-info">
            <h3>{{ displayName }}</h3>
            <p>{{ displayEmail }}</p>
            <p>{{ memberSinceLabel }}</p>
          </div>
        </div>

        <nav class="user-menu" aria-label="Panel de usuario">
          <ul>
            <li :class="{ active: activeSection === 'dashboard' }">
              <RouterLink :to="{ name: 'account-dashboard' }">
                <i class="fas fa-tachometer-alt" />
                Resumen
              </RouterLink>
            </li>
            <li :class="{ active: activeSection === 'orders' }">
              <RouterLink :to="{ name: 'account-orders' }">
                <i class="fas fa-shopping-bag" />
                Mis pedidos
              </RouterLink>
            </li>
            <li :class="{ active: activeSection === 'notifications' }">
              <RouterLink :to="{ name: 'account-notifications' }">
                <i class="fas fa-bell" />
                Notificaciones
                <span v-if="unreadNotifications > 0" class="notification-badge">{{ unreadBadge }}</span>
              </RouterLink>
            </li>
            <li :class="{ active: activeSection === 'addresses' }">
              <RouterLink :to="{ name: 'account-addresses' }">
                <i class="fas fa-map-marker-alt" />
                Direcciones
              </RouterLink>
            </li>
            <li :class="{ active: activeSection === 'wishlist' }">
              <RouterLink :to="{ name: 'account-wishlist' }">
                <i class="fas fa-heart" />
                Favoritos
              </RouterLink>
            </li>
            <li :class="{ active: activeSection === 'settings' }">
              <RouterLink :to="{ name: 'account-settings' }">
                <i class="fas fa-user-cog" />
                Configuración
              </RouterLink>
            </li>
            <li class="logout">
              <button type="button" @click="requestLogout">
                <i class="fas fa-sign-out-alt" />
                Cerrar sesión
              </button>
            </li>
          </ul>
        </nav>
      </aside>

      <section class="user-main-content">
        <slot />
      </section>
    </div>
  </main>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSession } from '../../../composables/useSession'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import '../views/AccountDashboardView.css'

const props = defineProps({
  activeSection: {
    type: String,
    default: 'dashboard',
  },
  unreadNotifications: {
    type: Number,
    default: 0,
  },
})

const router = useRouter()
const route = useRoute()
const { user, isLoggedIn, clearSession } = useSession()
const { showAlert } = useAlertSystem()

const currentPath = computed(() => route.fullPath || '/mi-cuenta/resumen')

const displayName = computed(() => String(user.value?.name || 'Usuario'))
const displayEmail = computed(() => String(user.value?.email || 'Sin correo'))
const unreadBadge = computed(() => (props.unreadNotifications > 99 ? '99+' : String(props.unreadNotifications)))

const avatarPath = computed(() => user.value?.image || user.value?.avatar || user.value?.profile_image || '')
const avatarUrl = computed(() => resolveMediaUrl(avatarPath.value, 'avatar'))

const memberSinceLabel = computed(() => {
  const raw = user.value?.created_at
  if (!raw) return 'Miembro de Angelow'

  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return 'Miembro de Angelow'

  return `Miembro desde ${date.toLocaleDateString('es-CO', { month: 'short', year: 'numeric' })}`
})

function onAvatarError(event) {
  handleMediaError(event, avatarPath.value, 'avatar')
}

function goBackOrHome() {
  if (window.history.length > 1) {
    router.back()
    return
  }

  router.push({ name: 'home' })
}

function requestLogout() {
  showAlert({
    type: 'question',
    title: 'Cerrar sesión',
    message: '¿Deseas cerrar tu sesión en Angelow?',
    actions: [
      {
        text: 'Cancelar',
        style: 'secondary',
      },
      {
        text: 'Cerrar sesión',
        style: 'danger',
        callback: () => {
          clearSession()
          router.push({ name: 'home' })
        },
      },
    ],
  })
}
</script>
