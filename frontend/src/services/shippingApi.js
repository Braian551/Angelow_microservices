import { shippingHttp } from './http'

// Obtiene métodos de envío disponibles con filtros opcionales.
export async function getShippingMethods(params = {}) {
  const { data } = await shippingHttp.get('/shipping/methods', { params })
  return data
}

// Lista reglas de envío usadas para estimación y administración.
export async function getShippingRules() {
  const { data } = await shippingHttp.get('/shipping/rules')
  return data
}

// Calcula el costo estimado de envío para el carrito y dirección seleccionados.
export async function estimateShipping(payload) {
  const { data } = await shippingHttp.post('/shipping/estimate', payload)
  return data
}

// Recupera direcciones del usuario usando id y correo para resolver identidad distribuida.
export async function getUserAddresses(userId, userEmail = '') {
  const { data } = await shippingHttp.get('/shipping/addresses', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Crea una dirección asociando los datos de identidad requeridos por shipping-service.
export async function createUserAddress(payload, userId, userEmail = '') {
  const { data } = await shippingHttp.post('/shipping/addresses', {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

// Actualiza una dirección existente sin perder la identidad del cliente en la petición.
export async function updateUserAddress(addressId, payload, userId, userEmail = '') {
  const { data } = await shippingHttp.put(`/shipping/addresses/${addressId}`, {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

// Elimina una dirección validando por parámetros la pertenencia al usuario.
export async function deleteUserAddress(addressId, userId, userEmail = '') {
  const { data } = await shippingHttp.delete(`/shipping/addresses/${addressId}`, {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Marca una dirección como predeterminada para reutilizarla en checkout.
export async function setDefaultUserAddress(addressId, userId, userEmail = '') {
  const { data } = await shippingHttp.patch(`/shipping/addresses/${addressId}/default`, {
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}
