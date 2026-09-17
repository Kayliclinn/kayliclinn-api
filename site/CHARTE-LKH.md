# Charte de référence LKH — à appliquer à kayliclinn.fr

> **Note de version (17/09/2026).** La version précédente de cette charte avait été extraite de fichiers de refonte LKH jamais publiés (dont `cd11705a-apropos.html`). Ses valeurs (Fraunces, anthracite `#19191a`, or `#bf9e33`, boutons pilule, sections de 60 px, cartes à 20 px, douze blocs par page prestation, tailles en px) ne correspondent pas au site en ligne. Cette version décrit le site **réel** `lkh.lucassistant.com` (Laravel + Inertia + Svelte 5), à partir de l'extraction `LKH_frontend_public_v3` vérifiée en neuf axes. Le rapport complet est dans `ANALYSE-LKH-REEL.md`. Les preuves sont dans `build/assets/app-BDw0yY9K.css` sauf mention contraire.

Seules les couleurs et les polices changent pour Kayli Clinn. Navy `#0D2340` à la place de l'encre brun-noir, teal `#0FA7A5` à la place de l'or, neutres froids à la place des neutres beiges. Les polices de titres et monospace sont à trancher par la propriétaire (Libre Baskerville et JetBrains Mono ne sont pas dans la charte Kayli Clinn). Tout le reste se transpose. Les règles non négociables de `CLAUDE.md` priment (un bloc HTML par page, un préfixe CSS par bloc, icônes SVG, vouvoiement, aucune donnée métier inventée, montants recalculés par `KC_Pricing`).

## 1. Typographie

