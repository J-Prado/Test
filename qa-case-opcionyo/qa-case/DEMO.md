# DEMO — Guion para presentar el caso

Guía práctica para tu demo en vivo (o grabada). Objetivo: mostrar en ~10-12 min
que la **estrategia** es sólida, que la **estructura** es clara y que los **tests
corren de verdad**. Ellos dijeron que quieren entender *cómo pensás*, no sólo lo
que entregaste — así que narrá las decisiones, no sólo el output.

---

## Antes de empezar (5 min de prep)

1. En la máquina donde vas a demostrar, corré el setup y confirmá que da verde:
   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts\setup.ps1   # Windows
   ```
   ```bash
   bash scripts/setup.sh                                        # macOS/Linux
   ```
2. Dejá **dos terminales** abiertas en la raíz del proyecto.
3. (Opcional, si vas a mostrar E2E) `cd e2e && npm install && npx playwright install chromium`.
4. Abrí el repo en el editor y tené a mano: `plan/qa-plan.md`, la carpeta
   `tests/`, `tests/Chime/DEVICE_MATRIX.md` y `.github/workflows/ci.yml`.
5. Cerrá notificaciones / silenciá el teléfono.

---

## Estructura de la demo (orden sugerido)

### 1. El porqué (1-2 min) — abrí con `plan/qa-plan.md`
Frase de apertura sugerida:
> "Con 11 mil sesiones al mes y cero tests, el mayor riesgo no es visual: es
> cobrar mal o que la videollamada no conecte. Por eso prioricé el 'camino de
> dinero' — Auth, Pago, Agenda — y traté Chime aparte porque no se puede testear
> con hardware real en CI."

Mostrá la **pirámide de tests** y la tabla de herramientas del plan.

### 2. Que corre de verdad (3-4 min) — terminal
```bash
php artisan test
```
Mientras corre, contá qué cubre cada archivo. Cuando termine en verde, destacá
que son **rápidos** (SQLite en memoria) y **deterministas** (sin red).

Después mostrá **un** test por dentro, el que mejor demuestra criterio. Sugerido:
el de agenda (`Flujo_C`) explicando la prevención de doble-booking con lock.

```bash
php artisan test tests/Feature/Flujo_C_AppointmentTest.php
```

### 3. Pago con Stripe (2 min)
Abrí `Flujo_B_PaymentTest.php` y contá la decisión clave:
> "El grueso corre con el SDK **mockeado**: rápido, sin secretos en CI. Pero
> dejé un test **opt-in contra el sandbox real** que se activa solo si hay una
> `sk_test_`. Y el webhook lo pruebo **firmando el payload** igual que Stripe,
> así verifico la validación de firma sin depender de la red."

Si tenés clave de test cargada, mostralo:
```bash
php artisan test --group=stripe
```

### 4. Chime — el flujo problemático (2-3 min)
Este es el que más van a mirar. Abrí `tests/Chime/DEVICE_MATRIX.md` y
`ChimeVideoTest.php`. Mensaje central:
> "No se puede testear medios reales en CI, así que separo dos cosas: la
> **lógica de orquestación** (crear meeting + attendee, tokens, manejo de error)
> la testeo con el **SDK de AWS mockeado** — eso va a CI en cada PR. Y la
> **realidad de dispositivos** la cubro con una **matriz priorizada** que corre
> en un device farm antes de cada release que toca video."

Corré:
```bash
php artisan test tests/Feature/ChimeVideoTest.php
```
Señalá el segundo test: el error de AWS **no se filtra** al paciente, se traduce
a un mensaje limpio de "intentá de nuevo".

### 5. E2E + CI (2 min)
- E2E (si el tiempo da, idealmente `--headed` para que se vea el navegador):
  ```bash
  cd e2e && npx playwright test --headed
  ```
- Abrí `.github/workflows/ci.yml` y explicá: corre en cada PR, dos jobs, y con
  branch protection **bloquea el merge** si algo falla.

### 6. Bugs (1 min) — cerrá con criterio de QA
Abrí `bugs/`. Mostrá que dos de los tres ya tienen test que los reproduce, y que
el de idempotencia de webhook está documentado como deuda con su fix propuesto.
Frase de cierre:
> "Mi definición de 'listo para prod' es simple: no confío en un flujo hasta que
> existe un test que falla cuando lo rompo. Todo bug que encuentro queda con un
> test que lo reproduce antes de darlo por resuelto."

---

## Preguntas que probablemente te hagan (y respuestas)

**¿Por qué SQLite si en prod usan MySQL?**
> Para que la suite corra en cualquier lado sin dependencias y sea rápida. El
> esquema y las migraciones son los mismos; para bugs de concurrencia real
> (doble-booking) corrés ese test puntual contra MySQL con dos conexiones.

**¿Por qué mockeás Stripe en vez de pegarle siempre al sandbox?**
> Determinismo y velocidad en CI, y no meter secretos en cada PR. El sandbox real
> lo cubro con un test opt-in para validar la integración de verdad cuando toca.

**¿Cómo probás la videollamada si no hay cámara en CI?**
> Divido lógica de medios. La lógica (meeting/attendee/tokens/errores) va con SDK
> mockeado en CI. Los medios reales van a una matriz de dispositivos en device
> farm, priorizada por tráfico × riesgo (Safari/iOS primero).

**¿Esto escala a un equipo?**
> Sí: convención de nombres por flujo, tests aislados, CI que bloquea merge, y
> cada bug entra con su test. Sumar cobertura es agregar archivos, no reescribir.

**¿Usaste IA?**
> Sí, y está documentado en `PROCESS.md` — la usé para acelerar scaffolding y
> boilerplate; las decisiones de estrategia, la matriz de Chime y la priorización
> son mías.

---

## Checklist final antes de grabar/presentar

- [ ] `php artisan test` da verde en la máquina de la demo.
- [ ] `.env` tiene `APP_KEY` (setup ya lo hace).
- [ ] Sé explicar **por qué** cada herramienta, no sólo qué hace.
- [ ] Tengo abierto el plan, un test, la matriz de Chime y el CI.
- [ ] Preparé la frase de apertura y la de cierre.
- [ ] Si muestro E2E, ya instalé el navegador de Playwright.
