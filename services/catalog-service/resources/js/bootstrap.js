// Comentario de mantenimiento: Este archivo inicializa JavaScript propio del servicio.
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
