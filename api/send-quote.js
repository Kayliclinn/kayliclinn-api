import { computeForfait } from '../lib/pricing.js';
import { setCors, escapeHtml, validateContact, rateLimit, sendEmail, emailFrom } from '../lib/helpers.js';

// ════════════════════════════════════════════════════════════
// POST /api/send-quote
// Envoie le devis estimatif par email (client + copie admin).
// Remplace l'ancien envoi via WordPress admin-ajax qui simulait un
// succès quand le module PHP n'était pas installé.
//
// Deux cas :
//  • kind = "forfait" : prix EXACTS recalculés serveur (grille officielle)
//  • kind = "estimation" : fourchette indicative pro, transmise pour
//    information (le devis ferme suit l'audit gratuit)
//  • kind = "audit" : demande de rappel / devis sans prix
//
// Corps : { kind, booking?, estimation?, prestation, contact, website }
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
    const { kind, booking, estimation, prestation, contact = {}, website } = req.body || {};

    // Honeypot anti-spam
    if (website) return res.status(200).json({ ok: true });

    const contactErrors = validateContact(contact);
    if (contactErrors.length) {
      return res.status(400).json({ error: contactErrors.join(' · ') });
    }

    const e = (v) => escapeHtml(String(v == null ? '' : v).slice(0, 500));
    const euro = (n) => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    let titre = e(prestation || 'Votre demande');
    let prixHtml = '';

    if (kind === 'forfait' && booking) {
      // Prix exacts : recalcul serveur sur la grille officielle
      let devis;
      try {
        devis = computeForfait({
          forfait: booking.forfait,
          taille: booking.taille,
          options: Array.isArray(booking.options) ? booking.options : [],
          majorations: Array.isArray(booking.majorations) ? booking.majorations : [],
        });
      } catch (err) {
        return res.status(400).json({ error: `Demande invalide : ${err.message}` });
      }
      titre = e(devis.label);
      prixHtml = `
        <h3>Votre tarif</h3>
        <ul>
          ${devis.lignes.map((l) => `<li>${e(l.label)} : ${euro(l.montant)} €</li>`).join('')}
        </ul>
        <p><strong>Total : ${euro(devis.total)} € TTC</strong><br>
        Acompte à la réservation (30 %) : ${euro(devis.acompte)} €<br>
        Solde après la prestation : ${euro(devis.solde)} €</p>
        <p>Ce tarif est ferme : vous pouvez réserver votre créneau en ligne
        sur <a href="https://kayliclinn.fr/reservation/">kayliclinn.fr/reservation</a>.</p>
      `;
    } else if (kind === 'estimation' && estimation) {
      const min = Math.max(0, Math.min(Number(estimation.min) || 0, 1_000_000));
      const max = Math.max(min, Math.min(Number(estimation.max) || 0, 1_000_000));
      prixHtml = `
        <h3>Votre estimation indicative</h3>
        <p><strong>${euro(min)} – ${euro(max)} ${e(estimation.unite || '€ HT')}</strong></p>
        ${estimation.detail ? `<p>${e(estimation.detail)}</p>` : ''}
        <p>Cette estimation est indicative : le devis ferme vous est remis après
        une visite d'audit gratuite et sans engagement.</p>
      `;
    } else {
      prixHtml = `
        <p>Votre demande nécessite une visite d'audit gratuite pour établir un
        devis ferme. Nous vous recontactons sous 24 h ouvrées.</p>
      `;
    }

    const detailsHtml = `
      <h3>Récapitulatif de la demande</h3>
      <ul>
        <li>Prestation : ${titre}</li>
        ${booking?.taille ? `<li>Taille : ${e(booking.taille)}</li>` : ''}
        ${estimation?.surface ? `<li>Surface : ${e(estimation.surface)} m²</li>` : ''}
        ${estimation?.frequence ? `<li>Fréquence : ${e(estimation.frequence)}</li>` : ''}
      </ul>
      <h3>Vos coordonnées</h3>
      <ul>
        <li>Nom : ${e(contact.firstname)} ${e(contact.lastname)}</li>
        <li>Email : ${e(contact.email)}</li>
        <li>Téléphone : ${e(contact.phone)}</li>
        <li>Adresse : ${e(contact.address) || 'Non renseignée'}</li>
        ${contact.message ? `<li>Précisions : ${e(contact.message)}</li>` : ''}
      </ul>
    `;

    const adminEmail = process.env.NOTIFICATION_EMAIL || 'contact@kayliclinn.fr';

    // Copie admin (lead entrant)
    await sendEmail({
      from: emailFrom(),
      to: adminEmail,
      reply_to: String(contact.email || '').slice(0, 80),
      subject: `Demande de devis — ${String(contact.firstname).slice(0, 40)} ${String(contact.lastname).slice(0, 40)} — ${titre}`,
      html: `<h2>Nouvelle demande de devis depuis le site</h2>${prixHtml}${detailsHtml}`,
    });

    // Devis au client
    await sendEmail({
      from: emailFrom(),
      to: String(contact.email).slice(0, 80),
      subject: `Votre estimation Kayli Clinn — ${titre}`,
      html: `
        <h2>Merci pour votre demande !</h2>
        <p>Bonjour ${e(contact.firstname)},</p>
        <p>Voici le récapitulatif de votre demande pour <strong>${titre}</strong>.</p>
        ${prixHtml}
        ${detailsHtml}
        <p>Une question ? Répondez à cet email ou appelez-nous au
        <a href="tel:+33670012061">06 70 01 20 61</a>.</p>
        <p>À très bientôt,<br>L'équipe Kayli Clinn</p>
      `,
    });

    return res.status(200).json({ ok: true });
  } catch (err) {
    console.error('Erreur send-quote:', err);
    return res.status(500).json({ error: "Impossible d'envoyer le devis pour le moment" });
  }
}
