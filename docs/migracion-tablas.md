# Mapa de Tablas Migradas (`basededatos.sql`)

## auth-service

- `users`
- `access_tokens`
- `google_auth`
- `login_attempts`
- `password_resets`
- `personal_access_tokens`
- `sessions`

## catalog-service

- `categories`
- `collections`
- `colors`
- `sizes`
- `products`
- `product_collections`
- `product_color_variants`
- `product_size_variants`
- `product_images`
- `variant_images`
- `wishlist`
- `product_reviews`
- `review_votes`
- `product_questions`
- `question_answers`
- `popular_searches`
- `search_history`
- `site_settings`
- `sliders`
- `announcements`
- `stock_history`

## cart-service

- `carts`
- `cart_items`

## order-service

- `orders`
- `order_items`
- `order_status_history`
- `order_views`

## payment-service

- `colombian_banks`
- `bank_account_config`
- `payment_transactions`

## discount-service

- `discount_types`
- `discount_codes`
- `discount_code_products`
- `discount_code_usage`
- `percentage_discounts`
- `fixed_amount_discounts`
- `free_shipping_discounts`
- `bulk_discount_rules`
- `user_applied_discounts`

## shipping-service

- `shipping_methods`
- `shipping_price_rules`
- `user_addresses`

## notification-service

- `notification_types`
- `notifications`
- `notification_preferences`
- `notification_queue`
- `admin_notification_dismissals`

## audit-service

- `audit_categories`
- `audit_orders`
- `audit_users`
- `productos_auditoria`
- `eliminaciones_auditoria`

## Tablas tecnicas Laravel (por servicio)

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `migrations`

## Nota de compatibilidad

El dump contiene columnas `trialXXX` (por ejemplo `trial548`, `trial554`, `trial558`) agregadas por la herramienta de migracion de origen. Se conservaron en las migraciones para facilitar trazabilidad e importaciones.
