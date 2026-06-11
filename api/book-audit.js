import { setCors, escapeHtml, validateContact, validateDateSlot, rateLimit, sendEmail, emailFrom } from '../lib/helpers.js';

// ════════════════════════════════════════════════════════════
// POST /api/book-audit
// Réserve une VISITE D'AUDIT GRATUITE (prestations sur devis :
// maison, grands logements, fin de chantier, sinistre, vitres en
// hauteur, etc. — feuille « Devis & audit » de la grille tarifaire).
// Aucun paiement : confirmation par email à l'admin et au client.
//
// Corps attendu :
// {
//   prestation: "libellé de la prestation",
//   details: { typeBien?, surface?, description? },
//   contact: { firstname, lastname, email, phone, address, message? },
//   date: "YYYY-MM-DD", slot: "HH:MM",
//   website: ""   ← honeypot anti-spam, doit rester vide
// }
// ════════════════════════════════════════════════════════════
export default async function handler(req, res) {
  setCors(req, res);
  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  if (!rateLimit(req, { max: 5, windowMs: 60_000 })) {
    return res.status(429).json({ error: 'Trop de tentatives, réessayez dans une minute.' });
  }

  try {
    const { prestation, details = {}, contact = {}, date, slot, website } = req.body || {};

    // Honeypot : un humain ne remplit jamais ce champ
    if (website) return res.status(200).json({ ok: true });

    const contactErrors = validateContact(contact);
    if (contactErrors.length) {
      return res.status(400).json({ error: contactErrors.join(' · ') });
    }
    const dateError = validateDateSlot(date, slot);
    if (dateError) return res.status(400).json({ error: dateError });
    if (!prestation || String(prestation).length > 120) {
      return res.status(400).json({ error: 'Prestation invalide' });
    }

    const adminEmail = process.env.NOTIFICATION_EMAIL || 'contact@kayliclinn.fr';
    const e = (v) => escapeHtml(String(v == null ? '' : v).slice(0, 500));

    const recapHtml = `
      <h3>Visite d'audit demandée</h3>
      <ul>
        <li>Prestation : ${e(prestation)}</li>
        <li>Date : ${e(date)} à ${e(slot)}</li>
        ${details.typeBien ? `<li>Type de bien : ${e(details.typeBien)}</li>` : ''}
        ${details.surface ? `<li>Surface indiquée : ${e(details.surface)} m²</li>` : ''}
        ${details.description ? `<li>Description : ${e(details.description)}</li>` : ''}
      </ul>
      <h3>Client</h3>
      <ul>
        <li>Nom : ${e(contact.firstname)} ${e(contact.lastname)}</li>
        <li>Email : ${e(contact.email)}</li>
        <li>Téléphone : ${e(contact.phone)}</li>
        <li>Adresse : ${e(contact.address) || 'Non renseignée'}</li>
        ${contact.message ? `<li>Message : ${e(contact.message)}</li>` : ''}
      </ul>
    `;

    // Notification à l'admin
    await sendEmail({
      from: emailFrom(),
      to: adminEmail,
      reply_to: String(contact.email || '').slice(0, 80),
      subject: `Visite d'audit — ${String(contact.firstname).slice(0, 40)} ${String(contact.lastname).slice(0, 40)} — ${date} ${slot}`,
      html: `<h2>Nouvelle demande de visite d'audit gratuite</h2>${recapHtml}
        <p>Pensez à confirmer le rendez-vous au client par téléphone ou email.</p>`,
    });

    // Confirmation au client
    await sendEmail({
      from: emailFrom(),
      to: String(contact.email).slice(0, 80),
      subject: 'Votre visite gratuite est enregistrée — Kayli Clinn',
      html: `
        <h2>Votre demande de visite est bien enregistrée</h2>
        <p>Bonjour ${e(contact.firstname)},</p>
        <p>Nous avons bien reçu votre demande de <strong>visite d'audit gratuite</strong>
        le <strong>${e(date)} à ${e(slot)}</strong>. Nous vous confirmons ce créneau
        très rapidement par téléphone ou par email.</p>
        ${recapHtml}
        <p>La visite est gratuite et sans engagement : elle nous permet de vous
        remettre un devis ferme et précis.</p>
        <p>Pour toute question : <a href="tel:+33670012061">06 70 01 20 61</a></p>
        <p>À très bientôt,<br>L'équipe Kayli Clinn</p>
      `,
    });

    return res.status(200).json({ ok: true });
  } catch (err) {
    console.error('Erreur book-audit:', err);
    return res.status(500).json({ error: "Impossible d'enregistrer la visite pour le moment" });
  }
}
