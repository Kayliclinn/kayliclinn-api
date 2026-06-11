// ════════════════════════════════════════════════════════════
// GRILLE TARIFAIRE OFFICIELLE v2 — Kayli Clinn (ARCHIVE LISIBLE)
// Source : « Kayli Clinn tarification 2 » (11/06/2026).
// ⚠️ Ce fichier n'est plus branché à rien (Vercel retiré du projet) :
// il sert de référence lisible. Les implémentations actives sont :
//  1. classe KC_Pricing dans plugins/kc-booking/kc-booking.php
//     et plugins/kc-devis/kc-devis.php (validation serveur),
//  2. constante KC_TARIFS dans site/estimation.html (affichage).
// total = (base + options) × (1 + majorations) + frais fixes.
// ════════════════════════════════════════════════════════════

// ─── Forfaits B2C TTC — prix fixe en ligne (typologie absente = devis) ───
export const FORFAITS = {
  airbnb: {
    label: 'Turnover Airbnb',
    prix: { studio: 55, t2: 75, t3: 95, t4: 120 }, // T5/maison/>90 m² : devis
  },
  'fin-de-bail': {
    label: 'Ménage fin de bail / déménagement (logement vide)',
    prix: { studio: 160, t2: 220, t3: 290, t4: 360, t5: 430 }, // >110 m²/maison : devis
    inclus: 'Électroménager + vitres intérieures + placards INCLUS',
  },
  'grand-menage': {
    label: 'Grand ménage (meublé occupé)',
    prix: { studio: 140, t2: 190, t3: 250, t4: 320 }, // T5+ : devis
  },
  vitrerie: {
    label: 'Vitrerie résidentielle simple (plain-pied / intérieur)',
    prix: { studio: 70, t2: 70, t3: 95, t4: 95, t5: 130 }, // hauteur/vitrine/verrière : devis
  },
};

export const TAILLES = { studio: 'Studio / T1', t2: 'T2', t3: 'T3', t4: 'T4', t5: 'T5' };

// ─── Options TTC ('fixe' = montant unique ; 'unite' = prix × quantité) ───
export const OPTIONS = {
  four:           { label: 'Four en profondeur',                prix: 35,  type: 'fixe',  compat: ['airbnb', 'grand-menage'] },
  frigo:          { label: 'Réfrigérateur / congélateur',       prix: 25,  type: 'fixe',  compat: ['airbnb', 'grand-menage'] },
  'vitres-int':   { label: 'Vitres intérieures',                prix: 20,  type: 'fixe',  compat: ['airbnb', 'grand-menage'] },
  placards:       { label: 'Intérieur des placards',            prix: 25,  type: 'fixe',  compat: ['grand-menage'] },
  consommables:   { label: 'Réassort consommables',             prix: 10,  type: 'fixe',  compat: ['airbnb'] },
  'cave-box':     { label: 'Cave / box',                        prix: 25,  type: 'fixe',  compat: ['fin-de-bail'] },
  'kit-linge':    { label: 'Kit linge complet (par lit)',       prix: 18,  type: 'unite', unite: 'lit', max: 10,  compat: ['airbnb'] },
  repassage:      { label: 'Repassage (par heure)',             prix: 35,  type: 'unite', unite: 'h',   max: 8,   compat: ['airbnb', 'grand-menage'] },
  balcon:         { label: 'Balcon / terrasse (par m²)',        prix: 2,   type: 'unite', unite: 'm²',  max: 150, minTotal: 20,  compat: ['fin-de-bail', 'grand-menage'] },
  moquette:       { label: 'Moquette injection-extraction (m²)', prix: 5,  type: 'unite', unite: 'm²',  max: 300, minTotal: 100, compat: ['fin-de-bail'] },
  'canape-2p':    { label: 'Canapé 2 places',                   prix: 80,  type: 'fixe',  compat: ['airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie'] },
  'canape-3p':    { label: 'Canapé 3 places',                   prix: 100, type: 'fixe',  compat: ['airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie'] },
  'canape-4p':    { label: 'Canapé 4 places',                   prix: 110, type: 'fixe',  compat: ['airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie'] },
  fauteuil:       { label: 'Fauteuil',                          prix: 50,  type: 'fixe',  compat: ['airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie'] },
  matelas:        { label: 'Matelas 2 places',                  prix: 75,  type: 'fixe',  compat: ['airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie'] },
};

