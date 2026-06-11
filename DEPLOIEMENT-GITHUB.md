# Connecter GitHub à WordPress.com — guide pas-à-pas

Objectif : ne plus jamais recopier de code à la main. Le dépôt GitHub devient
la source officielle de tes extensions ; WordPress.com vient y chercher le
code à chaque déploiement.

Le dépôt est **déjà prêt** : les extensions sont dans `plugins/` (la structure
qu'attend WordPress.com), et le fichier `.deployignore` empêche tout le reste
(documentation, pages, archives) d'être copié sur le site.

## Avant de commencer

- Ton plan **Creator** (l'ancien nom du plan « Business ») inclut cette
  fonction. Rien à acheter.
- Il te faut ton compte GitHub (celui du dépôt `Kayliclinn/kayliclinn-api`).

## Étape 1 — Ouvrir l'écran des déploiements

1. Va sur **wordpress.com** et connecte-toi.
2. Ouvre le tableau de bord d'hébergement : **Sites** → clique sur
   **kayliclinn.fr** → onglet **« Déploiements »** (Deployments).
3. Clique sur **« Connecter un dépôt »** (Connect repository).

## Étape 2 — Autoriser GitHub

1. WordPress.com te demande d'installer sa petite application sur GitHub :
   suis l'écran, connecte-toi à GitHub si besoin.
2. Quand GitHub demande quels dépôts autoriser, choisis
   **« Only select repositories »** → `Kayliclinn/kayliclinn-api`.
   (Inutile de donner accès à tout ton compte.)

## Étape 3 — Configurer le déploiement

Sur l'écran de configuration WordPress.com :

| Réglage | Valeur à mettre |
| --- | --- |
| Dépôt | `Kayliclinn/kayliclinn-api` |
| Branche | `main` |
| Répertoire de destination | `/wp-content` |
| Mode de déploiement | **Manuel** (recommandé pour un site en production) |

Pourquoi ces valeurs : le dépôt contient `plugins/kc-booking`, `plugins/kc-devis`,
etc. En le déployant vers `/wp-content`, chaque extension arrive exactement au
bon endroit (`wp-content/plugins/kc-booking`, …). Le `.deployignore` à la racine
exclut tout le reste (`site/`, `api/`, `lib/`, guides…).

Le mode **manuel** veut dire : rien ne part sur le site tant que tu n'as pas
cliqué toi-même sur **« Déployer »**. C'est le garde-fou recommandé — le mode
automatique enverrait chaque modification de `main` directement en production.

## Étape 4 — Premier déploiement

1. D'abord, fusionne la pull request dans `main` (bouton **Merge** sur GitHub).
2. Retourne dans WordPress.com → Déploiements → clique **« Déployer »**.
3. Vérifie le journal de déploiement : il doit lister uniquement des fichiers
   `plugins/…`.

## Étape 5 — Après le premier déploiement (une seule fois)

1. Admin WordPress → **Extensions** : active **« Kayli Clinn — Type Visite
   d'audit »** (la nouvelle). Les 4 autres sont déjà actives — leurs fichiers
   ont simplement été remplacés par les versions corrigées.
2. kc-booking → **⚙️ Configuration** : vérifie que tes clés Stripe (mode
   **test**), le secret du webhook et l'URL de la page réservation sont en place
   (ces réglages vivent en base de données : le déploiement n'y touche pas).
3. Déroule le **test de bout en bout** (voir `wordpress/COPIER-DANS-WORDPRESS.md`,
   dernière section).

## Au quotidien, ensuite

1. Une modification est poussée sur `main` (par toi, ou par Claude via une
   pull request que tu fusionnes après relecture).
2. WordPress.com → Déploiements → **« Déployer »**.
3. C'est en ligne. Chaque déploiement est historisé et le code reste versionné
   dans Git : tu peux toujours revenir en arrière.

## Les deux règles de sécurité à ne jamais oublier

- **Jamais de secret dans le dépôt** (clé Stripe `sk_…`, compte de service
  Google). Tes extensions les stockent dans les réglages WordPress — c'est
  bien, on ne change rien.
- **Mode manuel + relecture des pull requests** avant de fusionner : rien ne
  part en production sans ton accord explicite.
