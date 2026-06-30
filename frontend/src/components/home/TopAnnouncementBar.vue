<template>
  <!-- Se renderiza la barra solo si hay un mensaje válido para mostrar (v-if evita renderizar elementos vacíos) -->
  <div v-if="displayMessage" class="announcement-bar" :style="barStyle">
    <p>
      <!-- Se muestra el ícono solo si el anuncio define uno; se aplica dinámicamente con la clase de Font Awesome -->
      <i v-if="announcement.icon" class="fas" :class="announcement.icon" />
      <!-- Se interpola el mensaje calculado que ya está saneado (sin espacios vacíos) -->
      {{ displayMessage }}
    </p>
  </div>
</template>

<script setup>
// Se importa computed para crear propiedades computadas reactivas que dependen de los props
import { computed } from 'vue'

// Se definen las propiedades que este componente recibe desde el componente padre
const props = defineProps({
  announcement: {
    type: Object, // El anuncio es un objeto con campos como message, title, icon, background_color, text_color
    default: null, // Si no se pasa ningún anuncio, el valor por defecto es null (el componente no se renderiza)
  },
})

// Propiedad computada que extrae el texto a mostrar en la barra de anuncios
// Prioriza 'message', si no existe usa 'title', y si ambos faltan devuelve cadena vacía
// Se convierte a String y se eliminan espacios en blanco para evitar mostrar cadenas vacías o solo espacios
const displayMessage = computed(() => {
  const rawMessage = props.announcement?.message ?? props.announcement?.title ?? ''
  const text = String(rawMessage || '').trim()
  return text || ''
})

// Aplica colores dinámicos guardados en el anuncio (sobreescribe el color fijo del CSS)
// Propiedad computada que construye un objeto de estilos en línea con los colores personalizados del anuncio
// Solo asigna backgroundColor y color si el anuncio define esos campos; si no, la barra usa los estilos CSS por defecto
const barStyle = computed(() => {
  const style = {}
  if (props.announcement?.background_color) style.backgroundColor = props.announcement.background_color
  if (props.announcement?.text_color) style.color = props.announcement.text_color
  return style
})
</script>
