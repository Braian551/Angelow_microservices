# Despliegue desde cero de ANGELOW con Nginx

Guía para preparar un servidor Ubuntu limpio y desplegar ANGELOW con Docker Compose, PostgreSQL, Redis, microservicios Laravel, frontend Vue compilado y Nginx como proxy público.

> Esta guía es para el servidor definitivo. No usa Apache2 ni depende del servidor temporal.

## 1. Requisitos

- Ubuntu 22.04 o 24.04 LTS.
- Acceso `root` por SSH.
- Dominio apuntando al servidor, por ejemplo `angelow.online`.
- Mínimo recomendado: 2 vCPU, 8 GB RAM, 100 GB NVMe.
- Proyecto local listo en `C:\laragon\www\Angelow_microservices`.

## 2. Preparar el servidor

```bash
apt update && apt upgrade -y
apt install -y ca-certificates curl gnupg unzip rsync nginx certbot python3-certbot-nginx
curl -fsSL https://get.docker.com | sh
apt install -y docker-compose-plugin
systemctl enable docker nginx
systemctl start docker nginx
```

Crear swap de 2 GB:

```bash
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
```

Verificar:

```bash
docker --version
docker compose version
nginx -v
swapon --show
```

## 3. Crear estructura del proyecto

```bash
mkdir -p /var/www/angelow_microservices
mkdir -p /var/www/angelow_microservices/services
mkdir -p /var/www/angelow_microservices/frontend
mkdir -p /var/www/angelow_microservices/nginx/conf.d
mkdir -p /var/www/angelow_microservices/postgres/init
mkdir -p /var/www/angelow_microservices/uploads
mkdir -p /var/www/angelow_microservices/logs
cd /var/www/angelow_microservices
```

## 4. Crear `.env` raíz del servidor

Crear `/var/www/angelow_microservices/.env`:

```bash
cat > /var/www/angelow_microservices/.env <<'EOF'
DOMAIN=angelow.online
DB_PASSWORD=CAMBIAR_PASSWORD_POSTGRES_SEGURO
INTERNAL_API_TOKEN=CAMBIAR_TOKEN_INTERNO_SEGURO

AUTH_APP_KEY=base64:GENERAR_APP_KEY
CATALOG_APP_KEY=base64:GENERAR_APP_KEY
CART_APP_KEY=base64:GENERAR_APP_KEY
ORDER_APP_KEY=base64:GENERAR_APP_KEY
PAYMENT_APP_KEY=base64:GENERAR_APP_KEY
DISCOUNT_APP_KEY=base64:GENERAR_APP_KEY
SHIPPING_APP_KEY=base64:GENERAR_APP_KEY
NOTIFICATION_APP_KEY=base64:GENERAR_APP_KEY
AUDIT_APP_KEY=base64:GENERAR_APP_KEY

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=seguridad@angelow.com
MAIL_FROM_NAME="Seguridad Angelow"

VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_STORAGE_BUCKET=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_FIREBASE_MEASUREMENT_ID=
EOF
chmod 600 /var/www/angelow_microservices/.env
```

Generar valores seguros:

```bash
openssl rand -hex 32
```

Para `APP_KEY`, dentro de cualquier contenedor Laravel o en local:

```bash
php artisan key:generate --show
```

## 5. Subir el código desde Windows

No subir `node_modules`, `.git`, `vendor` ni dumps temporales.

Opción simple con `scp`:

```powershell
scp -r "C:\laragon\www\Angelow_microservices\services\auth-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\catalog-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\cart-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\order-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\payment-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\discount-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\shipping-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\notification-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
scp -r "C:\laragon\www\Angelow_microservices\services\audit-service" root@IP_SERVIDOR:/var/www/angelow_microservices/services/
```

Para frontend, preparar copia limpia:

```powershell
robocopy "C:\laragon\www\Angelow_microservices\frontend" "C:\temp\frontend_clean" /E /XD node_modules .git dist .vite
scp -r "C:\temp\frontend_clean" root@IP_SERVIDOR:/var/www/angelow_microservices/frontend
```

Verificar en el servidor:

```bash
for svc in auth-service catalog-service cart-service order-service payment-service discount-service shipping-service notification-service audit-service; do
  test -f /var/www/angelow_microservices/services/$svc/Dockerfile && echo "$svc OK" || echo "$svc FALTA Dockerfile"
done
test -f /var/www/angelow_microservices/frontend/Dockerfile && echo "frontend OK"
```

