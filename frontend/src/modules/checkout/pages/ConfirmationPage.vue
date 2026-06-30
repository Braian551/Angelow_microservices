<template>
  <main class="confirmation-page-root">
    <section class="confirmation-page-shell">
      <!-- Encabezado compartido del checkout con el cuarto paso marcado como finalizado. -->
      <CheckoutFlowHeader
        title="¡Pedido Confirmado!"
        icon-class="fas fa-check-circle"
        :active-step="4"
      />

      <div class="confirmation-page-divider" />

      <!-- Estado seguro cuando no existe confirmación reciente en localStorage. -->
      <section v-if="!result" class="confirmation-empty-state">
        <div class="confirmation-empty-icon">
          <i class="fas fa-receipt" />
        </div>
        <h2>No encontramos una confirmación reciente</h2>
        <p>Si acabas de comprar, intenta regresar a la tienda o revisa tus pedidos desde tu cuenta.</p>
        <div class="confirmation-empty-actions">
          <RouterLink :to="{ name: 'store' }" class="confirmation-outline-pill">
            <i class="fas fa-arrow-left" />
            <span>Seguir comprando</span>
          </RouterLink>
          <RouterLink :to="{ name: 'account-orders' }" class="confirmation-primary-pill">
            <i class="fas fa-box" />
            <span>Ver mis pedidos</span>
          </RouterLink>
        </div>
      </section>

      <!-- Contenido principal cuando el checkout dejó un resultado confirmado. -->
      <section v-else class="confirmation-page-content">
        <article class="confirmation-success-card">
          <div class="confirmation-success-icon">
            <i class="fas fa-check-circle" />
          </div>

          <div class="confirmation-success-copy">
            <h2>¡Gracias por tu compra!</h2>
            <p>
              Tu pedido <strong>#{{ result.order_number }}</strong> fue registrado correctamente y quedó pendiente de
              verificación manual del pago.
            </p>

            <p v-if="checkoutWarning" class="confirmation-warning-banner">
              <i class="fas fa-circle-info" />
              {{ checkoutWarning }}
            </p>

            <div class="confirmation-success-grid">
              <div class="confirmation-success-item">
                <span>Número de orden</span>
                <strong>#{{ result.order_number }}</strong>
              </div>
              <div class="confirmation-success-item">
                <span>Fecha</span>
                <strong>{{ formatCheckoutDateTime(result.created_at) || 'Ahora mismo' }}</strong>
              </div>
              <div class="confirmation-success-item">
                <span>Total pagado</span>
                <strong>{{ formatCheckoutPrice(result.total) }}</strong>
              </div>
              <div class="confirmation-success-item">
                <span>Referencia</span>
                <strong>{{ result.reference_number || 'Pendiente' }}</strong>
              </div>
            </div>
          </div>
        </article>

        <div class="confirmation-grid">
          <!-- Resumen de productos, variantes y totales que vienen del pedido creado. -->
          <section class="confirmation-card">
            <header class="confirmation-card-head">
              <h3>
                <i class="fas fa-bag-shopping" />
                Resumen del Pedido
              </h3>
            </header>

            <div class="confirmation-items-list">
              <article
                v-for="item in normalizedItems"
                :key="item.item_id || `${item.product_id}-${item.product_name}`"
                class="confirmation-item-row"
              >
                <div class="confirmation-item-media">
                  <img
                    :src="resolveMediaUrl(item.product_image, 'product')"
                    :alt="item.product_name"
                    @error="onItemImageError($event, item.product_image)"
                  />
                </div>

                <div class="confirmation-item-copy">
                  <h4>{{ item.product_name }}</h4>
                  <p v-if="buildCheckoutVariantName(item)">{{ buildCheckoutVariantName(item) }}</p>
                  <div class="confirmation-item-meta">
                    <span>{{ item.quantity }} x {{ formatCheckoutPrice(item.price) }}</span>
                    <strong>{{ formatCheckoutPrice(item.total || item.price * item.quantity) }}</strong>
                  </div>
                </div>
              </article>
            </div>

            <div class="confirmation-total-box">
              <div class="confirmation-total-row">
                <span>Subtotal</span>
                <strong>{{ formatCheckoutPrice(result.subtotal) }}</strong>
              </div>
              <div v-if="result.discount_amount > 0" class="confirmation-total-row confirmation-total-row--discount">
                <span>Descuento</span>
                <strong>-{{ formatCheckoutPrice(result.discount_amount) }}</strong>
              </div>
              <div class="confirmation-total-row">
                <span>Envío</span>
                <strong>{{ result.shipping_cost > 0 ? formatCheckoutPrice(result.shipping_cost) : 'Gratis' }}</strong>
              </div>
              <div class="confirmation-total-row confirmation-total-row--grand">
                <span>Total</span>
                <strong>{{ formatCheckoutPrice(result.total) }}</strong>
              </div>
            </div>
          </section>

          <!-- Información de entrega guardada durante el checkout. -->
          <section class="confirmation-card">
            <header class="confirmation-card-head">
              <h3>
                <i class="fas fa-truck" />
                Información de Envío
              </h3>
            </header>

            <div class="confirmation-info-grid">
              <div class="confirmation-info-block">
                <span>Destinatario</span>
                <strong>{{ result.shipping?.recipient_name || '-' }}</strong>
                <p>{{ result.shipping?.recipient_phone || '-' }}</p>
              </div>

              <div class="confirmation-info-block">
                <span>Dirección</span>
                <strong>{{ result.shipping?.address || '-' }}</strong>
                <p>{{ result.shipping?.zone || '-' }}</p>
              </div>

              <div class="confirmation-info-block">
                <span>Método</span>
                <strong>{{ result.shipping?.method_name || 'Envío' }}</strong>
                <p>{{ result.shipping?.method_description || '' }}</p>
              </div>

              <div class="confirmation-info-block">
                <span>Tiempo estimado</span>
                <strong>{{ result.shipping?.method_eta || 'Te lo confirmaremos por correo' }}</strong>
                <p v-if="result.shipping?.instructions">{{ result.shipping.instructions }}</p>
                <p v-if="result.shipping?.notes && result.shipping.notes !== result.shipping.instructions" class="confirmation-info-note">
                  Nota del pedido: {{ result.shipping.notes }}
                </p>
                <p v-if="!result.shipping?.instructions && !result.shipping?.notes">Sin instrucciones adicionales.</p>
              </div>
            </div>
          </section>

          <!-- Línea de seguimiento inicial mientras el pago queda pendiente de verificación. -->
          <section class="confirmation-card">
            <header class="confirmation-card-head">
              <h3>
                <i class="fas fa-route" />
                Progreso del Pedido
              </h3>
            </header>

            <div class="confirmation-tracking-list">
              <article
                v-for="step in trackingSteps"
                :key="step.title"
                class="confirmation-tracking-step"
                :class="{
                  'confirmation-tracking-step--active': step.state === 'active',
                  'confirmation-tracking-step--done': step.state === 'done',
                }"
              >
                <div class="confirmation-tracking-icon">
                  <i :class="step.icon" />
                </div>
                <div class="confirmation-tracking-copy">
                  <strong>{{ step.title }}</strong>
                  <span>{{ step.subtitle }}</span>
                </div>
              </article>
            </div>
          </section>
        </div>

        <!-- Acciones posteriores a la confirmación: continuar comprando o revisar pedidos. -->
        <div class="confirmation-actions">
          <RouterLink :to="{ name: 'store' }" class="confirmation-outline-pill">
            <i class="fas fa-bag-shopping" />
            <span>Seguir comprando</span>
          </RouterLink>
          <RouterLink :to="{ name: 'account-orders' }" class="confirmation-primary-pill">
            <i class="fas fa-user" />
            <span>Ver mis pedidos</span>
          </RouterLink>
        </div>

        <!-- Bloque de soporte con datos clave para atención al cliente. -->
        <section class="confirmation-contact-box">
          <h3>
            <i class="fas fa-headset" />
            ¿Necesitas ayuda?
          </h3>
          <p>Si tienes preguntas sobre tu pedido, ten a la mano tu número de orden y referencia de pago.</p>
          <div class="confirmation-contact-grid">
            <div>
              <span>Banco reportado</span>
              <strong>{{ result.payment_bank_name || 'Transferencia bancaria' }}</strong>
            </div>
            <div>
              <span>Comprobante</span>
              <strong>{{ result.payment_proof_name || 'Adjuntado en el checkout' }}</strong>
            </div>
            <div>
              <span>Estado actual</span>
              <strong>Pendiente de verificación</strong>
            </div>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import CheckoutFlowHeader from '../components/CheckoutFlowHeader.vue'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import {
  buildCheckoutVariantName,
  formatCheckoutDateTime,
  formatCheckoutPrice,
  normalizeCheckoutCartItem,
} from '../utils/checkoutHelpers'

