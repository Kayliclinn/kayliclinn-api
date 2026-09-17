# Le site LKH réel, et ce qu'il change pour kayliclinn.fr

Rapport du 17 septembre 2026. Il repose sur une extraction du front-end public de `lkh.lucassistant.com` (fichiers HAR fusionnés, dossier `LKH_frontend_public_v3`), vérifiée en neuf axes par des relecteurs indépendants. Chaque valeur chiffrée cite son fichier de preuve. Les constats jugés inexacts par les vérificateurs sont repris ici dans leur forme corrigée. Les propositions pour Kayli Clinn sont toujours marquées « proposition » et ne contiennent aucune donnée métier nouvelle.

Il s'adresse à deux lectrices. La propriétaire y trouve les décisions à prendre (sections 1, 6 et 7). La personne qui code y trouve les valeurs exactes et les preuves (sections 2 à 5 et 8).

---

## 1. En une page

### Ce qu'est vraiment le site LKH

Le site en ligne n'est pas un ensemble de blocs HTML collés dans WordPress. C'est une application web complète, très probablement Laravel côté serveur (balisage `@vite` sous `/build/assets`, balise `<title inertia>`), avec Inertia.js v2 et Svelte 5 côté navigateur, rendue côté serveur puis hydratée. Preuve dans `public_exact/index.html` (`<script data-page="app" type="application/json">`, `<div data-server-rendered="true" id="app">`) et dans `build/assets/app-rFsEXPAO.js` (en-têtes `X-Inertia-Except-Once-Props`, `__vite__mapDeps`).

Le bundle principal déclare 21 pages. Sept seulement ont été capturées. Trois en HTML (accueil, À propos, Réalisations) et quatre en réponses JSON (catalogue `/nos-prestations`, estimateur `/estimer/peinture-interieur`, panier `/estimer/panier`, finalisation `/estimer/finaliser`). Les six pages d'administration, l'espace de suivi prospect, la page de confirmation, les pages catégorie et les 19 autres estimateurs ne sont pas dans l'extraction.

L'estimateur est un tunnel en quatre écrans. Un catalogue de 20 prestations en 5 catégories. Un assistant par prestation (sept questions empilées pour la peinture, seule prestation capturée). Un panier conservé dans le navigateur (`localStorage`, clé `lkh.quote.v5`). Une finalisation en sept étapes qui se termine par un code à six chiffres envoyé par e-mail, puis un envoi au serveur qui ne contient jamais le prix calculé. Il n'y a pas de paiement.

### Les cinq faits qui changent ce qu'on croyait

1. **L'ancienne charte (avant le 17/09) ne décrivait pas le site réel.** `CHARTE-LKH.md` avait été écrite à partir de fichiers de refonte (dont `cd11705a-apropos.html`), qui sont des maquettes WordPress jamais mises en ligne. Fraunces, l'anthracite `#19191a`, l'or `#bf9e33`, les boutons pilule, les sections de 60 ou 82 px, les cartes à 20 px et les douze blocs de page prestation viennent de ces maquettes. Aucun fichier de l'extraction ne contient le mot « Fraunces ». Le site réel utilise Libre Baskerville, Inter et JetBrains Mono, une encre brun-noir `#171412` et un or `#c9a227` (`build/assets/app-BDw0yY9K.css`, bloc `:root`).

2. **Le site réel est plus animé et plus dense que la charte ne l'autorise.** La charte interdit halos, dégradés et carrousels automatiques. Le site réel a un halo doré sur le fond de page, deux sections à halos, un CTA final à halo, un défilement automatique d'avis (48 s), des onglets qui avancent seuls (5,2 s), une frise animée (9 s), un comparateur avant/après qui balaie seul, une carte inclinée à −1,5° et des tuiles 3D. Les animations automatiques sont coupées en `prefers-reduced-motion` (contrôles JavaScript dans `BeforeAfter-B_o3YYsk.js`, `FinalCta-BQXcIU9S.js` et `Home-BEyYY4bd.js`, 9 blocs CSS au total), mais les transitions au survol de quatre composants ne le sont pas (section 2.10).

3. **Le prix est calculé dans le navigateur et n'est jamais transmis.** La formule peinture est lisible dans `build/assets/floorAreaParam-85nDwDiL.js`. Pour 50 m² au sol en finition velours, elle donne 3 605 € HT, valeur qui figure telle quelle dans le HTML rendu de l'accueil. À la finalisation, chaque ligne du panier est réduite à `{serviceSlug, state}` (`build/assets/Checkout-CNz-78om.js`, fonction `ln`). Le serveur recalcule vraisemblablement, mais rien dans l'extraction ne le prouve. Chez Kayli Clinn, `KC_Pricing` fait déjà foi côté serveur. C'est un point sur lequel le site Kayli Clinn est en avance.

4. **Les pages prestation éditoriales en douze blocs n'existent pas.** Le site réel enchaîne un catalogue de cartes, un assistant par prestation, un panier et une finalisation. Les blocs « inquiétude, notre réponse », « une règle sans exception », « votre situation ressemble à », la bande sombre « visite » et la section suivi à citation viennent de la refonte. À propos n'a ni hero photo, ni FAQ, ni données structurées.

5. **Le site capturé est une préproduction.** Les trois pages HTML portent `<html lang="en">` et `<meta name="robots" content="noindex, nofollow">` (ligne 2 et ligne 15 de chaque fichier). Les délais annoncés (« Un prix en 5 minutes », « démarrage sous 2 semaines », « sous 48 h ») et les avis (« 5/5 », « 6 avis Google ») sont des données LKH. Aucun de ces chiffres ne doit être recopié.

### La décision à prendre

Le site LKH réel apporte une chose utile à Kayli Clinn. Un rythme de page, un jeu de composants et une manière d'écrire qui se transposent sans rien inventer. Il apporte aussi une architecture qui ne se transpose pas. Kayli Clinn reste sur WordPress.com, avec un bloc HTML par page, le tunnel `/devis/` puis `/reservation/`, Stripe et le recalcul serveur par `KC_Pricing`.

Vous avez trois décisions à prendre avant que quiconque code.

- **Le système graphique.** Garder navy et teal, c'est acquis. Reste à choisir la police de titres (Fraunces, déjà autorisée pour l'éditorial, ou une autre) et une police monospace pour les surtitres et les petits prix, car JetBrains Mono n'est pas dans la charte (section 7, question 1 et 2).
- **La forme des pages prestations.** Option A, un catalogue de cartes avec un bouton par prestation, fidèle au site réel. Option B, des pages éditoriales réduites aux composants réels. Les 21 pages de `direction-epuree/` et `commerces-retail.html` ont été construites sur la structure de la refonte, pas sur celle du site réel (section 6).
- **Les données à fournir.** Prestations phares, délais affichables, horaires de rappel, année de fondation, dirigeante, assureur, avis Google réels, photos avant/après avec accord des clients (section 7).

---

## 2. Le système de design réel

Tout ce qui suit vient de `build/assets/app-BDw0yY9K.css` sauf mention contraire. Les tailles en px sont converties depuis les rem à 16 px de base. Le site ne redéfinit pas la taille de base (aucun `font-size` sur `html` ni `:root`).

### 2.1 Polices par rôle

Trois familles auto-hébergées en woff2, toutes avec `font-display:swap` (30 règles `@font-face`). Seules six faces sont chargées. Libre Baskerville 400 et 700, Inter 400 et 600, JetBrains Mono 400 et 800. Aucune italique n'est chargée. Les graisses demandées sans face sont rabattues par le navigateur. Inter 700 et 800 tombent sur 600 sans gras synthétique. JetBrains Mono 700 tombe sur 800. Inter 500 tombe sur 400.

