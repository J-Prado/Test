# BUG-003 — Login sin límite de intentos (fuerza bruta)

- **Flujo:** A — Login
- **Severidad:** 🟠 Media-Alta (riesgo de account takeover; datos de salud = sensibles)

## Qué pasa

Un endpoint de login sin *rate limiting* permite probar miles de contraseñas por
minuto contra una cuenta. En una plataforma de salud, una toma de cuenta expone
datos clínicos sensibles, así que el impacto es alto aunque el ataque sea básico.

## Cómo reproducirlo

1. Crear un usuario.
2. Enviar 20+ `POST /login` con contraseña incorrecta en pocos segundos.
3. Observar que **todas** responden 422 (credenciales inválidas) sin bloqueo.

**Resultado observado (sin fix):** intentos ilimitados; ningún 429.

## Qué debería pasar

Tras N intentos fallidos en una ventana corta, el endpoint responde **429 Too
Many Requests** y frena el resto. Idealmente el límite combina IP + email y
crece con backoff.

## Estado en este repo (fix aplicado)

La ruta de login usa `throttle:6,1` (6 intentos por minuto). Al 7° intento
responde **429**.

Test que lo verifica: `app-mock/tests/Feature/Flujo_A_AuthTest.php` → *"throttles repeated
failed logins (brute-force protection)"*.

## Mejora futura

- Bloqueo por cuenta con backoff exponencial además del throttle por IP.
- CAPTCHA tras X fallos.
- Alerta/monitoreo de picos de 401/429 por usuario.
