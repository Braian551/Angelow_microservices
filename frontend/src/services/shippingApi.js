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