| Rôle | Famille | Taille (16 px de base) | Graisse | Interlettrage | Interligne | Preuve |
| --- | --- | --- | --- | --- | --- | --- |
| Corps | Inter | 16 px (`1rem`) | 400 | — | 1,6 | `body{font-size:1rem;line-height:1.6}` |
| h1 (hero accueil) | Libre Baskerville | `clamp(2rem, 4vw + 1rem, 3.5rem)` = 32 à 56 px | 700 | −0,01 em | 1,2 | `h1{…}` + règle groupée `h1,h2,h3,h4,h5,h6{line-height:1.2}` ; hero blanc, ombre `0 2px 30px #00000059`, `max-width:22ch` (`Home-5XXTBcEd.css`) |
| h1 (pages intérieures) | Libre Baskerville | `clamp(2rem, 4vw, 3.25rem)` = 32 à 52 px | 700 | −0,01 em (hérité du `h1` global) | 1,15 | `.page-head h1{…font-size:clamp(2rem,4vw,3.25rem);font-weight:700;line-height:1.15}` ; `.page-head` utilisé par `a-propos/index.html` et `realisations/index.html` |
| h2 de tête de section | Libre Baskerville | `clamp(1.5rem, 2vw + 1rem, 2.25rem)` = 24 à 36 px | 700 (gras navigateur, aucune graisse déclarée) | — | 1,2 | `h2{font-size:clamp(…)}` |
| h2 de projet | Libre Baskerville | 24 px (`1.5rem`) | 700 | — | 1,2 | `.project-title` |
| h2 du pied de page | Inter | 14,1 px (`.88rem`) | 700 | — | — | `SiteFooter-C94XINEC.css` |
| Question d'étape (estimateur) | Libre Baskerville | `clamp(1.75rem, 3vw, 2.5rem)` = 28 à 40 px | 700 | — | — | `.estimator-wizard-step-question` |
| h3 de carte phare | Libre Baskerville | 23,2 px (`1.45rem`) | 700 | — | 1,2 | `.feat-card h3` |
| h3 de carte prestation | Libre Baskerville | 22 px (`1.375rem`) | 700 | — | 1,2 | `.service-card-title` |
| h3 de panneau d'étape | Libre Baskerville | 21,6 px (`1.35rem`) | 700 | — | 1,2 | `.stage h3` (`Home-5XXTBcEd.css`) |
| h3 de niveau de suivi | Libre Baskerville | 18 px (`1.125rem`) | 700 | — | 1,3 | `TrackingLevels-DUzCanPm.css`, `.levels h3{…line-height:1.3}` |
| h3 de principe | Libre Baskerville | 17,6 px (`1.1rem`) | 700 | — | 1,3 | `About-DLPoKGHW.css`, `.principle h3{…line-height:1.3}` |
| Le `h3{font-size:1.5rem}` global (24 px) n'est effectif sur aucune page capturée. | | | | | | |
| Chapeau de section (`.lede`) | Inter | 17,6 px (`1.1rem`) | 400 | — | 1,7 | `.lede{color:var(--muted);max-width:60ch}` |
| Chapeau de page intérieure | Inter | 17 px (`1.0625rem`) | 400 | — | 1,7 | `.page-head .lede{color:var(--ink-2);max-width:none}` |
| Chapeau du hero | Inter | 17,6 px | 400 | — | 1,7 | `.hero .lede` blanc `#ffffffeb`, `max-width:52ch`, `strong` doré 600 |
| Surtitre éditorial | JetBrains Mono | 12,5 px (`.78rem`) | 600 demandé, rendu 800 (seule face supérieure disponible ; la face 600 n'existe pas) | 0,08 em, capitales | — | `.eyebrow.is-editorial`, tiret `:before` 18×1 px `--accent`, gap 10 px |
| Surtitre pilule | JetBrains Mono | 11 px | 700 (rendu 800) | 0,18 em, capitales | — | `.eyebrow`, bord 1 px `--rule`, fond `--paper`, rayon 999 px, point 6 px ; un seul usage, « Prix indicatif » du récapitulatif |
| Kicker de carte phare | JetBrains Mono | 11,5 px (`.72rem`) | 400 (aucune graisse déclarée) | 0,08 em, capitales | — | `.feat-card .kicker{…letter-spacing:.08em;…font-size:.72rem}` |
| Kicker de la carte du hero | JetBrains Mono | 10,9 px (`.68rem`) | 700 (rendu 800) | 0,14 em, capitales | — | `.kicker.svelte-1o4w8lg` (`Home-5XXTBcEd.css`) |
| Tag de projet | JetBrains Mono | 11 px | 400 (aucune graisse déclarée) | 0,15 em, capitales | — | `.project-tag` |
| Tag avant/après | JetBrains Mono | 10 px | 600 | 0,18 em, capitales | — | `.compare-tag{…font-size:10px;font-weight:600}` |
| Jalon de frise | JetBrains Mono | 10,6 px (`.66rem`) | 700 | 0,1 em, capitales | — | `.milestone` (`Home-5XXTBcEd.css`) |
| Bouton | Inter | 14,7 px (`.92rem`) | 600 | — | 1 | `.btn-primary,.btn-secondary,.btn-ghost` |
| Grand bouton | Inter | 16 px | 600 | — | 1 | `.btn-lg` |
| Pilule du hero | Inter | 13,8 px (`.86rem`) | 500 (rendu 400) | — | — | `.hero-pills .pill` |
| Badge | Inter | 12,5 px (`.78rem`) | 600 | — | — | `.badge` |
| Fil d'Ariane | Inter | 13 px (`.8125rem`) | 400 | — | — | `.breadcrumb ol`, couleur `--muted` ; composant `Breadcrumb-BBPi1Woc.js` (`<nav aria-label="Fil d'Ariane"><ol>`, séparateur `<li aria-hidden="true" class="sep">›</li>`, dernier item `<span aria-current="page">`, variante `on-dark`) |
| Navigation d'en-tête | Inter | 14,7 px | 500 | — | — | `SiteHeader-CpDtGUgc.css` |
| Nom de marque, baseline | Inter | 16 px et 10,9 px (`.68rem`) | 700 et 800 (rendus 600) | baseline 0,18 em capitales, dorée | — | `SiteHeader-CpDtGUgc.css` |
| Pied de page | Inter | 13,6 px (`.85rem`) | 400 | — | — | `SiteFooter-C94XINEC.css` |
| Grand prix (récapitulatif) | Libre Baskerville | `clamp(3rem, 8vw, 5rem)` = 48 à 80 px | 400 hérité (aucune graisse déclarée) | — | 1 | `.recap-hero-price`, couleur `--accent` |
| Prix héros (composant `PriceValue`) | Libre Baskerville | `clamp(3rem, 8vw, 5rem)` = 48 à 80 px | 400 hérité | — | 1 | `.hero{font-family:var(--font-display);color:var(--accent);…display:block}` (`PriceValue-BkodCNG0.css`) ; variante `.ht` en mono couleur `--ink` ; importé par `Cart`, `Catalog`, `Checkout` |
| Grand montant du panier et de la finalisation | Libre Baskerville | `clamp(2rem, 5vw, 3rem)` = 32 à 48 px | 400 hérité | — | — | `.ht-line` (`PriceBreakdown-DvF3tBj3.css`), couleur `--ink` ; importé par `Cart-Lb1KgKps.js` et `Checkout-CNz-78om.js` |
| Liste d'options d'une ligne | JetBrains Mono | 13 px (`.8125rem`), 12 px (`.75rem`) en `size-sm` | 400 | — | — | `.options-list` (`OptionsList-B8IFGGAz.css`), couleur `--muted`, gap 4 px |
| En-tête collant du tunnel | Inter | nom 14,1 px (`.88rem`), baseline 9,3 px (`.58rem`) | 700 et 800 (rendus 600) | nom 0,025 em, baseline 0,18 em capitales dorée | 1 | `header.estimator` (`EstimatorHeader-BVhbMLt4.css`), collant, `min-height:56px`, `-webkit-backdrop-filter:blur(8px)`, fond `--glass-bg`, filet bas `--rule`, logo 24 px de haut ; nom et baseline masqués sous 720 px (`@media (width<=719px){.brand-text{display:none}}`) |
| Montant de la carte hero | Libre Baskerville | 30,4 px (`1.9rem`) | 400 | −0,01 em, `tabular-nums` | 1,1 | `.amount` (`Home-5XXTBcEd.css`) |
| Petits prix d'option | JetBrains Mono | 12 à 14 px | 400 | — | — | `.estimator-page .lkh-radio-price` 12 px, `.service-card-price` 14 px |
| Montant de ligne, total | JetBrains Mono ou Inter | 15 à 18 px | 600 à 800 | — | — | `.line-price` 15 px, `.panel-total-value` 18 px, `.recap-total` Inter 18 px 700 |
| Notes secondaires | Inter | 13 à 14 px | 400, oblique synthétique | — | — | `.recap-note`, `.cart-note`, `.astuce-note` |
| Grand nombre du curseur (surface) | Libre Baskerville | 48 px (`3rem`) | 400 hérité | — | 1 | `.lkh-slider-number{font-family:var(--font-display);color:var(--ink);font-size:3rem;line-height:1}` (`Slider-LuvDsh02.css`) |
| Code OTP (champ à six chiffres) | JetBrains Mono | 28 px (`1.75rem`) | 400 (`font:inherit`) | 0,5 em, `text-indent:.5em`, centré | — | `.code-input` (`CodeInput-CyYVlEjc.css`, importé par `Checkout-CNz-78om.js`), bord `--rule`, rayon `--r-md`, `max-width:18rem`, `:focus-visible{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}` |

Deux règles d'emphase à retenir. `em{color:var(--accent);font-style:normal}` rend tout `em` doré et droit. `em.accent` ajoute seulement `font-weight:700`. Aucun titre capturé n'est en italique. La règle `.page-hero h1 em{font-style:italic}` existe mais aucune page ne l'utilise.

### 2.2 Palette, et l'équivalence proposée pour Kayli Clinn

Le `:root` de `app-BDw0yY9K.css` est le seul bloc de couleurs du site. Pas de mode sombre, pas de thème alternatif.

| Jeton LKH | Valeur | Rôle observé | Équivalent Kayli Clinn (proposition) |
| --- | --- | --- | --- |
| `--ink` | `#171412` brun-noir | texte, titres | navy `#0D2340` |
| `--ink-2` | `#2f2822` | texte secondaire, chapeaux intérieurs | `#24384F` (navy éclairci, proposition) |
| `--muted` | `#5f5850` | chapeaux, notes | `#5B6B7D` (déjà dans la charte) |
| `--rule` | `#e8e2d2` beige | filets, bordures 1 px (79 déclarations `1px solid var(--rule)`, 108 usages du jeton au total dans les CSS) | `rgba(13,35,64,.10)` ou `#E1E7EE` (proposition, voir ci-dessous) |
| `--surface-muted` | `#e8e6e0` | fonds neutres | `#EEF2F6` (proposition) |
| `--paper`, `--bg` | `#fff` | cartes, fond de page | blanc |
| `--accent` | `#c9a227` or | fonds de boutons pleins, bords et anneau de sélection, focus, gros prix, tout `em`, texte doré sur fond sombre | teal `#0FA7A5` |
| `--accent-2` | `#9a7a1c` or foncé | texte doré sur clair (surtitres, liens de carte), fond des boutons au survol, socle d'ombre des boutons | teal foncé `#076E6D` |
| `--accent-soft` | `#fbf7ee` | fonds d'attente des médias, encarts, sections teintées, états sélectionnés | teal pâle `#EAF6F5` (proposition) |
| `--ink-surface` | `#171412` | pied de page et section « Qui nous sommes », seules zones sombres pleines | navy `#0D2340` |
| `--on-ink` / `--on-ink-strong` / `--on-ink-rule` | `#ffffffad` / `#fff` / `#ffffff1f` | texte, titres, filets sur fond sombre | mêmes valeurs |
| `--glass-bg` | `#ffffffeb` | en-tête en verre | même valeur |
| `--ok` / `--danger` | `#2f6b4f` / `#8a2020` | badge disponibilité, erreurs, bouton du dialogue de confirmation | même logique, valeurs à fixer |
| `--danger-bg` / `--danger-border` | `#fff4f4` / `#f6c5c5` | fond et bord des encarts d'erreur | même logique, valeurs à fixer |
| `--lkh-grey` | `#8a8580` | registre RCS/SIRET du pied de page (`.registry`, `SiteFooter-C94XINEC.css`) | gris neutre à fixer |
| `--photo` / `--photo-soft` | `#2e6f85` / `#e9f2f6` | pastille « photo » (seul accent froid du site) | inutile chez Kayli Clinn |
| `--report` / `--report-soft` | `#8a5a2b` / `#f6efe6` | pastille « compte rendu » | inutile chez Kayli Clinn |
| `--check` / `--check-soft` | `#2f6b4f` / `#eaf3ee` | troisième pastille, « check » (`.deliverable.is-check`, `FinalCta-DOUYX8pg.css`) | vert à fixer si la pastille est reprise |
| `--ease-out` / `--ease-in-out` | `cubic-bezier(.2,.7,.2,1)` / `cubic-bezier(.5,.05,.2,1)` | courbes de transition | mêmes valeurs |
| `--z-base` … `--z-overlay` | 0 / 1 / 10 / 30 / 40 / 50 | plans (`base`, `raised`, `dropdown`, `popover`, `sticky`, `overlay`) | mêmes valeurs |

**Neutres chauds ou froids.** Je propose des neutres froids. La raison est dans la construction même de la palette LKH. Tous ses neutres (encre, filets, fonds) sont teintés par l'encre brun-noir et par l'or. Ils ne sont pas gris, ils sont « bruns très clairs ». Le principe à reprendre n'est pas la couleur, c'est la règle « les neutres sont teintés par l'encre ». Chez Kayli Clinn, l'encre est navy. Un filet beige `#e8e2d2` sous un titre navy et un bouton teal jurerait. Un filet proche de navy à 10 % d'opacité sur blanc (soit `#E7E9EC` exactement ; `#E1E7EE` en est une approximation à 12,4 / 10,9 / 8,9 % selon le canal) joue le même rôle que `#e8e2d2` chez LKH. Le plus simple est de reprendre directement `rgba(13,35,64,.10)`, ce que fait déjà `commerces-retail.html` avec `--line: rgba(13,35,64,.10)`.

Deux détails de fond à garder. Le fond de page LKH n'est pas plat, il porte un halo radial très discret (`body{background:radial-gradient(circle at 20% 10%, #c9a2270f 0%, transparent 60%), var(--bg)}`, dernière règle du fichier, 6 % d'opacité). Et les états sélectionnés combinent toujours bord accent, fond accent pâle et un anneau `box-shadow:0 0 0 1px var(--accent)` (7 sélectionnables, `Home-5XXTBcEd.css` `.level[aria-pressed=true]`, `RadioGroup-DzxUUb1_.css`).

### 2.3 Espacements

Échelle unique sur `:root`. `--space-1` 4 px, `-2` 8, `-3` 12, `-4` 16, `-5` 20, `-6` 24, `-8` 32, `-10` 40, `-12` 48, `-16` 64, `-20` 80, `-24` 96.

- Sections. `.section-block{padding:var(--space-24) 0}` = 96 px, ramené à 64 px sous 720 px (`@media (width<=720px)`). Sur l'accueil et À propos seulement. Les autres pages ont leur rythme (`.page-head` 48 px haut, 32 px bas, `.category` du catalogue 64 px, `.final-cta` 96 et 64 px).
- Filet entre sections. `.section-block+.section-block{border-top:1px solid var(--rule)}`, supprimé si l'une des deux est teintée. Deux filets sur l'accueil, aucun sur À propos.
- Conteneur. `.container{max-width:1180px;padding:0 var(--space-6)}`, soit 1 180 px et 24 px de gouttière. Variantes à 1 100 px (`.container-narrow`, `.catalog`, ce dernier à 32 px de gouttière).
- Hero de l'accueil. `.hero{min-height:88vh;align-items:flex-end}` et `.hero-container` en `padding-top:140px;padding-bottom:80px`, bas passant à 120 px et grille `1.15fr .85fr` à partir de 980 px (`Home-5XXTBcEd.css`).
- En-tête de section. Composant `SectionHead` = surtitre, h2, chapeau à 60ch, avec `margin-top:var(--space-5)` entre eux (`FinalCta-DOUYX8pg.css`). Aucun filet sous le titre.

### 2.4 Rayons

`--r-lg` 14 px pour les cartes et panneaux (24 usages). `--r-md` 10 px pour les boutons, les champs, les onglets (37 usages). `--r-sm` 6 px pour les petits contrôles et les étiquettes sur photo (13 usages). `--r-xs` 2 px pour la piste du curseur. 999 px pour les pilules, les badges texte et les puces de domaine. 50 % pour les pastilles rondes.

### 2.5 Ombres

Deux ombres tokenisées, teintées brun au repos et or au survol.

- `--shadow-card: 0 1px 0 var(--rule), 0 24px 48px -28px #1a17142e` (15 usages).
- `--shadow-hover: 0 1px 0 var(--accent), 0 24px 56px -24px #c9a22747` (7 usages), toujours avec `transform:translateY(-2px)`.

Plus des ombres codées en dur. `.feat-card` en plus fort (`0 30px 60px -20px #1a171447`, survol `0 36px 64px -20px #c9a22759`, remontée de 4 px). `.hero-estimator` en noir (`0 40px 80px -32px #0009`). Et un socle de 1 px accent foncé sous les boutons pleins (`box-shadow:0 1px 0 var(--accent-2)`).

Transposition proposée. Repos `0 1px 0 <filet>, 0 24px 48px -28px rgba(13,35,64,.18)`. Survol `0 1px 0 #0FA7A5, 0 24px 56px -24px rgba(15,167,165,.28)`.

### 2.6 Boutons

Un seul composant de bouton dans le bundle, `Button-Cu-B_Pb3.js` (props `variant` par défaut `primary`, `size` par défaut `md`, `type`, `disabled`, `href` ; classe calculée `` `btn-`+variant `` plus `btn-lg` si `size` vaut `lg` ; rend le composant de lien Inertia si `href` est fourni, sinon un `<button>`), importé par `SiteHeader`, `Painting`, `EstimatorRecapDetailed`, `useWizard.svelte`, `Projects` et le bundle principal. Base commune (`.btn-primary,.btn-secondary,.btn-ghost`). `display:inline-flex`, `gap:8px`, `padding:13px 20px`, `border-radius:10px`, bordure 1 px transparente, Inter 14,7 px 600, `line-height:1`, `text-decoration:none`. `.btn-lg` passe à `16px 24px` et 16 px.

- Primaire. Fond `--accent`, texte blanc, `box-shadow:0 1px 0 var(--accent-2)`. Survol fond `--accent-2` et `translateY(-1px)`.
- Secondaire. Texte et bord `--ink`, fond transparent. Survol inversé, fond `--ink`, texte blanc.
- Fantôme. Texte `--ink`, bord `--rule`. Survol bord `--ink`. Variante `.btn-ghost.on-dark` en blanc sur `#ffffff0f`, bord `#ffffff4d` (un seul usage, « Comment ça se passe » dans le hero).
- Désactivé. `[disabled]{cursor:not-allowed;opacity:.5;transform:none}`, survol neutralisé.
- Flèche. Caractère « → » dans un `span.arr`, `transition:transform .2s`, `translate(3px)` au survol (4 px sur les cartes et la mosaïque). Dans les écrans du tunnel, la flèche est du texte brut sans animation.
- Focus. Aucune règle `:focus-visible` sur les boutons. C'est un défaut du site réel, pas un modèle. Les pages Kayli Clinn en ont déjà une, à garder.

