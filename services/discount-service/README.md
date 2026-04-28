# Discount Service

Microservicio Laravel para reglas de descuento, códigos promocionales y seguimiento de uso.

## Endpoints

- `GET /api/health`
- `GET /api/discounts/codes`
- `POST /api/discounts/validate`

## Tablas de dominio

- `discount_types`
- `discount_codes`
- `discount_code_products`
- `discount_code_usage`
- `percentage_discounts`
- `fixed_amount_discounts`
- `free_shipping_discounts`
- `bulk_discount_rules`
- `user_applied_discounts`
