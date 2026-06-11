# Intégration WordPress — sécuriser et finir le tunnel de réservation

Tout le tunnel (devis → réservation → paiement) passe par WordPress :
extensions `kc-booking`, `kc-devis`, `kc-sheet-sync`. Ce dossier contient le
code à intégrer dans ces extensions. **À faire via Claude Code en local sur le
code source des extensions** (méthode du document de formation : modification
locale → ZIP → Extensions → Téléverser), ou bloc par bloc dans le chat.

## 1. `kc-pricing.php` — la grille officielle côté serveur 🔴 priorité sécurité

**Pourquoi.** La page `/reservation/` envoie `amount_total` / `amount_now` à
`POST /bookings`, mais un fraudeur peut appeler l'API directement avec un
montant de 1 €. Le plugin doit donc **recalculer** le prix à partir du champ
`quote` (JSON) et ignorer les montants reçus.

**Comment.**
1. Copier `kc-pricing.php` dans `kc-booking/includes/` et le `require_once`.
2. Dans le handler de `POST /bookings`, pour un type `calendar_paid` :

```php
$quote = json_decode( (string) $request['quote'], true );
try {
    $booking_data = is_array( $quote['booking'] ?? null ) ? $quote['booking'] : array();
    $devis        = KC_Pricing::compute_forfait( $booking_data );
    $mode         = ( 'full' === $request['payment_mode'] ) ? 'full' : 'deposit';
    $amount_cents = ( 'full' === $mode ) ? $devis['total_cents'] : $devis['acompte_cents'];
    // → utiliser $amount_cents pour la session Stripe (unit_amount),
    //   et $devis['total_cents'] / $devis['solde_cents'] pour l'enregistrement.
    //   NE PAS utiliser $request['amount_total'] ni $request['amount_now'].
} catch ( InvalidArgumentException $e ) {
    return new WP_Error( 'kc_bad_quote', 'Demande invalide : ' . $e->getMessage(), array( 'status' => 400 ) );
}
```

3. Idempotence (règle du projet) : avant de traiter un webhook Stripe,
   vérifier que l'événement (`event->id`) n'a pas déjà été traité
   (le stocker en base ou en option), pour ne jamais confirmer deux fois.

## 2. `kc-availability-endpoint.php` — Phase 3.2 (créneaux) 🔴 bloquant

Implémentation de référence de `GET /availability`, au format exact attendu
par la page `/reservation/`. Trois « POINTS D'INTÉGRATION » à brancher sur
l'existant de `kc-booking` : durées des variantes, horaires réels du
personnel, occupations (réservations + Google Agenda). Des valeurs par défaut
permettent de tester le parcours avant ce branchement.

## 3. `kc-devis-handler.php` — envoi du devis par email

Version durcie du handler admin-ajax `kc_devis` : nonce, honeypot, limitation
de débit, validation, **échappement HTML de toutes les données client**
(anti-hameçonnage), prix des forfaits recalculés via `KC_Pricing`. La page
`/devis/` n'affiche plus jamais de faux succès : si `window.kcDevis` n'est pas
injecté par le plugin, elle informe honnêtement le client.

L'extension doit injecter sur la page `/devis/` :

```php
wp_localize_script( $handle, 'kcDevis', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'kc_devis' ),
) );
```

## 4. Côté admin kc-booking — 2 réglages à faire (sans code)

1. **Créer le type « Visite d'audit »** : slug `visite-audit`, mode
   `calendar_free`, durée ~45 min, tout le personnel. C'est le type utilisé
   par **toutes** les prestations sur mesure du tunnel (sinistre, textile,
   containers, événement…) — un seul calendrier, zéro catalogue à rallonge.
   C'est la réponse au besoin « les prestations non estimables réservent un
   audit sur le même calendrier ».
2. **Vérifier les slugs** des types existants face à la table `TYPE_MAP` en
   haut du script de `site/reservation.html` (airbnb, demenagement, bureaux,
   commerces, parties-communes, vitres, fin-chantier, remise-etat). Si un slug
   diffère, ajuster `TYPE_MAP` — une ligne à modifier.

## 5. Correspondance devis → kc-booking (déjà câblée côté pages)

| Tunnel de devis | Type kc-booking | Paiement |
| --- | --- | --- |
| Airbnb | `airbnb` | Acompte 30 % / total (Stripe) |
| Déménagement · standard · logement vide | `demenagement` | Acompte 30 % / total (Stripe) |
| Bureaux · locaux | `bureaux` | Visite gratuite |
| Commerces · vitrines | `commerces` | Visite gratuite |
| Copropriétés | `parties-communes` | Visite gratuite |
| Vitres (toutes) | `vitres` | Visite gratuite |
| Fin de chantier | `fin-chantier` | Visite gratuite |
| Maison · grand ménage | `remise-etat` | Visite gratuite |
| Tout le reste (sinistre, textile, syndic, événement…) | `visite-audit` | Visite gratuite |

Le devis complet voyage dans le champ `quote` de la réservation : l'admin et
le Google Sheet (kc-sheet-sync) gardent ainsi le détail exact de la demande,
même quand le type kc-booking est générique.

## 6. Test de bout en bout (point 4.4 du document de suivi)

En mode test Stripe (carte `4242 4242 4242 4242`) :
1. `/devis/` → Airbnb 2 pièces + linge → tarif 85 € → « Réserver mon créneau » ;
2. `/reservation/` → créneau → acompte 25,50 € → paiement Stripe test ;
3. Vérifier : email admin + client, ligne Google Sheet, événement Agenda,
   lien « Gérer ma réservation » (`?resa=…`) → payer le solde / annuler ;
4. Refaire en parcours gratuit : `/devis/` → « Après sinistre » → visite
   d'audit → créneau → confirmation sans paiement ;
5. Tester la fraude : rejouer le `POST /bookings` avec `amount_now: 1` —
   le montant encaissé doit rester celui de la grille (preuve que le
   recalcul serveur fonctionne).
