import { catalogHttp } from './http'

export async function getHomeData() {
  const { data } = await catalogHttp.get('/home')
  return data
}

export async function getProducts(params = {}) {
  const { data } = await catalogHttp.get('/products', { params })
  return data
}

export async function getProductBySlug(slug) {
  const { data } = await catalogHttp.get(`/products/${slug}`)
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
