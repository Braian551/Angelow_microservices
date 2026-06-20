// Configuración global de Axios para el auth-service
// Expone Axios en window para que esté disponible en las vistas Blade
// y configura el encabezado X-Requested-With para que Laravel reconozca
// las peticiones como AJAX.
//
// La SPA de Angelow tiene su propia configuración de Axios en el
// frontend; esta configuración solo aplica a las vistas del servicio.

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
