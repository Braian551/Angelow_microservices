# auth-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_auth`, responsable de usuarios, sesiones, tokens, acceso con Google, recuperación de contraseña e intentos de inicio de sesión. Se incluyen las tablas propias del dominio y se omiten tablas técnicas de Laravel como `cache`, `jobs`, `failed_jobs` y `job_batches`.

## Diagrama

```plantuml
@startuml
title angelow_auth - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "users" as auth_users {
  * id : varchar(20) <<PK>>
  --
  name : varchar(100)
  email : varchar(100) <<UQ>>
  phone : varchar(15)
  password : varchar(255)
  image : varchar(255)
  role : enum(customer, admin)
  is_blocked : boolean
  last_access : datetime
  remember_token : varchar(255)
  token_expiry : datetime
}

entity "access_tokens" as auth_access_tokens {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  token : varchar(255)
  ip_address : varchar(45)
  user_agent : text
  expires_at : timestamp
  is_revoked : boolean
}

entity "google_auth" as auth_google_auth {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  google_id : varchar(255) <<UQ>>
  access_token : varchar(255)
  created_at : timestamp
}

entity "password_resets" as auth_password_resets {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  token : varchar(255)
  expires_at : timestamp
  is_used : boolean
  created_at : timestamp
}

entity "sessions" as auth_sessions {
  * id : varchar(255) <<PK>>
  --
  user_id : varchar(20) <<NULL>>
  ip_address : varchar(45)
  user_agent : text
  payload : text
  last_activity : int
}

entity "login_attempts" as auth_login_attempts_legacy {
  * id : int <<PK>>
  --
  username : varchar(255)
  ip_address : varchar(45)
  attempt_date : timestamp
}

entity "auth_login_attempts" as auth_login_attempts {
  * id : bigint <<PK>>
  --
  credential : varchar(150)
  ip_address : varchar(45)
  failed_attempts : smallint
  last_failed_at : timestamp
  blocked_until : timestamp
}

entity "personal_access_tokens" as auth_personal_tokens {
  * id : bigint <<PK>>
  --
  tokenable_type : varchar
  tokenable_id : varchar
  name : text
  token : varchar(64) <<UQ>>
  abilities : text
  last_used_at : timestamp
  expires_at : timestamp
}

auth_users ||--o{ auth_access_tokens : user_id
auth_users ||--o{ auth_google_auth : user_id
auth_users ||--o{ auth_password_resets : user_id
auth_users ||--o{ auth_sessions : user_id
auth_users ||--o{ auth_personal_tokens : tokenable_id
auth_users ||..o{ auth_login_attempts_legacy : username/email
auth_users ||..o{ auth_login_attempts : credential

note right of auth_login_attempts
Control agregado por credencial e IP.
No depende de llave foránea física.
end note

note right of auth_login_attempts_legacy
Histórico de intentos individuales.
end note
@enduml
```

## Fuentes revisadas

- `services/auth-service/database/migrations/2026_02_16_000001_create_users_table.php`
- `services/auth-service/database/migrations/2026_02_16_000002_create_login_attempts_table.php`
- `services/auth-service/database/migrations/2026_02_16_185802_create_personal_access_tokens_table.php`
- `services/auth-service/database/migrations/2026_03_24_010000_create_auth_domain_tables.php`
- `services/auth-service/database/migrations/2026_06_21_000001_create_auth_login_attempts_table.php`
- `services/auth-service/app/Models/*.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
