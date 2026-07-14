# OpciónYo — Aplicación de referencia (Laravel 11 + Vue 3)

Plataforma de bienestar que conecta pacientes con especialistas por videollamada.
Es el **sistema bajo prueba (SUT)** del caso técnico de QA: implementa los flujos
de Login, Pago (Stripe), Agendamiento y Videollamada (AWS Chime) sobre el stack
del documento — **Laravel (PHP) · Vue.js · MySQL · Stripe · AWS Chime**.

> ⚠️ Esta app fue escrita a mano en un entorno **sin PHP**, por lo que no pudo
> ejecutarse/verificarse aquí. El código sigue las convenciones estándar de
> Laravel 11 y arranca con los pasos de abajo en cualquier máquina con PHP 8.2+.

## Requisitos

- PHP **8.2+** con extensiones `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `json`
- **Composer** 2
- **MySQL** 8 (o MariaDB 10.4+)
- **Node.js** 18+ y npm

## Puesta en marcha (una vez)

```bash
# 1. Dependencias
composer install
npm install

# 2. Entorno
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# 3. Base de datos MySQL — crea el schema y ajusta credenciales en .env
#    (DB_DATABASE=opcionyo, DB_USERNAME, DB_PASSWORD)
mysql -u root -p -e "CREATE DATABASE opcionyo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migraciones + datos de ejemplo (especialistas, horarios, paciente demo)
php artisan migrate --seed
```

## Correr la app (dos procesos)

```bash
# Terminal 1 — API + servidor web de Laravel
php artisan serve            # http://localhost:8000

# Terminal 2 — build/HMR del frontend Vue con Vite
npm run dev
```

Abre **http://localhost:8000**.

Usuario demo (creado por el seeder):
`paciente@opcionyo.test` / `password123`

Para producción/estático: `npm run build` genera los assets en `public/build`
y ya no hace falta `npm run dev`.

## Flujos implementados

| Flujo | Endpoints | Notas |
|---|---|---|
| **A · Login** | `POST /api/auth/register`, `POST /api/auth/login`, `GET /api/me` | Tokens con Laravel Sanctum. `/api/me` responde 401 sin token. |
| **B · Pago (Stripe)** | `POST /api/payments/subscribe`, `POST /api/webhooks/stripe`, `GET /api/subscriptions/{user}` | Tarjetas de prueba abajo. El webhook actualiza el estado en la BD. |
| **C · Agendamiento** | `GET /api/specialists`, `GET /api/slots`, `GET/POST /api/appointments`, `DELETE /api/appointments/{id}` | Reserva con bloqueo de fila (`lockForUpdate`) para evitar doble reserva → 409. Cancelar libera el slot. |
| **Video · Chime** | `POST /api/video/meetings` | Devuelve payload con forma `CreateMeeting`/`CreateAttendee`. |

### Stripe (modo sandbox)

Sin `STRIPE_SECRET` en `.env` la app corre en **modo fake**: reconoce las
tarjetas de prueba estándar de Stripe y confirma el pago automáticamente
(simulando el webhook). Con claves sandbox reales, usa el SDK de Stripe.

- ✅ Aprobada: `4242 4242 4242 4242` (o `pm_card_visa`)
- ⛔ Declinada: `4000 0000 0000 0002` (o `pm_card_chargeDeclined`) → HTTP 402

`STRIPE_AUTO_CONFIRM_MS=-1` desactiva la auto-confirmación para poder probar el
webhook de forma explícita (así lo hace el entorno de tests en `phpunit.xml`).

### AWS Chime

`CHIME_ENABLED=false` (default) devuelve un stub determinista con la misma forma
que la API real, para poder testear la lógica sin hardware ni credenciales.
Con `CHIME_ENABLED=true` + credenciales AWS, llama a Chime real.

## Estructura

```
app/
  Http/Controllers/   AuthController, PaymentController, SchedulingController,
                      StripeWebhookController, VideoController
  Models/             User, Specialist, Slot, Appointment, Subscription
  Services/           StripeGateway, ChimeGateway   (lógica sandbox/fake)
database/
  migrations/         users, specialists, slots, appointments, subscriptions…
  seeders/            DatabaseSeeder (especialistas + slots + paciente demo)
  factories/          para tus tests
resources/
  js/                 SPA Vue 3 (router + componentes por flujo)
  css/app.css         tema del producto
  views/app.blade.php shell del SPA
routes/
  api.php             endpoints de la API
  web.php             sirve el SPA
tests/                phpunit configurado (agrega aquí la suite de QA)
```

## Tests

```bash
php artisan test
```

`phpunit.xml` corre sobre SQLite en memoria, así los tests no tocan tu MySQL.
Hay un smoke test de ejemplo en `tests/Feature/SmokeTest.php`; los flujos A/B/C
y Chime son el objetivo del caso de QA.
