import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { navigateToErrorPage } from './utils/errorPage'
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'intro.js/introjs.css'
import './styles/main.css'

// Convierte errores de renderizado en una pantalla recuperable sin exponer detalles internos.
const app = createApp(App)
app.config.errorHandler = (error, _instance, info) => {
  console.error('[Angelow] Error de interfaz:', error, info)
  void router.isReady()
    .then(() => navigateToErrorPage(router, 500, { from: router.currentRoute.value?.fullPath }))
    .catch(() => {})
}

// Monta la SPA de Vue con el router principal y los estilos globales ya cargados.
app.use(router).mount('#app')
