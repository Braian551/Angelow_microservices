<template>
  <div>
    <AdminPageHeader icon="fas fa-dollar-sign" title="Reglas de envio por precio" subtitle="Configura tarifas de envio segun el total del pedido." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Reglas de envio' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nueva regla</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Reglas de envio" icon="fas fa-shipping-fast" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="rules.length === 0" icon="fas fa-shipping-fast" title="Sin reglas de envio" description="Crea reglas para calcular el envio automaticamente." />
      <table v-else class="admin-table">
        <thead><tr><th>Desde ($)</th><th>Hasta ($)</th><th>Costo envio ($)</th><th>Envio gratis</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="r in rules" :key="r.id">
            <td>${{ Number(r.min_amount || 0).toLocaleString() }}</td>
            <td>{{ r.max_amount ? `$${Number(r.max_amount).toLocaleString()}` : 'Sin limite' }}</td>
            <td>{{ r.free_shipping ? 'Gratis' : `$${Number(r.shipping_cost || 0).toLocaleString()}` }}</td>
            <td><span class="status-badge" :class="r.free_shipping ? 'approved' : 'pending'">{{ r.free_shipping ? 'Si' : 'No' }}</span></td>
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
          <div class="form-group"><label>Monto minimo ($)</label><input v-model.number="form.min_amount" type="number" class="form-input" min="0" /></div>
          <div class="form-group"><label>Monto maximo ($) <small>(dejar vacio = sin limite)</small></label><input v-model.number="form.max_amount" type="number" class="form-input" min="0" /></div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.free_shipping" /> Envio gratis</label></div>
          <div v-if="!form.free_shipping" class="form-group"><label>Costo de envio ($)</label><input v-model.number="form.shipping_cost" type="number" class="form-input" min="0" /></div>
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
import { shippingHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const rules = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const form = ref({ min_amount: 0, max_amount: null, shipping_cost: 0, free_shipping: false })

function openModal(rule = null) {
  editing.value = rule ? rule.id : null
  form.value = rule
    ? { ...rule }
    : { min_amount: 0, max_amount: null, shipping_cost: 0, free_shipping: false }
  showModal.value = true
}

async function loadRules() {
  loading.value = true
  try {
    const { data } = await shippingHttp.get('/admin/shipping-rules')
    rules.value = data.data || data || []
  } catch { rules.value = [] } finally { loading.value = false }
}

async function saveRule() {
  try {
    if (editing.value) {
      await shippingHttp.put(`/admin/shipping-rules/${editing.value}`, form.value)
      showSnackbar({ type: 'success', message: 'Regla actualizada' })
    } else {
      await shippingHttp.post('/admin/shipping-rules', form.value)
      showSnackbar({ type: 'success', message: 'Regla creada' })
    }
    showModal.value = false
    await loadRules()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar regla' }) }
}

async function deleteRule(id) {
  if (!confirm('¿Eliminar esta regla?')) return
  try {
    await shippingHttp.delete(`/admin/shipping-rules/${id}`)
    showSnackbar({ type: 'success', message: 'Regla eliminada' })
    await loadRules()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadRules)
</script>
