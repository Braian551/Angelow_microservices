import { paymentHttp } from './http'

export async function getBanks() {
  const { data } = await paymentHttp.get('/banks')
  return data
}

export async function getPaymentAccount() {
  const { data } = await paymentHttp.get('/payment-account')
  return data
}

export async function getPayments(params = {}) {
  const { data } = await paymentHttp.get('/payments', { params })
  return data
}

export async function createPayment(payload) {
  const { data } = await paymentHttp.post('/payments', payload)
  return data
}

export async function verifyPayment(paymentId, payload) {
  const { data } = await paymentHttp.patch(`/payments/${paymentId}/verify`, payload)
  return data
}

export async function getAdminPaymentAccountConfig() {
  const { data } = await paymentHttp.get('/admin/payment-account')
  return data
}

export async function saveAdminPaymentAccountConfig(payload) {
  const { data } = await paymentHttp.put('/admin/payment-account', payload)
  return data
}
