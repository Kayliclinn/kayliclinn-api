# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Vue d'ensemble

Dépôt de travail du site **kayliclinn.fr** (Kayli Clinn, SAS de nettoyage en Île-de-France, WordPress.com Atomic). Tout le tunnel devis → réservation → paiement passe par **WordPress** (extensions maison `kc-booking`, `kc-devis`, `kc-sheet-sync`). Tout est en français ; les textes destinés aux clients sont au **vouvoiement**.

Contenu du dépôt :

| Dossier | Rôle |
| --- | --- |
| `plugins/` | **Versions complètes et corrigées** des extensions (`kc-booking`, `kc-devis`, `kc-sheet-sync`, `kc-contact`, `kc-visite-audit`) — structure prête pour les Déploiements GitHub de WordPress.com (destination `/wp-content`, voir `DEPLOIEMENT-GITHUB.md` et `.deployignore`) |
| `site/` | Les pages du site (blocs « HTML personnalisé » à coller dans WordPress) + guide `site/README.md` |
| `wordpress/` | Guides : `COPIER-DANS-WORDPRESS.md`, `INTEGRATION-kc-booking.md` (détail des correctifs), `ANALYSE-plugins.md` (revue de sécurité) |
| `lib/`, `api/` | **Archive** : ancienne API Vercel (décision du 11/06/2026 : Vercel retiré du projet). Ne plus brancher les pages dessus. `lib/pricing.js` reste une référence lisible de la grille. |

## État réel des extensions (vérifié sur le code source, 11/06/2026)

`kc-booking` est plus avancé que le document de suivi ne le dit : **les Phases 3.1 (`GET /types`) et 3.2 (`GET /availability`) sont terminées** (disponibilités croisant horaires du personnel + Google Agenda + réservations). Le webhook Stripe vérifie la signature et est idempotent. **La faille critique** (`kc_rest_create_booking` acceptait le montant envoyé par le navigateur) **est corrigée dans `plugins/kc-booking/`** via `KC_Pricing` — la correction prend effet sur le site une fois ce dossier déployé. Slugs réels des types : `bureaux, parties-communes, commerces, sensibles, fin-chantier, demenagement, remise-etat, decapage, vitres, airbnb` (seuls `airbnb` et `demenagement` sont `calendar_paid`).

## Architecture du tunnel (WordPress)

1. `/devis/` (`site/estimation.html`) : tunnel 3 parcours — **forfait** (prix ferme de la grille, réservable en ligne), **pro** (estimation immédiate), **audit** (visite gratuite). Envoi du devis par email via admin-ajax `kc_devis`. Passage à la réservation via `sessionStorage`, clé `kc_reservation_handoff` (v2, 1 h).
2. `/reservation/` (`site/reservation.html`) : REST `kc-booking/v1` — `GET /types`, `GET /availability` (Phase 3.2), `POST /bookings` (champ `quote` = devis complet en JSON), liens durables `?resa=token` (gérer / payer / annuler, compatibles kc-sheet-sync). Forfaits = paiement Stripe (acompte 30 % ou total) ; tout le reste = visite gratuite (type générique `visite-audit`).
3. La table `TYPE_MAP` en haut du script de `reservation.html` fait la correspondance prestation du devis → slug de type kc-booking.

## La grille tarifaire v2 (source de vérité, 11/06/2026)

Fichier source : `site/tarification.xlsx` (« Kayli Clinn tarification 2 »). Implémentations synchronisées — **toute modification de prix se reporte aux trois endroits** :
1. classe `KC_Pricing` intégrée en tête de `plugins/kc-booking/kc-booking.php` (validation côté serveur — fait foi à l'encaissement) **et** de `plugins/kc-devis/kc-devis.php` (copie identique — un plugin reste un seul fichier autonome, copiable d'un bloc),
2. constante `KC_TARIFS` dans `site/estimation.html` (affichage),
3. `lib/pricing.js` (archive lisible, avec les garde-fous documentés).

Formule : **total = (base + options) × (1 + majorations) + frais fixes**, acompte 30 %. TTC pour les particuliers, HT pour les pros — l'affichage doit le dire sans ambiguïté.

