# Charte Kayli Clinn v2 — référence pour Claude Code

Version du 19 septembre 2026. Remplace toute charte antérieure (Fraunces, Montserrat, Roboto, dégradés teal, effet verre).
Fichier compagnon : `charte-kc-v2.html` (page de démonstration de tous les composants, préfixe `kcds-`). En cas de doute, ce fichier HTML fait foi pour les valeurs.

**Direction :** reprendre la structure, les composants, la typographie et la façon d'écrire du site LKH (`lkh.lucassistant.com`, extraction `LKH_frontend_public_v3`), avec **la palette Kayli Clinn actuelle, sans aucune couleur ajoutée**. On ne recopie jamais un texte, un chiffre, un délai ou un avis LKH.

---

## 0. Règles de travail (non négociables)

1. **Une page = un seul bloc « HTML personnalisé » WordPress**, autonome (HTML + `<style>` + `<script>`), avec un **préfixe CSS unique** par page (ex. `kcac-` accueil, `kcap-` À propos, `kcpr-` catalogue, `kcvi-` vitres…). Aucun sélecteur global (`h1`, `a`, `:root`, `body`) : tout est préfixé ou scopé sous la classe racine.
2. Les jetons (variables CSS) sont déclarés **sur la classe racine du bloc**, jamais sur `:root`.
3. **Photos** déclarées en variables en tête du bloc (`--photo-hero`, `--photo-1`…), valeur par défaut `none`.
4. **Pas d'en-tête, de logo ni de pied de page** dans les blocs : le thème WordPress les affiche.
5. **Aucune donnée métier inventée** (prix, délai, horaire, effectif, assureur, SIRET, avis). Donnée manquante → `[À fournir]` visible, ou élément non affiché. Texte proposé non validé → badge ambre `Exemple` (classe `-ex`).
6. **Aucun témoignage** sans badge `Exemple` tant que les avis Google réels ne sont pas fournis (DGCCRF).
7. **Aucun secret** (Stripe, Google) dans un bloc. Le prix fait foi côté serveur (`KC_Pricing` dans `kc-booking` / `kc-devis`).
8. Retouche d'une page existante : partir du code exact fourni par la propriétaire, ne pas restructurer au-delà du gabarit ci-dessous.
9. Hébergement WordPress.com Atomic : pas de FTP. Les blocs sont collés à la main ; les extensions se déploient par ZIP.

---

## 1. Jetons

```css
.kcXX{
  /* Photos */
  --photo-hero:none;

  /* Couleurs : palette Kayli Clinn actuelle, inchangée */
  --kcXX-ink:#0D2340;          /* navy : titres, zone sombre */
  --kcXX-ink-2:#1A3A5C;        /* navy clair */
  --kcXX-text:#1F2937;         /* texte courant */
  --kcXX-text-2:#4B5563;       /* texte secondaire */
  --kcXX-muted:#6B7280;        /* chapeaux, notes */
  --kcXX-rule:#E5E7EB;         /* filets et bordures 1 px */
  --kcXX-surface:#F3F4F6;      /* fonds neutres */
  --kcXX-paper:#FFFFFF;        /* cartes */
  --kcXX-bg:#F7F8FA;           /* fond de page */
  --kcXX-accent:#0FA7A5;       /* teal : filets, tirets, anneau de sélection */
  --kcXX-accent-2:#0B8483;     /* teal foncé : fond des boutons, textes teal sur clair, gros prix */
  --kcXX-accent-3:#076E6D;     /* teal profond : survol des boutons + socle 1 px */
  --kcXX-accent-soft:#F0FAFA;  /* teal pâle : sections teintées, états sélectionnés */
  --kcXX-accent-light:#E6F5F5; /* teal clair : badges, fonds d'icônes */
  --kcXX-mint:#19E3DF;         /* menthe : accents sur fond navy uniquement */
  --kcXX-on-ink:rgba(255,255,255,.72);
  --kcXX-on-ink-rule:rgba(255,255,255,.14);

  /* Polices */
  --kcXX-serif:"Libre Baskerville",Georgia,"Times New Roman",serif;
  --kcXX-sans:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --kcXX-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;

  /* Formes */
  --kcXX-r-lg:14px;  /* cartes, panneaux */
  --kcXX-r-md:10px;  /* boutons, champs, FAQ */
  --kcXX-r-sm:6px;   /* petits contrôles */
  --kcXX-shadow:0 1px 0 var(--kcXX-rule),0 24px 48px -28px rgba(13,35,64,.18);
  --kcXX-shadow-h:0 1px 0 var(--kcXX-accent),0 24px 56px -24px rgba(15,167,165,.28);
  --kcXX-ease:cubic-bezier(.2,.7,.2,1);

  font-family:var(--kcXX-sans);font-size:16px;line-height:1.6;color:var(--kcXX-text);
}
```