Transposition proposée. Primaire teal `#0FA7A5` texte blanc, socle `0 1px 0 #076E6D`, survol fond `#076E6D`. Secondaire bord et texte navy, survol fond navy. Fantôme bord filet, survol bord navy.

### 2.7 Pilules

Trois pilules dans le hero (`Home-5XXTBcEd.css`, `.hero-pills .pill`). `display:inline-flex`, `gap:8px`, `padding:8px 14px`, texte blanc 13,8 px 500, fond `#ffffff1a`, bord `1px solid #fff3`, rayon 999 px, `-webkit-backdrop-filter:blur(8px)` (préfixe WebKit seulement, à compléter par la propriété standard chez Kayli Clinn). Chaque pilule est précédée d'une coche SVG 14×14 en `--accent`. Conteneur `gap:12px`, `margin-top:36px`.

Les badges (`.badge`) sont une autre famille. Inter 12,5 px 600, rayon 999 px, `padding:5px 11px`, icône SVG 12 px. `badge-time` sur fond `--accent-soft` texte `--accent-2`, `badge-avail` texte vert `--ok` sur `#2f6b4f14`. Rendus sur l'accueil dans les deux cartes phares.

### 2.8 Cartes

- Carte phare (`.feat-card`, `FeaturedCard-oJ49rmNd.js`). Toute la carte est un lien. Image de 220 px de large à gauche à partir de 520 px, sinon 220 px de haut au-dessus. Corps en `padding:24px 26px`. Kicker mono « Prestation phare », h3, description, deux badges, CTA « Estimer en ligne → ».
- Carte prestation du catalogue (`.service-card`). Image 16/10 sur fond `--accent-soft`, h3 22 px, une phrase, un bouton dont le libellé dépend du mode.
- Carte de niveau (`.level`, accueil). Bouton `aria-pressed`, `padding:20px 24px`, rayon 14 px, ombre carte. Pressé = bord accent, fond accent pâle, anneau 1 px.
- Carte d'avis (`.review`). 300 px fixes, `padding:24px`, étoiles, citation 15,2 px, auteur en mono capitales 11 px.
- Carte de principe (`.principle`, À propos). `padding:24px`, rayon 14 px, numéro « 01 » en mono 12,5 px 700 espacé 0,14 em.
- La classe `.card` générique (`padding:28px`) est définie mais utilisée par aucune page capturée.
- Dialogue de confirmation (`ConfirmDialog-BaTm3En8.js`, `ConfirmDialog-C4RVgHk_.css`). Seul motif de confirmation destructive du site, utilisé par `Cart-Lb1KgKps.js` et `Catalog-DqgjFC4j.js` avant le retrait d'une ligne. `<dialog class="confirm">` natif, `::backdrop` `#17141273`, carte bord 1 px `--rule`, rayon `--r-lg` (14 px), fond `--paper`, ombre `--shadow-card`, `padding:24px`, largeur max 34rem ; titre serif 1,25rem, message `--muted` ; bouton `.confirm-danger` fond et bord `--danger`, texte blanc, `13px 20px`, rayon 10 px, Inter 14,7 px 600, `:focus-visible` `outline:2px solid var(--danger)` (le seul bouton du site avec un focus visible). Texte « Retirer cette prestation ? » / « « <titre> » sera retirée de votre devis. » / « Retirer » / « Annuler ».

### 2.9 Points de rupture

Trois seuils, en syntaxe d'intervalle. 520 px (`@media (width>=520px)`, carte phare en ligne), 720 px (sections à 64 px, grilles à 2 ou 4 colonnes, panneau d'étape en 2 colonnes, mosaïque en 6 colonnes), 980 px (navigation visible, hero en 2 colonnes, onglets de profil en colonne, grille de projets en 2 colonnes). L'ancienne charte (avant le 17/09) disait 620, 900 et 1 040. C'était faux pour le site réel.

### 2.10 Mouvements

- Transitions courtes. 120 à 200 ms, `--ease-out: cubic-bezier(.2,.7,.2,1)`. Cartes en 250 ms.
- Révélation au défilement. `data-reveal="armed"` puis `"in"`, opacité et `translateY(16px)` sur 700 ms, délai 80 ms par enfant plafonné à 480 ms, seuil 0,08 (`FinalCta-BQXcIU9S.js`).
- Onglets « Comment ça se passe ». Avance automatique toutes les 5 200 ms avec barre dorée de 3 px qui se remplit, pause quand le pointeur ou le focus est sur le panneau (`sceneClock-CpgaXUNN.js`).
- Frise du suivi. Lecture de 9 000 ms, maintien de 2 200 ms à la fin, pause au survol.
- Marquee d'avis. 48 s, dupliqué avec `aria-hidden`, masque latéral 6 % et 94 %, pause au survol et au focus.
- Comparateur avant/après. Balayage de 100 % vers 22 % en 2 400 ms après un délai de 600 + 900 × index ms, dès 50 % de visibilité, coupé au premier geste (accueil seulement).
- Carte mini-estimateur. Inclinée de −1,5°, redressée au survol. Démonstration du curseur après 900 ms, une bosse de 50 à 65 puis 50 m² sur 1 800 ms.
- Tuiles de la mosaïque. `rotateY(180deg)` en 600 ms.
- Réduction des animations. Neuf blocs `@media (prefers-reduced-motion:reduce)` et cinq contrôles JavaScript. Couverture partielle. Catalog, RadioGroup, Slider, SiteHeader ne sont pas neutralisés.

---

## 3. Les pages, section par section

Pour chaque section. Son rôle, sa structure, le texte LKH mot pour mot, la transposition proposée pour Kayli Clinn, et l'interaction à garder ou non. Les textes de transposition sont des propositions. Aucun délai, aucun prix hors grille v2, aucune donnée d'entreprise n'y est affirmé. Cinq formulations proposées engagent un service que `CLAUDE.md` ne couvre pas et sont marquées « engagement de service à valider par la propriétaire » là où elles apparaissent (§3.1.3 « contrôle avant départ », §3.1.4 photos à l'arrivée et au départ remises au client, §3.1.5 « Contrôle point par point avant le départ de l'équipe », §3.2 « Ce qui est nettoyé est contrôlé », §5.3 « rien n'est facturé qui n'ait été affiché avant »), au même titre que les délais de la question 9.

### 3.1 Accueil (composant `Home`, `public_exact/index.html`)

Douze blocs dans un ordre fixé par le bundle (`Home-BEyYY4bd.js`, gabarit `Vt`). En-tête, hero, prestations phares, avant/après, comment ça se passe, mosaïque, suivi, preuve, profils, FAQ, CTA final, pied de page. Cinq blocs reçoivent leurs données du serveur (phares, projets, niveaux, avis, tarif peinture). Les sept autres sont codés en dur.

#### 3.1.1 En-tête transparent

Rôle. Marque, navigation, un bouton d'action.

Structure. `header.site.transparent`, absolu, sans fond ni bordure, texte blanc semi-opaque. Sur les autres pages, collant en verre (`#ffffffeb`, flou 10 px, filet bas). Marque = pictogramme SVG doré de 28 px de haut, « LKH Construction », baseline « Travaux suivis » en or. Navigation de quatre liens à partir de 980 px, lien actif souligné de 2 px accent. Bouton « Estimer maintenant ». Sur l'accueil (en-tête transparent), le bouton disparaît entièrement sous 720 px (`SiteHeader-CpDtGUgc.css`, `@media (width<=719px){header.site.transparent .header-cta{display:none}}`) ; sur les autres pages il devient « Estimer » seul (`.cta-long{display:none}` hors `@media (width>=720px)`). Burger 40×40 sous 980 px.

Transposition. Hors périmètre. L'en-tête de kayliclinn.fr est géré par le thème WordPress. `pushBelowHeader()` dans `reservation.html` gère déjà le décalage.

#### 3.1.2 Hero

Rôle. Poser la promesse et ouvrir vers l'estimation en un écran.

Structure. Section sombre de 88vh, contenu aligné en bas. Photo `hero.webp` sous `filter:saturate(.85) brightness(.85)` et trois dégradés (deux voiles `#0f0d0b`, une lueur dorée radiale en bas à gauche). Grille à deux colonnes `1.15fr .85fr` à partir de 980 px. À gauche, surtitre, h1, chapeau, deux boutons, trois pilules. À droite, une carte blanche inclinée de −1,5° qui contient un mini-estimateur.

Texte LKH.
- Surtitre « Entreprise générale du bâtiment · Île-de-France ».
- h1 « Des travaux *suivis*, pas subis. » (« suivis » en `em.accent`).
- Chapeau « Un prix en 5 minutes, sans inscription ni email à laisser. Un chantier documenté du devis à la réception. Disponibilité rapide sur peinture intérieure et pose de sol — démarrage sous 2 à 3 semaines. » (« 5 minutes », « peinture intérieure », « pose de sol » en `strong` doré).
- Boutons « Estimer mes travaux → » (primaire, grand) et « Comment ça se passe » (fantôme sur sombre).
- Pilules « Sans email ni téléphone », « Sans engagement », « Devis détaillé et clair ».
- Carte. Kicker « Peinture intérieure · murs », question « Quelle surface au sol ? », champ 50 m² avec curseur de 10 à 200, prix « 3 605 € HT », mention « Estimation indicative — murs seuls, support sain, finition velours. », bouton « Continuer l'estimation → », pied « Encore quatre questions · 3 à 5 minutes · sans email ni téléphone. ».

Transposition (proposition).
- Surtitre « Nettoyage professionnel · Île-de-France ».
- h1 « Un espace propre, *l'esprit tranquille*. » (déjà en place dans `accueil.html`, fragment en `em` teal droit).
- Chapeau en deux ou trois phrases factuelles, sans délai tant qu'aucun n'est validé. Par exemple « Un prix ferme en ligne pour les forfaits, sans inscription. Une intervention préparée et contrôlée. Devis gratuit sur visite pour le reste. »
- Boutons « Estimation en ligne » vers `/devis/` et « Voir nos prestations » (les deux boutons prévus pour le hero par `CARTE-DE-COLLAGE.md`, ligne 59). « Estimer & réserver en ligne » y est le bouton des pages forfait (ligne 50), pas celui du hero ; si l'on veut l'unifier, c'est un écart avec la carte de collage à signaler.
- Pilules « Sans engagement » et « Prix ferme sur les forfaits » (formulations présentes dans `a-propos.html`) et « Devis écrit avant intervention » (proposition, absente de `a-propos.html`, à valider). « Sans email ni téléphone » ne convient pas, le tunnel Kayli Clinn demande les coordonnées avant le résultat.
- Carte de droite. Pas de curseur de surface, Kayli Clinn ne vend pas au m² hors fin de chantier. Une carte « Forfait » avec un sélecteur de typologie (Studio/T1, T2, T3, T4) lisant `KC_TARIFS`, un montant serif à 30 px `tabular-nums` avec « TTC », la mention « Prix ferme — grille en vigueur », et « Continuer l'estimation → » vers `/devis/`. Exemple avec la grille v2 documentée dans `CLAUDE.md`, turnover Airbnb T2 = 75 € TTC.

