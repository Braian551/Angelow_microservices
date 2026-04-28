const DIRECT_REPLACEMENTS = [
  ['\u00C3\u00A1', '\u00E1'],
  ['\u00C3\u00A9', '\u00E9'],
  ['\u00C3\u00AD', '\u00ED'],
  ['\u00C3\u00B3', '\u00F3'],
  ['\u00C3\u00BA', '\u00FA'],
  ['\u00C3\u0081', '\u00C1'],
  ['\u00C3\u0089', '\u00C9'],
  ['\u00C3\u008D', '\u00CD'],
  ['\u00C3\u0093', '\u00D3'],
  ['\u00C3\u009A', '\u00DA'],
  ['\u00C3\u00B1', '\u00F1'],
  ['\u00C3\u0091', '\u00D1'],
  ['\u00C2\u00BF', '\u00BF'],
  ['\u00C2\u00A1', '\u00A1'],
  ['\u00C2', ''],
]

const WORD_REPLACEMENTS = [
  [/\?\?Oferta/g, '\u00A1Oferta'],
  [/\?\?Compra/g, '\u00A1Compra'],
  [/\?\?ltimas/g, '\u00DAltimas'],
  [/\?\?ltima/g, '\u00DAltima'],
  [/Ni\?{1,2}as/g, 'Ni\u00F1as'],
  [/ni\?{1,2}as/g, 'ni\u00F1as'],
  [/Ni\?{1,2}os/g, 'Ni\u00F1os'],
  [/ni\?{1,2}os/g, 'ni\u00F1os'],
  [/Ni\?{1,2}a/g, 'Ni\u00F1a'],
  [/ni\?{1,2}a/g, 'ni\u00F1a'],
  [/Ni\?{1,2}o/g, 'Ni\u00F1o'],
  [/ni\?{1,2}o/g, 'ni\u00F1o'],
  [/Beb\?{1,2}s/g, 'Beb\u00E9s'],
  [/beb\?{1,2}s/g, 'beb\u00E9s'],
  [/Colecci\?{1,2}n/g, 'Colecci\u00F3n'],
  [/colecci\?{1,2}n/g, 'colecci\u00F3n'],
  [/Cl\?{1,2}sica/g, 'Cl\u00E1sica'],
  [/cl\?{1,2}sica/g, 'cl\u00E1sica'],
  [/M\?{1,2}gico/g, 'M\u00E1gico'],
  [/m\?{1,2}gico/g, 'm\u00E1gico'],
  [/Sue\?{1,2}os/g, 'Sue\u00F1os'],
  [/sue\?{1,2}os/g, 'sue\u00F1os'],
  [/Ba\?{1,2}o/g, 'Ba\u00F1o'],
  [/ba\?{1,2}o/g, 'ba\u00F1o'],
  [/Dise\?{1,2}os/g, 'Dise\u00F1os'],
  [/dise\?{1,2}os/g, 'dise\u00F1os'],
  [/C\?{1,2}moda/g, 'C\u00F3moda'],
  [/c\?{1,2}moda/g, 'c\u00F3moda'],
  [/Peque\?{1,2}os/g, 'Peque\u00F1os'],
  [/peque\?{1,2}os/g, 'peque\u00F1os'],
  [/V\?{1,2}lido/g, 'V\u00E1lido'],
  [/v\?{1,2}lido/g, 'v\u00E1lido'],
  [/A\?{1,2}adir/g, 'A\u00F1adir'],
  [/a\?{1,2}adir/g, 'a\u00F1adir'],
  [/Informaci\?{1,2}n/g, 'Informaci\u00F3n'],
  [/informaci\?{1,2}n/g, 'informaci\u00F3n'],
  [/Gu\?{1,2}a/g, 'Gu\u00EDa'],
  [/gu\?{1,2}a/g, 'gu\u00EDa'],
  [/Env\?{1,2}os/g, 'Env\u00EDos'],
  [/env\?{1,2}os/g, 'env\u00EDos'],
  [/T\?{1,2}rminos/g, 'T\u00E9rminos'],
  [/t\?{1,2}rminos/g, 't\u00E9rminos'],
  [/Pol\?{1,2}tica/g, 'Pol\u00EDtica'],
  [/pol\?{1,2}tica/g, 'pol\u00EDtica'],
]

function applyDirectReplacements(value) {
  return DIRECT_REPLACEMENTS.reduce(
    (result, [from, to]) => result.split(from).join(to),
    value,
  )
}

export function normalizeUtf8Text(value) {
  if (typeof value !== 'string') return value

  const normalized = applyDirectReplacements(value)

  return WORD_REPLACEMENTS.reduce(
    (result, [pattern, replacement]) => result.replace(pattern, replacement),
    normalized,
  )
}

export function normalizeUtf8Data(payload) {
  if (Array.isArray(payload)) {
    return payload.map((item) => normalizeUtf8Data(item))
  }

  if (payload && typeof payload === 'object') {
    return Object.fromEntries(
      Object.entries(payload).map(([key, value]) => [key, normalizeUtf8Data(value)]),
    )
  }

  return normalizeUtf8Text(payload)
}
