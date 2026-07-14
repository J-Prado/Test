'use strict';

/**
 * Flow A — Login.
 *   POST /api/auth/register   email + password
 *   POST /api/auth/login      valid / invalid credentials
 *   GET  /api/me              protected resource (401 without token)
 */

const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('./db');

const JWT_SECRET = process.env.JWT_SECRET || 'dev-secret-not-for-production';
const TOKEN_TTL = '1h';

const router = express.Router();

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function publicUser(user) {
  return { id: user.id, email: user.email, createdAt: user.createdAt };
}

router.post('/auth/register', async (req, res) => {
  const { email, password } = req.body || {};

  if (!email || !EMAIL_RE.test(email)) {
    return res.status(422).json({ error: 'invalid_email' });
  }
  if (!password || String(password).length < 8) {
    return res.status(422).json({ error: 'weak_password', message: 'min 8 chars' });
  }
  if (db.findUserByEmail(email)) {
    return res.status(409).json({ error: 'email_taken' });
  }

  const passwordHash = await bcrypt.hash(String(password), 10);
  const user = db.createUser({ email, passwordHash });
  return res.status(201).json({ user: publicUser(user) });
});

router.post('/auth/login', async (req, res) => {
  const { email, password } = req.body || {};
  const user = db.findUserByEmail(email || '');

  // Same response for unknown user and wrong password (no user enumeration).
  const ok = user && (await bcrypt.compare(String(password || ''), user.passwordHash));
  if (!ok) {
    return res.status(401).json({ error: 'invalid_credentials' });
  }

  const token = jwt.sign({ sub: user.id, email: user.email }, JWT_SECRET, {
    expiresIn: TOKEN_TTL,
  });
  return res.json({ token, user: publicUser(user) });
});

// Middleware: requires a valid Bearer token.
function requireAuth(req, res, next) {
  const header = req.headers.authorization || '';
  const [scheme, token] = header.split(' ');

  if (scheme !== 'Bearer' || !token) {
    return res.status(401).json({ error: 'missing_token' });
  }
  try {
    const payload = jwt.verify(token, JWT_SECRET);
    req.user = { id: payload.sub, email: payload.email };
    return next();
  } catch (err) {
    return res.status(401).json({ error: 'invalid_token' });
  }
}

// Protected resource.
router.get('/me', requireAuth, (req, res) => {
  const user = db.findUserById(req.user.id);
  if (!user) return res.status(404).json({ error: 'not_found' });
  return res.json({ user: publicUser(user) });
});

module.exports = { router, requireAuth };
