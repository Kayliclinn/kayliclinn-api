# Design System — Kayli Clinn
Document de référence figé. Toutes les pages du site et tout code produit
(par Claude ou Claude Code) doivent s'y conformer. En cas de doute, ce document fait foi.
Dernière décision : standard typographique = Fraunces + Inter ; teal de référence = #0FA7A5.

---

## 1. Couleurs

| Rôle | Valeur | Usage |
|---|---|---|
| Navy (dominant) | `#0D2340` | Titres, texte fort, fonds sombres, éléments structurants |
| Navy clair | `#1a3a5c` | Dégradés de navy, surfaces sombres secondaires |
| Teal (accent) | `#0FA7A5` | **Accent uniquement** : boutons, liens, états interactifs, sélection |
| Teal foncé | `#0B8483` | Survol des éléments teal, texte teal sur fond clair |
| Teal profond | `#076E6D` | Icônes, micro-accents |
| Teal très clair | `#E6F5F5` | Fonds d'icônes, surfaces d'accent légères |
| Fond de page | `#F7F8FA` | Arrière-plan général |
| Blanc | `#FFFFFF` | Cartes, surfaces |
| Gris texte | `#5b6b7d` / `#6B7280` | Texte secondaire |
| Bordure | `#E5E7EB` | Bordures de cartes et champs |

**Règle d'or du teal** : accent seulement, jamais en aplats partout, jamais en dégradés
teal sur de grandes surfaces. Le navy domine, le teal ponctue.
L'or est réservé au logo et aux outils internes — jamais sur le site client.

---

## 2. Typographie

| Élément | Police | Graisse | Notes |
|---|---|---|---|
| Titres (h1, h2, titres de section) | **Fraunces** (serif) | 500–600 | Le mot accentué passe en teal #0FA7A5 |
| Texte courant, descriptions, labels | **Inter** | 400–500 | Corps de page |
| Boutons | **Roboto** | 600 | Libellés d'action |

Import Google Fonts :

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@300;400;500;600;700&family=Roboto:wght@500;600;700&display=swap" rel="stylesheet">
```

Note : le tunnel d'estimation (`/devis/`) a été **migré vers Fraunces** (titres) — corps en
Inter, boutons en Roboto, conforme au standard. Migration réalisée d'un seul tenant sur tout le tunnel.

---

## 3. Boutons

- **CTA principal** : fond teal `#0FA7A5`, texte blanc, Roboto 600, coins arrondis (~10px),
  survol → teal foncé `#0B8483`. Libellés selon contexte :
  - Hero / CTA final : « Obtenir mon estimation »
  - Section tarif : « Estimer mon [service] »
  - Fin de chantier (audience BTP) : « Réserver mon audit »
- **CTA secondaire** : fond blanc, bordure grise, texte navy, survol → bordure teal.
- Pas d'animation qui pulse ou tremble. Transitions douces uniquement.

---

## 4. Cartes & surfaces

- Fond blanc, bordure `1.5px #E5E7EB`, coins arrondis (12–18px).
- Survol : léger soulèvement + bordure teal. Pas d'effet néon, pas de glow agressif.
- Photos en haut de carte, ratio cohérent (4/3 desktop). Variables CSS `--photo-*` en haut du bloc.
- Icônes : SVG trait fin, jamais d'emoji.

---

## 5. Anti-modèles interdits (« ça sent l'IA »)

- Dégradés teal partout / sur grandes surfaces.
- Animations qui pulsent, tremblent, clignotent.
- Gros chiffres délavés en fond.
- Grilles de cartes génériques sans hiérarchie.
- Emojis en guise d'icônes.

---

## 6. Architecture WordPress (rappel technique)

- Le **thème** gère header / footer / logo. Les blocs HTML personnalisés ne les recréent jamais.
- Chaque bloc = préfixe CSS unique (ex. `kcfc-`, `kcidf-`, `kcfold-`) pour éviter le débordement de styles.
- Pas de FTP : déploiement par ZIP / collage dans l'éditeur de blocs.

---

## 7. Chantiers de mise en cohérence (ordre recommandé)

L'unification ne se fait pas page par page au hasard. Ordre proposé :

1. **Figer ce document** (fait) — le standard de référence.
2. **Recenser les pages** et leur écart au standard (audit page par page).
3. **Migrer le tunnel estimation/devis** de Montserrat → Fraunces (chantier d'un tenant) — **fait**.
4. **Aligner les pages anciennes** sur le gabarit Fraunces + Inter.
5. **Vérifier la cohérence transversale** : mêmes boutons, mêmes couleurs, mêmes espacements partout.

À chaque chantier : modifications chirurgicales, préfixe CSS unique, validation avant publication.

---

## 8. Points à valider par la propriétaire (métier)

Ces éléments ne sont pas des décisions de design — ils reviennent à Tatiana :

- Tarifs, règles de facturation, délais, contenu commercial.
- Témoignages : remplacer les exemples (badge ambre « Exemple ») par de vrais avis autorisés
  avant publication (obligation DGCCRF — pas de faux avis).
- Mentions de partenariat / sous-traitance pour les interventions spécialisées.
