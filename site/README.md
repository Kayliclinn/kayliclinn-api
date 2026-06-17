# Pages du site kayliclinn.fr — guide de mise en place

Ces fichiers sont des blocs « HTML personnalisé » à coller dans WordPress
(un fichier = le contenu complet de la page, hors en-tête/pied de page du
thème). Tout le tunnel passe par les extensions WordPress (`kc-booking`,
`kc-devis`, `kc-sheet-sync`) — aucune dépendance à Vercel.

| Fichier | Page WordPress | Contenu |
| --- | --- | --- |
| `accueil.html` | Page d'accueil | Hero, services (prix grille officielle), réalisations, avis (badge « Exemple »), FAQ |
| `prestation.html` | Gabarit prestation (ex. Turnover Airbnb) | 14 sections éditoriales, tarifs grille officielle |
| `estimation.html` | `/devis/` | Tunnel d'estimation 3 parcours (forfait / pro / audit) |
| `reservation.html` | `/reservation/` | Calendrier kc-booking + paiement Stripe (forfaits) ou visite gratuite (audits) |
| `tarification.xlsx` | — | Grille tarifaire source |

## Mise à jour d'une page

1. Ouvrir la page dans WordPress → bloc « HTML personnalisé ».
2. Remplacer **tout** le contenu du bloc par le contenu du fichier.
3. Prévisualiser puis publier.

## Prérequis côté extensions (voir `../wordpress/INTEGRATION-kc-booking.md`)

1. **kc-booking** : valider `GET /types` (Phase 3.1), intégrer
   `GET /availability` (Phase 3.2 — implémentation de référence fournie),
   et surtout **brancher `KC_Pricing`** dans `POST /bookings` pour que les
   montants soient recalculés côté serveur (sécurité paiement).
2. **Type « Visite d'audit »** : installer l'extension `kc-visite-audit.php`
   (ZIP → activer) — elle crée automatiquement le type gratuit `visite-audit`
   qui sert de calendrier commun aux prestations sur mesure. Sans elle, la page
   se replie sur le type « remise-etat » existant (le calendrier marche quand
   même).
3. **kc-devis** : intégrer le handler durci fourni et injecter
   `window.kcDevis = { ajaxUrl, nonce }` sur la page `/devis/`.
   Sans lui, la page affiche un message honnête (plus de faux succès).

## Comportement hors ligne / hors prod

Sur un autre domaine que kayliclinn.fr (prévisualisation), les pages passent
en **mode démo** : bannière visible, données d'exemple, aucune requête réseau.
Sur kayliclinn.fr sans extension prête, la réservation affiche un état
d'erreur clair avec le téléphone en secours — jamais de fausse confirmation.

## À remplacer par tes vraies données (rappels)

- Lien d'avis Google (accueil) : pointe vers une recherche Google en
  attendant le vrai lien `g.page/r/…` (Fiche Google Business → Demander des avis).
- Photos avant/après « Nos réalisations » (accueil) : les 12 images pointent
  encore vers le même fichier (`Image6.webp`).
- Témoignages : badge « Exemple » obligatoire (DGCCRF) tant qu'ils ne sont
  pas remplacés par de vrais avis autorisés.

## Test de bout en bout (avant mise en ligne publique)

Voir la checklist complète dans `../wordpress/INTEGRATION-kc-booking.md`
(section 6) : parcours payant Stripe test + parcours visite gratuite +
test anti-fraude (montant forcé → le serveur doit encaisser le prix grille).
