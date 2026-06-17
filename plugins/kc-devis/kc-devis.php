<?php
/**
 * Plugin Name: Kayli Clinn — Formulaire de Devis
 * Description: Handler sécurisé pour le wizard de devis multi-étapes (validation, sauvegarde BDD, email admin riche, accusé de réception)
 * Version: 1.0.0
 * Author: Kayli Clinn
 * Text Domain: kc-devis
 *
 * INSTALLATION :
 * 1. Créer un dossier : wp-content/plugins/kc-devis/
 * 2. Y placer ce fichier sous le nom : kc-devis.php
 * 3. Admin WP > Extensions > Activer "Kayli Clinn — Formulaire de Devis"
 * 4. La table SQL se crée automatiquement
 * 5. Le menu "Devis" apparaît dans l'admin WordPress
 */

if (!defined('ABSPATH')) exit;

/* ════════════════════════════════════════════════════════
   ⚙️ CONFIGURATION
   ════════════════════════════════════════════════════════ */
define('KC_DEVIS_ADMIN_EMAIL', 'contact@kayliclinn.fr');
define('KC_DEVIS_FROM_NAME',   'Kayli Clinn');
define('KC_DEVIS_FROM_EMAIL',  'contact@kayliclinn.fr');
define('KC_DEVIS_RATE_LIMIT',  3);
define('KC_DEVIS_DB_VERSION',  '1.0');

/* ════════════════════════════════════════════════════════════════════
   GRILLE TARIFAIRE OFFICIELLE v2 (KC_Pricing) — intégrée au plugin
   ────────────────────────────────────────────────────────────────────
   Source : « Kayli Clinn tarification 2 » (11/06/2026). Prix TTC (B2C).
   Source de vérité des prix, recalculés côté serveur (sécurité) :
   total = (base + options) × (1 + majorations) + frais fixes.
   La même classe est intégrée dans kc-devis.php : toute modification
   de tarif se reporte dans les deux fichiers (+ la page d'estimation).
   ════════════════════════════════════════════════════════════════════ */
