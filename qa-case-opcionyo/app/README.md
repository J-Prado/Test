# OpcionYo — System Under Test (SUT)

A self-contained reference backend that stands in for the real OpcionYo stack
(Laravel · Vue · Flutter · MySQL · Stripe · AWS Chime) so the QA suite can run
**for real** with zero external services or secrets. It implements exactly the
endpoints the three required flows plus the Chime video flow exercise.

> Per the case brief, this is intentionally a mock of the endpoints — not the
> real system. Node/Express was chosen over Laravel purely for portability: it
> boots with one command and runs in CI with no PHP/MySQL/Docker.

## Run

```bash
cd app
npm install
npm start            # http://localhost:4000
```

Health check: `GET /health` → `{ "status": "ok" }`

Data lives in an in-memory store (`src/db.js`) that reseeds on every boot, so
each test run starts from a known, isolated state.

## Endpoints

### Flow A — Login (`src/auth.js`)
| Method | Path | Notes |
|---|---|---|
| POST | `/api/auth/register` | `{ email, password }` → 201; 409 if taken; 422 invalid |
| POST | `/api/auth/login` | valid → `{ token }`; invalid → 401 |
| GET | `/api/me` | protected; 401 without/!invalid Bearer token |

### Flow B — Stripe payment (`src/payments.js`)
| Method | Path | Notes |
|---|---|---|
| POST | `/api/payments/subscribe` | `{ userId, paymentMethod, plan }` |
| POST | `/api/webhooks/stripe` | event → updates subscription status in DB |
| GET | `/api/subscriptions/:userId` | read-back for assertions |

Test instruments: approved → `pm_card_visa` / `tok_visa` / `4242424242424242`
(201, status `incomplete`); declined → `pm_card_chargeDeclined` /
`4000000000000002` (402). The `invoice.payment_succeeded` webhook flips the
subscription to `active` — mirroring Stripe's async confirmation.

Sandbox-ready: set `STRIPE_WEBHOOK_SECRET` (+ install the optional `stripe`
dep) and webhooks are verified with the real SDK against the raw request body.

### Flow C — Scheduling (`src/scheduling.js`)
| Method | Path | Notes |
|---|---|---|
| GET | `/api/specialists` | seeded specialists |
| GET | `/api/slots?specialistId=` | availability |
| POST | `/api/appointments` | auth; book available slot; 409 if taken |
| DELETE | `/api/appointments/:id` | auth; cancel → frees the slot |

### Video — AWS Chime (`src/chime.js`)
| Method | Path | Notes |
|---|---|---|
| POST | `/api/video/meetings` | auth; `{ appointmentId }` → `CreateMeeting`/`CreateAttendee` shape |

Returns a deterministic stub by default. Set `USE_REAL_CHIME=1` with AWS creds
(+ optional `@aws-sdk/client-chime-sdk-meetings`) to hit real Chime.

## Environment variables (all optional)
| Var | Default | Purpose |
|---|---|---|
| `PORT` | `4000` | HTTP port |
| `JWT_SECRET` | dev secret | token signing |
| `STRIPE_SECRET_KEY` | — | enables real Stripe SDK |
| `STRIPE_WEBHOOK_SECRET` | — | verifies webhook signatures |
| `AWS_REGION` | `us-east-1` | Chime region |
| `USE_REAL_CHIME` | — | `1` to use the real AWS SDK |

## For the test author
`createApp()` is exported from `src/server.js`, so tests can mount the app
in-process (e.g. supertest) without binding a port.