Interaction. À reproduire, la grille, l'alignement en bas, les pilules, la carte inclinée redressée au survol et en `prefers-reduced-motion`. À ne pas reproduire, la démonstration automatique du curseur. Le paramètre d'URL qui relie la carte au tunnel est à ajouter, `estimation.html` ne lit pas `window.location.search` (son seul `URLSearchParams`, ligne 2280, construit une chaîne, il n'en lit pas).

#### 3.1.3 Prestations phares

Rôle. Pousser deux prestations réservables tout de suite.

Structure. `div.featured`, `padding:48px 0 0` (`var(--space-12)`), 32 px sous 720 px (`@media (width<=720px){.featured{padding:var(--space-8) 0 0}}`, `Home-5XXTBcEd.css`), grille `.featured-grid` à deux colonnes et `gap:28px` à partir de 720 px (une colonne, `gap:var(--space-6)` = 24 px en dessous). Deux cartes `.feat-card` alimentées par la prop `featured`.

Texte LKH. Kicker « Prestation phare ». Carte 1 « Peinture intérieure », « Murs et plafonds — fourniture et pose, préparation des supports incluse. », badges « Estimation en 4 min », « Démarrage sous 2 semaines ». Carte 2 « Pose de sol », « Parquet, stratifié, vinyle, carrelage ou moquette — fourniture et pose, dépose en option. », « Estimation en 3 min », « Démarrage sous 3 semaines ». CTA « Estimer en ligne → ».

Transposition (proposition). Deux cartes « Turnover Airbnb » et « Fin de bail ». Descriptions au même motif. « Studio à T4 — ménage complet, linge et consommables en option, contrôle avant départ. » (« contrôle avant départ », engagement de service à valider par la propriétaire) et « Logement vide, studio à T5 — électroménager, vitres intérieures et placards inclus. » (périmètre conforme à la grille v2). Les badges de durée et de délai ne s'affichent pas tant que la propriétaire n'a pas fourni ces valeurs. CTA « Estimer & réserver en ligne → ».

Interaction. Toute la carte est le lien, ombre dorée et remontée de 4 px au survol. À reproduire.

#### 3.1.4 Avant / Après

Rôle. Preuve visuelle.

Structure. `section#avant-apres`, en-tête centré, `ul.sites` en 2 colonnes à partir de 720 px. Chaque projet dans un cadre à rayon 14 px avec le comparateur `role="slider"`, puis tag mono, titre serif, département.

Texte LKH. Surtitre « Avant · Après ». h2 « Nos chantiers, *avant et après.* ». Chapeau « Deux exemples parmi nos réalisations — faites glisser la poignée. Chaque chantier est photographié du premier jour à la réception, et ces images vous sont remises. ». Projets « Rénovation cuisine / Cuisine sur mesure, livrée clé en main / 91 Essonne » et « Rénovation appartement / Appartement 90 m², rénovation complète / 95 Val-d'Oise ». Bouton « Voir toutes nos réalisations → ».

Transposition (proposition). Surtitre « Avant · Après ». h2 « Nos interventions, *avant et après.* ». Chapeau « Deux exemples parmi nos interventions — faites glisser la poignée. Chaque passage est photographié à l'arrivée et au départ de l'équipe, et ces images vous sont remises. » (engagement de service à valider par la propriétaire). Les photos et leur accord client sont à fournir. Aucun nom de client particulier ne s'affiche.

Interaction. Comparateur à reproduire en JavaScript natif. Curseur 0 à 100, clip-path sur l'image « après », clavier flèches ±5 et Home/End, balayage automatique de 100 vers 22 % en 2,4 s après 600 + 900 × index ms, coupé au premier geste et en `prefers-reduced-motion`. Ajouter des `alt` distincts avant et après (LKH ne le fait pas).

#### 3.1.5 Comment ça se passe

Rôle. Dérouler le parcours en quatre étapes.

Structure. Onglets `role="tablist"` en grille 2 puis 4 colonnes à 720 px. Onglet = « Étape 01 » en mono, titre court, barre dorée de 3 px qui se remplit en 5,2 s. Panneau à 2 colonnes à partir de 720 px. À gauche h3, texte, encart « Vous recevez » sur fond accent pâle, durée en mono. À droite une vignette HTML animée.

Texte LKH. Surtitre « Estimer · Comment ça se passe ». h2 « Comment ça se passe, *étape par étape.* ». Chapeau « De la première question à la réception. Vous gardez la main — on ne récupère vos coordonnées que si vous décidez d'aller plus loin avec nous. » Étapes.
1. « Vous estimez en ligne » — « Vous estimez votre projet en ligne. » — « Quelques questions sur la surface, les dimensions et la finition. » — durée « 3 à 5 minutes » — vous recevez « Une fourchette de prix, sans laisser d'email ni de téléphone. »
2. « Vous réservez la visite » — « Vous réservez votre visite technique. » — « Un créneau choisi en ligne, on vient évaluer le chantier sur place. » — « Créneau au choix » — « Un devis détaillé et structuré. »
3. « Le chantier avance » — « Le chantier avance, et vous le voyez. » — « Planning, équipes, matériaux, accès : tout est organisé et annoncé. » — « Selon le devis » — « Des photos datées, des points d'avancement, toute modification validée par écrit. »
4. « Vous réceptionnez » — « Vous réceptionnez le chantier, avec nous. » — « Vérification pièce par pièce, réserves notées si besoin, validation finale. » — « Avec vous » — « Les réserves suivies jusqu'à leur levée, les documents prévus au marché. »
Bouton « Commencer mon estimation → ».

Transposition (proposition). Surtitre « Estimer · Comment ça se passe ». h2 identique. Chapeau « De la première question au contrôle final. Vous gardez la main — le prix des forfaits s'affiche avant tout paiement. » Étapes.
1. « Vous estimez en ligne » — « Vous estimez votre prestation en ligne. » — « Quelques questions sur le logement, les options et la date. » — vous recevez « Un prix ferme pour les forfaits, une estimation pour le reste. »
2. « Vous réservez » — « Vous réservez votre créneau, ou une visite gratuite. » — « Un créneau réel, choisi dans l'agenda de l'équipe. » — vous recevez « Une confirmation écrite et un lien pour gérer votre réservation. »
3. « L'intervention » — « L'équipe intervient, comme convenu. » — « Matériel, produits, accès : tout est préparé avant l'arrivée. »
4. « Le contrôle » — « Vous vérifiez le résultat, avec nous. » — « Contrôle point par point avant le départ de l'équipe. » (engagement de service à valider par la propriétaire)
Les durées et le contenu exact des livrables sont à valider.

Interaction. Onglets automatiques à 5,2 s avec pause sur le panneau, à reproduire. Vignettes animées « visite technique » et « procès-verbal », sans équivalent chez Kayli Clinn, à remplacer par des vignettes simples ou à omettre.

#### 3.1.6 Catalogue en mosaïque

Rôle. Entrée vers les cinq familles de prestations.

Structure. Section à halos. En-tête aligné à gauche avec un bouton fantôme à droite. Grille de 6 colonnes à partir de 720 px. Rangées de 240 px en une colonne sous 720 px, 180 px à partir de 720 px, 210 px à partir de 980 px (`.mosaic{…grid-auto-rows:240px}` hors media, puis `@media (width>=720px)` et `@media (width>=980px)`). Cinq tuiles sur 2×3, 3, 3, 2 et 4 colonnes. Chaque tuile se retourne (`rotateY`), une seule ouverte à la fois, bouton rond « i » de 34 px. Face avant = lien pleine tuile avec image, titre, exemples révélés au survol. Face arrière blanche bordée d'accent avec description et « Voir les prestations → ».

Texte LKH. Surtitre « Estimer · Catalogue ». h2 « Toutes nos *prestations*. ». Chapeau « Plusieurs prestations se chiffrent en ligne, en quelques minutes. Pour les autres, on convient d'un rendez-vous pour une visite technique. » Bouton « Voir le catalogue complet → ». Tuiles « Rénovation intérieur », « Rénovation extérieur », « Gros œuvre », « Énergie & réseaux », « Clé en main », avec exemples du type « Peinture, pose de sol, plomberie, électricité, cuisine, salle de bain… ».

Transposition (proposition). Surtitre « Estimer · Catalogue ». h2 identique. Chapeau « Les forfaits se réservent en ligne, prix ferme. Pour les autres prestations, on convient d'une visite gratuite. » `prestations.html` n'a que quatre familles (surtitres « Forfaits réservables en ligne », « Propreté récurrente », « Prestation ponctuelle », « Interventions spécialisées », `class="eyebrow"`). La mosaïque à cinq tuiles impose donc un découpage à trancher par la propriétaire (question 3 de la section 7, ou une nouvelle question). Garder quatre tuiles, ou scinder une famille. Les prestations 3D (dératisation, désinsectisation, punaises) restent hors mosaïque tant que le Certibiocide n'est pas obtenu (`CLAUDE.md`). Le mot « rendez-vous » est remplacé par « visite » conformément à `CARTE-DE-COLLAGE.md`.

Interaction. Tuiles retournables à reproduire, bouton toujours visible sur tactile, une seule ouverte.

#### 3.1.7 Suivi de chantier

Rôle. Vendre trois niveaux de suivi.

Structure. Section à halos. En-tête centré. Trois boutons `.level` (`aria-pressed`) puis une frise animée de 9 s avec 23 marqueurs (4 contrôles pour tous, 3/9/12 photos, 0/3/7 comptes rendus), légende, puis un tableau comparatif `role="table"` avec une ligne de base et 5 critères, boutons de pied « Je choisis ce suivi ».

Texte LKH. Surtitre « Suivre · Le chantier ». h2 « Votre chantier reste *lisible*, du devis à la réception. ». Chapeau « La coordination, les contrôles et la réception sont les mêmes sur tous nos chantiers, compris dans le prix. Ce qui change avec le niveau de suivi, c'est la fréquence des photos et l'étendue des comptes rendus. Choisissez un niveau : la frise montre ce que vous recevez. » Niveaux « Essentiel / Chantier Visible / Inclus », « Confort / Chantier Serein », « Premium / Chantier Signature ». Note « Chantier Visible est compris dans le prix du chantier. Chantier Serein et Chantier Signature s'ajoutent sur une ligne distincte du devis, sans abonnement ni coût caché. »

Transposition (proposition). Il n'existe pas de niveau de suivi tarifé chez Kayli Clinn. La position et la mécanique (trois cartes, tableau comparatif) peuvent porter « Trois façons d'obtenir un prix ». Forfait, prix de la grille et réservation immédiate. Visite gratuite, devis sur mesure après passage. Demande de devis, être rappelé. Sans frise ni pourcentage. Si la propriétaire souhaite un jour un niveau de service (photos, fiche d'intervention, reporting), le montant sera recalculé dans `KC_Pricing`.

Interaction. Sélecteur à trois cartes et tableau, à reproduire. Frise, à ne pas reproduire.

#### 3.1.8 Preuve sociale

Rôle. Logos et avis.

Structure. Section centrée. Deux logos, h2, note « 5/5 » avec cinq étoiles, lien « 6 avis Google → », marquee de six cartes de 300 px, bouton fantôme.

Texte LKH. Surtitre « Recevoir · Ils nous font confiance ». h2 « Ce qu'en disent *nos clients*. ». Bouton « Voir nos réalisations → ».

Transposition (proposition). Même structure. Pas de logos tant qu'aucun client n'a donné son accord. Note et nombre d'avis saisis à la main depuis la fiche Google réelle, sinon badge « Exemple » obligatoire sur chaque carte (règle DGCCRF dans `CLAUDE.md`). La section `kcav2` d'`accueil.html` existe déjà.

Interaction. Marquee de 48 s dupliqué avec `aria-hidden`, pause au survol et au focus, défilement natif en `prefers-reduced-motion`. À reproduire.

#### 3.1.9 Selon votre profil

Rôle. Adapter le discours à quatre publics.

Structure. `section#solutions.audiences`, seule section de l'accueil à fond plein `--accent-soft`. Grille `.8fr 1.2fr` à partir de 980 px. À gauche quatre onglets (bande horizontale défilante sous 980 px, colonne au-dessus). À droite un panneau blanc avec une phrase d'accroche dont un fragment est surligné, un texte, trois livrables cochés, un bouton.

Texte LKH. Surtitre « Recevoir · Selon votre profil ». h2 « Le même chantier ne se pilote pas de la même façon pour *chaque client*. ». Chapeau « Choisissez qui vous êtes : les échanges, les documents et le niveau de reporting suivent. » Profils.
- Particulier. « Vous voulez *comprendre* avant de vous engager. » Livrables « Un prix en ligne, avant tout contact », « Une visite technique, puis un devis détaillé », « Des photos datées du chantier, même sans être sur place ». CTA « Estimer mes travaux ».
- Professionnel. « Vous voulez que le chantier *gêne le moins possible*. » « Des interventions calées sur vos horaires », « Un délai annoncé, puis tenu », « Un compte rendu à chaque point d'avancement ». CTA « Étudier mon besoin ».
- Syndic & gestionnaire. « Vous devez *justifier* chaque décision. » « Des devis structurés, lot par lot », « Des comptes rendus datés, prêts pour l'assemblée », « Une traçabilité utile à vos arbitrages ». CTA « Demander une étude ».
- Architecte & prescripteur. « Vous cherchez une *exécution fiable* de votre projet. » CTA « Échanger avec LKH ».

Transposition (proposition). Quatre profils. Particulier, Hôte Airbnb, Syndic & bailleur, Entreprise & commerce. Accroches au même moule. « Vous voulez *un prix clair* avant de réserver. », « Vous voulez *un logement prêt* entre deux voyageurs. », « Vous devez *justifier* chaque intervention. », « Vous voulez que le passage *gêne le moins possible*. » Livrables et textes à valider. CTA vers `/devis/` ou `/demande-de-devis/`.

Interaction. Onglets à reproduire. Corriger `aria-orientation`, figé à « vertical » chez LKH quelle que soit la largeur.

#### 3.1.10 FAQ

Rôle. Six questions, accordéon natif.

Structure. `details.faq` sur fond blanc, bord `--rule`, rayon 10 px, `padding:18px 22px`, marqueur « + » doré devenant « − ». Conteneur à 820 px. Plusieurs peuvent être ouverts. Aucun filtre.

Texte LKH (extraits). « L'estimation en ligne est-elle un devis définitif ? » — « Non. Elle donne une première fourchette à partir des informations saisies. Le prix définitif est confirmé après analyse du projet et, lorsque cela est nécessaire, après une visite technique. » « Puis-je suivre mon chantier à distance ? » — « Oui. Chaque chantier est documenté par des photos datées ; … » « Dans quels départements intervenez-vous ? » — « La zone principale couvre Paris et l'Île-de-France : 75, 77, 78, 91, 92, 93, 94 et 95. … »

Transposition (proposition). Six questions au lieu des dix à onglets de `accueil.html`. « Le prix affiché en ligne est-il définitif ? » — « Oui pour les forfaits. Il est calculé sur la typologie, les options choisies, les majorations affichées (urgence, dimanche ou férié, logement non vidé, très encrassé) et les frais d'étage. Certaines situations renvoient vers un devis après visite : logement hors grille, état très dégradé, vitres en hauteur, vitrine ou verrière, entretien professionnel récurrent, ou une demande particulière signalée dans votre message. » (reprend la formule et les cinq bascules documentées dans `CLAUDE.md` et au §4.2 ; réponse à valider par la propriétaire). « Dans quels départements intervenez-vous ? » — « Paris et l'Île-de-France : 75, 77, 78, 91, 92, 93, 94 et 95. » Les mentions « AXA 5 M€ » et « Satisfait ou repassé sous 48 h » du JSON-LD actuel restent à vérifier avant tout réemploi.

Interaction. `<details>` natif, à reproduire. Attention, `kcfq` dans `accueil.html` n'en est pas un. C'est un accordéon JavaScript à 10 questions (0 `<details`, 10 `class="kcfq-item"` avec des `div.kcfq-toggle`) doublé de quatre boutons de filtre `button.kcfq-filter` (Général, Tarifs & paiement, Intervention, Toutes les questions), que la charte §7 bannit. À remplacer par 6 `<details>` natifs sans filtre.

#### 3.1.11 CTA final

Rôle. Dernier appel, un seul bouton.

Structure. `div.final-cta`, fond blanc avec halo radial doré (`radial-gradient(60% 100% at 50% 0,#c9a2271a,#0000 70%)`), filet haut, `padding:96px 0` (64 px sous 720), centré, h2 à 22ch, paragraphe `--muted` 16,8 px, un bouton `btn-primary btn-lg`. Présent sur l'accueil et À propos, pas sur Réalisations.

Texte LKH. « Un prix en 5 minutes. *Un chantier suivi jusqu'au bout.* » — « Estimez d'abord, en ligne et sans laisser vos coordonnées. On se parle quand vous le décidez — et le chantier reste lisible jusqu'à la réception. » — « Estimer mes travaux → ».

Transposition (proposition). « Un prix ferme en ligne. *Un espace propre, l'esprit tranquille.* » — « Estimez d'abord, en ligne. Réservez quand vous le décidez — et l'intervention reste préparée et contrôlée jusqu'au dernier passage. » — « Estimer & réserver en ligne → ». Le CTA sombre `kcct2` d'`accueil.html`, ses lueurs et son bouton « Urgence chantier » disparaissent.

Interaction. Aucune.

#### 3.1.12 Pied de page

Sombre `#171412`, quatre colonnes `1.2fr .8fr .8fr .9fr` à partir de 980 px, ligne légale « © 2026 LKH Construction. Tous droits réservés. » et « Mentions légales ». Variante compacte sur le tunnel. Hors périmètre, géré par le thème.

### 3.2 À propos (composant `About`, `public_exact/a-propos/index.html`)

Pas de hero photo. Un bloc d'ouverture puis cinq sections, puis le CTA final partagé. Ni FAQ ni JSON-LD.

#### Ouverture

Fil d'Ariane « Accueil › À propos ». h1 « Qui *sommes-nous* ? ». Chapeau « LKH Construction est une entreprise générale du bâtiment en Île-de-France. Maçonnerie, réseaux, finitions, rénovation complète : nous prenons vos travaux en charge de A à Z, avec un seul interlocuteur du devis à la réception. » Paragraphe d'intro de quatre phrases (« Sur un chantier, chaque métier sait faire son travail. … »).

Proposition. h1 « Qui *sommes-nous* ? ». Chapeau « Kayli Clinn est une société de nettoyage en Île-de-France. Turnover, fin de bail, vitres, bureaux, remise en état : nous prenons votre intervention en charge de la réservation au contrôle final, avec un seul interlocuteur. »

#### `#methode`, trois principes (fond accent pâle)

Surtitre « Notre façon de travailler ». h2 « Trois principes, appliqués sur *tous* nos chantiers. » Cartes « 01 Ce qui sera invisible est photographié », « 02 Ce qui change est écrit avant d'être fait » (« Toute modification ayant un effet sur le prix ou le délai est chiffrée, expliquée et validée par écrit avant exécution. Rien ne se découvre à la facture. »), « 03 Une seule personne répond ».

Proposition. « 01 Ce qui est nettoyé est contrôlé » (engagement de service à valider par la propriétaire), « 02 Ce qui s'ajoute est chiffré avant d'être fait », « 03 Une seule personne répond ». Texte 02 repris presque tel quel, il correspond à la règle des options chiffrées de la grille.

#### `#suivi`, trois niveaux

Surtitre « Le suivi de chantier ». h2 « Trois niveaux de suivi ». Trois cartes n'affichant que mention, libellé et nom, rythme photo, bénéfice. Lien « Comparer les trois niveaux → » vers `/#suivi`.

Proposition. « Trois façons d'obtenir un prix » (même composant que sur l'accueil), lien « Comparer les trois parcours → » vers l'ancre de l'accueil.

#### `#entreprise`, sombre

Surtitre « Qui nous sommes » (accent sur sombre). h2 « Une entreprise que vous pouvez nommer. » Chapeau « LKH Construction a été fondée en 2024 par Josiane Cruz de Oliveira, qui la préside aujourd'hui. » Paragraphes sur la zone (huit départements) et la clientèle. Carte « L'entreprise » avec six lignes. Raison sociale, SIRET, Siège, Direction, Assurances (« MMA Entreprise — RC professionnelle et garantie décennale »), Contact. Note « Nos attestations d'assurance sont transmises avec le devis, avant toute signature, et peuvent être demandées à tout moment. »

Proposition. Même h2. Phrase de fondation seulement si l'année et le nom sont fournis, sinon placeholder visible. Fiche à six lignes avec les seules données réelles de Kayli Clinn. Les lignes « Activité » et « Zone » de l'ancienne charte (avant le 17/09) viennent de la refonte et disparaissent.

#### `#equipes`

Surtitre « Qui réalise les travaux ». h2 « Nos équipes, et des entreprises partenaires *référencées.* » Bouton « Vous êtes artisan ? Rejoignez le réseau → ». Chapeau « Une partie de nos chantiers est réalisée avec des entreprises indépendantes que nous avons sélectionnées. Cela ne change rien pour vous : LKH Construction reste votre interlocuteur, de la préparation à la réception. » Cinq puces de domaine en pilules 999 px avec flèche.

Proposition. Même structure. Le texte partenaires de `a-propos.html` (marqué « À VALIDER ») reste tel quel. Bouton « Conciergerie ou professionnel ? Parlons partenariat → ».

#### `#chantiers` (fond accent pâle)

Surtitre « Nos chantiers ». h2 « Des chantiers réels, du diagnostic à la *réception.* » Chapeau « Plutôt qu'une galerie de résultats, nous montrons le déroulé de chantiers que nous avons menés — y compris les étapes qu'on ne voit plus une fois les travaux finis. » Trois cartes photo (image 16/10, catégorie mono, titre, département) toutes vers `/realisations`. Bouton secondaire « Voir toutes nos réalisations → ».

Proposition. h2 « Des interventions réelles, de l'arrivée au *contrôle final.* » Trois cartes vers `/realisations/`, photos à fournir.

### 3.3 Réalisations (composant `Projects`, `public_exact/realisations/index.html`)

Fil d'Ariane, `page-head` avec h1 « Nos dernières *réalisations*. » et chapeau « Quelques chantiers menés en Île-de-France ces derniers mois — du garage neuf à la rénovation complète d'appartement. Faites glisser la poignée pour voir l'avant et l'après. » Grille de 4 projets en 2 colonnes (1 sous 980 px). Trois comparateurs à 50 % sans balayage, une galerie de 5 photos (flèches, points `role="tab"`, fondu). Les noms des clients particuliers ne sont jamais affichés, seul le département. Aucune date, durée ni montant. Encart final `.projects-cta` sur fond accent pâle, « Un projet *en tête* ? », « … on revient vers vous sous 48 h. », bouton « Estimer mes travaux → ». Pas de CTA final partagé.

Proposition. `realisations.html` existe. Garder la règle « pas de nom de particulier ». Reprendre la formulation déjà en ligne sur kayliclinn.fr pour le délai de réponse, jamais « sous 48 h ». Ajouter des `alt` distincts et masquer les images inactives de la galerie aux lecteurs d'écran (deux défauts LKH).

### 3.4 Catalogue (composant `Estimator/Catalog`, `public_exact/nos-prestations/response.json`)

Rôle. Choisir une ou plusieurs prestations.

Structure. `page-head` avec h1 « Nos *prestations*. » et chapeau « Sélectionnez vos travaux ci-dessous : estimation immédiate quand c'est possible, étude détaillée pour le reste. Chaque catégorie a sa page, qui montre un chantier réel et notre façon de le mener. » Aside « Nous intervenons en Île-de-France · Paris et couronne (75, 77, 78, 91, 92, 93, 94, 95). » (composant `IdfInfoBlock-CosAD-CA.js`, `IdfInfoBlock-C_vO_IEG.css`, `.idf-info-block` avec épingle SVG 18 px en `--accent-2`, `border-left:3px solid var(--accent)`, fond `--accent-soft`, rayon `--r-sm` = 6 px, texte `.9375rem`/1,5 en `--ink-2`). Bloc « Comment composer votre devis » quand le panier est vide, en quatre étapes (« Choisissez une prestation », « Répondez à quelques questions — …», « Composez votre projet — …», « Contact & visite — …») et trois notes (« Estimable en ligne : prix immédiat indicatif. », « Sur devis : chiffrage après visite. », « Sans inscription, sans engagement. »). Cinq sections par catégorie (ancre `#slug`, h2 à 36 px, chapeau), grille 3 colonnes (2 sous 980, 1 sous 720). Aucun filtre ni recherche. Le seul paramètre d'URL lu est `?profil=`.

Carte. Image 16/10, h3 22 px, une phrase, un bouton dont le libellé dérive du mode. « Estimer mes travaux → » si estimable, « Décrire mon projet → » sinon. C'est la seule différence visible entre les deux modes tant que la carte n'est pas ajoutée. Une fois ajoutée, « dès N € HT » n'apparaît que si un prix existe. Panneau « Votre projet » et dock collant dès qu'une ligne existe. Règle d'exclusivité pour la rénovation complète (verrouille les autres cartes à 55 % d'opacité).

