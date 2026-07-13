# Bugs / edge cases

Tres casos que deberían testearse en Opción Yo, cada uno atado a un flujo del
caso. Formato: **qué pasa · cómo reproducir · qué debería pasar · severidad**.

Dos de ellos ya tienen un test que los cubre en esta suite (marcados abajo); el
tercero es un riesgo documentado con la reproducción propuesta.

| # | Caso | Flujo | Severidad | Cubierto por test |
|---|------|-------|-----------|-------------------|
| [BUG-001](BUG-001-double-booking-race.md) | Doble reserva del mismo slot por carrera | C – Agenda | 🔴 Alta | ✅ (versión secuencial) |
| [BUG-002](BUG-002-webhook-not-idempotent.md) | Webhook de Stripe no idempotente | B – Pago | 🔴 Alta | ⚠️ Riesgo documentado |
| [BUG-003](BUG-003-login-no-rate-limit.md) | Login sin límite de intentos (fuerza bruta) | A – Login | 🟠 Media-Alta | ✅ (fix + test) |
