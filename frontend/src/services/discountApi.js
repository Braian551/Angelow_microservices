import { discountHttp } from './http'

export async function getDiscountCodes() {
  const { data } = await discountHttp.get('/discounts/codes')
  return data
}

export async function validateDiscountCode(payload) {
  const { data } = await discountHttp.post('/discounts/validate', payload)
  return data
}

export async function validateBulkDiscount(payload) {
  const { data } = await discountHttp.post('/discounts/bulk/validate', payload)
  return data
}
