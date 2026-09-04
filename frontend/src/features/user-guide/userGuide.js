import introJs from 'intro.js'

export const OPEN_USER_GUIDE_EVENT = 'angelow:open-user-guide'

let activeTour = null

export function openUserGuideHub() {
  window.dispatchEvent(new CustomEvent(OPEN_USER_GUIDE_EVENT))
}

function isVisible(element) {
  if (!(element instanceof HTMLElement)) return false

  const styles = window.getComputedStyle(element)
  const rect = element.getBoundingClientRect()
  return styles.display !== 'none'
    && styles.visibility !== 'hidden'
    && Number(styles.opacity || 1) > 0
    && rect.width > 24
    && rect.height > 18
}

function normalizeLabel(value) {
  return String(value || '')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 90)
}

function elementSignature(element) {
  return `${element.tagName} ${element.className || ''} ${element.getAttribute('role') || ''}`.toLowerCase()
}

function contextualArea(element, guide) {
  const signature = elementSignature(element)
  const guideTitle = String(guide?.title || 'esta sección').toLocaleLowerCase('es')

  if (signature.includes('main-header')) {
    return {
      title: 'Encabezado de la tienda',
      intro: 'Desde el encabezado puedes buscar productos, abrir tu cuenta, revisar notificaciones y favoritos, entrar al carrito y usar el menú principal.',
    }
  }
  if (signature.includes('admin-sidebar')) {
    return {
      title: 'Menú del panel administrativo',
      intro: 'Este menú organiza las áreas de administración. Selecciona una opción para cambiar de módulo sin cerrar tu sesión.',
    }
  }
  if (signature.includes('admin-header')) {
    return {
      title: 'Barra superior del panel',
      intro: 'Aquí puedes usar la búsqueda global, revisar notificaciones, abrir acciones rápidas y controlar el menú lateral.',
    }
  }
  if (signature.includes('admin-page-header')) {
    return {
      title: `Encabezado de ${guide?.title || 'la sección'}`,
      intro: 'El encabezado identifica la pantalla actual, muestra la ruta de navegación y reúne las acciones principales disponibles.',
    }
  }
  if (signature.includes('user-sidebar')) {
    return {
      title: 'Menú de mi cuenta',
      intro: 'Desde este menú puedes entrar al resumen, pedidos, notificaciones, direcciones, favoritos y configuración de tu cuenta.',
    }
  }
  if (signature.includes('auth-topbar')) {
    return {
      title: 'Regresar al inicio',
      intro: 'Selecciona el logotipo para volver a la tienda cuando quieras salir de este proceso.',
    }
  }
  if (signature.includes('hero-banner') || signature.includes('hero-slider') || signature.includes('carousel')) {
    return {
      title: 'Slider de promociones',
      intro: 'Este slider presenta promociones y novedades destacadas. Usa las flechas o los puntos para cambiar de anuncio y selecciona el botón del contenido para abrir la promoción.',
    }
  }
  if (signature.includes('admin-stats-grid') || signature.includes('metric') || signature.includes('kpi')) {
    return {
      title: `Indicadores de ${guideTitle}`,
      intro: `Estas tarjetas resumen los datos principales de ${guideTitle}. Úsalas para reconocer rápidamente cantidades, estados o alertas antes de revisar el detalle.`,
    }
  }
  if (signature.includes('search')) {
    return {
      title: `Buscador de ${guideTitle}`,
      intro: `Escribe una palabra relacionada con ${guideTitle} para encontrar resultados concretos sin recorrer todo el contenido.`,
    }
  }
  if (signature.includes('filter')) {
    return {
      title: `Filtros de ${guideTitle}`,
      intro: 'Los filtros reducen la información visible según los criterios seleccionados. Puedes combinarlos y limpiarlos para volver al listado completo.',
    }
  }
  if (signature.includes('tablist') || signature.includes('tabs') || element.getAttribute('role') === 'tablist') {
    return {
      title: `Secciones de ${guideTitle}`,
      intro: 'Estas pestañas separan la información por temas. Selecciona una para mostrar sus campos y opciones sin abandonar la pantalla.',
    }
  }
  if (signature.includes('chart') || signature.includes('report')) {
    return {
      title: `Análisis de ${guideTitle}`,
      intro: 'Esta visualización convierte los datos actuales en una comparación fácil de leer. Revisa los filtros activos para interpretar correctamente el resultado.',
    }
  }
  if (signature.includes('pagination')) {
    return {
      title: 'Navegación entre páginas',
      intro: 'Usa estos controles para avanzar o retroceder por los resultados. Los filtros y la búsqueda se conservan mientras cambias de página.',
    }
  }
  if (signature.includes('upload') || signature.includes('gallery') || signature.includes('image')) {
    return {
      title: `Imágenes y archivos de ${guideTitle}`,
      intro: 'Aquí puedes revisar los archivos actuales y usar las acciones disponibles para seleccionar, cambiar o quitar una imagen o documento.',
    }
  }
  if (signature.includes('summary')) {
    return {
      title: `Resumen de ${guideTitle}`,
      intro: 'Este resumen reúne los datos más importantes y permite comprobarlos antes de continuar con la siguiente acción.',
    }
  }
  if (signature.includes('category')) {
    return {
      title: 'Categorías',
      intro: 'Las categorías agrupan productos relacionados. Selecciona una para abrir la tienda con ese filtro aplicado.',
    }
  }
  if (signature.includes('collection')) {
    return {
      title: 'Colecciones',
      intro: 'Las colecciones reúnen productos bajo una misma temática. Selecciona una tarjeta para explorar sus artículos.',
    }
  }
  if (signature.includes('featured-products') || signature.includes('products-grid') || signature.includes('product-list')) {
    return {
      title: 'Productos disponibles',
      intro: 'Cada tarjeta muestra la información principal de un producto. Desde allí puedes abrir el detalle y usar las acciones de compra o favoritos disponibles.',
    }
  }
  if (signature.includes('promo-banner') || signature.includes('announcement')) {
    return {
      title: 'Promoción destacada',
      intro: 'Este bloque comunica una oferta o novedad vigente. Selecciona su botón para abrir la información o productos relacionados.',
    }
  }
  if (signature.includes('cart')) {
    return {
      title: 'Productos del carrito',
      intro: 'Aquí puedes revisar lo que agregaste, cambiar cantidades, seleccionar qué productos comprar, retirar artículos y comprobar el total.',
    }
  }
  if (signature.includes('order')) {
    return {
      title: `Pedidos de ${guideTitle}`,
      intro: 'Esta sección muestra los pedidos y sus estados. Usa las acciones de cada registro para abrir el detalle o continuar su gestión.',
    }
  }
  if (signature.includes('payment')) {
    return {
      title: `Información de pago de ${guideTitle}`,
      intro: 'Aquí se muestran los datos necesarios para revisar o completar el pago, junto con su estado y las acciones permitidas.',
    }
  }
  if (signature.includes('shipping') || signature.includes('delivery')) {
    return {
      title: `Información de envío de ${guideTitle}`,
      intro: 'Esta sección reúne el método, la dirección, los tiempos y el estado de entrega para que puedas revisar el proceso de envío.',
    }
  }
  if (signature.includes('address') || signature.includes('location') || signature.includes('map')) {
    return {
      title: 'Dirección y ubicación',
      intro: 'Aquí puedes consultar o definir la dirección relacionada y verificar su ubicación antes de guardarla o usarla en una entrega.',
    }
  }
  if (signature.includes('notification')) {
    return {
      title: 'Notificaciones',
      intro: 'Las notificaciones informan cambios y acciones importantes. Puedes abrirlas para consultar el evento relacionado y marcar su lectura.',
    }
  }
  if (signature.includes('wishlist') || signature.includes('favorite')) {
    return {
      title: 'Productos favoritos',
      intro: 'Aquí aparecen los productos guardados para consultarlos después. Puedes abrir su detalle o retirarlos de favoritos.',
    }
  }
  if (signature.includes('review')) {
    return {
      title: 'Reseñas y calificaciones',
      intro: 'Esta sección muestra opiniones y puntuaciones de productos, junto con las acciones disponibles para consultarlas o moderarlas.',
    }
  }
  if (signature.includes('question')) {
    return {
      title: 'Preguntas sobre productos',
      intro: 'Aquí se reúnen las preguntas relacionadas con productos y las opciones disponibles para consultar o responder cada una.',
    }
  }
  if (signature.includes('inventory') || signature.includes('stock')) {
    return {
      title: 'Inventario y existencias',
      intro: 'Esta sección muestra las unidades disponibles y sus alertas. Úsala para identificar productos agotados o con existencias bajas.',
    }
  }
  if (signature.includes('discount') || signature.includes('coupon')) {
    return {
      title: 'Descuentos',
      intro: 'Aquí puedes consultar las condiciones del descuento, su vigencia, estado y las acciones disponibles para administrarlo o aplicarlo.',
    }
  }
  if (signature.includes('courier')) {
    return {
      title: 'Repartidores',
      intro: 'Esta sección reúne la información del repartidor, sus documentos, vehículo, estado y acciones de revisión disponibles.',
    }
  }
  if (signature.includes('action') || signature.includes('toolbar')) {
    return {
      title: `Acciones de ${guideTitle}`,
      intro: 'Estos botones permiten ejecutar las operaciones principales de la sección. Revisa los datos y el estado actual antes de confirmar un cambio.',
    }
  }
  if (element.matches('form') || signature.includes('form')) {
    return {
      title: `Formulario de ${guideTitle}`,
      intro: `Este formulario permite completar o actualizar la información de ${guideTitle}. Corrige los mensajes de validación antes de guardar o continuar.`,
    }
  }
  if (element.matches('table, [role="table"]') || signature.includes('table')) {
    return {
      title: `Tabla de ${guideTitle}`,
      intro: 'La tabla organiza cada registro en una fila. Revisa sus datos y usa los botones de la misma fila para abrir o administrar ese elemento.',
    }
  }
  if (element.matches('nav') || signature.includes('menu') || signature.includes('sidebar')) {
    return {
      title: `Navegación de ${guideTitle}`,
      intro: 'Estos enlaces permiten cambiar entre las secciones relacionadas sin recargar la aplicación.',
    }
  }

  return null
}

