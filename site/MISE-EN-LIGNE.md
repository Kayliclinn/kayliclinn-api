# Mise en ligne kayliclinn.fr — check-list ordonnée

Guide pas-à-pas pour passer le site du dépôt à la production WordPress.
À cocher dans l'ordre. Référence détaillée page→slug : `site/CARTE-DE-COLLAGE.md`.

> Légende : ☐ à faire · 🔒 ne pas publier sans validation · 🧩 dépend d'une extension.

---

## Phase 0 — Consolider le code (toi + moi)
- ☐ **Relire et fusionner les 3 PR** vers `claude/exciting-cannon-o2ey0a` :
  - #3 — Carte Île-de-France (étape zone)
  - #4 — Repli des prestations sur devis + photos/ALT
  - #5 — Titres Montserrat → Fraunces (accueil & réalisations)
- ☐ **Me prévenir une fois fusionnées** → j'enchaîne les 2 derniers alignements :
  1. Accueil (carrousel + liens → nouvelle taxonomie) ;
  2. Tunnel `/devis/` → Fraunces (chantier d'un tenant).
- ☐ Charger la police **Fraunces** côté thème (sinon les titres tombent en serif système).

## Phase 1 — Prérequis techniques (extensions WordPress) 🧩
Sans elles, les pages s'affichent mais affichent un message honnête + le téléphone (jamais de faux succès).
- ☐ **kc-booking** déployé, avec `KC_Pricing` branché (recalcul serveur) et le type **`visite-audit`** créé (extension `kc-visite-audit.php`). → fait marcher `/reservation/` + « Réserver une visite ».
- ☐ **kc-devis** déployé + injecter `window.kcDevis = { ajaxUrl, nonce }` sur `/devis/`.
- ☐ **kc-contact** déployé + injecter `window.kcContact = { ajaxUrl, nonce }` sur `/contact/` **et** `/demande-de-devis/`.
- ☐ Connecter les clés **Stripe en mode test** (avant le live).

## Phase 2 — Contenu à compléter (cherche les marqueurs `👉` dans chaque fichier)
- ☐ **Photos** : remplacer tous les placeholders — 4 visuels par page prestation (variables `--photo-*` en tête de fichier) + **20 avant/après** des réalisations (`Image6.webp`) + photos de l'accueil.
- ☐ **Données métier** (ne rien inventer) : à-propos (histoire, effectif, certifications, SIREN), **adresse postale**, **horaires précis**, **assureur + montant RC Pro** (Qualité & QSE), classes ISO (pièces blanches), durées d'engagement, chien détecteur (punaises), lien **Politique de confidentialité**.
- ☐ **Témoignages** : remplacer les exemples (badge « Exemple ») par de **vrais avis autorisés** (obligation DGCCRF) + vrai **lien d'avis Google** sur l'accueil.

## Phase 3 — Validations métier 🔒
- ☐ **Mention « partenaires certifiés »** (sous-traitance) : valider la formulation (marqueurs `👉 À VALIDER`) et la réalité du partenariat, avec ton conseil.
- ☐ **Certibiocide** obtenu **avant** de publier Dératisation / Désinsectisation / Punaises de lit / Diogène / Scènes sensibles.
- ☐ Validation **ligne à ligne des tarifs** (contrôle de rentabilité) si tu veux verrouiller la grille.

## Phase 4 — Collage des pages + menu + redirections
- ☐ **Coller chaque page** dans son bloc « HTML personnalisé » (mapping complet : `site/CARTE-DE-COLLAGE.md`). Prévisualiser avant publier.
- ☐ **Pages publiables** : Accueil, /turnover-airbnb/, /vitres-classiques/, /nettoyage-apres-demenagement-etat-des-lieux/, /entretien-de-bureaux/, /parties-communes-immeubles/, /commerces-retail/, /etablissements-sensibles/, /nettoyage-fin-de-chantier/, /remise-en-etat/, /decapage-cristallisation-sols/, /shampouinage-moquettes/, /nettoyage-haute-pression/, /pieces-blanches/, /prestations/, /realisations/, /qualite-qse/, /a-propos/, /contact/, /demande-de-devis/, /devis/, /reservation/.
- ☐ **Pages 🔒** (après Certibiocide/validation) : /deratisation/, /desinsectisation/, /punaises-de-lit/, /syndrome-de-diogene/, /scenes-sensibles/.
- ☐ **NE PAS publier (réserve)** : entretien-professionnel, desinfection-virucide, sinistres-degats-des-eaux.
- ☐ **Redirections 301** : `/nettoyage-demenagement/`→`/nettoyage-apres-demenagement-etat-des-lieux/` · `/nettoyage-fin-de-chantier-paris/`→`/nettoyage-fin-de-chantier/` · `/nettoyage-de-vitres/`→`/vitres-classiques/` · `/nos-services/`→`/prestations/` · `/devis-turnover-airbnb/`→`/devis/`.
- ☐ **Menu** : « Prestations » = méga-menu (les catégories sont de simples regroupements, pas des pages). Le bouton teal « Nous rejoindre » reste au **recrutement**. Ajouter si voulu une entrée **« Demander un devis »** → `/demande-de-devis/`.

## Phase 5 — Tests (en mode test, avant le live)
- ☐ **Parcours forfait** : estimation → réservation → **acompte 30 % Stripe (mode test)** → confirmation.
- ☐ **Anti-fraude** : forcer un montant côté navigateur → le serveur doit **recalculer via `KC_Pricing`** (jamais le montant du client).
- ☐ **Parcours sur devis** : « Demander un devis » → rappel (kc-contact) **et** « Réserver une visite gratuite » (visite-audit).
- ☐ **Carte IDF** (après #3) : clic département → l'étape avance.
- ☐ **Formulaires** contact + demande de devis : envoi OK / message honnête si extension absente.
- ☐ Vérifier qu'**aucun lien ne renvoie 404** (notamment les pages encore en réserve ne doivent pas être liées).

## Phase 6 — Mise en ligne + après
- ☐ Basculer Stripe en **live**.
- ☐ Publier les pages prêtes (garder les 🔒 et la réserve hors ligne).
- ☐ Vérifier mobile + temps de chargement (poids des photos < 300 Ko).
- ☐ Soumettre le sitemap / vérifier l'indexation (Search Console).

---

### Ordre conseillé
**Phase 0** (fusion + alignements) → **Phase 1** (extensions) en parallèle de la **Phase 2/3** (contenu + validations) → **Phase 4** (collage) → **Phase 5** (tests) → **Phase 6** (live).
