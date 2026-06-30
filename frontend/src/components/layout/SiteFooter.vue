<!--
  SiteFooter.vue
  Componente de pie de página principal del sitio.
  Muestra enlaces de navegación, información de contacto,
  redes sociales y copyright. Se adapta a la configuración
  de la tienda mediante la prop 'settings'.
-->
<template>
  <footer class="main-footer">
    <div class="footer-container">
      <!-- Columna de navegación de la tienda: enlaces a categorías por género y ofertas -->
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

      <!-- Columna de información general de la empresa -->
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

      <!-- Columna de ayuda al cliente: guías, envíos, devoluciones y políticas -->
      <div class="footer-column">
        <h3>Ayuda</h3>
        <ul>
          <li><a href="#">Guía de tallas</a></li>
          <li><a href="#">Envíos y entregas</a></li>
          <li><a href="#">Devoluciones</a></li>
          <li><RouterLink :to="{ name: 'terms-and-conditions' }" class="footer-legal-link">Términos y condiciones</RouterLink></li>
          <li><a href="#">Política de privacidad</a></li>
        </ul>
      </div>

      <!-- Columna de contacto: datos de soporte y redes sociales -->
      <div class="footer-column">
        <h3>Contacto</h3>
        <address>
          <!-- Cada línea muestra un dato de contacto con su ícono de Font Awesome -->
          <p><i class="fas fa-map-marker-alt" /> {{ supportAddress }}</p>
          <p><i class="fas fa-phone" /> {{ supportPhone }}</p>
          <p><i class="fas fa-envelope" /> {{ supportEmail }}</p>
          <!-- Los campos opcionales (horarios, WhatsApp) solo se muestran si tienen valor -->
          <p v-if="supportHours"><i class="fas fa-clock" /> {{ supportHours }}</p>
          <p v-if="supportWhatsApp"><i class="fab fa-whatsapp" /> {{ supportWhatsApp }}</p>
        </address>
        <!--
          Renderizado dinámico de redes sociales.
          v-for itera sobre el array 'socialLinks' (solo redes con URL configurada).
          Cada enlace abre en nueva pestaña con rel="noopener noreferrer" por seguridad.
          aria-label proporciona acceso para lectores de pantalla.
        -->
        <div class="social-links">
          <a v-for="social in socialLinks" :key="social.key" :href="social.url" :aria-label="social.label" target="_blank" rel="noopener noreferrer">
            <i :class="social.icon" />
          </a>
        </div>
      </div>
    </div>

    <!-- Sección inferior del pie de página: lema de la tienda y copyright -->
    <div class="footer-bottom">
      <!-- El lema (tagline) solo se muestra si está configurado en settings -->
      <p v-if="storeTagline" class="footer-tagline">{{ storeTagline }}</p>
      <!-- Copyright dinámico con año actual y nombre de la tienda -->
      <p class="copyright">
        &copy; {{ currentYear }} {{ storeName }}. Todos los derechos reservados.
      </p>
    </div>
  </footer>
</template>

<script setup>
/* Importación de 'computed' para crear propiedades computadas reactivas */
import { computed } from 'vue'
/* Importación de RouterLink para generar enlaces de navegación interna */
import { RouterLink } from 'vue-router'

/*
 * Definición de props del componente.
 * 'settings' contiene la configuración de la tienda (nombre, contacto, redes sociales, etc.).
 * Si no se proporciona, se usa un objeto vacío como valor predeterminado.
 */
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
})

/* Obtiene el año actual para mostrar en el copyright */
const currentYear = new Date().getFullYear()

/*
 * Propiedades computadas que extraen valores de configuración de la tienda.
 * Cada una usa el operador de encadenamiento optional (?.) para evitar errores
 * si 'settings' es undefined, y proporciona un valor por defecto si la
 * configuración específica no está definida.
 */
const storeName = computed(() => props.settings?.store_name || 'Angelow')
const storeTagline = computed(() => props.settings?.store_tagline || '')
const supportAddress = computed(() => props.settings?.support_address || 'Medellin, Colombia')
const supportPhone = computed(() => props.settings?.support_phone || '+57 300 000 0000')
const supportEmail = computed(() => props.settings?.support_email || 'soporte@angelow.com')
const supportHours = computed(() => props.settings?.support_hours || '')
const supportWhatsApp = computed(() => props.settings?.support_whatsapp || '')

/*
 * Propiedad computada que genera la lista de enlaces a redes sociales.
 * Construye un array con objetos que contienen la clave, URL, ícono (Font Awesome)
 * y etiqueta de accesibilidad para cada red social.
 * El método .filter() elimina las entradas cuya URL sea falsy (undefined, null o ''),
 * de modo que solo se renderizan las redes sociales que tienen una URL configurada.
 */
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
