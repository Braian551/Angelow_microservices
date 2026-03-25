<template>
  <SiteHeader :settings="settings" :cart-count="0" />

  <main class="section-container">
    <section class="orders-header">
      <h1 class="section-title">Mis pedidos</h1>
    </section>

    <section v-if="!user?.id" class="empty-cart">
      <p>Debes iniciar sesion para ver tus pedidos.</p>
      <RouterLink :to="{ name: 'login' }" class="btn">Iniciar sesion</RouterLink>
    </section>

    <p v-else-if="loading" class="loading-box">Cargando pedidos...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <section v-else-if="orders.length === 0" class="empty-cart">
      <p>No tienes pedidos aun.</p>
      <RouterLink :to="{ name: 'store' }" class="btn">Comprar ahora</RouterLink>
    </section>

    <section v-else class="orders-grid">
      <article v-for="order in orders" :key="order.id" class="order-card">
        <h3>{{ order.order_number }}</h3>
        <p>Estado: {{ order.status }}</p>
        <p>Total: {{ formatPrice(order.total) }}</p>
        <p>Fecha: {{ formatDate(order.created_at) }}</p>
      </article>
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
import { getOrders } from '../../../services/orderApi'
import { useSession } from '../../../composables/useSession'

const { user } = useSession()

const loading = ref(true)
const errorMessage = ref('')
const settings = ref({})
const orders = ref([])

onMounted(async () => {
  try {
    const homeRes = await getHomeData()
    settings.value = homeRes?.data?.settings || {}

    if (user.value?.id) {
      const ordersRes = await getOrders({ user_id: user.value.id })
      orders.value = ordersRes?.data || []
    }
  } catch {
    errorMessage.value = 'No se pudieron cargar los pedidos.'
  } finally {
    loading.value = false
  }
})

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('es-CO')
}
</script>

<style scoped>
.orders-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.order-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
}
</style>
