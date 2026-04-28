# Payment Service

Microservicio Laravel para pagos, bancos y verificación de comprobantes.

## Endpoints

- `GET /api/health`
- `GET /api/banks`
- `GET /api/payments`
- `POST /api/payments`
- `PATCH /api/payments/{id}/verify`

## Tablas de dominio

- `colombian_banks`
- `bank_account_config`
- `payment_transactions`