if ( ! class_exists( 'KC_Pricing' ) ) {
class KC_Pricing {

	/** Forfaits B2C TTC — prix fixe en ligne. Typologies absentes = devis. */
	const FORFAITS = array(
		'airbnb' => array(
			'label' => 'Turnover Airbnb',
			'prix'  => array( 'studio' => 55, 't2' => 75, 't3' => 95, 't4' => 120 ),
		),
		'fin-de-bail' => array(
			'label' => 'Ménage fin de bail / déménagement (logement vide)',
			'prix'  => array( 'studio' => 160, 't2' => 220, 't3' => 290, 't4' => 360, 't5' => 430 ),
		),
		'grand-menage' => array(
			'label' => 'Grand ménage (meublé occupé)',
			'prix'  => array( 'studio' => 140, 't2' => 190, 't3' => 250, 't4' => 320 ),
		),
		'vitrerie' => array(
			'label' => 'Vitrerie résidentielle simple (plain-pied / intérieur)',
			'prix'  => array( 'studio' => 70, 't2' => 70, 't3' => 95, 't4' => 95, 't5' => 130 ),
		),
	);

	const TAILLES = array(
		'studio' => 'Studio / T1',
		't2'     => 'T2',
		't3'     => 'T3',
		't4'     => 'T4',
		't5'     => 'T5',
	);

	/**
	 * Options TTC. type 'fixe' = montant unique (qty forcée à 1) ;
	 * type 'unite' = prix × quantité (lit, heure, m²) avec minimum éventuel.
	 * 'compat' = forfaits autorisés. 'max' = garde-fou technique anti-abus.
	 */
	const OPTIONS = array(
		'four'         => array( 'label' => 'Four en profondeur',                'prix' => 35, 'type' => 'fixe',  'compat' => array( 'airbnb', 'grand-menage' ) ),
		'frigo'        => array( 'label' => 'Réfrigérateur / congélateur',       'prix' => 25, 'type' => 'fixe',  'compat' => array( 'airbnb', 'grand-menage' ) ),
		'vitres-int'   => array( 'label' => 'Vitres intérieures',                'prix' => 20, 'type' => 'fixe',  'compat' => array( 'airbnb', 'grand-menage' ) ),
		'placards'     => array( 'label' => 'Intérieur des placards',            'prix' => 25, 'type' => 'fixe',  'compat' => array( 'grand-menage' ) ),
		'consommables' => array( 'label' => 'Réassort consommables',             'prix' => 10, 'type' => 'fixe',  'compat' => array( 'airbnb' ) ),
		'cave-box'     => array( 'label' => 'Cave / box',                        'prix' => 25, 'type' => 'fixe',  'compat' => array( 'fin-de-bail' ) ),
		'kit-linge'    => array( 'label' => 'Kit linge complet (par lit)',       'prix' => 18, 'type' => 'unite', 'unite' => 'lit',  'max' => 10,  'compat' => array( 'airbnb' ) ),
		'repassage'    => array( 'label' => 'Repassage (par heure)',             'prix' => 35, 'type' => 'unite', 'unite' => 'h',    'max' => 8,   'compat' => array( 'airbnb', 'grand-menage' ) ),
		'balcon'       => array( 'label' => 'Balcon / terrasse (par m²)',        'prix' => 2,  'type' => 'unite', 'unite' => 'm²',   'max' => 150, 'min_total' => 20,  'compat' => array( 'fin-de-bail', 'grand-menage' ) ),
		'moquette'     => array( 'label' => 'Moquette injection-extraction (m²)', 'prix' => 5, 'type' => 'unite', 'unite' => 'm²',   'max' => 300, 'min_total' => 100, 'compat' => array( 'fin-de-bail' ) ),
		'canape-2p'    => array( 'label' => 'Canapé 2 places',                   'prix' => 80,  'type' => 'fixe', 'compat' => array( 'airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie' ) ),
		'canape-3p'    => array( 'label' => 'Canapé 3 places',                   'prix' => 100, 'type' => 'fixe', 'compat' => array( 'airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie' ) ),
		'canape-4p'    => array( 'label' => 'Canapé 4 places',                   'prix' => 110, 'type' => 'fixe', 'compat' => array( 'airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie' ) ),
		'fauteuil'     => array( 'label' => 'Fauteuil',                          'prix' => 50,  'type' => 'fixe', 'compat' => array( 'airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie' ) ),
		'matelas'      => array( 'label' => 'Matelas 2 places',                  'prix' => 75,  'type' => 'fixe', 'compat' => array( 'airbnb', 'fin-de-bail', 'grand-menage', 'vitrerie' ) ),
	);

	/** Majorations en % de (base + options). 'compat' vide = tous forfaits. */
	const MAJORATIONS = array(
		'urgence'        => array( 'label' => 'Urgence < 48 h',                              'taux' => 0.30, 'compat' => array() ),
		'dimanche-ferie' => array( 'label' => 'Dimanche ou jour férié',                      'taux' => 0.25, 'compat' => array() ),
		'non-vide'       => array( 'label' => 'Logement non vidé',                           'taux' => 0.15, 'compat' => array( 'fin-de-bail' ) ),
		'tres-encrasse'  => array( 'label' => 'Très encrassé / > 6 mois sans entretien',      'taux' => 0.30, 'compat' => array( 'grand-menage' ) ),
	);

	/** Frais fixes ajoutés après majorations. */
	const FRAIS = array(
		'etage' => array( 'label' => 'Étage 3 et + sans ascenseur', 'prix' => 15 ),
	);

	const ACOMPTE_PCT = 30;

	/**
	 * Calcule un forfait B2C. Montants en CENTIMES (entiers).
	 *
	 * @param array $booking ['forfait' => slug, 'taille' => slug,
	 *                        'options' => [slug => quantité],
	 *                        'majorations' => [slugs], 'frais' => [slugs]]
	 * @throws InvalidArgumentException si la combinaison sort de la grille.
	 */
	public static function compute_forfait( array $booking ) {
		$forfait = isset( $booking['forfait'] ) ? (string) $booking['forfait'] : '';
		$taille  = isset( $booking['taille'] ) ? (string) $booking['taille'] : '';

		if ( ! isset( self::FORFAITS[ $forfait ] ) ) {
			throw new InvalidArgumentException( 'Forfait inconnu : ' . $forfait );
		}
		$grille = self::FORFAITS[ $forfait ];
		if ( ! isset( $grille['prix'][ $taille ] ) ) {
			throw new InvalidArgumentException( 'Typologie hors grille (devis requis) : ' . $taille );
		}

		$base_cents = (int) round( $grille['prix'][ $taille ] * 100 );
		$lignes     = array(
			array( 'label' => $grille['label'] . ' — ' . self::TAILLES[ $taille ], 'montant' => $base_cents / 100 ),
		);

		// ── Options (quantités validées, compatibilité par forfait) ──
		$options       = isset( $booking['options'] ) && is_array( $booking['options'] ) ? $booking['options'] : array();
		$options_cents = 0;
		foreach ( $options as $key => $qty ) {
			$key = (string) $key;
			if ( ! isset( self::OPTIONS[ $key ] ) ) {
				throw new InvalidArgumentException( 'Option inconnue : ' . $key );
			}
			$opt = self::OPTIONS[ $key ];
			if ( ! in_array( $forfait, $opt['compat'], true ) ) {
				throw new InvalidArgumentException( 'Option incompatible avec ce forfait : ' . $key );
			}
			$qty = (int) $qty;
			if ( 'fixe' === $opt['type'] ) {
				$qty = 1;
			}
			$max = isset( $opt['max'] ) ? (int) $opt['max'] : 1;
			if ( $qty < 1 || $qty > $max ) {
				throw new InvalidArgumentException( 'Quantité invalide pour ' . $key );
			}
			$montant_cents = (int) round( $opt['prix'] * $qty * 100 );
			if ( isset( $opt['min_total'] ) ) {
				$montant_cents = max( $montant_cents, (int) round( $opt['min_total'] * 100 ) );
			}
			$options_cents += $montant_cents;
			$label          = $opt['label'] . ( 'unite' === $opt['type'] ? ' × ' . $qty . ' ' . $opt['unite'] : '' );
			$lignes[]       = array( 'label' => $label, 'montant' => $montant_cents / 100 );
		}

		$sous_total_cents = $base_cents + $options_cents;

		// ── Majorations cumulables, en % de (base + options) ──
		$majorations = isset( $booking['majorations'] ) && is_array( $booking['majorations'] ) ? $booking['majorations'] : array();
		$taux_total  = 0.0;
		foreach ( array_unique( array_map( 'strval', $majorations ) ) as $key ) {
			if ( ! isset( self::MAJORATIONS[ $key ] ) ) {
				throw new InvalidArgumentException( 'Majoration inconnue : ' . $key );
			}
			$maj = self::MAJORATIONS[ $key ];
			if ( ! empty( $maj['compat'] ) && ! in_array( $forfait, $maj['compat'], true ) ) {
				throw new InvalidArgumentException( 'Majoration incompatible : ' . $key );
			}
			$taux_total += $maj['taux'];
			$lignes[]    = array(
				'label'   => $maj['label'] . ' (+' . round( $maj['taux'] * 100 ) . ' %)',
				'montant' => round( $sous_total_cents * $maj['taux'] ) / 100,
			);
		}

		$total_cents = (int) round( $sous_total_cents * ( 1 + $taux_total ) );

		// ── Frais fixes (après majorations) ──
		$frais = isset( $booking['frais'] ) && is_array( $booking['frais'] ) ? $booking['frais'] : array();
		foreach ( array_unique( array_map( 'strval', $frais ) ) as $key ) {
			if ( ! isset( self::FRAIS[ $key ] ) ) {
				throw new InvalidArgumentException( 'Frais inconnu : ' . $key );
			}
			$f            = self::FRAIS[ $key ];
			$total_cents += (int) round( $f['prix'] * 100 );
			$lignes[]     = array( 'label' => $f['label'], 'montant' => $f['prix'] );
		}

		$acompte_cents = (int) round( $total_cents * self::ACOMPTE_PCT / 100 );

		return array(
			'label'         => $grille['label'],
			'taille'        => self::TAILLES[ $taille ],
			'total_cents'   => $total_cents,
			'acompte_cents' => $acompte_cents,
			'solde_cents'   => $total_cents - $acompte_cents,
			'lignes'        => $lignes,
		);
	}

	/** Montant à encaisser maintenant, en centimes ('deposit' ou 'full'). */
	public static function amount_now_cents( array $booking, $mode ) {
		$devis = self::compute_forfait( $booking );
		return ( 'full' === $mode ) ? $devis['total_cents'] : $devis['acompte_cents'];
	}
}
}


