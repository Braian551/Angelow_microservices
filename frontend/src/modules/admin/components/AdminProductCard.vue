<template>
  <div class="product-admin-card">
    <div class="product-admin-select">
      <input
        type="checkbox"
        class="select-row"
        :checked="selected"
        @change="emit('toggle-select', { id: product.id, checked: $event.target.checked })"
      >
    </div>

    <div class="product-admin-status">
      <span class="status-badge" :class="product.is_active ? 'status-active' : 'status-inactive'">
        {{ product.is_active ? 'Activo' : 'Inactivo' }}
      </span>
    </div>

    <div class="product-admin-image">
      <img :src="product.image" :alt="product.name" @error="emit('image-error', { event: $event, imagePath: product.rawImage })">
      <div class="product-admin-overlay">
        <button class="btn-overlay" type="button" title="Vista rápida" @click="emit('quick-view', product)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <div class="product-admin-body">
      <div class="product-admin-header">
        <h3 class="product-admin-title">
          <RouterLink :to="{ name: 'admin-product-edit', params: { id: product.id } }">{{ product.name }}</RouterLink>
        </h3>
        <span class="product-admin-id">ID: {{ product.id }}</span>
      </div>

      <div class="product-admin-meta">
        <div class="meta-item">
          <i class="fas fa-tag"></i>
          <span>{{ product.category_name || 'Sin categoría' }}</span>
        </div>
        <div class="meta-item">
          <i class="fas fa-palette"></i>
          <span>{{ product.variant_count || 0 }} variante{{ (product.variant_count || 0) !== 1 ? 's' : '' }}</span>
        </div>
      </div>

      <div class="product-admin-info">
        <div class="info-item">
          <label>Stock total:</label>
          <span class="stock-value" :class="{ 'low-stock': product.stock < 10 }">{{ product.stock }} unidades</span>
        </div>
        <div class="info-item">
          <label>Precio:</label>
          <span class="price-value">{{ priceLabel }}</span>
        </div>
      </div>
    </div>

    <div class="product-admin-actions">
      <RouterLink :to="{ name: 'admin-product-edit', params: { id: product.id } }" class="btn-action btn-edit" title="Editar">
        <i class="fas fa-edit"></i>
        <span>Editar</span>
      </RouterLink>
      <button
        class="btn-action"
        :class="product.is_active ? 'btn-toggle-off' : 'btn-toggle-on'"
        type="button"
        :title="product.is_active ? 'Desactivar producto' : 'Activar producto'"
        @click="emit('toggle-status', product)"
      >
        <i :class="product.is_active ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
        <span>{{ product.is_active ? 'Desactivar' : 'Activar' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['toggle-select', 'quick-view', 'toggle-status', 'image-error'])

const priceLabel = computed(() => {
  const min = Number(props.product.min_price || props.product.price || 0)
  const max = Number(props.product.max_price || props.product.price || 0)

  if (min === max || max === 0) {
    return `$${min.toLocaleString('es-CO')}`
  }

  return `$${min.toLocaleString('es-CO')} - $${max.toLocaleString('es-CO')}`
})
</script>

<style scoped>
.product-admin-card {
  position: relative;
  border: 1px solid #b3d9ff;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 119, 182, 0.08);
  transition: all 0.3s ease;
  animation: fadeInUp 0.45s ease both;
}

.product-admin-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 119, 182, 0.15);
  border-color: #0077b6;
}

.product-admin-select {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 3;
}

.product-admin-select input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #0077b6;
}

.product-admin-status {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 3;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.4px;
}

.status-badge.status-active {
  background: #e6f7eb;
  color: #1d7a3f;
}

.status-badge.status-inactive {
  background: #ffe9e9;
  color: #b52e2e;
}

.product-admin-image {
  position: relative;
  height: 220px;
  background: #f8f9fa;
}

.product-admin-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-admin-card:hover .product-admin-image img {
  transform: scale(1.05);
}

.product-admin-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.55);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.product-admin-card:hover .product-admin-overlay {
  opacity: 1;
}

.btn-overlay {
  border: none;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #fff;
  color: #0077b6;
  font-size: 1.15rem;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn-overlay:hover {
  background: #0077b6;
  color: #fff;
  transform: scale(1.08);
}

.product-admin-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.product-admin-header {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.product-admin-title {
  margin: 0;
  font-size: 1.08rem;
  font-weight: 700;
  line-height: 1.35;
}

.product-admin-title a {
  color: #2d3748;
  text-decoration: none;
}

.product-admin-title a:hover {
  color: #0077b6;
}

.product-admin-id {
  font-size: 11px;
  color: #718096;
  font-weight: 500;
}

.product-admin-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #4d6880;
  background: #f7fafc;
  border-radius: 999px;
  padding: 4px 10px;
  border: 1px solid #e2e8f0;
}

.product-admin-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-top: 8px;
  border-top: 1px solid #e2e8f0;
}

.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  font-size: 13px;
}

.info-item label {
  color: #718096;
  font-weight: 500;
  margin: 0;
}

.stock-value {
  font-weight: 600;
  color: #2d3748;
}

.stock-value.low-stock {
  color: #e53e3e;
}

.price-value {
  font-weight: 700;
  color: #0077b6;
  font-size: 14px;
}

.product-admin-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  padding: 12px 16px;
  background: #f7fafc;
  border-top: 1px solid #e8eff5;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 40px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  border: 1px solid transparent;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s ease;
}

.btn-edit {
  background: #0077b6;
  color: #fff;
}

.btn-edit:hover,
.btn-toggle-off:hover,
.btn-toggle-on:hover {
  transform: translateY(-2px);
}

.btn-toggle-off {
  background: #fff5f5;
  color: #e53e3e;
  border-color: #feb2b2;
}

.btn-toggle-on {
  background: #ecfdf5;
  color: #15803d;
  border-color: #bbf7d0;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 600px) {
  .product-admin-actions {
    grid-template-columns: 1fr;
  }
}
</style>