## 6. PostgreSQL init

Crear `/var/www/angelow_microservices/postgres/init/01_create_databases.sh`:

```bash
cat > /var/www/angelow_microservices/postgres/init/01_create_databases.sh <<'EOF'
#!/bin/bash
set -e
for db in angelow_auth angelow_catalog angelow_cart angelow_orders \
           angelow_payments angelow_discounts angelow_shipping \
           angelow_notifications angelow_audit; do
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE $db;
    GRANT ALL PRIVILEGES ON DATABASE $db TO $POSTGRES_USER;
EOSQL
  echo "Base de datos $db creada"
done
EOF
chmod +x /var/www/angelow_microservices/postgres/init/01_create_databases.sh
```

## 7. Frontend en producción

Crear `/var/www/angelow_microservices/frontend/Dockerfile.prod`:

```dockerfile
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .

ARG VITE_AUTH_API_URL=https://angelow.online/api
ARG VITE_CATALOG_API_URL=https://angelow.online/api/catalog
ARG VITE_CART_API_URL=https://angelow.online/api/cart
ARG VITE_ORDER_API_URL=https://angelow.online/api/orders
ARG VITE_PAYMENT_API_URL=https://angelow.online/api/payments
ARG VITE_DISCOUNT_API_URL=https://angelow.online/api/discounts
ARG VITE_SHIPPING_API_URL=https://angelow.online/api/shipping
ARG VITE_NOTIFICATION_API_URL=https://angelow.online/api/notifications
ARG VITE_UPLOADS_BASE_URL=https://angelow.online/uploads

ENV VITE_AUTH_API_URL=$VITE_AUTH_API_URL \
    VITE_CATALOG_API_URL=$VITE_CATALOG_API_URL \
    VITE_CART_API_URL=$VITE_CART_API_URL \
    VITE_ORDER_API_URL=$VITE_ORDER_API_URL \
    VITE_PAYMENT_API_URL=$VITE_PAYMENT_API_URL \
    VITE_DISCOUNT_API_URL=$VITE_DISCOUNT_API_URL \
    VITE_SHIPPING_API_URL=$VITE_SHIPPING_API_URL \
    VITE_NOTIFICATION_API_URL=$VITE_NOTIFICATION_API_URL \
    VITE_UPLOADS_BASE_URL=$VITE_UPLOADS_BASE_URL

RUN npm run build

FROM nginx:alpine
COPY nginx.prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
```

Crear `/var/www/angelow_microservices/frontend/nginx.prod.conf`:

