# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Vue d'ensemble

Backend de paiement du site **kayliclinn.fr** (réservation de prestations de ménage). Deux fonctions serverless de style Vercel (répertoire `api/`, handlers en `export default`), écrites en ES modules (`"type": "module"`). Unique dépendance : `stripe`. Les commentaires du code et les messages destinés aux utilisateurs sont en français.

## Commandes

- `npm install` — installe les dépendances. Dans les sessions Claude Code sur le web, c'est fait automatiquement par le hook SessionStart (`.claude/hooks/session-start.sh`).
- `node --check api/<fichier>.js` — vérification de syntaxe.
- Aucun test, linter ou build n'est configuré à ce jour.

Le serveur MCP Stripe est déclaré dans `.mcp.json` (nécessite la variable d'environnement `STRIPE_RESTRICTED_API_KEY`).

## Architecture : flux de paiement

Le parcours traverse les deux fichiers, reliés par les **metadata** de la session Stripe Checkout — c'est le contrat de données central :

1. Le site appelle `POST /api/create-checkout-session` avec le détail de la réservation : `amount` (en euros), `mode` (`"acompte"` = 30 % ou `"total"`), `contact`, `service`, `date`, `slot`, `totalTTC`.
2. `create-checkout-session.js` crée une session Stripe Checkout et copie **toutes** les infos de réservation dans `metadata` (client, adresse, prestation, `balance_due` = solde restant après acompte). Le client est redirigé vers la page de paiement Stripe, puis vers `{SITE_URL}/confirmation` ou `/reservation?paiement=annule`.
3. Stripe notifie `POST /api/webhook`. Sur `checkout.session.completed`, le handler relit les `metadata` et envoie deux emails via l'API Resend : notification à l'admin et confirmation au client.

Toute nouvelle donnée de réservation doit donc être ajoutée aux deux bouts : dans les `metadata` à la création de la session **et** dans la lecture côté webhook.

## Contraintes à respecter

- **`api/webhook.js` désactive le parsing du corps** (`export const config = { api: { bodyParser: false } }`) : Stripe vérifie la signature sur le corps brut. Ne pas réactiver le parsing, ne pas lire `req.body` dans ce fichier.
- **Le webhook renvoie 200 même si le traitement d'un événement échoue** (pour éviter les retries en boucle de Stripe). Seul un échec de vérification de signature renvoie 400.
- **CORS** : `create-checkout-session.js` n'autorise que `kayliclinn.fr`, `www.kayliclinn.fr` et `http://localhost:3000` (liste `ALLOWED_ORIGINS`).
- Les montants arrivent **en euros** du frontend et sont convertis **en centimes** (`amountCents`) pour Stripe.

## Variables d'environnement

| Variable | Rôle |
| --- | --- |
| `STRIPE_SECRET_KEY` | Clé API Stripe (les deux endpoints) |
| `STRIPE_WEBHOOK_SECRET` | Vérification de signature du webhook |
| `RESEND_API_KEY` | Envoi des emails ; si absente, les emails sont **silencieusement ignorés** (log seulement) |
| `NOTIFICATION_EMAIL` | Destinataire admin (défaut : `contact@kayliclinn.fr`) |
| `SITE_URL` | Base des URLs de redirection (défaut : `https://kayliclinn.fr`) |
