# PROCESS — Qué herramientas usé y por qué

Este documento cuenta el *cómo* y el *por qué* detrás del entregable, incluido el
uso de IA. No hay una respuesta correcta; estas son mis decisiones y sus razones.

## Enfoque general

Traté el caso como lo trataría en el trabajo real: primero **estrategia y
riesgo**, después código. Con ~11.000 sesiones/mes y cero tests, prioricé el
"camino de dinero" (Auth → Pago → Agenda) y traté las videollamadas como un
problema aparte porque no se pueden testear con hardware real en CI. El detalle
está en `plan/qa-plan.md`.

Levanté una app Laravel de ejemplo, mínima pero **real**, para tener contra qué
correr los tests (el caso explícitamente permite esto). No es el objetivo en sí:
es el andamiaje para que los tests corran de verdad.

## Decisiones y por qué

| Decisión | Alternativas que descarté | Por qué esta |
|----------|---------------------------|--------------|
| **Laravel + Pest** para la suite principal | Postman/Newman, Cypress-para-todo | Es el stack real de Opción Yo; Pest corre contra la app con DB en memoria → tests rápidos, aislados y cercanos a producción. |
| **SQLite in-memory** en tests | MySQL en Docker para todo | Cero dependencias, arranque instantáneo, misma capa de migraciones. Para bugs de concurrencia real, ese test puntual se corre contra MySQL. |
| **Mock del SDK de Stripe** + 1 test opt-in al sandbox | Pegarle siempre al sandbox | Determinismo y velocidad en CI sin secretos por PR; el sandbox real queda cubierto por un test que se activa con una `sk_test_`. |
| **Webhook firmado a mano** en el test | Depender de `stripe listen` en CI | Verifica la validación de firma sin red ni herramientas externas; reproducible en cualquier máquina. |
| **AWS SDK MockHandler** para Chime | Skipear Chime; o intentar medios reales | Permite testear la lógica de orquestación (meeting/attendee/tokens/errores) sin AWS ni hardware. Los medios reales van a una matriz de dispositivos. |
| **Playwright (Chromium)** para E2E | Selenium, Cypress | Rápido, multiplataforma, soporta *fake media devices* (clave para Chime a futuro). Los mismos specs apuntarían al Vue real. |
| **Blade mínimo** como frontend del E2E | Montar un SPA Vue completo | El caso valora estrategia y que corra, no un SPA. Blade da un flujo de navegador real y autocontenido; lo dejé documentado como stand-in del Vue real. |
| **GitHub Actions** con 2 jobs | Un solo job monolítico | Separar API/Chime del E2E da feedback más claro y permite marcar el core como *required* para bloquear el merge. |

## Cómo decido que algo está "listo para prod"

Resumido (detalle en el plan): CI verde, camino feliz **y** de error cubiertos,
sin regresiones en los 3 flujos críticos, concurrencia/idempotencia consideradas,
y —para cambios de video— matriz de dispositivos P0/P1 verificada. Regla base:
**no confío en un flujo hasta que existe un test que falla cuando lo rompo.**

## Uso de IA

Usé **Claude Code (Anthropic)** como asistente durante el armado. Concretamente:

- **Para acelerar:** scaffolding del proyecto Laravel, boilerplate de modelos/
  migraciones/controladores, y el primer borrador de los archivos de tests y de
  documentación.
- **Lo que puse yo (y revisé/decidí a mano):** la **estrategia** de testing y la
  priorización por riesgo, la separación lógica-vs-medios en Chime, la **matriz
  de dispositivos**, la elección de qué mockear vs. qué integrar de verdad, y los
  edge cases de `bugs/`.
- **Verificación:** revisé cada archivo generado; la instrucción fue seguir
  convenciones estándar de Laravel 11 / Pest 3 / Playwright. La primera corrida
  real de la suite se hace en la máquina destino siguiendo `RUN.md`.

Mi criterio: la IA es una herramienta de productividad para el andamiaje; el
juicio de QA (qué probar, qué priorizar, qué significa "listo") es la parte que
aporta valor y esa la tomé yo.

## Qué haría con más tiempo

- Test de concurrencia real de doble-booking contra MySQL (dos conexiones).
- Deduplicación de webhooks por `event.id` + su test (ver `bugs/BUG-002`).
- E2E contra el frontend Vue real con *fake media devices* para estados de
  permisos de cámara/mic de Chime.
- Reporte de cobertura y *mutation testing* (Infection) para medir calidad de los
  tests, no sólo cantidad.
- Datos de prueba con factories más ricas y *seeders* por escenario.