// ─── Majorations en % de (base + options), cumulables ───
export const MAJORATIONS = {
  urgence:          { label: 'Urgence < 48 h',                               taux: 0.20, compat: [] },
  'dimanche-ferie': { label: 'Dimanche ou jour férié',                       taux: 0.25, compat: [] },
  'non-vide':       { label: 'Logement non vidé',                            taux: 0.15, compat: ['fin-de-bail'] },
  'tres-encrasse':  { label: 'Très encrassé / > 6 mois sans entretien',       taux: 0.30, compat: ['grand-menage'] },
};

// ─── Frais fixes (ajoutés après majorations) ───
export const FRAIS = { etage: { label: 'Étage 3 et + sans ascenseur', prix: 15 } };

export const ACOMPTE_PCT = 30;

// ─── Estimation indicative fin de chantier (HT, ± 15 %, min 300 € HT) ───
export const CHANTIER = {
  taux: { rafraichissement: 4.5, standard: 7, lourd: 11 }, // €/m² HT
  minimum: 300,
  marge: 0.15,
  mention: 'Prix confirmé après audit gratuit ou envoi de photos.',
};

// ─── Bureaux & locaux pro : budget indicatif seulement (jamais ferme) ───
export const BUREAUX = { tauxMin: 1.5, tauxMax: 3, position: 2, unite: '€/m²/mois HT' };

// ─── Garde-fous : bascule automatique vers devis ───
export const BASCULES = [
  'Surface > 110–120 m² (B2C) ou typologie maison',
  'État « très dégradé » (grand ménage)',
  'Vitres en hauteur, vitrine, verrière',
  'Toute demande B2B récurrente',
  'Mots-clés sensibles : sinistre, insalubre, décès, gravats',
];

// ════════════════════════════════════════════════════════════
// CALCUL D'UN FORFAIT (montants en centimes — référence)
// ════════════════════════════════════════════════════════════
export function computeForfait({ forfait, taille, options = {}, majorations = [], frais = [] }) {
  const f = FORFAITS[forfait];
  if (!f) throw new Error(`Forfait inconnu : ${forfait}`);
  const base = f.prix[taille];
  if (!base) throw new Error(`Typologie hors grille (devis requis) : ${taille}`);

  const lignes = [{ label: `${f.label} — ${TAILLES[taille]}`, montant: base }];

  let optionsCents = 0;
  for (const [key, qtyRaw] of Object.entries(options)) {
    const opt = OPTIONS[key];
    if (!opt) throw new Error(`Option inconnue : ${key}`);
    if (!opt.compat.includes(forfait)) throw new Error(`Option incompatible : ${key}`);
    const qty = opt.type === 'fixe' ? 1 : Math.trunc(Number(qtyRaw));
    const max = opt.max || 1;
    if (qty < 1 || qty > max) throw new Error(`Quantité invalide pour ${key}`);
    let cents = Math.round(opt.prix * qty * 100);
    if (opt.minTotal) cents = Math.max(cents, Math.round(opt.minTotal * 100));
    optionsCents += cents;
    lignes.push({ label: opt.label + (opt.type === 'unite' ? ` × ${qty} ${opt.unite}` : ''), montant: cents / 100 });
  }

  const sousTotalCents = Math.round(base * 100) + optionsCents;
  let taux = 0;
  for (const key of new Set(majorations)) {
    const m = MAJORATIONS[key];
    if (!m) throw new Error(`Majoration inconnue : ${key}`);
    if (m.compat.length && !m.compat.includes(forfait)) throw new Error(`Majoration incompatible : ${key}`);
    taux += m.taux;
    lignes.push({ label: `${m.label} (+${Math.round(m.taux * 100)} %)`, montant: Math.round(sousTotalCents * m.taux) / 100 });
  }
  let totalCents = Math.round(sousTotalCents * (1 + taux));

  for (const key of new Set(frais)) {
    const fr = FRAIS[key];
    if (!fr) throw new Error(`Frais inconnu : ${key}`);
    totalCents += Math.round(fr.prix * 100);
    lignes.push({ label: fr.label, montant: fr.prix });
  }

  const acompteCents = Math.round((totalCents * ACOMPTE_PCT) / 100);
  return {
    label: f.label,
    taille: TAILLES[taille],
    totalCents,
    acompteCents,
    soldeCents: totalCents - acompteCents,
    total: totalCents / 100,
    acompte: acompteCents / 100,
    solde: (totalCents - acompteCents) / 100,
    lignes,
  };
}