**Forfaits B2C TTC (prix fixe en ligne, Studio/T1·T2·T3·T4·T5)** : Turnover Airbnb 55/75/95/120 (T5+ : devis) ; fin de bail/déménagement logement vide 160/220/290/360/430 (électroménager + vitres int. + placards INCLUS ; >110 m²/maison : devis) ; grand ménage meublé 140/190/250/320 (T5+ : devis) ; vitrerie résidentielle 70/70/95/95/130 (hauteur/vitrine/verrière : devis). **Options** : four 35, frigo 25, vitres int. 20, placards 25, consommables 10, cave/box 25, kit linge 18 €/lit, repassage 35 €/h, balcon 2 €/m² (min 20), moquette 5 €/m² (min 100), canapé 2/3/4 pl. 80/100/110, fauteuil 50, matelas 75 — chaque option a sa liste de forfaits compatibles. **Majorations** : urgence <48 h +30 %, dimanche/férié +25 %, logement non vidé (fin de bail) +15 %, très encrassé (grand ménage) +30 %. **Frais** : étage ≥3 sans ascenseur 15 €. **Fin de chantier** : estimation indicative HT ±15 % (4,50/7/11 €/m² selon niveau, min 300 € HT), prix confirmé après audit/photos — jamais ferme en ligne. **Bureaux** : budget indicatif 1,5–3 €/m²/mois HT (position « à partir de 2 € »), devis seulement. **Copro, commerces/ERP, remise en état lourde** : devis pur, aucun chiffre en ligne (strict pour insalubrité/sinistre/décès).

**Bascules automatiques vers devis (garde-fous, implémentés dans le tunnel)** : typologie hors grille (T5+/maison), état « très dégradé », vitres hauteur/vitrine/verrière, B2B récurrent, mots-clés sensibles dans le champ libre (sinistre, insalubre, décès, gravats…).

**❓ Points à trancher par la propriétaire (ne JAMAIS inventer)** : validation ligne à ligne après contrôle de rentabilité ; dimanche +25 à +50 % (25 % implémenté en attendant) ; minimum d'intervention global B2C (70–80 € suggéré — non implémenté) ; zonage déplacement grande couronne 15–30 € (non implémenté) ; crédit d'impôt 50 % (non proposé — voir expert-comptable).

**Catalogue cible & arbitrages (doc « 18 prestations », 12/06/2026)** : les prestations supplémentaires du document (parties communes en paliers, commerces, établissements sensibles, décapage/cristallisation, moquettes en autonome, haute pression, Diogène, scènes sensibles, 3D dératisation/désinsectisation/punaises, pièces blanches) restent **hors tunnel** tant que leurs montants « recommandés » ne sont pas validés par la formule de rentabilité — et les 3D exigent le **Certibiocide** (certification biocides) avant toute publication de page. **Arbitrages rendus le 12/06** : Airbnb 55/75/95/120 (grille v2 conservée) ; fin de bail 160–430 (v2 conservée) ; vitrerie au forfait typologie (pas au prix/vitre) ; **urgence < 48 h portée à +30 %** (appliqué partout).

## Règles non négociables (établies avec la propriétaire)

- **Ne JAMAIS inventer de données métier** : prix, facturation, horaires, personnel → toujours demander.
- **Retouches chirurgicales** : modifier le minimum, jamais de restructuration non demandée.
- **Paiements** : montants recalculés côté serveur (`KC_Pricing`), signature des webhooks Stripe vérifiée, idempotence (jamais deux traitements d'un même événement), tests en mode test avant le live.
- **Secrets** uniquement côté serveur (jamais dans un bloc HTML ni dans Git).
- **Charte** : navy `#0D2340` dominant, teal `#0FA7A5` en accent seulement ; Montserrat/Inter/Roboto (+ Fraunces pages éditoriales) ; icônes SVG, jamais d'emoji ; **un préfixe CSS unique par bloc** WordPress.
- **Commits en français**, un commit par fonctionnalité.
- Témoignages fictifs : badge « Exemple » obligatoire tant qu'ils ne sont pas remplacés (DGCCRF).

## Contraintes WordPress.com Atomic

Pas d'accès FTP. Deux façons de mettre à jour les extensions : **Déploiements GitHub** (recommandé — dépôt connecté, destination `/wp-content`, mode manuel ; voir `DEPLOIEMENT-GITHUB.md`) ou ZIP via admin → Extensions → Téléverser (voir `wordpress/COPIER-DANS-WORDPRESS.md`).
