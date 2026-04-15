import defaultAvatar from '../../assets/foundnotimages/default-avatar.png'
import defaultProduct from '../../assets/foundnotimages/default-product.jpg'
import defaultCategory from '../../assets/foundnotimages/default-category.jpg'
import defaultCollection from '../../assets/foundnotimages/default-collection.jpg'
import defaultBanner from '../../assets/foundnotimages/default-banner.jpg'
import defaultSlider from '../../assets/foundnotimages/default-slider.jpg'

const configuredUploadsBase = String(import.meta.env.VITE_UPLOADS_BASE_URL || '').replace(/\/+$/, '')
const catalogApiUrl = String(import.meta.env.VITE_CATALOG_API_URL || '').trim()

function catalogUploadsBase() {
  if (!catalogApiUrl) return ''
  return catalogApiUrl.replace(/\/api\/?$/i, '').replace(/\/+$/, '') + '/uploads'
}

const uploadsBaseCandidates = unique([
  configuredUploadsBase,
  catalogUploadsBase(),
  '/uploads',
])

const FALLBACKS = {
  avatar: defaultAvatar,
  product: defaultProduct,
  category: defaultCategory,
  collection: defaultCollection,
  banner: defaultBanner,
  slider: defaultSlider,
  brand: defaultAvatar,
}

function normalizePath(value) {
  if (typeof value !== 'string') return ''
  return value.trim().replace(/\\/g, '/')
}

function joinUrl(base, relativePath) {
  const cleanBase = String(base || '').replace(/\/+$/, '')
  const cleanRelative = String(relativePath || '').replace(/^\/+/, '')

  if (!cleanBase) return `/${cleanRelative}`

  return `${cleanBase}/${cleanRelative}`
}

function buildUploadsCandidates(relativePath) {
  return uploadsBaseCandidates.map((base) => joinUrl(base, relativePath))
}

function unique(items) {
  const seen = new Set()
  return items.filter((item) => {
    if (!item || seen.has(item)) return false
    seen.add(item)
    return true
  })
}

function buildAvatarCandidates(normalized, fallbackUrl) {
  if (!normalized) return [fallbackUrl]

  if (/^https?:\/\//i.test(normalized)) {
    return unique([normalized, fallbackUrl])
  }

  if (normalized.startsWith('/uploads/')) {
    const relative = normalized.replace(/^\/+uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      normalized,
      joinUrl('/uploads', relative),
      fallbackUrl,
    ])
  }

  if (normalized.startsWith('uploads/')) {
    const relative = normalized.replace(/^uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      joinUrl('/uploads', relative),
      `/${normalized}`,
      fallbackUrl,
    ])
  }

  if (normalized.startsWith('/images/') || normalized.startsWith('images/')) {
    return unique([
      normalized.startsWith('/') ? normalized : `/${normalized}`,
      fallbackUrl,
    ])
  }

  if (normalized.includes('/')) {
    const clean = normalized.replace(/^\/+/, '')
    const fromUsers = clean.startsWith('users/')
      ? clean
      : `users/${clean}`

    return unique([
      ...buildUploadsCandidates(fromUsers),
      joinUrl('/uploads', fromUsers),
      `/${clean}`,
      fallbackUrl,
    ])
  }

  // Compatibilidad con legacy: en users.image se guarda solo el nombre del archivo.
  return unique([
    ...buildUploadsCandidates(`users/${normalized}`),
    joinUrl('/uploads', `users/${normalized}`),
    ...buildUploadsCandidates(normalized),
    joinUrl('/uploads', normalized),
    fallbackUrl,
  ])
}

export function getFallbackMediaUrl(type = 'product') {
  return FALLBACKS[type] || FALLBACKS.product
}

export function getMediaCandidates(path, fallbackType = 'product') {
  const normalized = normalizePath(path)
  const fallbackUrl = getFallbackMediaUrl(fallbackType)

  if (!normalized) return [fallbackUrl]

  if (fallbackType === 'avatar') {
    return buildAvatarCandidates(normalized, fallbackUrl)
  }

  if (/^https?:\/\//i.test(normalized)) {
    return unique([normalized, fallbackUrl])
  }

  if (normalized.startsWith('/uploads/')) {
    const relative = normalized.replace(/^\/+uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      normalized,
      joinUrl('/uploads', relative),
      fallbackUrl,
    ])
  }

  if (normalized.startsWith('uploads/')) {
    const relative = normalized.replace(/^uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      joinUrl('/uploads', relative),
      `/${normalized}`,
      fallbackUrl,
    ])
  }

  if (normalized.startsWith('/')) {
    return unique([normalized, fallbackUrl])
  }

  return unique([`/${normalized}`, fallbackUrl])
}

export function getUploadCandidates(path) {
  const normalized = normalizePath(path)

  if (!normalized) return []

  if (/^https?:\/\//i.test(normalized)) {
    return unique([normalized])
  }

  if (normalized.startsWith('/uploads/')) {
    const relative = normalized.replace(/^\/+uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      normalized,
      joinUrl('/uploads', relative),
    ])
  }

  if (normalized.startsWith('uploads/')) {
    const relative = normalized.replace(/^uploads\/?/, '')
    return unique([
      ...buildUploadsCandidates(relative),
      joinUrl('/uploads', relative),
      `/${normalized}`,
    ])
  }

  if (normalized.startsWith('/')) {
    return unique([normalized])
  }

  return unique([
    ...buildUploadsCandidates(normalized),
    `/${normalized}`,
  ])
}

export function resolveUploadUrl(path) {
  return getUploadCandidates(path)[0] || ''
}

export function resolveMediaUrl(path, fallbackType = 'product') {
  return getMediaCandidates(path, fallbackType)[0]
}

export function handleMediaError(event, originalPath, fallbackType = 'product') {
  const target = event?.target

  if (!target || typeof target !== 'object') return

  const candidates = getMediaCandidates(originalPath, fallbackType)
  const currentIndex = Number(target.dataset.mediaFallbackIndex || '0')
  const nextIndex = Math.min(currentIndex + 1, candidates.length - 1)

  target.dataset.mediaFallbackIndex = String(nextIndex)
  target.src = candidates[nextIndex]

  if (nextIndex >= candidates.length - 1) {
    target.onerror = null
  }
}
