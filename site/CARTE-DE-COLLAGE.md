# Carte de collage des pages — kayliclinn.fr

Chaque fichier `.html` de `site/` est **le contenu complet d'une page**, à coller
dans un bloc **« HTML personnalisé »** WordPress (un fichier = une page, hors
en-tête/pied de page du thème). Méthode : ouvrir la page → bloc « HTML
personnalisé » → remplacer **tout** le contenu par le fichier → prévisualiser →
publier.

> Charte commune : navy `#0D2340` / teal `#0FA7A5`, Montserrat/Inter/Roboto +
> Fraunces, icônes SVG, **aucun emoji**, vouvoiement. Prix de la grille v2 quand
> ils sont fermes, sinon **« sur devis »** (aucun prix inventé).

## Barre de navigation

| Fichier | Page / slug | Notes avant publication |
| --- | --- | --- |
| `accueil.html` | Accueil | photos (panneau `:root` en tête), vrai lien avis Google |
| `prestations.html` | `/prestations/` | catalogue (sert aussi `/nos-services/`) |
| `realisations.html` | `/realisations/` | **20 photos avant/après** (placeholder `Image6.webp`) |
| `qualite-qse.html` | `/qualite-qse/` | assureur & montant RC Pro, certifications |
| `contact.html` | `/contact/` | e-mail, adresse, horaires ; **kc-contact + `window.kcContact`** |
| `rendez-vous.html` | `/rendez-vous/` | e-mail/horaires ; **kc-contact + `window.kcContact`** |
| `estimation.html` | `/devis/` | **kc-devis** + `window.kcDevis` |
| `reservation.html` | `/reservation/` | **kc-booking** (KC_Pricing + type `visite-audit`) |

## Pages prestations (menu « Prestations »)

| Fichier | Page / slug | Tarifs |
| --- | --- | --- |
| `prestation.html` | `/turnover-airbnb/` | **Forfait** Airbnb 55–120 TTC |
| `vitres-classiques.html` | `/vitres-classiques/` | **Forfait** 70/70/95/95/130 TTC |
| `nettoyage-apres-demenagement-etat-des-lieux.html` | `/nettoyage-apres-demenagement-etat-des-lieux/` | **Forfait** 160→430 TTC |
| `entretien-de-bureaux.html` | `/entretien-de-bureaux/` | indicatif HT, sur devis |
| `parties-communes-immeubles.html` | `/parties-communes-immeubles/` | sur devis |
| `commerces-retail.html` | `/commerces-retail/` | sur devis |
| `etablissements-sensibles.html` | `/etablissements-sensibles/` | sur devis |
| `nettoyage-fin-de-chantier.html` | `/nettoyage-fin-de-chantier/` | indicatif HT (audit) |
| `remise-en-etat.html` | `/remise-en-etat/` | sur devis |
| `decapage-cristallisation-sols.html` | `/decapage-cristallisation-sols/` | sur devis |
| `shampouinage-moquettes.html` | `/shampouinage-moquettes/` | sur devis |
| `nettoyage-haute-pression.html` | `/nettoyage-haute-pression/` | sur devis |
| `pieces-blanches.html` | `/pieces-blanches/` | sur devis |

## Prise de rendez-vous (sur toutes les prestations)

- Chaque page prestation a un bouton **« Prendre rendez-vous »** → `/rendez-vous/?presta=<slug>`
  (primaire sur les pages « sur devis » ; secondaire sur les forfaits, où
  « Estimation/réservation en ligne » reste primaire). L'accueil a aussi ce bouton.
- **`/rendez-vous/`** propose 2 actions :
  1. **« Être rappelé(e) »** → envoi via **kc-contact** (`window.kcContact`).
  2. **« Réserver une visite gratuite »** → `/reservation/?visite=<slug>` (calendrier
     kc-booking, type `visite-audit`, sans paiement).
- `/reservation/` accepte désormais `?visite=<slug>` (entrée directe visite gratuite) en
  plus du relais depuis `/devis/` — ajout additif, le tunnel de paiement est inchangé.

## NE PAS PUBLIER en l'état (règles du projet)

Avertissement en tête de chaque fichier ; **sans aucun prix**, sans réservation en ligne.

| Fichier | Page / slug | Blocage |
| --- | --- | --- |
| `syndrome-de-diogene.html` | `/syndrome-de-diogene/` (Extrême) | **Certibiocide** + validation |
| `scenes-sensibles.html` | `/scenes-sensibles/` (Extrême) | validation (décès/biohazard) |
| `deratisation.html` | `/deratisation/` (3D) | **Certibiocide** |
| `desinsectisation.html` | `/desinsectisation/` (3D) | **Certibiocide** |
| `punaises-de-lit.html` | `/punaises-de-lit/` (3D) | **Certibiocide** |

## En réserve (hors menu, non publiées — gardées à ta demande)

`entretien-professionnel.html` · `desinfection-virucide.html` (⛔ Certibiocide) ·
`sinistres-degats-des-eaux.html`. Elles ont aussi le bouton « Prendre rendez-vous ».

## Redirections 301 (éviter le contenu dupliqué)

- `/nettoyage-demenagement/` → `/nettoyage-apres-demenagement-etat-des-lieux/`
- `/nettoyage-fin-de-chantier-paris/` → `/nettoyage-fin-de-chantier/`
- `/nettoyage-de-vitres/` → `/vitres-classiques/`
- `/nos-services/` → `/prestations/`
- `/devis-turnover-airbnb/` → `/devis/`

## Catégories de menu = simples regroupements

« Propreté récurrente », « Prestation ponctuelle », « Extrême », « 3D nuisibles »,
« Spécifique » ne sont **pas des pages** : juste des conteneurs de sous-menu.

## À créer plus tard

- **Grand ménage** (forfait validé 140/190/250/320 TTC) : pas encore de page ; le
  catalogue renvoie vers `/devis/` en attendant.

## Dépendances plugins (sinon repli honnête + téléphone)

- `/contact/` et `/rendez-vous/` → **kc-contact** + `window.kcContact { ajaxUrl, nonce }`.
- `/devis/` → **kc-devis** + `window.kcDevis`.
- `/reservation/` → **kc-booking** (KC_Pricing, type `visite-audit`).

## Données à compléter

Cherchez `👉` dans chaque fichier : photos, e-mail, adresse, horaires, assureur RC Pro,
certifications, classes ISO, etc. — **jamais inventées**, toujours en placeholder visible.
