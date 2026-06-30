import { authHttp } from './http'

// Registra una cuenta nueva usando el contrato del auth-service.
export async function registerUser(payload) {
  const { data } = await authHttp.post('/auth/register', payload)
  return data
}

// Inicia sesión con credenciales tradicionales y devuelve la respuesta de sesión.
export async function loginUser(payload) {
  const { data } = await authHttp.post('/auth/login', payload)
  return data
}

// Inicia sesión con Google después de validar el token en el backend.
export async function loginWithGoogle(payload) {
  const { data } = await authHttp.post('/auth/google', payload)
  return data
}

// Recupera el perfil autenticado para rehidratar sesión y datos de cuenta.
export async function getProfile() {
  const { data } = await authHttp.get('/auth/me')
  return data
}

// Cierra la sesión actual en el servicio de autenticación.
export async function logoutUser() {
  const { data } = await authHttp.post('/auth/logout')
  return data
}

// Actualiza el perfil y permite enviar avatar mediante multipart/form-data.
export async function updateProfile(payload) {
  const { data } = await authHttp.post('/auth/profile', payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return data
}

// Cambia la contraseña desde una sesión autenticada.
export async function updatePassword(payload) {
  const { data } = await authHttp.post('/auth/password', payload)
  return data
}

// Solicita el código inicial del flujo de recuperación de contraseña.
export async function requestRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/request-code', payload)
  return data
}

// Reenvía el código de recuperación respetando los controles de cooldown del backend.
export async function resendRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/resend-code', payload)
  return data
}

// Verifica el código de recuperación antes de permitir definir una nueva contraseña.
export async function verifyRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/verify-code', payload)
  return data
}

// Confirma la nueva contraseña usando el token de recuperación validado.
export async function resetRecoveryPassword(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/reset-password', payload)
  return data
}

// Solicita código de verificación para completar un registro por correo.
export async function requestRegistrationCode(payload) {
  const { data } = await authHttp.post('/auth/registration-verification/request-code', payload)
  return data
}

// Reenvía el código de registro cuando el usuario lo solicita desde el formulario.
export async function resendRegistrationCode(payload) {
  const { data } = await authHttp.post('/auth/registration-verification/resend-code', payload)
  return data
}

// Verifica el código de registro y devuelve el token temporal de alta.
export async function verifyRegistrationCode(payload) {
  const { data } = await authHttp.post('/auth/registration-verification/verify-code', payload)
  return data
}
