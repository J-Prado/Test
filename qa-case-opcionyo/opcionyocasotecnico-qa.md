# Opción **Yo** Caso Técnico — QA Engineer 

SSR / SSR-Avanzado Plazo de entrega: 3 días hábiles desde la recepción 

|**Stack**|**Infraestructura**|
|---|---|
|Laravel (PHP) · Vue.js · Flutter · MySQL|AWS · Stripe · AWS Chime|
|**Entrega**|**Carácter**|
|Repositorio Git con README|Confidencial — no compartir ni publicar|



## **El contexto** 

Somos una plataforma de bienestar que conecta pacientes con especialistas a través de videollamadas. Tenemos ~11,000 sesiones al mes. No tenemos ni una sola prueba automatizada. Tu trabajo es cambiar eso. 

_No vamos a darte acceso al código de OpcionYo. Esto es intencional — un buen QA sabe trabajar sin código base. Levanta un proyecto Laravel/Vue de ejemplo propio, usa uno público de GitHub, o crea mocks de los endpoints que necesitás. Lo que evaluamos es la estrategia, la estructura y que los tests corran — no que estén conectados a nuestro sistema real._ 

## **Lo que te pedimos** 

### **1. Plan (antes de escribir código)** 

En máximo una página, contanos: 

- Por dónde arrancas y por qué. 

- Qué herramientas usas para cada capa y por qué esas y no otras. 

- Cómo sabes que algo está listo para ir a producción. 

### **2. Suite de tests** 

Construye tests automatizados para estos 3 flujos. Tienen que correr de verdad. 

#### **Flujo A — Login** 

- Registro con email y contraseña. 

- Login con credenciales válidas e inválidas. 

- Acceso a recurso protegido sin token. 

#### **Flujo B — Pago con Stripe (usa el modo sandbox)** 

- Pago exitoso con tarjeta de prueba. 

- Pago con tarjeta declinada. 

- Webhook de Stripe actualiza el estado de la suscripción en la BD. 

#### **Flujo C — Agendamiento** 

- Paciente agenda sesión con especialista disponible. 

- Otro usuario intenta agendar el mismo slot ocupado. 

- Paciente cancela y el slot se libera. 

### **3. Videollamadas con AWS Chime** 

_Este es el flujo más problemático en producción. Un plan bien razonado con implementación parcial vale más que una implementación completa sin estrategia._ 

El desafío: Chime depende de periféricos físicos y de la red del usuario. No puedes correr tests reales en un pipeline de CI. Entonces: 

- ¿Cómo testearías la lógica de la app sin hardware real? 

- Diseña una matriz de combinaciones de dispositivo / SO / browser que cubran los casos más críticos. No necesitas correrlos — necesitamos ver que los pensaste. 

- Implementa al menos uno: el que más valor le daría a producción según tu criterio. 

### **4. Pipeline de CI** 

Un archivo de GitHub Actions que corra tus tests en cada PR y bloquee el merge si algo falla. 

### **5. Bugs o edge cases** 

3 casos que encontraste (o que creas que deberían testearse), documentados así: qué pasa, cómo reproducirlo, qué debería pasar, severidad. 

## **Formato de entrega** 

Entregá el link al repositorio por el mismo canal por donde recibiste este documento. 

Estructura esperada: 

```
qa-case-opcionyo/
├── README.md          # cómo correr todo con un solo comando
├── PROCESS.md         # qué herramientas usaste y por qué hiciste lo que hiciste
├── plan/              # el plan de QA
├── tests/             # flujos A, B, C y Chime
├── .github/workflows/ # pipeline de CI
└── bugs/              # edge cases documentados
```

_PROCESS.md es obligatorio. Cuentanos qué herramientas usaste (incluyendo AI si la usaste), qué decidiste y por qué. No hay respuesta correcta — queremos entender cómo piensas, no solo lo que entregaste._ 

_Cualquier duda, consultá por el mismo canal por donde recibiste este documento._ 

