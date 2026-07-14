'use strict';

/**
 * OpcionYo QA case — system under test.
 *
 * A single Express process exposing the endpoints the QA suite exercises:
 *   Flow A  auth        /api/auth/*, /api/me
 *   Flow B  payments    /api/payments/*, /api/webhooks/stripe
 *   Flow C  scheduling  /api/specialists, /api/slots, /api/appointments
 *   Video   chime       /api/video/meetings
 */

const express = require('express');

const auth = require('./auth');
const payments = require('./payments');
const scheduling = require('./scheduling');
const chime = require('./chime');

function createApp() {
  const app = express();

  app.get('/health', (req, res) => res.json({ status: 'ok' }));

  // Stripe webhook needs the raw body for signature verification, so mount it
  // BEFORE the JSON parser.
  app.post(
    '/api/webhooks/stripe',
    express.raw({ type: '*/*' }),
    payments.webhookHandler
  );

  // Everything else is JSON.
  app.use(express.json());

  app.use('/api', auth.router);
  app.use('/api', payments.router);
  app.use('/api', scheduling.router);
  app.use('/api', chime.router);

  // Fallbacks.
  app.use((req, res) => res.status(404).json({ error: 'not_found' }));
  // eslint-disable-next-line no-unused-vars
  app.use((err, req, res, next) => {
    res.status(500).json({ error: 'internal_error', message: err.message });
  });

  return app;
}

// Exported so tests can mount the app in-process (e.g. supertest) without a port.
module.exports = { createApp };

// Start a server only when run directly.
if (require.main === module) {
  const port = process.env.PORT || 4000;
  createApp().listen(port, () => {
    // eslint-disable-next-line no-console
    console.log(`OpcionYo SUT listening on http://localhost:${port}`);
  });
}
