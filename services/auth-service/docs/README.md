# Flujo de auth-service

```mermaid
flowchart TD
  A[Frontend] --> B[POST /api/auth/register]
  A --> C[POST /api/auth/login]
  C --> D[AuthService]
  D --> E[UserRepository]
  E --> F[(users)]
  D --> G[(personal_access_tokens)]
  A --> H[GET /api/auth/me]
```

## Patrones de diseño

- `Repository Pattern`: acceso a datos desacoplado.
- `Service Layer`: reglas de negocio de autenticación.
- `DTO + Request Validation`: entrada controlada.
- `Token-based auth`: Sanctum para sesiones API.
