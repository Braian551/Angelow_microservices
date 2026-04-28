# Auth Service

Microservicio Laravel de autenticación y sesión para Angelow.

## Endpoints

- `GET /api/health`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

## Tablas de dominio

- `users`
- `access_tokens`
- `google_auth`
- `login_attempts`
- `password_resets`
- `personal_access_tokens`
- `sessions`

## Patrones usados

- Repository Pattern
- Service Layer
- DTO + Form Request
- Tokens con Sanctum
