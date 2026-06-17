# Kayli Clinn — API

API **serverless** ([fonctions Vercel](https://vercel.com/docs/functions)) qui
gère les **paiements Stripe** et les **notifications de réservation** par email
pour le site [kayliclinn.fr](https://kayliclinn.fr) (prestations de ménage).

## Stack

- **Node.js** ≥ 18 (modules ESM)
- **Vercel** — fonctions serverless dans `api/`
- **Stripe** — Checkout Sessions + webhooks signés
- **Resend** — emails de confirmation (optionnel)

## Structure

```
api/
  create-checkout-session.js   # POST — crée une session de paiement Stripe
  webhook.js                   # POST — reçoit les événements Stripe (signés)
.claude/agents/                # sous-agents Claude Code (senior-backend, code-reviewer)
.env.example                   # variables d'environnement requises
package.json
```

## Variables d'environnement

Copier `.env.example` vers `.env.local` puis renseigner les valeurs.

| Variable | Requis | Rôle |
|---|:---:|---|
| `STRIPE_SECRET_KEY` | ✅ | Clé secrète API Stripe |
| `STRIPE_WEBHOOK_SECRET` | ✅ | Secret de signature du webhook |
| `SITE_URL` | — | URL du site pour les redirections (défaut `https://kayliclinn.fr`) |
| `RESEND_API_KEY` | — | Clé Resend ; si absente, aucun email n'est envoyé |
| `NOTIFICATION_EMAIL` | — | Destinataire de la notification admin (défaut `contact@kayliclinn.fr`) |

> ⚠️ Ne jamais commiter de fichier `.env*` (déjà couvert par `.gitignore`).

## Développement local

```bash
npm install
npm i -g vercel          # CLI Vercel (une seule fois)
npm run dev              # = vercel dev — sert les fonctions sur http://localhost:3000
```

Tester le webhook Stripe en local :

```bash
stripe login
stripe listen --forward-to localhost:3000/api/webhook
# Copier le secret whsec_... affiché dans STRIPE_WEBHOOK_SECRET
```

## Déploiement (Vercel)

Renseigner les variables d'environnement dans
*Project → Settings → Environment Variables*, puis :

```bash
npm run deploy          # = vercel --prod
```

(ou déploiement automatique à chaque push si le dépôt est lié à Vercel).

## Endpoints

### `POST /api/create-checkout-session`

Crée une session Stripe Checkout et renvoie l'URL de paiement.
CORS restreint aux origines `kayliclinn.fr`.

**Body (JSON)** — `amount` (€, requis), `mode` (`"acompte"` | `"total"`),
`totalTTC`, `contact { email*, firstname*, lastname, phone, address, zip, city }`,
`service { type, surface }`, `date`, `slot`.
*(\* = obligatoire)*

**Réponses** — `200 { "url": "https://checkout.stripe.com/…" }` ·
`400` données invalides · `405` méthode ≠ POST.

### `POST /api/webhook`

Reçoit les événements Stripe (corps brut + **signature vérifiée**).
Gère `checkout.session.completed` (emails de confirmation via Resend),
`checkout.session.expired`, `payment_intent.payment_failed`.

**Réponses** — `200 { "received": true }` · `400` signature invalide.
