// Archivo de constantes para eventos personalizados relacionados con la configuración del sitio
// Estas constantes se utilizan como identificadores únicos para eventos del DOM (CustomEvent)
// y permiten la comunicación entre componentes de forma desacoplada mediante un patrón pub/sub

// Constante que define el nombre del evento que se dispara cuando la configuración del sitio es actualizada
// Se utiliza con: window.dispatchEvent(new CustomEvent(SITE_SETTINGS_UPDATED_EVENT, { detail: data }))
// para notificar a otros componentes que los ajustes del sitio (tema, idioma, etc.) han cambiado
// El prefijo 'angelow:' evita conflictos con eventos nativos del navegador u otros frameworks
export const SITE_SETTINGS_UPDATED_EVENT = 'angelow:site-settings-updated'