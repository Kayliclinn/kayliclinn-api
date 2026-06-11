# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Vue d'ensemble

Dépôt de travail du site **kayliclinn.fr** (Kayli Clinn, SAS de nettoyage en Île-de-France, WordPress.com Atomic). Tout le tunnel devis → réservation → paiement passe par **WordPress** (extensions maison `kc-booking`, `kc-devis`, `kc-sheet-sync`). Tout est en français ; les textes destinés aux clients sont au **vouvoiement**.

Contenu du dépôt :

| Dossier | Rôle |
| --- | --- |
| `site/` | Les pages du site (blocs « HTML personnalisé » à coller dans WordPress) + guide `site/README.md` |
| `wordpress/` | Code PHP à intégrer dans les extensions (grille de prix serveur `kc-pricing.php`, handler kc-devis durci) + analyse de sécurité des 4 plugins (`ANALYSE-plugins.md`) + guide `INTEGRATION-kc-booking.md` |
| `lib/`, `api/` | **Archive** : ancienne API Vercel (décision du 11/06/2026 : Vercel retiré du projet). Ne plus brancher les pages dessus. `lib/pricing.js` reste une référence lisible de la grille. |

## État réel des extensions (vérifié sur le code source, 11/06/2026)

`kc-booking` est plus avancé que le document de suivi ne le dit : **les Phases 3.1 (`GET /types`) et 3.2 (`GET /availability`) sont terminées** (disponibilités croisant horaires du personnel + Google Agenda + réservations). Le webhook Stripe vérifie la signature et est idempotent. **Une faille critique demeure** : `kc_rest_create_booking` accepte le montant envoyé par le navigateur — à corriger avec `KC_Pricing` (voir `wordpress/INTEGRATION-kc-booking.md`, correctif 1). Slugs réels des types : `bureaux, parties-communes, commerces, sensibles, fin-chantier, demenagement, remise-etat, decapage, vitres, airbnb` (seuls `airbnb` et `demenagement` sont `calendar_paid`).

## Architecture du tunnel (WordPress)

1. `/devis/` (`site/estimation.html`) : tunnel 3 parcours — **forfait** (prix ferme de la grille, réservable en ligne), **pro** (estimation immédiate), **audit** (visite gratuite). Envoi du devis par email via admin-ajax `kc_devis`. Passage à la réservation via `sessionStorage`, clé `kc_reservation_handoff` (v2, 1 h).
2. `/reservation/` (`site/reservation.html`) : REST `kc-booking/v1` — `GET /types`, `GET /availability` (Phase 3.2), `POST /bookings` (champ `quote` = devis complet en JSON), liens durables `?resa=token` (gérer / payer / annuler, compatibles kc-sheet-sync). Forfaits = paiement Stripe (acompte 30 % ou total) ; tout le reste = visite gratuite (type générique `visite-audit`).
3. La table `TYPE_MAP` en haut du script de `reservation.html` fait la correspondance prestation du devis → slug de type kc-booking.

## La grille tarifaire (source de vérité)

Fichier source : `site/tarification.xlsx`. Implémentations synchronisées — **toute modification de prix se reporte aux trois endroits** :
1. `wordpress/kc-pricing.php` (validation côté serveur — fait foi à l'encaissement),
2. constante `KC_TARIFS` dans `site/estimation.html` (affichage),
3. `lib/pricing.js` (archive lisible).

Forfaits logement TTC (acompte 30 %) : Airbnb 45/60/75/95 € ; déménagement, fin de bail, état des lieux, standard, logement vide 79/99/119/149 € (studio/2P/3P/4P). Options : linge 15/25/40, frigo 10, four 15, petit balcon 10, terrasse 30. Majorations cumulables sur (base + options) : urgence +20 %, dimanche/férié +25 %. « Très sale » (+30 à 50 %) : jamais réservable en ligne — validation photos/visite. Pros (HT, indicatif) : bureaux 32–38 €/h (IDF contraint 38–45), locaux/commerces/copro 32–60 €/h, minimum passage 45–65 €, vitres 3–7 €/m².

## Règles non négociables (établies avec la propriétaire)

- **Ne JAMAIS inventer de données métier** : prix, facturation, horaires, personnel → toujours demander.
- **Retouches chirurgicales** : modifier le minimum, jamais de restructuration non demandée.
- **Paiements** : montants recalculés côté serveur (`KC_Pricing`), signature des webhooks Stripe vérifiée, idempotence (jamais deux traitements d'un même événement), tests en mode test avant le live.
- **Secrets** uniquement côté serveur (jamais dans un bloc HTML ni dans Git).
- **Charte** : navy `#0D2340` dominant, teal `#0FA7A5` en accent seulement ; Montserrat/Inter/Roboto (+ Fraunces pages éditoriales) ; icônes SVG, jamais d'emoji ; **un préfixe CSS unique par bloc** WordPress.
- **Commits en français**, un commit par fonctionnalité.
- Témoignages fictifs : badge « Exemple » obligatoire tant qu'ils ne sont pas remplacés (DGCCRF).

## Contraintes WordPress.com Atomic

Pas d'accès FTP : les extensions s'installent par ZIP (admin → Extensions → Téléverser). Le code de `wordpress/` s'intègre donc au code source local des extensions, puis on refabrique le ZIP (voir `wordpress/INTEGRATION-kc-booking.md`).
