'use strict';

/**
 * Flow C — Scheduling.
 *   GET    /api/specialists            list specialists
 *   GET    /api/slots?specialistId=    list slots (availability)
 *   POST   /api/appointments           book an available slot (auth)
 *   DELETE /api/appointments/:id       cancel -> frees the slot (auth)
 */

const express = require('express');
const db = require('./db');
const { requireAuth } = require('./auth');

const router = express.Router();

router.get('/specialists', (req, res) => {
  res.json({ specialists: db.listSpecialists() });
});

router.get('/slots', (req, res) => {
  const slots = db.listSlots(req.query.specialistId);
  res.json({ slots });
});

// Book a slot. Concurrency-safe against double booking because the store is
// single-threaded: we re-check status inside the same synchronous tick.
router.post('/appointments', requireAuth, (req, res) => {
  const { slotId } = req.body || {};
  const slot = db.findSlot(slotId);

  if (!slot) {
    return res.status(404).json({ error: 'slot_not_found' });
  }
  if (slot.status !== 'available') {
    return res.status(409).json({ error: 'slot_taken' });
  }

  const appt = db.createAppointment({ slotId, patientId: req.user.id });
  slot.status = 'booked';
  slot.appointmentId = appt.id;

  return res.status(201).json({ appointment: appt, slot });
});

router.delete('/appointments/:id', requireAuth, (req, res) => {
  const appt = db.findAppointment(req.params.id);

  if (!appt || appt.status === 'canceled') {
    return res.status(404).json({ error: 'appointment_not_found' });
  }
  if (appt.patientId !== req.user.id) {
    return res.status(403).json({ error: 'not_owner' });
  }

  appt.status = 'canceled';

  // Free the slot so it can be booked again.
  const slot = db.findSlot(appt.slotId);
  if (slot) {
    slot.status = 'available';
    slot.appointmentId = null;
  }

  return res.json({ appointment: appt, slot });
});

module.exports = { router };
