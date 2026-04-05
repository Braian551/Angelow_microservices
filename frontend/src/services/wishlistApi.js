import { catalogHttp } from './http'

export async function getWishlist(userId, userEmail = '') {
  const { data } = await catalogHttp.get('/wishlist', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function toggleWishlist(payload) {
  const { data } = await catalogHttp.post('/wishlist/toggle', payload)
  return data
}
