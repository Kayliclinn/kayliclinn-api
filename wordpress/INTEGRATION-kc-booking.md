# Intégration WordPress — détail technique des corrections

> **Tu veux juste copier les fichiers ?** → voir `COPIER-DANS-WORDPRESS.md`.
> Les versions **complètes et corrigées** sont dans `wordpress/plugins/` :
> tu n'as plus de bloc à remplacer à la main, ce document explique seulement
> *ce qui a changé* et *pourquoi*.

Après lecture des 4 extensions (`kc-booking`, `kc-devis`, `kc-sheet-sync`,
`kc-contact`), voici ce qui est **déjà en place** et ce qui **a été corrigé**.
Tout le tunnel passe par WordPress — aucune dépendance à Vercel.

## Ce qui est déjà fait (et bien fait) ✅

- **`kc-booking` Phase 3.1 et 3.2 sont opérationnelles** : `GET /types`,
  `GET /availability` (croise horaires du personnel + Google Agenda free/busy
  + réservations en base + buffer + délais min/max), `POST /bookings`,
  `GET|POST /bookings/{token}` (confirm/cancel), `POST /stripe/webhook`.
  → Le document de suivi indiquait la Phase 3.2 « pas encore construite » :
  **c'est périmé, elle existe**. La page `site/reservation.html` est déjà
  alignée sur ce contrat (champs envoyés/lus compatibles).
- **Webhook Stripe** : signature vérifiée (`kc_stripe_verify_sig`) +
  idempotence (`if ($b->status === 'pending')`). Conforme aux règles du projet.
- **`kc-contact`** : nonce, honeypot, rate limiting, validation, `esc_html`
  partout dans les emails. RAS.
- Liens durables `?resa=token` + endpoint `/pay` (kc-sheet-sync) :
  « gérer / payer plus tard / annuler ». Bien.

## Correctif 1 — 🔴 FAILLE DE PRIX dans `kc-booking` (priorité absolue)

Dans `kc_rest_create_booking`, le bloc « Tarification » fait **confiance au
montant envoyé par le navigateur** :

```php
if ($devis_driven) {
    $total = round((float) $q_total, 2);   // ← le client choisit son prix
    ...
}
```

Un fraudeur peut appeler `POST /bookings` avec `amount_total=1` et payer 1 €
une prestation à 149 €. **Correctif : recalculer le prix côté serveur** à partir
du forfait décrit dans `quote`, via `KC_Pricing` (fichier `kc-pricing.php`).

### a) Installer la grille serveur
Copier `kc-pricing.php` dans le dossier du plugin `kc-booking`, et l'inclure
tout en haut du fichier principal :
```php
require_once __DIR__ . '/kc-pricing.php';
```

### b) Remplacer le bloc « Tarification » de `kc_rest_create_booking`
Repérer (vers la ligne 1526) le bloc qui commence par
`// ── Tarification ──` et se termine juste avant `$token = bin2hex(...)`.
Le remplacer par :

```php
    // ── Tarification (RECALCULÉE CÔTÉ SERVEUR — ne jamais croire le client) ──
    if (is_string($q_blob)) { $dec = json_decode($q_blob, true); if (is_array($dec)) $q_blob = $dec; }

    $total = null; $deposit = null; $payment_mode = 'none'; $requires_payment = false;
    $server_priced = false;

    // Si le devis décrit un forfait logement connu → prix officiel de la grille
    if (is_array($q_blob) && !empty($q_blob['booking']['forfait']) && class_exists('KC_Pricing')) {
        try {
            $devis        = KC_Pricing::compute_forfait($q_blob['booking']);
            $total        = $devis['total_cents'] / 100;
            $payment_mode = ($q_mode === 'full') ? 'full' : 'deposit';
            $deposit      = ($payment_mode === 'full') ? $total : $devis['acompte_cents'] / 100;
            $requires_payment = ($deposit > 0);
            $server_priced = true;
        } catch (InvalidArgumentException $e) {
            return new WP_Error('kc_bad_quote', 'Demande invalide : ' . $e->getMessage(), ['status' => 400]);
        }
    }

    // Sinon : prix de référence du type/variante (jamais le montant reçu du client)
    if (!$server_priced) {
        $requires_payment = ($type->cta_type === 'calendar_paid');
        if ($requires_payment) {
            $price        = ($variant && $variant->price_indicative !== null) ? (float) $variant->price_indicative
                          : ($type->price !== null ? (float) $type->price : null);
            $total        = $price;
            $dp           = max(0, min(100, (int) kc_opt('deposit_percent', 30)));
            $deposit      = $price !== null ? round($price * $dp / 100, 2) : null;
            $payment_mode = 'deposit';
        }
    }
```

