# Page Devis — Kayli Clinn

`devis-page.html` est le **bloc « HTML personnalisé »** à coller dans la page `/devis/`
de WordPress (wizard de demande de devis en plusieurs étapes).

C'est un bloc **autonome** : tout le HTML, le CSS et le JavaScript sont inclus.
Aucune dépendance hormis Google Fonts (chargé automatiquement).

---

## ✨ Nouveauté V4 — Cartes de prestation avec photos

Chaque carte de prestation affiche désormais une **photo** (style élégant et pro) :

- Photo en haut de la carte + léger **zoom au survol**
- **Badge d'icône** en surimpression (effet verre dépoli)
- **État sélectionné** raffiné : anneau teal + pastille ✓, la photo reste visible
- **Fallback élégant** : si une image ne charge pas, un dégradé + icône s'affiche
  automatiquement (jamais d'image cassée)

---

## 🖼️ Changer les photos des cartes (le plus important)

Les photos sont **centralisées** en haut du `<script>`, dans l'objet
`KC_PRESTA_IMAGES`. **Une seule ligne à modifier par prestation.**

```js
const KC_PRESTA_IMAGES = {
  'bureaux':          'https://….jpg',
  'parties-communes': 'https://….jpg',
  'commerces':        'https://….jpg',
  'sensibles':        'https://….jpg',
  'fin-chantier':     'https://….jpg',
  'demenagement':     'https://….jpg',
  'remise-etat':      'https://….jpg',
  'decapage':         'https://….jpg',
  'vitres':           'https://….jpg',
  'airbnb':           'https://….jpg'
};
```

### Méthode recommandée — tes propres photos (idéal)

1. WordPress → **Médias** → **Ajouter** → téléverse ta photo.
2. Ouvre l'image → bouton **« Copier l'URL du fichier »**.
3. Colle l'URL à la place de l'exemple, pour la prestation concernée.

> 💡 **Conseils photos** : format paysage **~800×500 px**, fichier optimisé
> (JPG ou WebP, < 200 Ko). Des visuels cohérents (même ambiance/lumière)
> rendent la page beaucoup plus pro.

### Pas encore de photo pour une prestation ?

Mets une chaîne vide : `'vitres': ''`
→ la carte affiche automatiquement l'**icône stylée** sur un dégradé (élégant, jamais vide).

### ⚠️ À propos des photos par défaut

Les URLs livrées sont des **photos d'exemple Unsplash** (libres de droits), choisies
pour correspondre à peu près à chaque prestation. **Remplace-les par tes propres
visuels** dès que possible — c'est ce qui fera vraiment la différence côté image de marque.
Si une URL d'exemple ne se charge pas, le fallback icône + dégradé prend le relais.

---

## ⚙️ Autres réglages (objet `KC_CONFIG`)

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
