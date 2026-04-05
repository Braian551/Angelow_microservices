import { ref } from 'vue'
import { getCart } from '../services/cartApi'
import { getHomeData } from '../services/catalogApi'
import { useSession } from './useSession'

const settings = ref({})
const topBar = ref(null)
const cartCount = ref(0)
const shellLoaded = ref(false)
const shellLoading = ref(false)

async function loadShellData({ force = false } = {}) {
  if (shellLoading.value) return
  if (shellLoaded.value && !force) return

  shellLoading.value = true

  try {
    const { sessionId, user } = useSession()

    const [homeResponse, cartResponse] = await Promise.all([
      getHomeData(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])

    settings.value = homeResponse?.data?.settings || {}
    topBar.value = homeResponse?.data?.top_bar || null
    cartCount.value = Number(cartResponse?.data?.item_count || 0)
    shellLoaded.value = true
  } catch {
    if (!shellLoaded.value) {
      settings.value = {}
      topBar.value = null
      cartCount.value = 0
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

async function refreshShellData() {
  if (!shellLoaded.value) {
    await loadShellData()
    return
  }

  await refreshCartCount()
}

function setCartCount(value) {
  const parsed = Number(value || 0)
  cartCount.value = Number.isNaN(parsed) ? 0 : Math.max(0, parsed)
}

function invalidateShell() {
  shellLoaded.value = false
}

export function useAppShell() {
  return {
    settings,
    topBar,
    cartCount,
    loadShellData,
    refreshCartCount,
    refreshShellData,
    setCartCount,
    invalidateShell,
  }
}
