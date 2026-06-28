// Importamos computed y ref de Vue para crear propiedades reactivas y computadas
import { computed, ref } from 'vue'

// Claves utilizadas para almacenar datos de sesión en localStorage
const TOKEN_KEY = 'angelow_token' // Clave para guardar el token JWT de autenticación
const USER_KEY = 'angelow_user'   // Clave para guardar los datos del usuario autenticado
const SESSION_KEY = 'angelow_session_id' // Clave para identificar la sesión del usuario

// Variables reactivas que mantienen el estado de la sesión del usuario
const token = ref(localStorage.getItem(TOKEN_KEY) || '') // Token JWT, se inicializa desde localStorage o cadena vacía
const user = ref(loadUser()) // Datos del usuario, se carga desde localStorage mediante loadUser()
const sessionId = ref(localStorage.getItem(SESSION_KEY) || createSessionId()) // ID de sesión, se carga o genera uno nuevo

// Función que carga los datos del usuario desde localStorage
// Retorna el objeto usuario parseado o null si no existe o hay error
function loadUser() {
  const raw = localStorage.getItem(USER_KEY) // Obtiene el valor crudo de localStorage
  if (!raw) return null // Si no hay datos guardados, retorna null

  try {
    return JSON.parse(raw) // Parsea el JSON string a objeto JavaScript
  } catch {
    return null // Si el JSON es inválido, retorna null en lugar de lanzar error
  }
}

// Función que genera un ID de sesión único para usuarios invitados
// Combina timestamp + número aleatorio para garantizar unicidad
function createSessionId() {
  const random = Math.random().toString(36).slice(2) // Genera cadena aleatoria en base 36
  const generated = `guest_${Date.now()}_${random}` // Formato: guest_[timestamp]_[aleatorio]
  localStorage.setItem(SESSION_KEY, generated) // Guarda el ID en localStorage para persistencia
  return generated
}

// Función que guarda los datos de sesión después del login exitoso
// Actualiza las variables reactivas y persiste en localStorage
function saveSession(authToken, authUser) {
  // Actualiza el estado reactivo del token
  token.value = authToken || '' // Si no se proporciona token, usa cadena vacía
  // Actualiza el estado reactivo del usuario
  user.value = authUser || null // Si no se proporciona usuario, usa null

  // Persiste o elimina el token en localStorage
  if (authToken) {
    localStorage.setItem(TOKEN_KEY, authToken) // Guarda el token si existe
  } else {
    localStorage.removeItem(TOKEN_KEY) // Elimina el token si está vacío/null
  }

  // Persiste o elimina los datos del usuario en localStorage
  if (authUser) {
    localStorage.setItem(USER_KEY, JSON.stringify(authUser)) // Guarda usuario como JSON string
  } else {
    localStorage.removeItem(USER_KEY) // Elimina los datos del usuario si es null
  }
}

// Función que limpia completamente la sesión del usuario
// Utilizada al hacer logout o cuando la sesión expira
function clearSession() {
  saveSession('', null) // Llama a saveSession con valores vacíos para limpiar todo
}

// Composable principal que expone el estado y métodos de sesión
// Permite a cualquier componente reactivo acceder a la información de autenticación
export function useSession() {
  return {
    token, // Token JWT reactivo para peticiones autenticadas
    user, // Objeto usuario reactivo con datos del perfil
    sessionId, // ID de sesión para rastreo de sesión actual
    isLoggedIn: computed(() => Boolean(token.value && user.value)), // Propiedad computada que indica si el usuario está autenticado (requiere token Y usuario)
    saveSession, // Función para guardar sesión después del login
    clearSession, // Función para cerrar sesión y limpiar datos
  }
}
