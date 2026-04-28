import { cartHttp } from './http'

export async function getCart(params) {
  const { data } = await cartHttp.get('/cart', { params })
  return data
}

export async function addToCart(payload) {
  const { data } = await cartHttp.post('/cart/add', payload)
  return data
}

export async function updateCartItem(itemId, quantity) {
  const { data } = await cartHttp.put(`/cart/${itemId}`, { quantity })
  return data
}

export async function removeCartItem(itemId) {
  const { data } = await cartHttp.delete(`/cart/${itemId}`)
  return data
}
