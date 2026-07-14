# Plan de QA — Opción Yo (1 página)

## Por dónde arranco y por qué

Arranco por **lo que más duele y más plata mueve**: pagos y videollamadas.
Con ~11.000 sesiones/mes y cero pruebas, el mayor riesgo no es un botón torcido:
es **cobrar mal** o que **la videollamada no conecte**. Por eso el orden es:

1. **Red de seguridad mínima primero** — tests de API para Auth, Pago y
   Agendamiento (rápidos, deterministas, corren en CI sin cuentas externas).
   Estos tres flujos son el "camino de dinero" del producto.
2. **Chime (video)** — el flujo más problemático en producción. No se puede
   testear con hardware real en CI, así que separo *lógica* (mockeable, va a CI)
   de *medios/dispositivos* (matriz manual + device farm antes de release).
3. **E2E de login** en navegador real (Playwright) para cubrir la capa que los
   tests de API no ven (formularios, sesión, redirecciones).

La estrategia es una **pirámide de tests**: mucha base de tests de integración
de API (baratos y estables), una capa fina de E2E en los caminos críticos, y
pruebas manuales/exploratorias sólo donde el costo de automatizar no se paga
(medios reales de Chime).

## Herramientas por capa y por qué esas

| Capa | Herramienta | Por qué |
|------|-------------|---------|
| API / integración (Auth, Pago, Agenda) | **Pest sobre PHPUnit** (Laravel) | Es el runner nativo del stack; los tests corren contra la app real con DB en memoria (SQLite `:memory:`) → rápidos y aislados. |
| Base de datos en test | **SQLite in-memory + RefreshDatabase** | Cero dependencias externas; cada test parte de una DB limpia. En prod es MySQL; el mismo esquema/migraciones aplican. |
| Stripe | **SDK mockeado** + **1 test opt-in contra sandbox** | El grueso corre sin red (determinista, sin secretos en CI). El test de sandbox verifica la integración real cuando hay una `sk_test_`. Webhooks se prueban **firmando el payload** igual que Stripe. |
| Chime | **AWS SDK MockHandler** | Permite probar creación de meeting/attendee, tokens y manejo de errores sin AWS ni hardware. |
| E2E navegador | **Playwright (Chromium)** | Multiplataforma, rápido, soporta *fake media devices* para simular cámara/mic; los mismos specs apuntarían al frontend Vue real. |
| CI | **GitHub Actions** | Corre la suite en cada PR y **bloquea el merge** si algo falla (branch protection). |

*(En el sistema real el frontend es Vue; acá uso páginas Blade mínimas como
stand-in para que el E2E tenga un flujo de navegador real sin empaquetar un SPA.)*

## Cómo sé que algo está listo para producción

Un cambio va a prod sólo si cumple **todo** esto (mi "Definition of Done"):

- ✅ **CI verde**: suite de API + Chime + E2E de login pasan en el PR.
- ✅ **Cubre camino feliz y de error**: p. ej. tarjeta OK *y* rechazada; slot libre *y* ocupado; con token *y* sin token.
- ✅ **Sin regresiones** en los 3 flujos críticos (Auth, Pago, Agenda).
- ✅ **Idempotencia y concurrencia** consideradas donde aplica (webhooks duplicados, doble-booking del mismo slot).
- ✅ Para cambios que tocan **video**: matriz de dispositivos P0/P1 verificada en device farm.
- ✅ Cada bug encontrado queda **documentado y con un test que lo reproduce** antes de darse por resuelto.

> Principio guía: *no confío en que un flujo funciona hasta que existe un test
> que falla cuando lo rompo.*
