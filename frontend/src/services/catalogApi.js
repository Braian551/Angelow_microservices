import { catalogHttp } from './http'

export async function getHomeData() {
  const { data } = await catalogHttp.get('/home')
  return data
}

export async function getProducts(params = {}) {
  const { data } = await catalogHttp.get('/products', { params })
  return data
}

// Sugerencias de búsqueda: producto con imagen + términos relevantes
export async function getSearchSuggestions(term, userId) {
  const params = { term }
  if (userId) params.user_id = userId
  const { data } = await catalogHttp.get('/search/suggestions', { params })
  return data
}

export async function getSearchHistory(userId) {
  if (!userId) {
    return { success: true, data: { terms: [] } }
  }

  const { data } = await catalogHttp.get('/search/history', {
    params: { user_id: userId },
  })
  return data
}

export async function saveSearchHistory(term, userId) {
  if (!userId || !term) {
    return { success: true }
  }

  const { data } = await catalogHttp.post('/search/history', {
    term,
    user_id: userId,
  })
  return data
}

export async function getProductBySlug(slug, params = {}) {
  const { data } = await catalogHttp.get(`/products/${slug}`, { params })
  return data
}

export async function getCategories() {
  const { data } = await catalogHttp.get('/categories')
  return data
}

export async function getCollections() {
  const { data } = await catalogHttp.get('/collections')
  return data
}