/* ════════════════════════════════════════════════════════
   🗃️ CRÉATION DE LA TABLE
   ════════════════════════════════════════════════════════ */
register_activation_hook(__FILE__, 'kc_devis_install');
function kc_devis_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kc_devis_requests';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        presta_key VARCHAR(50) NOT NULL,
        presta_name VARCHAR(100) NOT NULL,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address VARCHAR(255) NOT NULL,
        postal_code VARCHAR(5) DEFAULT NULL,
        bien VARCHAR(100) DEFAULT NULL,
        surface INT(11) DEFAULT NULL,
        frequence VARCHAR(20) DEFAULT NULL,
        niveau VARCHAR(30) DEFAULT NULL,
        delai VARCHAR(20) DEFAULT NULL,
        options_json TEXT DEFAULT NULL,
        price_min INT(11) DEFAULT NULL,
        price_max INT(11) DEFAULT NULL,
        price_unit VARCHAR(30) DEFAULT NULL,
        message TEXT DEFAULT NULL,
        full_payload LONGTEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        status ENUM('new','contacted','quoted','won','lost','archived') NOT NULL DEFAULT 'new',
        PRIMARY KEY (id),
        KEY status (status),
        KEY created_at (created_at),
        KEY presta_key (presta_key)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    update_option('kc_devis_db_version', KC_DEVIS_DB_VERSION);
}

/* ════════════════════════════════════════════════════════
   📚 NOMS DES PRESTATIONS (pour les emails)
   ════════════════════════════════════════════════════════ */
function kc_devis_presta_names() {
    return [
        // Forfaits logement (réservables en ligne)
        'airbnb'           => 'Nettoyage Airbnb / location courte durée',
        'demenagement'     => 'Nettoyage après déménagement / fin de bail',
        'standard'         => 'Nettoyage appartement standard',
        'logement-vide'    => 'Nettoyage logement vide',
        // Professionnels (estimation immédiate)
        'bureaux'          => 'Entretien de bureaux',
        'locaux'           => 'Nettoyage régulier de locaux',
        'commerce'         => 'Nettoyage commerce',
        'copropriete'      => 'Copropriété / parties communes',
        'vitres-ponctuel'  => 'Vitres intérieures (ponctuel)',
        // Sur mesure (visite d'audit gratuite)
        'maison'           => 'Maison & grands logements',
        'grand-menage'     => 'Grand ménage / logement très sale',
        'fin-chantier'     => 'Nettoyage fin de chantier / après travaux',
        'sinistre'         => 'Nettoyage après sinistre',
        'vitres-hauteur'   => 'Vitres en hauteur / extérieures',
        'textile'          => 'Canapés, moquettes, tapis',
        'syndic'           => 'Containers, local poubelles, parking',
        'evenement'        => 'Nettoyage après événement',
        // Anciens slugs (compatibilité)
        'parties-communes' => 'Parties communes immeubles',
        'commerces'        => 'Commerces & Retail',
        'sensibles'        => 'Établissements sensibles',
        'remise-etat'      => 'Remise en état logement/bureaux',
        'decapage'         => 'Décapage / Cristallisation sols',
        'vitres'           => 'Nettoyage vitres',
    ];
}

/* ════════════════════════════════════════════════════════
   🔌 ENDPOINTS AJAX
   ════════════════════════════════════════════════════════ */
add_action('wp_ajax_nopriv_kc_devis', 'kc_devis_handle_submission');
add_action('wp_ajax_kc_devis',         'kc_devis_handle_submission');

/* ════════════════════════════════════════════════════════
   🎯 HANDLER PRINCIPAL
   ════════════════════════════════════════════════════════ */
