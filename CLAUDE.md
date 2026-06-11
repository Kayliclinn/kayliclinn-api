# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Vue d'ensemble

Backend de paiement et de devis du site **kayliclinn.fr** (Kayli Clinn, SAS de nettoyage en Île-de-France). Fonctions serverless de style Vercel (répertoire `api/`, handlers en `export default`), ES modules (`"type": "module"`). Unique dépendance : `stripe`. Tout le code, les commentaires et les messages sont en français ; les textes destinés aux clients sont au **vouvoiement**.

Le site WordPress (WordPress.com Atomic) appelle cette API depuis les pages du répertoire `site/` (blocs « HTML personnalisé » à coller dans WordPress — voir `site/README.md`).

## Commandes

- `npm install` — installe les dépendances (automatique en session web via le hook SessionStart).
- `node --check api/<fichier>.js` — vérification de syntaxe.
- Aucun test, linter ou build n'est configuré à ce jour.

Le serveur MCP Stripe est déclaré dans `.mcp.json` (variable `STRIPE_RESTRICTED_API_KEY`).

## Architecture

### Source de vérité des prix : `lib/pricing.js`

Grille tarifaire officielle (issue du fichier Excel « Kayli Clinn tarification »). **Le serveur recalcule toujours les prix** ; aucun montant envoyé par le navigateur n'est accepté. Toute modification de tarif se fait ici **puis** dans la constante `KC_TARIFS` de `site/estimation.html` (affichage).

### Endpoints (`api/`)

| Endpoint | Rôle |
| --- | --- |
| `POST /api/create-checkout-session` | Forfaits logement : recalcule le prix (`computeForfait`), crée la session Stripe Checkout (acompte 30 % ou total). Metadata = contrat de données vers le webhook. |
| `POST /api/webhook` | Reçoit `checkout.session.completed`, envoie les emails admin + client (Resend). |
| `POST /api/book-audit` | Visite d'audit gratuite (prestations sur devis) : validation + emails. |
| `POST /api/send-quote` | Envoie le devis par email (forfait recalculé serveur / estimation pro indicative / demande de rappel). |

`lib/helpers.js` : CORS (liste `ALLOWED_ORIGINS`), échappement HTML, validations, rate limiting (en mémoire, best effort), envoi Resend.

### Parcours côté site (`site/`)

`estimation.html` (page `/devis/`) → 3 parcours alignés sur la grille : **forfait** (réservation + paiement en ligne), **pro** (estimation immédiate puis audit), **audit** (visite gratuite). Passage de relais vers `/reservation/` via `sessionStorage` (clé `kc_reservation_handoff`, v2, 1 h). `reservation.html` → calendrier + paiement Stripe (forfaits) ou réservation de visite gratuite (audits). Retours Stripe : `/reservation/?paiement=succes|annule`.

## Règles non négociables (établies avec la propriétaire)

- **Ne JAMAIS inventer de données métier** : prix, règles de facturation, horaires, personnel → toujours demander. La grille `lib/pricing.js` est la seule référence tarifaire.
- **Retouches chirurgicales** : modifier le minimum ; jamais de restructuration non demandée.
- **Paiements** : montants recalculés côté serveur, signature des webhooks Stripe vérifiée sur le corps brut, tester en mode test Stripe avant tout passage en live.
- **Secrets** uniquement en variables d'environnement (Vercel) — jamais dans le code ni dans `site/`.
- **Charte site** : navy `#0D2340` dominant, teal `#0FA7A5` en accent seulement ; Montserrat/Inter/Roboto (+ Fraunces pages éditoriales) ; icônes SVG, jamais d'emoji ; un préfixe CSS unique par bloc WordPress.
- **Commits en français**, un commit par fonctionnalité.

## Contraintes techniques

- **`api/webhook.js` désactive le parsing du corps** (`bodyParser: false`) : signature Stripe vérifiée sur le corps brut. Ne pas lire `req.body` dans ce fichier.
- **Le webhook renvoie 200 même si le traitement échoue** (anti-retries Stripe). Seul un échec de signature renvoie 400.
- **CORS** : seuls `kayliclinn.fr`, `www.kayliclinn.fr` et `localhost:3000` (liste dans `lib/helpers.js`).
- Montants calculés **en centimes** côté serveur pour éviter les erreurs de flottants.

## Variables d'environnement

| Variable | Rôle |
| --- | --- |
| `STRIPE_SECRET_KEY` | Clé API Stripe (mode test tant que le KYC n'est pas fini) |
| `STRIPE_WEBHOOK_SECRET` | Vérification de signature du webhook |
| `RESEND_API_KEY` | Envoi des emails ; si absente, emails **silencieusement ignorés** (log seulement) |
| `EMAIL_FROM` | Expéditeur vérifié Resend (défaut : `onboarding@resend.dev`, à remplacer après vérification du domaine) |
| `NOTIFICATION_EMAIL` | Destinataire admin (défaut : `contact@kayliclinn.fr`) |
| `SITE_URL` | Base des redirections (défaut : `https://kayliclinn.fr`) |

## Contexte projet plus large

Le site WordPress possède aussi des extensions maison (`kc-booking` — réservations + Google Agenda, REST `kc-booking/v1`, phases 3.1/3.2 inachevées —, `kc-sheet-sync`, `kc-contact`, `kc-devis`). À terme, la page réservation pourra rebasculer sur `kc-booking` quand ses endpoints (`/types`, `/availability`, `/bookings`) seront finis ; cette API Vercel restera l'intermédiaire Stripe sécurisé. Ne pas mélanger les deux chemins sans décision explicite de la propriétaire.
