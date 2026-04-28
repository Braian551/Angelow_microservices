import { authHttp } from './http'

export async function registerUser(payload) {
  const { data } = await authHttp.post('/auth/register', payload)
  return data
}

export async function loginUser(payload) {
  const { data } = await authHttp.post('/auth/login', payload)
  return data
}

export async function loginWithGoogle(payload) {
  const { data } = await authHttp.post('/auth/google', payload)
  return data
}

export async function getProfile() {
  const { data } = await authHttp.get('/auth/me')
  return data
}

export async function logoutUser() {
  const { data } = await authHttp.post('/auth/logout')
  return data
}

export async function updateProfile(payload) {
  const { data } = await authHttp.post('/auth/profile', payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return data
}

export async function updatePassword(payload) {
  const { data } = await authHttp.post('/auth/password', payload)
  return data
}

export async function requestRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/request-code', payload)
  return data
}

export async function resendRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/resend-code', payload)
  return data
}

export async function verifyRecoveryCode(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/verify-code', payload)
  return data
}

export async function resetRecoveryPassword(payload) {
  const { data } = await authHttp.post('/auth/password-recovery/reset-password', payload)
  return data
}