Proposition. Voir section 6, option A. Deux verbes seulement, « Estimer & réserver en ligne → » pour les forfaits et « Demander un devis → » pour le reste. Notes « Estimable en ligne : prix ferme de la grille. », « Sur devis : chiffrage après visite gratuite. », « Sans inscription, sans engagement. ». Pas d'exclusivité, Kayli Clinn traite une prestation par devis.

---

## 4. L'estimateur

### 4.1 Fonctionnement réel

#### Les sept étapes (peinture intérieure, `build/assets/Painting-DP4Hk8sY.js`)

Chaque étape est un écran de 100svh. Un bouton « Suivant → » reste désactivé tant que la réponse est vide. « ← Étape précédente » revient en arrière, libellé court « ← Précédent » sous 520 px (`useWizard.svelte-DHlHx5QX.js`, `.wizard-back-full` / `.wizard-back-short` dans `useWizard-8cGzw6Ku.css`, `@media (width<=520px)`). Les listes de choix viennent du serveur (`estimer/peinture-interieur/response.json`). Le reste du texte est codé en dur.

| # | Étape | Question | Réponses |
| --- | --- | --- | --- |
| 1 | `rooms` | « Quelles *pièces* souhaitez-vous rénover ? » | compteurs par pièce (7 types), au moins une pièce ; n'influe pas sur le prix ; lignes `.room-row` bord 1 px `--rule`, rayon `--r-md`, `.selected` bord accent et fond `--accent-soft` (`rooms-BA5oHWAY.css`) |
| 2 | `floorArea` | « Quelle *surface au sol* ? » | curseur 10 à 200 m², défaut 50, plus la part de murs (tous, trois, deux, un) |
| 3 | `height` | « Quelle *hauteur sous plafond* ? » | Standard (≤ 2,70 m), Haute (2,70 à 3,20 m), Très haute / loft |
| 4 | `ceilings` | « Faut-il peindre le(s) *plafond(s)* ? » | Oui / Non |
| 5 | `condition` | « Dans quel *état* sont les supports ? » | Bon état, Petites reprises, Fissures importantes, Support à refaire ; case « Dépose de papier peint » |
| 6 | `finish` | « Quelle *finition* ? » | Standard (mat), Velours / satin, Laque / haut de gamme ; case « Peinture des boiseries » |
| 7 | `clearance` | « La pièce sera-t-elle *dégagée* ? » | Vide, Meublée ; case « Déplacement de meubles lourds » |

Dès la deuxième étape, une barre collante affiche « À partir de X HT (Estimation en cours) », calculée avec les réponses connues et des valeurs de repli pour les autres (`std`, `bon`, `standard`, `vide`). Le repli `bon` vaut ×1,15. Pour 50 m², la barre affiche 4 146 € HT alors que l'accueil affiche 3 605 € HT avec l'hypothèse « support sain ». Ce n'est pas une erreur de formule, c'est une hypothèse différente. À éviter chez Kayli Clinn.

#### La formule (`build/assets/floorAreaParam-85nDwDiL.js`)

Coefficients reçus du serveur (`props.pricing`). `wallRatio` 2,5. `wallScopeMultipliers` 1 / 0,75 / 0,5 / 0,25. `heightMultipliers` 1 / 1,15 / 1,4. `conditionMultipliers` 1 / 1,15 / 1,35 / 1,65. `finishPricePerSqm` 18 / 28 / 45. `ceilingCoefficient` 1,1. `wallpaperRemovalPerSqm` 12. `clearanceMultipliers` 1 / 1,08. `woodworkFlatHt` 680. `heavyFurnitureMoveHt` 180. L'uplift est une prop distincte, `props.pricingPolicy.methodUplift` 1,03 (`"pricingPolicy":{"methodUplift":1.03}` dans `estimer/peinture-interieur/response.json` et dans `index.html`), lue par `Painting-DP4Hk8sY.js` (`O.pricingPolicy.methodUplift`) et par `Home-BEyYY4bd.js` (`t.pricingPolicy.methodUplift`). Le fichier `pricingPolicy-C_8sxp37.js` ne porte que l'arrondi, `function e(e,t){return Math.round(e*t)}`.

