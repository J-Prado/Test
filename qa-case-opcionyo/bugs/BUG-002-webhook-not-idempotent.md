# BUG-002 — Webhook de Stripe no idempotente

- **Flujo:** B — Pago
- **Severidad:** 🔴 Alta (estado de suscripción incorrecto, posible doble contabilización de ingresos)

## Qué pasa

Stripe **reintenta** la entrega de webhooks y puede enviar el **mismo evento más
de una vez** (garantía *at-least-once*). Si el handler procesa cada request sin
registrar qué `event.id` ya vio, un evento reentregado se aplica dos veces. En un
`updateStatus` idempotente el daño es menor, pero cualquier efecto acumulativo
(crear un registro de pago, sumar créditos, mandar email de bienvenida) se
duplica.

## Cómo reproducirlo

1. Suscripción existente con `stripe_id = sub_test_123`, `status = pending`.
2. Enviar el mismo evento `customer.subscription.updated` firmado **dos veces**.
3. Observar los efectos secundarios (no sólo el status).

**Resultado observado (sin fix):** el evento se procesa las dos veces; efectos
laterales duplicados.

## Qué debería pasar

El segundo procesamiento del mismo `event.id` se **descarta** (se responde 200
para que Stripe no siga reintentando) y no se ejecuta ningún efecto lateral otra
vez.

## Fix recomendado

Tabla `processed_stripe_events(event_id PRIMARY KEY, processed_at)`. Al recibir
un evento: `INSERT` del `event.id`; si viola la PK, ya fue procesado → responder
200 y salir. Todo el manejo, dentro de una transacción.

## Estado en este repo

El handler (`StripeWebhookController`) **verifica firma** y actualiza estado, pero
**todavía no deduplica por `event.id`** — documentado acá como deuda de test/fix.
El test `tests/Feature/Flujo_B_PaymentTest.php` cubre firma válida/ inválida y la
actualización de estado; el test de idempotencia se agrega junto con la tabla de
deduplicación.
