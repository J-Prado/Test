'use strict';

/**
 * In-memory data store standing in for MySQL.
 *
 * The real OpcionYo stack uses MySQL. For the QA case we need something that
 * boots with a single command and gives each CI run a clean, isolated state,
 * so persistence is kept in-process. Everything the app touches goes through
 * this module, so swapping in a real driver later is a localized change.
 */

let store;

function seed() {
  store = {
    users: new Map(), // id -> { id, email, passwordHash, createdAt }
    usersByEmail: new Map(), // email -> id
    subscriptions: new Map(), // id -> { id, userId, plan, status, stripeCustomerId }
    specialists: new Map(), // id -> { id, name, specialty }
    slots: new Map(), // id -> { id, specialistId, startsAt, status, appointmentId }
    appointments: new Map(), // id -> { id, slotId, patientId, status }
    meetings: new Map(), // id -> Chime meeting payload
    seq: 0,
  };

  // Seed specialists + availability so Flow C has something to book against.
  const drAna = createSpecialist('Dra. Ana Ríos', 'Psicología clínica');
  const drLuis = createSpecialist('Dr. Luis Peña', 'Nutrición');

  createSlot(drAna.id, '2026-08-01T15:00:00Z');
  createSlot(drAna.id, '2026-08-01T16:00:00Z');
  createSlot(drLuis.id, '2026-08-02T10:00:00Z');
}

function nextId(prefix) {
  store.seq += 1;
  return `${prefix}_${store.seq}`;
}

// --- users ---------------------------------------------------------------
function createUser({ email, passwordHash }) {
  const id = nextId('usr');
  const user = { id, email, passwordHash, createdAt: new Date().toISOString() };
  store.users.set(id, user);
  store.usersByEmail.set(email.toLowerCase(), id);
  return user;
}

function findUserByEmail(email) {
  const id = store.usersByEmail.get(String(email).toLowerCase());
  return id ? store.users.get(id) : undefined;
}

function findUserById(id) {
  return store.users.get(id);
}

// --- subscriptions -------------------------------------------------------
function upsertSubscription(sub) {
  const id = sub.id || nextId('sub');
  const existing = store.subscriptions.get(id) || {};
  const merged = { ...existing, ...sub, id };
  store.subscriptions.set(id, merged);
  return merged;
}

function findSubscriptionByUser(userId) {
  for (const sub of store.subscriptions.values()) {
    if (sub.userId === userId) return sub;
  }
  return undefined;
}

function findSubscriptionByCustomer(stripeCustomerId) {
  for (const sub of store.subscriptions.values()) {
    if (sub.stripeCustomerId === stripeCustomerId) return sub;
  }
  return undefined;
}

// --- specialists & slots -------------------------------------------------
function createSpecialist(name, specialty) {
  const id = nextId('spc');
  const specialist = { id, name, specialty };
  store.specialists.set(id, specialist);
  return specialist;
}

function listSpecialists() {
  return [...store.specialists.values()];
}

function createSlot(specialistId, startsAt) {
  const id = nextId('slot');
  const slot = { id, specialistId, startsAt, status: 'available', appointmentId: null };
  store.slots.set(id, slot);
  return slot;
}

function findSlot(id) {
  return store.slots.get(id);
}

function listSlots(specialistId) {
  return [...store.slots.values()].filter(
    (s) => !specialistId || s.specialistId === specialistId
  );
}

// --- appointments --------------------------------------------------------
function createAppointment({ slotId, patientId }) {
  const id = nextId('appt');
  const appt = { id, slotId, patientId, status: 'booked' };
  store.appointments.set(id, appt);
  return appt;
}

function findAppointment(id) {
  return store.appointments.get(id);
}

module.exports = {
  seed,
  nextId,
  createUser,
  findUserByEmail,
  findUserById,
  upsertSubscription,
  findSubscriptionByUser,
  findSubscriptionByCustomer,
  createSpecialist,
  listSpecialists,
  createSlot,
  findSlot,
  listSlots,
  createAppointment,
  findAppointment,
  get store() {
    return store;
  },
};

// Boot with a fresh dataset on load.
seed();
