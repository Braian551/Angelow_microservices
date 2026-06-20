const SPANISH_STOPWORDS = new Set(['a', 'al', 'con', 'de', 'del', 'el', 'en', 'la', 'las', 'los', 'para', 'por', 'sin', 'un', 'una', 'y'])

const GENDER_SLUG_LABELS = {
  unisex: 'unisex',
  mujer: 'mujer',
  hombre: 'hombre',
  nina: 'nina',
  nino: 'nino',
}

// Normaliza texto para reutilizar la regla original de slugs sin acentos ni símbolos.
export function normalizePlainText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

// Genera slugs estables a partir de texto visible, igual que lo hacía la página original.
export function slugifyText(value) {
  return normalizePlainText(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
}

function buildWordSignature(value) {
  const normalized = slugifyText(value).replace(/-/g, '')

  if (normalized.length > 4 && normalized.endsWith('es')) {
    return normalized.slice(0, -2)
  }

  if (normalized.length > 3 && normalized.endsWith('s')) {
    return normalized.slice(0, -1)
  }

  return normalized
}

export function extractMeaningfulWords(value) {
  return slugifyText(value)
    .split('-')
    .filter(Boolean)
    .filter((word) => !SPANISH_STOPWORDS.has(word))
}

export function normalizeGenderKey(value) {
  const normalized = slugifyText(value).replace(/-/g, '')
  return GENDER_SLUG_LABELS[normalized] ? normalized : 'unisex'
}

export function genderSlugLabel(value) {
  return GENDER_SLUG_LABELS[normalizeGenderKey(value)] || 'unisex'
}

// Evita repetir palabras equivalentes entre categoría, nombre y género al construir el slug.
export function uniqueMeaningfulWords(sources) {
  const seen = new Set()
  const words = []

  for (const source of sources) {
    for (const word of extractMeaningfulWords(source)) {
      const signature = buildWordSignature(word)
      if (!signature || seen.has(signature)) {
        continue
      }

      seen.add(signature)
      words.push(word)
    }
  }

  return words
}

export function buildGeneratedProductSlug({ categorySource, gender, name }) {
  if (!String(name || '').trim()) {
    return ''
  }

  const words = uniqueMeaningfulWords([
    categorySource,
    name,
    genderSlugLabel(gender),
  ])

  return words.length ? words.join('-') : slugifyText(name)
}

export function shouldTreatExistingSlugAsManual({ generatedSlug, name, value }) {
  const normalizedSlug = slugifyText(value)
  if (!normalizedSlug) {
    return false
  }

  const basicNameSlug = slugifyText(name)
  return normalizedSlug !== generatedSlug && normalizedSlug !== basicNameSlug
}