**Contraste (obligatoire) :** texte blanc sur `#0FA7A5` = 3:1, **interdit** pour du texte. Texte blanc sur `#0B8483` = 4,5:1, autorisé. Donc boutons pleins et textes teal sur fond clair en `accent-2` ; `accent` réservé aux éléments non textuels. Sur fond navy, l'accent est la menthe `#19E3DF`.

**Aucune nouvelle couleur :** on n'utilise que la palette ci-dessus (celle déjà en place sur le site). Titres en navy, texte courant en `#1F2937`.

**Or :** interdit sur le site (logo et outils internes uniquement).

---

## 2. Typographie

Trois familles, rôles fixes. Aucune autre police (Fraunces, Montserrat, Roboto supprimées).

| Rôle | Police | Taille | Graisse | Autres |
|---|---|---|---|---|
| h1 accueil | Libre Baskerville | `clamp(32px, 4vw + 16px, 56px)` | 700 | interligne 1,2, `letter-spacing:-.01em` |
| h1 pages intérieures | Libre Baskerville | `clamp(32px, 4vw, 52px)` | 700 | interligne 1,15 |
| h2 de section | Libre Baskerville | `clamp(24px, 2vw + 16px, 36px)` | 700 | interligne 1,2 |
| h3 carte phare | Libre Baskerville | 23 px | 700 | |
| h3 carte prestation | Libre Baskerville | 22 px | 700 | |
| h3 principe / niveau | Libre Baskerville | 17,6–18 px | 700 | interligne 1,3 |
| Question d'étape (tunnel) | Libre Baskerville | `clamp(28px, 3vw, 40px)` | 700 | |
| Texte courant | Inter | 16 px | 400 | interligne 1,6 |
| Chapeau (`lede`) | Inter | 17,6 px | 400 | interligne 1,7, `max-width:60ch`, couleur `muted` |
| Surtitre | JetBrains Mono | 12,5 px | 700 | capitales, `letter-spacing:.08em`, couleur `accent-2`, tiret `::before` 18×1 px `accent`, gap 10 px |
| Kicker de carte | JetBrains Mono | 11,5 px | 400 | capitales, `.08em` |
| Numéro « 01 » | JetBrains Mono | 12,5 px | 700 | `.14em` |
| Petit prix (« dès 55 € TTC ») | JetBrains Mono | 14 px | 400 | |
| Gros prix (récapitulatif) | Libre Baskerville | `clamp(48px, 8vw, 80px)` | 400 | couleur `accent-2`, `tabular-nums` |
| Bouton | Inter | 14,7 px (16 px en `lg`) | 600 | interligne 1 |
| Badge | Inter | 12,5 px | 600 | |

**Emphase :** un seul fragment par titre en `<em>`, couleur `accent-2`, **droit** (`font-style:normal`), 700. Jamais d'italique, jamais de surlignage sous le mot.

