import Stripe from 'stripe';
import { escapeHtml, sendEmail, emailFrom } from '../lib/helpers.js';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);
const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET;

// IMPORTANT : on désactive le parsing automatique du corps de la requête.
// Stripe a besoin du corps "brut" (non modifié) pour vérifier la signature.
export const config = {
  api: {
    bodyParser: false,
  },
};

// Petit utilitaire qui lit le corps brut de la requête
async function buffer(readable) {
  const chunks = [];
  for await (const chunk of readable) {
    chunks.push(typeof chunk === 'string' ? Buffer.from(chunk) : chunk);
  }
  return Buffer.concat(chunks);
}

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const sig = req.headers['stripe-signature'];
  let event;

  // ─── Vérification que l'événement vient bien de Stripe ───
  try {
    const rawBody = await buffer(req);
    event = stripe.webhooks.constructEvent(rawBody, sig, webhookSecret);
  } catch (err) {
    console.error('Échec de vérification du webhook:', err.message);
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  // ─── Traitement de l'événement ───
  try {
    switch (event.type) {
      case 'checkout.session.completed': {
        const session = event.data.object;
        await sendConfirmationEmails(session);
        break;
      }
      case 'checkout.session.expired':
        console.log('Session de paiement expirée:', event.data.object.id);
        break;
      case 'payment_intent.payment_failed':
        console.log('Paiement échoué:', event.data.object.id);
        break;
      default:
        // Les autres événements sont ignorés
        break;
    }
  } catch (err) {
    console.error("Erreur de traitement de l'événement:", err);
    // On renvoie quand même 200 pour éviter que Stripe ne réessaie en boucle
  }

  return res.status(200).json({ received: true });
}

async function sendConfirmationEmails(session) {
  const m = session.metadata || {};
  const adminEmail = process.env.NOTIFICATION_EMAIL || 'contact@kayliclinn.fr';
  const isAcompte = m.mode === 'acompte';

  // SÉCURITÉ : toutes les valeurs issues du client sont échappées avant
  // insertion dans le HTML (anti-injection / hameçonnage par les champs).
  const e = (v) => escapeHtml(v);

  const detailsHtml = `
    <h3>Intervention</h3>
    <ul>
      <li>Date : ${e(m.intervention_date) || 'N/A'} ${m.intervention_slot ? 'à ' + e(m.intervention_slot) : ''}</li>
      <li>Prestation : ${e(m.service_type) || 'N/A'} ${m.service_taille ? '· ' + e(m.service_taille) : ''}</li>
      <li>Options : ${e(m.service_options) || 'Aucune'}</li>
      <li>Adresse : ${e(m.client_address) || 'Non renseignée'}</li>
    </ul>
    <h3>Client</h3>
    <ul>
      <li>Nom : ${e(m.client_firstname)} ${e(m.client_lastname)}</li>
      <li>Email : ${e(m.client_email)}</li>
      <li>Téléphone : ${e(m.client_phone)}</li>
      ${m.client_message ? `<li>Message : ${e(m.client_message)}</li>` : ''}
    </ul>
    <h3>Paiement</h3>
    <ul>
      <li>Mode : ${isAcompte ? 'Acompte 30 %' : 'Paiement total'}</li>
      <li>Versé : ${e(m.amount)} €</li>
      <li>Total TTC : ${e(m.total_ttc)} €</li>
      <li>Solde dû après intervention : ${e(m.balance_due) || '0'} €</li>
      <li>Référence Stripe : ${e(session.id)}</li>
    </ul>
  `;

  // Email de notification à Kayli Clinn
  await sendEmail({
    from: emailFrom(),
    to: adminEmail,
    reply_to: String(m.client_email || '').slice(0, 80),
    subject: `Nouvelle réservation — ${String(m.client_firstname || '').slice(0, 40)} ${String(m.client_lastname || '').slice(0, 40)}`,
    html: `<h2>Nouvelle réservation payée</h2>${detailsHtml}`,
  });

  // Email de confirmation au client
  if (m.client_email) {
    await sendEmail({
      from: emailFrom(),
      to: m.client_email,
      subject: 'Confirmation de votre réservation — Kayli Clinn',
      html: `
        <h2>Merci pour votre réservation !</h2>
        <p>Bonjour ${e(m.client_firstname)},</p>
        <p>Nous confirmons la réception de votre paiement de ${isAcompte ? 'un acompte de' : 'la totalité de'} ${e(m.amount)} €.</p>
        ${detailsHtml}
        <p>Pour annuler ou décaler votre rendez-vous, appelez-nous au
        <a href="tel:+33670012061">06 70 01 20 61</a>.</p>
        <p>À très bientôt,<br>L'équipe Kayli Clinn</p>
      `,
    });
  }
}
