// Función reactiva de Vue 3 para crear referencias que se actualizan en la interfaz
import { ref } from 'vue'
// Servicio para obtener los datos del carrito de compras desde la API
import { getCart } from '../services/cartApi'
// Servicio para obtener los datos principales del catálogo (configuración y barra superior)
import { getHomeData } from '../services/catalogApi'
// Servicio para obtener las notificaciones del usuario desde la API
import { getNotifications } from '../services/notificationApi'
// Composable para acceder al estado de la sesión del usuario (ID y datos del usuario)
import { useSession } from './useSession'

// Configuración general del sitio (se obtiene del home)
const settings = ref({})
// Datos de la barra superior del sitio (menú, enlaces, etc.)
const topBar = ref(null)
// Cantidad total de artículos en el carrito (se muestra en el ícono del carrito)
const cartCount = ref(0)
// Cantidad de notificaciones no leídas del usuario
const notificationCount = ref(0)
// Indica si los datos del shell (barra, configuración) ya fueron cargados exitosamente
const shellLoaded = ref(false)
// Indica si el shell se está cargando actualmente (evita llamadas duplicadas)
const shellLoading = ref(false)

// Extrae la cantidad de notificaciones no leídas de la respuesta de la API.
// Maneja diferentes formatos de respuesta posibles del backend.
function extractUnreadNotificationCount(response) {
  // Obtiene el payload real: intenta data.data, luego data, o usa response directamente
  const payload = response?.data?.data ?? response?.data ?? response
  // Normaliza el array de notificaciones según el formato recibido
  const notifications = Array.isArray(payload?.notifications)
    ? payload.notifications
    : (Array.isArray(payload) ? payload : [])

  // Filtra notificaciones no leídas: is_read debe ser falso y read_at debe ser nulo/undefined
  return notifications.filter((notification) => notification?.is_read !== true && Number(notification?.read_at ? 1 : 0) === 0).length
}

// Verifica si el usuario tiene identidad válida para poder obtener notificaciones.
// Se necesita al menos un ID o un email no vacío.
function hasNotificationIdentity(user) {
  return Boolean(user?.id || String(user?.email || '').trim())
}

// Carga todos los datos del shell: configuración del sitio, carrito y notificaciones.
// Se ejecuta al iniciar la aplicación. El parámetro force permite forzar recarga.
async function loadShellData({ force = false } = {}) {
  // Evita múltiples cargas simultáneas (debounce implícito)
  if (shellLoading.value) return
  // Si ya está cargado y no se fuerza recarga, no vuelve a pedir datos
  if (shellLoaded.value && !force) return

  // Marca que ha comenzado la carga para bloquear otras solicitudes
  shellLoading.value = true

  try {
    // Obtiene la sesión actual para identificar al usuario
    const { sessionId, user } = useSession()

    // Prepara la promesa de notificaciones: solo se ejecuta si el usuario tiene identidad
    const notificationsPromise = hasNotificationIdentity(user.value)
      // Si el usuario tiene ID o email, pide notificaciones; si falla, retorna null
      ? getNotifications(user.value?.id, user.value?.email).catch(() => null)
      // Si no hay identidad del usuario, resuelve con null (no pide notificaciones)
      : Promise.resolve(null)

    // Ejecuta las tres peticiones en paralelo para mayor eficiencia
    const [homeResponse, cartResponse, notificationsResponse] = await Promise.all([
      getHomeData(),
      getCart({
        // Envía user_id si el usuario está autenticado, si no usa session_id (carrito anónimo)
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
      notificationsPromise,
    ])

    // Actualiza la configuración del sitio con los datos recibidos
    settings.value = homeResponse?.data?.settings || {}
    topBar.value = homeResponse?.data?.top_bar || null
    cartCount.value = Number(cartResponse?.data?.item_count || 0)
    notificationCount.value = extractUnreadNotificationCount(notificationsResponse)
    shellLoaded.value = true
  } catch {
    // En caso de error, solo resetea los valores si aún no se había cargado nada
    if (!shellLoaded.value) {
      settings.value = {}
      topBar.value = null
      cartCount.value = 0
      notificationCount.value = 0
    }
  } finally {
    // Siempre marca que la carga terminó, independientemente del resultado
    shellLoading.value = false
  }
}

// Actualiza únicamente el contador del carrito sin recargar todo el shell.
// Se usa cuando el usuario agrega/elimina productos.
async function refreshCartCount() {
  try {
    // Obtiene la sesión actual para enviar el identificador correcto del carrito
    const { sessionId, user } = useSession()
    const cartResponse = await getCart({
      user_id: user.value?.id || undefined,
      session_id: user.value?.id ? undefined : sessionId.value,
    })

    // Actualiza el contador con el valor más reciente del servidor
    cartCount.value = Number(cartResponse?.data?.item_count || 0)
  } catch {
    // El layout no se rompe si falla el refresco de carrito.
  }
}

// Actualiza únicamente el contador de notificaciones no leídas.
// Se usa cuando el usuario lee notificaciones o cuando se necesita un refresh puntual.
async function refreshNotificationCount() {
  try {
    // Obtiene la sesión actual para identificar al usuario
    const { user } = useSession()
    // Verifica que el usuario tenga identidad válida para pedir notificaciones
    if (!hasNotificationIdentity(user.value)) {
      // Si el usuario no tiene identidad, resetea el contador a cero
      notificationCount.value = 0
      return
    }

    const notificationsResponse = await getNotifications(user.value?.id, user.value?.email)
    notificationCount.value = extractUnreadNotificationCount(notificationsResponse)
  } catch {
    // El header conserva el ultimo contador valido si falla el refresco.
  }
}

// Actualiza solo la configuración del sitio y la barra superior.
// Útil cuando cambian ajustes globales del catálogo sin necesidad de recargar carrito ni notificaciones.
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

// Actualiza carrito y notificaciones en paralelo.
// Si el shell no fue cargado previamente, ejecuta loadShellData en su lugar.
async function refreshShellData() {
  if (!shellLoaded.value) {
    await loadShellData()
    return
  }

  await Promise.all([refreshCartCount(), refreshNotificationCount()])
}

// Establece manualmente el contador del carrito con un valor específico.
// Valida que sea un número positivo; en caso contrario usa 0.
function setCartCount(value) {
  const parsed = Number(value || 0)
  cartCount.value = Number.isNaN(parsed) ? 0 : Math.max(0, parsed)
}

// Establece manualmente el contador de notificaciones con un valor específico.
// Valida que sea un número positivo; en caso contrario usa 0.
function setNotificationCount(value) {
  const parsed = Number(value || 0)
  notificationCount.value = Number.isNaN(parsed) ? 0 : Math.max(0, parsed)
}

// Invalida el estado del shell para forzar una recarga completa en la próxima llamada a loadShellData.
// Se usa cuando el usuario cierra sesión o se producen cambios significativos.
function invalidateShell() {
  shellLoaded.value = false
}

// Composable principal que expone todo el estado y funciones del shell de la aplicación.
// Permite a cualquier componente acceder a configuración, carrito y notificaciones.
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
