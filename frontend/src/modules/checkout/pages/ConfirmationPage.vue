<template>
  <main class="confirmation-page-root">
    <section class="confirmation-page-shell">
      <CheckoutFlowHeader
        title="¡Pedido Confirmado!"
        icon-class="fas fa-check-circle"
        :active-step="4"
      />

      <div class="confirmation-page-divider" />

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
                <p>{{ result.shipping?.instructions || 'Sin instrucciones adicionales.' }}</p>
              </div>
            </div>
          </section>

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

const result = ref(null)

const normalizedItems = computed(() => {
  const items = Array.isArray(result.value?.items) ? result.value.items : []
  return items.map(normalizeCheckoutCartItem)
})

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

onMounted(() => {
  const raw = localStorage.getItem('angelow_checkout_result')
  if (raw) {
    result.value = parseStoredJson(raw)
  }
})

function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

function parseStoredJson(rawValue) {
  try {
    return JSON.parse(rawValue)
  } catch {
    return null
  }
}
</script>

<style scoped>
.confirmation-page-root {
  width: 100%;
  padding: 2rem 0 3.5rem;
}

.confirmation-page-shell {
  width: min(100%, 1480px);
  margin: 0 auto;
  padding: 0 1.5rem;
  animation: confirmationPageFadeIn 0.4s ease;
}

.confirmation-page-divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
}

.confirmation-page-content {
  display: grid;
  gap: 2.4rem;
}

.confirmation-success-card,
.confirmation-card,
.confirmation-contact-box,
.confirmation-empty-state {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.confirmation-success-card {
  padding: 2.6rem;
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

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

.confirmation-success-copy h2,
.confirmation-empty-state h2 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 2.6rem;
  font-weight: 700;
}

.confirmation-success-copy p,
.confirmation-empty-state p,
.confirmation-contact-box p {
  margin: 0;
  color: #55616d;
  font-size: 1.5rem;
  line-height: 1.65;
}

.confirmation-success-grid,
.confirmation-grid,
.confirmation-contact-grid,
.confirmation-info-grid {
  display: grid;
  gap: 1.2rem;
}

.confirmation-success-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 2rem;
}

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

.confirmation-success-item span,
.confirmation-info-block span,
.confirmation-contact-grid span {
  color: #6b7280;
  font-size: 1.2rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 700;
}

.confirmation-success-item strong,
.confirmation-info-block strong,
.confirmation-contact-grid strong {
  color: #334155;
  font-size: 1.5rem;
}

.confirmation-card,
.confirmation-contact-box,
.confirmation-empty-state {
  padding: 2.4rem;
}

.confirmation-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.confirmation-card-head {
  margin-bottom: 1.6rem;
  padding-bottom: 1.4rem;
  border-bottom: 1px solid #e0e0e0;
}

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

.confirmation-card-head h3 i,
.confirmation-contact-box h3 i {
  color: #0077b6;
}

.confirmation-items-list,
.confirmation-tracking-list {
  display: grid;
  gap: 1rem;
}

.confirmation-item-row {
  display: flex;
  gap: 1rem;
  padding: 1.1rem;
  border-radius: 1.6rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
}

.confirmation-item-media {
  width: 7rem;
  height: 7rem;
  overflow: hidden;
  border-radius: 1.4rem;
  flex-shrink: 0;
  border: 1px solid #dbe5ed;
  background: #ffffff;
}

.confirmation-item-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.confirmation-item-copy {
  min-width: 0;
  flex: 1;
}

.confirmation-item-copy h4 {
  margin: 0 0 0.45rem;
  color: #334155;
  font-size: 1.46rem;
  font-weight: 700;
}

.confirmation-item-copy p {
  margin: 0 0 0.5rem;
  color: #64748b;
  font-size: 1.22rem;
}

.confirmation-item-meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #55616d;
  font-size: 1.2rem;
}

.confirmation-item-meta strong {
  color: #0077b6;
  font-size: 1.28rem;
}

.confirmation-total-box {
  margin-top: 1.6rem;
  padding-top: 1.4rem;
  border-top: 1px solid #e0e0e0;
  display: grid;
  gap: 1rem;
}

.confirmation-total-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #444444;
  font-size: 1.42rem;
}

.confirmation-total-row strong {
  color: #0077b6;
}

.confirmation-total-row--discount strong {
  color: #4bb543;
}

.confirmation-total-row--grand {
  padding-top: 1.2rem;
  border-top: 2px dashed #e0e0e0;
  color: #005b8c;
  font-size: 1.7rem;
  font-weight: 700;
}

.confirmation-total-row--grand strong {
  color: #005b8c;
}

.confirmation-info-block p {
  margin: 0;
  color: #55616d;
  font-size: 1.32rem;
  line-height: 1.6;
}

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

.confirmation-tracking-step--active {
  background: #e6f2ff;
  border-color: rgba(0, 119, 182, 0.25);
  opacity: 1;
}

.confirmation-tracking-step--done {
  background: rgba(75, 181, 67, 0.08);
  border-color: rgba(75, 181, 67, 0.2);
  opacity: 1;
}

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

.confirmation-tracking-step--done .confirmation-tracking-icon {
  color: #4bb543;
}

.confirmation-tracking-copy {
  display: grid;
  gap: 0.35rem;
}

.confirmation-tracking-copy strong {
  color: #334155;
  font-size: 1.42rem;
}

.confirmation-tracking-copy span {
  color: #64748b;
  font-size: 1.24rem;
}

.confirmation-actions,
.confirmation-empty-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.confirmation-actions {
  justify-content: flex-end;
}

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

.confirmation-outline-pill {
  border: 2px solid #0077b6;
  color: #0077b6;
  background: #ffffff;
}

.confirmation-outline-pill:hover {
  transform: translateX(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.confirmation-primary-pill {
  border: none;
  background: #0077b6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.confirmation-primary-pill:hover {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.confirmation-contact-box {
  display: grid;
  gap: 1.4rem;
}

.confirmation-empty-state {
  max-width: 64rem;
  margin: 1rem auto 0;
  text-align: center;
}

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

@media (max-width: 1180px) {
  .confirmation-success-grid,
  .confirmation-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

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
