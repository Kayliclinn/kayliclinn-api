# Page Devis — Kayli Clinn

`devis-page.html` est le **bloc « HTML personnalisé »** à coller dans la page `/devis/`
de WordPress (wizard de demande de devis en plusieurs étapes).

C'est un bloc **autonome** : tout le HTML, le CSS et le JavaScript sont inclus.
Aucune dépendance hormis Google Fonts (chargé automatiquement).

> **Version : V3** — design épuré fidèle à la charte teal d'origine (#0FA7A5),
> cartes blanches avec badge d'icône vert clair, **sans photos**.

---

## 🎨 Charte & style

- Couleur principale **teal `#0FA7A5`** (+ variantes `--teal-dark`, `--teal-deep`,
  `--teal-light`, `--teal-soft`), définies en variables CSS sur `.kc-devis`.
- Typographies : **Montserrat** (titres), **Inter** (texte), **Roboto** (boutons).
- Cartes de prestation : fond blanc, **badge d'icône en carré arrondi vert clair**,
  accent teal sur le mot clé du titre.

Tout est encapsulé sous `.kc-devis` pour ne pas entrer en conflit avec le thème.

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
