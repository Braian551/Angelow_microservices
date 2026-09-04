const guide = (name, title, description, group, audience, options = {}) => ({
  name,
  title,
  description,
  group,
  audience,
  icon: options.icon || 'fas fa-circle-question',
  contextOnly: Boolean(options.contextOnly),
})

export const USER_GUIDE_GROUPS = {
  start: 'Primeros pasos',
  store: 'Tienda y compra',
  account: 'Mi cuenta',
  checkout: 'Finalizar compra',
  adminOverview: 'Panel administrativo',
  adminCatalog: 'Catálogo e inventario',
  adminOrders: 'Pedidos y clientes',
  adminOperations: 'Pagos, envíos y postventa',
  adminMarketing: 'Descuentos y contenido',
  adminSettings: 'Informes y configuración',
}

export const USER_GUIDES = [
  guide('home', 'Inicio', 'Conoce la portada, sus categorías, productos destacados, promociones y colecciones.', 'start', ['guest', 'client'], { icon: 'fas fa-house' }),
  guide('login', 'Iniciar sesión', 'Aprende a identificar tu cuenta, ingresar la contraseña, recuperar el acceso o continuar con Google.', 'start', ['guest'], { icon: 'fas fa-right-to-bracket' }),
  guide('register', 'Crear una cuenta', 'Recorre el registro, la verificación del correo y los datos necesarios para crear una cuenta de cliente.', 'start', ['guest'], { icon: 'fas fa-user-plus' }),
  guide('forgot-password', 'Recuperar la cuenta', 'Solicita y valida el código de recuperación para establecer una contraseña nueva.', 'start', ['guest'], { icon: 'fas fa-key' }),
  guide('admin-forgot-password', 'Recuperar acceso administrativo', 'Explica el proceso seguro para recuperar el acceso al panel administrativo.', 'start', ['guest'], { icon: 'fas fa-shield-halved', contextOnly: true }),
  guide('terms-and-conditions', 'Términos y condiciones', 'Consulta las condiciones de uso, compra, pago, envío, postventa y tratamiento de datos.', 'start', ['guest', 'client'], { icon: 'fas fa-file-contract' }),

  guide('store', 'Explorar la tienda', 'Usa filtros, ordenamiento, búsqueda, tarjetas de producto y paginación para encontrar prendas.', 'store', ['guest', 'client'], { icon: 'fas fa-store' }),
  guide('collections', 'Explorar colecciones', 'Descubre las colecciones disponibles y abre sus productos relacionados.', 'store', ['guest', 'client'], { icon: 'fas fa-layer-group' }),
  guide('product', 'Detalle de producto', 'Revisa imágenes, precio, variantes, disponibilidad, reseñas, preguntas, favoritos y acciones de compra.', 'store', ['guest', 'client'], { icon: 'fas fa-shirt', contextOnly: true }),
  guide('cart', 'Carrito de compras', 'Gestiona productos, cantidades, selección para compra, descuentos, totales y avance al checkout.', 'store', ['guest', 'client'], { icon: 'fas fa-cart-shopping' }),

  guide('account-dashboard', 'Resumen de mi cuenta', 'Consulta el resumen de pedidos, notificaciones, direcciones, favoritos y accesos rápidos.', 'account', ['client'], { icon: 'fas fa-gauge-high' }),
  guide('account-orders', 'Mis pedidos', 'Consulta pedidos, estados, pagos, seguimiento, reembolsos y acceso a cada detalle.', 'account', ['client'], { icon: 'fas fa-box' }),
  guide('account-order-detail', 'Detalle de mi pedido', 'Revisa productos, estado, pago, envío, factura, seguimiento y código de entrega del pedido actual.', 'account', ['client'], { icon: 'fas fa-receipt', contextOnly: true }),
  guide('account-notifications', 'Mis notificaciones', 'Lee, filtra y marca notificaciones relacionadas con tu cuenta y tus compras.', 'account', ['client'], { icon: 'fas fa-bell' }),
  guide('account-addresses', 'Mis direcciones', 'Crea, edita, ubica en el mapa, selecciona como principal y elimina direcciones guardadas.', 'account', ['client'], { icon: 'fas fa-location-dot' }),
  guide('account-wishlist', 'Mis favoritos', 'Consulta productos guardados, abre su detalle o retíralos de favoritos.', 'account', ['client'], { icon: 'fas fa-heart' }),
  guide('account-settings', 'Configuración de mi cuenta', 'Actualiza perfil, seguridad, avatar y preferencias de comunicación de la cuenta.', 'account', ['client'], { icon: 'fas fa-user-gear' }),

  guide('shipping', 'Dirección y envío', 'Selecciona la dirección, el método de entrega y revisa el resumen antes de continuar.', 'checkout', ['client'], { icon: 'fas fa-truck' }),
  guide('payment', 'Pago y comprobante', 'Selecciona la forma de pago, revisa los datos bancarios, adjunta el comprobante y confirma las condiciones.', 'checkout', ['client'], { icon: 'fas fa-credit-card' }),
  guide('confirmation', 'Confirmación de compra', 'Comprueba el resultado de la compra, el número de pedido y los siguientes pasos.', 'checkout', ['client'], { icon: 'fas fa-circle-check' }),

  guide('admin-dashboard', 'Dashboard administrativo', 'Interpreta métricas, alertas, gráficas, órdenes recientes, búsqueda global y acciones rápidas.', 'adminOverview', ['admin'], { icon: 'fas fa-gauge' }),
  guide('admin-products', 'Productos', 'Busca, filtra, consulta, exporta y administra los productos del catálogo.', 'adminCatalog', ['admin'], { icon: 'fas fa-shirt' }),
  guide('admin-product-create', 'Crear producto', 'Completa información general, imágenes, variantes, precios y disponibilidad de un producto nuevo.', 'adminCatalog', ['admin'], { icon: 'fas fa-circle-plus' }),
  guide('admin-product-edit', 'Editar producto', 'Actualiza información, imágenes, variantes, precios y disponibilidad del producto actual.', 'adminCatalog', ['admin'], { icon: 'fas fa-pen-to-square', contextOnly: true }),
  guide('admin-categories', 'Categorías', 'Crea, busca, edita, activa o elimina categorías del catálogo.', 'adminCatalog', ['admin'], { icon: 'fas fa-tags' }),
  guide('admin-collections', 'Colecciones', 'Crea, organiza, publica y actualiza colecciones de productos.', 'adminCatalog', ['admin'], { icon: 'fas fa-layer-group' }),
  guide('admin-sizes', 'Tallas', 'Gestiona tallas, su orden visual, estado y uso dentro de las variantes.', 'adminCatalog', ['admin'], { icon: 'fas fa-ruler' }),
  guide('admin-inventory', 'Inventario', 'Consulta existencias, filtra variantes, identifica alertas y ajusta el stock disponible.', 'adminCatalog', ['admin'], { icon: 'fas fa-boxes-stacked' }),

  guide('admin-orders', 'Órdenes', 'Busca, filtra, exporta y gestiona estados, pagos y acciones de las órdenes.', 'adminOrders', ['admin'], { icon: 'fas fa-box-open' }),
  guide('admin-order-detail', 'Detalle de orden', 'Revisa cliente, productos, direcciones, pago, envío, historial y acciones de la orden actual.', 'adminOrders', ['admin'], { icon: 'fas fa-clipboard-list', contextOnly: true }),
  guide('admin-customers', 'Clientes', 'Consulta, busca, filtra y administra la información visible de los clientes.', 'adminOrders', ['admin'], { icon: 'fas fa-users' }),
  guide('admin-reviews', 'Reseñas', 'Modera reseñas, consulta calificaciones y administra su estado de publicación.', 'adminOrders', ['admin'], { icon: 'fas fa-star' }),
  guide('admin-questions', 'Preguntas de productos', 'Consulta preguntas, responde al cliente y controla su publicación.', 'adminOrders', ['admin'], { icon: 'fas fa-comments' }),

  guide('admin-payments', 'Pagos', 'Consulta transacciones, comprobantes, estados y acciones de validación de pagos.', 'adminOperations', ['admin'], { icon: 'fas fa-money-check-dollar' }),
  guide('admin-refunds', 'Reembolsos', 'Revisa solicitudes, evidencia, motivos, estados y decisiones de reembolso.', 'adminOperations', ['admin'], { icon: 'fas fa-rotate-left' }),
  guide('admin-invoices', 'Facturas', 'Busca, consulta, descarga, reenvía y administra facturas de pedidos.', 'adminOperations', ['admin'], { icon: 'fas fa-file-invoice-dollar' }),
  guide('admin-shipping-rules', 'Reglas de envío', 'Crea y administra reglas de precio, cobertura y condiciones de envío.', 'adminOperations', ['admin'], { icon: 'fas fa-route' }),
  guide('admin-shipping-methods', 'Métodos de envío', 'Configura métodos, tiempos, costos, disponibilidad y puntos de recogida.', 'adminOperations', ['admin'], { icon: 'fas fa-truck-fast' }),
  guide('admin-couriers', 'Repartidores', 'Revisa solicitudes, documentos, vehículos, estados y decisiones sobre repartidores.', 'adminOperations', ['admin'], { icon: 'fas fa-motorcycle' }),
  guide('admin-deliveries', 'Entregas asignadas', 'Consulta asignaciones, repartidores, estados de ruta y datos de entrega.', 'adminOperations', ['admin'], { icon: 'fas fa-map-location-dot' }),

  guide('admin-bulk-discounts', 'Descuentos por cantidad', 'Configura reglas de descuento según cantidades y condiciones de compra.', 'adminMarketing', ['admin'], { icon: 'fas fa-percent' }),
  guide('admin-discount-codes', 'Códigos de descuento', 'Crea, filtra, activa, desactiva y consulta el uso de códigos promocionales.', 'adminMarketing', ['admin'], { icon: 'fas fa-ticket' }),
  guide('admin-discount-codes-specific-campaign', 'Campañas específicas', 'Selecciona destinatarios y configura campañas de descuentos dirigidas.', 'adminMarketing', ['admin'], { icon: 'fas fa-bullseye' }),
  guide('admin-announcements', 'Anuncios', 'Crea, programa, segmenta y administra anuncios para clientes.', 'adminMarketing', ['admin'], { icon: 'fas fa-bullhorn' }),
  guide('admin-sliders', 'Sliders', 'Sube imágenes, organiza el orden, configura enlaces y publica sliders de portada.', 'adminMarketing', ['admin'], { icon: 'fas fa-images' }),

  guide('admin-reports', 'Informes', 'Cambia de informe, aplica filtros, analiza gráficas, abre detalles y exporta resultados.', 'adminSettings', ['admin'], { icon: 'fas fa-chart-line' }),
  guide('admin-reports-sales', 'Informe de ventas', 'Analiza ingresos, órdenes, ticket promedio, métodos de pago y evolución por período.', 'adminSettings', ['admin'], { icon: 'fas fa-chart-column' }),
  guide('admin-reports-products', 'Informe de productos', 'Analiza productos vendidos, ingresos asociados y alertas de inventario.', 'adminSettings', ['admin'], { icon: 'fas fa-chart-pie' }),
  guide('admin-reports-customers', 'Informe de clientes', 'Analiza recurrencia, valor acumulado, promedios y clientes destacados.', 'adminSettings', ['admin'], { icon: 'fas fa-users-viewfinder' }),
  guide('admin-settings', 'Configuración general', 'Administra identidad, contacto, operaciones, redes sociales y accede al manual completo.', 'adminSettings', ['admin'], { icon: 'fas fa-gears' }),
  guide('admin-settings-general', 'Configuración general', 'Administra identidad, contacto, operaciones, redes sociales y accede al manual completo.', 'adminSettings', ['admin'], { icon: 'fas fa-gears' }),
  guide('admin-administrators', 'Administradores', 'Consulta, crea, actualiza y controla el acceso de administradores.', 'adminSettings', ['admin'], { icon: 'fas fa-user-shield' }),
]

export function findUserGuide(routeName) {
  return USER_GUIDES.find((item) => item.name === String(routeName || '')) || null
}

export function getUserGuidesForAudience(audience, currentRouteName) {
  return USER_GUIDES.filter((item) => (
    item.audience.includes(audience)
    && (!item.contextOnly || item.name === currentRouteName)
  ))
}