function kc_devis_handle_submission() {

    // 1. Nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'kc_devis_action')) {
        wp_send_json_error(['message' => 'Session expirée, veuillez recharger la page.'], 403);
    }

    // 2. Honeypot
    if (!empty($_POST['website'])) {
        error_log('[KC Devis] Bot détecté via honeypot — IP : ' . kc_devis_get_ip());
        wp_send_json_success(['message' => 'Demande envoyée avec succès.']);
    }

    // 3. Rate limiting
    if (!kc_devis_check_rate_limit()) {
        wp_send_json_error(['message' => 'Trop de demandes envoyées. Merci de patienter.'], 429);
    }

    // 4. Récupération du payload JSON
    $payload_raw = $_POST['payload'] ?? '';
    if (empty($payload_raw)) {
        wp_send_json_error(['message' => 'Données manquantes.'], 400);
    }

    $payload = json_decode(wp_unslash($payload_raw), true);
    if (!is_array($payload)) {
        wp_send_json_error(['message' => 'Format de données invalide.'], 400);
    }

    // 5. Extraction + normalisation du format du tunnel (3 parcours :
    //    forfait / estimation / audit). On ramène tout à la forme attendue
    //    par la suite du plugin (validation, BDD, e-mails).
    $kind       = in_array($payload['kind'] ?? '', ['forfait', 'estimation', 'audit'], true) ? $payload['kind'] : 'audit';
    $contact    = is_array($payload['contact'] ?? null)    ? $payload['contact']    : [];
    $booking    = is_array($payload['booking'] ?? null)    ? $payload['booking']    : [];
    $estimation = is_array($payload['estimation'] ?? null) ? $payload['estimation'] : [];
    $details    = is_array($payload['details'] ?? null)    ? $payload['details']    : [];

    // Libellé lisible de la taille / du bien
    $taille_labels = ['studio' => 'Studio ≤ 25 m²', 'p2' => '2 pièces', 'p3' => '3 pièces', 'p4' => '4 pièces'];
    $bien = '';
    if (!empty($booking['taille']))       $bien = $taille_labels[$booking['taille']] ?? (string) $booking['taille'];
    elseif (!empty($details['typeBien'])) $bien = (string) $details['typeBien'];

    // Options du forfait → tableau [slug => true] (pour affichage e-mail)
    $opts = [];
    if (!empty($booking['options']) && is_array($booking['options'])) {
        foreach ($booking['options'] as $k) { $opts[sanitize_text_field($k)] = true; }
    }

    $data = [
        'presta_key' => sanitize_text_field($payload['presta'] ?? ''),
        'kind'       => $kind,
        'firstname'  => sanitize_text_field($contact['firstname'] ?? ''),
        'lastname'   => sanitize_text_field($contact['lastname'] ?? ''),
        'email'      => sanitize_email($contact['email'] ?? ''),
        'phone'      => sanitize_text_field($contact['phone'] ?? ''),
        'address'    => sanitize_text_field($contact['address'] ?? ''),
        'message'    => sanitize_textarea_field($contact['message'] ?? ($details['description'] ?? '')),
        'bien'       => sanitize_text_field($bien),
        'surface'    => (int) ($estimation['surface'] ?? ($details['surface'] ?? 0)),
        'frequence'  => sanitize_text_field((string) ($estimation['frequence'] ?? '')),
        'niveau'     => '',
        'delai'      => '',
        'options'    => $opts,
        'booking'    => $booking,
        'estimation' => $estimation,
    ];

    // 6. Validation
    $errors = kc_devis_validate($data);
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Données invalides : ' . implode(' ', $errors),
            'fields'  => $errors
        ], 400);
    }

    // 7. Calcul du prix serveur (sécurité — on ne fait pas confiance au client)
    $price = kc_devis_calculate_price($data);

    // 8. Sauvegarde en BDD
    $request_id = kc_devis_save_to_db($data, $price, $payload);
    if (!$request_id) {
        wp_send_json_error(['message' => 'Erreur technique, veuillez réessayer.'], 500);
    }

    // 9. Emails
    $admin_sent = kc_devis_send_admin_email($data, $price, $request_id);
    $ack_sent   = kc_devis_send_acknowledgment($data, $price);

    if (!$admin_sent || !$ack_sent) {
        error_log(sprintf('[KC Devis] Erreur email pour devis #%d — admin:%s, ack:%s',
            $request_id, $admin_sent ? 'OK' : 'KO', $ack_sent ? 'OK' : 'KO'));
    }

    // 10. Réponse
    wp_send_json_success([
        'message'    => 'Votre demande de devis a bien été enregistrée !',
        'request_id' => $request_id,
        'price'      => $price,
    ]);
}

/* ════════════════════════════════════════════════════════
   ✅ VALIDATION
   ════════════════════════════════════════════════════════ */
function kc_devis_validate($data) {
    $errors = [];

    // Prestation : slug connu de la grille des libellés
    $allowed = array_keys(kc_devis_presta_names());
    if (empty($data['presta_key']) || !in_array($data['presta_key'], $allowed, true)) {
        $errors['presta'] = 'Prestation invalide.';
    }

    // Nom/prénom
    if (empty($data['firstname']) || mb_strlen($data['firstname']) < 2 || mb_strlen($data['firstname']) > 40) {
        $errors['firstname'] = 'Prénom invalide.';
    }
    if (empty($data['lastname']) || mb_strlen($data['lastname']) < 2 || mb_strlen($data['lastname']) > 40) {
        $errors['lastname'] = 'Nom invalide.';
    }

    // Email
    if (empty($data['email']) || !is_email($data['email'])) {
        $errors['email'] = 'Email invalide.';
    }

    // Téléphone
    $phone_clean = preg_replace('/[\s.\-]/', '', $data['phone']);
    if (!preg_match('/^(?:\+33|0033|0)[1-9]\d{8}$/', $phone_clean)) {
        $errors['phone'] = 'Numéro de téléphone français invalide.';
    }

    // Adresse + Code postal IDF
    if (empty($data['address']) || mb_strlen($data['address']) < 10) {
        $errors['address'] = 'Adresse incomplète.';
    } else {
        preg_match('/\b(\d{5})\b/', $data['address'], $cp_match);
        if (empty($cp_match)) {
            $errors['address'] = 'Code postal manquant dans l\'adresse.';
        } else {
            $dept = substr($cp_match[1], 0, 2);
            $idf_depts = ['75','77','78','91','92','93','94','95'];
            if (!in_array($dept, $idf_depts, true)) {
                $errors['address'] = 'Adresse hors Île-de-France.';
            }
        }
    }

    // Surface (optionnelle selon prestation)
    if (!empty($data['surface']) && ($data['surface'] < 0 || $data['surface'] > 10000)) {
        $errors['surface'] = 'Surface invalide.';
    }

    return $errors;
}

/* ════════════════════════════════════════════════════════
   💰 CALCUL DU PRIX (côté serveur)
   ════════════════════════════════════════════════════════ */
function kc_devis_calculate_price($data) {
    $kind = $data['kind'] ?? 'audit';

    // Forfait logement : prix EXACT recalculé sur la grille officielle.
    if ($kind === 'forfait' && !empty($data['booking']['forfait']) && class_exists('KC_Pricing')) {
        try {
            $devis = KC_Pricing::compute_forfait($data['booking']);
            $total = (int) round($devis['total_cents'] / 100);
            return ['min' => $total, 'max' => $total, 'unit' => '€ TTC'];
        } catch (Exception $e) {
            return ['min' => 0, 'max' => 0, 'unit' => 'sur devis'];
        }
    }

    // Estimation pro : fourchette indicative fournie par le tunnel (bornée).
    if ($kind === 'estimation' && !empty($data['estimation'])) {
        $min  = (int) max(0, min((float) ($data['estimation']['min'] ?? 0), 1000000));
        $max  = (int) max($min, min((float) ($data['estimation']['max'] ?? 0), 1000000));
        $unit = sanitize_text_field((string) ($data['estimation']['unite'] ?? '€ HT'));
        return ['min' => $min, 'max' => $max, 'unit' => $unit];
    }

    // Audit / sur devis : pas de prix tant que la visite n'a pas eu lieu.
    return ['min' => 0, 'max' => 0, 'unit' => 'sur devis'];
}

