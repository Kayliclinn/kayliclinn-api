# Pages du site kayliclinn.fr — guide de mise en place

Ces fichiers sont des blocs « HTML personnalisé » à coller dans WordPress
(un fichier = le contenu complet de la page, hors en-tête/pied de page du thème).

| Fichier | Page WordPress | Contenu |
| --- | --- | --- |
| `accueil.html` | Page d'accueil | Hero, services (prix grille officielle), réalisations, avis (badge « Exemple »), FAQ |
| `prestation.html` | Gabarit prestation (ex. Turnover Airbnb) | 14 sections éditoriales, tarifs grille officielle |
| `estimation.html` | `/devis/` | Tunnel d'estimation 3 parcours (forfait / pro / audit) |
| `reservation.html` | `/reservation/` | Calendrier + paiement Stripe (forfaits) ou visite gratuite (audits) |
| `tarification.xlsx` | — | Grille tarifaire source (reportée dans `lib/pricing.js`) |

## Mise à jour d'une page

1. Ouvrir la page dans WordPress → bloc « HTML personnalisé ».
2. Remplacer **tout** le contenu du bloc par le contenu du fichier.
3. Prévisualiser puis publier.

## Configuration requise avant le test de bout en bout

1. **URL de l'API** : les pages `estimation.html` et `reservation.html` appellent
   l'API Vercel. Par défaut : `https://kayliclinn-api.vercel.app`.
   Si votre projet Vercel a une autre URL (Vercel → Settings → Domains),
   remplacez-la dans les deux fichiers (constante en haut du `<script>`), ou
   définissez `window.kcApiBase = 'https://votre-url'` dans un bloc avant.
2. **Variables d'environnement Vercel** : `STRIPE_SECRET_KEY` (test d'abord),
   `STRIPE_WEBHOOK_SECRET`, `RESEND_API_KEY`, `EMAIL_FROM` (après vérification
   du domaine dans Resend), `NOTIFICATION_EMAIL`, `SITE_URL=https://kayliclinn.fr`.
3. **Webhook Stripe** : dashboard Stripe → Webhooks → endpoint
   `https://<url-api>/api/webhook`, événement `checkout.session.completed`,
   copier le secret dans `STRIPE_WEBHOOK_SECRET`.
4. **Test complet en mode test Stripe** (carte 4242 4242 4242 4242) :
   devis forfait → réservation → paiement acompte → email admin + client ;
   puis parcours audit : devis « sur mesure » → visite gratuite → emails.

## À remplacer par vos vraies données (rappels)

- Lien d'avis Google (bouton « Laisser un avis Google » de l'accueil) :
  pointe pour l'instant vers une recherche Google. Fiche Google Business →
  « Demander des avis » → coller le lien `g.page/r/…`.
- Photos avant/après de la section « Nos réalisations » (accueil) :
  les 12 images pointent encore vers le même fichier (`Image6.webp`).
- Témoignages : marqués « Exemple » (obligatoire — DGCCRF) tant qu'ils ne sont
  pas remplacés par de vrais avis clients autorisés.

## Créneaux du calendrier de réservation

Définis dans `reservation.html` (constante `KC_RESA`) : lundi–samedi,
créneaux 08:30 / 11:00 / 14:00 / 16:30, délai mini 48 h (24 h si majoration
urgence), dimanche/fériés uniquement si la majoration « dimanche/férié » est
choisie, horizon 35 jours. Le serveur revalide la date et le créneau.
Quand l'extension `kc-booking` (phases 3.x) sera terminée, ce calendrier
pourra rebasculer sur ses disponibilités réelles (Google Agenda).