// Resultado del checkout persistido temporalmente después de crear el pedido.
const result = ref(null)
const checkoutWarning = ref('')

// Normaliza ítems antes de renderizar para mantener formato uniforme en el resumen.
const normalizedItems = computed(() => {
  const items = Array.isArray(result.value?.items) ? result.value.items : []
  return items.map(normalizeCheckoutCartItem)
})

// Define los pasos de seguimiento iniciales que se muestran tras confirmar el pedido.
const trackingSteps = computed(() => [
  {
    icon: 'fas fa-shopping-cart',
    title: 'Pedido realizado',
    subtitle: formatCheckoutDateTime(result.value?.created_at) || 'Confirmado ahora mismo',
    state: 'done',
  },
  {
    icon: 'fas fa-money-bill-wave',
    title: 'Verificación de pago',
    subtitle: 'En proceso',
    state: 'active',
  },
  {
    icon: 'fas fa-box',
    title: 'Preparando pedido',
    subtitle: 'Próximamente',
    state: 'idle',
  },
  {
    icon: 'fas fa-truck',
    title: 'En camino',
    subtitle: 'Pendiente',
    state: 'idle',
  },
  {
    icon: 'fas fa-home',
    title: 'Entregado',
    subtitle: 'Pendiente',
    state: 'idle',
  },
])