/* Libellé d'affichage du prix (gère le cas « sur devis » et le prix unique). */
function kc_devis_price_label($price) {
    $min = (int) ($price['min'] ?? 0);
    $max = (int) ($price['max'] ?? 0);
    if ($min <= 0 && $max <= 0) return 'Sur devis (après visite gratuite)';
    if ($min === $max)          return number_format($min, 0, ',', ' ') . ' ' . ($price['unit'] ?? '€');
    return number_format($min, 0, ',', ' ') . ' – ' . number_format($max, 0, ',', ' ') . ' ' . ($price['unit'] ?? '€');
}

/* ════════════════════════════════════════════════════════
   💾 SAUVEGARDE
   ════════════════════════════════════════════════════════ */
function kc_devis_save_to_db($data, $price, $full_payload) {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_devis_requests';

    $presta_names = kc_devis_presta_names();
    preg_match('/\b(\d{5})\b/', $data['address'], $cp_match);
    $postal_code = $cp_match[1] ?? null;

    $inserted = $wpdb->insert(
        $table,
        [
            'created_at'   => current_time('mysql'),
            'presta_key'   => $data['presta_key'],
            'presta_name'  => $presta_names[$data['presta_key']] ?? 'Inconnu',
            'firstname'    => $data['firstname'],
            'lastname'     => $data['lastname'],
            'email'        => $data['email'],
            'phone'        => $data['phone'],
            'address'      => $data['address'],
            'postal_code'  => $postal_code,
            'bien'         => $data['bien'] ?: null,
            'surface'      => $data['surface'] ?: null,
            'frequence'    => $data['frequence'] ?: null,
            'niveau'       => $data['niveau'] ?: null,
            'delai'        => $data['delai'] ?: null,
            'options_json' => !empty($data['options']) ? wp_json_encode($data['options']) : null,
            'price_min'    => $price['min'],
            'price_max'    => $price['max'],
            'price_unit'   => $price['unit'],
            'message'      => $data['message'] ?: null,
            'full_payload' => wp_json_encode($full_payload),
            'ip_address'   => kc_devis_get_ip(),
            'user_agent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'status'       => 'new',
        ]
    );

    return $inserted ? $wpdb->insert_id : false;
}

/* ════════════════════════════════════════════════════════
   📧 EMAIL ADMIN
   ════════════════════════════════════════════════════════ */
function kc_devis_send_admin_email($data, $price, $request_id) {
    $presta_names = kc_devis_presta_names();
    $presta_name = $presta_names[$data['presta_key']] ?? 'Devis';

    $subject = sprintf('[Kayli Clinn] 💼 Nouveau devis %s — %s %s (#%d)',
        $presta_name, $data['firstname'], $data['lastname'], $request_id);

    $price_display = kc_devis_price_label($price);

    $body  = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f8f9fb;padding:20px;color:#1F2937;margin:0;'>";
    $body .= "<div style='max-width:640px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);'>";

    // Header
    $body .= "<div style='background:linear-gradient(135deg,#0FA7A5 0%,#0B8483 100%);color:#fff;padding:28px 32px;'>";
    $body .= "<div style='font-size:11px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.85;margin-bottom:6px;'>Nouveau devis #" . $request_id . " · " . date('d/m/Y H:i') . "</div>";
    $body .= "<h1 style='margin:0;font-size:22px;'>" . esc_html($presta_name) . "</h1>";
    $body .= "<div style='margin-top:12px;font-size:24px;font-weight:800;letter-spacing:-0.5px;'>" . esc_html($price_display) . "</div>";
    $body .= "</div>";

    // Body
    $body .= "<div style='padding:28px 32px;'>";

    // CTAs rapides
    $body .= "<div style='display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;'>";
    $body .= "<a href='mailto:" . esc_attr($data['email']) . "' style='background:#0FA7A5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;'>📧 Répondre</a>";
    $body .= "<a href='tel:" . esc_attr(preg_replace('/\s/', '', $data['phone'])) . "' style='background:#10B981;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;'>📞 Appeler</a>";
    $body .= "<a href='" . admin_url('admin.php?page=kc-devis-requests') . "' style='background:#0D2340;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;'>📋 Voir détails</a>";
    $body .= "</div>";

    // Coordonnées client
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;'>Client</div>";
    $body .= "<table style='width:100%;border-collapse:collapse;margin-bottom:24px;'>";
    $body .= kc_devis_email_row('Nom complet', esc_html($data['firstname'] . ' ' . $data['lastname']));
    $body .= kc_devis_email_row('Email', '<a href="mailto:' . esc_attr($data['email']) . '" style="color:#0B8483;">' . esc_html($data['email']) . '</a>');
    $body .= kc_devis_email_row('Téléphone', '<a href="tel:' . esc_attr(preg_replace('/\s/', '', $data['phone'])) . '" style="color:#0B8483;">' . esc_html($data['phone']) . '</a>');
    $body .= kc_devis_email_row('Adresse', esc_html($data['address']));
    $body .= "</table>";

    // Détails de la prestation
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;'>Détails de la demande</div>";
    $body .= "<table style='width:100%;border-collapse:collapse;margin-bottom:24px;'>";
    if (!empty($data['bien']))       $body .= kc_devis_email_row('Type de bien',  esc_html($data['bien']));
    if (!empty($data['surface']))    $body .= kc_devis_email_row('Surface',       esc_html($data['surface']) . ' m²');
    if (!empty($data['frequence']))  $body .= kc_devis_email_row('Fréquence',     esc_html($data['frequence']) . '× / semaine');
    if (!empty($data['niveau']))     $body .= kc_devis_email_row('Niveau état',   esc_html(ucfirst(str_replace('-', ' ', $data['niveau']))));
    if (!empty($data['delai'])) {
        $delais = ['flex' => 'Standard (3-7 jours)', 'rapide' => 'Rapide (48h)', 'urgent' => 'Urgence (24h)'];
        $body .= kc_devis_email_row('Délai', esc_html($delais[$data['delai']] ?? $data['delai']));
    }
    $body .= "</table>";

    // Options
    if (!empty($data['options']) && is_array($data['options'])) {
        $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;'>Options sélectionnées</div>";
        $body .= "<ul style='margin:0 0 24px 0;padding-left:20px;color:#1F2937;font-size:14px;'>";
        foreach ($data['options'] as $key => $opt) {
            $body .= "<li style='margin-bottom:4px;'>" . esc_html(str_replace('-', ' ', $key)) . "</li>";
        }
        $body .= "</ul>";
    }

    // Message client
    if (!empty($data['message'])) {
        $body .= "<div style='padding:18px;background:#F0FAFA;border-left:4px solid #0FA7A5;border-radius:6px;margin-bottom:20px;'>";
        $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;'>Message du client</div>";
        $body .= "<div style='white-space:pre-wrap;line-height:1.6;color:#1F2937;font-size:14px;'>" . esc_html($data['message']) . "</div>";
        $body .= "</div>";
    }

    // Métadonnées
    $body .= "<div style='margin-top:24px;padding-top:18px;border-top:1px solid #E5E7EB;font-size:11px;color:#6B7280;'>";
    $body .= "IP : " . esc_html(kc_devis_get_ip()) . " · User-Agent : " . esc_html(substr($_SERVER['HTTP_USER_AGENT'] ?? 'inconnu', 0, 100));
    $body .= "</div>";

    $body .= "</div></div></body></html>";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . KC_DEVIS_FROM_NAME . ' <' . KC_DEVIS_FROM_EMAIL . '>',
        'Reply-To: ' . $data['firstname'] . ' ' . $data['lastname'] . ' <' . $data['email'] . '>',
    ];

    return wp_mail(KC_DEVIS_ADMIN_EMAIL, $subject, $body, $headers);
}

