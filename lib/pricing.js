// ════════════════════════════════════════════════════════════
// GRILLE TARIFAIRE OFFICIELLE — Kayli Clinn
// Source de vérité unique, issue de « Kayli Clinn tarification.xlsx ».
// Le serveur recalcule TOUJOURS les prix à partir de cette grille :
// aucun montant envoyé par le navigateur n'est accepté tel quel.
// Pour changer un tarif : modifier ici PUIS reporter dans la page
// estimation (constante KC_TARIFS) pour l'affichage.
// ════════════════════════════════════════════════════════════

// ─── Forfaits logement (prix fixes TTC, réservables en ligne) ───
// Feuille « Forfaits logement » : Studio ≤25 m² / 2 pièces / 3 pièces / 4 pièces
export const FORFAITS = {
  airbnb: {
    label: 'Nettoyage Airbnb / location courte durée',
    prix: { studio: 45, p2: 60, p3: 75, p4: 95 },
  },
  demenagement: {
    label: 'Nettoyage après déménagement / fin de bail / état des lieux',
    prix: { studio: 79, p2: 99, p3: 119, p4: 149 },
  },
  standard: {
    label: 'Nettoyage appartement standard (ponctuel complet)',
    prix: { studio: 79, p2: 99, p3: 119, p4: 149 },
  },
  'logement-vide': {
    label: 'Nettoyage logement vide',
    prix: { studio: 79, p2: 99, p3: 119, p4: 149 },
  },
};

export const TAILLES = {
  studio: 'Studio ≤ 25 m²',
  p2: '2 pièces',
  p3: '3 pièces',
  p4: '4 pièces',
};

// ─── Options à montant fixe (feuille « Options et majorations ») ───
export const OPTIONS = {
  'linge-petit':  { label: 'Gestion du linge — petit volume',  prix: 15 },
  'linge-moyen':  { label: 'Gestion du linge — volume moyen',  prix: 25 },
  'linge-gros':   { label: 'Gestion du linge — gros volume',   prix: 40 },
  frigo:          { label: 'Nettoyage intérieur frigo',        prix: 10 },
  four:           { label: 'Nettoyage intérieur four',         prix: 15 },
  'petit-balcon': { label: 'Petit balcon',                     prix: 10 },
  terrasse:       { label: 'Terrasse ou grand balcon',         prix: 30 },
};

// ─── Majorations en % appliquées sur (base + options) ───
// « Logement très sale » (+30 à +50 %) n'est PAS réservable en ligne :
// validation par photos ou audit obligatoire → parcours audit.
export const MAJORATIONS = {
  urgence:          { label: 'Intervention urgente (< 48 h)', taux: 0.20 },
  'dimanche-ferie': { label: 'Dimanche ou jour férié',        taux: 0.25 },
};

export const ACOMPTE_PCT = 30; // Acompte 30 % sur tous les forfaits réservables en ligne

// ─── Prestations professionnelles « estimables en ligne » (HT) ───
// Feuille « Tarifs horaires & récurrents ». Fourchettes indicatives :
// le devis ferme est remis après audit gratuit sur place.
export const TARIFS_PRO = {
  bureaux:           { label: 'Nettoyage bureaux',                tauxMin: 32, tauxMax: 38, unite: '€ HT/h' },
  'bureaux-idf':     { label: 'Bureaux Paris / IDF (contraintes)', tauxMin: 38, tauxMax: 45, unite: '€ HT/h' },
  locaux:            { label: 'Nettoyage régulier de locaux',     tauxMin: 32, tauxMax: 60, unite: '€ HT/h' },
  commerce:          { label: 'Nettoyage commerce',               tauxMin: 32, tauxMax: 60, unite: '€ HT/h' },
  copropriete:       { label: 'Copropriété / parties communes',   tauxMin: 32, tauxMax: 60, unite: '€ HT/h' },
  'vitres-ponctuel': { label: 'Vitres intérieures — ponctuel',    tauxMin: 3,  tauxMax: 7,  unite: '€/m² vitré' },
  vitrine:           { label: 'Nettoyage vitrine commerce',       tauxMin: 3,  tauxMax: 7,  unite: '€/m²' },
};

// Minimum de déplacement par passage (bureaux / locaux) : 45–65 € HT
export const MINIMUM_PASSAGE = { min: 45, max: 65 };

