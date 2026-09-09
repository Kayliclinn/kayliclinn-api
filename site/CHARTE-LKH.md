# Charte de référence LKH — à appliquer à kayliclinn.fr

Extraite des pages LKH Construction fournies (À propos, Gros œuvre, Énergie & réseaux,
note de refonte « Rénovation clé en main »). Seules les couleurs changent : navy `#0D2340`
à la place de l'anthracite, teal `#0FA7A5` à la place de l'or. Tout le reste se copie tel quel.

## 1. Typographie

| Élément | Police | Taille | Graisse | Interligne | Autres |
| --- | --- | --- | --- | --- | --- |
| Corps de page | Inter | 16,5 px (16 px sous 620 px) | 400 | 1,68 | couleur texte `#4a5568` |
| Titre de page (h1) | Fraunces | `clamp(30px, 3.8vw, 48px)` | 600 | 1,16 | lettrage −0,01 em |
| Titre de section (h2) | Fraunces | `clamp(24px, 2.6vw, 34px)` | 600 | 1,16 | lettrage −0,01 em |
| Titre de carte (h3) | Fraunces | 18,5 px | 600 | 1,35 | |
| Sur-titre (eyebrow) | Inter | 11,5 px | 600 | | capitales, espacement 0,18 em, teal foncé `#076E6D` |
| Chapeau (lead) | Inter | 17 px | 400 | 1,68 | largeur max 66 caractères |
| Citation | Fraunces | `clamp(21px, 2.4vw, 30px)` | 500 | 1,34 | filet teal 3 px à gauche, largeur max 24 à 34 caractères |
| Texte de carte | Inter | 15 px | 400 | 1,5 à 1,65 | |
| Étiquettes de carte (tags) | Inter | 14 px | 400 | | teal foncé, séparateur « · » |
| Ligne de réassurance | Inter | 14 px | 500 | | séparateur « · » ou « • », mots forts en blanc sur fond sombre |
| Encadré signature (hero) | Inter | 15 px | 400 | | fond blanc à 7 %, filet teal 3 px à gauche, coins 14 px à droite |
| Fil d'Ariane | Inter | 13,3 px | 400 | | séparateur « › » |
| Bouton | Inter | 15 px | 600 | | pilule (rayon 999 px), padding 14 px 26 px, flèche « → » |
| Notes secondaires | Inter | 13,5 à 14,5 px | 400 | 1,5 | gris `#5b6b7d` |

Les tailles sont en pixels, jamais en `rem`, pour ne pas dépendre de la taille de base du thème WordPress.
Titres en casse normale (pas de « Chaque Mot En Capitale »), une seule phrase, point final.

## 2. Espacements et gabarit

