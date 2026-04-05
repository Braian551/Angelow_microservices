<template>
  <div>
    <AdminPageHeader icon="fas fa-user-shield" title="Administradores" subtitle="Gestiona los usuarios con rol de administrador." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Administradores' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-user-plus"></i> Nuevo administrador</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Administradores del sistema" icon="fas fa-user-shield" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['circle','line','line','pill','line','pill','btn']" />
      <AdminEmptyState v-else-if="admins.length === 0" icon="fas fa-user-shield" title="Sin administradores" description="Agrega administradores para gestionar la tienda." />
      <table v-else class="admin-table">
        <thead><tr><th>Avatar</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Ultimo acceso</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="a in admins" :key="a.id">
            <td><div class="admin-avatar"><img :src="a.avatar_url || '/assets/foundnotimages/user-default.png'" :alt="a.name" /></div></td>
            <td><strong>{{ a.name }}</strong></td>
            <td>{{ a.email }}</td>
            <td><span class="status-badge approved">{{ a.role || 'admin' }}</span></td>
            <td>{{ a.last_login || '—' }}</td>
            <td><span class="status-badge" :class="a.active !== false ? 'approved' : 'rejected'">{{ a.active !== false ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <button class="btn btn-sm btn-secondary" @click="openModal(a)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" @click="deleteAdmin(a.id)" :disabled="a.id === currentUserId"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal">
        <div class="modal-header"><h3>{{ editing ? 'Editar administrador' : 'Nuevo administrador' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre</label>
            <input v-model="form.name" class="form-input" :class="{ 'input-error': errors.name }" @input="validateField('name')" />
            <span v-if="errors.name" class="field-error">{{ errors.name }}</span>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="form.email" type="email" class="form-input" :class="{ 'input-error': errors.email }" @input="validateField('email')" />
            <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
          </div>
          <div v-if="!editing" class="form-group">
            <label>Contrasena</label>
            <input v-model="form.password" type="password" class="form-input" :class="{ 'input-error': errors.password }" @input="validateField('password')" />
            <span v-if="errors.password" class="field-error">{{ errors.password }}</span>
          </div>
          <div class="form-group"><label>Rol</label>
            <select v-model="form.role" class="form-input"><option value="admin">Administrador</option><option value="super_admin">Super Admin</option></select>
          </div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveAdmin">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { authHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useSession } from '../../../composables/useSession'

const { showSnackbar } = useSnackbarSystem()
const { user } = useSession()
const currentUserId = ref(user.value?.id)
const admins = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const errors = ref({})
const emptyForm = { name: '', email: '', password: '', role: 'admin', active: true }
const form = ref({ ...emptyForm })

function validateField(field) {
  errors.value[field] = ''
  if (field === 'name' && !form.value.name?.trim()) errors.value.name = 'El nombre es requerido'
  if (field === 'email') {
    if (!form.value.email?.trim()) errors.value.email = 'El email es requerido'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.value.email = 'Email invalido'
  }
  if (field === 'password' && !editing.value && form.value.password.length < 6) errors.value.password = 'Minimo 6 caracteres'
}

function openModal(admin = null) {
  editing.value = admin ? admin.id : null
  form.value = admin ? { ...admin, password: '' } : { ...emptyForm }
  errors.value = {}
  showModal.value = true
}

async function loadAdmins() {
  loading.value = true
  try {
    const { data } = await authHttp.get('/admin/administrators')
    admins.value = data.data || data || []
  } catch { admins.value = [] } finally { loading.value = false }
}

async function saveAdmin() {
  ;['name', 'email'].forEach(validateField)
  if (!editing.value) validateField('password')
  if (Object.values(errors.value).some(Boolean)) return

  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
    if (editing.value) {
      await authHttp.put(`/admin/administrators/${editing.value}`, payload)
      showSnackbar({ type: 'success', message: 'Administrador actualizado' })
    } else {
      await authHttp.post('/admin/administrators', payload)
      showSnackbar({ type: 'success', message: 'Administrador creado' })
    }
    showModal.value = false
    await loadAdmins()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar administrador' }) }
}

async function deleteAdmin(id) {
  if (id === currentUserId.value) { showSnackbar({ type: 'warning', message: 'No puedes eliminarte a ti mismo' }); return }
  if (!confirm('¿Eliminar este administrador?')) return
  try {
    await authHttp.delete(`/admin/administrators/${id}`)
    showSnackbar({ type: 'success', message: 'Administrador eliminado' })
    await loadAdmins()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadAdmins)
</script>

<style scoped>
.admin-avatar { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e0e0e0; }
.admin-avatar img { width: 100%; height: 100%; object-fit: cover; }
</style>
