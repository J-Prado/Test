# RUN — Cómo correr todo

Asumí que ya seguiste **[INSTALL.md](INSTALL.md)** (tenés PHP + Composer, y Node
si vas a correr el E2E).

Todos los comandos se corren **desde la raíz** `qa-case-opcionyo/`.

---

## 0. Setup con un solo comando (recomendado la primera vez)

**Windows (PowerShell):**
```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup.ps1
```
**macOS / Linux:**
```bash
bash scripts/setup.sh
```

Esto instala dependencias, crea `.env`, genera la key, crea la base SQLite, corre
migraciones + seed y ejecuta la suite completa. Si termina en verde, listo.

---

## 1. Setup manual (si preferís paso a paso)

```bash
composer install                # instala Laravel y dependencias
cp .env.example .env            # (Windows: copy .env.example .env)
php artisan key:generate

# base de datos SQLite
#   macOS/Linux:
touch database/database.sqlite
#   Windows PowerShell:
#   New-Item -ItemType File database\database.sqlite

php artisan migrate --seed
```

---

## 2. Correr la suite de tests (Flows A, B, C + Chime)

```bash
php artisan test                       # toda la suite
php artisan test --exclude-group=integration   # sin el test opt-in de Stripe (lo que corre CI)
```

Correr un flujo puntual:

```bash
php artisan test tests/Feature/Flujo_A_AuthTest.php          # Login
php artisan test tests/Feature/Flujo_B_PaymentTest.php       # Pagos
php artisan test tests/Feature/Flujo_C_AppointmentTest.php   # Agenda
php artisan test tests/Feature/ChimeVideoTest.php            # Video (Chime, mock)
```

Filtrar por nombre:
```bash
php artisan test --filter="declined"
```

---

## 3. E2E con Playwright (flujo de login en navegador real)

Necesita Node. El server lo arranca Playwright solo, pero la base tiene que estar
migrada (el paso 0/1 ya lo hace).

```bash
cd e2e
npm install
npx playwright install --with-deps chromium   # sólo la primera vez
npx playwright test                            # corre el E2E
npx playwright test --headed                   # con navegador visible (para demo)
npx playwright show-report                     # ver el reporte HTML
```

> Playwright levanta `php artisan serve` en `http://127.0.0.1:8000`. Si ya tenés
> un server corriendo, lo reutiliza.

---

## 4. Probar contra el sandbox REAL de Stripe (opcional)

La suite corre con mocks por defecto. Para verificar la integración real:

1. Poné tu clave de test en `.env`:
   ```
   STRIPE_SECRET=sk_test_tu_clave_real
   ```
2. Corré sólo el test de integración:
   ```bash
   php artisan test --group=stripe
   ```
   (Si `STRIPE_SECRET` sigue siendo el dummy, el test se **skipea** solo.)

### Reenviar webhooks reales a tu máquina (opcional)

```bash
stripe login
stripe listen --forward-to 127.0.0.1:8000/api/stripe/webhook
# copiá el whsec_... que imprime a STRIPE_WEBHOOK_SECRET en .env
# en otra terminal:
stripe trigger customer.subscription.updated
```

---

## 5. Levantar la app para explorar a mano (opcional)

```bash
php artisan serve
# abrí http://127.0.0.1:8000  -> /login , /register , /dashboard
```

Rutas de API disponibles: `POST /api/register`, `POST /api/login`,
`GET /api/user` (requiere token), `POST /api/pay`, `POST /api/appointments`,
`DELETE /api/appointments/{id}`, `POST /api/stripe/webhook`.

---

## 6. Simular el pipeline de CI localmente

```bash
php artisan test --exclude-group=integration     # lo que corre el job `tests`
cd e2e && npx playwright test                     # lo que corre el job `e2e`
```

---

## Problemas comunes

| Síntoma | Arreglo |
|--------|---------|
| `Database file ... does not exist` | Creá `database/database.sqlite` (paso 1) o corré el script de setup. |
| `No application encryption key` | Corré `php artisan key:generate`. |
| Tests de Stripe "skipped" | Es lo esperado sin una `sk_test_` real; la suite igual pasa. |
| Playwright: `Executable doesn't exist` | Corré `npx playwright install --with-deps chromium`. |
| `Class ... not found` tras editar | `composer dump-autoload`. |
