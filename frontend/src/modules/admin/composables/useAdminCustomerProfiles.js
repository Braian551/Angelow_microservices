import { authHttp } from '../../../services/http'

/**
 * Composable para la carga y resolución de perfiles de clientes.
 * Se usa como helper compartido para completar datos faltantes
 * de clientes desde el endpoint de autenticación.
 */

/** Normaliza los datos de un perfil del backend a un formato consistente. */
function normalizeProfile(profile) {
  return {
    id: String(profile.id || ''),
    name: profile.name || 'Cliente',
    email: profile.email || '',
    image: profile.image || '',
  }
}

/**
 * Carga los perfiles de clientes por sus IDs (máximo 200).
 * Retorna un mapa { id: { id, name, email, image } }.
 */
export async function loadAdminCustomerProfiles(userIds = []) {
  const ids = [...new Set(userIds.map((value) => String(value || '').trim()).filter(Boolean))].slice(0, 200)

  if (ids.length === 0) {
    return {}
  }

  try {
    const response = await authHttp.get('/admin/customers', {
      params: {
        ids: ids.join(','),
      },
    })

    const payload = response.data?.data || response.data || []
    const rows = Array.isArray(payload) ? payload : (payload.data || [])

    return rows.reduce((profiles, row) => {
      const profile = normalizeProfile(row)
      if (profile.id) {
        profiles[profile.id] = profile
      }
      return profiles
    }, {})
  } catch {
    return {}
  }
}

/** Busca y retorna el perfil de un cliente por su ID en el mapa de perfiles. */
export function resolveAdminCustomerProfile(profiles, userId) {
  const normalizedId = String(userId || '').trim()

  if (!normalizedId || !profiles || typeof profiles !== 'object') {
    return null
  }

  return profiles[normalizedId] || null
}