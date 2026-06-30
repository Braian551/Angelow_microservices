import { discountHttp } from './http'

// Obtiene códigos de descuento disponibles para validación en checkout.
export async function getDiscountCodes() {
  const { data } = await discountHttp.get('/discounts/codes')
  return data
}

// Valida un código ingresado por el cliente contra discount-service.
export async function validateDiscountCode(payload) {
  const { data } = await discountHttp.post('/discounts/validate', payload)
  return data
}

// Valida descuentos masivos aplicables al carrito completo.
export async function validateBulkDiscount(payload) {
  const { data } = await discountHttp.post('/discounts/bulk/validate', payload)
  return data
}