Les tailles de texte du site réel sont en rem sur une base de 16 px non redéfinie, sauf quelques étiquettes mono à 10–11 px (`.eyebrow`, `.project-tag` 11 px, `.compare-tag` 10 px dans `app-BDw0yY9K.css`, `.review figcaption` 11 px dans `Home-5XXTBcEd.css`) et les flèches de galerie `.gallery-nav` 22 px ; `.hero-lede` 17 px et `.page-hero h1{font-size:clamp(34px,5vw,52px)}` sont aussi en px mais ne sont utilisés par aucune page ni aucun bundle capturés ; les paddings mêlent jetons et px (`13px var(--space-5)` sur les boutons, `18px 22px` sur `details.faq`, `var(--space-6) 26px` sur `.feat-card .body`, `28px` sur `.card`). Les tailles ci-dessous sont converties à 16 px. Trois familles auto-hébergées, six faces seulement (Libre Baskerville 400/700, Inter 400/600, JetBrains Mono 400/800), aucune italique. Chez Kayli Clinn, Google Fonts reste la seule option (pas d'accès FTP).

| Élément | Police LKH (Kayli Clinn) | Taille | Graisse | Interligne | Autres |
| --- | --- | --- | --- | --- | --- |
| Corps de page | Inter (Inter) | 16 px (`1rem`) | 400 | 1,6 | couleur encre (`--ink` → navy) |
| Titre de page (h1, hero de l'accueil) | Libre Baskerville (Fraunces, à valider) | `clamp(2rem, 4vw + 1rem, 3.5rem)` = 32 à 56 px | 700 | 1,2 | lettrage −0,01 em ; sur le hero blanc, ombre `0 2px 30px #00000059`, largeur max 22ch |
| Titre de page intérieure (h1 du `page-head`) | idem | `clamp(2rem, 4vw, 3.25rem)` = 32 à 52 px | 700 | 1,15 | lettrage −0,01 em hérité du `h1` global ; `.page-head h1` (À propos, Réalisations) |
| Titre de section (h2) | idem | `clamp(1.5rem, 2vw + 1rem, 2.25rem)` = 24 à 36 px | 700 (gras navigateur) | 1,2 | un fragment en `em` accent, droit, 700 |
| Titre de carte (h3) | idem | 22 px carte prestation, 23,2 px carte phare, 18 px niveau, 17,6 px principe | 700 | 1,2 (cartes prestation et phare), 1,3 (niveau et principe) | le `h3` global à 24 px n'est jamais effectif ; `.levels h3` et `.principle h3` sont à `line-height:1.3` |
| Question d'étape (estimateur) | idem | `clamp(1.75rem, 3vw, 2.5rem)` = 28 à 40 px | 700 | 1,2 | mot clé en `em` |
| Sur-titre éditorial (eyebrow) | JetBrains Mono (mono à valider) | 12,5 px (`.78rem`) | 600 | | capitales, espacement 0,08 em, couleur accent foncé (`--accent-2`) sur clair, accent sur sombre ; tiret 18×1 px accent devant, gap 10 px ; cinq surtitres sur huit de l'accueil suivent « Verbe · Complément », les pages intérieures utilisent des surtitres nominaux |
| Sur-titre pilule | JetBrains Mono | 11 px | 700 | | capitales, 0,18 em, bord 1 px filet, fond blanc, rayon 999 px, point rond 6 px ; un seul usage (« Prix indicatif ») |
| Chapeau (lead) | Inter | 17,6 px (`1.1rem`) | 400 | 1,7 | couleur `--muted`, largeur max 60ch ; 17 px sans limite dans un en-tête de page intérieure ; blanc à 52ch dans le hero |
| Texte de carte | Inter | 15 à 15,2 px (`.9375` à `.95rem`) | 400 | 1,5 à 1,65 | `.feat-card p` `.95rem` sans interligne déclaré, `.service-card-sub` `.9375rem`/1,5, `.review blockquote` `.95rem`/1,6 (`Home-5XXTBcEd.css`), `.principle p` `.94rem`/1,65 (`About-DLPoKGHW.css`) |
| Tag de projet | JetBrains Mono | 11 px | 400 (aucune graisse déclarée) | | capitales, 0,15 em, accent foncé (`.project-tag`) |
| Tag avant/après | JetBrains Mono | 10 px | 600 | | capitales, 0,18 em, blanc sur `#171412a6`, rayon 6 px (`.compare-tag`) |
| Kicker de carte phare | JetBrains Mono | 11,5 px (`.72rem`) | 400 (aucune graisse déclarée) | | capitales, 0,08 em (« Prestation phare », `.feat-card .kicker`) |
| Kicker de la carte du hero | JetBrains Mono | 10,9 px (`.68rem`) | 700 (rendu 800) | | capitales, 0,14 em (`.kicker.svelte-1o4w8lg`, `Home-5XXTBcEd.css`) |
| Pilule de réassurance (hero) | Inter | 13,8 px (`.86rem`) | 500 | | blanc sur `#ffffff1a`, bord `#fff3`, rayon 999 px, flou 8 px, coche SVG 14 px accent |
| Badge | Inter | 12,5 px | 600 | | rayon 999 px, `5px 11px`, icône SVG 12 px |
| Fil d'Ariane | Inter | 13 px | 400 | | séparateur « › », dernier item `span aria-current` |
| Bouton | Inter | 14,7 px (`.92rem`) | 600 | 1 | rayon 10 px, padding `13px 20px`, flèche « → » dans un `span` animé de 3 px |
| Grand bouton (`.btn-lg`) | Inter | 16 px | 600 | 1 | padding `16px 24px` |
| Grand prix | Libre Baskerville | `clamp(3rem, 8vw, 5rem)` = 48 à 80 px | 400 (hérité, jamais déclaré) | 1 | couleur accent ; carte du hero 30,4 px `tabular-nums` |
| Petits prix | JetBrains Mono | 12 à 14 px | 400 | | accent ou accent foncé |
| Notes secondaires | Inter | 13 à 14 px | 400 | | `--muted`, oblique synthétique sur 7 notes du tunnel |
| Pied de page | Inter | 13,6 px | 400 | | |

Règles d'emphase. `em{color:accent;font-style:normal}` sur tout le site, `em.accent` ajoute `font-weight:700`. Aucun titre en italique. Titres en casse de phrase. Les h2 éditoriaux prennent un point final (11 sur 16) ou un « ? » ; les titres nominaux (pied de page, projets, panier) n'ont pas de point.

## 2. Couleurs (équivalence)

| Jeton LKH | Valeur réelle | Kayli Clinn |
| --- | --- | --- |
| `--ink` | `#171412` | `#0D2340` |
| `--ink-2` | `#2f2822` | `#24384F` (proposition) |
| `--muted` | `#5f5850` | `#5B6B7D` |
| `--rule` | `#e8e2d2` | `rgba(13,35,64,.10)` (navy à 10 %, comme `--line` de `commerces-retail.html`) ; `#E1E7EE` en est une approximation opaque, l'exact étant `#E7E9EC` |
| `--accent` | `#c9a227` | `#0FA7A5` |
| `--accent-2` | `#9a7a1c` | `#076E6D` |
| `--accent-soft` | `#fbf7ee` | `#EAF6F5` (proposition) |
| `--ink-surface` | `#171412` | `#0D2340` |
| `--on-ink` / `--on-ink-strong` / `--on-ink-rule` | `#ffffffad` / `#fff` / `#ffffff1f` | identiques |
| `--ok` / `--danger` | `#2f6b4f` / `#8a2020` | même logique, valeurs à fixer |
| `--danger-bg` / `--danger-border` | `#fff4f4` / `#f6c5c5` | même logique, valeurs à fixer |
| `--lkh-grey` | `#8a8580` (registre RCS/SIRET du pied de page) | gris neutre à fixer |
| `--photo` / `--photo-soft`, `--report` / `--report-soft`, `--check` / `--check-soft` | `#2e6f85` / `#e9f2f6`, `#8a5a2b` / `#f6efe6`, `#2f6b4f` / `#eaf3ee` (les trois pastilles photo, compte rendu et check, `FinalCta-DOUYX8pg.css`) | inutiles sauf si les pastilles sont reprises |
| `--ease-out` / `--ease-in-out` | `cubic-bezier(.2,.7,.2,1)` / `cubic-bezier(.5,.05,.2,1)` | identiques |
| `--z-base` … `--z-overlay` | 0 / 1 / 10 / 30 / 40 / 50 | identiques |

Répartition observée. Accent = fonds de boutons pleins, bords et anneau de sélection, focus, gros prix, `em`, texte accent sur fond sombre. Accent foncé = texte accent sur fond clair (sur-titres, liens de carte), fond des boutons pleins au survol, socle d'ombre des boutons. Accent pâle = fonds d'attente des images, encarts, sections teintées, états sélectionnés. Le fond de page porte un halo radial accent à 6 % en haut à gauche (`body{background:radial-gradient(circle at 20% 10%, #c9a2270f 0%, transparent 60%), var(--bg)}`).

## 3. Espacements et gabarit

- Échelle unique. 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96 px (`--space-1` à `--space-24`).
- Largeur de contenu 1 180 px, gouttières 24 px (`.container`). Variantes à 1 100 px.
- Sections de 96 px en haut et en bas, 64 px sous 720 px (`.section-block`). Filet 1 px `--rule` entre deux sections consécutives non teintées, jamais autour d'une section teintée.
- En-tête de section = sur-titre + h2 + chapeau à 60ch, chacun séparé de 20 px, centré ou aligné à gauche avec un bouton fantôme à droite. Aucun filet sous le titre.
- Grilles à 14, 16, 24, 28 ou 32 px d'écart (`.cta-row` 14 px, `.featured-grid` 28 px à partir de 720 px, `.sites` 32/24 px, `Home-5XXTBcEd.css`). Cartes à coins 14 px, bord 1 px `--rule`, padding 24 à 28 px. Boutons, champs, onglets à 10 px. Petits contrôles et étiquettes sur photo à 6 px. Pilules à 999 px.
- Ombres. Repos `0 1px 0 var(--rule), 0 24px 48px -28px <encre à 18 %>`. Survol `0 1px 0 var(--accent), 0 24px 56px -24px <accent à 28 %>` avec remontée de 2 px (4 px sur les cartes phares).
- Sélection = bord accent + fond accent pâle + anneau `box-shadow:0 0 0 1px var(--accent)`. Focus clavier = `outline:2px solid var(--accent); outline-offset:2px` (le site réel l'oublie sur les boutons, Kayli Clinn le garde partout).
- Fonds. Blanc avec halo discret ; sections teintées en accent pâle ; **une seule zone sombre pleine par page** (chez LKH le pied de page, plus « Qui nous sommes » sur À propos). Halos radiaux discrets autorisés sur deux sections et sur le CTA final. Voile sombre en dégradé sur la photo du hero. Jamais de dégradé dans le texte.
- Hero de l'accueil. Section de 88vh, contenu aligné en bas, photo `saturate(.85) brightness(.85)` sous trois dégradés, grille `1.15fr .85fr` à partir de 980 px, padding 140 px en haut et 80 px en bas (120 px à partir de 980 px). Pas de filet en bas. Colonne droite = carte blanche inclinée de −1,5°, redressée au survol.
- Pages intérieures. Pas de hero photo. Un `page-head` avec fil d'Ariane, h1, chapeau, 48 px en haut et 32 px en bas.
- Seuils de rupture 520, 720 et 980 px.
- Confirmation destructive. `<dialog>` natif (`ConfirmDialog-C4RVgHk_.css`), `::backdrop` `#17141273`, carte bord 1 px `--rule`, rayon 14 px, ombre carte, padding 24 px, largeur max 34rem, titre serif 1,25rem, message `--muted`, bouton danger (fond `--danger`, texte blanc, rayon 10 px, `:focus-visible` `outline:2px solid var(--danger)`) plus « Annuler ». Texte « Retirer cette prestation ? » / « « <titre> » sera retirée de votre devis. ».

## 4. Boutons

Base commune `display:inline-flex; gap:8px; padding:13px 20px; border-radius:10px; border:1px solid transparent; font:600 .92rem/1 Inter`.
- Primaire. Fond accent, texte blanc, socle `box-shadow:0 1px 0 accent-foncé`. Survol fond accent foncé et `translateY(-1px)`.
- Secondaire. Texte et bord encre, fond transparent. Survol inversé (fond encre, texte blanc).
- Fantôme. Bord `--rule`, survol bord encre. Variante sur fond sombre en blanc sur `#ffffff0f`.
- Désactivé. `opacity:.5; cursor:not-allowed`, survol neutralisé.
- Libellés à l'infinitif, sans point. Chez Kayli Clinn, deux verbes officiels, « Estimer & réserver en ligne → » et « Demander un devis → ». Dans le tunnel, possessif possible (« Réserver mon créneau → »).

## 5. Structure des pages (site réel)

**Accueil, 12 blocs.** En-tête (thème) → hero à deux colonnes avec carte d'estimation → 2 cartes « Prestation phare » → avant/après (comparateur) → « Comment ça se passe » (4 onglets automatiques) → catalogue en mosaïque (5 tuiles retournables) → 3 niveaux avec tableau comparatif (chez Kayli Clinn, 3 façons d'obtenir un prix) → avis en défilement (badge « Exemple » tant que non réels) → « Selon votre profil » (4 onglets, fond accent pâle, seule section teintée) → FAQ (6 `<details>`) → CTA final blanc centré à halo, un seul bouton → pied de page (thème).

**À propos, 7 blocs (page-head + 5 sections + CTA).** `page-head` (« Qui sommes-nous ? ») → 3 principes (fond pâle) → 3 niveaux (3 parcours de prix) → entreprise (sombre, fiche à 6 lignes dedans) → partenaires + 5 puces de domaine → 3 cartes chantiers → CTA final. Ni hero photo, ni FAQ, ni citation, ni JSON-LD.

**Réalisations.** `page-head` → grille de projets en 2 colonnes (comparateur ou galerie, tag, titre, département, description) → encart CTA sur fond pâle. Jamais de nom de client particulier, jamais de date ni de montant.

**Prestations.** Le site réel n'a pas de page prestation éditoriale. Il a un catalogue de cartes (une phrase et un bouton par prestation, bloc « Comment composer votre devis » si le panier est vide, aside Île-de-France, aucun filtre) puis un estimateur par prestation (une question par écran, barre collante « À partir de », récapitulatif par poste), un panier, une finalisation en 7 étapes. Les douze blocs de l'ancienne charte venaient de la refonte. Option A ou B à trancher (voir `ANALYSE-LKH-REEL.md`, section 6.3).

## 6. Tournures de phrases (à imiter)

- **Un pivot lexical décliné.** « Travaux suivis, pas subis » (accueil seulement), baseline « Travaux suivis » partout, CTA « Un prix en 5 minutes. Un chantier suivi jusqu'au bout. », locution « du devis à la réception ». Chez Kayli Clinn, « Un espace propre, l'esprit tranquille. » et une baseline courte à trouver.
- **Titre en deux temps avec un fragment accentué.** « Nos chantiers, *avant et après.* », « Votre chantier reste *lisible*, du devis à la réception. » → « Votre passage reste *lisible*, de la réservation au contrôle final. »
- **Sur-titre « Verbe · Complément » sur l'accueil.** Cinq surtitres sur huit de l'accueil le suivent (« Estimer · Catalogue », « Suivre · Le chantier », « Recevoir · Selon votre profil »…) ; les pages intérieures utilisent des surtitres nominaux (« Notre façon de travailler », « Qui nous sommes », « Nos chantiers ») → « Estimer · Réserver · Vérifier » sur l'accueil.
- **Chapeau en deux phrases articulées par un tiret ou un deux-points**, impératif seulement devant un élément interactif. « Choisissez qui vous êtes : les échanges, les documents et le niveau de reporting suivent. »
- **Carte prestation = titre nominal + une phrase « énumération — périmètre » avec point final.** « Murs et plafonds — fourniture et pose, préparation des supports incluse. » → « Électroménager, vitres intérieures et placards — inclus, logement vide. »
- **Principes en phrase sans point.** « Ce qui sera invisible est photographié », « Ce qui change est écrit avant d'être fait », « Une seule personne répond ».
- **Réassurance en pilules nominales de 2 à 4 mots**, deux sur trois en « Sans ». « Sans engagement », « Devis détaillé et clair ».
- **FAQ.** Question de 4 à 8 mots à inversion, réponse en deux phrases de 23 à 38 mots, parfois ouverte par « Oui. » ou « Non. » sec.
- **Prix toujours qualifié.** « HT · fourchette indicative », « dès N € HT », « Sur devis après visite ». Chez Kayli Clinn, TTC particuliers / HT pros dit sans ambiguïté, « prix ferme » pour les forfaits.
- **Vouvoiement, « nous » d'engagement et « on » conversationnel, « je » réservé au client.** « On se parle quand vous le décidez », « Je choisis ce suivi ».
- **Ponctuation.** Tiret cadratin pour le périmètre et la relance, point médian pour les métadonnées, points de suspension pour les listes ouvertes, espace insécable avant « ? ».

## 7. Ce que LKH ne fait jamais (à bannir aussi chez Kayli Clinn)

Point d'exclamation, superlatif d'auto-promotion (« leader », « expert », « n°1 »), titre en italique, capitale à chaque mot, dégradé dans le texte, emoji, deuxième zone sombre pleine sur une page, prix sans mention HT/TTC ni qualification, chiffre ou délai non vérifiable, filtre ou recherche dans le catalogue.

Ce que LKH fait et qu'il ne faut **pas** reprendre. `lang="en"` et `noindex`, doublons de `<title>`, absence de `:focus-visible` sur les boutons, absence de `<main>` et de lien d'évitement, images sans `srcset` ni dimensions, calcul du prix côté navigateur seul, uplift caché de 1,03, surface sans plafond, noms de clients dans les données de page, délais affirmés sans validation.

## 8. Mouvement

Autorisé et borné. Révélation au défilement en 700 ms (délai 80 ms par enfant, plafond 480 ms), onglets automatiques à 5,2 s avec pause au survol du panneau, défilement d'avis en 48 s avec pause au survol et au focus, comparateur avant/après avec balayage de 2,4 s coupé au premier geste, tuiles retournables en 0,6 s, carte du hero inclinée redressée au survol. Chez Kayli Clinn, tout est coupé en `prefers-reduced-motion` (le site réel ne neutralise que Home, FinalCta, BeforeAfter et Checkout, soit 9 blocs CSS dans `app-BDw0yY9K.css`, `Home-5XXTBcEd.css` et `Checkout-CI1gLQMb.css` et 5 contrôles JavaScript dans `Home-BEyYY4bd.js`, `FinalCta-BQXcIU9S.js` et `BeforeAfter-B_o3YYsk.js` ; `Slider-LuvDsh02.css`, `RadioGroup-DzxUUb1_.css`, `Catalog-CSNR3qH8.css` et `SiteHeader-CpDtGUgc.css` n'en ont aucun). Restent bannis, zoom du fond, faisceaux, grain, points pulsants, compteurs animés.

## 9. Où c'est déjà appliqué (état au 17/09/2026)

Les fichiers ci-dessous ont été construits sur l'**ancienne** charte (refonte). Ce qui y est conforme au site réel est listé, ainsi que ce qui reste à reprendre. Le détail page par page est dans `ANALYSE-LKH-REEL.md`, section 6.

- `a-propos.html` (préfixe `.kcl`). Conforme. Fil d'Ariane, trois principes, texte partenaires, fiche légale avec placeholders, révélation au défilement (`.kcl-rv`), vouvoiement. À reprendre. Hero photo et encadré signature (à remplacer par un `page-head`), section « Pourquoi nous existons », citation `.kcl-quote`, FAQ et JSON-LD `FAQPage`, CTA sombre, cartes de liens, boutons pilule, sur-titres Inter, italiques, filets teal `.kcl-preuve`, ordre des sections.
- `commerces-retail.html` (préfixe `.kc-page`, hybride). Conforme. Filet `--line rgba(13,35,64,.10)` (bon principe de neutre froid), témoignages avec badge « Exemple ». À reprendre. `.kc-btn` pilule, `.eyebrow` Inter, `.kc-banner` et `.kc-promise` (deux zones sombres de trop), cartes « inquiétude → réponse » `.kc-why`, h2 en px, cartes à 16 px, bouton « Nous appeler » du hero.
- `accueil.html` (préfixe `.kc-home`, bloc `#kc-alignement`). Conforme. Slogan du hero, les deux boutons prévus, textes de la FAQ `kcfq`, avis `kcav2`, animations de fond déjà retirées, révélation au défilement par `revealAll` et garde `reducedMotion` (11 `IntersectionObserver`, 8 `prefers-reduced-motion`). À reprendre. FAQ `kcfq`, qui est un accordéon JavaScript à 10 questions (0 `<details`, 10 `class="kcfq-item"` avec des `div.kcfq-toggle`) avec quatre boutons de filtre `button.kcfq-filter` (Général, Tarifs & paiement, Intervention, Toutes les questions), à remplacer par 6 `<details>` natifs sans filtre (§7 bannit le filtre). Hero centré (→ grille 1.15/.85 alignée en bas avec carte forfait), sections `ka-section`, `kh4`, `kcw2`, `kczn2`, CTA sombre `kcct2` (→ CTA blanc centré), ordre des 12 blocs, `#kc-alignement` (italiques, pilules, `!important`), « Réponse sous 2 h » et « Urgence chantier » (délais non validés).
- `direction-epuree/*.html` (21 pages, préfixe `.kcl`). Conforme. Trois cartes « Trois façons d'obtenir un prix » avec carte recommandée (`.kcl-pack--reco`, équivalent des trois niveaux LKH), révélation, vouvoiement. À reprendre. Douze blocs issus de la refonte (sélecteur « votre situation », filtre, `.kcl-inq`, « règle sans exception », suivi sombre `.kcl-quote`, bande `.kcl-visite`, liens internes), pilules, sur-titres, italiques. Sort dépendant de l'option A ou B.
- `estimation.html` et `reservation.html` (tunnel, préfixes `.kc-devis` et `.kc-resa`). Conforme et en avance sur le site réel. Recalcul serveur par `KC_Pricing`, créneaux réels `GET /availability`, Stripe, liens `?resa=token`, bascules vers la visite, TTC/HT, sur-titres à tiret teal (`.step-tag` et `.result-tag` dans `estimation.html`, `.head-tag` dans `reservation.html`), grands prix serif `tabular-nums`, `:focus-visible`, `showStep` qui focalise le titre. À ajouter. Lecture de `?presta`/`?taille`/`?profil`, barre collante « À partir de » pour les forfaits, colonne « Ajuster l'estimation » (facultatif), retrait de `amount_total`/`amount_now` dans `reservation.html`. Le repli LKH « Sur RDV » se transpose en « Sur devis après visite » ; `estimation.html` n'a rien à remplacer sur ce point (le libellé n'y existe pas).
- `CHARTE-LKH.md`. Réécrite (ce fichier).