**Chargement des polices :** en production, les polices sont **installées sur le site** (bibliothèque de polices WordPress ou fichiers `.woff2` en Médiathèque), **pas de lien Google Fonts** dans les blocs (RGPD). Graisses à installer : Libre Baskerville 400/700, Inter 400/600/700, JetBrains Mono 400/700.

---

## 3. Espacements et mise en page

- Échelle unique : 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96 px.
- Section : `padding:96px 0`, **64 px sous 720 px**.
- Filet `1px solid rule` entre deux sections claires consécutives ; supprimé si l'une est teintée ou sombre.
- Conteneur : `max-width:1180px; padding:0 24px`. Conteneur étroit (FAQ, texte) : 820 px.
- En-tête de section : surtitre → h2 → chapeau, `gap:20px`, `margin-bottom:48px`, aligné à gauche (centré seulement pour FAQ, preuve sociale, CTA final). Aucun filet sous les titres.
- Points de rupture : **520, 720, 980 px** uniquement.
- Fonds : fond de page `bg`, cartes `paper`, sections teintées `accent-soft`, **une seule zone sombre navy par page**.

---

## 4. Composants

Référence visuelle et code : `charte-kc-v2.html`.

- **Boutons** (3 variantes, pas une de plus) : base `inline-flex`, `gap:8px`, `padding:13px 20px`, rayon 10 px, bordure 1 px transparente. `lg` = `16px 24px`, 16 px.
  - Primaire : fond `accent-2`, texte blanc, `box-shadow:0 1px 0 accent-3` ; survol fond `accent-3` + `translateY(-1px)`.
  - Secondaire : texte et bord `ink`, fond transparent ; survol fond `ink`, texte blanc.
  - Fantôme : texte `ink`, bord `rule` ; survol bord `ink`. Variante sur fond sombre : blanc sur `rgba(255,255,255,.06)`, bord `rgba(255,255,255,.3)`.
  - Désactivé : `opacity:.5; cursor:not-allowed`.
  - Flèche « → » dans un `<span>`, glisse de 3 px au survol.
  - `:focus-visible{outline:2px solid accent-2;outline-offset:2px}` sur tous les éléments interactifs.
- **Badge** : 12,5 px 600, rayon 999 px, `padding:5px 11px`, fond `accent-light`, texte `accent-3`.
- **Badge Exemple** : ambre `#8a5a06` sur `rgba(245,158,11,.14)`, capitales 10,5 px.
- **Pilule** (hero sombre uniquement) : blanc sur `rgba(255,255,255,.1)`, bord `rgba(255,255,255,.2)`, coche SVG menthe 14 px.
- **Carte phare** : toute la carte est un lien ; image 220 px à gauche dès 520 px ; kicker, h3, une phrase, lien « … → ». Survol : remontée 4 px.
- **Carte prestation** : image 16/10 sur fond `accent-soft`, h3, une phrase, petit prix mono, **un seul bouton**.
- **Carte principe** : numéro mono, h3, texte.
- **Carte de choix** (`button aria-pressed`) : sélectionnée = bord `accent` + fond `accent-soft` + `box-shadow:0 0 0 1px accent`.
- **FAQ** : `<details>` natif, bord `rule`, rayon 10 px, `padding:18px 22px`, marqueur « + / − » mono teal. 4 à 6 questions, **aucun filtre**.
- **Fiche entreprise** (zone sombre) : `<dl>` à 6 lignes (Raison sociale, SIRET, Siège, Direction, Assurances, Contact).
- **CTA final** : fond blanc, centré, h2 max 22ch, une phrase, **un seul bouton** `lg`.
- **Icônes** : SVG trait fin (1,8), jamais d'emoji.

### Mouvements autorisés
- Transitions de survol 150–250 ms, courbe `ease`.
- Apparition au défilement : opacité + `translateY(16px)` sur 700 ms, une fois, délai max 480 ms.
- Tout est coupé en `@media (prefers-reduced-motion:reduce)`.