- Surface murale = surface au sol × 2,5 × part de murs.
- Murs = surface murale × prix/m² de la finition × hauteur × état × dégagement.
- Plafonds (si cochés) = surface au sol × prix/m² × 1,1 × état × dégagement.
- Dépose de papier peint = surface murale × 12.
- Boiseries 680, meubles lourds 180.
- Chaque poste est multiplié par 1,03 puis arrondi à l'euro (`Math.round(e*t)`). Le total est la somme des postes arrondis. Aucun minimum, aucun plafond, aucun frais fixe.

Exemple 1, celui de l'accueil. 50 m², tous les murs, standard, bon état, velours, vide, sans option. 50 × 2,5 = 125 m² de murs. 125 × 28 = 3 500. × 1,03 = 3 605 € HT. C'est la valeur rendue dans `index.html` (`<span class="amount">3 605 €<small> HT</small></span>`).

Exemple 2, complet. 50 m², hauteur haute, petites reprises, velours, meublée, plafonds, papier peint, boiseries, meubles. Murs 125 × 28 × 1,15 × 1,15 × 1,08 × 1,03 = 5 149. Plafonds 50 × 28 × 1,1 × 1,15 × 1,08 × 1,03 = 1 970. Papier peint 125 × 12 × 1,03 = 1 545. Boiseries 700. Meubles 185. Total 9 549 € HT.

Les cases à cocher affichent leur prix dérivé de la même formule (« +700 € HT », « +185 € HT », « ≈ +1 545 € HT »). La saisie manuelle accepte jusqu'à 2 000 m² (dix fois le maximum du curseur) et le prix est calculé dessus sans bascule vers un devis. Seul le paramètre d'URL `?surface=` est borné à 10–200.

#### Le récapitulatif (`EstimatorRecapDetailed-BlpRUYRg.js`)

Surtitre pilule « Prix indicatif », prix héros serif de 48 à 80 px, lignes par poste (« Peinture murs · finition velours » avec « 125 m² murs · 50 m² au sol »), « Total HT », note « Un devis ferme sera établi après visite technique gratuite, sans engagement. », colonne « Ajuster l'estimation » avec les trois options. Les coefficients ne sont jamais montrés comme lignes. Boutons « Ajouter et continuer » et « Ajouter et passer au devis → ».

#### Le panier (`quoteStore-BV9FCFuU.js`, `Cart-Lb1KgKps.js`)

Store persisté dans `localStorage` sous `lkh.quote.v5`, forme `{clientProfile, quoteAnswers, lines[], trackingPack}`. Une ligne par prestation `{serviceSlug, title, summary, options, estimatedPriceHt, state}`. Totaux calculés côté client. `totalHt` = somme des prix, `hasUnpriced`, `allUnpriced`, `unpricedCount`. Une ligne sans prix affiche « Sur devis après visite » et, dans le détail, « Chiffrage établi lors de la visite technique gratuite. ». Si tout est sans prix, le bloc de total devient « Devis établi sur rendez-vous — Ces prestations sont chiffrées ensemble lors de la visite technique. ». Sinon « Estimation totale indicative », montant, « + n prestation(s) sur devis, chiffrée(s) lors de la visite. » et « Estimation indicative HT, hors fournitures spécifiques et imprévus. Le devis établi après visite fait foi. ». Les 18 prestations sans estimateur dédié reçoivent leurs questions du serveur (`prestationQuestions`), avec deux questions partagées (« Type de bien », « Occupation ») regroupées dans « Votre bien ». Tous les prix sont HT, jamais de TTC dans le code livré au navigateur. Le grand montant du panier est le `.ht-line` serif `clamp(2rem,5vw,3rem)` de `PriceBreakdown-DvF3tBj3.css` (importé par `Cart-Lb1KgKps.js` et `Checkout-CNz-78om.js`), les options d'une ligne sont en mono 13 px (`OptionsList-B8IFGGAz.css`).

Retirer une ligne passe par un dialogue natif de confirmation (`ConfirmDialog-BaTm3En8.js`, importé par `Cart-Lb1KgKps.js` et `Catalog-DqgjFC4j.js`). Titre « Retirer cette prestation ? », message « « <titre> » sera retirée de votre devis. », boutons « Retirer » (fond `--danger`, `:focus-visible` `outline:2px solid var(--danger)`) et « Annuler ». `<dialog>` avec `::backdrop` `#17141273`, carte rayon `--r-lg` (14 px), bord `--rule`, ombre `--shadow-card`, largeur max 34rem (`ConfirmDialog-C4RVgHk_.css`). À reproduire chez Kayli Clinn s'il existe un jour une ligne à retirer (aujourd'hui les options se décochent, rien n'est détruit).

#### Les packs de suivi (`Checkout-CNz-78om.js`)

Trois packs du serveur. Essentiel / Chantier Visible à 0 %, Confort / Chantier Serein à 3 %, Premium / Chantier Signature à 6 %. Prix affiché = `Math.round(worksHt × rate)`, sur la carte (« X € HT », « soit +N % du montant de votre devis ») et sur une ligne « Suivi confort — Chantier Serein » (« Compris » à 0 %), ajouté dans « Total estimé … HT ». Recommandation automatique. Si une seule ligne chiffrée et travaux ≤ 3 000 € HT, Essentiel avec « Pour un chantier de cette ampleur, nous vous conseillons … ». Sinon selon le profil (particulier et autre → Confort, professionnel et syndic → Premium) avec « D'après votre profil, nous vous conseillons … ». « Le choix reste le vôtre. » Le pack est transmis par son slug seulement. Les seuils et la table par profil sont des données serveur, pas du code. `estimer/finaliser/response.json` porte `"trackingSimpleSiteRule":{"slug":"essential","maxLines":1,"maxWorksHt":3000}` et `"trackingRecommendations":{"individual":"comfort","business":"premium","syndic":"premium","other":"comfort"}` ; `Checkout-CNz-78om.js` les applique (`e.length>0&&e.every(e=>e.estimatedPriceHt!=null)&&e.length<=i.maxLines&&t<=i.maxWorksHt` → `{slug:i.slug,reason:'simple-site'}`, sinon `r[n]` avec `byProfile:l.trackingRecommendations`).

#### La finalisation (sept étapes)

Les listes de profils, de délais et d'accès viennent de `siteAccess-BGGoAY5z.js`, les libellés de rappel (moments, jours) de `timelineLabel-D4LfTjQf.js`, les huit départements de `departments-D52Qp1Dm.js`, la normalisation des réponses de `questionnaire-DuD1a0rm.js`, le repli « Sur RDV » de `format-VF7ANsSA.js` (`t==null?'Sur RDV':e.format(t)`) rendu par `PriceFallback-CgAP7Y1P.js` (`<span class="rdv">Sur RDV</span>`, mono `--muted` dans `PriceFallback-BTzPLsC5.css`, importé par `PriceValue` et `PriceBreakdown`) ; `legacy-D8G4tzsw.js` n'appelle qu'une fonction du bundle principal ; `arrays-DAm-0TJq.js` (tests de type) et `index-client-C8xbxNdL.js` (collections réactives) sont des utilitaires du framework sans texte ni style.

1. Profil « Vous êtes… » (Particulier, Professionnel, Syndic de copro, Autre ; `siteAccess-BGGoAY5z.js`). Aide « Cela nous permet d'adapter notre proposition (TVA, interlocuteur, documents). »
2. Rappel « Quand préférez-vous être *rappelé* ? ». Au moins un moment (Matinée 9h – 12h, Après-midi 13h – 17h, Fin de journée 17h – 19h), au moins un jour (lundi à samedi), un téléphone valide (`/^0[1-9]\d{8}$/` après normalisation de +33).
3. Visite. Calendrier hebdomadaire (`CalendarGrid-DQiZ-PP8.js`, prop `weeks` avec boutons semaine précédente / suivante, 6 pistes, bandes Matinée et Après-midi, créneaux barrés si indisponibles), chargé par un rechargement partiel Inertia avec les moments et jours préférés. Dans la capture, `visitAvailability` vaut `null`, aucune donnée de créneau n'existe. Opt-out « Aucun créneau ne convient ? Convenir d'un rendez-vous lors du rappel ». Confirmation « ✓ Vous avez choisi le <jour>, entre <h1> et <h2>. Ce rendez-vous vous sera confirmé lors de notre appel. »
4. Chantier « Où se trouve le *chantier* ? ». Huit départements en boutons, code postal à 5 chiffres qui recalcule le département dès deux chiffres, hors Île-de-France = étape bloquée. Accès (RDC / plain-pied, Étage avec ascenseur, Étage sans ascenseur, Accès difficile).
5. Délai (Dès que possible, Sous 1 mois, Sous 3 mois, Je me renseigne).
6. Suivi « Quel *suivi* souhaitez-vous pendant le chantier ? ».
7. Coordonnées. Aide « On vous envoie un code pour vérifier votre email : il vous ouvre l'accès à votre devis en ligne, que vous pourrez suivre à tout moment. »

Puis un récapitulatif « Et après l'envoi ? » modifiable ligne par ligne et une timeline « Rappel selon vos disponibilités → Visite technique gratuite → Devis ferme, sans engagement » (`Timeline-owFzQTCh.js`, `Timeline-DnvX-6OI.css`, `ul.timeline` avec filet 1 px `--rule` en `:before`, points 18 px bord 2 px `--rule` devenant accent sur `.step.active`, libellé serif `.9375rem`).

#### Le code de vérification

Le bouton « Envoyer ma demande → » déclenche `POST /estimer/verifier-email {email}`. Un champ de six chiffres (`inputmode="numeric"`, `autocomplete="one-time-code"`) valide dès six chiffres via `POST /estimer/verifier-email/confirmer {email, code}`. Erreur « Trop de tentatives. Patientez une minute. » sur 429, sinon « Code invalide ou expiré. ». « Renvoyer un code » relance le premier appel. Pas de SMS.

#### L'envoi

`POST /estimer/finaliser` par `useForm` Inertia. Le formulaire complet est envoyé (coordonnées, créneau ou rappel, adresse, accès, délai, pack, message, profil) et, pour chaque ligne, uniquement `{serviceSlug, state}`. Aucun montant ne quitte le navigateur. Aucune page de succès n'est capturée. Le bundle principal déclare une page `Estimator/Confirmation` et une page `Prospect/Tracking`, absentes de l'extraction.

### 4.2 Correspondance avec le tunnel Kayli Clinn

Le tunnel Kayli Clinn (`site/estimation.html` pour `/devis/`, `site/reservation.html` pour `/reservation/`, extensions `kc-devis`, `kc-booking`, `kc-visite-audit`) est différent par nature. Trois parcours (forfait, pro, audit), une prestation par devis, un créneau réel, un paiement Stripe pour les forfaits, un recalcul serveur systématique. Ce tableau dit ce qui existe, ce qui manque et ce qui demande un développement serveur ou une décision.

