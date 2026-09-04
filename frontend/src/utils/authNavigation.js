// Roles administrativos aceptados por los servicios actuales y por datos legacy.
export const ADMIN_ROLES = new Set(['admin', 'super_admin', 'superadmin', 'administrator'])

export function normalizeRole(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
}

export function resolveUserRole(userData) {
  const directRole = normalizeRole(
    userData?.role
      || userData?.rol
      || userData?.user_role
      || userData?.tipo_usuario,
  )

  if (directRole) {
    return directRole
  }

  const firstRole = Array.isArray(userData?.roles)
    ? normalizeRole(userData.roles[0]?.name || userData.roles[0])
    : ''

  return firstRole
}

export function isAdminRole(roleOrUser) {
  const role = typeof roleOrUser === 'object'
    ? resolveUserRole(roleOrUser)
    : normalizeRole(roleOrUser)

  return ADMIN_ROLES.has(role)
}

export function resolveRoleLandingRoute(userData) {
  return isAdminRole(userData)
    ? { name: 'admin-dashboard' }
    : { name: 'account-dashboard' }
}
