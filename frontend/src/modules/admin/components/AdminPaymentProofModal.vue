<template>
  <AdminModal
    :show="show"
    title="Comprobante de pago"
    icon="fas fa-file-invoice-dollar"
    max-width="880px"
    @close="emit('close')"
  >
    <div class="proof-modal">
      <template v-if="proofAvailable">
        <div v-if="proofIsImage" class="proof-modal__viewer">
          <div class="proof-modal__image-wrap" :class="{ 'proof-modal__image-wrap--zoomed': proofZoomed }">
            <img :src="payment?.proof_url" alt="Comprobante de pago" class="proof-modal__image" @click="toggleProofZoom">
          </div>

          <div class="proof-modal__zoom-bar">
            <button type="button" class="proof-modal__zoom-btn" @click="toggleProofZoom">
              <i :class="proofZoomed ? 'fas fa-search-minus' : 'fas fa-search-plus'"></i>
              <span>{{ proofZoomed ? 'Reducir' : 'Ampliar' }}</span>
            </button>
          </div>
        </div>

        <div v-else class="proof-modal__file">
          <i class="fas fa-file-pdf"></i>
          <strong>{{ payment?.proof_name || 'Documento adjunto' }}</strong>
          <p>Este archivo no puede previsualizarse aquí. Usa el botón para abrirlo.</p>
        </div>
      </template>

      <div v-else-if="payment?.proof_url" class="proof-modal__missing">
        <i class="fas fa-image-slash"></i>
        <div>
          <strong>Comprobante no disponible.</strong>
          <p>No pudimos mostrar el archivo en este momento.</p>
        </div>
      </div>

      <div v-else class="proof-modal__empty">
        <i class="fas fa-file-circle-xmark"></i>
        <div>
          <strong>Sin comprobante adjunto</strong>
          <p>Este pago no tiene un comprobante disponible para revisar.</p>
        </div>
      </div>

      <div v-if="proofAvailable && (payment?.reference_number || paymentStatusLabel)" class="proof-modal__meta">
        <div v-if="payment?.reference_number" class="proof-modal__meta-item">
          <span>Referencia</span>
          <strong>{{ payment.reference_number }}</strong>
        </div>

        <div class="proof-modal__meta-item">
          <span>Estado de pago</span>
          <strong>{{ paymentStatusLabel }}</strong>
        </div>
      </div>
    </div>

    <template #footer>
      <a
        v-if="proofAvailable && payment?.proof_url"
        :href="payment.proof_url"
        target="_blank"
        rel="noreferrer"
        class="btn btn-primary"
        @click="emit('close')"
      >
        <i class="fas fa-external-link-alt"></i>
        Abrir en nueva pestaña
      </a>

      <button type="button" class="btn btn-secondary" @click="emit('close')">Cerrar</button>
    </template>
  </AdminModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import AdminModal from './AdminModal.vue'
import { getPaymentStatusLabel } from '../utils/orderPresentation'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  payment: {
    type: Object,
    default: null,
  },
  fallbackStatus: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])

const proofZoomed = ref(false)

const proofAvailable = computed(() => Boolean(props.payment?.proof_url && props.payment?.proof_exists !== false))
const proofIsImage = computed(() => Boolean(props.payment?.proof_url && /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i.test(props.payment.proof_url)))
const paymentStatusLabel = computed(() => getPaymentStatusLabel(props.payment?.status || props.fallbackStatus || 'pending'))

watch(() => props.show, (isOpen) => {
  if (!isOpen) {
    proofZoomed.value = false
  }
})

function toggleProofZoom() {
  if (!proofIsImage.value) return
  proofZoomed.value = !proofZoomed.value
}
</script>

<style scoped>
.proof-modal {
  display: grid;
  gap: 1.2rem;
}

.proof-modal__viewer {
  display: grid;
  gap: 0.6rem;
}

.proof-modal__image-wrap {
  border-radius: 1.5rem;
  overflow: hidden;
  background: rgba(247, 251, 255, 0.9);
  border: 1px solid rgba(148, 184, 216, 0.22);
  display: flex;
  align-items: center;
  justify-content: center;
  max-height: 52vh;
  transition: max-height 0.3s ease;
}

.proof-modal__image-wrap--zoomed {
  max-height: 72vh;
  overflow: auto;
  align-items: flex-start;
  justify-content: flex-start;
}

.proof-modal__image {
  width: 100%;
  max-height: 52vh;
  object-fit: contain;
  display: block;
  cursor: zoom-in;
}

.proof-modal__image-wrap--zoomed .proof-modal__image {
  width: auto;
  min-width: 100%;
  max-height: none;
  cursor: zoom-out;
}

.proof-modal__zoom-bar {
  display: flex;
  justify-content: flex-end;
}

.proof-modal__zoom-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 1.1rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.08);
  border: 1px solid rgba(0, 119, 182, 0.16);
  color: var(--admin-primary-dark);
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease;
}

.proof-modal__zoom-btn:hover {
  background: rgba(0, 119, 182, 0.15);
}

.proof-modal__file,
.proof-modal__empty,
.proof-modal__missing {
  display: grid;
  gap: 0.7rem;
  justify-items: center;
  text-align: center;
  padding: 3rem 1rem;
  border-radius: 1.5rem;
  background: rgba(247, 251, 255, 0.8);
}

.proof-modal__file {
  border: 1px dashed rgba(148, 184, 216, 0.3);
  color: var(--admin-primary-dark);
}

.proof-modal__empty,
.proof-modal__missing {
  border: 1px solid rgba(207, 224, 236, 0.86);
  color: #526277;
}

.proof-modal__file i,
.proof-modal__empty i,
.proof-modal__missing i {
  font-size: 3.3rem;
}

.proof-modal__file i {
  color: #dc2626;
}

.proof-modal__empty i,
.proof-modal__missing i {
  color: #0f7abf;
}

.proof-modal__file strong,
.proof-modal__empty strong,
.proof-modal__missing strong {
  font-size: 1.45rem;
  color: var(--admin-text-heading);
}

.proof-modal__file p,
.proof-modal__empty p,
.proof-modal__missing p {
  margin: 0;
  color: var(--admin-text-soft);
  font-size: 1.2rem;
}

.proof-modal__meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.proof-modal__meta-item {
  padding: 0.95rem 1.1rem;
  border-radius: 1.3rem;
  border: 1px solid rgba(148, 184, 216, 0.2);
  background: rgba(255, 255, 255, 0.84);
  display: grid;
  gap: 0.35rem;
}

.proof-modal__meta-item span {
  font-size: 1.05rem;
  color: var(--admin-text-light);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 700;
}

.proof-modal__meta-item strong {
  font-size: 1.35rem;
  color: var(--admin-text-dark);
}

@media (max-width: 768px) {
  .proof-modal__meta {
    grid-template-columns: 1fr;
  }
}
</style>