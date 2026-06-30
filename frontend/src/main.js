import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@fortawesome/fontawesome-free/css/all.min.css'
import './styles/main.css'

// Monta la SPA de Vue con el router principal y los estilos globales ya cargados.
createApp(App).use(router).mount('#app')
