#!/usr/bin/env bash
# Generate TIMESTAMP, NONCE, BODY_HASH and SIGNATURE in your trusted application.
curl -X POST 'https://pay.example.com/api/v1/payments' \
  -H 'Content-Type: application/json' \
  -H 'X-CardPay-Key: cp_pk_REPLACE_ME' \
  -H 'X-CardPay-Timestamp: TIMESTAMP' \
  -H 'X-CardPay-Nonce: NONCE' \
  -H 'X-CardPay-Signature: SIGNATURE' \
  -H 'Idempotency-Key: order-123-first-attempt' \
  --data '{"amount":80000000,"external_order_id":"ORDER-123"}'
