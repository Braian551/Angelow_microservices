<template>
  <div>
    <AdminPageHeader icon="fas fa-cog" title="Configuracion" subtitle="Ajustes generales de la tienda." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Configuracion' }]">
      <template #actions>
        <button class="btn btn-primary btn-lg" @click="saveSettings" :disabled="saving"><i class="fas fa-save"></i> Guardar cambios</button>
      </template>
    </AdminPageHeader>

    <div class="settings-grid">
      <AdminCard title="Informacion de la tienda" icon="fas fa-store">
        <div class="form-group"><label>Nombre de la tienda</label><input v-model="settings.store_name" class="form-input" /></div>
        <div class="form-group"><label>Eslogan</label><input v-model="settings.slogan" class="form-input" /></div>
        <div class="form-group"><label>Email de contacto</label><input v-model="settings.contact_email" class="form-input" type="email" /></div>
        <div class="form-group"><label>Telefono</label><input v-model="settings.phone" class="form-input" /></div>
        <div class="form-group"><label>Direccion</label><textarea v-model="settings.address" class="form-input" rows="2"></textarea></div>
      </AdminCard>

      <AdminCard title="Logo y branding" icon="fas fa-image">
        <div class="form-group"><label>Logo principal</label>
          <div class="image-upload-zone" @click="$refs.logoInput.click()"><i class="fas fa-cloud-upload-alt"></i><p>Subir logo</p></div>
          <input ref="logoInput" type="file" accept="image/*" style="display:none;" @change="onLogoSelect" />
          <img v-if="settings.logo_url" :src="settings.logo_url" style="max-width:200px;margin-top:0.5rem;" />
        </div>
        <div class="form-group"><label>Favicon URL</label><input v-model="settings.favicon_url" class="form-input" /></div>
        <div class="form-group"><label>Color primario</label>
          <div style="display:flex;gap:0.5rem;align-items:center;">
            <input v-model="settings.primary_color" type="color" style="width:50px;height:35px;border:none;cursor:pointer;" />
            <input v-model="settings.primary_color" class="form-input" style="width:120px;" />
          </div>
        </div>
      </AdminCard>

      <AdminCard title="Redes sociales" icon="fab fa-instagram">
        <div class="form-group"><label><i class="fab fa-instagram"></i> Instagram</label><input v-model="settings.instagram" class="form-input" placeholder="https://instagram.com/..." /></div>
        <div class="form-group"><label><i class="fab fa-facebook"></i> Facebook</label><input v-model="settings.facebook" class="form-input" placeholder="https://facebook.com/..." /></div>
        <div class="form-group"><label><i class="fab fa-tiktok"></i> TikTok</label><input v-model="settings.tiktok" class="form-input" placeholder="https://tiktok.com/..." /></div>
        <div class="form-group"><label><i class="fab fa-whatsapp"></i> WhatsApp</label><input v-model="settings.whatsapp" class="form-input" placeholder="+57..." /></div>
      </AdminCard>

      <AdminCard title="SEO y metadatos" icon="fas fa-search">
        <div class="form-group"><label>Meta titulo</label><input v-model="settings.meta_title" class="form-input" /></div>
        <div class="form-group"><label>Meta descripcion</label><textarea v-model="settings.meta_description" class="form-input" rows="2"></textarea></div>
        <div class="form-group"><label>Palabras clave</label><input v-model="settings.meta_keywords" class="form-input" placeholder="Separadas por comas" /></div>
      </AdminCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { catalogHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const saving = ref(false)
const settings = ref({
  store_name: '', slogan: '', contact_email: '', phone: '', address: '',
  logo_url: '', favicon_url: '', primary_color: '#0077b6',
  instagram: '', facebook: '', tiktok: '', whatsapp: '',
  meta_title: '', meta_description: '', meta_keywords: '',
})

function onLogoSelect(e) {
  const file = e.target.files?.[0]
  if (file) settings.value.logo_url = URL.createObjectURL(file)
}

async function loadSettings() {
  try {
    const { data } = await catalogHttp.get('/admin/settings')
    const s = data.data || data || {}
    Object.keys(settings.value).forEach(k => { if (s[k] !== undefined) settings.value[k] = s[k] })
  } catch { /* Usar valores por defecto */ }
}

async function saveSettings() {
  saving.value = true
  try {
    await catalogHttp.put('/admin/settings', settings.value)
    showSnackbar({ type: 'success', message: 'Configuracion guardada correctamente' })
  } catch {
    showSnackbar({ type: 'error', message: 'Error al guardar configuracion' })
  } finally { saving.value = false }
}

onMounted(loadSettings)
</script>

<style scoped>
.settings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(480px, 1fr)); gap: 1.5rem; }
@media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
</style>
