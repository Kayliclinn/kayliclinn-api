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
| `demande-de-devis.html` | `/demande-de-devis/` | e-mail/horaires ; **kc-contact + `window.kcContact`** |
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

## Deux intentions : « Réserver » et « Demander un devis »

Le mot « rendez-vous » est volontairement banni (il se confondait avec « réservation »).
Deux verbes seulement, jamais ambigus :

- **Prestations estimables** (forfaits : Turnover Airbnb, Vitres classiques, Fin de
  bail/déménagement) → bouton **« Estimer & réserver en ligne »** → `/devis/`. L'estimation
  **calcule le montant et recueille les infos**, puis enchaîne sur la **réservation** du créneau
  (ou une visite). On ne passe donc jamais à côté du montant. *Pas* de bouton « Demander un
  devis » sur ces pages (il court-circuiterait l'estimation).
- **Prestations sur devis** (tout le reste) → bouton **« Demander un devis »** →
  `/demande-de-devis/?presta=<slug>`.
- **`/demande-de-devis/` est directement accessible** (jamais conditionné à l'estimation). Pensez
  à pointer une entrée de menu vers `/demande-de-devis/` (le bouton teal du menu reste réservé au
  recrutement « Nous rejoindre »).
- Hero de l'accueil : **2 boutons** (« Estimation en ligne » + « Voir nos prestations »).
- **`/demande-de-devis/`** propose 2 actions :
  1. **« Être rappelé(e) »** → envoi via **kc-contact** (`window.kcContact`).
  2. **« Réserver une visite gratuite »** → `/reservation/?visite=<slug>` (calendrier
     kc-booking, type `visite-audit`, sans paiement).
- `/reservation/` accepte `?visite=<slug>` (entrée directe visite gratuite) en plus du relais
  depuis `/devis/` — ajout additif, le tunnel de paiement est inchangé.
- Le tunnel `/devis/` route déjà les prestations non estimables vers « sur devis · visite
  gratuite » (aucun emploi du mot « rendez-vous »).

## Interventions spécialisées — sous-traitance (partenaires certifiés)

Décision propriétaire : ces 5 prestations réglementées/sensibles sont **proposées en
sous-traitance** (un partenaire certifié réalise l'acte réglementé ; Kayli Clinn coordonne).
Elles sont **publiables**, en **« sur devis »** (aucun prix, aucune réservation en ligne),
avec une bande « partenariat » en tête de page et le bouton « Demander un devis ». Présentes
aussi dans le tunnel `/devis/` (section « Interventions spécialisées ») et le catalogue.

| Fichier | Page / slug | Catégorie menu |
| --- | --- | --- |
| `deratisation.html` | `/deratisation/` | 3D nuisibles |
| `desinsectisation.html` | `/desinsectisation/` | 3D nuisibles |
| `punaises-de-lit.html` | `/punaises-de-lit/` | 3D nuisibles |
| `syndrome-de-diogene.html` | `/syndrome-de-diogene/` | Extrême |
| `scenes-sensibles.html` | `/scenes-sensibles/` | Extrême |

> 👉 **À valider par la propriétaire** : la formulation du partenariat (commentaire `À VALIDER`
> dans chaque fichier) et la réalité du/des partenaire(s) certifié(s) Certibiocide. À confirmer
> avec votre conseil avant publication.

## En réserve (hors menu, non publiées — gardées à ta demande)

`entretien-professionnel.html` · `desinfection-virucide.html` · `sinistres-degats-des-eaux.html`.
Elles ont aussi le bouton « Demander un devis ».

## Redirections 301 (éviter le contenu dupliqué)

- `/nettoyage-demenagement/` → `/nettoyage-apres-demenagement-etat-des-lieux/`
- `/nettoyage-fin-de-chantier-paris/` → `/nettoyage-fin-de-chantier/`
- `/nettoyage-de-vitres/` → `/vitres-classiques/`
- `/nos-services/` → `/prestations/`
- `/devis-turnover-airbnb/` → `/devis/`

## Catégories de menu = simples regroupements

« Propreté récurrente », « Prestation ponctuelle », « Extrême », « 3D nuisibles »,
« Spécifique » ne sont **pas des pages** : juste des conteneurs de sous-menu.

## Dépendances plugins (sinon repli honnête + téléphone)

- `/contact/` et `/demande-de-devis/` → **kc-contact** + `window.kcContact { ajaxUrl, nonce }`.
- `/devis/` → **kc-devis** + `window.kcDevis`.
- `/reservation/` → **kc-booking** (KC_Pricing, type `visite-audit`).

## Données à compléter

Cherchez `👉` dans chaque fichier : photos, e-mail, adresse, horaires, assureur RC Pro,
certifications, classes ISO, etc. — **jamais inventées**, toujours en placeholder visible.
