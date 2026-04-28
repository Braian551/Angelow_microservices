<template>
  <AccountDashboardLayout
    :active-section="activeSection"
    :unread-notifications="unreadNotifications"
  >
    <RouterView />
  </AccountDashboardLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, provide, readonly, ref } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import { getProfile } from '../../../services/authApi'
import { getNotifications } from '../../../services/notificationApi'
import AccountDashboardLayout from '../components/AccountDashboardLayout.vue'

const route = useRoute()
const { user, token, saveSession } = useSession()

const unreadNotifications = ref(0)
const NOTIFICATIONS_POLLING_MS = 20000
let unreadNotificationsTimer = null

const activeSection = computed(() => String(route.meta?.accountSection || 'dashboard'))

provide('accountUnreadNotifications', readonly(unreadNotifications))
provide('setAccountUnreadNotifications', setAccountUnreadNotifications)
provide('refreshAccountUnreadNotifications', refreshUnreadNotifications)

onMounted(async () => {
  await syncProfile()
  await refreshUnreadNotifications()
  startUnreadNotificationsPolling()
})

onUnmounted(() => {
  stopUnreadNotificationsPolling()
})

function setAccountUnreadNotifications(value) {
  unreadNotifications.value = normalizeCount(value)
}

async function syncProfile() {
  if (!user.value?.id || !token.value) return

  try {
    const response = await getProfile()
    const profile = response?.data || response?.user || null

    if (!profile || typeof profile !== 'object') return

    saveSession(token.value, {
      ...user.value,
      ...profile,
    })
  } catch {
    // El layout mantiene el dashboard funcional aunque falle el sync.
  }
}

async function refreshUnreadNotifications() {
  if (!user.value?.id && !user.value?.email) {
    unreadNotifications.value = 0
    return
  }

  try {
    const notificationsResponse = await getNotifications(
      String(user.value?.id || '').trim(),
      String(user.value?.email || '').trim(),
    )

    if (Array.isArray(notificationsResponse?.data)) {
      unreadNotifications.value = notificationsResponse.data.filter((item) => !item?.is_read).length
      return
    }
  } catch {
    // Si falla la carga, no se rompe la navegación del dashboard.
  }

  unreadNotifications.value = 0
}

function startUnreadNotificationsPolling() {
  stopUnreadNotificationsPolling()

  unreadNotificationsTimer = window.setInterval(() => {
    if (document.visibilityState === 'hidden') {
      return
    }

    refreshUnreadNotifications()
  }, NOTIFICATIONS_POLLING_MS)
}

function stopUnreadNotificationsPolling() {
  if (unreadNotificationsTimer !== null) {
    window.clearInterval(unreadNotificationsTimer)
    unreadNotificationsTimer = null
  }
}

function normalizeCount(value) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed) || parsed < 0) return 0
  return Math.floor(parsed)
}
</script>