| Élément LKH | Kayli Clinn aujourd'hui | Statut |
| --- | --- | --- |
| Catalogue de cartes, libellé dérivé du mode | étape `presta` de `/devis/`, `KC_PRESTAS` | existe, habillage à revoir |
| Lecture de `?profil=`, `?surface=` | `estimation.html` ne lit aucun paramètre d'URL | manque (petit, bloc) |
| Bloc « Comment composer votre devis » | `step-desc` | existe, bloc à quatre étapes facultatif |
| Une question par écran, « Suivant → » bloqué si vide | avance automatique à 450 ms, `validateAllFields` | existe |
| Compteur de pièces, surface au m², hauteur, finition | typologie Studio à T5, surface bornée pour chantier et bureaux | ne pas reproduire, la grille v2 est par typologie |
| État des supports ×1,15 à ×1,65 | `etat` (+30 %, très dégradé → audit), `niveau` 4,50/7/11, `vide` (+15 %) | existe |
| Options à cocher avec prix dérivé | étape `options` (prix, quantités, minimums, compatibilités) | existe |
| Barre collante « À partir de X HT » | `pb-progress` sans prix | manque (bloc), forfaits seulement, sans hypothèse cachée |
| Récapitulatif par poste, total, note, colonne « Ajuster » | `result`, `r-detail`, acompte, `result-note` | existe, colonne « Ajuster » facultative |
| Repli « Sur RDV » si prix null | bascules vers la visite (hors grille, très dégradé, vitres hauteur, B2B, mots sensibles) | existe, mieux |
| Persistance `localStorage` au clic | `sessionStorage` `kc_devis_progress` à chaque étape, bannière de reprise | existe |
| Panier multi-prestations, exclusivité | une prestation par devis ; les « lignes » sont les options | ne pas reproduire sans décision (question 3) ; exigerait `KC_Pricing::compute_quote(lines[])` dans les deux extensions |
| Uplift 1,03 caché | formule (base + options) × (1 + majorations) + frais fixes, tout visible | ne pas reproduire |
| Prix toujours HT | TTC particuliers / HT pros, explicite | existe |
| Profil client | absent | à demander (question 4), puis bloc + `kc-devis` |
| Préférences de rappel | téléphone validé, pas de plages | serveur (`kc-devis`, champs sanitisés, e-mail) + horaires à demander |
| Calendrier hebdomadaire, créneaux réels | `/reservation/`, `GET /wp-json/kc-booking/v1/availability` (Google Agenda + horaires + réservations), type gratuit `visite-audit` | existe, mieux (LKH n'a aucune donnée de créneau) ; vue hebdomadaire facultative |
| Opt-out « convenir lors du rappel » | envoi `kc_devis` en parcours audit | existe |
| Huit départements, CP → département | étape `zone`, `VALIDATORS.address` | existe |
| Accès au lieu, délai souhaité | absents | à demander (question 6), puis bloc + `kc-devis` ; l'accès sert au frais « étage ≥ 3 sans ascenseur 15 € » |
| Packs de suivi tarifés | absents | ne pas reproduire sans décision (question 7) |
| Code e-mail à six chiffres | nonce + honeypot + rate-limit ; Stripe joue ce rôle pour les forfaits | serveur, optionnel (question 8), `kc-booking` `verify-email` + `verify-email/confirm` pour `calendar_free` seulement |
| Envoi sans montant, recalcul serveur | `POST /bookings` ignore `amount_*` et recalcule via `KC_Pricing::compute_forfait` ; `kc_devis` recalcule | existe ; retirer `amount_total` et `amount_now` de `reservation.html` (lignes 721-722), inutiles |
| Page de confirmation, suivi | `st-confirm`, liens `?resa=token` (payer, annuler), e-mails, Google Sheet | existe, mieux |
| Timeline « rappel → visite → devis ferme » | trois lignes cochées dans `renderResult` audit | existe |

En résumé, Kayli Clinn a déjà ce que LKH n'a pas (créneaux réels, paiement, recalcul serveur, liens de gestion, TTC/HT). Ce qui manque est de l'habillage (une question par écran, barre collante, récapitulatif) et quelques champs de qualification qui demandent une décision.

---

## 5. Le guide de rédaction

Tout ce qui suit est mesuré sur les textes capturés. Les transpositions sont des propositions.

### 5.1 Le pivot

Un mot porte tout le site, « suivi ». Slogan « Travaux suivis, pas subis » (uniquement sur l'accueil, `<title>` et h1). Baseline courte « Travaux suivis » partout (en-tête, pied de page, en-tête de l'estimateur). CTA final « Un prix en 5 minutes. Un chantier suivi jusqu'au bout. » (accueil et À propos). Nom de l'offre « niveau de suivi ». Les paliers suivent le motif « Chantier + adjectif » sans le mot suivi. La locution d'encadrement « du devis à la réception » revient au moins cinq fois.

Transposition. Le pivot de Kayli Clinn est à choisir par la propriétaire (question 11). « Un espace propre, l'esprit tranquille. » est déjà en place. Une baseline courte de deux mots pour l'en-tête et le pied de page reste à trouver. Locution d'encadrement possible, « de la réservation au contrôle final ».

### 5.2 Les titres

- h1 de 2 à 5 mots. « Des travaux suivis, pas subis. », « Qui sommes-nous ? », « Nos dernières réalisations. », « Nos prestations. ».
- h2 de tête de section de 2 à 14 mots, casse de phrase, point final sur 11 des 16 h2 éditoriaux, « ? » sur 2. Les titres nominaux (pied de page, projets, panier) n'ont pas de point.
- Un fragment en `em` doré, gras, droit, en position variable. « Nos chantiers, *avant et après.* », « Votre chantier reste *lisible*, du devis à la réception. », « Trois principes, appliqués sur *tous* nos chantiers. ». Certains h2 n'en ont pas (« Questions fréquentes »).
- Surtitre au format « Verbe · Complément » sur cinq des huit surtitres de l'accueil (« Estimer · Comment ça se passe », « Estimer · Catalogue », « Suivre · Le chantier », « Recevoir · Ils nous font confiance », « Recevoir · Selon votre profil »). Trois verbes structurent l'accueil, Estimer, Suivre, Recevoir. Les trois autres surtitres de l'accueil (« Entreprise générale du bâtiment · Île-de-France », « Avant · Après », « FAQ ») et les cinq des pages intérieures (« Notre façon de travailler », « Le suivi de chantier », « Qui nous sommes », « Qui réalise les travaux », « Nos chantiers ») sont nominaux (13 `class="eyebrow"` sur les trois HTML).
- h3 de principes en phrase sans point. « Ce qui sera invisible est photographié », « Ce qui change est écrit avant d'être fait », « Une seule personne répond ».
- h3 d'étapes en phrase avec « Vous ». « Vous réceptionnez le chantier, avec nous. »

Transposition. Surtitres « Estimer · Réserver · Vérifier ». h2 « Votre passage reste *lisible*, de la réservation au contrôle final. » h3 de principes « Ce qui est nettoyé est contrôlé », « Ce qui s'ajoute est chiffré avant d'être fait ». `em` teal droit, jamais italique.

### 5.3 Les chapeaux

Treize chapeaux `.lede` de 15 à 48 mots (moyenne 30), le plus souvent deux phrases (8 sur 13), articulées dans 11 cas par un tiret cadratin ou un deux-points. L'impératif n'apparaît que devant un élément interactif (« faites glisser la poignée », « Choisissez qui vous êtes », « Sélectionnez vos travaux »). Les chapeaux de présentation sont à l'indicatif.

Exemples. « De la première question à la réception. Vous gardez la main — on ne récupère vos coordonnées que si vous décidez d'aller plus loin avec nous. » « Choisissez qui vous êtes : les échanges, les documents et le niveau de reporting suivent. »

Transposition. « Choisissez votre logement : le prix ferme et les options suivent. » « De la réservation au contrôle final. Vous gardez la main — rien n'est facturé qui n'ait été affiché avant. » (engagement de service à valider par la propriétaire)

### 5.4 Les boutons

Infinitif en tête dans la plupart des cas, sans point ni exclamation, 1 à 6 mots sauf deux exceptions longues. Les CTA du tunnel ajoutent un possessif client. « Estimer mes travaux → », « Commencer mon estimation → », « Demander mon devis → », « Envoyer ma demande → », « Décrire mon projet → », « Voir mon panier → ». Les boutons de palier sont à la première personne, « Je choisis ce suivi », « Je garde ce suivi », avec un état à l'infinitif « Changer pour ce suivi ». La flèche accompagne la plupart des actions de progression mais pas exclusivement (elle figure sur « Modifier → » et « Retour au catalogue → », elle manque sur « Estimer maintenant »). Le retour utilise « ← Étape précédente », raccourci en « ← Précédent » sous 520 px (`useWizard-8cGzw6Ku.css`).

Transposition. Kayli Clinn garde ses deux verbes officiels, « Estimer & réserver en ligne → » et « Demander un devis → ». Dans le tunnel, le possessif est possible, « Réserver mon créneau → », « Envoyer ma demande → ». Choix de parcours, « Je choisis ce parcours ».

### 5.5 Les pilules et micro-réassurances

Groupes nominaux de 2 à 4 mots, sans ponctuation, deux sur trois en « Sans ». « Sans email ni téléphone », « Sans engagement », « Devis détaillé et clair ». Formes voisines, « Sans inscription, sans engagement. » (catalogue), « La visite est gratuite et sans engagement. » (finalisation), « Inclus dans chaque devis », « HT · fourchette indicative ».

Transposition. « Sans engagement », « Prix ferme sur les forfaits », « Devis écrit avant intervention ». « TTC · prix ferme » sous les montants forfait, « HT · estimation indicative ±15 % » pour la fin de chantier.

### 5.6 Les cartes prestation

Titre nominal de 1 à 4 mots sans article. Une seule phrase nominale de 4 à 15 mots, sans verbe conjugué, toujours terminée par un point. Douze descriptions sur 20 sont des énumérations nues (« Réseaux, sanitaires et recherche de fuite. »). Huit ajoutent un tiret cadratin entre l'énumération et le périmètre (« Murs et plafonds — fourniture et pose, préparation des supports incluse. », « Zinc, aluminium ou PVC — fourniture et pose. »).

Transposition. « Électroménager, vitres intérieures et placards — inclus, logement vide. » (fin de bail) et « Studio à T4 — ménage complet, linge et consommables en option. » (turnover), conformes à la grille v2. Pour la vitrerie, « Vitres … — hauteur, vitrine et verrière sur devis. ». Les bascules sont conformes à la grille v2, mais le périmètre intérieur/extérieur de la vitrerie résidentielle n'est pas précisé dans `CLAUDE.md` et reste à valider par la propriétaire avant d'écrire « intérieures et extérieures accessibles ».

### 5.7 La FAQ

Six questions de 4 à 8 mots terminées par « ? », cinq sur six à inversion sujet-verbe, deux seulement à la voix du client. Réponses en un paragraphe de 23 à 38 mots, deux phrases, précédées dans deux cas d'un « Non. » ou « Oui. » sec. Trois réponses sur six se closent par le bénéfice client. Les réponses parlent de « Le client » et de « LKH » à la troisième personne.

Exemple. « Comment sont gérés les travaux supplémentaires ? » — « Toute prestation qui n'était pas prévue dans le périmètre initial doit être décrite, chiffrée et validée avant son exécution. Cela évite les ajouts non compris et les mauvaises surprises. »

Transposition (à valider par la propriétaire). « Le prix affiché est-il définitif ? » — « Oui pour les forfaits. Il dépend de la typologie, des options, des majorations affichées et des frais d'étage. Un logement hors grille, un état très dégradé, des vitres en hauteur, un entretien professionnel récurrent ou une situation particulière renvoient vers un devis après visite. » (les cinq bascules du §4.2, au lieu des deux seules citées auparavant).

### 5.8 Chiffres et délais

Tout prix porte « HT » et une qualification (« indicative », « fourchette », « dès », « ~ », « sur RDV »). « TTC » n'apparaît nulle part. Les délais, eux, sont énoncés sans qualificatif (« Un prix en 5 minutes », « Démarrage sous 2 semaines », « sous 48 h »). « ±10 % » est une note d'aide à la mesure réutilisée pour la peinture et les gouttières. Espace insécable avant %, €, h, m². Virgule décimale. Séparateur de milliers = espace.

Transposition. TTC pour les particuliers, HT pour les pros, dit sans ambiguïté. Aucun délai ni compteur sans validation de la propriétaire.

### 5.9 Ton

Vouvoiement exclusif (0 tutoiement). Deux voix pour l'entreprise. Le « nous » d'engagement (À propos, indications du formulaire, « nous vous conseillons ») et le « on » conversationnel sur toutes les pages (« on ne récupère vos coordonnées que si… », « On se parle quand vous le décidez », « on revient vers vous sous 48 h », « On vous envoie un code »). Le « je » est réservé au client (« Je ne sais pas », « Je choisis ce suivi », « Je suis à distance ou le projet est complexe »). Aucun point d'exclamation dans les textes de l'entreprise (les seuls sont dans un avis client). Aucun « leader », « expert », « n°1 », « passion », « promo ». Subsistent des superlatifs de gamme, « Premium », « haut de gamme », « meilleur compromis ». Vocabulaire technique assumé, « périmètre », « réserves », « traçabilité », « phasage ».

Transposition. Vouvoiement obligatoire (règle Kayli Clinn). Le « on » est possible, mesuré. Pas de « ! », pas de superlatif.

### 5.10 Ponctuation

Tiret cadratin « — » entouré d'espaces pour le périmètre et la relance, aussi seul comme symbole « non compris ». Deux-points pour annoncer une énumération ou une explication. Point médian « · » dans les surtitres et les métadonnées (« Estimer · Catalogue », « HT · fourchette indicative », « 3 à 5 minutes · sans email ni téléphone »). Points de suspension pour les listes ouvertes, les avis tronqués et les états d'attente (« Envoi du code… »). Point-virgule quasi absent. Espace insécable avant « ? », « : », « ; ».

### 5.11 Formulaires

Cent dix questions sur 110 finissent par « ? », 3 à 8 mots. Le mot clé en `em`, « Quelle *surface au sol* ? », « Où se trouve le *chantier* ? ». Aides en une phrase à l'indicatif (« L'état des supports détermine le temps de préparation. »). Erreurs à l'impératif avec précision chiffrée (« Numéro incomplet : 10 chiffres attendus (ou +33 6 12 34 56 78). », « Incomplet — 2/5 chiffres. »). Champs optionnels marqués « (optionnel) », obligatoires par « * ». Recommandation « D'après votre profil, nous vous conseillons … Le choix reste le vôtre. »

---

## 6. Plan de mise en œuvre priorisé

P1 = écart de fond visible. P2 = composant. P3 = finition. Chaque page reste un bloc HTML avec son préfixe.

### 6.0 D'abord, transversal

