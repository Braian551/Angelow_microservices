import { computed, ref } from 'vue'

const TOKEN_KEY = 'angelow_token'
const USER_KEY = 'angelow_user'
const SESSION_KEY = 'angelow_session_id'

const token = ref(localStorage.getItem(TOKEN_KEY) || '')
const user = ref(loadUser())
const sessionId = ref(localStorage.getItem(SESSION_KEY) || createSessionId())

function loadUser() {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null

  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function createSessionId() {
  const random = Math.random().toString(36).slice(2)
  const generated = `guest_${Date.now()}_${random}`
  localStorage.setItem(SESSION_KEY, generated)
  return generated
}

function saveSession(authToken, authUser) {
  token.value = authToken || ''
  user.value = authUser || null

  if (authToken) {
    localStorage.setItem(TOKEN_KEY, authToken)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }

  if (authUser) {
    localStorage.setItem(USER_KEY, JSON.stringify(authUser))
  } else {
    localStorage.removeItem(USER_KEY)
  }
}

function clearSession() {
  saveSession('', null)
}

export function useSession() {
  return {
    token,
    user,
    sessionId,
    isLoggedIn: computed(() => Boolean(token.value && user.value)),
    saveSession,
    clearSession,
  }
}