// Recupera la confirmación desde localStorage y limpia avisos de checkout ya consumidos.
onMounted(() => {
  const raw = localStorage.getItem('angelow_checkout_result')
  if (raw) {
    result.value = parseStoredJson(raw)
  }

  checkoutWarning.value = localStorage.getItem('angelow_checkout_warning') || ''
  localStorage.removeItem('angelow_checkout_warning')
})

// Usa el fallback común de imágenes cuando el producto confirmado no carga.
function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

// Parsea el resultado persistido sin romper la pantalla si el JSON está corrupto.
function parseStoredJson(rawValue) {
  try {
    return JSON.parse(rawValue)
  } catch {
    return null
  }
}
</script>

<style scoped>
/* Contenedor raíz de la confirmación: separa la página del resto del checkout. */
.confirmation-page-root {
  width: 100%;
  padding: 2rem 0 3.5rem;
}

/* Shell centrado que contiene cards, grillas y acciones de confirmación. */
.confirmation-page-shell {
  width: min(100%, 1480px);
  margin: 0 auto;
  padding: 0 1.5rem;
  animation: confirmationPageFadeIn 0.4s ease;
}

/* Línea divisoria que separa el header del contenido de confirmación. */
.confirmation-page-divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
}

/* Grilla vertical del contenido cuando existe pedido confirmado. */
.confirmation-page-content {
  display: grid;
  gap: 2.4rem;
}

/* Nota secundaria de envío o instrucciones especiales del pedido. */
.confirmation-info-note {
  color: #5c6773;
}

/* Superficies reutilizadas para estado exitoso, tarjetas y soporte. */
.confirmation-success-card,
.confirmation-card,
.confirmation-contact-box,
.confirmation-empty-state {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Card de éxito con ícono y resumen principal del pedido. */
.confirmation-success-card {
  padding: 2.6rem;
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

/* Íconos circulares para éxito o estado vacío. */
.confirmation-success-icon,
.confirmation-empty-icon {
  width: 8rem;
  height: 8rem;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(75, 181, 67, 0.12);
  color: #4bb543;
  font-size: 3rem;
}

/* Tipografía principal de mensajes de éxito y estado vacío. */
.confirmation-success-copy h2,
.confirmation-empty-state h2 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 2.6rem;
  font-weight: 700;
}

/* Párrafos descriptivos dentro de la confirmación. */
.confirmation-success-copy p,
.confirmation-empty-state p,
.confirmation-contact-box p {
  margin: 0;
  color: #55616d;
  font-size: 1.5rem;
  line-height: 1.65;
}

/* Aviso de checkout para mostrar advertencias no bloqueantes. */
.confirmation-warning-banner {
  margin-top: 1rem !important;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 999px;
  border: 1px solid #f4d38b;
  background: #fff8e8;
  color: #9a6700 !important;
  padding: 0.5rem 1rem;
  font-size: 1.22rem !important;
  font-weight: 600;
}

/* Grillas internas para métricas, columnas de cards y datos de contacto. */
.confirmation-success-grid,
.confirmation-grid,
.confirmation-contact-grid,
.confirmation-info-grid {
  display: grid;
  gap: 1.2rem;
}

/* Cuatro métricas principales del pedido en desktop. */
.confirmation-success-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 2rem;
}

