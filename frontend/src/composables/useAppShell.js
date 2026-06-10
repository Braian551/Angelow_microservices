import { ref } from 'vue'
import { getCart } from '../services/cartApi'
import { getHomeData } from '../services/catalogApi'
import { getNotifications } from '../services/notificationApi'
import { useSession } from './useSession'

const settings = ref({})
const topBar = ref(null)
const cartCount = ref(0)
const notificationCount = ref(0)
const shellLoaded = ref(false)
const shellLoading = ref(false)

function extractUnreadNotificationCount(response) {
  const payload = response?.data?.data ?? response?.data ?? response
  const notifications = Array.isArray(payload?.notifications)
    ? payload.notifications
    : (Array.isArray(payload) ? payload : [])

  return notifications.filter((notification) => notification?.is_read !== true && Number(notification?.read_at ? 1 : 0) === 0).length
}

function hasNotificationIdentity(user) {
  return Boolean(user?.id || String(user?.email || '').trim())
}

async function loadShellData({ force = false } = {}) {
  if (shellLoading.value) return
  if (shellLoaded.value && !force) return

  shellLoading.value = true

  try {
    const { sessionId, user } = useSession()

    const notificationsPromise = hasNotificationIdentity(user.value)
      ? getNotifications(user.value?.id, user.value?.email).catch(() => null)
      : Promise.resolve(null)

    const [homeResponse, cartResponse, notificationsResponse] = await Promise.all([
      getHomeData(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
      notificationsPromise,
    ])

    settings.value = homeResponse?.data?.settings || {}
    topBar.value = homeResponse?.data?.top_bar || null
    cartCount.value = Number(cartResponse?.data?.item_count || 0)
    notificationCount.value = extractUnreadNotificationCount(notificationsResponse)
    shellLoaded.value = true
  } catch {
    if (!shellLoaded.value) {
      settings.value = {}
      topBar.value = null
      cartCount.value = 0
      notificationCount.value = 0
    }
  } finally {
    shellLoading.value = false
  }
}

async function refreshCartCount() {
  try {
    const { sessionId, user } = useSession()
    const cartResponse = await getCart({
      user_id: user.value?.id || undefined,
      session_id: user.value?.id ? undefined : sessionId.value,
    })

    cartCount.value = Number(cartResponse?.data?.item_count || 0)
  } catch {
    // El layout no se rompe si falla el refresco de carrito.
  }
}

async function refreshNotificationCount() {
  try {
    const { user } = useSession()
    if (!hasNotificationIdentity(user.value)) {
      notificationCount.value = 0
      return
    }

    const notificationsResponse = await getNotifications(user.value?.id, user.value?.email)
    notificationCount.value = extractUnreadNotificationCount(notificationsResponse)
  } catch {
    // El header conserva el ultimo contador valido si falla el refresco.
  }
}

async function refreshShellSettings() {
  try {
    const homeResponse = await getHomeData()
    settings.value = homeResponse?.data?.settings || {}
    topBar.value = homeResponse?.data?.top_bar || null
    shellLoaded.value = true
  } catch {
    // Se mantiene la ultima configuracion valida del shell si falla el refresco.
  }
}

async function refreshShellData() {
  if (!shellLoaded.value) {
    await loadShellData()
    return
  }

  await Promise.all([refreshCartCount(), refreshNotificationCount()])
}

function setCartCount(value) {
  const parsed = Number(value || 0)
  cartCount.value = Number.isNaN(parsed) ? 0 : Math.max(0, parsed)
}

function setNotificationCount(value) {
  const parsed = Number(value || 0)
  notificationCount.value = Number.isNaN(parsed) ? 0 : Math.max(0, parsed)
}

function invalidateShell() {
  shellLoaded.value = false
}

export function useAppShell() {
  return {
    settings,
    topBar,
    cartCount,
    notificationCount,
    shellLoading,
    loadShellData,
    refreshCartCount,
    refreshNotificationCount,
    refreshShellSettings,
    refreshShellData,
    setCartCount,
    setNotificationCount,
    invalidateShell,
  }
}
