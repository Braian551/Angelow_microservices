<template>
  <!--
    Componente AccountShimmer: esqueleto de carga (shimmer) para la sección de cuenta.
    Muestra una animación de carga personalizada según la variante seleccionada,
    mejorando la experiencia de usuario mientras se cargan los datos reales.
  -->
  <!--
    Contenedor principal del shimmer.
    - Se le asigna una clase dinámica según la variante para estilos específicos.
    - aria-busy="true" indica a los lectores de pantalla que el contenido está cargando.
    - aria-label proporciona una descripción accesible del estado del componente.
  -->
  <div class="account-shimmer" :class="`account-shimmer--${variant}`" aria-busy="true" aria-label="Cargando contenido">
    <!--
      Variante 'dashboard': esqueleto para la página principal de la cuenta.
      Incluye encabezado, tarjetas de resumen, lista de pedidos recientes y productos destacados.
    -->
    <template v-if="variant === 'dashboard'">
      <!-- Sección de encabezado del dashboard -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de resumen: muestra tarjetas con información clave (ej: total de pedidos, saldo, etc.).
        Se iteran 3 tarjetas de ejemplo para simular la carga.
      -->
      <section class="dashboard-summary account-shimmer__summary">
        <!--
          Ciclo v-for: genera 3 tarjetas de resumen.
          - summaryCards contiene [1, 2, 3] para crear 3 elementos.
          - La clave única combina el prefijo 'dashboard-summary-' con el índice.
          - Cada tarjeta contiene un ícono circular y líneas de texto simuladas.
        -->
        <article
          v-for="card in summaryCards"
          :key="`dashboard-summary-${card}`"
          class="summary-card account-shimmer__summary-card"
        >
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
            <span class="account-shimmer__line account-shimmer__line--link" />
          </div>
        </article>
      </section>

      <!--
        Sección de pedidos recientes: lista de tarjetas de pedido.
        Cada tarjeta simula información de un pedido con estado, precio y botón de acción.
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__line account-shimmer__line--link" />
        </header>

        <!--
          Lista de pedidos: se iteran 3 tarjetas de pedido.
          - primaryRows contiene [1, 2, 3] para crear 3 filas de pedidos.
          - Cada tarjeta incluye encabezado, estado, precio y botón de acción.
        -->
        <div class="account-shimmer__list">
          <article v-for="order in primaryRows" :key="`dashboard-order-${order}`" class="account-shimmer__order-card">
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__stack">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--small" />
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--status" />
            </div>

            <div class="account-shimmer__row account-shimmer__row--between">
              <span class="account-shimmer__line account-shimmer__line--small" />
              <span class="account-shimmer__line account-shimmer__line--price" />
            </div>

            <span class="account-shimmer__btn" />
          </article>
        </div>
      </section>

      <!--
        Sección de productos destacados: cuadrícula de tarjetas de producto.
        Simula una lista de productos con imagen, nombre, precio y botón de acción.
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__line account-shimmer__line--link" />
        </header>

        <!--
          Cuadrícula de productos: se iteran 4 tarjetas de producto.
          - productCards contiene [1, 2, 3, 4] para crear 4 productos.
          - Cada tarjeta incluye imagen simulada (rectángulo), líneas de texto y botón.
        -->
        <div class="account-shimmer__product-grid">
          <article v-for="product in productCards" :key="`dashboard-product-${product}`" class="account-shimmer__product-card">
            <span class="account-shimmer__rect account-shimmer__rect--product" />
            <span class="account-shimmer__line account-shimmer__line--text" />
            <span class="account-shimmer__line account-shimmer__line--heading account-shimmer__line--wide" />
            <span class="account-shimmer__line account-shimmer__line--price" />
            <span class="account-shimmer__btn account-shimmer__btn--full" />
          </article>
        </div>
      </section>
    </template>

    <!--
      Variante 'orders': esqueleto para la página de historial de pedidos.
      Muestra una lista de pedidos con más detalles que la vista del dashboard,
      incluyendo métricas adicionales, botones de acción y barra de progreso.
    -->
    <template v-else-if="variant === 'orders'">
      <!-- Sección de encabezado de pedidos -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de lista de pedidos detallada.
        Cada tarjeta de pedido incluye información adicional como métricas,
        líneas de texto anchas y barra de progreso de envío/entrega.
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <!--
          Lista de pedidos detallados: se iteran 4 tarjetas de pedido.
          - orderRows contiene [1, 2, 3, 4] para crear 4 pedidos.
          - Cada tarjeta tiene la clase '--detailed' para estilos adicionales.
          - Incluye fila de métricas (fecha, total, etc.).
          - Incluye botones de acción (primario y fantasma).
          - Incluye barra de progreso con 4 pasos (progressSteps).
        -->
        <div class="account-shimmer__list">
          <article v-for="order in orderRows" :key="`orders-${order}`" class="account-shimmer__order-card account-shimmer__order-card--detailed">
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__stack">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--small" />
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--status" />
            </div>

            <!-- Fila de métricas: fecha, total, etc. -->
            <div class="account-shimmer__metric-row">
              <span class="account-shimmer__line account-shimmer__line--small" />
              <span class="account-shimmer__line account-shimmer__line--price" />
              <span class="account-shimmer__line account-shimmer__line--small" />
            </div>

            <!-- Línea ancha para descripción o notas -->
            <span class="account-shimmer__line account-shimmer__line--wide" />

            <!-- Fila de botones de acción -->
            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>

            <!--
              Barra de progreso: muestra el estado del pedido (ej: procesando, enviado, entregado).
              Se iteran 4 pasos para simular una barra de progreso de 4 estados.
            -->
            <div class="account-shimmer__progress-row">
              <span v-for="step in progressSteps" :key="`orders-progress-${order}-${step}`" class="account-shimmer__progress-dot" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <!--
      Variante 'addresses': esqueleto para la página de direcciones guardadas.
      Muestra una cuadrícula de tarjetas de dirección con información de contacto,
      direcciones completas y botones de editar/eliminar.
    -->
    <template v-else-if="variant === 'addresses'">
      <!-- Sección de encabezado de direcciones -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de direcciones guardadas.
        El encabezado contiene un botón (probablemente "Agregar dirección").
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header account-shimmer__row account-shimmer__row--between">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__btn" />
        </header>

        <!--
          Cuadrícula de direcciones: se iteran 3 tarjetas de dirección.
          - primaryRows contiene [1, 2, 3] para crear 3 direcciones.
          - Cada tarjeta incluye: ícono pequeño, nombre/título, etiqueta,
            líneas de dirección, y botones de acción (editar/eliminar).
        -->
        <div class="account-shimmer__address-grid">
          <article v-for="address in primaryRows" :key="`address-${address}`" class="account-shimmer__address-card">
            <!-- Fila superior: ícono, nombre de dirección y etiqueta -->
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__row">
                <span class="account-shimmer__circle account-shimmer__circle--small" />
                <div class="account-shimmer__stack">
                  <span class="account-shimmer__line account-shimmer__line--heading" />
                  <span class="account-shimmer__line account-shimmer__line--small" />
                </div>
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--small" />
            </div>

            <!-- Dirección completa: líneas de texto anchas para simular dirección detallada -->
            <div class="account-shimmer__stack account-shimmer__stack--spaced">
              <span class="account-shimmer__line account-shimmer__line--wide" />
              <span class="account-shimmer__line account-shimmer__line--wide" />
              <span class="account-shimmer__line account-shimmer__line--text" />
              <span class="account-shimmer__line account-shimmer__line--text" />
            </div>

            <!-- Botones de acción: editar y eliminar dirección -->
            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <!--
      Variante 'wishlist': esqueleto para la página de lista de deseos.
      Muestra un resumen de la lista de deseos y una cuadrícula de productos guardados.
    -->
    <template v-else-if="variant === 'wishlist'">
      <!-- Sección de encabezado de lista de deseos -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de resumen de la lista de deseos.
        Incluye un encabezado con título, texto descriptivo y botón de acción.
      -->
      <section class="account-card account-shimmer__surface">
        <!-- Fila con título y botón de acción -->
        <div class="account-shimmer__row account-shimmer__row--between account-shimmer__row--wrap">
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
          <span class="account-shimmer__btn" />
        </div>

        <!--
          Tarjeta de resumen: muestra estadísticas de la lista de deseos
          (ej: número total de productos, precio total estimado).
        -->
        <div class="account-shimmer__summary-card account-shimmer__summary-card--single">
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--price" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
        </div>
      </section>

      <!--
        Cuadrícula de productos de la lista de deseos.
        - Se iteran 4 productos guardados.
        - Cada tarjeta incluye imagen, nombre, precio y botón de agregar al carrito.
        - Las tarjetas tienen la clase '--surface' para estilos de fondo.
      -->
      <section class="account-shimmer__product-grid">
        <article v-for="product in productCards" :key="`wishlist-product-${product}`" class="account-shimmer__product-card account-shimmer__surface">
          <span class="account-shimmer__rect account-shimmer__rect--product" />
          <span class="account-shimmer__line account-shimmer__line--text" />
          <span class="account-shimmer__line account-shimmer__line--wide" />
          <span class="account-shimmer__line account-shimmer__line--price" />
          <span class="account-shimmer__btn account-shimmer__btn--full" />
        </article>
      </section>
    </template>

    <!--
      Variante 'notifications': esqueleto para la página de notificaciones.
      Muestra tarjetas de resumen (no leídas, leídas), filtros y lista de notificaciones.
    -->
    <template v-else-if="variant === 'notifications'">
      <!-- Sección de encabezado de notificaciones -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Tarjetas de resumen de notificaciones: muestreador de estadísticas
        (ej: notificaciones no leídas, total de notificaciones).
        - Se iteran 2 tarjetas de resumen (summaryCards tiene 3 elementos pero el grid usa 2).
        - Se usa una cuadrícula de 2 columnas (account-grid-2).
      -->
      <section class="account-grid-2 notifications-summary-grid account-shimmer__summary">
        <article v-for="card in summaryCards" :key="`notifications-summary-${card}`" class="summary-card account-shimmer__summary-card">
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
        </article>
      </section>

      <!--
        Sección de lista de notificaciones.
        Incluye encabezado con título y filtro, pestañas de filtrado,
        y lista de notificaciones individuales.
      -->
      <section class="account-card account-shimmer__surface">
        <!-- Encabezado con título y botón de filtro -->
        <header class="section-header account-shimmer__row account-shimmer__row--between">
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--small" />
          </div>
          <span class="account-shimmer__line account-shimmer__line--filter" />
        </header>

        <!--
          Barra de herramientas de filtrado: pestañas para filtrar notificaciones
          (ej: todas, no leídas, leídas). Se iteran 3 pestañas.
        -->
        <div class="account-shimmer__toolbar">
          <span v-for="pill in summaryCards" :key="`notifications-pill-${pill}`" class="account-shimmer__pill account-shimmer__pill--small" />
        </div>

        <!--
          Lista de notificaciones: se iteran 4 notificaciones.
          - orderRows contiene [1, 2, 3, 4] para crear 4 notificaciones.
          - Cada notificación incluye ícono, contenido (título + descripción) y botones de acción.
        -->
        <div class="account-shimmer__list">
          <article v-for="notification in orderRows" :key="`notification-${notification}`" class="account-shimmer__notification-row">
            <div class="account-shimmer__row">
              <span class="account-shimmer__circle account-shimmer__circle--small" />
              <div class="account-shimmer__stack account-shimmer__stack--grow">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--wide" />
              </div>
            </div>
            <!-- Botones de acción: marcar como leída/eliminar -->
            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <!--
      Variante 'order-detail': esqueleto para la página de detalle de un pedido específico.
      Muestra información completa del pedido: encabezado, estado, lista de productos,
      barra de progreso y posibles acciones adicionales.
    -->
    <template v-else-if="variant === 'order-detail'">
      <!-- Sección de encabezado del detalle de pedido -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de información principal del pedido.
        Incluye número de pedido, fecha y pills de estado.
      -->
      <section class="account-card account-shimmer__surface">
        <div class="account-shimmer__stack">
          <span class="account-shimmer__line account-shimmer__line--title" />
          <span class="account-shimmer__line account-shimmer__line--small" />
        </div>

        <!-- Fila de pills de estado (ej: pagado, enviado, entregado) -->
        <div class="account-shimmer__row account-shimmer__row--wrap">
          <span class="account-shimmer__pill account-shimmer__pill--status" />
          <span class="account-shimmer__pill account-shimmer__pill--status" />
          <span class="account-shimmer__btn account-shimmer__btn--ghost" />
        </div>
      </section>

      <!--
        Sección de productos del pedido: lista de items comprados.
        Cada item muestra imagen en miniatura, nombre, cantidad y precio.
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <!--
          Lista de productos del pedido: se iteran 3 items.
          - primaryRows contiene [1, 2, 3] para crear 3 items.
          - Cada item incluye: imagen (rectángulo), nombre, cantidad, precio.
        -->
        <div class="account-shimmer__list">
          <article v-for="item in primaryRows" :key="`order-detail-item-${item}`" class="account-shimmer__detail-row">
            <span class="account-shimmer__rect account-shimmer__rect--thumb" />
            <div class="account-shimmer__stack account-shimmer__stack--grow">
              <span class="account-shimmer__line account-shimmer__line--heading" />
              <span class="account-shimmer__line account-shimmer__line--text" />
              <span class="account-shimmer__line account-shimmer__line--small" />
            </div>
            <span class="account-shimmer__line account-shimmer__line--price" />
          </article>
        </div>
      </section>

      <!--
        Sección de progreso del pedido: barra de progreso completa.
        Muestra el estado actual del pedido (ej: pedido confirmado → en preparación → enviado → entregado).
        Se iteran 4 pasos (progressSteps).
      -->
      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <!-- Barra de progreso ancha con 4 pasos -->
        <div class="account-shimmer__progress-row account-shimmer__progress-row--wide">
          <span v-for="step in progressSteps" :key="`order-detail-progress-${step}`" class="account-shimmer__progress-dot" />
        </div>
      </section>
    </template>

    <!--
      Variante 'settings': esqueleto para la página de configuración de la cuenta.
      Muestra un diseño de dos columnas: sidebar con pestañas de navegación
      y contenido principal con información del perfil y campos de configuración.
    -->
    <template v-else-if="variant === 'settings'">
      <!-- Sección de encabezado de configuración -->
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <!--
        Sección de configuración: diseño de dos columnas (sidebar + contenido).
        - Sidebar: pestañas de navegación (perfil, seguridad, notificaciones, etc.).
        - Contenido: información del perfil, campos de formulario y botón de guardar.
      -->
      <section class="account-card account-shimmer__surface account-shimmer__settings-shell">
        <div class="account-shimmer__settings-layout">
          <!--
            Sidebar de navegación: pestañas de configuración.
            - Se iteran 3 pestañas (settingsTabs tiene 3 elementos).
            - Cada pestaña es una línea de texto simulando un enlace de navegación.
          -->
          <aside class="account-shimmer__settings-sidebar">
            <span v-for="tab in settingsTabs" :key="`settings-tab-${tab}`" class="account-shimmer__line account-shimmer__line--nav" />
          </aside>

          <!--
            Contenido principal de configuración.
            Incluye: información del perfil (avatar, nombre, email, enlace),
            campos de formulario del perfil y botón de guardar cambios.
          -->
          <div class="account-shimmer__settings-content">
            <!-- Fila de perfil: avatar, nombre de usuario, email y enlace -->
            <div class="account-shimmer__row account-shimmer__row--wrap">
              <span class="account-shimmer__circle account-shimmer__circle--avatar" />
              <div class="account-shimmer__stack account-shimmer__stack--grow">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--text" />
                <span class="account-shimmer__line account-shimmer__line--link" />
              </div>
            </div>

            <!--
              Campos de formulario: se iteran 5 campos de configuración.
              - settingsFields contiene [1, 2, 3, 4, 5] para crear 5 campos.
              - Cada campo incluye una etiqueta (línea pequeña) y un input simulado (rectángulo).
            -->
            <div class="account-shimmer__settings-fields">
              <div v-for="field in settingsFields" :key="`settings-field-${field}`" class="account-shimmer__field-block">
                <span class="account-shimmer__line account-shimmer__line--small" />
                <span class="account-shimmer__rect account-shimmer__rect--input" />
              </div>
            </div>

            <!-- Botón de guardar cambios -->
            <span class="account-shimmer__btn" />
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
/*
  Componente AccountShimmer: esqueleto de carga (skeleton/shimmer) para la sección de cuenta.
  Utiliza Vue 3 Composition API con <script setup> para simplificar la sintaxis.
*/

