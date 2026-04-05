<template>
  <div>
    <AdminPageHeader icon="fas fa-images" title="Sliders" subtitle="Gestiona las imagenes del carrusel principal." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Sliders' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nuevo slider</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Sliders del carrusel" icon="fas fa-images">
      <div v-if="loading" class="admin-shimmer-grid">
        <div v-for="i in 3" :key="i" class="admin-shimmer shimmer-card"></div>
      </div>
      <AdminEmptyState v-else-if="sliders.length === 0" icon="fas fa-images" title="Sin sliders" description="Agrega imagenes al carrusel principal de tu tienda." />
      <div v-else class="sliders-grid">
        <div v-for="s in sliders" :key="s.id" class="slider-card">
          <div class="slider-image-wrapper">
            <img :src="s.image_url || '/assets/foundnotimages/default.png'" :alt="s.title" />
            <div class="slider-overlay">
              <span class="status-badge" :class="s.active ? 'approved' : 'rejected'">{{ s.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </div>
          <div class="slider-info">
            <h4>{{ s.title || 'Sin titulo' }}</h4>
            <p class="slider-subtitle">{{ s.subtitle || '' }}</p>
            <p class="slider-link" v-if="s.link_url"><i class="fas fa-link"></i> {{ s.link_url }}</p>
            <div class="slider-actions">
              <span class="slider-order">Orden: {{ s.sort_order ?? 0 }}</span>
              <div>
                <button class="btn btn-sm btn-secondary" @click="openModal(s)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" @click="deleteSlider(s.id)"><i class="fas fa-trash"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal" style="max-width:600px;">
        <div class="modal-header"><h3>{{ editing ? 'Editar slider' : 'Nuevo slider' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Titulo</label><input v-model="form.title" class="form-input" /></div>
          <div class="form-group"><label>Subtitulo</label><input v-model="form.subtitle" class="form-input" /></div>
          <div class="form-group"><label>URL destino</label><input v-model="form.link_url" class="form-input" placeholder="/tienda o https://..." /></div>
          <div class="form-group"><label>Imagen</label>
            <div class="image-upload-zone" @click="$refs.fileInput.click()"><i class="fas fa-cloud-upload-alt"></i><p>Subir imagen</p></div>
            <input ref="fileInput" type="file" accept="image/*" style="display:none;" @change="onFileSelect" />
            <img v-if="form.image_url" :src="form.image_url" style="max-width:100%;max-height:200px;margin-top:0.5rem;border-radius:6px;" />
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:1;"><label>Orden</label><input v-model.number="form.sort_order" type="number" class="form-input" min="0" /></div>
            <div class="form-group" style="flex:1;padding-top:1.5rem;"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveSlider">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { catalogHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const sliders = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const emptyForm = { title: '', subtitle: '', link_url: '', image_url: '', sort_order: 0, active: true }
const form = ref({ ...emptyForm })

function openModal(slider = null) {
  editing.value = slider ? slider.id : null
  form.value = slider ? { ...slider } : { ...emptyForm }
  showModal.value = true
}

function onFileSelect(e) {
  const file = e.target.files?.[0]
  if (file) form.value.image_url = URL.createObjectURL(file)
}

async function loadSliders() {
  loading.value = true
  try {
    const { data } = await catalogHttp.get('/admin/sliders')
    sliders.value = data.data || data || []
  } catch { sliders.value = [] } finally { loading.value = false }
}

async function saveSlider() {
  if (!form.value.title?.trim()) { showSnackbar({ type: 'warning', message: 'El titulo es requerido' }); return }
  try {
    if (editing.value) {
      await catalogHttp.put(`/admin/sliders/${editing.value}`, form.value)
      showSnackbar({ type: 'success', message: 'Slider actualizado' })
    } else {
      await catalogHttp.post('/admin/sliders', form.value)
      showSnackbar({ type: 'success', message: 'Slider creado' })
    }
    showModal.value = false
    await loadSliders()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar slider' }) }
}

async function deleteSlider(id) {
  if (!confirm('¿Eliminar este slider?')) return
  try {
    await catalogHttp.delete(`/admin/sliders/${id}`)
    showSnackbar({ type: 'success', message: 'Slider eliminado' })
    await loadSliders()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadSliders)
</script>

<style scoped>
.sliders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.slider-card { border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background: #fff; }
.slider-image-wrapper { position: relative; height: 180px; overflow: hidden; background: #f5f5f5; }
.slider-image-wrapper img { width: 100%; height: 100%; object-fit: cover; }
.slider-overlay { position: absolute; top: 8px; right: 8px; }
.slider-info { padding: 1rem; }
.slider-info h4 { margin: 0 0 0.25rem 0; font-size: 1rem; }
.slider-subtitle { color: #666; font-size: 0.85rem; margin: 0 0 0.5rem 0; }
.slider-link { color: #0077b6; font-size: 0.8rem; margin: 0 0 0.5rem 0; }
.slider-actions { display: flex; justify-content: space-between; align-items: center; }
.slider-order { font-size: 0.8rem; color: #888; }
</style>
