import Stripe from 'stripe';
import { computeForfait, ACOMPTE_PCT } from '../lib/pricing.js';
import { setCors, validateContact, validateDateSlot, rateLimit } from '../lib/helpers.js';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);

// ════════════════════════════════════════════════════════════
// POST /api/create-checkout-session
// Crée une session de paiement Stripe pour un forfait logement.
//
// SÉCURITÉ : le navigateur n'envoie JAMAIS de montant. Il décrit la
// réservation (forfait, taille, options, majorations) et le serveur
// recalcule le prix à partir de la grille officielle (lib/pricing.js).
//
// Corps attendu :
// {
//   booking: { forfait, taille, options: [], majorations: [] },
//   mode: "acompte" | "total",
//   contact: { firstname, lastname, email, phone, address, message? },
//   date: "YYYY-MM-DD", slot: "HH:MM"
// }
// ════════════════════════════════════════════════════════════
export default async function handler(req, res) {
  setCors(req, res);

  // Réponse aux requêtes "preflight" du navigateur
  if (req.method === 'OPTIONS') return res.status(200).end();

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  if (!rateLimit(req, { max: 8, windowMs: 60_000 })) {
    return res.status(429).json({ error: 'Trop de tentatives, réessayez dans une minute.' });
  }

  try {
    const { booking = {}, mode, contact = {}, date, slot } = req.body || {};

    // ─── Validations ───
    const contactErrors = validateContact(contact);
    if (contactErrors.length) {
      return res.status(400).json({ error: contactErrors.join(' · ') });
    }
    const dateError = validateDateSlot(date, slot);
    if (dateError) {
      return res.status(400).json({ error: dateError });
    }

    // ─── Prix : recalculé côté serveur, jamais reçu du client ───
    let devis;
    try {
      devis = computeForfait({
        forfait: booking.forfait,
        taille: booking.taille,
        options: Array.isArray(booking.options) ? booking.options : [],
        majorations: Array.isArray(booking.majorations) ? booking.majorations : [],
      });
    } catch (e) {
      return res.status(400).json({ error: `Réservation invalide : ${e.message}` });
    }

    const isAcompte = mode !== 'total'; // acompte 30 % par défaut
    const amountCents = isAcompte ? devis.acompteCents : devis.totalCents;
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
                ? `Acompte ${ACOMPTE_PCT} % — ${devis.label}`
                : devis.label,
              description: [
                devis.taille,
                date ? `Le ${date}${slot ? ' à ' + slot : ''}` : null,
              ].filter(Boolean).join(' · '),
            },
          },
        },
      ],
      // Les metadata sont transmises au webhook après le paiement
      metadata: {
        mode: isAcompte ? 'acompte' : 'total',
        amount: String(amountCents / 100),
        total_ttc: String(devis.total),
        balance_due: String(isAcompte ? devis.soldeCents / 100 : 0),
        forfait: String(booking.forfait),
        client_firstname: String(contact.firstname || '').slice(0, 40),
        client_lastname: String(contact.lastname || '').slice(0, 40),
        client_email: String(contact.email || '').slice(0, 80),
        client_phone: String(contact.phone || '').slice(0, 20),
        client_address: String(contact.address || '').slice(0, 200),
        service_type: devis.label,
        service_taille: devis.taille,
        service_options: devis.lignes.slice(1).map((l) => l.label).join(' · ').slice(0, 480) || 'Aucune',
        intervention_date: String(date),
        intervention_slot: String(slot),
        client_message: String(contact.message || '').slice(0, 480),
      },
      success_url: `${siteUrl}/reservation/?paiement=succes&session_id={CHECKOUT_SESSION_ID}`,
      cancel_url: `${siteUrl}/reservation/?paiement=annule`,
    });

    return res.status(200).json({
      url: session.url,
      // Renvoyé à titre informatif : le front affiche ce que le serveur a calculé
      devis: {
        total: devis.total,
        acompte: devis.acompte,
        solde: devis.solde,
        montantRegle: amountCents / 100,
        lignes: devis.lignes,
      },
    });
  } catch (err) {
    console.error('Erreur create-checkout-session:', err);
    return res
      .status(500)
      .json({ error: 'Erreur lors de la création du paiement' });
  }
}
