<template>
  <section class="admin-stats-grid">
    <template v-if="loading">
      <article v-for="i in count" :key="i" class="admin-stat-card admin-stat-card--loading">
        <AdminShimmer type="circle" width="4.8rem" height="4.8rem" />
        <div class="admin-stat-card__body">
          <AdminShimmer type="line" width="60%" height="1.2rem" />
          <AdminShimmer type="line" width="40%" height="2rem" />
          <AdminShimmer type="line" width="50%" height="1rem" />
        </div>
      </article>
    </template>
    <template v-else>
      <article v-for="stat in stats" :key="stat.key || stat.label" class="admin-stat-card">
        <div class="admin-stat-card__icon" :class="stat.color || 'primary'">
          <i :class="stat.icon"></i>
        </div>
        <div class="admin-stat-card__body">
          <p class="admin-stat-card__label">{{ stat.label }}</p>
          <p class="admin-stat-card__value">{{ stat.value }}</p>
          <div v-if="stat.meta" class="admin-stat-card__meta">
            <span v-if="stat.meta.change" class="admin-stat-card__change" :class="stat.meta.changeClass">{{ stat.meta.change }}</span>
            <span v-if="stat.meta.helper" class="admin-stat-card__helper">{{ stat.meta.helper }}</span>
          </div>
          <div v-if="stat.pills" class="admin-stat-card__pills">
            <span v-for="pill in stat.pills" :key="pill.label" class="admin-stat-card__pill" :class="pill.class">
              {{ pill.label }} <strong>{{ pill.value }}</strong>
            </span>
          </div>
        </div>
      </article>
    </template>
  </section>
</template>

<script setup>
import AdminShimmer from './AdminShimmer.vue'

defineProps({
  /** Array de stats: { key?, label, value, icon, color?, meta?, pills? } */
  stats: { type: Array, default: () => [] },
  /** Estado de carga */
  loading: { type: Boolean, default: false },
  /** Cuantas tarjetas shimmer mostrar en loading */
  count: { type: Number, default: 4 },
})
</script>