/*
  defineProps: define las propiedades que recibe el componente.
  - variant (String): determina qué tipo de esqueleto mostrar.
    Opciones: 'dashboard', 'orders', 'addresses', 'wishlist', 'notifications', 'order-detail', 'settings'.
    Por defecto es 'dashboard' para mostrar el esqueleto de la página principal de cuenta.
*/
defineProps({
  variant: {
    type: String,
    default: 'dashboard',
  },
})

/*
  Arrays de ejemplo para iterar elementos en el template.
  Cada array contiene números que representan la cantidad de elementos a renderizar.
  Estos arrays se usan con v-for para generar la cantidad correcta de elementos shimmer
  según la sección que se esté cargando.
*/
// Tarjetas de resumen: se renderizan 3 tarjetas en secciones de resumen (dashboard, notifications)
const summaryCards = [1, 2, 3]
// Filas principales: se renderizan 3 elementos en listas principales (dashboard pedidos, addresses, order-detail items)
const primaryRows = [1, 2, 3]
// Filas de pedidos: se renderizan 4 tarjetas de pedido en la vista de historial de pedidos
const orderRows = [1, 2, 3, 4]
// Tarjetas de producto: se renderizan 4 productos en cuadrículas de productos (dashboard, wishlist)
const productCards = [1, 2, 3, 4]
// Pasos de progreso: se renderizan 4 puntos de progreso en barras de progreso (orders, order-detail)
const progressSteps = [1, 2, 3, 4]
// Pestañas de configuración: se renderizan 3 pestañas de navegación en el sidebar de settings
const settingsTabs = [1, 2, 3]
// Campos de configuración: se renderizan 5 campos de formulario en la sección de configuración
const settingsFields = [1, 2, 3, 4, 5]
</script>