```nginx
server {
    listen 80;
    server_name _;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /assets/ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

## 8. Nginx interno de Docker

Crear `/var/www/angelow_microservices/nginx/conf.d/angelow.conf`:

```nginx
server {
    listen 80;
    server_name angelow.online www.angelow.online;

    location /uploads/ {
        alias /var/www/uploads/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location /api/auth/ {
        proxy_pass http://auth-service:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/catalog/ {
        proxy_pass http://catalog-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/cart/ {
        proxy_pass http://cart-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/orders/ {
        proxy_pass http://order-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/payments/ {
        proxy_pass http://payment-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/discounts/ {
        proxy_pass http://discount-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/shipping/ {
        proxy_pass http://shipping-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/notifications/ {
        proxy_pass http://notification-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/audit/ {
        proxy_pass http://audit-service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location / {
        proxy_pass http://frontend:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## 9. `docker-compose.yml` de producción

Crear `/var/www/angelow_microservices/docker-compose.yml`.

> Ajustar los valores de Firebase/SMTP en `.env`, no quemarlos dentro del compose.

```yaml
x-logging: &default_logging
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"

x-laravel-base: &laravel_base
  APP_ENV: production
  APP_DEBUG: "false"
  APP_URL: https://angelow.online
  DB_CONNECTION: pgsql
  DB_HOST: postgres
  DB_PORT: 5432
  DB_USERNAME: angelow
  DB_PASSWORD: ${DB_PASSWORD}
  REDIS_HOST: redis
  REDIS_PORT: 6379
  REDIS_PASSWORD: null
  QUEUE_CONNECTION: redis
  CACHE_STORE: redis
  SESSION_DRIVER: file
  FILESYSTEM_DISK: public

services:
  postgres:
    image: postgres:15-alpine
    container_name: angelow_postgres
    restart: unless-stopped
    environment:
      POSTGRES_USER: angelow
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./postgres/init:/docker-entrypoint-initdb.d
    networks: [angelow_network]
    logging: *default_logging

  redis:
    image: redis:7-alpine
    container_name: angelow_redis
    restart: unless-stopped
    command: redis-server --maxmemory 256mb --maxmemory-policy allkeys-lru
    networks: [angelow_network]
    logging: *default_logging

  auth-service:
    build: { context: ./services/auth-service, dockerfile: Dockerfile }
    container_name: angelow_auth_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowAuth
      APP_KEY: ${AUTH_APP_KEY}
      DB_DATABASE: angelow_auth
      FRONTEND_URL: https://angelow.online
      INTERNAL_API_TOKEN: ${INTERNAL_API_TOKEN}
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
      FIREBASE_WEB_API_KEY: ${VITE_FIREBASE_API_KEY:-}
      PHPMAILER_HOST: ${MAIL_HOST:-smtp.gmail.com}
      PHPMAILER_PORT: ${MAIL_PORT:-587}
      PHPMAILER_USERNAME: ${MAIL_USERNAME:-}
      PHPMAILER_PASSWORD: ${MAIL_PASSWORD:-}
      PHPMAILER_ENCRYPTION: tls
      PHPMAILER_FROM_EMAIL: ${MAIL_FROM_ADDRESS:-seguridad@angelow.com}
      PHPMAILER_FROM_NAME: ${MAIL_FROM_NAME:-Seguridad Angelow}
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  catalog-service:
    build: { context: ./services/catalog-service, dockerfile: Dockerfile }
    container_name: angelow_catalog_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowCatalog
      APP_KEY: ${CATALOG_APP_KEY}
      DB_DATABASE: angelow_catalog
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
      NOTIFICATION_SERVICE_URL: http://notification-service:8000/api
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  cart-service:
    build: { context: ./services/cart-service, dockerfile: Dockerfile }
    container_name: angelow_cart_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowCart
      APP_KEY: ${CART_APP_KEY}
      DB_DATABASE: angelow_cart
      CATALOG_API_URL: http://catalog-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  order-service:
    build: { context: ./services/order-service, dockerfile: Dockerfile }
    container_name: angelow_order_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment: &order_env
      <<: *laravel_base
      APP_NAME: AngelowOrder
      APP_KEY: ${ORDER_APP_KEY}
      DB_DATABASE: angelow_orders
      CATALOG_SERVICE_URL: http://catalog-service:8000/api
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
      NOTIFICATION_SERVICE_URL: http://notification-service:8000/api
      NOTIFICATION_ORDER_TYPE_ID: 1
      FRONTEND_URL: https://angelow.online
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis, catalog-service]
    networks: [angelow_network]
    logging: *default_logging

  payment-service:
    build: { context: ./services/payment-service, dockerfile: Dockerfile }
    container_name: angelow_payment_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowPayment
      APP_KEY: ${PAYMENT_APP_KEY}
      DB_DATABASE: angelow_payments
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  discount-service:
    build: { context: ./services/discount-service, dockerfile: Dockerfile }
    container_name: angelow_discount_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowDiscount
      APP_KEY: ${DISCOUNT_APP_KEY}
      DB_DATABASE: angelow_discounts
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
      NOTIFICATION_SERVICE_URL: http://notification-service:8000/api
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  shipping-service:
    build: { context: ./services/shipping-service, dockerfile: Dockerfile }
    container_name: angelow_shipping_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowShipping
      APP_KEY: ${SHIPPING_APP_KEY}
      DB_DATABASE: angelow_shipping
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  notification-service:
    build: { context: ./services/notification-service, dockerfile: Dockerfile }
    container_name: angelow_notification_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment: &notification_env
      <<: *laravel_base
      APP_NAME: AngelowNotification
      APP_KEY: ${NOTIFICATION_APP_KEY}
      DB_DATABASE: angelow_notifications
      AUTH_SERVICE_URL: http://auth-service:8000/api
      AUTH_INTERNAL_TOKEN: ${INTERNAL_API_TOKEN}
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  audit-service:
    build: { context: ./services/audit-service, dockerfile: Dockerfile }
    container_name: angelow_audit_service
    restart: unless-stopped
    command: ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
    environment:
      <<: *laravel_base
      APP_NAME: AngelowAudit
      APP_KEY: ${AUDIT_APP_KEY}
      DB_DATABASE: angelow_audit
    volumes: ["./uploads:/var/www/html/public/uploads"]
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  order-worker:
    build: { context: ./services/order-service, dockerfile: Dockerfile }
    container_name: angelow_order_worker
    restart: unless-stopped
    command: ["php", "artisan", "queue:work", "--queue=orders", "--sleep=1", "--tries=3", "--timeout=120"]
    environment: *order_env
    depends_on: [postgres, redis, catalog-service]
    networks: [angelow_network]
    logging: *default_logging

  notification-worker:
    build: { context: ./services/notification-service, dockerfile: Dockerfile }
    container_name: angelow_notification_worker
    restart: unless-stopped
    command: ["php", "artisan", "queue:work", "--queue=default", "--sleep=1", "--tries=3", "--timeout=120"]
    environment: *notification_env
    depends_on: [postgres, redis]
    networks: [angelow_network]
    logging: *default_logging

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile.prod
      args:
        VITE_AUTH_API_URL: https://angelow.online/api
        VITE_CATALOG_API_URL: https://angelow.online/api/catalog
        VITE_CART_API_URL: https://angelow.online/api/cart
        VITE_ORDER_API_URL: https://angelow.online/api/orders
        VITE_PAYMENT_API_URL: https://angelow.online/api/payments
        VITE_DISCOUNT_API_URL: https://angelow.online/api/discounts
        VITE_SHIPPING_API_URL: https://angelow.online/api/shipping
        VITE_NOTIFICATION_API_URL: https://angelow.online/api/notifications
        VITE_UPLOADS_BASE_URL: https://angelow.online/uploads
    container_name: angelow_frontend
    restart: unless-stopped
    networks: [angelow_network]
    logging: *default_logging

  nginx:
    image: nginx:alpine
    container_name: angelow_nginx
    restart: unless-stopped
    ports: ["8080:80"]
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
      - ./uploads:/var/www/uploads
    depends_on:
      - frontend
      - auth-service
      - catalog-service
      - cart-service
      - order-service
      - payment-service
      - discount-service
      - shipping-service
      - notification-service
      - audit-service
    networks: [angelow_network]
    logging: *default_logging

networks:
  angelow_network:
    driver: bridge

volumes:
  postgres_data:
```

## 10. Nginx del host

Crear `/etc/nginx/sites-available/angelow`:

```nginx
server {
    listen 80;
    server_name angelow.online www.angelow.online;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60;
        proxy_connect_timeout 60;
    }
}
```

Activar:

```bash
ln -s /etc/nginx/sites-available/angelow /etc/nginx/sites-enabled/angelow
nginx -t
systemctl reload nginx
```

## 11. SSL con Certbot

Verificar DNS:

```bash
dig +short angelow.online
```

Emitir certificado:

```bash
certbot --nginx -d angelow.online -d www.angelow.online \
  --non-interactive --agree-tos -m admin@angelow.online
```

Verificar renovación:

```bash
certbot renew --dry-run
```

## 12. Construir y levantar contenedores

```bash
cd /var/www/angelow_microservices
docker compose config
docker compose build --no-cache
docker compose up -d
sleep 20
docker compose ps
```

## 13. Migraciones y caches

```bash
cd /var/www/angelow_microservices
for svc in auth-service catalog-service cart-service order-service payment-service discount-service shipping-service notification-service audit-service; do
  container="angelow_${svc//-/_}"
  echo "--- Migrando $container ---"
  docker exec "$container" php artisan migrate --force
  docker exec "$container" php artisan config:cache
  docker exec "$container" php artisan route:cache
  docker exec "$container" php artisan view:cache
done
```

## 14. Subir uploads

Desde Windows:

```powershell
scp -r "C:\laragon\www\Angelow_microservices\uploads" root@IP_SERVIDOR:/var/www/angelow_microservices/
```

En servidor:

```bash
chown -R www-data:www-data /var/www/angelow_microservices/uploads 2>/dev/null || chown -R root:root /var/www/angelow_microservices/uploads
chmod -R 755 /var/www/angelow_microservices/uploads
find /var/www/angelow_microservices/uploads -type f | wc -l
```

## 15. Migrar datos PostgreSQL

Exportar desde local, una base por servicio:

```powershell
docker start angelow_auth_db angelow_catalog_db angelow_cart_db angelow_order_db angelow_payment_db angelow_discount_db angelow_shipping_db angelow_notification_db angelow_audit_db

docker exec angelow_auth_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_auth > C:\temp\dump_auth.sql
docker exec angelow_catalog_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_catalog > C:\temp\dump_catalog.sql
docker exec angelow_cart_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_cart > C:\temp\dump_cart.sql
docker exec angelow_order_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_orders > C:\temp\dump_orders.sql
docker exec angelow_payment_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_payments > C:\temp\dump_payments.sql
docker exec angelow_discount_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_discounts > C:\temp\dump_discounts.sql
docker exec angelow_shipping_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_shipping > C:\temp\dump_shipping.sql
docker exec angelow_notification_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_notifications > C:\temp\dump_notifications.sql
docker exec angelow_audit_db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges angelow_audit > C:\temp\dump_audit.sql
```

Subir:

```powershell
scp C:\temp\dump_*.sql root@IP_SERVIDOR:/tmp/
```

Importar en servidor:

```bash
for db in auth catalog cart orders payments discounts shipping notifications audit; do
  echo "--- Importando angelow_$db ---"
  docker exec -i angelow_postgres psql -U angelow -d angelow_$db < /tmp/dump_$db.sql
done
rm -f /tmp/dump_*.sql
```

Si una importación falla por tablas existentes:

```bash
docker exec angelow_postgres psql -U angelow -c "DROP DATABASE angelow_NOMBRE;"
docker exec angelow_postgres psql -U angelow -c "CREATE DATABASE angelow_NOMBRE;"
docker exec angelow_postgres psql -U angelow -c "GRANT ALL PRIVILEGES ON DATABASE angelow_NOMBRE TO angelow;"
docker exec -i angelow_postgres psql -U angelow -d angelow_NOMBRE < /tmp/dump_NOMBRE.sql
```

## 16. Verificación final

```bash
cd /var/www/angelow_microservices
docker compose ps
curl -I http://127.0.0.1:8080
curl -I https://angelow.online
```

Ver tablas:

```bash
for db in auth catalog cart orders payments discounts shipping notifications audit; do
  count=$(docker exec angelow_postgres psql -U angelow -d angelow_$db -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public';" | tr -d ' ')
  echo "angelow_$db: $count tablas"
done
```

Logs:

```bash
docker compose logs -f
docker compose logs --tail=100 auth-service
docker compose logs --tail=100 nginx
docker compose logs --tail=100 frontend
```

## 17. Problemas comunes

### Error 419 CSRF en login

El frontend usa API token/Bearer, no sesión cookie. En `auth-service/bootstrap/app.php`, evitar activar Sanctum stateful API para login público si produce CSRF:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
})
```

Luego:

```bash
docker compose build auth-service
docker compose up -d auth-service
docker exec angelow_auth_service php artisan optimize:clear
docker exec angelow_auth_service php artisan config:cache
docker exec angelow_auth_service php artisan route:cache
```

### Login devuelve 401

Es correcto si las credenciales no existen o el dump de `angelow_auth` no fue importado.

### Correos no salen

Revisar `.env` raíz:

```bash
grep -E 'MAIL_|PHPMAILER_' /var/www/angelow_microservices/.env
```

Debe existir:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=correo@gmail.com
MAIL_PASSWORD=app_password_de_gmail
MAIL_FROM_ADDRESS=seguridad@angelow.com
MAIL_FROM_NAME="Seguridad Angelow"
```

Después:

```bash
docker compose up -d auth-service
docker exec angelow_auth_service php artisan optimize:clear
docker exec angelow_auth_service php artisan config:cache
```

### Frontend no toma variables nuevas

Las variables `VITE_*` se hornean durante build:

```bash
docker compose build --no-cache frontend
docker compose up -d frontend nginx
```

## 18. Backups

Backup de PostgreSQL:

```bash
mkdir -p /root/backups/angelow
docker exec angelow_postgres pg_dumpall -U angelow > /root/backups/angelow/pg_dumpall_$(date +%F_%H%M).sql
tar -czf /root/backups/angelow/uploads_$(date +%F_%H%M).tar.gz -C /var/www/angelow_microservices uploads
```

Restaurar:

```bash
docker exec -i angelow_postgres psql -U angelow < /root/backups/angelow/pg_dumpall_ARCHIVO.sql
tar -xzf /root/backups/angelow/uploads_ARCHIVO.tar.gz -C /var/www/angelow_microservices
```

