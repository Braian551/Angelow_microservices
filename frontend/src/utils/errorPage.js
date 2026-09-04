// Catálogo de estados que pueden presentarse como una pantalla completa de error.
export const ERROR_PAGE_DEFINITIONS = Object.freeze({
  400: {
    eyebrow: 'La solicitud no pudo continuar',
    title: 'Solicitud no válida',
    description: 'No pudimos procesar esta solicitud. Revisa los datos e inténtalo nuevamente.',
    icon: 'fa-file-circle-exclamation',
    tone: 'warning',
    action: 'home',
  },
  401: {
    eyebrow: 'Necesitamos verificar tu sesión',
    title: 'Tu sesión requiere atención',
    description: 'Inicia sesión para continuar con esta sección.',
    icon: 'fa-lock',
    tone: 'accent',
    action: 'login',
  },
  403: {
    eyebrow: 'Esta sección es privada',
    title: 'Acceso restringido',
    description: 'No tienes permisos para ver este contenido.',
    icon: 'fa-ban',
    tone: 'danger',
    action: 'home',
  },
  404: {
    eyebrow: 'La página no está aquí',
    title: 'No encontramos esa página',
    description: 'La dirección puede estar escrita de forma incorrecta o el contenido ya no está disponible.',
    icon: 'fa-magnifying-glass',
    tone: 'accent',
    action: 'home',
  },
  408: {
    eyebrow: 'La respuesta tardó demasiado',
    title: 'Se agotó el tiempo de espera',
    description: 'La conexión tardó más de lo esperado. Intenta nuevamente.',
    icon: 'fa-hourglass-half',
    tone: 'warning',
    action: 'retry',
  },
  429: {
    eyebrow: 'Vamos un poco rápido',
    title: 'Demasiadas solicitudes',
    description: 'Espera unos instantes e inténtalo de nuevo.',
    icon: 'fa-gauge-high',
    tone: 'warning',
    action: 'retry',
  },
  500: {
    eyebrow: 'Algo no salió como esperábamos',
    title: 'Tuvimos un problema',
    description: 'No pudimos completar la solicitud. Intenta nuevamente en unos momentos.',
    icon: 'fa-circle-exclamation',
    tone: 'danger',
    action: 'retry',
  },
  502: {
    eyebrow: 'La conexión con el servicio se interrumpió',
    title: 'No pudimos obtener una respuesta',
    description: 'El servicio no respondió correctamente. Intenta nuevamente en unos momentos.',
    icon: 'fa-link-slash',
    tone: 'warning',
    action: 'retry',
  },
  503: {
    eyebrow: 'El servicio está temporalmente ocupado',
    title: 'Volveremos en un momento',
    description: 'Estamos atendiendo muchas solicitudes o realizando una tarea temporal. Intenta nuevamente pronto.',
    icon: 'fa-server',
    tone: 'accent',
    action: 'retry',
  },
  504: {
    eyebrow: 'La respuesta no llegó a tiempo',
    title: 'La conexión tardó demasiado',
    description: 'No recibimos una respuesta a tiempo. Intenta nuevamente en unos momentos.',
    icon: 'fa-clock',
    tone: 'warning',
    action: 'retry',
  },
})

// Convierte valores externos en un estado soportado sin mostrar códigos desconocidos en la interfaz.
export function normalizeErrorPageStatus(status) {
  const numericStatus = Number.parseInt(String(status || ''), 10)

  if (ERROR_PAGE_DEFINITIONS[numericStatus]) {
    return numericStatus
  }

  return numericStatus >= 500 && numericStatus < 600 ? 500 : 404
}

export function getErrorPageDefinition(status) {
  return ERROR_PAGE_DEFINITIONS[normalizeErrorPageStatus(status)]
}

// Solo permite volver a rutas internas para evitar redirecciones hacia sitios externos.
export function getSafeInternalPath(value) {
  const path = String(value || '').trim()

  if (!path.startsWith('/') || path.startsWith('//') || path.startsWith('/error/')) {
    return ''
  }

  return path
}

// Envía al usuario a la pantalla global de error conservando, cuando es seguro, la ruta que falló.
export function navigateToErrorPage(router, status, { from = '' } = {}) {
  if (!router) return Promise.resolve()

  const currentRoute = router.currentRoute?.value
  if (currentRoute?.meta?.layout === 'error') {
    return Promise.resolve()
  }

  const normalizedStatus = normalizeErrorPageStatus(status)
  const originPath = getSafeInternalPath(from || currentRoute?.fullPath)
  const target = {
    name: 'error-status',
    params: { status: String(normalizedStatus) },
  }

  if (originPath) {
    target.query = { from: originPath }
  }

  return router.replace(target)
}
