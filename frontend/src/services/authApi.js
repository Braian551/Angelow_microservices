import { authHttp } from './http'

export async function registerUser(payload) {
  const { data } = await authHttp.post('/auth/register', payload)
  return data
}

export async function loginUser(payload) {
  const { data } = await authHttp.post('/auth/login', payload)
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