Les variables `$q_total` / `$q_now` ne sont **plus utilisées** pour fixer le
prix : on peut les ignorer (la page continue de les envoyer, sans effet).
`kc_stripe_create_checkout($booking, $deposit)` reçoit donc toujours un montant
calculé serveur. **Rien d'autre à changer** : le reste de la fonction (création
en base, Stripe, emails) fonctionne tel quel.

### c) Vérifier la grille
Les `price_indicative` des variantes (`kc_booking_phase_1_2_seed_variants` :
Airbnb 55/80/120, déménagement 210/330/550) sont des **anciens montants** ;
ils ne servent plus que de repli si `quote` est absent. La vérité tarifaire est
désormais `kc-pricing.php` (Airbnb 45/60/75/95, déménagement 79/99/119/149).

## Correctif 2 — 🟠 `kc-sheet-sync` traite le webhook Stripe sans vérifier la signature

`kc_sheet_dispatch` (filtre `rest_post_dispatch`) re-décode le corps du webhook
et appelle `kc_sheet_on_paid` **même si la signature était invalide** : le
callback principal rejette (400), mais le filtre s'exécute quand même. Un
attaquant peut donc forger un faux `checkout.session.completed` pour marquer une
ligne « Payé » dans le Google Sheet et déclencher l'email « acompte reçu »
(pas d'impact sur la base, mais pollution + faux signal).

**Correctif** : dans `kc_sheet_dispatch`, pour la branche webhook, ne traiter
que si la signature est valide :

```php
elseif ($route === $ns . '/stripe/webhook' && $method === 'POST') {
    $secret = function_exists('kc_opt') ? kc_opt('stripe_webhook_secret') : '';
    $sig    = $request->get_header('Stripe-Signature');
    if ($secret && function_exists('kc_stripe_verify_sig')
        && !kc_stripe_verify_sig($request->get_body(), $sig, $secret)) {
        return $result; // signature invalide → on ignore
    }
    // … suite inchangée …
}
```

## Correctif 3 — 🟠 `kc-devis` calcule des prix périmés

`kc_devis_calculate_price` contient l'**ancienne grille inventée** (Airbnb
45–65 / 65–95 / 100–140, déménagement 180–240…), incohérente avec la grille
officielle. De plus, la page `/devis/` envoie désormais un format différent
(`kind`, `booking`, `estimation`). **Remplacer le handler `kc_devis` actuel par
`kc-devis-handler.php`** (fourni) : il lit le nouveau format, recalcule les
forfaits via `KC_Pricing`, échappe toutes les données client et n'invente aucun
prix pour les estimations pro/audit. Conserver l'`enqueue` qui injecte
`window.kcDevis = { ajaxUrl, nonce }` (le nonce attendu est `kc_devis`).

Détail mineur : remplacer `json_decode(stripslashes($payload_raw), true)` par
`json_decode(wp_unslash($payload_raw), true)` (plus sûr sur les apostrophes).

## Correctif 4 — type « Visite d'audit » + alignement (déjà fait)

`site/reservation.html` (table `TYPE_MAP`) mappe les 17 prestations du tunnel
vers les **slugs réels** du plugin. Les prestations à type dédié pointent
dessus (bureaux, commerces, parties-communes, fin-chantier, remise-etat) ; les
demandes vraiment diverses (sinistre, textile, vitres en hauteur, événement —
et les vitres, dont le type plugin est en mode « email » sans calendrier)
pointent vers un type générique **`visite-audit`**.

Ce type est créé automatiquement par l'extension **`kc-visite-audit.php`**
(fournie) : installer le ZIP → activer, rien à régler. Tant qu'il n'est pas
là, `reservation.html` se replie tout seul sur `remise-etat` puis sur le
premier type gratuit — le calendrier fonctionne dans tous les cas.

Installation : Extensions → Téléverser → activer `kc-visite-audit`. Le type
« Visite d'audit gratuite » (slug `visite-audit`, gratuit, 45 min, adresse
requise) apparaît dans kc-booking → Prestations. Affecter le personnel
habilité si besoin.

## Test de bout en bout (point 4.4 du suivi) — en mode test Stripe

1. `/devis/` → Airbnb 2 pièces + linge → 85 € → « Réserver mon créneau ».
2. `/reservation/` → créneau → acompte 25,50 € → Stripe test (carte `4242…`).
3. Vérifier : email client + admin, ligne Google Sheet (réf `KC-XXXX`),
   événement Google Agenda, lien « Gérer ma réservation » (`?resa=…`).
4. Parcours gratuit : `/devis/` → « Après sinistre » → visite d'audit →
   créneau → confirmation sans paiement.
5. **Test anti-fraude** (après correctif 1) : rejouer `POST /bookings` avec
   `amount_total: 1` → le montant Stripe doit rester celui de la grille.
