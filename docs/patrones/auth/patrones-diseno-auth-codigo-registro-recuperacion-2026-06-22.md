# Código compartido para registro y recuperación

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Variables de entorno](#variables-de-entorno)
- [Validación](#validación)
<!-- indice:auto:end -->

## Objetivo

Unificar la verificación de código usada en recuperación de contraseña y agregar verificación de correo antes del paso de teléfono en el registro nativo.

## Patrones aplicados

- **Facade**: `services/auth-service/app/Services/RegistrationVerificationService.php` encapsula generación, envío, verificación, cooldown y consumo del token temporal de registro.
- **State**: `frontend/src/modules/auth/pages/RegisterPage.vue` y `frontend/src/modules/auth/pages/ForgotPasswordPage.vue` controlan pasos, temporizadores, estados de código y bloqueo de acciones mientras hay solicitudes pendientes.
- **Reusable Component**: `frontend/src/modules/auth/components/AuthCodeVerification.vue` centraliza el campo de código, estado visual y reenvío para evitar diferencias entre registro y recuperación.

## Archivos intervenidos

- `frontend/src/modules/auth/components/AuthCodeVerification.vue`
- `frontend/src/modules/auth/pages/RegisterPage.vue`
- `frontend/src/modules/auth/pages/ForgotPasswordPage.vue`
- `frontend/src/modules/auth/views/RegisterView.css`
- `frontend/src/services/authApi.js`
- `services/auth-service/app/Http/Controllers/Api/Auth/RegisterController.php`
- `services/auth-service/app/Http/Controllers/Api/Auth/RegistrationVerificationController.php`
- `services/auth-service/app/Http/Requests/RegisterRequest.php`
- `services/auth-service/app/Http/Requests/RegistrationCodeRequest.php`
- `services/auth-service/app/Http/Requests/RegistrationVerifyCodeRequest.php`
- `services/auth-service/app/Services/RegistrationVerificationService.php`
- `services/auth-service/config/services.php`
- `services/auth-service/routes/api.php`

## Variables de entorno

- `REGISTRATION_VERIFICATION_CODE_TTL`: duración del código de registro.
- `REGISTRATION_VERIFICATION_RESEND_COOLDOWN`: espera entre envíos o reenvíos.
- `PHPMAILER_*`: configuración SMTP ya usada por correos transaccionales del `auth-service`.

No se agregaron dependencias npm ni Composer. El envío usa PHPMailer existente en el servicio.

## Validación

- `docker compose exec -T frontend npm run build`
- `php artisan route:list --path=auth/registration-verification`
- `php artisan config:clear`