- Largeur de contenu 1 160 px, marges latérales 24 px (18 px sur mobile).
- Sections de 60 px de haut et de bas (46 px pour les sections « serrées », 46 px sur mobile).
- En-tête de section (`head`) sur 70 caractères max, marge basse 34 px ; filet teal de 52 × 2 px sous le titre quand l'en-tête est centré.
- Grilles à 20 px d'écart, cartes avec coins 20 px (14 px pour les petits éléments), bordure 1 px `#E5E7EB`, padding 26 px.
- Survol des cartes : bordure teal claire, remontée de 2 px, ombre douce `0 16px 38px rgba(13,35,64,.07)`. Jamais de halo, de reflet ni de dégradé.
- Fonds alternés : blanc, `#FBFCFD` (papier), `#F7F8FA` (ivoire), navy (une seule zone sombre par page, plus l'appel final).
- Hero : photo de fond sous un voile navy dégradé de 94 % à gauche vers 42 % à droite, texte aligné à gauche sur 660 px, filet teal 3 px en bas de hero, padding 78 px en haut et 70 px en bas.

## 3. Structure d'une page prestation (douze blocs, dans cet ordre)

1. Fil d'Ariane : Accueil › Prestations › Nom.
2. Hero : sur-titre « Nom · Famille · Île-de-France », h1, chapeau, un seul bouton, ligne de réassurance à trois éléments, encadré signature.
3. La prestation : titre, filet, deux paragraphes dont une phrase forte en gras, pastilles « pour qui », encadré latéral avec illustration au trait et sélecteur « Votre situation ressemble à… ».
4. Ce que couvre la prestation : filtre par famille, huit à neuf cartes illustrées au trait, encadré « Une règle sans exception ».
5. Ce qui vous inquiète, et ce que nous en faisons : trois cartes inquiétude → réponse.
6. La méthode : six étapes numérotées « 01 · Échange », toutes visibles.
7. Le suivi : section sombre, citation, chapeau, bouton, carte « Ce que vous obtenez ».
8. Trois cartes de choix (chez LKH les trois niveaux de suivi, chez Kayli Clinn les trois façons d'obtenir un prix), carte du milieu recommandée, note tarifaire, encadré « Quel parcours choisir ? ».
9. FAQ en accordéon, six à huit questions, JSON-LD identique.
10. Bande sombre « visite » avant le pied de page.
11. Liens internes en cartes.
12. Données structurées.

Structure de la page À propos : hero → constat (« Pourquoi nous existons ») → trois principes →
signature (section sombre) → réalisations → intervenants → fiche légale → FAQ → appel à l'action → liens.

## 4. Tournures de phrases (à imiter)

- **Titre de hero en une idée, souvent en deux temps.** « Une base solide, une exécution maîtrisée et un chantier documenté. » → « Un espace propre, l'esprit tranquille. »
- **Chapeau factuel qui énumère le périmètre puis dit la méthode.** « Maçonnerie, démolition, dalles, ouvertures et travaux structurels : nous préparons et réalisons… » → chez Kayli Clinn, sans deux-points : « Surface de vente, vitrines, sanitaires, réserves. Nous nettoyons votre commerce avant ou après ouverture. »
- **Encadré signature en une phrase courte, formule mémorisable.** « Le gros œuvre ne laisse pas de place à l'improvisation. » / « Des travaux suivis, pas subis. »
- **Titre de section qui pose un principe, pas une promesse.** « Avant de transformer un espace, il faut comprendre ce qui le soutient. » / « La preuve se constitue pendant, jamais après. » / « Le problème d'un chantier n'est presque jamais technique. »
- **Ce qui sera invisible est nommé.** LKH photographie ce qui disparaît sous les finitions ; Kayli Clinn contrôle ce qu'on ne remarque que quand il manque (sanitaires, cuisine, points de contact).
- **Une règle que nous ne contournons pas.** Un encadré par page qui dit ce que l'entreprise refuse de faire (chiffrer sans visite, intervenir en présence de la clientèle…).
- **Inquiétude → « Notre réponse. »** Trois cartes : le titre est l'inquiétude du client, une phrase la précise, la réponse commence par « Notre réponse. » en gras.
- **Pas de superlatifs, pas de chiffres non vérifiables.** Aucun « meilleur », « leader », « 100 % » ; aucune durée moyenne ni délai non validé.
- **Réassurance en trois mots-clés**, jamais en slogans : « Étude préalable quand elle s'impose • Interlocuteur identifié • Suivi documenté ».
- **Boutons à l'infinitif ou au nom d'action**, une flèche : « Étudier mon projet → », « Planifier une visite technique → », « Estimer cette prestation → ».
- **FAQ qui commence par oui ou non**, puis nuance : « Oui, dans un cadre précis. » / « Non, mais elle l'est chaque fois que… ».
- **Fiche légale en clair** : raison sociale, SIRET, siège, activité, zone, direction, assurances, contact.

## 5. Ce que LKH ne fait jamais (à bannir aussi chez Kayli Clinn)

Dégradés de couleur dans les titres, boutons à halo, animations qui pulsent ou zooment, compteurs animés,
carrousels automatiques hors défilement de cartes, badges flottants, gros chiffres décoratifs, emojis,
titres en capitales à chaque mot, phrases à deux-points sans liste derrière, texte centré sur toute la page.

## 6. Où c'est déjà appliqué

- `a-propos.html` : gabarit LKH complet (préfixe `.kcl`).
- `commerces-retail.html` : version hybride (structure du design system `.kc-page`, typographie et textes LKH).
- `accueil.html` : structure d'origine conservée, typographie, boutons et effets alignés, slogan dans le hero.
- `direction-epuree/*.html` : les 21 pages prestations au gabarit LKH complet, prêtes à remplacer les versions actuelles si la propriétaire retient cette direction plutôt que l'hybride.