/* ════════════════════════════════════════════════════════
   📨 ACCUSÉ DE RÉCEPTION CLIENT
   ════════════════════════════════════════════════════════ */
function kc_devis_send_acknowledgment($data, $price) {
    $presta_names = kc_devis_presta_names();
    $presta_name = $presta_names[$data['presta_key']] ?? 'votre demande';

    $subject = 'Votre demande de devis a bien été reçue — Kayli Clinn';
    $price_display = kc_devis_price_label($price);

    $body  = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f8f9fb;padding:20px;color:#1F2937;margin:0;'>";
    $body .= "<div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);'>";

    // Header
    $body .= "<div style='background:linear-gradient(135deg,#0FA7A5 0%,#0B8483 100%);color:#fff;padding:36px;text-align:center;'>";
    $body .= "<div style='width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 18px;display:inline-block;line-height:64px;font-size:32px;'>✓</div>";
    $body .= "<h1 style='margin:0;font-size:24px;letter-spacing:-0.5px;'>Demande bien reçue !</h1>";
    $body .= "<p style='margin:10px 0 0;opacity:0.9;font-size:14px;'>Estimation pour " . esc_html(strtolower($presta_name)) . "</p>";
    $body .= "</div>";

    // Body
    $body .= "<div style='padding:32px;line-height:1.6;'>";
    $body .= "<p style='font-size:16px;margin:0 0 16px;'>Bonjour " . esc_html($data['firstname']) . ",</p>";
    $body .= "<p>Nous avons bien reçu votre demande de devis pour <strong>" . esc_html($presta_name) . "</strong>. Notre équipe étudie votre demande et vous transmettra un <strong>devis détaillé sous 48h ouvrées</strong>.</p>";

    // Récap prix
    $body .= "<div style='margin:24px 0;padding:24px;background:#F0FAFA;border-radius:10px;border-left:4px solid #0FA7A5;text-align:center;'>";
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;'>Estimation indicative</div>";
    $body .= "<div style='font-size:28px;font-weight:800;color:#0D2340;letter-spacing:-1px;margin-bottom:4px;'>" . esc_html($price_display) . "</div>";
    $body .= "<div style='font-size:12px;color:#6B7280;font-style:italic;'>Estimation à ±15% · devis ferme transmis sous 48h</div>";
    $body .= "</div>";

    // Récap demande
    $body .= "<div style='margin:20px 0;padding:18px;background:#F8F9FB;border-radius:8px;border:1px solid #E5E7EB;'>";
    $body .= "<div style='font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;'>Récapitulatif</div>";
    $body .= "<table style='width:100%;border-collapse:collapse;font-size:13px;'>";
    if (!empty($data['bien']))    $body .= kc_devis_email_row('Type de bien', esc_html($data['bien']));
    if (!empty($data['surface'])) $body .= kc_devis_email_row('Surface', esc_html($data['surface']) . ' m²');
    $body .= kc_devis_email_row('Adresse', esc_html($data['address']));
    $body .= "</table>";
    $body .= "</div>";

    // Étapes suivantes
    $body .= "<div style='margin:24px 0;'>";
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;'>Prochaines étapes</div>";
    $body .= "<ol style='padding-left:20px;font-size:14px;color:#4B5563;line-height:1.8;margin:0;'>";
    $body .= "<li>Nous étudions votre demande</li>";
    $body .= "<li>Nous vous contactons sous 48h ouvrées</li>";
    $body .= "<li>Devis ferme & rendez-vous éventuel</li>";
    $body .= "<li>Intervention selon planning convenu</li>";
    $body .= "</ol></div>";

    // Contact direct
    $body .= "<p>Une question avant ?</p>";
    $body .= "<div style='margin:20px 0;'>";
    $body .= "<a href='tel:+33670012061' style='display:inline-block;background:#0FA7A5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;margin-right:8px;margin-bottom:8px;'>📞 06 70 01 20 61</a>";
    $body .= "<a href='https://wa.me/33670012061' style='display:inline-block;background:#25D366;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>💬 WhatsApp</a>";
    $body .= "</div>";

    $body .= "<p style='margin-top:24px;'>À très bientôt,<br><strong>L'équipe Kayli Clinn</strong></p>";
    $body .= "</div>";

    // Footer
    $body .= "<div style='background:#F8F9FB;padding:20px 32px;border-top:1px solid #E5E7EB;text-align:center;font-size:11px;color:#6B7280;line-height:1.5;'>";
    $body .= "Cet email automatique confirme la réception de votre demande de devis.<br>";
    $body .= "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce message.";
    $body .= "</div>";

    $body .= "</div></body></html>";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . KC_DEVIS_FROM_NAME . ' <' . KC_DEVIS_FROM_EMAIL . '>',
        'Reply-To: ' . KC_DEVIS_ADMIN_EMAIL,
    ];

    return wp_mail($data['email'], $subject, $body, $headers);
}

