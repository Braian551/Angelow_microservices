import { shippingHttp } from './http'

export async function getShippingMethods() {
  const { data } = await shippingHttp.get('/shipping/methods')
  return data
}

export async function getShippingRules() {
  const { data } = await shippingHttp.get('/shipping/rules')
  return data
}

export async function estimateShipping(payload) {
  const { data } = await shippingHttp.post('/shipping/estimate', payload)
  return data
}

export async function getUserAddresses(userId, userEmail = '') {
  const { data } = await shippingHttp.get('/shipping/addresses', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function createUserAddress(payload, userId, userEmail = '') {
  const { data } = await shippingHttp.post('/shipping/addresses', {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

export async function updateUserAddress(addressId, payload, userId, userEmail = '') {
  const { data } = await shippingHttp.put(`/shipping/addresses/${addressId}`, {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

export async function deleteUserAddress(addressId, userId, userEmail = '') {
  const { data } = await shippingHttp.delete(`/shipping/addresses/${addressId}`, {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function setDefaultUserAddress(addressId, userId, userEmail = '') {
  const { data } = await shippingHttp.patch(`/shipping/addresses/${addressId}/default`, {
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}