/* Bloques compactos de información con borde y fondo neutro. */
.confirmation-success-item,
.confirmation-info-block,
.confirmation-contact-grid > div {
  padding: 1.4rem 1.5rem;
  border-radius: 1.8rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  display: grid;
  gap: 0.45rem;
}

/* Etiquetas superiores de cada dato resumido. */
.confirmation-success-item span,
.confirmation-info-block span,
.confirmation-contact-grid span {
  color: #6b7280;
  font-size: 1.2rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 700;
}

/* Valores principales de métricas, datos de envío y soporte. */
.confirmation-success-item strong,
.confirmation-info-block strong,
.confirmation-contact-grid strong {
  color: #334155;
  font-size: 1.5rem;
}

/* Padding común de tarjetas secundarias y estado vacío. */
.confirmation-card,
.confirmation-contact-box,
.confirmation-empty-state {
  padding: 2.4rem;
}

/* Layout de tres columnas para productos, envío y seguimiento. */
.confirmation-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

/* Encabezado interno de cada tarjeta de detalle. */
.confirmation-card-head {
  margin-bottom: 1.6rem;
  padding-bottom: 1.4rem;
  border-bottom: 1px solid #e0e0e0;
}

/* Títulos con ícono para separar visualmente cada bloque de información. */
.confirmation-card-head h3,
.confirmation-contact-box h3 {
  margin: 0;
  color: #333333;
  font-size: 1.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

/* Íconos de sección con color de marca. */
.confirmation-card-head h3 i,
.confirmation-contact-box h3 i {
  color: #0077b6;
}

/* Listas verticales para productos y pasos de seguimiento. */
.confirmation-items-list,
.confirmation-tracking-list {
  display: grid;
  gap: 1rem;
}

/* Fila de producto confirmado con imagen y totales. */
.confirmation-item-row {
  display: flex;
  gap: 1rem;
  padding: 1.1rem;
  border-radius: 1.6rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
}

/* Marco fijo de imagen para evitar saltos mientras carga el producto. */
.confirmation-item-media {
  width: 7rem;
  height: 7rem;
  overflow: hidden;
  border-radius: 1.4rem;
  flex-shrink: 0;
  border: 1px solid #dbe5ed;
  background: #ffffff;
}

/* Imagen de producto ajustada al marco sin deformarse. */
.confirmation-item-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Contenedor de nombre, variante y total por línea. */
.confirmation-item-copy {
  min-width: 0;
  flex: 1;
}

/* Nombre del producto dentro del resumen. */
.confirmation-item-copy h4 {
  margin: 0 0 0.45rem;
  color: #334155;
  font-size: 1.46rem;
  font-weight: 700;
}

/* Variante de color o talla debajo del nombre. */
.confirmation-item-copy p {
  margin: 0 0 0.5rem;
  color: #64748b;
  font-size: 1.22rem;
}

/* Línea de cantidad, precio unitario y total. */
.confirmation-item-meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #55616d;
  font-size: 1.2rem;
}

/* Total por producto resaltado con color de marca. */
.confirmation-item-meta strong {
  color: #0077b6;
  font-size: 1.28rem;
}

/* Caja de totales del pedido al final de la lista de productos. */
.confirmation-total-box {
  margin-top: 1.6rem;
  padding-top: 1.4rem;
  border-top: 1px solid #e0e0e0;
  display: grid;
  gap: 1rem;
}

/* Fila base de subtotal, descuento, envío y total. */
.confirmation-total-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #444444;
  font-size: 1.42rem;
}

/* Valor monetario destacado en cada fila de total. */
.confirmation-total-row strong {
  color: #0077b6;
}

/* Descuento resaltado como valor positivo para el cliente. */
.confirmation-total-row--discount strong {
  color: #4bb543;
}

