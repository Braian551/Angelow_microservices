-- ============================================================
-- Angelow - Base de datos unificada importable para MySQL Workbench
-- Archivo convertido automáticamente desde la versión PostgreSQL entregada.
-- Recomendado: importar en una conexión MySQL 8.x / 9.x con InnoDB.
-- ADVERTENCIA: este script elimina y crea de nuevo la base `angelow_microservices`.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS angelow_microservices;
CREATE DATABASE angelow_microservices
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE angelow_microservices;

-- Angelow - Estructura unificada de base de datos para microservicios
-- Motor objetivo: MySQL 8/9 - MySQL Workbench
-- Fecha de análisis: 2026-06-30
--
-- Este archivo representa cómo quedaría Angelow si todas las bases de datos
-- de los microservicios vivieran en una sola base relacional.
--
-- Criterios aplicados:
-- 1. Se consolidan las tablas técnicas repetidas de Laravel una sola vez
--    (`migrations`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`).
-- 2. Se conserva una tabla `users` central como origen de identidad.
-- 3. Las columnas `user_id` se normalizan a VARCHAR(50) para soportar los
--    distintos largos usados por los servicios durante la migración.
-- 4. La tabla `announcements` se unifica como superset de catálogo y
--    notificaciones, porque ambos dominios declaran una tabla con ese nombre.
-- 5. `user_addresses` conserva campos heredados y campos normalizados vistos
--    en migraciones del servicio de envíos.
-- 6. El archivo define estructura y relaciones; no incluye datos semilla.
-- 7. Se incorporan vistas, funciones e índices de rendimiento inspirados en
--    el dump completo `basededatoscompletaAntigua.sql`, adaptados a MySQL
--    y a los límites actuales de cada microservicio.
--
-- Importante: es un diseño de referencia. No ejecutarlo sobre producción sin
-- revisión previa. Este script elimina y crea la base `angelow_microservices`; no migra datos.

-- ============================================================
-- Tablas técnicas compartidas de Laravel
-- ============================================================

CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_cache_expiration ON cache (expiration);

CREATE TABLE cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_cache_locks_expiration ON cache_locks (expiration);

CREATE TABLE jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INT NULL,
    available_at INT NOT NULL,
    created_at INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_jobs_queue ON jobs (queue);

CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- auth-service
-- ============================================================

CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    is_blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_access DATETIME NULL,
    remember_token VARCHAR(255) NULL,
    token_expiry DATETIME NULL,
    trial548 CHAR(1) NULL,
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_phone ON users (phone);
CREATE INDEX idx_users_role ON users (role);

CREATE TABLE access_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_access_tokens_user_id ON access_tokens (user_id);

CREATE TABLE google_auth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    google_id VARCHAR(255) NOT NULL UNIQUE,
    access_token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_google_auth_user_id ON google_auth (user_id);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_password_resets_user_id ON password_resets (user_id);
CREATE INDEX idx_password_resets_token ON password_resets (token);

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_sessions_user_id ON sessions (user_id);
CREATE INDEX idx_sessions_last_activity ON sessions (last_activity);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_login_attempts_username_date ON login_attempts (username, attempt_date);
CREATE INDEX idx_login_attempts_ip_date ON login_attempts (ip_address, attempt_date);

CREATE TABLE auth_login_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    credential VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    failed_attempts SMALLINT NOT NULL DEFAULT 0,
    last_failed_at DATETIME NULL,
    blocked_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_auth_login_attempts_credential_ip UNIQUE (credential, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_auth_login_attempts_blocked_until ON auth_login_attempts (blocked_until);

CREATE TABLE personal_access_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id VARCHAR(50) NOT NULL,
    name TEXT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE INDEX idx_personal_access_tokens_expires_at ON personal_access_tokens (expires_at);

-- ============================================================
-- shipping-service
-- ============================================================

CREATE TABLE shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    base_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
    delivery_time VARCHAR(50) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    free_shipping_threshold DECIMAL(10, 2) NULL,
    available_cities TEXT NULL,
    estimated_days_min INT NOT NULL DEFAULT 1,
    estimated_days_max INT NOT NULL DEFAULT 3,
    city VARCHAR(100) NOT NULL DEFAULT 'Medellin',
    free_shipping_minimum DECIMAL(10, 2) NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'fas fa-truck',
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_price_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_price DECIMAL(10, 2) NOT NULL,
    max_price DECIMAL(10, 2) NULL,
    shipping_cost DECIMAL(10, 2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    address_type VARCHAR(20) NOT NULL DEFAULT 'casa',
    alias VARCHAR(50) NULL,
    recipient_name VARCHAR(100) NOT NULL,
    recipient_phone VARCHAR(20) NULL,
    phone VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    complement VARCHAR(100) NULL,
    neighborhood VARCHAR(100) NULL,
    building_type VARCHAR(20) NULL DEFAULT 'casa',
    building_name VARCHAR(100) NULL,
    apartment_number VARCHAR(20) NULL,
    delivery_instructions TEXT NULL,
    address_line_1 VARCHAR(180) NULL,
    address_line_2 VARCHAR(180) NULL,
    city VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Colombia',
    notes TEXT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    gps_latitude DECIMAL(10, 8) NULL,
    gps_longitude DECIMAL(11, 8) NULL,
    gps_accuracy DECIMAL(10, 2) NULL,
    gps_timestamp DATETIME NULL,
    gps_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_addresses_user_id ON user_addresses (user_id);
CREATE INDEX idx_user_addresses_is_default ON user_addresses (is_default);
CREATE INDEX idx_user_addresses_is_active ON user_addresses (is_active);

-- ============================================================
-- catalog-service
-- ============================================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    parent_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    launch_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    hex_code VARCHAR(7) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    brand VARCHAR(100) NULL,
    gender VARCHAR(20) NOT NULL DEFAULT 'unisex',
    collection VARCHAR(50) NULL,
    material VARCHAR(100) NULL,
    care_instructions TEXT NULL,
    compare_price DECIMAL(10, 2) NULL,
    price DECIMAL(10, 2) NULL,
    category_id INT NOT NULL,
    collection_id INT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_refundable TINYINT(1) NOT NULL DEFAULT 0,
    refund_days SMALLINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_products_category_id ON products (category_id);
CREATE INDEX idx_products_collection_id ON products (collection_id);
CREATE INDEX idx_products_is_active ON products (is_active);

CREATE TABLE product_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    collection_id INT NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT uq_product_collections_product_collection UNIQUE (product_id, collection_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_collections_product_id ON product_collections (product_id);
CREATE INDEX idx_product_collections_collection_id ON product_collections (collection_id);

CREATE TABLE product_color_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_id INT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_color_variants_product_id ON product_color_variants (product_id);

CREATE TABLE product_size_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_variant_id INT NOT NULL,
    size_id INT NULL,
    sku VARCHAR(50) NULL,
    barcode VARCHAR(50) NULL,
    price DECIMAL(10, 2) NOT NULL,
    compare_price DECIMAL(10, 2) NULL,
    quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_size_variants_color_variant_id ON product_size_variants (color_variant_id);
CREATE INDEX idx_product_size_variants_size_id ON product_size_variants (size_id);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_variant_id INT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    `order` INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_images_product_id ON product_images (product_id);

CREATE TABLE variant_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    color_variant_id INT NOT NULL,
    product_id INT NOT NULL,
    image_id INT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    `order` INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_variant_images_color_variant_id ON variant_images (color_variant_id);
CREATE INDEX idx_variant_images_product_id ON variant_images (product_id);

CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL,
    CONSTRAINT uq_wishlist_user_product UNIQUE (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    order_id INT NULL,
    rating SMALLINT NOT NULL,
    title VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    images TEXT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT chk_product_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_reviews_product_id ON product_reviews (product_id);
CREATE INDEX idx_product_reviews_user_id ON product_reviews (user_id);

CREATE TABLE review_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    is_helpful TINYINT(1) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT uq_review_votes_review_user UNIQUE (review_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    question TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_product_questions_product_id ON product_questions (product_id);

CREATE TABLE question_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    answer TEXT NOT NULL,
    is_seller TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_question_answers_question_id ON question_answers (question_id);

CREATE TABLE popular_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_term VARCHAR(255) NOT NULL UNIQUE,
    search_count INT NOT NULL DEFAULT 1,
    last_searched DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    search_term VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_search_history_user_id ON search_history (user_id);

CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    category VARCHAR(40) NOT NULL DEFAULT 'general',
    updated_by VARCHAR(50) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    image VARCHAR(500) NOT NULL,
    image_url VARCHAR(500) NULL,
    link VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    order_position INT NOT NULL DEFAULT 0,
    sort_order INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(30) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    subtitle VARCHAR(255) NULL,
    button_text VARCHAR(100) NULL,
    button_link VARCHAR(500) NULL,
    image VARCHAR(500) NULL,
    background_color VARCHAR(20) NULL DEFAULT '#000000',
    text_color VARCHAR(20) NULL DEFAULT '#ffffff',
    icon VARCHAR(50) NULL,
    priority INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    previous_qty INT NOT NULL,
    new_qty INT NOT NULL,
    operation VARCHAR(12) NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_stock_history_variant_id ON stock_history (variant_id);

CREATE TABLE inventory_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL UNIQUE,
    product_id INT NULL,
    product_name VARCHAR(255) NULL,
    color_name VARCHAR(120) NULL,
    size_label VARCHAR(120) NULL,
    sku VARCHAR(80) NULL,
    stock INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'out',
    out_of_stock_since DATETIME NULL,
    last_initial_notification_at DATETIME NULL,
    last_reminder_at DATETIME NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_inventory_alerts_status_out_since ON inventory_alerts (status, out_of_stock_since);
CREATE INDEX idx_inventory_alerts_product_id ON inventory_alerts (product_id);

-- ============================================================
-- cart-service
-- ============================================================

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    session_id VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    color_variant_id INT NULL,
    size_variant_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL,
    CONSTRAINT chk_cart_items_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_cart_items_cart_id ON cart_items (cart_id);
CREATE INDEX idx_cart_items_product_id ON cart_items (product_id);

-- ============================================================
-- order-service
-- ============================================================

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    invoice_number VARCHAR(20) NULL,
    user_id VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    shipping_address TEXT NULL,
    shipping_city VARCHAR(100) NULL,
    shipping_method_id INT NULL,
    shipping_address_id INT NULL,
    billing_address TEXT NULL,
    billing_address_id INT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    invoice_resolution VARCHAR(50) NULL,
    invoice_date DATETIME NULL,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_orders_user_id ON orders (user_id);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_payment_status ON orders (payment_status);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    color_variant_id INT NULL,
    size_variant_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_name VARCHAR(255) NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_items_order_id ON order_items (order_id);

CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    changed_by VARCHAR(50) NULL,
    changed_by_name VARCHAR(100) NULL,
    change_type VARCHAR(20) NOT NULL DEFAULT 'other',
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_status_history_order_id ON order_status_history (order_id);

CREATE TABLE order_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_order_views_order_user UNIQUE (order_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    size_variant_id INT NULL,
    reservation_key VARCHAR(120) NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'reserved',
    expires_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    released_at DATETIME NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_stock_reservations_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_stock_reservations_order_id ON stock_reservations (order_id);
CREATE INDEX idx_stock_reservations_status ON stock_reservations (status);
CREATE INDEX idx_stock_reservations_expires_at ON stock_reservations (expires_at);
CREATE INDEX idx_stock_reservations_key ON stock_reservations (reservation_key);
CREATE INDEX idx_stock_reservations_order_status ON stock_reservations (order_id, status);

CREATE TABLE order_refund_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id VARCHAR(50) NULL,
    user_email VARCHAR(255) NULL,
    reason VARCHAR(80) NOT NULL,
    details TEXT NULL,
    evidence_path VARCHAR(500) NULL,
    evidence_original_name VARCHAR(255) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'requested',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_refund_requests_order_id ON order_refund_requests (order_id);
CREATE INDEX idx_order_refund_requests_status ON order_refund_requests (status);

-- ============================================================
-- payment-service
-- ============================================================

CREATE TABLE colombian_banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_code VARCHAR(10) NOT NULL UNIQUE,
    bank_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_account_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_code VARCHAR(10) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_type VARCHAR(20) NOT NULL,
    account_holder VARCHAR(100) NOT NULL,
    identification_type VARCHAR(10) NOT NULL DEFAULT 'cc',
    identification_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    user_id VARCHAR(50) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reference_number VARCHAR(50) NULL,
    payment_proof VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    verified_by VARCHAR(50) NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_payment_transactions_order_id ON payment_transactions (order_id);
CREATE INDEX idx_payment_transactions_user_id ON payment_transactions (user_id);
CREATE INDEX idx_payment_transactions_status ON payment_transactions (status);

-- ============================================================
-- discount-service
-- ============================================================

CREATE TABLE discount_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discount_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_type_id INT NOT NULL,
    discount_value DECIMAL(10, 2) NULL,
    max_uses INT NULL,
    used_count INT NOT NULL DEFAULT 0,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_single_use TINYINT(1) NOT NULL DEFAULT 0,
    created_by VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discount_code_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_discount_code_products_code_product UNIQUE (discount_code_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discount_code_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT NOT NULL,
    user_id VARCHAR(50) NULL,
    order_id INT NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE percentage_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    max_discount_amount DECIMAL(10, 2) NULL,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fixed_amount_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) NULL,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE free_shipping_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT NOT NULL,
    shipping_method_id INT NULL,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bulk_discount_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_quantity INT NOT NULL,
    max_quantity INT NULL,
    discount_percentage DECIMAL(5, 2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_applied_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    discount_code_id INT NOT NULL,
    discount_code VARCHAR(20) NOT NULL,
    discount_amount DECIMAL(10, 2) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    used_at DATETIME NULL,
    trial558 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_applied_discounts_user_id ON user_applied_discounts (user_id);
CREATE INDEX idx_user_applied_discounts_discount_code_id ON user_applied_discounts (discount_code_id);

-- ============================================================
-- notification-service
-- ============================================================

CREATE TABLE notification_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    template TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    type_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(30) NULL,
    related_entity_id INT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_email_sent TINYINT(1) NOT NULL DEFAULT 0,
    is_sms_sent TINYINT(1) NOT NULL DEFAULT 0,
    is_push_sent TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_notifications_user_id ON notifications (user_id);
CREATE INDEX idx_notifications_type_id ON notifications (type_id);

CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    type_id INT NOT NULL,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
    push_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_notification_preferences_user_type UNIQUE (user_id, type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    channel VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts SMALLINT NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NULL,
    scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    error_message TEXT NULL,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_notification_queue_notification_id ON notification_queue (notification_id);
CREATE INDEX idx_notification_queue_status ON notification_queue (status);

CREATE TABLE admin_notification_dismissals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(50) NOT NULL,
    notification_key VARCHAR(120) NOT NULL,
    dismissed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL,
    CONSTRAINT uq_admin_notification_dismissals_admin_key UNIQUE (admin_id, notification_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- audit-service
-- ============================================================

CREATE TABLE audit_categories (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    action_type VARCHAR(10) NULL,
    old_name VARCHAR(100) NULL,
    new_name VARCHAR(100) NULL,
    action_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    accion VARCHAR(10) NOT NULL,
    usuario_id VARCHAR(50) NULL,
    sql_usuario VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT NULL,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_orders_orden_id ON audit_orders (orden_id);

CREATE TABLE audit_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(50) NOT NULL,
    accion VARCHAR(10) NOT NULL,
    usuario_modificador VARCHAR(50) NULL,
    sql_usuario VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT NULL,
    trial548 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_users_usuario_id ON audit_users (usuario_id);

CREATE TABLE productos_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL DEFAULT 'Creado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eliminaciones_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL DEFAULT 'Eliminado',
    fecha_eliminacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Relaciones entre tablas
-- ============================================================

-- auth-service
ALTER TABLE access_tokens
    ADD CONSTRAINT fk_access_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE google_auth
    ADD CONSTRAINT fk_google_auth_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE password_resets
    ADD CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE sessions
    ADD CONSTRAINT fk_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- shipping-service
ALTER TABLE user_addresses
    ADD CONSTRAINT fk_user_addresses_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- catalog-service
ALTER TABLE categories
    ADD CONSTRAINT fk_categories_parent
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL;

ALTER TABLE products
    ADD CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT;

ALTER TABLE products
    ADD CONSTRAINT fk_products_collection
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL;

ALTER TABLE product_collections
    ADD CONSTRAINT fk_product_collections_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_collections
    ADD CONSTRAINT fk_product_collections_collection
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE;

ALTER TABLE product_color_variants
    ADD CONSTRAINT fk_product_color_variants_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_color_variants
    ADD CONSTRAINT fk_product_color_variants_color
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL;

ALTER TABLE product_size_variants
    ADD CONSTRAINT fk_product_size_variants_color_variant
    FOREIGN KEY (color_variant_id) REFERENCES product_color_variants(id) ON DELETE CASCADE;

ALTER TABLE product_size_variants
    ADD CONSTRAINT fk_product_size_variants_size
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE SET NULL;

ALTER TABLE product_images
    ADD CONSTRAINT fk_product_images_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_images
    ADD CONSTRAINT fk_product_images_color_variant
    FOREIGN KEY (color_variant_id) REFERENCES product_color_variants(id) ON DELETE SET NULL;

ALTER TABLE variant_images
    ADD CONSTRAINT fk_variant_images_color_variant
    FOREIGN KEY (color_variant_id) REFERENCES product_color_variants(id) ON DELETE CASCADE;

ALTER TABLE variant_images
    ADD CONSTRAINT fk_variant_images_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE variant_images
    ADD CONSTRAINT fk_variant_images_image
    FOREIGN KEY (image_id) REFERENCES product_images(id) ON DELETE SET NULL;

ALTER TABLE wishlist
    ADD CONSTRAINT fk_wishlist_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE wishlist
    ADD CONSTRAINT fk_wishlist_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_reviews
    ADD CONSTRAINT fk_product_reviews_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_reviews
    ADD CONSTRAINT fk_product_reviews_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE review_votes
    ADD CONSTRAINT fk_review_votes_review
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE;

ALTER TABLE review_votes
    ADD CONSTRAINT fk_review_votes_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE product_questions
    ADD CONSTRAINT fk_product_questions_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_questions
    ADD CONSTRAINT fk_product_questions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE question_answers
    ADD CONSTRAINT fk_question_answers_question
    FOREIGN KEY (question_id) REFERENCES product_questions(id) ON DELETE CASCADE;

ALTER TABLE question_answers
    ADD CONSTRAINT fk_question_answers_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE search_history
    ADD CONSTRAINT fk_search_history_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE site_settings
    ADD CONSTRAINT fk_site_settings_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE stock_history
    ADD CONSTRAINT fk_stock_history_variant
    FOREIGN KEY (variant_id) REFERENCES product_size_variants(id) ON DELETE CASCADE;

ALTER TABLE stock_history
    ADD CONSTRAINT fk_stock_history_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE inventory_alerts
    ADD CONSTRAINT fk_inventory_alerts_variant
    FOREIGN KEY (variant_id) REFERENCES product_size_variants(id) ON DELETE CASCADE;

ALTER TABLE inventory_alerts
    ADD CONSTRAINT fk_inventory_alerts_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- cart-service
ALTER TABLE carts
    ADD CONSTRAINT fk_carts_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE cart_items
    ADD CONSTRAINT fk_cart_items_cart
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE;

ALTER TABLE cart_items
    ADD CONSTRAINT fk_cart_items_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;

ALTER TABLE cart_items
    ADD CONSTRAINT fk_cart_items_color_variant
    FOREIGN KEY (color_variant_id) REFERENCES product_color_variants(id) ON DELETE SET NULL;

ALTER TABLE cart_items
    ADD CONSTRAINT fk_cart_items_size_variant
    FOREIGN KEY (size_variant_id) REFERENCES product_size_variants(id) ON DELETE SET NULL;

-- order-service
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_shipping_method
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_shipping_address
    FOREIGN KEY (shipping_address_id) REFERENCES user_addresses(id) ON DELETE SET NULL;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_billing_address
    FOREIGN KEY (billing_address_id) REFERENCES user_addresses(id) ON DELETE SET NULL;

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_color_variant
    FOREIGN KEY (color_variant_id) REFERENCES product_color_variants(id) ON DELETE SET NULL;

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_size_variant
    FOREIGN KEY (size_variant_id) REFERENCES product_size_variants(id) ON DELETE SET NULL;

ALTER TABLE order_status_history
    ADD CONSTRAINT fk_order_status_history_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_status_history
    ADD CONSTRAINT fk_order_status_history_changed_by
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE order_views
    ADD CONSTRAINT fk_order_views_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_views
    ADD CONSTRAINT fk_order_views_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE stock_reservations
    ADD CONSTRAINT fk_stock_reservations_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE stock_reservations
    ADD CONSTRAINT fk_stock_reservations_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;

ALTER TABLE stock_reservations
    ADD CONSTRAINT fk_stock_reservations_size_variant
    FOREIGN KEY (size_variant_id) REFERENCES product_size_variants(id) ON DELETE SET NULL;

ALTER TABLE order_refund_requests
    ADD CONSTRAINT fk_order_refund_requests_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_refund_requests
    ADD CONSTRAINT fk_order_refund_requests_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE product_reviews
    ADD CONSTRAINT fk_product_reviews_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- payment-service
ALTER TABLE bank_account_config
    ADD CONSTRAINT fk_bank_account_config_bank
    FOREIGN KEY (bank_code) REFERENCES colombian_banks(bank_code) ON DELETE RESTRICT;

ALTER TABLE bank_account_config
    ADD CONSTRAINT fk_bank_account_config_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE payment_transactions
    ADD CONSTRAINT fk_payment_transactions_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

ALTER TABLE payment_transactions
    ADD CONSTRAINT fk_payment_transactions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE payment_transactions
    ADD CONSTRAINT fk_payment_transactions_verified_by
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;

-- discount-service
ALTER TABLE discount_codes
    ADD CONSTRAINT fk_discount_codes_type
    FOREIGN KEY (discount_type_id) REFERENCES discount_types(id) ON DELETE RESTRICT;

ALTER TABLE discount_codes
    ADD CONSTRAINT fk_discount_codes_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE discount_code_products
    ADD CONSTRAINT fk_discount_code_products_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

ALTER TABLE discount_code_products
    ADD CONSTRAINT fk_discount_code_products_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE discount_code_usage
    ADD CONSTRAINT fk_discount_code_usage_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

ALTER TABLE discount_code_usage
    ADD CONSTRAINT fk_discount_code_usage_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE discount_code_usage
    ADD CONSTRAINT fk_discount_code_usage_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

ALTER TABLE percentage_discounts
    ADD CONSTRAINT fk_percentage_discounts_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

ALTER TABLE fixed_amount_discounts
    ADD CONSTRAINT fk_fixed_amount_discounts_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

ALTER TABLE free_shipping_discounts
    ADD CONSTRAINT fk_free_shipping_discounts_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

ALTER TABLE free_shipping_discounts
    ADD CONSTRAINT fk_free_shipping_discounts_shipping_method
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL;

ALTER TABLE user_applied_discounts
    ADD CONSTRAINT fk_user_applied_discounts_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE user_applied_discounts
    ADD CONSTRAINT fk_user_applied_discounts_code
    FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE;

-- notification-service
ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_type
    FOREIGN KEY (type_id) REFERENCES notification_types(id) ON DELETE RESTRICT;

ALTER TABLE notification_preferences
    ADD CONSTRAINT fk_notification_preferences_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE notification_preferences
    ADD CONSTRAINT fk_notification_preferences_type
    FOREIGN KEY (type_id) REFERENCES notification_types(id) ON DELETE CASCADE;

ALTER TABLE notification_queue
    ADD CONSTRAINT fk_notification_queue_notification
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE;

ALTER TABLE admin_notification_dismissals
    ADD CONSTRAINT fk_admin_notification_dismissals_admin
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE;

-- audit-service
ALTER TABLE audit_categories
    ADD CONSTRAINT fk_audit_categories_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

ALTER TABLE audit_orders
    ADD CONSTRAINT fk_audit_orders_order
    FOREIGN KEY (orden_id) REFERENCES orders(id) ON DELETE RESTRICT;

ALTER TABLE audit_orders
    ADD CONSTRAINT fk_audit_orders_user
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE audit_users
    ADD CONSTRAINT fk_audit_users_user
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE audit_users
    ADD CONSTRAINT fk_audit_users_modifier
    FOREIGN KEY (usuario_modificador) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- Índices, vistas y procedimientos compatibles con MySQL
-- ============================================================

CREATE INDEX idx_products_active_filters
    ON products (is_active, category_id, gender, collection_id, is_featured, created_at);

CREATE INDEX idx_products_active_price
    ON products (is_active, price);

CREATE INDEX idx_product_images_primary_order
    ON product_images (product_id, is_primary DESC, `order`, id);

CREATE INDEX idx_product_reviews_product_approved_rating
    ON product_reviews (product_id, is_approved, rating);

CREATE INDEX idx_product_questions_product_created
    ON product_questions (product_id, created_at DESC);

CREATE INDEX idx_search_history_user_term_created
    ON search_history (user_id, search_term, created_at DESC);

CREATE INDEX idx_popular_searches_term_count
    ON popular_searches (search_term, search_count DESC);

CREATE OR REPLACE VIEW catalog_product_listing_view AS
SELECT
    p.id,
    p.name,
    p.slug,
    p.description,
    p.brand,
    p.gender,
    p.collection,
    p.material,
    p.care_instructions,
    p.compare_price,
    p.price,
    p.category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    p.collection_id,
    col.name AS collection_name,
    col.slug AS collection_slug,
    p.is_featured,
    p.is_active,
    p.created_at,
    p.updated_at,
    p.trial554,
    COALESCE(
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_primary DESC, pi.`order` ASC, pi.id ASC
            LIMIT 1
        ),
        'uploads/products/default-product.jpg'
    ) AS primary_image,
    CAST(
        COALESCE(
            (
                SELECT MIN(psv.price)
                FROM product_color_variants pcv
                JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                WHERE pcv.product_id = p.id
            ),
            p.price,
            0
        ) AS DECIMAL(10, 2)
    ) AS min_price,
    CAST(
        COALESCE(
            (
                SELECT MAX(psv.price)
                FROM product_color_variants pcv
                JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                WHERE pcv.product_id = p.id
            ),
            p.price,
            0
        ) AS DECIMAL(10, 2)
    ) AS max_price,
    CAST(
        COALESCE(
            (
                SELECT SUM(CASE WHEN psv.is_active = 1 THEN psv.quantity ELSE 0 END)
                FROM product_color_variants pcv
                JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                WHERE pcv.product_id = p.id
            ),
            0
        ) AS SIGNED
    ) AS available_stock,
    CAST(
        COALESCE(
            (
                SELECT AVG(pr.rating)
                FROM product_reviews pr
                WHERE pr.product_id = p.id
                  AND COALESCE(pr.is_approved, 1) = 1
            ),
            0
        ) AS DECIMAL(4, 2)
    ) AS avg_rating,
    CAST(
        COALESCE(
            (
                SELECT COUNT(*)
                FROM product_reviews pr
                WHERE pr.product_id = p.id
                  AND COALESCE(pr.is_approved, 1) = 1
            ),
            0
        ) AS SIGNED
    ) AS review_count
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN collections col ON col.id = p.collection_id;

CREATE OR REPLACE VIEW catalog_active_categories_view AS
SELECT
    c.id,
    c.name,
    c.slug,
    c.description,
    c.image,
    c.parent_id,
    parent.name AS parent_name,
    c.is_active,
    c.created_at,
    c.updated_at
FROM categories c
LEFT JOIN categories parent ON parent.id = c.parent_id
WHERE c.is_active = 1;

CREATE INDEX idx_discount_codes_active_dates
    ON discount_codes (is_active, start_date, end_date, used_count);

CREATE INDEX idx_discount_code_usage_code_user
    ON discount_code_usage (discount_code_id, user_id);

CREATE INDEX idx_bulk_discount_rules_active_range
    ON bulk_discount_rules (is_active, min_quantity, max_quantity, discount_percentage);

CREATE INDEX idx_orders_user_created
    ON orders (user_id, created_at DESC);

CREATE INDEX idx_orders_admin_reports
    ON orders (created_at DESC, status, payment_status);

CREATE INDEX idx_orders_payment_status_created
    ON orders (payment_status, created_at DESC);

CREATE INDEX idx_order_items_product_created
    ON order_items (product_id, created_at DESC);

CREATE INDEX idx_order_status_history_order_created
    ON order_status_history (order_id, created_at DESC, id DESC);

CREATE INDEX idx_order_views_user_viewed
    ON order_views (user_id, viewed_at DESC);

CREATE INDEX idx_order_refund_requests_status_requested
    ON order_refund_requests (status, requested_at DESC);

CREATE OR REPLACE VIEW order_history_view AS
SELECT
    osh.id,
    osh.order_id,
    o.order_number,
    o.user_id AS order_user_id,
    o.status AS order_status,
    o.payment_status AS order_payment_status,
    osh.changed_by,
    osh.changed_by_name,
    osh.change_type,
    osh.field_changed,
    osh.old_value,
    osh.new_value,
    osh.description,
    osh.ip_address,
    osh.user_agent,
    osh.created_at
FROM order_status_history osh
JOIN orders o ON o.id = osh.order_id;

CREATE OR REPLACE VIEW order_sales_daily_view AS
SELECT
    DATE(created_at) AS sales_date,
    status,
    payment_status,
    CAST(COUNT(*) AS SIGNED) AS orders_count,
    CAST(COALESCE(SUM(subtotal), 0) AS DECIMAL(12, 2)) AS subtotal,
    CAST(COALESCE(SUM(shipping_cost), 0) AS DECIMAL(12, 2)) AS shipping_total,
    CAST(COALESCE(SUM(discount_amount), 0) AS DECIMAL(12, 2)) AS discount_total,
    CAST(COALESCE(SUM(total), 0) AS DECIMAL(12, 2)) AS sales_total
FROM orders
GROUP BY DATE(created_at), status, payment_status;

CREATE INDEX idx_notifications_user_read_created
    ON notifications (user_id, is_read, created_at DESC);

CREATE INDEX idx_notifications_expires_at
    ON notifications (expires_at);

CREATE INDEX idx_notification_queue_status_scheduled
    ON notification_queue (status, scheduled_at, attempts);

CREATE INDEX idx_admin_notification_dismissals_admin_key
    ON admin_notification_dismissals (admin_id, notification_key);

CREATE INDEX idx_announcements_active_window
    ON announcements (is_active, priority DESC, start_date, end_date);

CREATE OR REPLACE VIEW notification_inbox_view AS
SELECT
    n.id,
    n.user_id,
    n.type_id,
    nt.name AS type_name,
    nt.description AS type_description,
    n.title,
    n.message,
    n.related_entity_type,
    n.related_entity_id,
    n.is_read,
    n.is_email_sent,
    n.is_sms_sent,
    n.is_push_sent,
    n.expires_at,
    n.created_at,
    n.read_at
FROM notifications n
LEFT JOIN notification_types nt ON nt.id = n.type_id
WHERE n.expires_at IS NULL
   OR n.expires_at > CURRENT_TIMESTAMP;

DELIMITER $$

DROP PROCEDURE IF EXISTS catalog_search_products_and_terms $$
CREATE PROCEDURE catalog_search_products_and_terms(
    IN p_search_term TEXT,
    IN p_limit INT
)
BEGIN
    SET p_limit = IFNULL(p_limit, 5);
    SET p_limit = GREATEST(1, LEAST(p_limit, 20));

    SELECT *
    FROM (
        SELECT
            'product' AS result_type,
            p.id AS product_id,
            p.name,
            p.slug,
            p.primary_image AS image_path,
            CAST(NULL AS CHAR(255)) AS search_term_result,
            CASE
                WHEN p.name LIKE CONCAT(p_search_term, '%') THEN 1
                WHEN p.name LIKE CONCAT('%', p_search_term, '%') THEN 2
                ELSE 3
            END AS sort_rank
        FROM catalog_product_listing_view p
        WHERE p.is_active = 1
          AND (
              p.name LIKE CONCAT('%', p_search_term, '%')
              OR p.description LIKE CONCAT('%', p_search_term, '%')
              OR p.brand LIKE CONCAT('%', p_search_term, '%')
          )
        ORDER BY sort_rank, p.name
        LIMIT p_limit
    ) AS product_results
    UNION ALL
    SELECT *
    FROM (
        SELECT
            'term' AS result_type,
            CAST(NULL AS SIGNED) AS product_id,
            CAST(NULL AS CHAR(255)) AS name,
            CAST(NULL AS CHAR(255)) AS slug,
            CAST(NULL AS CHAR(255)) AS image_path,
            terms.name AS search_term_result,
            10 AS sort_rank
        FROM (
            SELECT DISTINCT p.name
            FROM catalog_product_listing_view p
            WHERE p.is_active = 1
              AND p.name LIKE CONCAT('%', p_search_term, '%')
              AND p.name IS NOT NULL
              AND p.name <> ''
            ORDER BY p.name
            LIMIT 6
        ) AS terms
    ) AS term_results;
END $$

DROP PROCEDURE IF EXISTS catalog_get_categories $$
CREATE PROCEDURE catalog_get_categories(
    IN p_include_inactive TINYINT(1)
)
BEGIN
    SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.parent_id,
        parent.name AS parent_name,
        c.is_active,
        c.created_at,
        c.updated_at
    FROM categories c
    LEFT JOIN categories parent ON parent.id = c.parent_id
    WHERE IFNULL(p_include_inactive, 0) = 1
       OR c.is_active = 1
    ORDER BY c.name ASC, c.id ASC;
END $$

DROP PROCEDURE IF EXISTS discount_cleanup_expired_codes $$
CREATE PROCEDURE discount_cleanup_expired_codes()
BEGIN
    DECLARE v_deactivated_codes INT DEFAULT 0;
    DECLARE v_purged_applied_discounts INT DEFAULT 0;

    UPDATE discount_codes
    SET is_active = 0,
        updated_at = CURRENT_TIMESTAMP
    WHERE is_active = 1
      AND end_date IS NOT NULL
      AND end_date < CURRENT_TIMESTAMP;

    SET v_deactivated_codes = ROW_COUNT();

    DELETE FROM user_applied_discounts
    WHERE expires_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MONTH);

    SET v_purged_applied_discounts = ROW_COUNT();

    SELECT
        v_deactivated_codes AS deactivated_codes,
        v_purged_applied_discounts AS purged_applied_discounts;
END $$

DROP PROCEDURE IF EXISTS order_get_history $$
CREATE PROCEDURE order_get_history(
    IN p_order_id INT
)
BEGIN
    SELECT
        osh.id,
        osh.order_id,
        o.order_number,
        o.user_id AS order_user_id,
        o.status AS order_status,
        o.payment_status AS order_payment_status,
        osh.changed_by,
        osh.changed_by_name,
        u.name AS changed_by_full_name,
        u.role AS changed_by_role,
        osh.change_type,
        osh.field_changed,
        osh.old_value,
        osh.new_value,
        osh.description,
        osh.ip_address,
        osh.user_agent,
        osh.created_at
    FROM order_status_history osh
    JOIN orders o ON o.id = osh.order_id
    LEFT JOIN users u ON u.id = osh.changed_by
    WHERE osh.order_id = p_order_id
    ORDER BY osh.created_at DESC, osh.id DESC;
END $$

DROP FUNCTION IF EXISTS notification_user_summary $$
CREATE FUNCTION notification_user_summary(
    p_user_id VARCHAR(50)
)
RETURNS TEXT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_result TEXT;

    SELECT CONCAT(
        'Notificaciones para ', COALESCE(uc.name, p_user_id), ' (', p_user_id, '):', CHAR(10),
        CASE
            WHEN uc.pending_orders > 0
                THEN CONCAT('- Tienes ', uc.pending_orders, ' pedido(s) pendiente(s) por procesar.', CHAR(10))
            ELSE CONCAT('- No tienes pedidos pendientes.', CHAR(10))
        END,
        CASE
            WHEN uc.cart_items > 0
                THEN CONCAT('- Tienes ', uc.cart_items, ' artículo(s) en tu carrito. ¡Completa tu compra!', CHAR(10))
            ELSE CONCAT('- Tu carrito está vacío.', CHAR(10))
        END
    )
    INTO v_result
    FROM (
        SELECT
            u.id,
            u.name,
            COALESCE(oc.pending_orders, 0) AS pending_orders,
            COALESCE(cc.cart_items, 0) AS cart_items
        FROM users u
        LEFT JOIN (
            SELECT
                user_id,
                COUNT(CASE WHEN status NOT IN ('completed', 'cancelled') THEN 1 END) AS pending_orders
            FROM orders
            GROUP BY user_id
        ) AS oc ON oc.user_id = u.id
        LEFT JOIN (
            SELECT
                c.user_id,
                COALESCE(SUM(ci.quantity), 0) AS cart_items
            FROM carts c
            JOIN cart_items ci ON ci.cart_id = c.id
            GROUP BY c.user_id
        ) AS cc ON cc.user_id = u.id
        WHERE u.id = p_user_id
    ) AS uc;

    RETURN v_result;
END $$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
