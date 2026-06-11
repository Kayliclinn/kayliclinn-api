# Analyse de sécurité des 4 extensions WordPress

Revue du 11/06/2026 — `kc-booking`, `kc-devis`, `kc-sheet-sync`, `kc-contact`.
Les correctifs concrets sont dans `INTEGRATION-kc-booking.md`.

## Synthèse

| Extension | État | Risque le plus important |
| --- | --- | --- |
| `kc-booking` | Solide, mais 1 faille critique | Prix accepté du client dans `POST /bookings` |
| `kc-sheet-sync` | Bien fait, 1 faille moyenne | Webhook Stripe traité sans vérif de signature |
| `kc-devis` | Fonctionnel, à réaligner | Grille de prix périmée + nouveau format de payload |
| `kc-contact` | Sain | RAS (bon exemple à suivre) |

## kc-booking (réservation) — le cœur

**Bien :** signature webhook vérifiée + idempotence ; routes publiques bien
isolées (`kc_booking_is_our_rest_route`) ; disponibilités robustes (Google
Agenda + base, buffers, délais, exclusion d'un agent dont l'agenda échoue) ;
SQL via `$wpdb->prepare`. Phases 3.1 et 3.2 **terminées** (contrairement au
document de suivi).

**🔴 Critique — fraude au prix.** `kc_rest_create_booking` retient
`$total = round((float) $q_total, 2)` : le montant vient du navigateur. Paiement
d'1 € possible pour n'importe quelle prestation. → Correctif 1 (recalcul serveur
via `KC_Pricing`).

**🟡 Mineur.** Le nom de produit Stripe est figé à « … — acompte » même en
paiement total ; le `price_indicative` des variantes est obsolète (sans impact
une fois le correctif 1 posé, car non utilisé pour les forfaits du devis).

## kc-sheet-sync (Google Sheet)

**Bien :** JWT compte de service propre, idempotence de l'email « acompte reçu »
(`transient kc_sheet_paid_*`), repli si la ligne n'existe pas, écriture Sheet
robuste (détection de l'onglet, échappement des quotes de plage A1).

**🟠 Moyen — webhook non authentifié.** `kc_sheet_dispatch` agit sur
`checkout.session.completed` sans revérifier la signature Stripe ; le filtre
s'exécute même quand le callback principal a rejeté l'événement. Spoofing
possible du Sheet + faux email admin. → Correctif 2.

**🟡 Mineur.** `kc_sheet_improve_pending_email` retrouve la réservation par
`client_email` + `status='pending'` la plus récente : si deux personnes portent
le même email au même moment, risque de croisement (très improbable, à garder en
tête).

## kc-devis (formulaire de devis)

**Bien :** nonce, honeypot, rate limiting, validation IDF, `esc_html` dans les
emails, page d'admin CRM (statuts new→won/lost) utile.

**🟠 Moyen — grille de prix périmée.** `kc_devis_calculate_price` reprend les
anciens prix inventés (Airbnb 45–65/65–95/100–140, déménagement 180–240…),
faux par rapport à la grille officielle. De plus la page `/devis/` envoie
maintenant `{kind, booking, estimation}`, que ce handler ne sait pas lire. →
Correctif 3 : remplacer par `kc-devis-handler.php` (recalcul via `KC_Pricing`).

**🟡 Mineur.** `json_decode(stripslashes(...))` → préférer `wp_unslash`.

## kc-contact (formulaire de contact)

**Sain.** Nonce, honeypot, rate limiting (3/h/IP), validation stricte (sujet en
liste blanche, téléphone FR, longueurs), `esc_html`/`esc_attr` sur toutes les
sorties d'email, IP derrière proxy gérée proprement, `$wpdb->insert` typé.
Aucune correction nécessaire — c'est le modèle à suivre pour les autres.

**🟡 Mineur.** `Reply-To` construit avec le nom + l'email du visiteur : un nom
contenant des retours-ligne pourrait théoriquement tenter une injection
d'en-tête, mais `sanitize_text_field` retire déjà les `\r\n`. Sans risque en
l'état.

## Remarques transverses

- **Expéditeur des emails.** Les plugins envoient depuis `contact@kayliclinn.fr`
  via `wp_mail` (WP Mail SMTP est en place d'après le suivi). Vérifier SPF/DKIM
  du domaine pour la délivrabilité.
- **RGPD.** `kc-contact` et `kc-devis` stockent IP + user-agent + coordonnées :
  prévoir une durée de conservation et la mention dans la politique de
  confidentialité (cf. checklist sécurité, points 19–20).
- **Idempotence Stripe.** Côté `kc-booking`, le webhook ne traite que les
  réservations `pending` : un même événement rejoué ne double pas le traitement.
  Bon. Garder ce garde-fou si le handler évolue.
