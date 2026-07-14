# QA Case — Opción Yo

Este repo está dividido en **dos partes** con un propósito distinto cada una:

| Carpeta | Qué es | Para qué |
|---------|--------|----------|
| **`app-mock/`** | Una app **Laravel de ejemplo** (auth, pagos, agenda, video). | El *andamiaje*: algo real contra qué correr los tests. No es el entregable. |
| **`qa-case/`** | El **entregable de QA**: plan, E2E (Playwright), bugs y la documentación del caso. | Lo que se evalúa: estrategia, estructura y que los tests corran de verdad. |

> **¿Por qué los tests de API/Feature (Flows A, B, C, Chime) están dentro de
> `app-mock/` y no en `qa-case/`?** Porque son tests nativos de Laravel: arrancan
> el kernel de la app y usan `RefreshDatabase`, factories y modelos. Sólo corren
> *desde dentro* del proyecto Laravel (`php artisan test`). El **E2E** de
> Playwright, en cambio, le pega a la app por HTTP y sí vive aparte, en
> `qa-case/e2e/`.

## Requisitos

- **PHP 8.2+** y **Composer** (para la suite principal, en `app-mock/`)
- **Node 18+** (sólo para el E2E con Playwright, en `qa-case/e2e/`)
- Ver **[qa-case/INSTALL.md](qa-case/INSTALL.md)** para instalarlos paso a paso.

## Correr todo con un solo comando

Desde la **raíz del repo**:

**Windows (PowerShell):**

```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup.ps1
```

**macOS / Linux:**

```bash
bash scripts/setup.sh
```

Eso entra a `app-mock/`, instala dependencias, crea `.env`, genera la key, crea
la base SQLite, corre migraciones + seed y **ejecuta la suite completa** (Flows
A, B, C y Chime).

Para el detalle de cada flujo, el E2E y la integración real con Stripe sandbox:
**[qa-case/RUN.md](qa-case/RUN.md)**.

## Qué se prueba

| Flujo | Qué cubre | Archivo |
|-------|-----------|---------|
| **A — Login** | registro, login válido/ inválido, recurso protegido sin token, rate-limit | `app-mock/tests/Feature/Flujo_A_AuthTest.php` |
| **B — Pago (Stripe)** | pago exitoso, tarjeta rechazada, webhook actualiza suscripción, firma inválida | `app-mock/tests/Feature/Flujo_B_PaymentTest.php` |
| **C — Agendamiento** | reservar slot, doble-booking rechazado, cancelar libera slot | `app-mock/tests/Feature/Flujo_C_AppointmentTest.php` |
| **Video — Chime** | crea meeting+attendee (mock AWS), manejo de error | `app-mock/tests/Feature/ChimeVideoTest.php` |
| **E2E — Login** | flujo en navegador real (Playwright) | `qa-case/e2e/tests/login.spec.ts` |

## Estructura

```
qa-case-opcionyo/
├── README.md                # este archivo — orientación general
├── scripts/                 # setup con un comando (sh / ps1), corre sobre app-mock
├── .github/workflows/       # pipeline de CI (bloquea el merge si algo falla)
│
├── app-mock/                # LA APP MOCK (Laravel de ejemplo)
│   ├── app/ · routes/ · database/ · config/ · resources/ · public/
│   ├── artisan · composer.json · phpunit.xml · .env.example
│   └── tests/               # Flows A, B, C + Chime + Unit (php artisan test)
│                            #   tests/Chime/DEVICE_MATRIX.md
│
└── qa-case/                 # EL CASO QA (entregable)
    ├── plan/                # plan de QA (1 página)
    ├── bugs/                # 3 edge cases documentados
    ├── e2e/                 # Playwright (E2E de login)
    ├── INSTALL.md           # cómo instalar todo, paso a paso
    ├── RUN.md               # cómo correr cada cosa
    ├── DEMO.md              # guion para presentar el caso
    └── PROCESS.md           # herramientas usadas y por qué (incluye uso de IA)
```

## CI

`.github/workflows/ci.yml` corre en cada PR y push a `main` (vive en la raíz
porque GitHub sólo ejecuta workflows ahí):

- **job `tests`** — suite de API + Chime, corre en `app-mock/` (obligatorio).
- **job `e2e`** — Playwright en `qa-case/e2e/` contra un server real levantado
  desde `app-mock/`.

Para que **bloquee el merge**, marcar estos checks como *required* en
**Settings → Branches → Branch protection rules** del repo.
