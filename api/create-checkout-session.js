import Stripe from 'stripe';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);

// Seuls ces sites ont le droit d'appeler cette API (sécurité)
const ALLOWED_ORIGINS = [
  'https://kayliclinn.fr',
  'https://www.kayliclinn.fr',
  'http://localhost:3000', // pour d'éventuels tests en local
];

function setCors(req, res) {
  const origin = req.headers.origin;
  if (ALLOWED_ORIGINS.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
  }
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

export default async function handler(req, res) {
  setCors(req, res);

  // Réponse aux requêtes "preflight" du navigateur
  if (req.method === 'OPTIONS') return res.status(200).end();

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const {
      amount,    // montant à payer en euros (ex: 285)
      mode,      // "acompte" ou "total"
      contact = {},
      service = {},
      date,
      slot,
      totalTTC,  // prix total de la prestation (pour calculer le solde)
    } = req.body || {};

    // ─── Validations ───
    if (!amount || Number(amount) < 1) {
      return res.status(400).json({ error: 'Montant invalide' });
    }
    if (!contact.email || !contact.firstname) {
      return res.status(400).json({ error: 'Coordonnées incomplètes' });
    }

    const amountCents = Math.round(Number(amount) * 100);
    const isAcompte = mode === 'acompte';
    const balanceDue = totalTTC
      ? Math.max(0, Number(totalTTC) - Number(amount))
      : 0;
    const siteUrl = process.env.SITE_URL || 'https://kayliclinn.fr';

    const session = await stripe.checkout.sessions.create({
      mode: 'payment',
      payment_method_types: ['card'],
      customer_email: contact.email,
      line_items: [
        {
          quantity: 1,
          price_data: {
            currency: 'eur',
            unit_amount: amountCents,
            product_data: {
              name: isAcompte
                ? 'Acompte 30% — Prestation de ménage Kayli Clinn'
                : 'Prestation de ménage Kayli Clinn',
              description:
                [
                  service.type ? `Type : ${service.type}` : null,
                  service.surface ? `Surface : ${service.surface} m²` : null,
                  date ? `Date : ${date}${slot ? ' à ' + slot : ''}` : null,
                ]
                  .filter(Boolean)
                  .join(' · ') || undefined,
            },
          },
        },
      ],
      // Les metadata sont transmises au webhook après le paiement
      metadata: {
        mode: mode || 'total',
        amount: String(amount),
        total_ttc: String(totalTTC || amount),
        balance_due: String(balanceDue),
        client_firstname: contact.firstname || '',
        client_lastname: contact.lastname || '',
        client_email: contact.email || '',
        client_phone: contact.phone || '',
        client_address: [contact.address, contact.zip, contact.city]
          .filter(Boolean)
          .join(', '),
        service_type: service.type || '',
        service_surface: String(service.surface || ''),
        intervention_date: date || '',
        intervention_slot: slot || '',
      },
      success_url: `${siteUrl}/confirmation?session_id={CHECKOUT_SESSION_ID}`,
      cancel_url: `${siteUrl}/reservation?paiement=annule`,
    });

    return res.status(200).json({ url: session.url });
  } catch (err) {
    console.error('Erreur create-checkout-session:', err);
    return res
      .status(500)
      .json({ error: 'Erreur lors de la création du paiement' });
  }
}
