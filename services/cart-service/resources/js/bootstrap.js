// Configuración global de Axios para el cart-service
// Expone Axios en window y configura el encabezado X-Requested-With
// para que Laravel reconozca las peticiones como AJAX.
//
// Este servicio es principalmente API, por lo que Axios tiene uso
// limitado (solo aplica a la vista welcome).

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
