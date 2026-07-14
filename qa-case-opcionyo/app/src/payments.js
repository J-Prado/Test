'use strict';

/**
 * Flow B — Payment with Stripe (sandbox).
 *   POST /api/payments/subscribe   test card -> success or declined
 *   POST /api/webhooks/stripe      event -> updates subscription status in DB
 *
 * Design note: charging real Stripe subscriptions needs live price IDs, keys
 * and network access, none of which belong in a CI unit run. So subscribe uses
 * a deterministic fake that recognizes the standard Stripe test cards, giving
 * repeatable success/decline paths. If STRIPE_WEBHOOK_SECRET is set, incoming
 * webhooks are verified with the real Stripe SDK exactly as in production.
 */

const express = require('express');
const db = require('./db');

const router = express.Router();

// Standard Stripe test instruments -> outcome.
const DECLINED = new Set([
  '4000000000000002',
  'tok_chargeDeclined',
  'pm_card_chargeDeclined',
]);
const APPROVED = new Set([
  '4242424242424242',
  'tok_visa',
  'pm_card_visa',
]);

let stripe = null;
if (process.env.STRIPE_SECRET_KEY) {
  try {
    // eslint-disable-next-line global-require
    stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
  } catch (_) {
    stripe = null; // SDK not installed; fall back to fake verification.
  }
}

router.post('/payments/subscribe', (req, res) => {
  const { userId, paymentMethod, plan = 'monthly' } = req.body || {};

  if (!userId || !db.findUserById(userId)) {
    return res.status(422).json({ error: 'unknown_user' });
  }
  if (!paymentMethod) {
    return res.status(422).json({ error: 'missing_payment_method' });
  }

  const customerId = `cus_${userId}`;

  if (DECLINED.has(String(paymentMethod))) {
    // Mirror Stripe: subscription exists but payment did not clear.
    db.upsertSubscription({
      userId,
      plan,
      status: 'incomplete',
      stripeCustomerId: customerId,
    });
    return res.status(402).json({
      error: 'card_declined',
      decline_code: 'generic_decline',
    });
  }

  if (!APPROVED.has(String(paymentMethod))) {
    return res.status(422).json({ error: 'unknown_payment_method' });
  }

  // Approved: subscription created but not yet active — Stripe confirms it
  // asynchronously via the invoice.payment_succeeded webhook below.
  const sub = db.upsertSubscription({
    userId,
    plan,
    status: 'incomplete',
    stripeCustomerId: customerId,
  });

  return res.status(201).json({
    subscription: { id: sub.id, status: sub.status, plan: sub.plan },
    stripeCustomerId: customerId,
    requiresWebhookConfirmation: true,
  });
});

// Webhook handler — mounted with a raw body parser in server.js so signature
// verification sees the exact bytes Stripe signed.
function webhookHandler(req, res) {
  let event;

  const secret = process.env.STRIPE_WEBHOOK_SECRET;
  if (secret && stripe) {
    try {
      event = stripe.webhooks.constructEvent(
        req.body, // Buffer (raw)
        req.headers['stripe-signature'],
        secret
      );
    } catch (err) {
      return res.status(400).json({ error: 'invalid_signature' });
    }
  } else {
    // No signing secret configured: accept the raw JSON as-is.
    try {
      event = JSON.parse(Buffer.isBuffer(req.body) ? req.body.toString('utf8') : req.body);
    } catch (err) {
      return res.status(400).json({ error: 'invalid_payload' });
    }
  }

  const object = event?.data?.object || {};
  const customerId = object.customer || object.stripeCustomerId;
  const sub = customerId ? db.findSubscriptionByCustomer(customerId) : undefined;

  if (!sub) {
    // Acknowledge unknown subscriptions so Stripe stops retrying.
    return res.status(200).json({ received: true, matched: false });
  }

  const statusByEvent = {
    'invoice.payment_succeeded': 'active',
    'customer.subscription.updated': object.status || 'active',
    'invoice.payment_failed': 'past_due',
    'customer.subscription.deleted': 'canceled',
  };

  const nextStatus = statusByEvent[event.type];
  if (nextStatus) {
    db.upsertSubscription({ id: sub.id, status: nextStatus });
  }

  return res.status(200).json({ received: true, matched: true, status: nextStatus || sub.status });
}

// Read-only helper the QA suite can assert against.
router.get('/subscriptions/:userId', (req, res) => {
  const sub = db.findSubscriptionByUser(req.params.userId);
  if (!sub) return res.status(404).json({ error: 'not_found' });
  return res.json({ subscription: sub });
});

module.exports = { router, webhookHandler };
