-- Angelow - Estructura unificada de base de datos para microservicios
-- Motor objetivo: PostgreSQL
-- Fecha de análisis: 2026-06-29
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
--    el dump completo `basededatoscompletaAntigua.sql`, adaptados a PostgreSQL
--    y a los límites actuales de cada microservicio.
--
-- Importante: es un diseño de referencia. No ejecutarlo sobre producción sin
-- revisión previa, porque no contiene `DROP TABLE` ni lógica de migración de datos.

BEGIN;

-- ============================================================
-- Tablas técnicas compartidas de Laravel
-- ============================================================

CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE INDEX idx_cache_expiration ON cache (expiration);

CREATE TABLE cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE INDEX idx_cache_locks_expiration ON cache_locks (expiration);

CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE INDEX idx_jobs_queue ON jobs (queue);

CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT NULL,
    cancelled_at INTEGER NULL,
    created_at INTEGER NOT NULL,
    finished_at INTEGER NULL
);

CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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
    is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL,
    remember_token VARCHAR(255) NULL,
    token_expiry TIMESTAMP NULL,
    trial548 CHAR(1) NULL,
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin'))
);

CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_phone ON users (phone);
CREATE INDEX idx_users_role ON users (role);

CREATE TABLE access_tokens (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_revoked BOOLEAN NOT NULL DEFAULT FALSE,
    trial548 CHAR(1) NULL
);

CREATE INDEX idx_access_tokens_user_id ON access_tokens (user_id);

