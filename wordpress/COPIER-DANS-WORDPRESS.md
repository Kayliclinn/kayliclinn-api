# À copier dans WordPress — guide simple

Les **fichiers complets et corrigés** de tes extensions sont dans
`wordpress/plugins/`. Plus besoin de chercher un bloc à remplacer : tu copies
le fichier **en entier**. Tout a été vérifié (syntaxe PHP + calcul des prix).

## Méthode (sans FTP, comme d'habitude)

Pour chaque extension : refabrique le ZIP du dossier puis
Admin WordPress → Extensions → Téléverser une extension → installer/remplacer.
(Ou, si tu utilises l'éditeur de fichiers d'extension de WordPress, colle le
contenu du fichier `.php` à la place de l'ancien.)

## Ce qu'il faut copier

| Extension | Fichier(s) | Ce qui change |
| --- | --- | --- |
| **kc-booking** (réservations) | `kc-booking.php` **+ le nouveau `kc-pricing.php`** (même dossier) | 🔴 Corrige la faille de prix : les montants sont recalculés côté serveur. C'est la correction la plus importante. |
| **kc-devis** (devis) | `kc-devis.php` **+ `kc-pricing.php`** (même dossier) | Comprend le nouveau formulaire (3 parcours), prix officiels, e-mails échappés. Ton suivi des devis (admin) est conservé. |
| **kc-sheet-sync** (Google Sheet) | `kc-sheet-sync.php` | Vérifie la signature Stripe avant d'écrire dans le Sheet. |
| **kc-visite-audit** (nouveau) | `kc-visite-audit.php` | Crée le créneau « Visite d'audit gratuite » commun aux prestations sur mesure. |
| **kc-contact** (contact) | *(rien à faire)* | Déjà sûr — aucune correction nécessaire. La copie est là pour ta sauvegarde. |

> **Important — kc-booking et kc-devis** : chacun a besoin du fichier
> `kc-pricing.php` **dans son propre dossier** (il y est déjà). C'est la grille
> tarifaire officielle : pour changer un prix plus tard, modifie ces deux
> copies (et la page d'estimation pour l'affichage).

## Réglages à vérifier après installation

1. **kc-booking → ⚙️ Configuration** : clés Stripe (en **mode test** d'abord),
   secret du webhook, URL de la page réservation `https://kayliclinn.fr/reservation/`.
2. **Stripe → Webhooks** : endpoint `…/wp-json/kc-booking/v1/stripe/webhook`,
   événement `checkout.session.completed`.
3. **kc-visite-audit** : une fois activé, va dans kc-booking → Prestations,
   ouvre « Visite d'audit gratuite » et affecte le personnel habilité.

## Le test à faire (mode test Stripe, carte 4242 4242 4242 4242)

1. `/devis/` → Airbnb 2 pièces + linge → 85 € → « Réserver mon créneau ».
2. `/reservation/` → créneau → acompte 25,50 € → paiement test.
3. Vérifie : e-mail client + admin, ligne dans le Google Sheet (réf KC-XXXX),
   événement Google Agenda, lien « Gérer ma réservation ».
4. Parcours gratuit : `/devis/` → « Après sinistre » → visite d'audit → créneau.
5. **Test anti-fraude** : rejoue la réservation en forçant un petit montant —
   le prix encaissé doit rester celui de la grille (preuve que le serveur
   recalcule bien).

Le détail technique de chaque correction est dans `INTEGRATION-kc-booking.md`
et l'analyse de sécurité dans `ANALYSE-plugins.md`.
