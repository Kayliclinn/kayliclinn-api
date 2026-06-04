# Page Devis — Kayli Clinn

`devis-page.html` est le **bloc « HTML personnalisé »** à coller dans la page `/devis/`
de WordPress (wizard de demande de devis en plusieurs étapes).

C'est un bloc **autonome** : tout le HTML, le CSS et le JavaScript sont inclus.
Aucune dépendance hormis Google Fonts (chargé automatiquement).

> **Version : V4** — design haut de gamme fidèle à la charte teal (#0FA7A5),
> fond clair : glassmorphism subtil, ombres douces multi-couches, accents
> dégradés teal, glow sur la carte sélectionnée, scroll reveal, barre de
> progression animée, soulignement animé du titre. **Photos sur les cartes
> de prestation** (Médiathèque WordPress). `prefers-reduced-motion` et
> fallbacks `@supports` (backdrop-filter) gérés.

---

## 🎨 Charte & style

- Couleur principale **teal `#0FA7A5`** (+ variantes `--teal-dark`, `--teal-deep`,
  `--teal-light`, `--teal-soft`), définies en variables CSS sur `.kc-devis`.
- Typographies : **Montserrat** (titres), **Inter** (texte), **Roboto** (boutons).
- Cartes de prestation : **photo en haut** + badge d'icône teal frosté, corps en
  verre dépoli, accent teal sur le mot clé du titre, glow teal sur la sélection.

Tout est encapsulé sous `.kc-devis` pour ne pas entrer en conflit avec le thème.

---

## 🖼️ Photos des cartes (objet `KC_PRESTA_IMAGES`, en haut du `<script>`)

Une ligne par prestation. Les URLs pointent vers la **Médiathèque WordPress**
(`kayliclinn.fr`) → même origine, donc fiables (pas de blocage externe).

- **Changer une photo** : Médias → ouvre l'image → « Copier l'URL du fichier » →
  colle-la à la place de l'ancienne.
- **Pas de photo** : laisse `''` (vide) → la carte affiche l'icône stylée sur un
  dégradé teal (fallback élégant). Idem si une image ne charge pas.
- À compléter : **`commerces`** est en fallback (vide) — ajoute son URL quand prête.
- Format conseillé : paysage, ~800×550 px, JPG/WebP optimisé.

---

## ⚙️ Réglages (objet `KC_CONFIG`, en haut du `<script>`)

| Clé              | Rôle                                                        |
|------------------|-------------------------------------------------------------|
| `reservationURL` | Page vers laquelle redirigent les CTA Acuity / Stripe       |
| `acuityURL`      | URL de ton agenda Acuity (`owner=XXXXXXX` à compléter)      |
| `stripeLinks`    | Liens de paiement Stripe (Airbnb, Déménagement)             |
| `sessionExpiry`  | Durée de sauvegarde auto du formulaire (24h par défaut)     |

L'envoi du formulaire fonctionne via **WordPress AJAX** si `window.kcDevis`
(`ajaxUrl` + `nonce`) est défini ; sinon le bloc passe en **mode simulation**
(succès affiché, données loguées en console) — pratique pour tester la mise en page.

---

## 🧩 Intégration WordPress

1. Édite la page `/devis/` avec l'éditeur de blocs.
2. Ajoute un bloc **« HTML personnalisé »**.
3. Colle l'intégralité de `devis-page.html`.
4. Mets à jour / Publie.

Le bas du fichier contient un complément qui masque le logo en double et cale
le bloc juste sous l'en-tête du thème — à garder tel quel.

---

## ✅ Fonctionnalités

- Parcours en **8 étapes** adapté à chacune des 10 prestations
- Validation des champs (email, téléphone FR, code postal Île-de-France)
- **Sauvegarde automatique** (sessionStorage) qui survit aux rafraîchissements
- Estimation de prix détaillée + CTA (email / Acuity / Stripe selon la prestation)
- **Accessibilité** WCAG AA (ARIA, focus trap, navigation clavier)
- Honeypot anti-spam, `prefers-reduced-motion`, styles d'impression