/* ════════════════════════════════════════════════════════
   🔧 UTILITAIRES
   ════════════════════════════════════════════════════════ */
function kc_devis_email_row($label, $value) {
    return "<tr>"
        . "<td style='padding:8px 0;border-bottom:1px solid #F3F4F6;font-weight:600;color:#0D2340;width:140px;font-size:13px;'>$label</td>"
        . "<td style='padding:8px 0;border-bottom:1px solid #F3F4F6;color:#1F2937;font-size:14px;'>$value</td>"
        . "</tr>";
}

function kc_devis_get_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function kc_devis_check_rate_limit() {
    $ip = kc_devis_get_ip();
    $key = 'kc_devis_rl_' . md5($ip);
    $count = (int) get_transient($key);
    if ($count >= KC_DEVIS_RATE_LIMIT) return false;
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
    return true;
}

/* ════════════════════════════════════════════════════════
   📤 EXPOSE NONCE AU JS
   ════════════════════════════════════════════════════════ */
add_action('wp_enqueue_scripts', 'kc_devis_enqueue');
function kc_devis_enqueue() {
    wp_register_script('kc-devis-config', '', [], false, true);
    wp_enqueue_script('kc-devis-config');
    wp_localize_script('kc-devis-config', 'kcDevis', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('kc_devis_action'),
    ]);
}

/* ════════════════════════════════════════════════════════
   👨‍💼 PAGE ADMIN — Liste des devis
   ════════════════════════════════════════════════════════ */
add_action('admin_menu', 'kc_devis_admin_menu');
function kc_devis_admin_menu() {
    add_menu_page(
        'Demandes de devis',
        'Devis',
        'manage_options',
        'kc-devis-requests',
        'kc_devis_admin_page',
        'dashicons-money-alt',
        31
    );
}

function kc_devis_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_devis_requests';

    // Détail d'un devis ?
    if (isset($_GET['view']) && current_user_can('manage_options')) {
        kc_devis_admin_detail((int) $_GET['view']);
        return;
    }

    // Changement de statut ?
    if (isset($_GET['set_status'], $_GET['id']) && current_user_can('manage_options')) {
        $allowed = ['new','contacted','quoted','won','lost','archived'];
        $new_status = sanitize_text_field($_GET['set_status']);
        if (in_array($new_status, $allowed, true)) {
            $wpdb->update($table, ['status' => $new_status], ['id' => (int) $_GET['id']]);
            echo '<div class="notice notice-success is-dismissible"><p>Statut mis à jour.</p></div>';
        }
    }

    // Filtre par statut
    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $where = ($filter_status !== 'all') ? $wpdb->prepare("WHERE status = %s", $filter_status) : '';

    $requests = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 200");

    // Stats globales
    $stats = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table GROUP BY status");
    $stats_map = [];
    foreach ($stats as $s) $stats_map[$s->status] = (int) $s->count;
    ?>
    <div class="wrap">
        <h1>Demandes de devis</h1>
        <p>Les 200 dernières demandes reçues. <strong>Total : <?php echo array_sum($stats_map); ?> devis.</strong></p>

        <!-- Onglets de filtre -->
        <ul class="subsubsub">
            <li><a href="?page=kc-devis-requests&status=all" <?php echo $filter_status === 'all' ? 'class="current"' : ''; ?>>Tous (<?php echo array_sum($stats_map); ?>)</a> |</li>
            <li><a href="?page=kc-devis-requests&status=new" <?php echo $filter_status === 'new' ? 'class="current"' : ''; ?>>Nouveau (<?php echo $stats_map['new'] ?? 0; ?>)</a> |</li>
            <li><a href="?page=kc-devis-requests&status=contacted" <?php echo $filter_status === 'contacted' ? 'class="current"' : ''; ?>>Contacté (<?php echo $stats_map['contacted'] ?? 0; ?>)</a> |</li>
            <li><a href="?page=kc-devis-requests&status=quoted" <?php echo $filter_status === 'quoted' ? 'class="current"' : ''; ?>>Devis envoyé (<?php echo $stats_map['quoted'] ?? 0; ?>)</a> |</li>
            <li><a href="?page=kc-devis-requests&status=won" <?php echo $filter_status === 'won' ? 'class="current"' : ''; ?>>Gagné (<?php echo $stats_map['won'] ?? 0; ?>)</a> |</li>
            <li><a href="?page=kc-devis-requests&status=lost" <?php echo $filter_status === 'lost' ? 'class="current"' : ''; ?>>Perdu (<?php echo $stats_map['lost'] ?? 0; ?>)</a></li>
        </ul>

        <table class="wp-list-table widefat fixed striped" style="margin-top:20px">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:120px;">Date</th>
                    <th>Client</th>
                    <th>Prestation</th>
                    <th style="width:140px;">Estimation</th>
                    <th style="width:100px;">Statut</th>
                    <th style="width:120px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:#6B7280;">Aucune demande pour ce statut.</td></tr>
                <?php else : foreach ($requests as $r) :
                    $price_display = number_format($r->price_min, 0, ',', ' ') . ' – ' . number_format($r->price_max, 0, ',', ' ') . ' €';
                    ?>
                    <tr style="<?php echo $r->status === 'new' ? 'background:#fffbea;' : ''; ?>">
                        <td><strong>#<?php echo (int) $r->id; ?></strong></td>
                        <td><?php echo esc_html(date('d/m H:i', strtotime($r->created_at))); ?></td>
                        <td>
                            <strong><?php echo esc_html($r->firstname . ' ' . $r->lastname); ?></strong><br>
                            <small><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a> · <?php echo esc_html($r->phone); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($r->presta_name); ?></strong>
                            <?php if ($r->surface) : ?><br><small><?php echo (int) $r->surface; ?> m²<?php if ($r->bien) echo ' · ' . esc_html($r->bien); ?></small><?php endif; ?>
                        </td>
                        <td><strong style="color:#0B8483;"><?php echo esc_html($price_display); ?></strong></td>
                        <td>
                            <?php
                            $badges = [
                                'new'       => ['#FEF3C7', '#92400E', 'Nouveau'],
                                'contacted' => ['#DBEAFE', '#1E40AF', 'Contacté'],
                                'quoted'    => ['#E0E7FF', '#3730A3', 'Devis envoyé'],
                                'won'       => ['#D1FAE5', '#065F46', 'Gagné 🎉'],
                                'lost'      => ['#FEE2E2', '#991B1B', 'Perdu'],
                                'archived'  => ['#F3F4F6', '#374151', 'Archivé'],
                            ];
                            $b = $badges[$r->status] ?? $badges['new'];
                            echo '<span style="background:' . $b[0] . ';color:' . $b[1] . ';padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;display:inline-block;">' . $b[2] . '</span>';
                            ?>
                        </td>
                        <td>
                            <a href="?page=kc-devis-requests&view=<?php echo (int) $r->id; ?>" class="button button-small">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Page de détail d'un devis (avec actions de changement de statut)
 */
