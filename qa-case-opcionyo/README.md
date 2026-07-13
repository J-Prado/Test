# QA Case — Opción Yo

Suite de tests automatizados para los flujos críticos de una plataforma de
bienestar con videollamadas: **Login/Auth**, **Pagos con Stripe**,
**Agendamiento** y **Videollamadas con AWS Chime**. Incluye pipeline de CI que
corre todo en cada PR.

> Es un proyecto Laravel de ejemplo, autocontenido. No usa el código real de
> Opción Yo — lo que se evalúa es la estrategia, la estructura y que los tests
> corran de verdad.

## Requisitos

- **PHP 8.2+** y **Composer** (para la suite principal)
- **Node 18+** (sólo para el E2E con Playwright)
- Ver **[INSTALL.md](INSTALL.md)** para instalarlos paso a paso en Windows/macOS/Linux.

## Correr todo con un solo comando

**Windows (PowerShell):**

```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup.ps1
```

**macOS / Linux:**

```bash
bash scripts/setup.sh
```

Eso instala dependencias, crea `.env`, genera la key, crea la base SQLite, corre
migraciones + seed y **ejecuta la suite completa** (Flows A, B, C y Chime).

Para el detalle de cómo correr cada flujo, el E2E y la integración real con
Stripe sandbox: **[RUN.md](RUN.md)**.

## Qué se prueba

| Flujo | Qué cubre | Archivo |
|-------|-----------|---------|
| **A — Login** | registro, login válido/ inválido, recurso protegido sin token, rate-limit | `tests/Feature/Flujo_A_AuthTest.php` |
| **B — Pago (Stripe)** | pago exitoso, tarjeta rechazada, webhook actualiza suscripción, firma inválida | `tests/Feature/Flujo_B_PaymentTest.php` |
| **C — Agendamiento** | reservar slot, doble-booking rechazado, cancelar libera slot | `tests/Feature/Flujo_C_AppointmentTest.php` |
| **Video — Chime** | crea meeting+attendee (mock AWS), manejo de error | `tests/Feature/ChimeVideoTest.php` |
| **E2E — Login** | flujo en navegador real (Playwright) | `e2e/tests/login.spec.ts` |

## Estructura

```
qa-case-opcionyo/
├── README.md            # este archivo — cómo correr todo
├── PROCESS.md           # herramientas usadas y por qué (incluye uso de IA)
├── INSTALL.md           # cómo instalar todo, paso a paso
├── RUN.md               # cómo correr cada cosa
├── DEMO.md              # guion para presentar el caso
├── plan/                # plan de QA (1 página)
├── tests/               # Flows A, B, C + Chime (+ matriz de dispositivos)
├── e2e/                 # Playwright (E2E de login)
├── bugs/                # 3 edge cases documentados
├── .github/workflows/   # pipeline de CI (bloquea el merge si algo falla)
├── app/ · routes/ · database/ · config/   # la app Laravel de ejemplo
└── scripts/             # setup con un comando (sh / ps1)
```

## CI

`.github/workflows/ci.yml` corre en cada PR y push a `main`:

- **job `tests`** — suite de API + Chime (obligatorio).
- **job `e2e`** — Playwright contra un server real.

Para que **bloquee el merge**, marcar estos checks como *required* en
**Settings → Branches → Branch protection rules** del repo.
