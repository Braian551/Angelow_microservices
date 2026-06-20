import {
  extractMeaningfulWords,
  genderSlugLabel,
  normalizeGenderKey,
  normalizePlainText,
} from './productSlug'

const GENDER_SKU_CODES = {
  unisex: 'UNI',
  mujer: 'WMN',
  hombre: 'MEN',
  nina: 'GRL',
  nino: 'BOY',
}

function buildWordSignature(value) {
  const normalized = extractMeaningfulWords(value).join('').replace(/-/g, '')

  if (normalized.length > 4 && normalized.endsWith('es')) {
    return normalized.slice(0, -2)
  }

  if (normalized.length > 3 && normalized.endsWith('s')) {
    return normalized.slice(0, -1)
  }

  return normalized
}

function genderSkuCode(value) {
  return GENDER_SKU_CODES[normalizeGenderKey(value)] || 'UNI'
}

function buildCodeSegment(value, length, fallback) {
  const words = extractMeaningfulWords(value)

  if (!words.length) {
    return fallback
  }

  if (words.length === 1) {
    const singleWord = words[0].replace(/[^a-z0-9]/g, '')
    if (!singleWord) {
      return fallback
    }

    return singleWord.substring(0, length).toUpperCase().padEnd(length, singleWord.charAt(0).toUpperCase())
  }

  const compact = words.map((word) => word.slice(0, 2)).join('').replace(/[^a-z0-9]/g, '')
  if (!compact) {
    return fallback
  }

  return compact.substring(0, length).toUpperCase().padEnd(length, compact.charAt(0).toUpperCase())
}

function buildStyleSource({ brand, categorySource, gender, name }) {
  const excludedSignatures = new Set([
    ...extractMeaningfulWords(brand),
    ...extractMeaningfulWords(categorySource),
    ...extractMeaningfulWords(genderSlugLabel(gender)),
  ].map((word) => buildWordSignature(word)))

  const styleWords = extractMeaningfulWords(name)
    .filter((word) => !excludedSignatures.has(buildWordSignature(word)))

  return styleWords.join(' ') || name
}

export function normalizeSkuValue(value) {
  return normalizePlainText(value)
    .toUpperCase()
    .replace(/[^A-Z0-9-]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
}

export function shouldTreatExistingSkuAsManual(value) {
  const normalizedSku = normalizeSkuValue(value)
  return Boolean(normalizedSku) && normalizedSku !== '-' && normalizedSku.length > 2
}

export function buildGeneratedSku({ brand, categorySource, colorName, gender, name, sizeName }) {
  if (!String(name || '').trim() || !categorySource || !colorName || !sizeName) {
    return ''
  }

  const normalizedSize = normalizePlainText(sizeName)
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')

  return [
    buildCodeSegment(brand || 'Angelow', 3, 'ANG'),
    buildCodeSegment(categorySource, 4, 'CATG'),
    genderSkuCode(gender),
    buildCodeSegment(buildStyleSource({ brand, categorySource, gender, name }), 4, 'MODE'),
    buildCodeSegment(colorName, 3, 'COL'),
    normalizedSize ? normalizedSize.substring(0, 4) : 'TAL',
  ].join('-')
}
