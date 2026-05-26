import Stripe from 'stripe';

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
  const apiKey = process.env.RESEND_API_KEY;
  if (!apiKey) {
    console.log('RESEND_API_KEY non configurée — emails non envoyés pour le moment.');
    return;
  }

  const m = session.metadata || {};
  const adminEmail = process.env.NOTIFICATION_EMAIL || 'contact@kayliclinn.fr';
  const isAcompte = m.mode === 'acompte';

  const detailsHtml = `
    <h3>Intervention</h3>
    <ul>
      <li>Date : ${m.intervention_date || 'N/A'} ${m.intervention_slot ? 'à ' + m.intervention_slot : ''}</li>
      <li>Type : ${m.service_type || 'N/A'} ${m.service_surface ? '· ' + m.service_surface + ' m²' : ''}</li>
      <li>Adresse : ${m.client_address || 'Non renseignée'}</li>
    </ul>
    <h3>Client</h3>
    <ul>
      <li>Nom : ${m.client_firstname || ''} ${m.client_lastname || ''}</li>
      <li>Email : ${m.client_email || ''}</li>
      <li>Téléphone : ${m.client_phone || ''}</li>
    </ul>
    <h3>Paiement</h3>
    <ul>
      <li>Mode : ${isAcompte ? 'Acompte 30%' : 'Paiement total'}</li>
      <li>Versé : ${m.amount || ''} €</li>
      <li>Total TTC : ${m.total_ttc || ''} €</li>
      <li>Solde dû après intervention : ${m.balance_due || '0'} €</li>
      <li>Référence Stripe : ${session.id}</li>
    </ul>
  `;

  // Email de notification à Kayli Clinn
  await sendEmail(apiKey, {
    from: 'Kayli Clinn <onboarding@resend.dev>',
    to: adminEmail,
    subject: `Nouvelle réservation — ${m.client_firstname} ${m.client_lastname}`,
    html: `<h2>Nouvelle réservation payée</h2>${detailsHtml}`,
  });

  // Email de confirmation au client
  if (m.client_email) {
    await sendEmail(apiKey, {
      from: 'Kayli Clinn <onboarding@resend.dev>',
      to: m.client_email,
      subject: 'Confirmation de votre réservation — Kayli Clinn',
      html: `
        <h2>Merci pour votre réservation !</h2>
        <p>Bonjour ${m.client_firstname},</p>
        <p>Nous confirmons la réception de votre paiement de ${isAcompte ? "un acompte de" : "la totalité de"} ${m.amount} €.</p>
        ${detailsHtml}
        <p>À très bientôt,<br>L'équipe Kayli Clinn</p>
      `,
    });
  }
}

async function sendEmail(apiKey, body) {
  const response = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${apiKey}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  });
  if (!response.ok) {
    const err = await response.text();
    console.error('Erreur Resend:', err);
  }
}