function elementLabel(element, guide) {
  const explicit = normalizeLabel(
    element.getAttribute('data-guide-title')
    || element.getAttribute('aria-label')
    || element.getAttribute('title'),
  )
  if (explicit) return explicit

  const contextual = contextualArea(element, guide)
  if (contextual) return contextual.title

  const heading = element.matches('h1, h2, h3, legend')
    ? element
    : element.querySelector('h1, h2, h3, legend, [class*="title"]')
  return normalizeLabel(heading?.textContent)
}

function featureExplanation(element, label, guide) {
  const contextual = contextualArea(element, guide)
  if (contextual) return contextual.intro
  if (!label) return ''

  return `${guide.description} En «${label}» encontrarás los datos y acciones relacionados con esta parte del proceso.`
}

function addCandidateSteps(steps, seen, selector, limit, guide) {
  const elements = Array.from(document.querySelectorAll(selector))

  for (const element of elements) {
    if (steps.length >= limit || !isVisible(element) || seen.has(element)) continue
    if (element.closest('.user-guide-hub, .introjs-tooltip, .introjs-overlay')) continue

    const containsSeenElement = Array.from(seen).some((seenElement) => element.contains(seenElement))
    if (containsSeenElement && !element.matches('form, table, [role="tablist"]')) continue

    const label = elementLabel(element, guide)
    const intro = element.getAttribute('data-guide-intro') || featureExplanation(element, label, guide)
    if (!label || !intro) continue

    steps.push({
      element,
      title: label,
      intro,
      position: 'auto',
    })
    seen.add(element)
  }
}

