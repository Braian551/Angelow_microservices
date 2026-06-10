const POSITIVE_INTEGER_MESSAGE = 'La cantidad debe ser un número entero mayor o igual a 1.'
const COP_PRICE_MESSAGE = 'El precio debe ser un número entero en pesos colombianos, mayor o igual a 1.'

function cleanValue(value) {
  return String(value ?? '').trim()
}

export function isPositiveIntegerValue(value) {
  const clean = cleanValue(value)
  return /^[1-9]\d*$/.test(clean)
}

export function validatePositiveInteger(value, message = POSITIVE_INTEGER_MESSAGE) {
  if (!isPositiveIntegerValue(value)) {
    return { valid: false, value: null, message }
  }

  return { valid: true, value: Number(cleanValue(value)), message: '' }
}

export function normalizePositiveInteger(value) {
  const result = validatePositiveInteger(value)
  return result.valid ? result.value : null
}

export function normalizeCopInput(value) {
  const clean = cleanValue(value)
  if (!clean) return ''

  // Se conserva el punto visual para validar que realmente esté agrupando miles.
  return clean
    .replace(/\s/g, '')
    .replace(/^\$/, '')
}

export function validateCopPrice(value, message = COP_PRICE_MESSAGE) {
  const displayValue = normalizeCopInput(value)
  const hasValidGrouping = /^[1-9]\d*$/.test(displayValue) || /^[1-9]\d{0,2}(\.\d{3})+$/.test(displayValue)
  const normalized = displayValue.replace(/\./g, '')

  if (!hasValidGrouping || !isPositiveIntegerValue(normalized)) {
    return { valid: false, value: null, normalized, message }
  }

  return { valid: true, value: Number(normalized), normalized, message: '' }
}

export function formatCopPrice(value) {
  const result = validateCopPrice(value)
  const amount = result.valid ? result.value : Number(value || 0)

  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number.isFinite(amount) ? amount : 0)
}

export const numericValidationMessages = {
  positiveInteger: POSITIVE_INTEGER_MESSAGE,
  copPrice: COP_PRICE_MESSAGE,
}
