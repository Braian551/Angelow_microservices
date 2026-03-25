<template>
  <SiteHeader :settings="settings" :cart-count="0" />

  <main class="section-container confirmation-page">
    <section class="confirmation-card">
      <h1>Pedido confirmado</h1>
      <p>Tu compra fue registrada correctamente.</p>

      <div v-if="result">
        <p><strong>Orden:</strong> {{ result.order_number }}</p>
        <p><strong>ID orden:</strong> {{ result.order_id }}</p>
        <p><strong>Referencia:</strong> {{ result.reference_number }}</p>
        <p><strong>Total:</strong> {{ formatPrice(result.total) }}</p>
      </div>

      <div class="confirmation-actions">
        <RouterLink :to="{ name: 'orders' }" class="btn">Ver mis pedidos</RouterLink>
        <RouterLink :to="{ name: 'store' }" class="btn">Seguir comprando</RouterLink>
      </div>
    </section>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import { getHomeData } from '../../../services/catalogApi'

const settings = ref({})
const result = ref(null)

onMounted(async () => {
  try {
    const homeRes = await getHomeData()
    settings.value = homeRes?.data?.settings || {}
  } catch {
    settings.value = {}
  }

  const raw = localStorage.getItem('angelow_checkout_result')
  if (raw) {
    result.value = JSON.parse(raw)
  }
})

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}
</script>

<style scoped>
.confirmation-page {
  display: grid;
  place-items: center;
  padding: 2rem 0;
}

.confirmation-card {
  width: min(640px, 100%);
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
}

.confirmation-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}
</style>
