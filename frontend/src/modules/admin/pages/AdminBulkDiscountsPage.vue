<template>
  <div>
    <AdminPageHeader icon="fas fa-layer-group" title="Descuentos por cantidad" subtitle="Configura descuentos automaticos por volumen de compra." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Descuentos por cantidad' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nueva regla</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Reglas de descuento por cantidad" icon="fas fa-layer-group" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="rules.length === 0" icon="fas fa-layer-group" title="Sin reglas de descuento" description="Crea reglas para ofrecer descuentos por volumen." />
      <table v-else class="admin-table">
        <thead><tr><th>Producto / Categoria</th><th>Cantidad minima</th><th>Descuento (%)</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="r in rules" :key="r.id">
            <td>{{ r.product_name || r.category_name || 'Global' }}</td>
            <td>{{ r.min_quantity }}</td>
            <td><strong>{{ r.discount_percent }}%</strong></td>
            <td><span class="status-badge" :class="r.active ? 'approved' : 'rejected'">{{ r.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <button class="btn btn-sm btn-secondary" @click="openModal(r)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" @click="deleteRule(r.id)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal">
        <div class="modal-header"><h3>{{ editing ? 'Editar regla' : 'Nueva regla' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Aplicar a</label>
            <select v-model="form.scope" class="form-input"><option value="global">Global</option><option value="category">Categoria</option><option value="product">Producto</option></select>
          </div>
          <div v-if="form.scope === 'category'" class="form-group"><label>ID Categoria</label><input v-model.number="form.category_id" type="number" class="form-input" /></div>
          <div v-if="form.scope === 'product'" class="form-group"><label>ID Producto</label><input v-model.number="form.product_id" type="number" class="form-input" /></div>
          <div class="form-group"><label>Cantidad minima</label><input v-model.number="form.min_quantity" type="number" class="form-input" min="1" /></div>
          <div class="form-group"><label>Descuento (%)</label><input v-model.number="form.discount_percent" type="number" class="form-input" min="1" max="100" /></div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveRule">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { discountHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const rules = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const form = ref({ scope: 'global', category_id: null, product_id: null, min_quantity: 2, discount_percent: 10, active: true })

function openModal(rule = null) {
  editing.value = rule ? rule.id : null
  form.value = rule
    ? { ...rule }
    : { scope: 'global', category_id: null, product_id: null, min_quantity: 2, discount_percent: 10, active: true }
  showModal.value = true
}

async function loadRules() {
  loading.value = true
  try {
    const { data } = await discountHttp.get('/admin/bulk-discounts')
    rules.value = data.data || data || []
  } catch { rules.value = [] } finally { loading.value = false }
}

async function saveRule() {
  if (!form.value.min_quantity || !form.value.discount_percent) { showSnackbar({ type: 'warning', message: 'Completa todos los campos' }); return }
  try {
    if (editing.value) {
      await discountHttp.put(`/admin/bulk-discounts/${editing.value}`, form.value)
      showSnackbar({ type: 'success', message: 'Regla actualizada' })
    } else {
      await discountHttp.post('/admin/bulk-discounts', form.value)
      showSnackbar({ type: 'success', message: 'Regla creada' })
    }
    showModal.value = false
    await loadRules()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar regla' }) }
}

async function deleteRule(id) {
  if (!confirm('¿Eliminar esta regla?')) return
  try {
    await discountHttp.delete(`/admin/bulk-discounts/${id}`)
    showSnackbar({ type: 'success', message: 'Regla eliminada' })
    await loadRules()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadRules)
</script>
