CREATE TABLE IF NOT EXISTS stock_reservations (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    size_variant_id BIGINT NULL,
    reservation_key VARCHAR(120) NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'reserved',
    expires_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS stock_reservations_order_id_idx
    ON stock_reservations (order_id);

CREATE INDEX IF NOT EXISTS stock_reservations_status_idx
    ON stock_reservations (status);

CREATE INDEX IF NOT EXISTS stock_reservations_expires_at_idx
    ON stock_reservations (expires_at);

CREATE INDEX IF NOT EXISTS stock_reservations_reservation_key_idx
    ON stock_reservations (reservation_key);

CREATE INDEX IF NOT EXISTS stock_reservations_order_status_idx
    ON stock_reservations (order_id, status);