function buildTourSteps(guide, audience) {
  const steps = [{
    title: `Guía: ${guide.title}`,
    intro: guide.description,
  }]
  const seen = new Set()
  const maxSteps = 12

  addCandidateSteps(steps, seen, '[data-guide-intro]', maxSteps, guide)

  if (audience === 'admin') {
    addCandidateSteps(steps, seen, '.admin-sidebar', maxSteps, guide)
    addCandidateSteps(steps, seen, '.admin-header', maxSteps, guide)
    addCandidateSteps(steps, seen, '.admin-page-header', maxSteps, guide)
    addCandidateSteps(steps, seen, '.admin-stats-grid', maxSteps, guide)
    addCandidateSteps(steps, seen, '.admin-filter-card, .admin-results-bar', maxSteps, guide)
  } else {
    addCandidateSteps(steps, seen, '.main-header', maxSteps, guide)
    addCandidateSteps(steps, seen, '.user-sidebar', maxSteps, guide)
    addCandidateSteps(steps, seen, '.auth-topbar', maxSteps, guide)
  }

  const contentSelectors = [
    'main [role="tablist"]',
    '.dashboard-content [role="tablist"]',
    'main form',
    '.dashboard-content form',
    'main [class*="filter"]',
    '.dashboard-content [class*="filter"]',
    'main [class*="summary"]',
    '.dashboard-content [class*="summary"]',
    'main [class*="stats"]',
    '.dashboard-content [class*="stats"]',
    'main table',
    '.dashboard-content table',
    'main [class*="pagination"]',
    '.dashboard-content [class*="pagination"]',
    'main section',
    '.dashboard-content .admin-card',
    'main [class*="grid"]',
    'main [class*="actions"]',
    '.dashboard-content [class*="actions"]',
  ]

  for (const selector of contentSelectors) {
    addCandidateSteps(steps, seen, selector, maxSteps, guide)
    if (steps.length >= maxSteps) break
  }

  steps.push({
    title: 'Guía completada',
    intro: 'Ya conoces las funciones visibles de esta vista. Puedes abrir Ayuda de nuevo para consultar otra sección del manual.',
  })

  return steps
}

export async function startUserGuide(guide, audience) {
  if (!guide) return

  if (activeTour?.isActive()) {
    await activeTour.exit(true)
  }

  const tour = introJs.tour()
  activeTour = tour
  tour.setOptions({
    steps: buildTourSteps(guide, audience),
    nextLabel: 'Siguiente',
    prevLabel: 'Anterior',
    doneLabel: 'Finalizar',
    skipLabel: 'Salir',
    showProgress: true,
    showBullets: false,
    showStepNumbers: true,
    stepNumbersOfLabel: 'de',
    exitOnEsc: true,
    exitOnOverlayClick: false,
    keyboardNavigation: true,
    scrollToElement: true,
    scrollTo: 'element',
    scrollPadding: 24,
    overlayOpacity: 0.58,
    disableInteraction: false,
    tooltipClass: 'angelow-intro-tooltip',
    highlightClass: 'angelow-intro-highlight',
  })

  tour.onExit(() => {
    activeTour = null
  })
  tour.onComplete(() => {
    activeTour = null
  })

  await tour.start()
}
