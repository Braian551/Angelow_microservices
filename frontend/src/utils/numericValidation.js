const POSITIVE_INTEGER_MESSAGE = 'La cantidad debe ser un número entero mayor o igual a 1.'
const COP_PRICE_MESSAGE = 'El precio debe ser un número entero en pesos colombianos, mayor o igual a 1.'

// Convierte cualquier entrada a texto limpio antes de validar.
function cleanValue(value) {
  return String(value ?? '').trim()
}

// Verifica que el valor represente un entero positivo sin decimales.
export function isPositiveIntegerValue(value) {
  const clean = cleanValue(value)
  return /^[1-9]\d*$/.test(clean)
}

// Valida cantidades positivas y devuelve estructura uniforme para formularios.
export function validatePositiveInteger(value, message = POSITIVE_INTEGER_MESSAGE) {
  if (!isPositiveIntegerValue(value)) {
    return { valid: false, value: null, message }
  }

  return { valid: true, value: Number(cleanValue(value)), message: '' }
}

// Normaliza enteros positivos o retorna null si el dato no es válido.
export function normalizePositiveInteger(value) {
  const result = validatePositiveInteger(value)
  return result.valid ? result.value : null
}

// Limpia entradas de precio COP conservando puntos de miles para validar agrupación.
export function normalizeCopInput(value) {
  const clean = cleanValue(value)
  if (!clean) return ''

  // Se conserva el punto visual para validar que realmente esté agrupando miles.
  return clean
    .replace(/\s/g, '')
    .replace(/^\$/, '')
}

// Valida precios enteros en COP con o sin separador de miles.
export function validateCopPrice(value, message = COP_PRICE_MESSAGE) {
  const displayValue = normalizeCopInput(value)
  const hasValidGrouping = /^[1-9]\d*$/.test(displayValue) || /^[1-9]\d{0,2}(\.\d{3})+$/.test(displayValue)
  const normalized = displayValue.replace(/\./g, '')

  if (!hasValidGrouping || !isPositiveIntegerValue(normalized)) {
    return { valid: false, value: null, normalized, message }
  }

  return { valid: true, value: Number(normalized), normalized, message: '' }
}

// Formatea un valor monetario a pesos colombianos para presentación.
export function formatCopPrice(value) {
  const result = validateCopPrice(value)
  const amount = result.valid ? result.value : Number(value || 0)

  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number.isFinite(amount) ? amount : 0)
}

// Mensajes compartidos para mantener validaciones consistentes entre formularios.
export const numericValidationMessages = {
  positiveInteger: POSITIVE_INTEGER_MESSAGE,
  copPrice: COP_PRICE_MESSAGE,
}
