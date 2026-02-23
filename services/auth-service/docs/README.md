# Auth Service - Angelow Microservices

## Descripción
Servicio de autenticación para la plataforma Angelow. Maneja registro de usuarios, inicio de sesión y gestión de tokens.

## Arquitectura

```
app/
├── DTOs/                          # Data Transfer Objects
│   ├── RegisterUserDTO.php
│   └── LoginUserDTO.php
├── Exceptions/
│   └── AuthException.php          # Excepciones personalizadas
├── Http/
│   ├── Controllers/Api/Auth/
│   │   ├── RegisterController.php # POST /api/auth/register
│   │   └── LoginController.php    # POST /api/auth/login, logout, me
│   └── Requests/
│       ├── RegisterRequest.php    # Validaciones de registro
│       └── LoginRequest.php       # Validaciones de login
├── Models/
│   └── User.php                   # Eloquent Model
├── Repositories/
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   └── EloquentUserRepository.php
├── Services/
│   └── AuthService.php            # Lógica de negocio
└── Providers/
    └── RepositoryServiceProvider.php
```

## API Endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/auth/register` | No | Registro de usuario |
| POST | `/api/auth/login` | No | Inicio de sesión |
| POST | `/api/auth/logout` | Sí | Cerrar sesión |
| GET | `/api/auth/me` | Sí | Perfil del usuario |

## Patrones de Diseño
- **Repository Pattern**: Abstrae el acceso a datos
- **Service Layer**: Concentra la lógica de negocio
- **DTO Pattern**: Datos inmutables entre capas
- **Form Request**: Validaciones centralizadas