CREATE TABLE google_auth (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    google_id VARCHAR(255) NOT NULL UNIQUE,
    access_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE INDEX idx_google_auth_user_id ON google_auth (user_id);

CREATE TABLE password_resets (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_password_resets_user_id ON password_resets (user_id);
CREATE INDEX idx_password_resets_token ON password_resets (token);

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_sessions_user_id ON sessions (user_id);
CREATE INDEX idx_sessions_last_activity ON sessions (last_activity);

CREATE TABLE login_attempts (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE INDEX idx_login_attempts_username_date ON login_attempts (username, attempt_date);
CREATE INDEX idx_login_attempts_ip_date ON login_attempts (ip_address, attempt_date);

CREATE TABLE auth_login_attempts (
    id BIGSERIAL PRIMARY KEY,
    credential VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    failed_attempts SMALLINT NOT NULL DEFAULT 0,
    last_failed_at TIMESTAMP NULL,
    blocked_until TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_auth_login_attempts_credential_ip UNIQUE (credential, ip_address)
);

CREATE INDEX idx_auth_login_attempts_blocked_until ON auth_login_attempts (blocked_until);

CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id VARCHAR(50) NOT NULL,
    name TEXT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE INDEX idx_personal_access_tokens_expires_at ON personal_access_tokens (expires_at);

-- ============================================================
-- shipping-service
-- ============================================================

CREATE TABLE shipping_methods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    base_cost NUMERIC(10, 2) NOT NULL DEFAULT 0,
    delivery_time VARCHAR(50) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    free_shipping_threshold NUMERIC(10, 2) NULL,
    available_cities TEXT NULL,
    estimated_days_min INTEGER NOT NULL DEFAULT 1,
    estimated_days_max INTEGER NOT NULL DEFAULT 3,
    city VARCHAR(100) NOT NULL DEFAULT 'Medellin',
    free_shipping_minimum NUMERIC(10, 2) NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'fas fa-truck',
    trial554 CHAR(1) NULL
);

CREATE TABLE shipping_price_rules (
    id SERIAL PRIMARY KEY,
    min_price NUMERIC(10, 2) NOT NULL,
    max_price NUMERIC(10, 2) NULL,
    shipping_cost NUMERIC(10, 2) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE TABLE user_addresses (
    id SERIAL PRIMARY KEY,
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
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    gps_latitude NUMERIC(10, 8) NULL,
    gps_longitude NUMERIC(11, 8) NULL,
    gps_accuracy NUMERIC(10, 2) NULL,
    gps_timestamp TIMESTAMP NULL,
    gps_used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
);

CREATE INDEX idx_user_addresses_user_id ON user_addresses (user_id);
CREATE INDEX idx_user_addresses_is_default ON user_addresses (is_default);
CREATE INDEX idx_user_addresses_is_active ON user_addresses (is_active);

-- ============================================================
-- catalog-service
-- ============================================================

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    parent_id INTEGER NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE collections (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    launch_date DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE colors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    hex_code VARCHAR(7) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE sizes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(100) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    brand VARCHAR(100) NULL,
    gender VARCHAR(20) NOT NULL DEFAULT 'unisex',
    collection VARCHAR(50) NULL,
    material VARCHAR(100) NULL,
    care_instructions TEXT NULL,
    compare_price NUMERIC(10, 2) NULL,
    price NUMERIC(10, 2) NULL,
    category_id INTEGER NOT NULL,
    collection_id INTEGER NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_refundable BOOLEAN NOT NULL DEFAULT FALSE,
    refund_days SMALLINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_products_category_id ON products (category_id);
CREATE INDEX idx_products_collection_id ON products (collection_id);
CREATE INDEX idx_products_is_active ON products (is_active);

CREATE TABLE product_collections (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    collection_id INTEGER NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT uq_product_collections_product_collection UNIQUE (product_id, collection_id)
);

CREATE INDEX idx_product_collections_product_id ON product_collections (product_id);
CREATE INDEX idx_product_collections_collection_id ON product_collections (collection_id);

CREATE TABLE product_color_variants (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    color_id INTEGER NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_product_color_variants_product_id ON product_color_variants (product_id);

CREATE TABLE product_size_variants (
    id SERIAL PRIMARY KEY,
    color_variant_id INTEGER NOT NULL,
    size_id INTEGER NULL,
    sku VARCHAR(50) NULL,
    barcode VARCHAR(50) NULL,
    price NUMERIC(10, 2) NOT NULL,
    compare_price NUMERIC(10, 2) NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_product_size_variants_color_variant_id ON product_size_variants (color_variant_id);
CREATE INDEX idx_product_size_variants_size_id ON product_size_variants (size_id);

CREATE TABLE product_images (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    color_variant_id INTEGER NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    "order" INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_product_images_product_id ON product_images (product_id);

CREATE TABLE variant_images (
    id SERIAL PRIMARY KEY,
    color_variant_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    image_id INTEGER NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    "order" INTEGER NOT NULL DEFAULT 0,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
);

CREATE INDEX idx_variant_images_color_variant_id ON variant_images (color_variant_id);
CREATE INDEX idx_variant_images_product_id ON variant_images (product_id);

CREATE TABLE wishlist (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    product_id INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL,
    CONSTRAINT uq_wishlist_user_product UNIQUE (user_id, product_id)
);

CREATE TABLE product_reviews (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    order_id INTEGER NULL,
    rating SMALLINT NOT NULL,
    title VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    images TEXT NULL,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    is_approved BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT chk_product_reviews_rating CHECK (rating BETWEEN 1 AND 5)
);

CREATE INDEX idx_product_reviews_product_id ON product_reviews (product_id);
CREATE INDEX idx_product_reviews_user_id ON product_reviews (user_id);

CREATE TABLE review_votes (
    id SERIAL PRIMARY KEY,
    review_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    is_helpful BOOLEAN NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL,
    CONSTRAINT uq_review_votes_review_user UNIQUE (review_id, user_id)
);

CREATE TABLE product_questions (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    question TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_product_questions_product_id ON product_questions (product_id);

CREATE TABLE question_answers (
    id SERIAL PRIMARY KEY,
    question_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    answer TEXT NOT NULL,
    is_seller BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_question_answers_question_id ON question_answers (question_id);

CREATE TABLE popular_searches (
    id SERIAL PRIMARY KEY,
    search_term VARCHAR(255) NOT NULL UNIQUE,
    search_count INTEGER NOT NULL DEFAULT 1,
    last_searched TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE TABLE search_history (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    search_term VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_search_history_user_id ON search_history (user_id);

CREATE TABLE site_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    category VARCHAR(40) NOT NULL DEFAULT 'general',
    updated_by VARCHAR(50) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE TABLE sliders (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    image VARCHAR(500) NOT NULL,
    image_url VARCHAR(500) NULL,
    link VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    order_position INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
);

CREATE TABLE announcements (
    id SERIAL PRIMARY KEY,
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
    priority INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
);

CREATE TABLE stock_history (
    id SERIAL PRIMARY KEY,
    variant_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    previous_qty INTEGER NOT NULL,
    new_qty INTEGER NOT NULL,
    operation VARCHAR(12) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial558 CHAR(1) NULL
);

CREATE INDEX idx_stock_history_variant_id ON stock_history (variant_id);

CREATE TABLE inventory_alerts (
    id SERIAL PRIMARY KEY,
    variant_id INTEGER NOT NULL UNIQUE,
    product_id INTEGER NULL,
    product_name VARCHAR(255) NULL,
    color_name VARCHAR(120) NULL,
    size_label VARCHAR(120) NULL,
    sku VARCHAR(80) NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'out',
    out_of_stock_since TIMESTAMP NULL,
    last_initial_notification_at TIMESTAMP NULL,
    last_reminder_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_inventory_alerts_status_out_since ON inventory_alerts (status, out_of_stock_since);
CREATE INDEX idx_inventory_alerts_product_id ON inventory_alerts (product_id);

-- ============================================================
-- cart-service
-- ============================================================

CREATE TABLE carts (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE cart_items (
    id SERIAL PRIMARY KEY,
    cart_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    color_variant_id INTEGER NULL,
    size_variant_id INTEGER NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL,
    CONSTRAINT chk_cart_items_quantity CHECK (quantity > 0)
);

CREATE INDEX idx_cart_items_cart_id ON cart_items (cart_id);
CREATE INDEX idx_cart_items_product_id ON cart_items (product_id);

-- ============================================================
-- order-service
-- ============================================================

CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    invoice_number VARCHAR(20) NULL,
    user_id VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    subtotal NUMERIC(10, 2) NOT NULL,
    shipping_cost NUMERIC(10, 2) NOT NULL DEFAULT 0,
    discount_amount NUMERIC(10, 2) NOT NULL DEFAULT 0,
    total NUMERIC(10, 2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    shipping_address TEXT NULL,
    shipping_city VARCHAR(100) NULL,
    shipping_method_id INTEGER NULL,
    shipping_address_id INTEGER NULL,
    billing_address TEXT NULL,
    billing_address_id INTEGER NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    invoice_resolution VARCHAR(50) NULL,
    invoice_date TIMESTAMP NULL,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_orders_user_id ON orders (user_id);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_payment_status ON orders (payment_status);

CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    color_variant_id INTEGER NULL,
    size_variant_id INTEGER NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_name VARCHAR(255) NULL,
    price NUMERIC(10, 2) NOT NULL,
    quantity INTEGER NOT NULL,
    total NUMERIC(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0)
);

CREATE INDEX idx_order_items_order_id ON order_items (order_id);

CREATE TABLE order_status_history (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    changed_by VARCHAR(50) NULL,
    changed_by_name VARCHAR(100) NULL,
    change_type VARCHAR(20) NOT NULL DEFAULT 'other',
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE INDEX idx_order_status_history_order_id ON order_status_history (order_id);

CREATE TABLE order_views (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_order_views_order_user UNIQUE (order_id, user_id)
);

CREATE TABLE stock_reservations (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    size_variant_id INTEGER NULL,
    reservation_key VARCHAR(120) NOT NULL,
    quantity INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'reserved',
    expires_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_stock_reservations_quantity CHECK (quantity > 0)
);

CREATE INDEX idx_stock_reservations_order_id ON stock_reservations (order_id);
CREATE INDEX idx_stock_reservations_status ON stock_reservations (status);
CREATE INDEX idx_stock_reservations_expires_at ON stock_reservations (expires_at);
CREATE INDEX idx_stock_reservations_key ON stock_reservations (reservation_key);
CREATE INDEX idx_stock_reservations_order_status ON stock_reservations (order_id, status);

CREATE TABLE order_refund_requests (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    user_id VARCHAR(50) NULL,
    user_email VARCHAR(255) NULL,
    reason VARCHAR(80) NOT NULL,
    details TEXT NULL,
    evidence_path VARCHAR(500) NULL,
    evidence_original_name VARCHAR(255) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'requested',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_order_refund_requests_order_id ON order_refund_requests (order_id);
CREATE INDEX idx_order_refund_requests_status ON order_refund_requests (status);

-- ============================================================
-- payment-service
-- ============================================================

CREATE TABLE colombian_banks (
    id SERIAL PRIMARY KEY,
    bank_code VARCHAR(10) NOT NULL UNIQUE,
    bank_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    trial551 CHAR(1) NULL
);

CREATE TABLE bank_account_config (
    id SERIAL PRIMARY KEY,
    bank_code VARCHAR(10) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_type VARCHAR(20) NOT NULL,
    account_holder VARCHAR(100) NOT NULL,
    identification_type VARCHAR(10) NOT NULL DEFAULT 'cc',
    identification_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
);

CREATE TABLE payment_transactions (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NULL,
    user_id VARCHAR(50) NULL,
    amount NUMERIC(10, 2) NOT NULL,
    reference_number VARCHAR(50) NULL,
    payment_proof VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    verified_by VARCHAR(50) NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE INDEX idx_payment_transactions_order_id ON payment_transactions (order_id);
CREATE INDEX idx_payment_transactions_user_id ON payment_transactions (user_id);
CREATE INDEX idx_payment_transactions_status ON payment_transactions (status);

-- ============================================================
-- discount-service
-- ============================================================

CREATE TABLE discount_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE discount_codes (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_type_id INTEGER NOT NULL,
    discount_value NUMERIC(10, 2) NULL,
    max_uses INTEGER NULL,
    used_count INTEGER NOT NULL DEFAULT 0,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_single_use BOOLEAN NOT NULL DEFAULT FALSE,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE discount_code_products (
    id SERIAL PRIMARY KEY,
    discount_code_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_discount_code_products_code_product UNIQUE (discount_code_id, product_id)
);

CREATE TABLE discount_code_usage (
    id SERIAL PRIMARY KEY,
    discount_code_id INTEGER NOT NULL,
    user_id VARCHAR(50) NULL,
    order_id INTEGER NULL,
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE percentage_discounts (
    id SERIAL PRIMARY KEY,
    discount_code_id INTEGER NOT NULL,
    percentage NUMERIC(5, 2) NOT NULL,
    max_discount_amount NUMERIC(10, 2) NULL,
    trial554 CHAR(1) NULL
);

CREATE TABLE fixed_amount_discounts (
    id SERIAL PRIMARY KEY,
    discount_code_id INTEGER NOT NULL,
    amount NUMERIC(10, 2) NOT NULL,
    min_order_amount NUMERIC(10, 2) NULL,
    trial551 CHAR(1) NULL
);

CREATE TABLE free_shipping_discounts (
    id SERIAL PRIMARY KEY,
    discount_code_id INTEGER NOT NULL,
    shipping_method_id INTEGER NULL,
    trial551 CHAR(1) NULL
);

CREATE TABLE bulk_discount_rules (
    id SERIAL PRIMARY KEY,
    min_quantity INTEGER NOT NULL,
    max_quantity INTEGER NULL,
    discount_percentage NUMERIC(5, 2) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
);

CREATE TABLE user_applied_discounts (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    discount_code_id INTEGER NOT NULL,
    discount_code VARCHAR(20) NOT NULL,
    discount_amount NUMERIC(10, 2) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    trial558 CHAR(1) NULL
);

CREATE INDEX idx_user_applied_discounts_user_id ON user_applied_discounts (user_id);
CREATE INDEX idx_user_applied_discounts_discount_code_id ON user_applied_discounts (discount_code_id);

-- ============================================================
-- notification-service
-- ============================================================

CREATE TABLE notification_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    template TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    type_id INTEGER NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(30) NULL,
    related_entity_id INTEGER NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    is_email_sent BOOLEAN NOT NULL DEFAULT FALSE,
    is_sms_sent BOOLEAN NOT NULL DEFAULT FALSE,
    is_push_sent BOOLEAN NOT NULL DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    trial551 CHAR(1) NULL
);

CREATE INDEX idx_notifications_user_id ON notifications (user_id);
CREATE INDEX idx_notifications_type_id ON notifications (type_id);

CREATE TABLE notification_preferences (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    type_id INTEGER NOT NULL,
    email_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    sms_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    push_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL,
    CONSTRAINT uq_notification_preferences_user_type UNIQUE (user_id, type_id)
);

CREATE TABLE notification_queue (
    id SERIAL PRIMARY KEY,
    notification_id INTEGER NOT NULL,
    channel VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts SMALLINT NOT NULL DEFAULT 0,
    last_attempt_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    trial551 CHAR(1) NULL
);

CREATE INDEX idx_notification_queue_notification_id ON notification_queue (notification_id);
CREATE INDEX idx_notification_queue_status ON notification_queue (status);

CREATE TABLE admin_notification_dismissals (
    id SERIAL PRIMARY KEY,
    admin_id VARCHAR(50) NOT NULL,
    notification_key VARCHAR(120) NOT NULL,
    dismissed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL,
    CONSTRAINT uq_admin_notification_dismissals_admin_key UNIQUE (admin_id, notification_key)
);

-- ============================================================
-- audit-service
-- ============================================================

CREATE TABLE audit_categories (
    audit_id SERIAL PRIMARY KEY,
    category_id INTEGER NULL,
    action_type VARCHAR(10) NULL,
    old_name VARCHAR(100) NULL,
    new_name VARCHAR(100) NULL,
    action_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial548 CHAR(1) NULL
);

CREATE TABLE audit_orders (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER NOT NULL,
    accion VARCHAR(10) NOT NULL,
    usuario_id VARCHAR(50) NULL,
    sql_usuario VARCHAR(255) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT NULL,
    trial548 CHAR(1) NULL
);

CREATE INDEX idx_audit_orders_orden_id ON audit_orders (orden_id);

CREATE TABLE audit_users (
    id SERIAL PRIMARY KEY,
    usuario_id VARCHAR(50) NOT NULL,
    accion VARCHAR(10) NOT NULL,
    usuario_modificador VARCHAR(50) NULL,
    sql_usuario VARCHAR(255) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT NULL,
    trial548 CHAR(1) NULL
);

CREATE INDEX idx_audit_users_usuario_id ON audit_users (usuario_id);

CREATE TABLE productos_auditoria (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL DEFAULT 'Creado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial554 CHAR(1) NULL
);

CREATE TABLE eliminaciones_auditoria (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL DEFAULT 'Eliminado',
    fecha_eliminacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trial551 CHAR(1) NULL
);

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
-- Triggers, vistas, funciones y procedimientos
-- ============================================================
--
-- El dump completo contiene triggers de auditoría y procedimientos MySQL.
-- En la arquitectura distribuida se evitan triggers que crucen dominios o
-- dupliquen escrituras que ya hace Laravel. Se conservan como objetos seguros
-- las vistas y funciones de lectura/mantenimiento que mejoran consultas.

CREATE INDEX idx_products_active_filters
    ON products (is_active, category_id, gender, collection_id, is_featured, created_at);

CREATE INDEX idx_products_active_price
    ON products (is_active, price);

CREATE INDEX idx_product_images_primary_order
    ON product_images (product_id, is_primary DESC, "order", id);

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
    COALESCE(primary_image.image_path, 'uploads/products/default-product.jpg') AS primary_image,
    COALESCE(price_stats.min_price, p.price, 0)::numeric(10, 2) AS min_price,
    COALESCE(price_stats.max_price, p.price, 0)::numeric(10, 2) AS max_price,
    COALESCE(price_stats.available_stock, 0)::integer AS available_stock,
    COALESCE(review_stats.avg_rating, 0)::numeric(4, 2) AS avg_rating,
    COALESCE(review_stats.review_count, 0)::integer AS review_count
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN collections col ON col.id = p.collection_id
LEFT JOIN LATERAL (
    SELECT pi.image_path
    FROM product_images pi
    WHERE pi.product_id = p.id
    ORDER BY pi.is_primary DESC, pi."order" ASC, pi.id ASC
    LIMIT 1
) AS primary_image ON TRUE
LEFT JOIN LATERAL (
    SELECT
        MIN(psv.price) AS min_price,
        MAX(psv.price) AS max_price,
        SUM(CASE WHEN psv.is_active THEN psv.quantity ELSE 0 END) AS available_stock
    FROM product_color_variants pcv
    JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
    WHERE pcv.product_id = p.id
) AS price_stats ON TRUE
LEFT JOIN LATERAL (
    SELECT
        AVG(pr.rating) AS avg_rating,
        COUNT(*) AS review_count
    FROM product_reviews pr
    WHERE pr.product_id = p.id
      AND COALESCE(pr.is_approved, TRUE) = TRUE
) AS review_stats ON TRUE;

CREATE OR REPLACE FUNCTION catalog_search_products_and_terms(
    p_search_term TEXT,
    p_limit INTEGER DEFAULT 5
)
RETURNS TABLE (
    result_type TEXT,
    product_id INTEGER,
    name VARCHAR(255),
    slug VARCHAR(255),
    image_path VARCHAR(255),
    search_term_result VARCHAR(255),
    sort_rank INTEGER
)
LANGUAGE sql
STABLE
AS $$
    (
        SELECT
            'product'::TEXT AS result_type,
            p.id AS product_id,
            p.name,
            p.slug,
            p.primary_image::VARCHAR(255) AS image_path,
            NULL::VARCHAR(255) AS search_term_result,
            CASE
                WHEN p.name ILIKE p_search_term || '%' THEN 1
                WHEN p.name ILIKE '%' || p_search_term || '%' THEN 2
                ELSE 3
            END AS sort_rank
        FROM catalog_product_listing_view p
        WHERE p.is_active = TRUE
          AND (
              p.name ILIKE '%' || p_search_term || '%'
              OR p.description ILIKE '%' || p_search_term || '%'
              OR p.brand ILIKE '%' || p_search_term || '%'
          )
        ORDER BY sort_rank, p.name
        LIMIT GREATEST(1, LEAST(COALESCE(p_limit, 5), 20))
    )
    UNION ALL
    (
        SELECT
            'term'::TEXT AS result_type,
            NULL::INTEGER AS product_id,
            NULL::VARCHAR(255) AS name,
            NULL::VARCHAR(255) AS slug,
            NULL::VARCHAR(255) AS image_path,
            terms.name::VARCHAR(255) AS search_term_result,
            10 AS sort_rank
        FROM (
            SELECT DISTINCT p.name
            FROM catalog_product_listing_view p
            WHERE p.is_active = TRUE
              AND p.name ILIKE '%' || p_search_term || '%'
              AND p.name IS NOT NULL
              AND p.name <> ''
            ORDER BY p.name
            LIMIT 6
        ) AS terms
    );
$$;

CREATE INDEX idx_discount_codes_code_lower
    ON discount_codes (LOWER(code));

CREATE INDEX idx_discount_codes_active_dates
    ON discount_codes (is_active, start_date, end_date, used_count);

CREATE INDEX idx_discount_code_usage_code_user
    ON discount_code_usage (discount_code_id, user_id);

CREATE INDEX idx_bulk_discount_rules_active_range
    ON bulk_discount_rules (is_active, min_quantity, max_quantity, discount_percentage);

CREATE OR REPLACE FUNCTION discount_cleanup_expired_codes()
RETURNS TABLE (
    deactivated_codes INTEGER,
    purged_applied_discounts INTEGER
)
LANGUAGE plpgsql
AS $$
BEGIN
    WITH updated_codes AS (
        UPDATE discount_codes
        SET is_active = FALSE,
            updated_at = CURRENT_TIMESTAMP
        WHERE is_active = TRUE
          AND end_date IS NOT NULL
          AND end_date < CURRENT_TIMESTAMP
        RETURNING 1
    )
    SELECT COUNT(*)::INTEGER INTO deactivated_codes
    FROM updated_codes;

    WITH deleted_applied AS (
        DELETE FROM user_applied_discounts
        WHERE expires_at < CURRENT_TIMESTAMP - INTERVAL '2 months'
        RETURNING 1
    )
    SELECT COUNT(*)::INTEGER INTO purged_applied_discounts
    FROM deleted_applied;

    RETURN NEXT;
END;
$$;

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
    DATE_TRUNC('day', created_at)::DATE AS sales_date,
    status,
    payment_status,
    COUNT(*)::INTEGER AS orders_count,
    COALESCE(SUM(subtotal), 0)::numeric(12, 2) AS subtotal,
    COALESCE(SUM(shipping_cost), 0)::numeric(12, 2) AS shipping_total,
    COALESCE(SUM(discount_amount), 0)::numeric(12, 2) AS discount_total,
    COALESCE(SUM(total), 0)::numeric(12, 2) AS sales_total
FROM orders
GROUP BY DATE_TRUNC('day', created_at)::DATE, status, payment_status;

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

COMMIT;
