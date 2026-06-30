# Configuración de cuenta y preferencias

<!-- indice:auto:start -->
## Índice

- [Objetivo](#objetivo)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Problema resuelto](#problema-resuelto)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Objetivo

Alinear la configuración del cliente con el flujo vigente de cuenta: el correo queda como dato de solo lectura, las pestañas disponibles se limitan a Perfil, Seguridad y Notificaciones, y los interruptores de preferencias conservan dimensiones estables en la interfaz.

## Patrones aplicados

- **State**: `frontend/src/modules/account/pages/SettingsPage.vue` mantiene una lista explícita de pestañas permitidas para que cualquier hash no disponible regrese a Perfil sin exponer flujos retirados.
- **Adapter**: `frontend/src/modules/account/views/SettingsView.css` adapta el control visual del switch a la estructura de formulario existente, evitando una implementación nueva para preferencias binarias.
- **Reusable Component**: `frontend/src/modules/auth/views/ForgotPasswordView.css` conserva el layout compartido de autenticación y solo ajusta la presentación del logo de recuperación.

## Archivos intervenidos

- `frontend/src/modules/account/pages/SettingsPage.vue`
- `frontend/src/modules/account/views/SettingsView.css`
- `frontend/src/modules/auth/views/ForgotPasswordView.css`
- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`

## Problema resuelto

Se elimina el acceso visible al cambio de correo desde la cuenta del cliente, se evita que enlaces antiguos abran una pestaña inexistente, se corrige el switch de preferencias que se estiraba por un error de posicionamiento CSS y se centra el logo en recuperación de contraseña.

## Documentos relacionados

- [Índice de patrones](../README.md)
- [Matriz de requerimientos funcionales](../../referencias/matriz-requerimientos-funcionales-actualizada.md)