// Forfaits mensuels « à partir de » (HT)
export const FORFAITS_MENSUELS_PRO = {
  'vitres-recurrent': { label: 'Vitres intérieures — récurrent', prix: 79, unite: '€ HT/mois' },
  tisanerie:          { label: 'Tisanerie / kitchenette',        prix: 89, unite: '€ HT/mois' },
  consommables:       { label: 'Gestion des consommables',       prix: 39, unite: '€ HT/mois' },
};

// Rendement standard utilisé pour convertir une surface en heures
// (entretien courant ≈ 150 m²/h ; ajustable après retours terrain).
export const RENDEMENT_M2_PAR_HEURE = 150;
export const HEURES_MIN_PASSAGE = 1.5;

// ════════════════════════════════════════════════════════════
// CALCUL D'UN FORFAIT LOGEMENT (montants exacts, réservables)
// Lève une Error si la combinaison n'existe pas dans la grille.
// ════════════════════════════════════════════════════════════
export function computeForfait({ forfait, taille, options = [], majorations = [] }) {
  const f = FORFAITS[forfait];
  if (!f) throw new Error(`Forfait inconnu : ${forfait}`);
  const base = f.prix[taille];
  if (!base) throw new Error(`Taille inconnue : ${taille}`);

  const lignes = [{ label: `${f.label} — ${TAILLES[taille]}`, montant: base }];

  let totalOptions = 0;
  const linge = options.filter((o) => String(o).startsWith('linge-'));
  if (linge.length > 1) throw new Error('Une seule option linge possible');
  for (const key of options) {
    const opt = OPTIONS[key];
    if (!opt) throw new Error(`Option inconnue : ${key}`);
    totalOptions += opt.prix;
    lignes.push({ label: opt.label, montant: opt.prix });
  }

  const sousTotal = base + totalOptions;
  let tauxMajoration = 0;
  for (const key of majorations) {
    const maj = MAJORATIONS[key];
    if (!maj) throw new Error(`Majoration inconnue : ${key}`);
    tauxMajoration += maj.taux;
    lignes.push({
      label: `${maj.label} (+${Math.round(maj.taux * 100)} %)`,
      montant: Math.round(sousTotal * maj.taux * 100) / 100,
    });
  }

  // Total en centimes pour éviter les flottants (ex. 45 × 1,2 = 54)
  const totalCents = Math.round(sousTotal * (1 + tauxMajoration) * 100);
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

// ════════════════════════════════════════════════════════════
// ESTIMATION PRO (fourchette indicative HT — jamais facturée telle
// quelle : le devis ferme suit l'audit gratuit).
// ════════════════════════════════════════════════════════════
export function estimateProRange({ presta, surface, passagesParSemaine = 1 }) {
  const t = TARIFS_PRO[presta];
  if (!t) throw new Error(`Prestation pro inconnue : ${presta}`);
  const s = Math.min(Math.max(Number(surface) || 0, 10), 10000);

  // Tarification au m² (vitres / vitrines) : prix = surface × taux
  if (t.unite.includes('m²')) {
    return {
      label: t.label,
      min: Math.round(s * t.tauxMin),
      max: Math.round(s * t.tauxMax),
      unite: '€ HT',
      detail: `${s} m² × ${t.tauxMin}–${t.tauxMax} ${t.unite}`,
    };
  }

  // Tarification horaire : heures estimées par passage × taux × passages/mois
  const heures = Math.max(HEURES_MIN_PASSAGE, s / RENDEMENT_M2_PAR_HEURE);
  const passages = Math.min(Math.max(Number(passagesParSemaine) || 1, 1), 7);
  const parPassageMin = Math.max(MINIMUM_PASSAGE.min, Math.round(heures * t.tauxMin));
  const parPassageMax = Math.max(MINIMUM_PASSAGE.max, Math.round(heures * t.tauxMax));
  const parMoisMin = Math.round(parPassageMin * passages * 4.33);
  const parMoisMax = Math.round(parPassageMax * passages * 4.33);

  return {
    label: t.label,
    min: parMoisMin,
    max: parMoisMax,
    unite: '€ HT/mois',
    parPassage: { min: parPassageMin, max: parPassageMax },
    heuresParPassage: Math.round(heures * 10) / 10,
    detail: `≈ ${Math.round(heures * 10) / 10} h/passage × ${t.tauxMin}–${t.tauxMax} €/h × ${passages}/sem.`,
  };
}