| Prio | Changement | Cible | Réutilisable | À refaire |
| --- | --- | --- | --- | --- |
| P1 | `CHARTE-LKH.md` | réécrite avec les valeurs réelles (fait, voir le fichier) | — | — |
| P1 | Boutons | rayon 10 px, `13px 20px`, Inter 14,7 px 600, `line-height:1`, socle 1 px, `.btn-lg`, `[disabled]` ; garder le `:focus-visible` Kayli Clinn | `.btn-confirm`, `.btn-state`, `.cta-submit-*` du tunnel | pilules 999 px de `.kcl-btn`, `.kc-btn`, `#kc-alignement` |
| P1 | Surtitres | mono 12,5 px 600, 0,08 em, tiret 18×1 px teal, gap 10 px, format « Verbe · Complément » | `.head-tag`, `.step-tag`, `.result-tag` (tiret teal déjà là) | `.eyebrow` Inter 11,5 px des pages `.kcl` et `.kc-page` |
| P1 | Emphase des titres | `em` teal, droit, 700 | — | `.kh-title em{font-style:italic}`, `.ka-h2 span`, italiques `.kcl` |
| P2 | Échelle en rem | corps 1rem/1,6, h1 clamp 32–56 px 700, h2 clamp 24–36 px, h3 22–23 px cartes, lede 1,1rem/1,7 60ch ; fixer `font-size:16px` sur le conteneur du bloc si le thème impose autre chose | — | tailles en px de l'ancienne charte (avant le 17/09) |
| P2 | Rythme | sections 96/64 px, filet 1 px entre sections non teintées, conteneur 1 180/24 px, seuils 520/720/980 | `.kc-page-wrap` (1 240 px, à ramener) | sections de 60 px |
| P2 | Rayons, ombres, sélection | 14/10/6 px, ombres tokenisées, anneau `0 0 0 1px` | états sélectionnés du tunnel | cartes à 20 px |
| P2 | Fonds | une zone sombre navy par page, teintes teal pâle, halo radial discret autorisé | `.kc-resa .guarantees` | deuxième et troisième zones sombres |
| P3 | Badges | `.badge` 12,5 px 600 999 px, seulement avec des délais validés | `.funnel-tag`, `.result-audit-badge` | — |
| P3 | Aside zone | une ligne « Nous intervenons en Île-de-France · … » dans le tunnel | étape `zone` (plus riche) | grilles de départements des pages |

### 6.1 Accueil (`accueil.html`, `.kc-home` + `#kc-alignement`)

| Prio | Changement | Réutilisable | À refaire |
| --- | --- | --- | --- |
| P1 | Hero en grille `1.15fr .85fr` aligné en bas, 88vh, deux boutons, trois pilules en verre, carte forfait inclinée à droite | h1 et slogan du hero `kh4`, les deux boutons prévus | centrage, ligne de preuve, zoom du fond |
| P1 | Réordonner en 12 blocs (phares, avant/après, comment ça se passe, mosaïque, trois parcours de prix, avis, profils, FAQ, CTA blanc) | `kcav2` (avis), `kcfq` (textes de la FAQ seulement ; l'accordéon JavaScript à 10 questions et 4 filtres est à remplacer par 6 `<details>` natifs sans filtre), section « réservation en 4 étapes » (fusionnée dans « Comment ça se passe ») | `ka-section` (À propos en 2 colonnes), `kcw2` (pourquoi nous, sombre), `kczn2` (zone), `kcct2` (CTA sombre) |
| P1 | CTA final blanc centré à halo, un bouton | — | `kcct2`, « Réponse sous 2 h », « Urgence chantier » |
| P2 | Bloc `#kc-alignement` | à remplacer par les vraies valeurs plutôt qu'un empilement de `!important` | `text-wrap:balance` sur h1 |
| P3 | Animations bornées (reveal 700 ms, onglets 5,2 s, marquee 48 s, balayage 2,4 s), toutes coupées en reduced-motion | `revealAll` et `reducedMotion` (11 `IntersectionObserver`, 8 `prefers-reduced-motion` dans `accueil.html`) | zoom, faisceau, grain, points pulsants restent retirés |

### 6.2 À propos (`a-propos.html`, `.kcl`)

| Prio | Changement | Réutilisable | À refaire |
| --- | --- | --- | --- |
| P1 | Supprimer le hero photo et l'encadré signature, passer à un `page-head` (fil d'Ariane, h1 « Qui sommes-nous ? », chapeau) | fil d'Ariane, textes | `.kcl-hero`, `.kcl-tag` |
| P1 | Ordre réel. méthode (3 principes, fond pâle) → trois parcours de prix → entreprise (sombre, fiche à 6 lignes dedans) → partenaires + 5 puces → 3 cartes chantiers → CTA final blanc | 3 principes, texte partenaires, fiche (placeholders conservés) | « Pourquoi nous existons », « Notre signature » (`.kcl-quote`), FAQ et JSON-LD `FAQPage`, CTA sombre « Les prochaines étapes », cartes de liens internes ; garder `BreadcrumbList` et `LocalBusiness` |
| P2 | Fiche légale à 6 intitulés (Raison sociale, SIRET, Siège, Direction, Assurances, Contact) | — | lignes « Activité » et « Zone » |
| P2 | Cartes chantiers avec `width`/`height` réels, `loading="lazy"`, jamais de nom de particulier | `realisations.html` | — |
| P3 | Numéros « 01 » en mono 12,5 px, retirer `.kcl-preuve{border-top:3px}` et les puces rondes | `.kcl-rv` (reveal) | — |

### 6.3 Pages prestations (`commerces-retail.html` `.kc-page`, `direction-epuree/*.html` `.kcl`, `prestation.html`, `prestations.html`)

Deux options, à trancher (question 3 de la section 1).

- **Option A, fidèle au site réel.** `prestations.html` devient le catalogue. Cinq sections par famille avec ancres, grille 3/2/1, une carte par prestation (image 16/10, h3, une phrase au motif « énumération — périmètre », un bouton dont le libellé dérive du mode), bloc « Comment composer votre devis » en quatre étapes, aside Île-de-France, aucun filtre. Les 21 pages éditoriales deviennent des pages catégorie courtes ou disparaissent.
- **Option B, pages éditoriales réduites.** Chaque page garde `page-head` (h1, chapeau, sans photo), carte phare, « Comment ça se passe » en 4 étapes (pas 6), trois parcours de prix, FAQ à 6 `<details>`, CTA final blanc. Suppression des blocs qui n'existent pas sur le site réel. Sélecteur « Votre situation ressemble à », filtre par famille, cartes « inquiétude → Notre réponse » (`.kcl-inq`, `.kc-why`), « Une règle sans exception », suivi sombre à citation (`.kcl-quote`), bande sombre « visite » (`.kcl-visite`), liens internes en cartes.

Dans les deux cas. Un seul bouton par carte, deux libellés. `.kc-btn` et `.kcl-btn` passent de la pilule au rayon 10 px. Les surtitres passent en mono. `commerces-retail.html` perd `.kc-banner` et `.kc-promise` (deux zones sombres de trop). `direction-epuree/entretien-de-bureaux.html` garde ses trois cartes « Trois façons d'obtenir un prix » (`.kcl-pack--reco`, carte du milieu recommandée = bord teal, fond pâle, anneau 1 px), c'est l'équivalent exact des trois niveaux LKH. Les témoignages gardent leur badge « Exemple ».

### 6.4 Tunnel (`estimation.html`, `reservation.html`, `demande-de-devis.html`)

La logique ne change pas. Seul l'habillage évolue.

| Prio | Changement | Réutilisable | À refaire ou ajouter |
| --- | --- | --- | --- |
| P1 | Lecture de `?presta`, `?taille`, `?profil` au chargement, valeurs validées, reste ignoré | `sessionStorage` existant | ajout (bloc) |
| P1 | Écrans d'étapes, une question par écran, `em` sur le mot clé, cartes radio avec anneau de sélection, « Suivant → » désactivé si vide, « ← Étape précédente » | flux `KC_PRESTAS`, `validateAllFields`, `showStep` (focalise le h2, mieux que LKH) | habillage |
| P1 | Barre collante « À partir de X TTC (Estimation en cours) » pour les forfaits seulement, prix de base tant que rien n'est coché ; fourchette pour le parcours pro ; rien pour l'audit | `calcForfait` | ajout (bloc) |
| P1 | Récapitulatif. Surtitre pilule « Prix ferme » / « Estimation indicative » / « Sur devis », prix héros serif 48–80 px teal, lignes par poste avec majorations et frais en lignes distinctes (plus honnête que LKH), « Total TTC » ou « Total HT », note, colonne « Ajuster l'estimation » | `result`, `r-detail`, `result-note` | colonne « Ajuster » (facultative) |
| P1 | Libellés. « Sur devis après visite », « Chiffrage établi lors de la visite gratuite. » ; jamais « rendez-vous » | parcours `audit` | Le repli LKH « Sur RDV » (`format-VF7ANsSA.js`) se transpose en « Sur devis après visite » ; `estimation.html` n'a rien à remplacer sur ce point (aucun « Sur RDV » dans `site/*.html` ni `site/direction-epuree/*.html`) |
| P2 | Finalisation. Profil en puces, préférences de rappel, accès au lieu, délai souhaité (valeurs Kayli Clinn à demander, jamais celles de LKH) | `contact`, `zone`, `VALIDATORS.address` | serveur `kc-devis` (champs sanitisés, meta, e-mail) |
| P2 | Vue hebdomadaire du calendrier (6 pistes, bandes Matinée / Après-midi, créneaux barrés) | `GET /availability` | CSS/JS facultatif |
| P2 | Vérification par code e-mail pour les visites gratuites | nonce, honeypot, rate-limit | serveur `kc-booking` si décidé (question 8) |
| P3 | Aside Île-de-France en tête d'estimateur ; retirer `amount_total` et `amount_now` de `reservation.html` | étape `zone` | nettoyage |

### 6.5 Ce qu'il ne faut pas reprendre du site réel

`lang="en"` et `noindex, nofollow`. Doublons de `<title>` et de meta description. Absence de `<main>` sur l'accueil, absence de lien d'évitement, absence de `:focus-visible` sur les boutons. `-webkit-backdrop-filter` seul. Images sans `srcset`, hero non préchargé, comparateur sans `width`/`height`. Noms de clients particuliers dans les données de page. Les délais et compteurs LKH. Le calcul du prix uniquement côté navigateur. L'uplift de 1,03 caché. L'hypothèse d'état par défaut qui change le prix sans le dire. La saisie de surface sans plafond.

---

## 7. Questions à trancher par la propriétaire

Aucune de ces réponses ne sera inventée. Tant qu'une question est ouverte, la page garde un placeholder visible ou n'affiche pas l'élément.

1. Police des titres. Fraunces (déjà autorisée pour l'éditorial) sur toutes les pages, ou une autre serif. Libre Baskerville n'est pas dans la charte.
2. Police monospace pour les surtitres, jalons et petits prix. Roboto Mono ou une mono système. JetBrains Mono n'est pas dans la charte.
3. Pages prestations. Option A (catalogue de cartes + estimateur) ou option B (pages éditoriales réduites). Et faut-il un panier multi-prestations (même créneau, remise) ou une prestation par devis comme aujourd'hui.
4. Profil client dans le tunnel (Particulier, Hôte Airbnb, Syndic & bailleur, Entreprise & commerce). Libellés, effet sur TTC/HT, effet sur le parcours.
5. Ordre du tunnel. Résultat chiffré avant ou après les coordonnées. La pilule « Sans email ni téléphone » n'est possible que si le prix s'affiche avant.
6. Qualification des demandes. Plages réelles de rappel (matin, après-midi, soir, quels jours), délai souhaité, accès au lieu (lien avec le frais « étage ≥ 3 sans ascenseur »).
7. Niveau de service. Existe-t-il un équivalent des niveaux de suivi (photos, fiche d'intervention, reporting QSE), gratuit ou tarifé. Sinon, pas de section « suivi ».
8. Code de vérification par e-mail sur les visites gratuites. Utile ou friction inutile.
9. Engagements de délai affichables. Durée de l'estimation, délai de réponse, délai de démarrage. Rien ne s'affiche sans votre validation.
10. Données et médias. Photos avant/après avec accord des clients, avis Google réels (note, nombre, extraits), logos clients avec accord, fiche entreprise (année de fondation, dirigeante, SIRET, siège, assureur et montant RC Pro).
11. Accroche et baseline courte de deux mots pour l'en-tête et le pied de page.
12. Certibiocide. Obtenu ou non. Sinon les prestations 3D restent hors mosaïque et hors tunnel.
13. Garde-fous encore ouverts dans `CLAUDE.md` (minimum d'intervention B2C, dimanche +25 ou +50 %, zonage grande couronne, crédit d'impôt). Rien dans ce rapport ne les préjuge.

---

## 8. Limites de l'analyse

- Sept réponses de page sur 21 composants déclarés. Pas de page catégorie, pas d'estimateur autre que la peinture, pas de page de confirmation, pas d'espace de suivi prospect, pas d'administration, pas de `/mentions-legales` ni `/nous-rejoindre`.
- Aucune réponse POST. L'envoi de la finalisation, l'envoi du code et sa confirmation ne sont connus que par le code du navigateur. Le recalcul serveur du prix est une déduction.
- Aucun en-tête HTTP. Cache, compression, politique de sécurité ne sont pas vérifiables. Cloudflare n'est attesté que par le script d'obfuscation des e-mails.
- `visitAvailability` vaut `null` dans la capture. Le format réel des créneaux (durée, horizon, libellés) n'est pas connu.
- Les six estimateurs génériques (placo, nettoyage fin de chantier, menuiserie, façade, isolation, gouttière) reçoivent des multiplicateurs dans `prestationQuestions`, mais ni leurs prix de base au m² ni leur formule ne sont dans l'extraction.
- Les fichiers `/logos/*`, `favicon.ico` et `apple-touch-icon.png` sont référencés mais absents.
- Le rendu réel des graisses non chargées (Inter 700 et 800, italiques) et l'effet de `-webkit-backdrop-filter` dépendent du navigateur, non observables ici.
- Le bundle est minifié. Le verbe surligné du profil « Syndic & gestionnaire » n'a pas pu être isolé avec certitude.
- Le site capturé est une préproduction (`noindex`, `lang="en"`). Le site de production peut différer.
- Le fichier `Projects-CtwxOvND.js` de l'extraction a été reconstitué à la main.
