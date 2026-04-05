<template>
  <div>
    <AdminPageHeader
      icon="fas fa-warehouse"
      title="Inventario"
      subtitle="Control de stock por producto y variante."
      :breadcrumbs="[{ label: 'Inventario' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="inventoryStats" />

    <div class="filters-bar">
      <div class="filter-group"><label>Buscar:</label><input type="text" v-model="search" placeholder="Producto..." @input="loadInventory"></div>
      <div class="filter-group"><label>Stock:</label>
        <select v-model="stockFilter" @change="loadInventory"><option value="">Todos</option><option value="low">Bajo stock (&lt;5)</option><option value="zero">Sin stock</option></select>
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState v-else-if="products.length === 0" icon="fas fa-warehouse" title="Sin productos" description="No se encontraron productos con los filtros actuales." />
      <div v-else class="table-responsive"><table class="dashboard-table">
        <thead><tr><th>Imagen</th><th>Producto</th><th>SKU</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td><img :src="p.image" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;" @error="onInventoryImageError($event, p.rawImage)"></td>
            <td><strong>{{ p.name }}</strong></td>
            <td>{{ p.sku || '-' }}</td>
            <td><strong :style="{ color: stockColor(p.stock ?? p.total_stock ?? 0) }">{{ p.stock ?? p.total_stock ?? 0 }}</strong></td>
            <td><span class="status-badge" :class="stockBadge(p.stock ?? p.total_stock ?? 0)">{{ stockLabel(p.stock ?? p.total_stock ?? 0) }}</span></td>
            <td><button class="action-btn edit" @click="adjustStock(p)" title="Ajustar stock"><i class="fas fa-sliders-h"></i></button></td>
          </tr>
        </tbody>
      </table></div>
    </AdminCard>

    <div v-if="showAdjust" class="admin-modal-overlay" @click.self="showAdjust = false">
      <div class="admin-modal" style="max-width:400px;">
        <div class="admin-modal-header"><h3>Ajustar stock</h3><button class="admin-modal-close" @click="showAdjust = false">&times;</button></div>
        <div class="admin-modal-body">
          <p style="margin-bottom:1rem;">{{ adjustProduct?.name }}</p>
          <div class="form-group"><label>Accion</label>
            <select v-model="adjustAction" class="form-control"><option value="add">Agregar</option><option value="subtract">Restar</option><option value="set">Establecer</option></select>
          </div>
          <div class="form-group"><label>Cantidad</label><input v-model="adjustQty" type="number" min="0" class="form-control"></div>
        </div>
        <div class="admin-modal-footer"><button class="btn btn-secondary" @click="showAdjust = false">Cancelar</button><button class="btn btn-primary" @click="saveAdjust">Aplicar</button></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()
const products = ref([]), loading = ref(true), search = ref(''), stockFilter = ref('')
const showAdjust = ref(false), adjustProduct = ref(null), adjustAction = ref('add'), adjustQty = ref(0)

const totalProducts = computed(() => products.value.length)
const inStock = computed(() => products.value.filter(p => (p.stock ?? p.total_stock ?? 0) > 5).length)
const lowStock = computed(() => products.value.filter(p => { const s = p.stock ?? p.total_stock ?? 0; return s > 0 && s <= 5 }).length)
const outOfStock = computed(() => products.value.filter(p => (p.stock ?? p.total_stock ?? 0) === 0).length)

const inventoryStats = computed(() => [
  { key: 'total', label: 'Total productos', value: String(totalProducts.value), icon: 'fas fa-box', color: 'primary' },
  { key: 'in-stock', label: 'En stock', value: String(inStock.value), icon: 'fas fa-check-circle', color: 'success' },
  { key: 'low', label: 'Bajo stock', value: String(lowStock.value), icon: 'fas fa-exclamation-triangle', color: 'warning' },
  { key: 'out', label: 'Sin stock', value: String(outOfStock.value), icon: 'fas fa-times-circle', color: 'danger' },
])

function stockColor(s) { return s === 0 ? 'var(--admin-error)' : s <= 5 ? '#8a6d00' : 'var(--admin-success)' }
function stockBadge(s) { return s === 0 ? 'cancelled' : s <= 5 ? 'pending' : 'active' }
function stockLabel(s) { return s === 0 ? 'Sin stock' : s <= 5 ? 'Bajo stock' : 'En stock' }
function onInventoryImageError(event, imagePath) { handleMediaError(event, imagePath, 'product') }

function adjustStock(p) { adjustProduct.value = p; adjustAction.value = 'add'; adjustQty.value = 0; showAdjust.value = true }
async function saveAdjust() {
  if (!adjustProduct.value?.id) return

  try {
    await catalogHttp.patch(`/admin/inventory/${adjustProduct.value.id}/stock`, {
      action: adjustAction.value,
      quantity: Number(adjustQty.value || 0),
    })
    showSnackbar({ type: 'success', message: 'Stock actualizado' })
    showAdjust.value = false
    await loadInventory()
  } catch {
    showSnackbar({ type: 'error', message: 'Error ajustando stock' })
  }
}

async function loadInventory() {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (stockFilter.value) params.stock_filter = stockFilter.value === 'zero' ? 'out' : stockFilter.value

    const r = await catalogHttp.get('/admin/inventory', { params })
    let data = r.data?.data || r.data || []; data = Array.isArray(data) ? data : (data.data || [])
    products.value = data.map((item) => ({
      ...item,
      name: item.product_name || item.name || item.nombre || 'Sin nombre',
      stock: Number(item.stock ?? item.quantity ?? item.total_stock ?? 0),
      rawImage: item.primary_image || item.image || item.product_image || item.imagen || null,
      image: resolveMediaUrl(item.primary_image || item.image || item.product_image || item.imagen || null, 'product'),
    }))
  } catch { showSnackbar({ type: 'error', message: 'Error cargando inventario' }) }
  finally { loading.value = false }
}

onMounted(loadInventory)
</script>
