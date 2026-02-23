import axios from 'axios';

/**
 * Axios instance configured for the Catalog API (catalog-service).
 */
const catalogApi = axios.create({
    baseURL: import.meta.env.VITE_CATALOG_API_URL || 'http://localhost:8002/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000,
});

// ── Home (aggregated) ───────────────────────────────────────────────────

/**
 * Get all home page data in one call (settings, sliders, top_bar, promo_banner).
 */
export const getHomeData = () =>
    catalogApi.get('/home').then(res => res.data);

// ── Site Content ────────────────────────────────────────────────────────

export const getSettings = () =>
    catalogApi.get('/settings').then(res => res.data);

export const getSliders = () =>
    catalogApi.get('/sliders').then(res => res.data);

// ── Products ────────────────────────────────────────────────────────────

export const getProducts = (params = {}) =>
    catalogApi.get('/products', { params }).then(res => res.data);

export const getProductBySlug = (slug) =>
    catalogApi.get(`/products/${slug}`).then(res => res.data);

// ── Categories & Collections ────────────────────────────────────────────

export const getCategories = () =>
    catalogApi.get('/categories').then(res => res.data);

export const getCollections = () =>
    catalogApi.get('/collections').then(res => res.data);

// ── Wishlist ────────────────────────────────────────────────────────────

export const toggleWishlist = (userId, productId) =>
    catalogApi.post('/wishlist/toggle', { user_id: userId, product_id: productId }).then(res => res.data);

export const getWishlist = (userId) =>
    catalogApi.get('/wishlist', { params: { user_id: userId } }).then(res => res.data);

export default catalogApi;