### Interdits (ce qui « sent l'IA »)
Dégradés teal (boutons, barres, fonds), effet verre (`backdrop-filter`), halos et lueurs, surlignage sous les mots, boutons pilule, animations automatiques (carrousel, onglets qui avancent seuls, défilement d'avis, balayage avant/après, points pulsants, zoom de fond), cartes inclinées, gros chiffres délavés, plusieurs zones sombres par page, emoji, italique dans les titres.

---

## 5. Rédaction

- Vouvoiement exclusif. « On » conversationnel autorisé avec mesure. Aucun « ! », aucun superlatif (leader, expert, n°1, premium).
- **Surtitres** au format « Verbe · Complément » : `Estimer · Catalogue`, `Réserver · Comment ça se passe`, `Vérifier · Le contrôle`. Les autres sont nominaux (`Qui nous sommes`, `FAQ`).
- **h2** : phrase, un fragment en `<em>`, point final. Ex. « Votre passage reste *lisible*, de la réservation au contrôle final. »
- **Chapeaux** : 2 phrases, 15–48 mots, articulées par un tiret cadratin ou deux-points. Impératif seulement devant un élément interactif.
- **Boutons** : infinitif, sans point. Libellés officiels : **« Estimer & réserver en ligne → »** (forfaits) et **« Demander un devis → »** (le reste). Dans le tunnel : « Réserver mon créneau → », « Envoyer ma demande → ».
- **Cartes prestation** : titre nominal sans article ; une phrase nominale « énumération — périmètre. »
- **FAQ** : question 4–8 mots ; réponse 2 phrases, 23–38 mots.
- **Prix** : TTC pour les particuliers, HT pour les professionnels, toujours précisé. Espace insécable avant €, %, h, m². Virgule décimale.
- Jamais « rendez-vous » : dire « visite ».
- Aucun délai, compteur ou note d'avis sans validation de la propriétaire.

---

## 6. Gabarits de pages

### 6.1 Accueil (préfixe `kcac-`) — 11 blocs dans cet ordre
1. **Hero** : sombre navy (la zone sombre de la page), photo `--photo-hero` voilée, 88vh, contenu aligné en bas, grille `1.15fr .85fr` dès 980 px. À gauche : surtitre « Nettoyage professionnel · Île-de-France », h1 « Un espace propre, *l'esprit tranquille*. », chapeau, boutons « Estimation en ligne » (`/devis/`) + « Voir nos prestations », 3 pilules (« Sans engagement », « Prix ferme sur les forfaits », « Devis écrit avant intervention » `Exemple`). À droite : carte blanche forfait (sélecteur de typologie Studio→T5 lisant la grille, montant serif TTC, « Continuer l'estimation → » vers `/devis/?presta=airbnb&taille=…`). **Pas d'inclinaison, pas d'animation automatique.**
2. **Prestations phares** : 2 cartes phares (Turnover Airbnb, Fin de bail).
3. **Avant / Après** : masqué tant que les photos avec accord client ne sont pas fournies.
4. **Comment ça se passe** : 4 étapes en onglets (clic seulement) : Vous estimez en ligne / Vous réservez / L'intervention / Le contrôle.
5. **Catalogue** : 4 familles de `prestations.html` en tuiles (pas 5).
6. **Trois façons d'obtenir un prix** : 3 cartes de choix (Forfait / Visite gratuite / Être rappelé).
7. **Avis** : section `kcav2` existante, badge `Exemple` sur chaque avis tant que non réels. Pas de logos clients.
8. **Selon votre profil** : fond teinté, 4 onglets (Particulier, Hôte Airbnb, Syndic & bailleur, Entreprise & commerce), textes `Exemple`.
9. **FAQ** : 6 `<details>`, reprendre les textes validés de `kcfq`, supprimer les 4 filtres.
10. **CTA final** blanc : « Un prix ferme en ligne. *Un espace propre, l'esprit tranquille.* » + « Estimer & réserver en ligne → ».

