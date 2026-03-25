<template>
  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3>Tienda</h3>
        <ul>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'nina' } }">Ninas</RouterLink></li>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'nino' } }">Ninos</RouterLink></li>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }">Bebes</RouterLink></li>
          <li><RouterLink :to="{ name: 'store', query: { offers: '1' } }">Ofertas</RouterLink></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Informacion</h3>
        <ul>
          <li><a href="#">Sobre nosotros</a></li>
          <li><a href="#">Contacto</a></li>
          <li><a href="#">Preguntas frecuentes</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Ayuda</h3>
        <ul>
          <li><a href="#">Guia de tallas</a></li>
          <li><a href="#">Envios y entregas</a></li>
          <li><a href="#">Terminos y condiciones</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Contacto</h3>
        <address>
          <p><i class="fas fa-map-marker-alt" /> {{ supportAddress }}</p>
          <p><i class="fas fa-phone" /> {{ supportPhone }}</p>
          <p><i class="fas fa-envelope" /> {{ supportEmail }}</p>
        </address>
      </div>
    </div>

    <div class="footer-bottom">
      <p class="copyright">
        &copy; {{ currentYear }} {{ storeName }}. Todos los derechos reservados.
      </p>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
})

const currentYear = new Date().getFullYear()
const storeName = computed(() => props.settings?.store_name || 'Angelow')
const supportAddress = computed(() => props.settings?.support_address || 'Medellin, Colombia')
const supportPhone = computed(() => props.settings?.support_phone || '+57 300 000 0000')
const supportEmail = computed(() => props.settings?.support_email || 'soporte@angelow.com')
</script>
