import { paymentHttp } from './http'

export async function getBanks() {
  const { data } = await paymentHttp.get('/banks')
  return data
}

export async function getPayments(params = {}) {
  const { data } = await paymentHttp.get('/payments', { params })
  return data
}

export async function createPayment(payload) {
  const requestConfig = payload instanceof FormData
    ? { headers: { 'Content-Type': 'multipart/form-data' } }
    : undefined

  const { data } = await paymentHttp.post('/payments', payload, requestConfig)
  return data
}

export async function verifyPayment(paymentId, payload) {
  const { data } = await paymentHttp.patch(`/payments/${paymentId}/verify`, payload)
  return data
}