function kc_devis_admin_detail($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_devis_requests';
    $r = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    if (!$r) {
        echo '<div class="wrap"><h1>Devis introuvable</h1><a href="?page=kc-devis-requests">← Retour</a></div>';
        return;
    }

    $price_display = number_format($r->price_min, 0, ',', ' ') . ' – ' . number_format($r->price_max, 0, ',', ' ') . ' ' . esc_html($r->price_unit);
    $options = $r->options_json ? json_decode($r->options_json, true) : [];
    ?>
    <div class="wrap">
        <a href="?page=kc-devis-requests" class="button" style="margin-bottom:20px">← Retour à la liste</a>
        <h1>Devis #<?php echo (int) $r->id; ?> — <?php echo esc_html($r->presta_name); ?></h1>
        <p style="color:#6B7280;">Reçu le <?php echo esc_html(date('d/m/Y à H:i', strtotime($r->created_at))); ?></p>

        <!-- Actions rapides -->
        <div style="background:#fff;padding:20px;border-radius:8px;border:1px solid #E5E7EB;margin:20px 0;">
            <strong>Actions :</strong>
            <a href="?page=kc-devis-requests&set_status=contacted&id=<?php echo $id; ?>" class="button">Marquer contacté</a>
            <a href="?page=kc-devis-requests&set_status=quoted&id=<?php echo $id; ?>" class="button">Devis envoyé</a>
            <a href="?page=kc-devis-requests&set_status=won&id=<?php echo $id; ?>" class="button button-primary">🎉 Gagné</a>
            <a href="?page=kc-devis-requests&set_status=lost&id=<?php echo $id; ?>" class="button">Perdu</a>
            <a href="?page=kc-devis-requests&set_status=archived&id=<?php echo $id; ?>" class="button">Archiver</a>
        </div>

        <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;">

            <!-- Détails -->
            <div style="background:#fff;padding:24px;border-radius:8px;border:1px solid #E5E7EB;">
                <h2 style="margin-top:0;">Estimation</h2>
                <div style="font-size:28px;font-weight:800;color:#0B8483;margin-bottom:20px;"><?php echo $price_display; ?></div>

                <h3>Coordonnées</h3>
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                    <tr><td style="padding:6px 0;width:140px;"><strong>Nom :</strong></td><td><?php echo esc_html($r->firstname . ' ' . $r->lastname); ?></td></tr>
                    <tr><td style="padding:6px 0;"><strong>Email :</strong></td><td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td></tr>
                    <tr><td style="padding:6px 0;"><strong>Téléphone :</strong></td><td><a href="tel:<?php echo esc_attr(preg_replace('/\s/', '', $r->phone)); ?>"><?php echo esc_html($r->phone); ?></a></td></tr>
                    <tr><td style="padding:6px 0;"><strong>Adresse :</strong></td><td><?php echo esc_html($r->address); ?></td></tr>
                </table>

                <h3>Détails de la demande</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <?php if ($r->bien)      : ?><tr><td style="padding:6px 0;width:140px;"><strong>Type de bien :</strong></td><td><?php echo esc_html($r->bien); ?></td></tr><?php endif; ?>
                    <?php if ($r->surface)   : ?><tr><td style="padding:6px 0;"><strong>Surface :</strong></td><td><?php echo (int) $r->surface; ?> m²</td></tr><?php endif; ?>
                    <?php if ($r->frequence) : ?><tr><td style="padding:6px 0;"><strong>Fréquence :</strong></td><td><?php echo esc_html($r->frequence); ?>× / semaine</td></tr><?php endif; ?>
                    <?php if ($r->niveau)    : ?><tr><td style="padding:6px 0;"><strong>Niveau :</strong></td><td><?php echo esc_html(ucfirst(str_replace('-', ' ', $r->niveau))); ?></td></tr><?php endif; ?>
                    <?php if ($r->delai)     : ?><tr><td style="padding:6px 0;"><strong>Délai :</strong></td><td><?php echo esc_html($r->delai); ?></td></tr><?php endif; ?>
                </table>

                <?php if (!empty($options)) : ?>
                <h3>Options sélectionnées</h3>
                <ul><?php foreach ($options as $key => $opt) : ?><li><?php echo esc_html(str_replace('-', ' ', $key)); ?></li><?php endforeach; ?></ul>
                <?php endif; ?>

                <?php if (!empty($r->message)) : ?>
                <h3>Message du client</h3>
                <div style="background:#F0FAFA;padding:14px;border-left:4px solid #0FA7A5;border-radius:6px;white-space:pre-wrap;"><?php echo esc_html($r->message); ?></div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="background:#fff;padding:20px;border-radius:8px;border:1px solid #E5E7EB;">
                    <h3 style="margin-top:0;">Contact direct</h3>
                    <a href="mailto:<?php echo esc_attr($r->email); ?>?subject=Votre demande de devis Kayli Clinn" class="button button-primary" style="width:100%;text-align:center;margin-bottom:8px;">📧 Répondre par email</a>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s/', '', $r->phone)); ?>" class="button" style="width:100%;text-align:center;">📞 Appeler</a>
                </div>

                <div style="background:#fff;padding:20px;border-radius:8px;border:1px solid #E5E7EB;font-size:12px;color:#6B7280;">
                    <h3 style="margin-top:0;">Métadonnées</h3>
                    <p><strong>IP :</strong> <?php echo esc_html($r->ip_address); ?></p>
                    <p><strong>User-Agent :</strong><br><?php echo esc_html(substr($r->user_agent, 0, 100)); ?>...</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}