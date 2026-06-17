// ════════════════════════════════════════════════════════════
// Utilitaires partagés des endpoints — Kayli Clinn API
// ════════════════════════════════════════════════════════════

// ─── CORS : seuls ces sites peuvent appeler l'API depuis un navigateur ───
export const ALLOWED_ORIGINS = [
  'https://kayliclinn.fr',
  'https://www.kayliclinn.fr',
  'http://localhost:3000', // tests en local
];

export function setCors(req, res) {
  const origin = req.headers.origin;
  if (ALLOWED_ORIGINS.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
  }
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

// ─── Échappement HTML : toute donnée client insérée dans un email
// DOIT passer par ici (anti-injection HTML / hameçonnage) ───
export function escapeHtml(value) {
  return String(value == null ? '' : value).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

// ─── Validations serveur ───
const RE_EMAIL = /^[a-zA-Z0-9](?:[a-zA-Z0-9._%+\-]{0,62}[a-zA-Z0-9])?@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.){1,3}[a-zA-Z]{2,24}$/;
const RE_NOM = /^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s\-'.]{0,39}$/;
const RE_TEL = /^(?:\+33|0033|0)\s?[1-9](?:[\s.\-]?\d{2}){4}$/;
const RE_DATE = /^\d{4}-\d{2}-\d{2}$/;
const RE_HEURE = /^([01]\d|2[0-3]):[0-5]\d$/;

export function validateContact(contact) {
  const c = contact || {};
  const errors = [];
  if (!RE_NOM.test(String(c.firstname || ''))) errors.push('Prénom invalide');
  if (!RE_NOM.test(String(c.lastname || ''))) errors.push('Nom invalide');
  if (!RE_EMAIL.test(String(c.email || '')) || String(c.email).length > 80) errors.push('Email invalide');
  if (!RE_TEL.test(String(c.phone || ''))) errors.push('Téléphone invalide');
  if (c.address != null && String(c.address).length > 200) errors.push('Adresse trop longue');
  if (c.message != null && String(c.message).length > 1000) errors.push('Message trop long');
  return errors;
}

// Date au format YYYY-MM-DD, ni passée, ni à plus d'un an
export function validateDateSlot(date, slot) {
  if (!RE_DATE.test(String(date || ''))) return 'Date invalide';
  if (!RE_HEURE.test(String(slot || ''))) return 'Créneau invalide';
  const d = new Date(`${date}T${slot}:00+02:00`);
  if (Number.isNaN(d.getTime())) return 'Date invalide';
  const now = Date.now();
  if (d.getTime() < now - 2 * 3600 * 1000) return 'Cette date est déjà passée';
  if (d.getTime() > now + 366 * 86400 * 1000) return 'Date trop lointaine';
  return null;
}

// ─── Limitation de débit (en mémoire, par instance serverless).
// Best effort : suffisant contre les scripts naïfs, pas contre un
// botnet — documenté dans CLAUDE.md. ───
const rlBuckets = new Map();
export function rateLimit(req, { max = 8, windowMs = 60_000 } = {}) {
  const ip = String(req.headers['x-forwarded-for'] || req.socket?.remoteAddress || 'inconnu')
    .split(',')[0].trim();
  const now = Date.now();
  const bucket = rlBuckets.get(ip) || [];
  const recent = bucket.filter((t) => now - t < windowMs);
  recent.push(now);
  rlBuckets.set(ip, recent);
  if (rlBuckets.size > 5000) rlBuckets.clear(); // garde-fou mémoire
  return recent.length <= max;
}

// ─── Envoi d'email via l'API Resend ───
// EMAIL_FROM doit être un expéditeur vérifié dans Resend
// (ex. « Kayli Clinn <contact@kayliclinn.fr> » après vérification du domaine).
export function emailFrom() {
  return process.env.EMAIL_FROM || 'Kayli Clinn <onboarding@resend.dev>';
}

export async function sendEmail(body) {
  const apiKey = process.env.RESEND_API_KEY;
  if (!apiKey) {
    console.log('RESEND_API_KEY non configurée — email non envoyé :', body.subject);
    return false;
  }
  const response = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${apiKey}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  });
  if (!response.ok) {
    console.error('Erreur Resend:', await response.text());
    return false;
  }
  return true;
}