À supprimer de l'accueil actuel : `ka-section`, `kcw2` (sombre), `kczn2`, `kcct2` (CTA sombre, « Réponse sous 2 h », « Urgence chantier »), bloc `#kc-alignement` et ses `!important`.

### 6.2 À propos (préfixe `kcap-`)
Fil d'Ariane + `page-head` (h1 « Qui *sommes-nous* ? », chapeau, sans photo) → 3 principes (fond teinté) → trois façons d'obtenir un prix → entreprise (zone sombre + fiche 6 lignes, `[À fournir]` pour les données manquantes) → partenaires (texte existant, marqué à valider) → 3 cartes réalisations → CTA final. Supprimer : hero photo, « Notre signature », FAQ et JSON-LD `FAQPage` (garder `BreadcrumbList` et `LocalBusiness`).

### 6.3 Catalogue `prestations.html` (préfixe `kcpr-`)
`page-head` → bloc « Comment composer votre demande » (4 étapes) → aside « Nous intervenons en Île-de-France · 75, 77, 78, 91, 92, 93, 94, 95 » → une section par famille (ancre, h2, chapeau, grille 3/2/1) → cartes prestation (un bouton : « Estimer & réserver en ligne → » ou « Demander un devis → »). Aucun filtre, aucune recherche.

### 6.4 Page prestation (préfixe propre à chaque page) — gabarit commun
1. Fil d'Ariane + en-tête sobre : h1, chapeau 2 phrases, 2 boutons. Photo `--photo-hero` en bandeau dessous (facultative).
2. **Ce qui est inclus** : liste cochée courte.
3. **Le prix** : forfait → carte avec « dès X € TTC » + « Estimer & réserver en ligne → » ; autres → 3 cartes de choix (forfait/visite/rappel selon ce qui existe).
4. **Comment ça se passe** : 4 étapes en onglets (clic seulement).
5. **Avant / Après** : seulement avec vraies photos.
6. **FAQ** : 4 à 6 `<details>`.
7. **CTA final** blanc.

À supprimer partout : cartes « inquiétude → notre réponse » (`.kcl-inq`, `.kc-why`), « Une règle sans exception », « Votre situation ressemble à », suivi sombre à citation (`.kcl-quote`), bande sombre « visite » (`.kcl-visite`), `.kc-banner`, `.kc-promise`, filtres par famille, cartes de liens internes. Les trois cartes « Trois façons d'obtenir un prix » d'`entretien-de-bureaux.html` sont conservées (carte du milieu = état sélectionné).
Pages dératisation, désinsectisation, punaises de lit : mention « avec nos partenaires spécialisés certifiés » uniquement (voir décision 12).

### 6.5 Tunnel (`estimation.html`, `reservation.html`, `demande-de-devis.html`)
**Logique inchangée** (maquette validée par la propriétaire). Seul l'habillage passe à la charte v2 : suppression des dégradés (`btn-next`, `cta-1`, `pb-fill`, `step-tag::before`), de l'effet verre (`.card`, `.row`, `.panel`, `.dep`, `.res-main`), des lueurs (`--glow`), du surlignage `step-title em::after`. Titres d'étape en Libre Baskerville, surtitres en mono, boutons selon §4, gros prix serif `accent-2`. Retirer `amount_total` et `amount_now` de `reservation.html` (le serveur recalcule).

---

## 7. Décisions sur les questions ouvertes