/* Total general separado con línea discontinua para jerarquía. */
.confirmation-total-row--grand {
  padding-top: 1.2rem;
  border-top: 2px dashed #e0e0e0;
  color: #005b8c;
  font-size: 1.7rem;
  font-weight: 700;
}

/* Color final del total general. */
.confirmation-total-row--grand strong {
  color: #005b8c;
}

/* Texto auxiliar de cada bloque de información de envío. */
.confirmation-info-block p {
  margin: 0;
  color: #55616d;
  font-size: 1.32rem;
  line-height: 1.6;
}

/* Paso de seguimiento base con opacidad menor para estados pendientes. */
.confirmation-tracking-step {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 1.2rem;
  border-radius: 1.6rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  opacity: 0.88;
}

/* Paso activo: el pago queda en verificación manual. */
.confirmation-tracking-step--active {
  background: #e6f2ff;
  border-color: rgba(0, 119, 182, 0.25);
  opacity: 1;
}

/* Paso completado: pedido ya registrado correctamente. */
.confirmation-tracking-step--done {
  background: rgba(75, 181, 67, 0.08);
  border-color: rgba(75, 181, 67, 0.2);
  opacity: 1;
}

/* Ícono circular de cada paso de seguimiento. */
.confirmation-tracking-icon {
  width: 4.8rem;
  height: 4.8rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  color: #0077b6;
  border: 1px solid #dbe5ed;
  flex-shrink: 0;
}

/* Ícono verde para pasos completados. */
.confirmation-tracking-step--done .confirmation-tracking-icon {
  color: #4bb543;
}

/* Texto del paso de seguimiento. */
.confirmation-tracking-copy {
  display: grid;
  gap: 0.35rem;
}

/* Título de cada paso del seguimiento. */
.confirmation-tracking-copy strong {
  color: #334155;
  font-size: 1.42rem;
}

/* Subtítulo de cada paso del seguimiento. */
.confirmation-tracking-copy span {
  color: #64748b;
  font-size: 1.24rem;
}

/* Contenedores de acciones, con wrap para pantallas estrechas. */
.confirmation-actions,
.confirmation-empty-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Acciones principales alineadas a la derecha en desktop. */
.confirmation-actions {
  justify-content: flex-end;
}

/* Botones tipo píldora para navegación posterior a la compra. */
.confirmation-outline-pill,
.confirmation-primary-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.85rem;
  min-height: 5rem;
  padding: 0.9rem 1.8rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: 1.5rem;
  font-weight: 700;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

/* Botón secundario con borde de marca. */
.confirmation-outline-pill {
  border: 2px solid #0077b6;
  color: #0077b6;
  background: #ffffff;
}

/* Hover del botón secundario con desplazamiento lateral sutil. */
.confirmation-outline-pill:hover {
  transform: translateX(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* Botón primario para ir a pedidos. */
.confirmation-primary-pill {
  border: none;
  background: #0077b6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* Hover del botón primario con elevación ligera. */
.confirmation-primary-pill:hover {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

/* Caja de contacto con datos útiles para soporte. */
.confirmation-contact-box {
  display: grid;
  gap: 1.4rem;
}

/* Estado vacío centrado cuando falta confirmación reciente. */
.confirmation-empty-state {
  max-width: 64rem;
  margin: 1rem auto 0;
  text-align: center;
}

/* Animación de entrada suave de la página. */
@keyframes confirmationPageFadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Tablet: reduce cards principales a dos columnas. */
@media (max-width: 1180px) {
  .confirmation-success-grid,
  .confirmation-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Móvil: apila tarjetas, reduce padding y convierte acciones en columna. */
@media (max-width: 768px) {
  .confirmation-page-root {
    padding-top: 1.4rem;
    padding-bottom: 2.4rem;
  }

  .confirmation-page-shell {
    padding: 0 1rem;
  }

  .confirmation-success-card,
  .confirmation-card,
  .confirmation-contact-box,
  .confirmation-empty-state {
    padding: 1.8rem 1.4rem;
  }

  .confirmation-success-card {
    flex-direction: column;
  }

  .confirmation-success-grid,
  .confirmation-grid {
    grid-template-columns: 1fr;
  }

  .confirmation-actions,
  .confirmation-empty-actions {
    flex-direction: column;
  }
}
</style>
