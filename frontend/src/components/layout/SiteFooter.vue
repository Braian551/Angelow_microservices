<template>
  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3>Tienda</h3>
        <ul>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'nina' } }">Niñas</RouterLink></li>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'nino' } }">Niños</RouterLink></li>
          <li><RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }">Bebés</RouterLink></li>
          <li><a href="#">Novedades</a></li>
          <li><RouterLink :to="{ name: 'store', query: { offers: '1' } }">Ofertas</RouterLink></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Información</h3>
        <ul>
          <li><a href="#">Sobre nosotros</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">Contacto</a></li>
          <li><a href="#">Preguntas frecuentes</a></li>
          <li><a href="#">Sostenibilidad</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Ayuda</h3>
        <ul>
          <li><a href="#">Guía de tallas</a></li>
          <li><a href="#">Envíos y entregas</a></li>
          <li><a href="#">Devoluciones</a></li>
          <li><a href="#">Términos y condiciones</a></li>
          <li><a href="#">Política de privacidad</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Contacto</h3>
        <address>
          <p><i class="fas fa-map-marker-alt" /> {{ supportAddress }}</p>
          <p><i class="fas fa-phone" /> {{ supportPhone }}</p>
          <p><i class="fas fa-envelope" /> {{ supportEmail }}</p>
        </address>
        <div class="social-links">
          <a v-for="social in socialLinks" :key="social.key" :href="social.url" :aria-label="social.label" target="_blank" rel="noopener noreferrer">
            <i :class="social.icon" />
          </a>
        </div>
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

const socialLinks = computed(() => ([
  {
    key: 'facebook',
    url: props.settings?.social_facebook,
    icon: 'fab fa-facebook-f',
    label: 'Facebook',
  },
  {
    key: 'instagram',
    url: props.settings?.social_instagram,
    icon: 'fab fa-instagram',
    label: 'Instagram',
  },
  {
    key: 'tiktok',
    url: props.settings?.social_tiktok,
    icon: 'fab fa-tiktok',
    label: 'TikTok',
  },
  {
    key: 'whatsapp',
    url: props.settings?.social_whatsapp,
    icon: 'fab fa-whatsapp',
    label: 'WhatsApp',
  },
]).filter((item) => Boolean(item.url)))
</script>
