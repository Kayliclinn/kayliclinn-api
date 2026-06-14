# Carte de collage des pages — kayliclinn.fr

Chaque fichier `.html` de `site/` est **le contenu complet d'une page**, à coller
dans un bloc **« HTML personnalisé »** WordPress (un fichier = une page, hors
en-tête/pied de page du thème). Méthode : ouvrir la page → bloc « HTML
personnalisé » → remplacer **tout** le contenu par le fichier → prévisualiser →
publier.

> Toutes les pages partagent le design du gabarit (`prestation.html`) : charte
> navy `#0D2340` / teal `#0FA7A5`, polices Montserrat/Inter/Roboto + Fraunces,
> icônes SVG, **aucun emoji**, vouvoiement. Prix issus de la grille v2 quand ils
> sont fermes, sinon **« sur devis »** (aucun prix inventé).

## Pages à publier

| Fichier | Page / slug WordPress | Tarifs | Avant de publier |
| --- | --- | --- | --- |
| `accueil.html` | Accueil | grille | photos « réalisations », vrai lien avis Google |
| `prestation.html` | `/turnover-airbnb/` | Airbnb 55–120 TTC | 4 photos |
| `nettoyage-de-vitres.html` | `/nettoyage-de-vitres/` | 70/70/95/95/130 TTC | 4 photos |
| `nettoyage-apres-demenagement-etat-des-lieux.html` | `/nettoyage-apres-demenagement-etat-des-lieux/` | 160/220/290/360/430 TTC | 4 photos |
| `nettoyage-fin-de-chantier-paris.html` | `/nettoyage-fin-de-chantier-paris/` | indicatif HT (audit) | 4 photos, position gravats |
| `entretien-de-bureaux.html` | `/entretien-de-bureaux/` | indicatif HT, devis | 4 photos, durée d'engagement, gamme produits |
| `entretien-professionnel.html` | `/entretien-professionnel/` | sur devis | 4 photos |
| `prestations.html` | `/prestations/` | repères / devis | — |
| `a-propos.html` | `/a-propos/` | — | histoire, dirigeante, effectif, certifications, SIREN |
| `contact.html` | `/contact/` | — | e-mail, adresse, horaires, lien confidentialité ; **plugin kc-contact actif + `window.kcContact`** |
| `realisations.html` | `/realisations/` | — | **20 photos avant/après** (placeholder `Image6.webp`) |
| `estimation.html` | `/devis/` | grille | **plugin kc-devis** + `window.kcDevis` injecté |
| `reservation.html` | `/reservation/` | grille | **plugin kc-booking** (KC_Pricing branché) |

## Redirections de slugs (301) — pour éviter le contenu dupliqué

- `/nettoyage-demenagement/` → `/nettoyage-apres-demenagement-etat-des-lieux/` (même prestation)
- `/nettoyage-fin-de-chantier/` → `/nettoyage-fin-de-chantier-paris/`
- `/nos-services/` → `/prestations/`
- `/devis-turnover-airbnb/` → **faire pointer vers `/devis/`** (ne pas dupliquer le tunnel)

## NE PAS PUBLIER en l'état (règles du projet)

Ces pages sont prêtes mais **conditionnées** ; un commentaire d'avertissement
est présent en tête de chaque fichier.

| Fichier | Page / slug | Blocage |
| --- | --- | --- |
| `syndrome-de-diogene.html` | `/syndrome-de-diogene/` | **Certibiocide** (désinsectisation) + validation propriétaire |
| `desinfection-virucide.html` | `/desinfection-virucide/` | **Certibiocide** + normes/produits à confirmer |
| `sinistres-degats-des-eaux.html` | `/sinistres-degats-des-eaux/` | validation propriétaire + modalités assurance |

Ces trois pages sont **sans aucun prix** (devis pur) et **sans réservation en
ligne**, conformément à la règle « insalubrité / sinistre / décès ».

## Pages encore à créer (non bloquantes)

- **Grand ménage** (meublé occupé) : prix validés 140/190/250/320 TTC, mais pas
  encore de page dédiée. Le catalogue (`prestations.html`) renvoie vers `/devis/`
  en attendant. Page à bâtir sur le même gabarit quand vous voulez.

## Données à remplacer partout

Chaque page comporte des marqueurs `<!-- 👉 À COMPLÉTER : … -->` visibles dans le
code aux endroits où une donnée métier manque (jamais inventée). Cherchez `👉`
dans chaque fichier avant publication.