| # | Question | Statut | Règle pour Claude Code |
|---|---|---|---|
| 1 | Police des titres | **Décidé** | Libre Baskerville |
| 2 | Police monospace | **Décidé** | JetBrains Mono |
| 3 | Pages prestations / panier | **Décidé** | Pages prestations conservées (gabarit §6.4) + catalogue (§6.3). Une prestation par devis, pas de panier (conforme au tunnel validé) |
| 4 | Profil client dans le tunnel | **Décidé** | Pas d'étape profil (tunnel validé). Le profil n'existe que sur l'accueil (bloc 8, contenu `Exemple`) |
| 5 | Prix avant ou après coordonnées | **Décidé** | Après (tunnel validé : coordonnées puis « Voir mon tarif »). La pilule « Sans email ni téléphone » est **interdite** |
| 6 | Qualification (accès, délai, rappel) | **Décidé** | Accès et date déjà dans le tunnel validé (forfaits). Pas de plages de rappel |
| 7 | Niveau de service | **Décidé** | Existe dans le tunnel validé (« Suivi Confort », niveaux Essentiel / Confort / Premium). Sur le site : n'en parler qu'avec les libellés et montants du tunnel, sans rien ajouter |
| 8 | Code de vérification par e-mail | **Décidé** | Non (nonce + champ piège + limite d'envois suffisent) |
| 9 | Délais affichables | **Ouvert** | Aucun délai sur les pages. Seuls ceux déjà présents dans le tunnel validé restent dans le tunnel |
| 10 | Photos, avis, logos, fiche entreprise | **Ouvert** | `[À fournir]`, avant/après masqué, avis avec badge `Exemple`, pas de logos |
| 11 | Baseline courte | **Ouvert** | Rien dans l'en-tête ; aucune baseline inventée |
| 12 | Certibiocide | **Ouvert** | Pages 3D conservées, formule « avec nos partenaires spécialisés certifiés » uniquement ; aucune mention de certification propre à Kayli Clinn |
| 13 | Garde-fous tarifaires (`CLAUDE.md`) | Hors périmètre | Ne pas toucher |

### Points à corriger dans le tunnel validé (en attente de la propriétaire — ne pas modifier sans son accord, les signaler)
1. Écrans « Date souhaitée » et « Logement vidé » : affichent « +30 % », « +25 % », « +15 % » alors que la règle interne dit « jamais de pourcentage visible ».
2. Encadré « Vos garanties » : « RC Pro AXA 5 M€ » non vérifié.
3. Pack conciergerie : promet une « Application Kayli Clinn » qui n'existe pas encore.
4. Engagements à confirmer : « devis ferme sous 24 à 48 h », « remplacement garanti sous 24 h », « garantie retouche sous 7 jours ».

**Confirmé par la propriétaire (19/09/2026) :** minimum d'intervention fin de chantier = **300 € HT** (valeur du tunnel, à conserver ; vérifier que `KC_Pricing` côté serveur applique aussi 300 € HT).

---

## 8. Ordre de production

1. Accueil → 2. À propos → 3. Catalogue → 4. Pages prestations, une par une (d'abord Turnover Airbnb et Fin de bail) → 5. Habillage du tunnel.
Chaque livrable : deux fichiers par page (`.src.html` lisible et `.html` minifié prêt à coller, voir §11). Le préfixe utilisé et la page WordPress cible sont notés dans le `.src.html` uniquement.

## 9. Vérifications avant livraison (checklist)
- [ ] Toutes les classes préfixées, aucune règle sur `:root`, `body`, `h1`… nus.
- [ ] Trois polices seulement, aucun lien Google Fonts.
- [ ] Aucun dégradé, `backdrop-filter`, halo, animation automatique.
- [ ] Texte blanc jamais sur `#0FA7A5`.
- [ ] Un seul bouton par carte, une seule zone sombre par page.
- [ ] `:focus-visible` présent, `prefers-reduced-motion` respecté, rendu vérifié à 375, 720 et 1280 px.
- [ ] Aucun prix, délai, avis ou donnée d'entreprise inventé ; badges `Exemple` et `[À fournir]` en place.
- [ ] Vouvoiement, pas de « ! », pas de superlatif, pas de « rendez-vous ».
- [ ] Chaque bouton et lien d'action porte un `data-kc-event` (§10), sans donnée personnelle, sans script de mesure.
- [ ] Aucun commentaire interne, aucune note de validation dans la version à coller ; version `.src` lisible + version minifiée livrées (§11).
- [ ] Aucun script anti-clic droit ou anti-copie.

---

## 10. Repères de mesure (clics)

Aucun outil de mesure n'est installé pour l'instant. On pose seulement des **repères invisibles**, pour brancher plus tard Google Analytics 4 ou Microsoft Clarity sans retoucher les pages.

- Chaque bouton, lien d'action et carte cliquable porte un attribut `data-kc-event`.
- Format : `page:type:cible`, en minuscules, sans accents, mots séparés par des tirets.
  - `accueil:cta:estimer-hero`, `accueil:carte:airbnb`, `accueil:onglet:etape-2`
  - `vitres:cta:estimer`, `prestations:carte:fin-de-bail`, `a-propos:cta:final`
  - Tunnel : `tunnel:etape:<id-etape>` à l'affichage de chaque étape (attribut sur le conteneur de l'étape), `tunnel:cta:reserver`, `tunnel:cta:devis-email`
  - Liens téléphone et e-mail : `<page>:contact:tel`, `<page>:contact:mail`
- **Jamais de donnée personnelle** dans un repère (ni nom, ni e-mail, ni téléphone, ni adresse, ni montant saisi).
- Aucun script de mesure, aucun cookie, aucun appel à un service tiers dans les blocs. Le branchement se fera plus tard, en une fois, **après** la mise en place d'un bandeau de consentement conforme CNIL.

---

## 11. Protection du code

Rappel honnête : tout ce qu'un navigateur affiche (HTML, CSS, JavaScript) peut être lu par un visiteur. Aucune technique ne l'empêche. On protège donc ce qui compte, sans dégrader le site.

**Interdits**
- Scripts qui bloquent le clic droit, la sélection de texte, le copier-coller ou les outils de développement : inutiles (contournables en une seconde), ils gênent l'accessibilité, empêchent de copier le numéro de téléphone et nuisent au référencement.

**Obligatoires**
1. **Rien de sensible dans les blocs** : aucun secret, aucune clé, aucune adresse d'API privée. Le calcul de prix qui fait foi reste côté serveur (`KC_Pricing`).
2. **Rien d'interne dans les livrables** : supprimer tous les commentaires internes, notes de validation (« Note de validation », « Dans le tunnel réel… »), mode « Relecture », noms de fichiers ou de personnes, mentions « à valider » destinées à l'équipe (le badge `Exemple` visible reste, lui, autorisé tant que la donnée n'est pas validée).
3. **Grille tarifaire et majorations** : dans le tunnel, les pourcentages de majoration et les grilles ne doivent plus être écrits en clair dans le bloc ; ils sont lus depuis l'API `GET /wp-json/kc-booking/v1/types` (Phase 3.1) au chargement. Le visiteur ne voit que les montants en euros qui le concernent.
4. **Deux versions de chaque page** :
   - `nom-page.src.html` : version lisible, commentée, conservée dans le dépôt (jamais collée dans WordPress) ;
   - `nom-page.html` : version **minifiée** (espaces et commentaires retirés, noms de variables JavaScript raccourcis), à coller dans WordPress.
   Toute modification se fait sur la version `.src`, puis on régénère la version minifiée.

**À faire par la propriétaire (hors code)**
- Mention légale en pied de page : « © 2026 Kayli Clinn. Tous droits réservés. Toute reproduction, même partielle, est interdite. »
- Preuve de date de création : dépôt e-Soleau (INPI) des fichiers `.src` et de la charte, pour pouvoir prouver l'antériorité en cas de copie.
- Photos avant/après : filigrane discret « kayliclinn.fr » avant mise en ligne.
