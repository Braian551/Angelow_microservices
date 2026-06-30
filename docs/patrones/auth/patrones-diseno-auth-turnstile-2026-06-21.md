# Patrones de diseño en autenticación con verificación de seguridad

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Variables de entorno](#variables-de-entorno)
- [Decisiones de seguridad](#decisiones-de-seguridad)
<!-- indice:auto:end -->

## Objetivo

Proteger los flujos nativos de registro, inicio de sesión y recuperación de contraseña con Cloudflare Turnstile, sin modificar el flujo de Google/Firebase y sin exponer secretos en frontend ni archivos versionados.

## Patrones aplicados

- **Facade**: `services/auth-service/app/Services/TurnstileVerificationService.php` encapsula la validación remota contra Cloudflare para que controladores y requests no dupliquen llamadas sensibles.
- **State**: `services/auth-service/app/Services/LoginAttemptProtectionService.php` administra el estado de intentos fallidos, solicitud de verificación y bloqueo temporal por combinación de credencial e IP.
- **Reusable Component**: `frontend/src/components/security/TurnstileWidget.vue` centraliza el renderizado, emisión de token y reseteo de la verificación para login, registro y recuperación.

## Archivos intervenidos

- `services/auth-service/app/Http/Controllers/Api/Auth/LoginController.php`
- `services/auth-service/app/Http/Controllers/Api/Auth/RegisterController.php`
- `services/auth-service/app/Http/Controllers/Api/Auth/PasswordRecoveryController.php`
- `services/auth-service/app/Http/Requests/LoginRequest.php`
- `services/auth-service/app/Http/Requests/RegisterRequest.php`
- `services/auth-service/app/Http/Requests/PasswordRecoveryCodeRequest.php`
- `services/auth-service/app/Models/AuthLoginAttempt.php`
- `services/auth-service/app/Services/TurnstileVerificationService.php`
- `services/auth-service/app/Services/LoginAttemptProtectionService.php`
- `services/auth-service/database/migrations/2026_06_21_000001_create_auth_login_attempts_table.php`
- `services/auth-service/config/services.php`
- `frontend/src/components/security/TurnstileWidget.vue`
- `frontend/src/modules/auth/pages/LoginPage.vue`
- `frontend/src/modules/auth/pages/RegisterPage.vue`
- `frontend/src/modules/auth/pages/ForgotPasswordPage.vue`
- `frontend/src/modules/admin/pages/AdminForgotPasswordPage.vue`

## Variables de entorno

- Frontend: `VITE_TURNSTILE_SITE_KEY`, pública y consumida por Vite.
- Backend: `TURNSTILE_SECRET_KEY`, privada y consumida únicamente por `auth-service`.
- Backend: `TURNSTILE_VERIFY_URL`, `AUTH_LOGIN_CAPTCHA_AFTER_ATTEMPTS`, `AUTH_LOGIN_TEMP_BLOCK_AFTER_ATTEMPTS`, `AUTH_LOGIN_TEMP_BLOCK_MINUTES`.

No se agregaron dependencias npm ni Composer. La integración usa el script público de Cloudflare en frontend y el cliente HTTP nativo de Laravel en backend.

## Decisiones de seguridad

- Registro y solicitud/reenvío de recuperación exigen verificación antes de crear usuario o enviar correo.
- Login solo muestra verificación cuando el backend responde que es obligatoria.
- Después de intentos fallidos repetidos se aplica bloqueo temporal con mensaje controlado.
- Los tokens, contraseñas y secretos no se registran ni se devuelven en respuestas.
