# BUG-001 — Doble reserva del mismo slot (condición de carrera)

- **Flujo:** C — Agendamiento
- **Severidad:** 🔴 Alta (dos pacientes pagando por el mismo turno = incidente de soporte + reembolso + especialista con doble booking)

## Qué pasa

Si dos pacientes envían `POST /appointments` para el **mismo `slot_id` casi
simultáneamente**, y la disponibilidad del slot se chequea *sin* un lock, ambas
requests leen `status = available`, ambas crean una cita y el slot queda
doble-reservado. Es el clásico *check-then-act* sin sincronización.

## Cómo reproducirlo

1. Crear un slot disponible.
2. Disparar dos requests concurrentes de reserva del mismo slot (p. ej. dos hilos
   / `ab -c 2 -n 2` / dos llamadas `Promise.all`).
3. Consultar la tabla `appointments`.

**Resultado observado (sin fix):** 2 citas para el mismo slot.

## Qué debería pasar

Exactamente **una** reserva gana (201) y la otra recibe **409 Conflict**;
`appointments` tiene 1 sola fila para ese slot.

## Cómo lo mitigo en este repo

`AppointmentController::store()` envuelve la operación en una transacción con
`lockForUpdate()` sobre el slot y chequea la disponibilidad **bajo el lock**.
Test: `tests/Feature/Flujo_C_AppointmentTest.php` → *"rejects booking a slot that
is already taken"* (versión secuencial, que es lo determinista en CI).

## Nota de testing

La carrera real es no-determinista y difícil de reproducir en SQLite (un solo
writer). Para cubrirla de verdad se corre un test de concurrencia contra
**MySQL** (dos conexiones en paralelo) y/o se agrega una **restricción única a
nivel DB** sobre citas activas por slot como segunda barrera. Recomendación:
ambas — lock aplicativo + unique index — defensa en profundidad.
