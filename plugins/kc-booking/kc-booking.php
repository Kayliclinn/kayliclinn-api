<?php
/**
 * Plugin Name: Kayli Clinn — Réservations
 * Description: Système de réservation complet. Phases 1+1.2 (BDD, types, équipe, variants) + Phase 2 (Google Calendar) + Phase 3 (API publique, réservations, e-mails, paiements).
 * Version: 2.0.0
 * Author: Kayli Clinn
 * Text Domain: kc-booking
 */

if (!defined('ABSPATH')) exit;

define('KC_BOOKING_VERSION', '2.0.0');
define('KC_BOOKING_DB_VERSION', '1.2');
if (!defined('KC_BOOKING_REST_NS'))          define('KC_BOOKING_REST_NS', 'kc-booking/v1');
if (!defined('KC_BOOKING_PHASE_1_2_FLAG'))   define('KC_BOOKING_PHASE_1_2_FLAG', 'kc_booking_phase_1_2_done');

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


/* ════════════════════════════════════════════════════════════════════════
                      PHASE 1 — TABLES BDD + ADMIN
   ════════════════════════════════════════════════════════════════════════ */

register_activation_hook(__FILE__, 'kc_booking_install');
function kc_booking_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql_types = "CREATE TABLE {$wpdb->prefix}kc_booking_types (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        icon VARCHAR(50) DEFAULT 'calendar',
        color VARCHAR(7) DEFAULT '#0FA7A5',
        duration_minutes INT(11) NOT NULL DEFAULT 60,
        buffer_minutes INT(11) NOT NULL DEFAULT 15,
        min_advance_hours INT(11) NOT NULL DEFAULT 24,
        max_advance_days INT(11) NOT NULL DEFAULT 60,
        price DECIMAL(10,2) DEFAULT NULL,
        is_free TINYINT(1) NOT NULL DEFAULT 1,
        allow_team_selection TINYINT(1) NOT NULL DEFAULT 0,
        require_address TINYINT(1) NOT NULL DEFAULT 1,
        confirmation_email_text TEXT DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        position INT(11) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY is_active (is_active)
    ) $charset_collate;";

    $sql_staff = "CREATE TABLE {$wpdb->prefix}kc_booking_staff (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        role VARCHAR(100) DEFAULT NULL,
        color VARCHAR(7) DEFAULT '#0FA7A5',
        google_calendar_id VARCHAR(255) DEFAULT NULL,
        working_hours_json TEXT DEFAULT NULL,
        timezone VARCHAR(50) DEFAULT 'Europe/Paris',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        position INT(11) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY is_active (is_active)
    ) $charset_collate;";

    $sql_type_staff = "CREATE TABLE {$wpdb->prefix}kc_booking_type_staff (
        type_id BIGINT(20) UNSIGNED NOT NULL,
        staff_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (type_id, staff_id),
        KEY staff_id (staff_id)
    ) $charset_collate;";

    $sql_bookings = "CREATE TABLE {$wpdb->prefix}kc_bookings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type_id BIGINT(20) UNSIGNED NOT NULL,
        staff_id BIGINT(20) UNSIGNED DEFAULT NULL,
        client_firstname VARCHAR(50) NOT NULL,
        client_lastname VARCHAR(50) NOT NULL,
        client_email VARCHAR(100) NOT NULL,
        client_phone VARCHAR(20) NOT NULL,
        client_address VARCHAR(255) DEFAULT NULL,
        client_message TEXT DEFAULT NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        timezone VARCHAR(50) DEFAULT 'Europe/Paris',
        google_event_id VARCHAR(255) DEFAULT NULL,
        cancellation_token VARCHAR(64) DEFAULT NULL,
        status ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
        cancelled_at DATETIME DEFAULT NULL,
        cancellation_reason TEXT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status),
        KEY start_datetime (start_datetime),
        KEY type_id (type_id),
        KEY staff_id (staff_id),
        UNIQUE KEY cancellation_token (cancellation_token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_types);
    dbDelta($sql_staff);
    dbDelta($sql_type_staff);
    dbDelta($sql_bookings);

    if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_booking_staff") == 0) {
        kc_booking_create_default_staff();
    }

    update_option('kc_booking_db_version', KC_BOOKING_DB_VERSION);
}

function kc_booking_create_default_staff() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_staff';
    $default_hours = wp_json_encode([
        'mon' => ['active' => 1, 'start' => '09:00', 'end' => '18:00'],
        'tue' => ['active' => 1, 'start' => '09:00', 'end' => '18:00'],
        'wed' => ['active' => 1, 'start' => '09:00', 'end' => '18:00'],
        'thu' => ['active' => 1, 'start' => '09:00', 'end' => '18:00'],
        'fri' => ['active' => 1, 'start' => '09:00', 'end' => '18:00'],
        'sat' => ['active' => 0, 'start' => '09:00', 'end' => '18:00'],
        'sun' => ['active' => 0, 'start' => '09:00', 'end' => '18:00'],
    ]);
    $defaults = [
        ['name' => 'Kayli',           'email' => 'contact@kayliclinn.fr', 'role' => 'Gérante',     'color' => '#0FA7A5', 'working_hours_json' => $default_hours, 'is_active' => 1, 'position' => 1],
        ['name' => 'Collaborateur 1', 'email' => 'collab1@kayliclinn.fr', 'role' => 'Intervenant', 'color' => '#B65B35', 'working_hours_json' => $default_hours, 'is_active' => 1, 'position' => 2],
        ['name' => 'Collaborateur 2', 'email' => 'collab2@kayliclinn.fr', 'role' => 'Intervenant', 'color' => '#081D3A', 'working_hours_json' => $default_hours, 'is_active' => 1, 'position' => 3],
        ['name' => 'Collaborateur 3', 'email' => 'collab3@kayliclinn.fr', 'role' => 'Intervenant', 'color' => '#A88959', 'working_hours_json' => $default_hours, 'is_active' => 1, 'position' => 4],
        ['name' => 'Collaborateur 4', 'email' => 'collab4@kayliclinn.fr', 'role' => 'Intervenant', 'color' => '#0B8483', 'working_hours_json' => $default_hours, 'is_active' => 1, 'position' => 5],
    ];
    foreach ($defaults as $staff) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $staff['email']));
        if (!$exists) $wpdb->insert($table, $staff);
    }
}

/* ──────── MENU ADMINISTRATION ──────── */
add_action('admin_menu', 'kc_booking_admin_menu');
function kc_booking_admin_menu() {
    add_menu_page('Réservations', 'Réservations', 'manage_options', 'kc-booking-dashboard', 'kc_booking_admin_dashboard', 'dashicons-calendar-alt', 32);
    add_submenu_page('kc-booking-dashboard', 'Tableau de bord', 'Tableau de bord', 'manage_options', 'kc-booking-dashboard', 'kc_booking_admin_dashboard');
    add_submenu_page('kc-booking-dashboard', 'Types de RDV', 'Types de RDV', 'manage_options', 'kc-booking-types', 'kc_booking_admin_types');
    add_submenu_page('kc-booking-dashboard', 'Équipe', 'Équipe', 'manage_options', 'kc-booking-staff', 'kc_booking_admin_staff');
    add_submenu_page('kc-booking-dashboard', 'Réservations', 'Toutes les réservations', 'manage_options', 'kc-booking-list', 'kc_booking_admin_list');
    add_submenu_page('kc-booking-dashboard', 'Réglages', 'Réglages', 'manage_options', 'kc-booking-settings', 'kc_booking_admin_settings');
}

/* ──────── PAGE : Tableau de bord ──────── */
function kc_booking_admin_dashboard() {
    global $wpdb;
    $stats = [
        'total_types'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_booking_types WHERE is_active = 1"),
        'total_staff'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_booking_staff WHERE is_active = 1"),
        'total_bookings' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_bookings"),
        'pending'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_bookings WHERE status IN ('pending','confirmed') AND start_datetime > NOW()"),
        'today'          => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kc_bookings WHERE DATE(start_datetime) = CURDATE()"),
    ];
    $upcoming = $wpdb->get_results("
        SELECT b.*, t.name as type_name, t.color as type_color, s.name as staff_name
        FROM {$wpdb->prefix}kc_bookings b
        LEFT JOIN {$wpdb->prefix}kc_booking_types t ON t.id = b.type_id
        LEFT JOIN {$wpdb->prefix}kc_booking_staff s ON s.id = b.staff_id
        WHERE b.start_datetime > NOW() AND b.status IN ('pending','confirmed')
        ORDER BY b.start_datetime ASC LIMIT 10
    ");
    ?>
    <div class="wrap">
        <h1>📅 Tableau de bord — Réservations</h1>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:20px;">
                <div style="font-size:11px;color:#6B7280;letter-spacing:0.06em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Types actifs</div>
                <div style="font-size:32px;font-weight:800;color:#0D2340;letter-spacing:-1px;"><?php echo $stats['total_types']; ?></div>
            </div>
            <div style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:20px;">
                <div style="font-size:11px;color:#6B7280;letter-spacing:0.06em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Équipe</div>
                <div style="font-size:32px;font-weight:800;color:#0D2340;letter-spacing:-1px;"><?php echo $stats['total_staff']; ?></div>
            </div>
            <div style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:20px;">
                <div style="font-size:11px;color:#6B7280;letter-spacing:0.06em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Total RDV</div>
                <div style="font-size:32px;font-weight:800;color:#0D2340;letter-spacing:-1px;"><?php echo $stats['total_bookings']; ?></div>
            </div>
            <div style="background:#fff;border:1.5px solid #0FA7A5;border-radius:10px;padding:20px;">
                <div style="font-size:11px;color:#076E6D;letter-spacing:0.06em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">À venir</div>
                <div style="font-size:32px;font-weight:800;color:#0B8483;letter-spacing:-1px;"><?php echo $stats['pending']; ?></div>
            </div>
            <div style="background:#fff;border:1.5px solid #F59E0B;border-radius:10px;padding:20px;">
                <div style="font-size:11px;color:#92400E;letter-spacing:0.06em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Aujourd'hui</div>
                <div style="font-size:32px;font-weight:800;color:#B45309;letter-spacing:-1px;"><?php echo $stats['today']; ?></div>
            </div>
        </div>

        <h2 style="margin-top:30px;">🗓️ 10 prochains RDV</h2>
        <?php if (empty($upcoming)) : ?>
            <div style="background:#F8F9FB;padding:30px;text-align:center;border-radius:10px;color:#6B7280;">Aucune réservation à venir.</div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Date</th><th>Type</th><th>Intervenant</th><th>Client</th><th>Statut</th></tr></thead>
                <tbody>
                    <?php foreach ($upcoming as $b) : ?>
                    <tr>
                        <td>#<?php echo (int) $b->id; ?></td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($b->start_datetime))); ?></td>
                        <td><span style="background:<?php echo esc_attr($b->type_color); ?>;color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;"><?php echo esc_html($b->type_name); ?></span></td>
                        <td><?php echo esc_html($b->staff_name ?: '—'); ?></td>
                        <td><strong><?php echo esc_html($b->client_firstname . ' ' . $b->client_lastname); ?></strong><br><small><?php echo esc_html($b->client_email); ?></small></td>
                        <td><?php echo esc_html(ucfirst($b->status)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ──────── PAGE : Types de RDV (CRUD) ──────── */
function kc_booking_admin_types() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_types';

    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        check_admin_referer('kc_booking_delete_type');
        $wpdb->delete($table, ['id' => (int) $_GET['delete']]);
        echo '<div class="notice notice-success is-dismissible"><p>Type supprimé.</p></div>';
    }
    if (isset($_GET['toggle']) && current_user_can('manage_options')) {
        $id = (int) $_GET['toggle'];
        $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $table WHERE id = %d", $id));
        $wpdb->update($table, ['is_active' => $current ? 0 : 1], ['id' => $id]);
    }
    if (isset($_GET['edit']) || isset($_GET['new'])) { kc_booking_admin_type_edit(); return; }
    if (isset($_POST['kc_booking_type_save']) && check_admin_referer('kc_booking_save_type')) { kc_booking_save_type(); return; }

    $types = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC, id ASC");
    ?>
    <div class="wrap">
        <h1 style="display:inline-block;">🗂️ Types de RDV</h1>
        <a href="?page=kc-booking-types&new=1" class="page-title-action">➕ Nouveau type</a>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:60px;">ID</th><th>Nom</th><th>Slug</th><th>Durée</th><th>Prix</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (empty($types)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px;">Aucun type. <a href="?page=kc-booking-types&new=1">Créer le premier</a></td></tr>
                <?php else : foreach ($types as $t) : ?>
                <tr>
                    <td>#<?php echo (int) $t->id; ?></td>
                    <td>
                        <span style="display:inline-block;width:18px;height:18px;background:<?php echo esc_attr($t->color); ?>;border-radius:50%;vertical-align:middle;margin-right:10px;box-shadow:0 0 0 2px #fff,0 0 0 3px <?php echo esc_attr($t->color); ?>33;"></span>
                        <strong><?php echo esc_html($t->name); ?></strong>
                        <?php if ($t->description) : ?><br><small style="color:#6B7280;"><?php echo esc_html(mb_substr($t->description, 0, 90)); ?>…</small><?php endif; ?>
                    </td>
                    <td><code><?php echo esc_html($t->slug); ?></code></td>
                    <td><?php echo (int) $t->duration_minutes; ?> min</td>
                    <td><?php echo $t->is_free ? '<em>Gratuit</em>' : number_format($t->price, 2, ',', ' ') . ' €'; ?></td>
                    <td><?php if ($t->is_active) : ?><span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">✅ Actif</span><?php else : ?><span style="background:#FEE2E2;color:#991B1B;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">⛔ Désactivé</span><?php endif; ?></td>
                    <td>
                        <a href="?page=kc-booking-types&edit=<?php echo (int) $t->id; ?>" class="button button-small">Éditer</a>
                        <a href="?page=kc-booking-types&toggle=<?php echo (int) $t->id; ?>" class="button button-small">Basculer</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function kc_booking_admin_type_edit() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_types';
    $staff_table = $wpdb->prefix . 'kc_booking_staff';
    $link_table = $wpdb->prefix . 'kc_booking_type_staff';
    $is_new = isset($_GET['new']);
    $type = null;
    $linked_staff = [];
    if (!$is_new) {
        $id = (int) $_GET['edit'];
        $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$type) { echo '<div class="wrap"><p>Type introuvable.</p></div>'; return; }
        $linked_staff = $wpdb->get_col($wpdb->prepare("SELECT staff_id FROM $link_table WHERE type_id = %d", $id));
    }
    $all_staff = $wpdb->get_results("SELECT * FROM $staff_table WHERE is_active = 1 ORDER BY name ASC");
    $v = function($field, $default = '') use ($type) { return $type ? esc_attr($type->$field) : $default; };
    ?>
    <div class="wrap">
        <a href="?page=kc-booking-types" class="button" style="margin-bottom:14px;">← Retour</a>
        <h1><?php echo $is_new ? '➕ Nouveau type de RDV' : '✏️ Éditer : ' . esc_html($type->name); ?></h1>
        <form method="post" action="?page=kc-booking-types">
            <?php wp_nonce_field('kc_booking_save_type'); ?>
            <input type="hidden" name="kc_booking_type_save" value="1">
            <?php if (!$is_new) : ?><input type="hidden" name="id" value="<?php echo (int) $type->id; ?>"><?php endif; ?>
            <table class="form-table">
                <tr><th><label>Nom *</label></th><td><input type="text" name="name" value="<?php echo $v('name'); ?>" required class="regular-text"></td></tr>
                <tr><th><label>Description</label></th><td><textarea name="description" rows="3" class="large-text"><?php echo $type ? esc_textarea($type->description) : ''; ?></textarea></td></tr>
                <tr><th><label>Durée (min) *</label></th><td><input type="number" name="duration_minutes" value="<?php echo $v('duration_minutes', '60'); ?>" min="5" max="480" step="5" required></td></tr>
                <tr><th><label>Battement (min)</label></th><td><input type="number" name="buffer_minutes" value="<?php echo $v('buffer_minutes', '15'); ?>" min="0" max="120" step="5"></td></tr>
                <tr><th>Tarif</th><td><label><input type="checkbox" name="is_free" value="1" <?php echo (!$type || $type->is_free) ? 'checked' : ''; ?>> Gratuit</label><br><input type="number" name="price" value="<?php echo $type && !$type->is_free ? esc_attr($type->price) : ''; ?>" min="0" step="0.01" placeholder="0.00" style="margin-top:8px;"> €</td></tr>
                <tr><th>Options</th><td>
                    <label><input type="checkbox" name="require_address" value="1" <?php echo (!$type || $type->require_address) ? 'checked' : ''; ?>> Adresse obligatoire</label><br>
                    <label><input type="checkbox" name="allow_team_selection" value="1" <?php echo $type && $type->allow_team_selection ? 'checked' : ''; ?>> Le client choisit l'intervenant</label><br>
                    <label><input type="checkbox" name="is_active" value="1" <?php echo (!$type || $type->is_active) ? 'checked' : ''; ?>> Actif</label>
                </td></tr>
                <tr><th>Apparence</th><td><label>Couleur : <input type="color" name="color" value="<?php echo $v('color', '#0FA7A5'); ?>"></label></td></tr>
                <?php if (!empty($all_staff)) : ?>
                <tr><th>Intervenants</th><td>
                    <?php foreach ($all_staff as $s) : ?>
                    <label style="display:block;margin-bottom:6px;"><input type="checkbox" name="staff_ids[]" value="<?php echo (int) $s->id; ?>" <?php echo in_array($s->id, $linked_staff) ? 'checked' : ''; ?>><span style="display:inline-block;width:10px;height:10px;background:<?php echo esc_attr($s->color); ?>;border-radius:50%;vertical-align:middle;margin:0 4px;"></span><?php echo esc_html($s->name); ?></label>
                    <?php endforeach; ?>
                    <p class="description">Si aucun coché : tous peuvent prendre ce RDV.</p>
                </td></tr>
                <?php endif; ?>
            </table>
            <p><button type="submit" class="button button-primary button-large">💾 Enregistrer</button> <a href="?page=kc-booking-types" class="button button-large">Annuler</a></p>
        </form>
    </div>
    <?php
}

function kc_booking_save_type() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_types';
    $link_table = $wpdb->prefix . 'kc_booking_type_staff';
    $is_new = empty($_POST['id']);
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'slug' => sanitize_title($_POST['name']),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'duration_minutes' => max(5, min(480, (int) $_POST['duration_minutes'])),
        'buffer_minutes' => max(0, min(120, (int) ($_POST['buffer_minutes'] ?? 15))),
        'is_free' => !empty($_POST['is_free']) ? 1 : 0,
        'price' => !empty($_POST['is_free']) ? null : (float) ($_POST['price'] ?? 0),
        'require_address' => !empty($_POST['require_address']) ? 1 : 0,
        'allow_team_selection' => !empty($_POST['allow_team_selection']) ? 1 : 0,
        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        'color' => sanitize_hex_color($_POST['color'] ?? '#0FA7A5'),
    ];
    if ($is_new) { $wpdb->insert($table, $data); $type_id = $wpdb->insert_id; }
    else { $type_id = (int) $_POST['id']; $wpdb->update($table, $data, ['id' => $type_id]); }
    $wpdb->delete($link_table, ['type_id' => $type_id]);
    if (!empty($_POST['staff_ids']) && is_array($_POST['staff_ids'])) {
        foreach ($_POST['staff_ids'] as $sid) {
            $wpdb->insert($link_table, ['type_id' => $type_id, 'staff_id' => (int) $sid]);
        }
    }
    echo '<div class="notice notice-success"><p>✅ Enregistré</p></div>';
    echo '<script>setTimeout(()=>location.href="?page=kc-booking-types",1000)</script>';
}

/* ──────── PAGE : Équipe (CRUD) ──────── */
function kc_booking_admin_staff() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_staff';
    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        check_admin_referer('kc_booking_delete_staff');
        $wpdb->delete($table, ['id' => (int) $_GET['delete']]);
    }
    if (isset($_GET['toggle']) && current_user_can('manage_options')) {
        $id = (int) $_GET['toggle'];
        $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $table WHERE id = %d", $id));
        $wpdb->update($table, ['is_active' => $current ? 0 : 1], ['id' => $id]);
    }
    if (isset($_GET['edit']) || isset($_GET['new'])) { kc_booking_admin_staff_edit(); return; }
    if (isset($_POST['kc_booking_staff_save']) && check_admin_referer('kc_booking_save_staff')) { kc_booking_save_staff(); return; }
    $staff = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC, name ASC");
    ?>
    <div class="wrap">
        <h1 style="display:inline-block;">👥 Équipe</h1>
        <a href="?page=kc-booking-staff&new=1" class="page-title-action">➕ Ajouter</a>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:60px;">ID</th><th>Nom</th><th>Rôle</th><th>Email</th><th>Google Calendar</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (empty($staff)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px;">Aucun membre.</td></tr>
                <?php else : foreach ($staff as $s) : ?>
                <tr style="border-left:4px solid <?php echo esc_attr($s->color); ?>;">
                    <td>#<?php echo (int) $s->id; ?></td>
                    <td>
                        <span style="display:inline-block;width:18px;height:18px;background:<?php echo esc_attr($s->color); ?>;border-radius:50%;vertical-align:middle;margin-right:10px;box-shadow:0 0 0 2px #fff,0 0 0 3px <?php echo esc_attr($s->color); ?>33;"></span>
                        <strong><?php echo esc_html($s->name); ?></strong>
                    </td>
                    <td><?php echo esc_html($s->role ?: '—'); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($s->email); ?>"><?php echo esc_html($s->email); ?></a></td>
                    <td><?php if ($s->google_calendar_id) : ?><span style="color:#10B981;font-weight:600;">✓ Connecté</span><?php else : ?><em style="color:#9CA3AF;">—</em><?php endif; ?></td>
                    <td><?php echo $s->is_active ? '<span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">✅ Actif</span>' : '<span style="background:#FEE2E2;color:#991B1B;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">⛔ Off</span>'; ?></td>
                    <td>
                        <a href="?page=kc-booking-staff&edit=<?php echo (int) $s->id; ?>" class="button button-small">Éditer</a>
                        <a href="?page=kc-booking-staff&toggle=<?php echo (int) $s->id; ?>" class="button button-small">Basculer</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function kc_booking_admin_staff_edit() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_staff';
    $is_new = isset($_GET['new']);
    $staff = null;
    $hours = [];
    if (!$is_new) {
        $id = (int) $_GET['edit'];
        $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$staff) { echo '<div class="wrap"><p>Membre introuvable.</p></div>'; return; }
        $hours = $staff->working_hours_json ? json_decode($staff->working_hours_json, true) : [];
    }
    $default_hours = [
        'mon' => ['active'=>1, 'start'=>'09:00', 'end'=>'18:00'], 'tue' => ['active'=>1, 'start'=>'09:00', 'end'=>'18:00'],
        'wed' => ['active'=>1, 'start'=>'09:00', 'end'=>'18:00'], 'thu' => ['active'=>1, 'start'=>'09:00', 'end'=>'18:00'],
        'fri' => ['active'=>1, 'start'=>'09:00', 'end'=>'18:00'], 'sat' => ['active'=>0, 'start'=>'09:00', 'end'=>'18:00'],
        'sun' => ['active'=>0, 'start'=>'09:00', 'end'=>'18:00'],
    ];
    $hours = array_merge($default_hours, $hours);
    $day_labels = ['mon'=>'Lundi','tue'=>'Mardi','wed'=>'Mercredi','thu'=>'Jeudi','fri'=>'Vendredi','sat'=>'Samedi','sun'=>'Dimanche'];
    $v = function($field, $default = '') use ($staff) { return $staff ? esc_attr($staff->$field) : $default; };
    ?>
    <div class="wrap">
        <a href="?page=kc-booking-staff" class="button" style="margin-bottom:14px;">← Retour</a>
        <h1><?php echo $is_new ? '➕ Nouveau membre' : '✏️ Éditer : ' . esc_html($staff->name); ?></h1>
        <form method="post" action="?page=kc-booking-staff">
            <?php wp_nonce_field('kc_booking_save_staff'); ?>
            <input type="hidden" name="kc_booking_staff_save" value="1">
            <?php if (!$is_new) : ?><input type="hidden" name="id" value="<?php echo (int) $staff->id; ?>"><?php endif; ?>
            <h2>👤 Informations</h2>
            <table class="form-table">
                <tr><th><label>Nom *</label></th><td><input type="text" name="name" value="<?php echo $v('name'); ?>" required class="regular-text"></td></tr>
                <tr><th><label>Email *</label></th><td><input type="email" name="email" value="<?php echo $v('email'); ?>" required class="regular-text"></td></tr>
                <tr><th><label>Téléphone</label></th><td><input type="tel" name="phone" value="<?php echo $v('phone'); ?>"></td></tr>
                <tr><th><label>Rôle</label></th><td><input type="text" name="role" value="<?php echo $v('role'); ?>" class="regular-text"></td></tr>
                <tr><th>Couleur</th><td><input type="color" name="color" value="<?php echo $v('color', '#0FA7A5'); ?>"></td></tr>
                <tr><th>Statut</th><td><label><input type="checkbox" name="is_active" value="1" <?php echo (!$staff || $staff->is_active) ? 'checked' : ''; ?>> Actif</label></td></tr>
            </table>
            <h2>📅 Google Calendar</h2>
            <table class="form-table">
                <tr><th><label>ID Calendar Google</label></th><td><input type="text" name="google_calendar_id" value="<?php echo $v('google_calendar_id'); ?>" class="regular-text" placeholder="email@kayliclinn.fr"><p class="description">Souvent l'email Workspace suffit.</p></td></tr>
                <tr><th><label>Fuseau horaire</label></th><td><select name="timezone"><option value="Europe/Paris" <?php echo (!$staff || $staff->timezone === 'Europe/Paris') ? 'selected' : ''; ?>>Europe/Paris</option></select></td></tr>
            </table>
            <h2>⏰ Horaires de travail</h2>
            <table class="form-table">
                <?php foreach ($day_labels as $key => $label) : $h = $hours[$key] ?? ['active'=>0, 'start'=>'09:00', 'end'=>'18:00']; ?>
                <tr><th style="width:120px;"><?php echo esc_html($label); ?></th><td>
                    <label style="display:inline-block;margin-right:18px;"><input type="checkbox" name="hours[<?php echo $key; ?>][active]" value="1" <?php echo $h['active'] ? 'checked' : ''; ?>> Disponible</label>
                    de <input type="time" name="hours[<?php echo $key; ?>][start]" value="<?php echo esc_attr($h['start']); ?>">
                    à <input type="time" name="hours[<?php echo $key; ?>][end]" value="<?php echo esc_attr($h['end']); ?>">
                </td></tr>
                <?php endforeach; ?>
            </table>
            <p><button type="submit" class="button button-primary button-large">💾 Enregistrer</button> <a href="?page=kc-booking-staff" class="button button-large">Annuler</a></p>
        </form>
    </div>
    <?php
}

function kc_booking_save_staff() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_staff';
    $is_new = empty($_POST['id']);
    $hours = [];
    if (!empty($_POST['hours']) && is_array($_POST['hours'])) {
        foreach ($_POST['hours'] as $day => $h) {
            $hours[$day] = ['active' => !empty($h['active']) ? 1 : 0, 'start' => sanitize_text_field($h['start'] ?? '09:00'), 'end' => sanitize_text_field($h['end'] ?? '18:00')];
        }
    }
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'role' => sanitize_text_field($_POST['role'] ?? ''),
        'color' => sanitize_hex_color($_POST['color'] ?? '#0FA7A5'),
        'google_calendar_id' => sanitize_text_field($_POST['google_calendar_id'] ?? ''),
        'timezone' => sanitize_text_field($_POST['timezone'] ?? 'Europe/Paris'),
        'working_hours_json' => wp_json_encode($hours),
        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
    ];
    if ($is_new) { $wpdb->insert($table, $data); }
    else { $wpdb->update($table, $data, ['id' => (int) $_POST['id']]); }
    echo '<div class="notice notice-success"><p>✅ Enregistré</p></div>';
    echo '<script>setTimeout(()=>location.href="?page=kc-booking-staff",1000)</script>';
}

/* ──────── PAGE : Liste des réservations ──────── */
function kc_booking_admin_list() {
    global $wpdb;
    $bookings = $wpdb->get_results("
        SELECT b.*, t.name as type_name, t.color as type_color, s.name as staff_name
        FROM {$wpdb->prefix}kc_bookings b
        LEFT JOIN {$wpdb->prefix}kc_booking_types t ON t.id = b.type_id
        LEFT JOIN {$wpdb->prefix}kc_booking_staff s ON s.id = b.staff_id
        ORDER BY b.start_datetime DESC LIMIT 100
    ");
    ?>
    <div class="wrap">
        <h1>📋 Toutes les réservations</h1>
        <?php if (empty($bookings)) : ?>
            <div style="background:#F8F9FB;padding:30px;text-align:center;border-radius:10px;color:#6B7280;margin-top:20px;">Aucune réservation pour le moment.</div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Date</th><th>Type</th><th>Intervenant</th><th>Client</th><th>Statut</th></tr></thead>
                <tbody>
                    <?php foreach ($bookings as $b) : ?>
                    <tr style="border-left:4px solid <?php echo esc_attr($b->type_color); ?>;">
                        <td>#<?php echo (int) $b->id; ?></td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($b->start_datetime))); ?></td>
                        <td><span style="background:<?php echo esc_attr($b->type_color); ?>;color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;"><?php echo esc_html($b->type_name); ?></span></td>
                        <td><?php echo esc_html($b->staff_name ?: '—'); ?></td>
                        <td><strong><?php echo esc_html($b->client_firstname . ' ' . $b->client_lastname); ?></strong><br><small><?php echo esc_html($b->client_email); ?></small></td>
                        <td><?php echo esc_html(ucfirst($b->status)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ──────── PAGE : Réglages (Google + général) ──────── */
function kc_booking_admin_settings() {
    if (isset($_POST['kc_booking_settings_save']) && check_admin_referer('kc_booking_settings')) {
        update_option('kc_booking_admin_email', sanitize_email($_POST['admin_email']));
        update_option('kc_booking_page_url', esc_url_raw($_POST['page_url']));
        $sa_input = trim(wp_unslash($_POST['google_service_account']));
        update_option('kc_booking_google_service_account', $sa_input === '' ? '' : base64_encode($sa_input));
        echo '<div class="notice notice-success is-dismissible"><p>Réglages enregistrés.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>⚙️ Réglages</h1>
        <form method="post">
            <?php wp_nonce_field('kc_booking_settings'); ?>
            <input type="hidden" name="kc_booking_settings_save" value="1">
            <h2>Général</h2>
            <table class="form-table">
                <tr><th><label>Email administrateur</label></th><td><input type="email" name="admin_email" value="<?php echo esc_attr(get_option('kc_booking_admin_email', 'contact@kayliclinn.fr')); ?>" class="regular-text"></td></tr>
                <tr><th><label>URL page réservation</label></th><td><input type="url" name="page_url" value="<?php echo esc_attr(get_option('kc_booking_page_url', '')); ?>" class="regular-text" placeholder="https://kayliclinn.fr/devis/"></td></tr>
            </table>
            <h2>🔐 Google Calendar API</h2>
            <table class="form-table">
                <tr><th><label>Service Account JSON</label></th><td><textarea name="google_service_account" rows="8" class="large-text code" placeholder='{"type": "service_account", ...}'><?php
                    $sa_stored = get_option('kc_booking_google_service_account', '');
                    $sa_dec = base64_decode($sa_stored, true);
                    echo esc_textarea(($sa_dec !== false && json_decode($sa_dec, true) !== null) ? $sa_dec : $sa_stored);
                ?></textarea><p class="description">Collez le contenu intégral du fichier JSON depuis Google Cloud Console. Stocké en base64 pour éviter toute corruption WordPress.</p></td></tr>
            </table>
            <p><button type="submit" class="button button-primary button-large">💾 Enregistrer</button></p>
        </form>
    </div>
    <?php
}

/* ════════════════════════════════════════════════════════════════════════
                  PHASE 2 — INTÉGRATION GOOGLE CALENDAR API
   ════════════════════════════════════════════════════════════════════════ */

function kc_b64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

add_filter('pre_update_option_kc_booking_google_service_account', function($value, $old_value) {
    return wp_unslash($value);
}, 10, 2);

function kc_get_service_account() {
    $raw = get_option('kc_booking_google_service_account');
    if (empty($raw)) return null;
    $maybe = base64_decode($raw, true);
    if ($maybe !== false) {
        $sa = json_decode($maybe, true);
        if (is_array($sa) && isset($sa['client_email'], $sa['private_key'])) return $sa;
    }
    $sa = json_decode($raw, true);
    if (!is_array($sa)) $sa = json_decode(wp_unslash($raw), true);
    return is_array($sa) ? $sa : null;
}

function kc_google_generate_jwt() {
    $sa = kc_get_service_account();
    if (!$sa || !isset($sa['client_email'], $sa['private_key'])) {
        return new WP_Error('invalid_sa', 'JSON Service Account invalide ou non configuré. Collez-le depuis Réglages.');
    }
    $private_key = $sa['private_key'];
    $private_key = preg_replace('/\\\\+n/', "\n", $private_key);
    $private_key = str_replace(["\r\n", "\r"], "\n", $private_key);
    $private_key = trim($private_key);
    if (preg_match('/-----BEGIN ([A-Z0-9 ]+)-----(.*?)-----END \\1-----/s', $private_key, $m)) {
        $type = trim($m[1]);
        $body = preg_replace('/[^A-Za-z0-9+\/=]/', '', $m[2]);
        $private_key = "-----BEGIN {$type}-----\n" . chunk_split($body, 64, "\n") . "-----END {$type}-----\n";
    }

    $now = time();
    $header  = ['alg' => 'RS256', 'typ' => 'JWT'];
    $payload = ['iss' => $sa['client_email'], 'scope' => 'https://www.googleapis.com/auth/calendar', 'aud' => 'https://oauth2.googleapis.com/token', 'exp' => $now + 3600, 'iat' => $now];
    $b64_header  = kc_b64url_encode(wp_json_encode($header));
    $b64_payload = kc_b64url_encode(wp_json_encode($payload));
    $signing_input = $b64_header . '.' . $b64_payload;
    $signature = '';
    $ok = openssl_sign($signing_input, $signature, $private_key, 'sha256');
    if (!$ok) {
        $ssl_err = '';
        while ($e = openssl_error_string()) { $ssl_err = $e; }
        return new WP_Error('sign_failed', 'Échec de la signature JWT (' . ($ssl_err ?: 'clé privée illisible') . '). Re-collez le JSON Service Account complet dans Réglages.');
    }
    return $signing_input . '.' . kc_b64url_encode($signature);
}

function kc_google_get_access_token($force_refresh = false) {
    if (!$force_refresh) {
        $cached = get_transient('kc_google_access_token');
        if ($cached) return $cached;
    }
    $jwt = kc_google_generate_jwt();
    if (is_wp_error($jwt)) return $jwt;
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 15,
        'body' => ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt],
    ]);
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200 || !isset($body['access_token'])) {
        $err = $body['error_description'] ?? $body['error'] ?? 'Erreur OAuth inconnue';
        return new WP_Error('oauth_failed', 'OAuth Google : ' . $err . ' (code ' . $code . ')');
    }
    set_transient('kc_google_access_token', $body['access_token'], 3500);
    return $body['access_token'];
}

function kc_google_get_busy($calendar_id, $start_iso, $end_iso) {
    $token = kc_google_get_access_token();
    if (is_wp_error($token)) return $token;
    $response = wp_remote_post('https://www.googleapis.com/calendar/v3/freeBusy', [
        'timeout' => 15,
        'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
        'body' => wp_json_encode(['timeMin' => $start_iso, 'timeMax' => $end_iso, 'items' => [['id' => $calendar_id]], 'timeZone' => 'Europe/Paris']),
    ]);
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200) {
        $err = $body['error']['message'] ?? 'Erreur freeBusy';
        return new WP_Error('freebusy_failed', $err . ' (code ' . $code . ')');
    }
    if (isset($body['calendars'][$calendar_id]['errors'])) {
        $errors = $body['calendars'][$calendar_id]['errors'];
        $reason = $errors[0]['reason'] ?? 'unknown';
        $hint = ($reason === 'notFound') ? ' → vérifier que l\'agenda est bien partagé avec le Service Account avec droits "Modifier des événements".' : '';
        return new WP_Error('calendar_error', 'Agenda "' . $calendar_id . '" : ' . $reason . '.' . $hint);
    }
    return $body['calendars'][$calendar_id]['busy'] ?? [];
}

function kc_google_create_event($calendar_id, $event, $send_updates = true) {
    $token = kc_google_get_access_token();
    if (is_wp_error($token)) return $token;
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendar_id) . '/events';
    if ($send_updates) $url .= '?sendUpdates=all';
    $response = wp_remote_post($url, [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
        'body' => wp_json_encode($event),
    ]);
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200) {
        $err = $body['error']['message'] ?? 'Erreur création événement';
        return new WP_Error('event_failed', $err . ' (code ' . $code . ')');
    }
    return $body;
}

function kc_google_delete_event($calendar_id, $event_id) {
    $token = kc_google_get_access_token();
    if (is_wp_error($token)) return $token;
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendar_id) . '/events/' . rawurlencode($event_id);
    $response = wp_remote_request($url, ['method' => 'DELETE', 'timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . $token]]);
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 204 && $code !== 200) return new WP_Error('delete_failed', 'Suppression échouée (code ' . $code . ')');
    return true;
}

/* ──────── PAGE ADMIN : Diagnostic Google ──────── */
add_action('admin_menu', 'kc_booking_google_admin_menu', 11);
function kc_booking_google_admin_menu() {
    add_submenu_page('kc-booking-dashboard', 'Diagnostic Google', '🧪 Diagnostic Google', 'manage_options', 'kc-booking-google-diag', 'kc_booking_admin_google_diag');
}

function kc_booking_admin_google_diag() {
    global $wpdb;
    $staff = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kc_booking_staff WHERE is_active = 1 ORDER BY position ASC, name ASC");
    $sa_configured = !empty(get_option('kc_booking_google_service_account'));
    ?>
    <div class="wrap">
        <h1>🧪 Diagnostic Google Calendar</h1>
        <?php if (!$sa_configured) : ?>
            <div class="notice notice-error" style="margin:20px 0;padding:14px 20px;"><strong>❌ Service Account JSON non configuré.</strong><br>Allez sur <a href="?page=kc-booking-settings">Réglages</a> et collez le JSON.</div>
            <?php return; ?>
        <?php endif; ?>
        <h2>Test 1 — Authentification Google</h2>
        <button type="button" class="button button-primary" id="kc-test-auth">🔐 Tester l'authentification</button>
        <div id="kc-test-auth-result" style="margin-top:12px;"></div>
        <h2 style="margin-top:36px;">Test 2 — Lecture du calendrier</h2>
        <?php if (empty($staff)) : ?><p><em>Aucun membre actif.</em></p><?php else : ?>
            <div style="display:flex;gap:12px;align-items:center;">
                <label>Membre : <select id="kc-test-staff" style="min-width:240px;margin-left:8px;">
                    <?php foreach ($staff as $s) : ?><option value="<?php echo (int) $s->id; ?>"><?php echo esc_html($s->name); ?> — <?php echo esc_html($s->google_calendar_id ?: $s->email); ?></option><?php endforeach; ?>
                </select></label>
                <button type="button" class="button button-primary" id="kc-test-busy">📅 Lire le calendrier (14j)</button>
            </div>
            <div id="kc-test-busy-result" style="margin-top:12px;"></div>
            <h2 style="margin-top:36px;">Test 3 — Écriture (create & cleanup)</h2>
            <button type="button" class="button" id="kc-test-write">✍️ Test d'écriture</button>
            <div id="kc-test-write-result" style="margin-top:12px;"></div>
        <?php endif; ?>
        <h2 style="margin-top:36px;">Infos</h2>
        <table class="form-table">
            <tr><th>Service Account email</th><td><code><?php $sa = kc_get_service_account(); echo esc_html($sa['client_email'] ?? '(non lisible)'); ?></code></td></tr>
            <tr><th>Project ID</th><td><code><?php echo esc_html($sa['project_id'] ?? '(non lisible)'); ?></code></td></tr>
            <tr><th>Token OAuth en cache</th><td><?php echo get_transient('kc_google_access_token') ? '✓ Présent' : '— Non'; ?></td></tr>
        </table>
    </div>
    <script>
    (function() {
        const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('kc_google_diag')); ?>';
        function showResult(elId, success, msg) {
            const el = document.getElementById(elId);
            const color = success ? '#065F46' : '#991B1B';
            const bg = success ? '#D1FAE5' : '#FEE2E2';
            el.innerHTML = '<div style="color:' + color + ';background:' + bg + ';padding:12px 16px;border-radius:6px;font-size:14px;">' + msg + '</div>';
        }
        function setLoading(btn) { btn.disabled = true; btn.dataset.originalText = btn.textContent; btn.textContent = '⏳ Test…'; }
        function clearLoading(btn) { btn.disabled = false; btn.textContent = btn.dataset.originalText; }
        function call(action, params, resultId, btnEl) {
            setLoading(btnEl);
            const fd = new FormData();
            fd.append('action', action); fd.append('nonce', nonce);
            Object.entries(params || {}).forEach(([k, v]) => fd.append(k, v));
            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => { clearLoading(btnEl); showResult(resultId, data.success, (data.data && data.data.message) || (data.success ? '✅ OK' : '❌ Erreur')); })
                .catch(err => { clearLoading(btnEl); showResult(resultId, false, '❌ ' + err.message); });
        }
        document.getElementById('kc-test-auth').addEventListener('click', function() { call('kc_google_test_auth', {}, 'kc-test-auth-result', this); });
        const busyBtn = document.getElementById('kc-test-busy');
        if (busyBtn) busyBtn.addEventListener('click', function() { call('kc_google_test_busy', { staff_id: document.getElementById('kc-test-staff').value }, 'kc-test-busy-result', this); });
        const writeBtn = document.getElementById('kc-test-write');
        if (writeBtn) writeBtn.addEventListener('click', function() {
            if (!confirm('Cela va créer un événement test puis le supprimer. Continuer ?')) return;
            call('kc_google_test_write', { staff_id: document.getElementById('kc-test-staff').value }, 'kc-test-write-result', this);
        });
    })();
    </script>
    <?php
}

add_action('wp_ajax_kc_google_test_auth', 'kc_ajax_test_auth');
function kc_ajax_test_auth() {
    check_ajax_referer('kc_google_diag', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Permission refusée', 403);
    $token = kc_google_get_access_token(true);
    if (is_wp_error($token)) wp_send_json_error(['message' => '❌ <strong>Échec :</strong> ' . esc_html($token->get_error_message())]);
    wp_send_json_success(['message' => '✅ <strong>Authentification OK !</strong> Token OAuth obtenu (' . strlen($token) . ' caractères).']);
}

add_action('wp_ajax_kc_google_test_busy', 'kc_ajax_test_busy');
function kc_ajax_test_busy() {
    check_ajax_referer('kc_google_diag', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Permission refusée', 403);
    global $wpdb;
    $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_staff WHERE id = %d", (int) ($_POST['staff_id'] ?? 0)));
    if (!$staff) wp_send_json_error(['message' => 'Membre introuvable.']);
    $calendar_id = $staff->google_calendar_id ?: $staff->email;
    if (!$calendar_id) wp_send_json_error(['message' => 'Aucun email configuré pour ' . esc_html($staff->name)]);
    $tz = new DateTimeZone('Europe/Paris');
    $busy = kc_google_get_busy($calendar_id, (new DateTime('now', $tz))->format('c'), (new DateTime('+14 days', $tz))->format('c'));
    if (is_wp_error($busy)) wp_send_json_error(['message' => '❌ ' . esc_html($busy->get_error_message())]);
    $count = count($busy);
    $list = '';
    if ($count > 0) {
        $list = '<br><br><strong>5 prochains :</strong><ul style="margin-top:6px;">';
        foreach (array_slice($busy, 0, 5) as $slot) {
            $s = (new DateTime($slot['start']))->setTimezone($tz);
            $e = (new DateTime($slot['end']))->setTimezone($tz);
            $list .= '<li>' . esc_html($s->format('D d/m H:i')) . ' → ' . esc_html($e->format('H:i')) . '</li>';
        }
        $list .= '</ul>';
    }
    wp_send_json_success(['message' => sprintf('✅ <strong>Lecture OK !</strong> Calendrier <code>%s</code>. <strong>%d créneau(x) occupé(s)</strong> sur 14j.%s', esc_html($calendar_id), $count, $list)]);
}

add_action('wp_ajax_kc_google_test_write', 'kc_ajax_test_write');
function kc_ajax_test_write() {
    check_ajax_referer('kc_google_diag', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Permission refusée', 403);
    global $wpdb;
    $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_staff WHERE id = %d", (int) ($_POST['staff_id'] ?? 0)));
    if (!$staff) wp_send_json_error(['message' => 'Membre introuvable.']);
    $calendar_id = $staff->google_calendar_id ?: $staff->email;
    $tz = new DateTimeZone('Europe/Paris');
    $start = new DateTime('tomorrow 09:00', $tz);
    $end = new DateTime('tomorrow 09:30', $tz);
    $event = ['summary' => '[TEST Kayli Clinn] ' . date('H:i:s'), 'description' => 'Événement de test.', 'start' => ['dateTime' => $start->format('c'), 'timeZone' => 'Europe/Paris'], 'end' => ['dateTime' => $end->format('c'), 'timeZone' => 'Europe/Paris'], 'reminders' => ['useDefault' => false]];
    $created = kc_google_create_event($calendar_id, $event, false);
    if (is_wp_error($created)) wp_send_json_error(['message' => '❌ ' . esc_html($created->get_error_message())]);
    if (!empty($created['id'])) kc_google_delete_event($calendar_id, $created['id']);
    wp_send_json_success(['message' => '✅ <strong>Écriture OK !</strong> Événement créé puis supprimé.']);
}

/* ════════════════════════════════════════════════════════════════════════
                PHASE 1.2 — MIGRATION : 10 prestations + variants
   ════════════════════════════════════════════════════════════════════════ */

add_action('plugins_loaded', 'kc_booking_run_phase_1_2_migration', 20);
function kc_booking_run_phase_1_2_migration() {
    if (get_option(KC_BOOKING_PHASE_1_2_FLAG)) return;
    global $wpdb;
    $types_table = $wpdb->prefix . 'kc_booking_types';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $types_table)) !== $types_table) return;
    kc_booking_phase_1_2_alter_tables();
    kc_booking_phase_1_2_seed_prestations();
    kc_booking_phase_1_2_seed_variants();
    kc_booking_phase_1_2_deactivate_legacy_types();
    update_option(KC_BOOKING_PHASE_1_2_FLAG, 1);
    set_transient('kc_booking_phase_1_2_migrated', 1, 60);
}

function kc_booking_phase_1_2_alter_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$wpdb->prefix}kc_booking_types (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        icon VARCHAR(50) DEFAULT 'calendar',
        color VARCHAR(7) DEFAULT '#0FA7A5',
        duration_minutes INT(11) NOT NULL DEFAULT 60,
        buffer_minutes INT(11) NOT NULL DEFAULT 15,
        min_advance_hours INT(11) NOT NULL DEFAULT 24,
        max_advance_days INT(11) NOT NULL DEFAULT 60,
        price DECIMAL(10,2) DEFAULT NULL,
        is_free TINYINT(1) NOT NULL DEFAULT 1,
        allow_team_selection TINYINT(1) NOT NULL DEFAULT 0,
        require_address TINYINT(1) NOT NULL DEFAULT 1,
        confirmation_email_text TEXT DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        cta_type VARCHAR(20) NOT NULL DEFAULT 'calendar_free',
        external_url VARCHAR(255) DEFAULT NULL,
        position INT(11) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY is_active (is_active)
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}kc_bookings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type_id BIGINT(20) UNSIGNED NOT NULL,
        variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
        staff_id BIGINT(20) UNSIGNED DEFAULT NULL,
        client_firstname VARCHAR(50) NOT NULL,
        client_lastname VARCHAR(50) NOT NULL,
        client_email VARCHAR(100) NOT NULL,
        client_phone VARCHAR(20) NOT NULL,
        client_address VARCHAR(255) DEFAULT NULL,
        client_message TEXT DEFAULT NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        timezone VARCHAR(50) DEFAULT 'Europe/Paris',
        total_amount_ttc DECIMAL(10,2) DEFAULT NULL,
        deposit_amount_ttc DECIMAL(10,2) DEFAULT NULL,
        payment_mode VARCHAR(20) DEFAULT 'none',
        metadata_json LONGTEXT DEFAULT NULL,
        google_event_id VARCHAR(255) DEFAULT NULL,
        cancellation_token VARCHAR(64) DEFAULT NULL,
        status ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
        cancelled_at DATETIME DEFAULT NULL,
        cancellation_reason TEXT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY status (status),
        KEY start_datetime (start_datetime),
        KEY type_id (type_id),
        KEY variant_id (variant_id),
        KEY staff_id (staff_id),
        UNIQUE KEY cancellation_token (cancellation_token)
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}kc_booking_type_variants (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type_id BIGINT(20) UNSIGNED NOT NULL,
        slug VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        duration_minutes INT(11) NOT NULL DEFAULT 60,
        price_indicative DECIMAL(10,2) DEFAULT NULL,
        position INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY  (id),
        UNIQUE KEY type_slug (type_id, slug),
        KEY type_id (type_id)
    ) $charset;");
}

function kc_booking_phase_1_2_seed_prestations() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_types';
    $prestations = [
        ['name'=>'Entretien de bureaux',       'slug'=>'bureaux',         'icon'=>'briefcase','color'=>'#0FA7A5','duration_minutes'=>60, 'cta_type'=>'calendar_free','description'=>'Cabinets, PME, sièges sociaux. Contrats récurrents.',      'position'=>10],
        ['name'=>'Parties communes immeubles', 'slug'=>'parties-communes','icon'=>'home',     'color'=>'#081D3A','duration_minutes'=>60, 'cta_type'=>'calendar_free','description'=>'Immeubles résidentiels, syndics, copropriétés.',           'position'=>20],
        ['name'=>'Commerces & Retail',         'slug'=>'commerces',       'icon'=>'briefcase','color'=>'#B65B35','duration_minutes'=>60, 'cta_type'=>'calendar_free','description'=>'Boutiques, restaurants, hôtels, concept stores.',          'position'=>30],
        ['name'=>'Établissements sensibles',   'slug'=>'sensibles',       'icon'=>'star',     'color'=>'#A88959','duration_minutes'=>60, 'cta_type'=>'calendar_free','description'=>'Cabinets médicaux, dentaires, pharmacies, crèches.',       'position'=>40],
        ['name'=>'Nettoyage fin de chantier',     'slug'=>'fin-chantier','icon'=>'clipboard','color'=>'#B65B35','duration_minutes'=>180,'cta_type'=>'calendar_free','description'=>'Post-travaux, VEFA, rénovations.',                                'position'=>50],
        ['name'=>'Nettoyage après déménagement',  'slug'=>'demenagement','icon'=>'home',     'color'=>'#B65B35','duration_minutes'=>300,'cta_type'=>'calendar_paid','description'=>'État des lieux, sortie de location, remise en état.',           'position'=>60, 'is_free'=>0],
        ['name'=>'Remise en état logement',       'slug'=>'remise-etat', 'icon'=>'clipboard','color'=>'#A88959','duration_minutes'=>240,'cta_type'=>'calendar_free','description'=>'Logement ou bureaux fortement encrassés.',                       'position'=>70],
        ['name'=>'Décapage / Cristallisation',    'slug'=>'decapage',    'icon'=>'star',     'color'=>'#0B8483','duration_minutes'=>240,'cta_type'=>'calendar_free','description'=>'Décapage sols, cristallisation marbre, lustrage.',                'position'=>80],
        ['name'=>'Nettoyage vitres',              'slug'=>'vitres',      'icon'=>'calendar', 'color'=>'#0FA7A5','duration_minutes'=>60, 'cta_type'=>'email',        'description'=>'Vitrines, baies, façades. Devis envoyé par email.',               'position'=>90],
        ['name'=>'Turnover Airbnb',               'slug'=>'airbnb',      'icon'=>'home',     'color'=>'#0FA7A5','duration_minutes'=>150,'cta_type'=>'calendar_paid','description'=>'Rotation entre deux locations courte durée. Ménage, linge, vérifs.','position'=>100,'is_free'=>0],
    ];
    foreach ($prestations as $p) {
        $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $p['slug']));
        if ($existing_id) {
            $update = $p; unset($update['slug']); $update['is_active'] = 1;
            $wpdb->update($table, $update, ['id' => $existing_id]);
        } else {
            $wpdb->insert($table, $p);
        }
    }
}

function kc_booking_phase_1_2_seed_variants() {
    global $wpdb;
    $variants_table = $wpdb->prefix . 'kc_booking_type_variants';
    $types_table = $wpdb->prefix . 'kc_booking_types';
    $variants = [
        ['type_slug'=>'airbnb',       'slug'=>'studio',      'name'=>'Studio / T1',         'duration_minutes'=>90,  'price_indicative'=>55,  'position'=>1],
        ['type_slug'=>'airbnb',       'slug'=>'appartement', 'name'=>'Appartement T2 à T4', 'duration_minutes'=>150, 'price_indicative'=>80,  'position'=>2],
        ['type_slug'=>'airbnb',       'slug'=>'maison',      'name'=>'Maison T5+',          'duration_minutes'=>240, 'price_indicative'=>120, 'position'=>3],
        ['type_slug'=>'demenagement', 'slug'=>'studio',      'name'=>'Studio / T1',         'duration_minutes'=>180, 'price_indicative'=>210, 'position'=>1],
        ['type_slug'=>'demenagement', 'slug'=>'appartement', 'name'=>'Appartement T2 à T5', 'duration_minutes'=>300, 'price_indicative'=>330, 'position'=>2],
        ['type_slug'=>'demenagement', 'slug'=>'maison',      'name'=>'Maison',              'duration_minutes'=>480, 'price_indicative'=>550, 'position'=>3],
    ];
    foreach ($variants as $v) {
        $type_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $types_table WHERE slug = %s", $v['type_slug']));
        if (!$type_id) continue;
        unset($v['type_slug']); $v['type_id'] = $type_id;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $variants_table WHERE type_id = %d AND slug = %s", $type_id, $v['slug']));
        if (!$exists) $wpdb->insert($variants_table, $v);
    }
}

function kc_booking_phase_1_2_deactivate_legacy_types() {
    global $wpdb;
    $legacy = ['rotation-airbnb','nettoyage-fin-de-bail','entretien-regulier-pro','visite-devis-gratuit'];
    $placeholders = implode(',', array_fill(0, count($legacy), '%s'));
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}kc_booking_types SET is_active = 0 WHERE slug IN ($placeholders)", ...$legacy));
}

add_action('admin_notices', 'kc_booking_phase_1_2_admin_notice');
function kc_booking_phase_1_2_admin_notice() {
    if (!get_transient('kc_booking_phase_1_2_migrated')) return;
    delete_transient('kc_booking_phase_1_2_migrated');
    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Migration Phase 1.2 effectuée.</strong> 10 prestations + variantes Airbnb/Déménagement seedées. <a href="?page=kc-booking-types">Vérifier →</a> · <a href="?page=kc-booking-rest-diag">Tester l\'API →</a></p></div>';
}

/* ════════════════════════════════════════════════════════════════════════
   CATALOGUE v2 (grille tarifaire 11/06/2026) — types payants additionnels
   ────────────────────────────────────────────────────────────────────────
   Grand ménage et Vitrerie résidentielle sont désormais vendus en ligne à
   prix fixe (Flux B). Cet amorçage idempotent crée les deux types s'ils
   manquent (durées par défaut À AJUSTER dans l'admin selon le terrain).
   ════════════════════════════════════════════════════════════════════════ */
add_action('admin_init', 'kc_booking_seed_catalogue_v2');
function kc_booking_seed_catalogue_v2() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_booking_types';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return;

    $types = [
        ['name' => 'Grand ménage (meublé occupé)', 'slug' => 'grand-menage', 'icon' => 'star',
         'color' => '#0B8483', 'duration_minutes' => 240, 'cta_type' => 'calendar_paid', 'is_free' => 0,
         'require_address' => 1, 'position' => 65,
         'description' => 'Grand ménage ponctuel d\'un logement meublé occupé. Durée par défaut à ajuster.'],
        ['name' => 'Vitrerie résidentielle',       'slug' => 'vitrerie',     'icon' => 'calendar',
         'color' => '#0FA7A5', 'duration_minutes' => 120, 'cta_type' => 'calendar_paid', 'is_free' => 0,
         'require_address' => 1, 'position' => 95,
         'description' => 'Vitres plain-pied / intérieures accessibles, forfait par typologie. Durée par défaut à ajuster.'],
    ];
    foreach ($types as $t) {
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $t['slug']));
        if ($existing) {
            $wpdb->update($table, ['is_active' => 1, 'cta_type' => $t['cta_type'], 'is_free' => 0], ['id' => (int) $existing]);
        } else {
            $t['is_active'] = 1;
            $wpdb->insert($table, $t);
        }
    }
}

/* ════════════════════════════════════════════════════════════════════════
        PHASE 3 — API PUBLIQUE · RÉSERVATIONS · E-MAILS · PAIEMENTS
   ════════════════════════════════════════════════════════════════════════ */

if (!defined('KC_BOOKING_TZ'))        define('KC_BOOKING_TZ', 'Europe/Paris');
if (!defined('KC_BOOKING_SLOT_STEP')) define('KC_BOOKING_SLOT_STEP', 30);
if (!defined('KC_BOOKING_MAX_RANGE')) define('KC_BOOKING_MAX_RANGE', 31);

function kc_opt($key, $default = '') { return get_option('kc_booking_' . $key, $default); }
function kc_tz()  { static $tz; if (!$tz) $tz = new DateTimeZone(KC_BOOKING_TZ); return $tz; }
function kc_google_ready() { return !empty(get_option('kc_booking_google_service_account')); }
function kc_money($v) { return number_format((float) $v, 2, ',', ' ') . ' €'; }

function kc_fmt_dt($mysql_dt) {
    $d = new DateTime($mysql_dt, kc_tz());
    $jours = ['Mon'=>'lundi','Tue'=>'mardi','Wed'=>'mercredi','Thu'=>'jeudi','Fri'=>'vendredi','Sat'=>'samedi','Sun'=>'dimanche'];
    $mois  = ['01'=>'janvier','02'=>'février','03'=>'mars','04'=>'avril','05'=>'mai','06'=>'juin','07'=>'juillet','08'=>'août','09'=>'septembre','10'=>'octobre','11'=>'novembre','12'=>'décembre'];
    return $jours[$d->format('D')] . ' ' . (int) $d->format('d') . ' ' . $mois[$d->format('m')] . ' ' . $d->format('Y') . ' à ' . $d->format('H:i');
}

function kc_eligible_staff($type_id) {
    global $wpdb;
    $linked = $wpdb->get_col($wpdb->prepare("SELECT staff_id FROM {$wpdb->prefix}kc_booking_type_staff WHERE type_id = %d", $type_id));
    if (!empty($linked)) {
        $in = implode(',', array_map('intval', $linked));
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kc_booking_staff WHERE id IN ($in) AND is_active = 1 ORDER BY position ASC, id ASC");
    }
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kc_booking_staff WHERE is_active = 1 ORDER BY position ASC, id ASC");
}

function kc_staff_working_window($staff, DateTime $day) {
    $hours = $staff->working_hours_json ? json_decode($staff->working_hours_json, true) : [];
    if (!is_array($hours)) return null;
    $keys = ['mon','tue','wed','thu','fri','sat','sun'];
    $h = $hours[$keys[(int) $day->format('N') - 1]] ?? null;
    if (!$h || empty($h['active'])) return null;
    list($sh, $sm) = array_map('intval', array_pad(explode(':', $h['start'] ?? '09:00'), 2, 0));
    list($eh, $em) = array_map('intval', array_pad(explode(':', $h['end']   ?? '18:00'), 2, 0));
    return [(clone $day)->setTime($sh, $sm, 0), (clone $day)->setTime($eh, $em, 0)];
}

function kc_staff_works_at($staff, DateTime $start, DateTime $end) {
    $w = kc_staff_working_window($staff, $start);
    return $w ? ($start >= $w[0] && $end <= $w[1]) : false;
}

function kc_slot_free_for_staff($staff, DateTime $start, DateTime $end, $buffer) {
    global $wpdb;
    $ps = (clone $start)->modify('-' . $buffer . ' minutes');
    $pe = (clone $end)->modify('+' . $buffer . ' minutes');
    $cnt = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}kc_bookings
         WHERE staff_id = %d AND status IN ('pending','confirmed')
         AND end_datetime > %s AND start_datetime < %s",
        (int) $staff->id, $ps->format('Y-m-d H:i:s'), $pe->format('Y-m-d H:i:s')));
    if ($cnt > 0) return false;
    if (kc_google_ready()) {
        $cid = $staff->google_calendar_id ?: $staff->email;
        if ($cid) {
            $busy = kc_google_get_busy($cid, $ps->format('c'), $pe->format('c'));
            if (is_wp_error($busy)) return $busy;
            foreach ((array) $busy as $b) {
                $bs = (new DateTime($b['start']))->modify('-' . $buffer . ' minutes');
                $be = (new DateTime($b['end']))->modify('+' . $buffer . ' minutes');
                if ($start < $be && $end > $bs) return false;
            }
        }
    }
    return true;
}

function kc_google_get_busy_multi(array $calendar_ids, $start_iso, $end_iso) {
    $calendar_ids = array_values(array_unique(array_filter($calendar_ids)));
    if (empty($calendar_ids)) return ['busy' => [], 'errors' => []];
    $token = kc_google_get_access_token();
    if (is_wp_error($token)) return $token;
    $items = array_map(function ($id) { return ['id' => $id]; }, $calendar_ids);
    $resp = wp_remote_post('https://www.googleapis.com/calendar/v3/freeBusy', [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
        'body'    => wp_json_encode(['timeMin' => $start_iso, 'timeMax' => $end_iso, 'timeZone' => KC_BOOKING_TZ, 'items' => $items]),
    ]);
    if (is_wp_error($resp)) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code !== 200) return new WP_Error('freebusy_failed', ($body['error']['message'] ?? 'Erreur freeBusy') . ' (code ' . $code . ')');
    $busy = []; $errors = [];
    foreach ($calendar_ids as $cid) {
        $cal = $body['calendars'][$cid] ?? null;
        if ($cal === null)          { $errors[$cid] = 'not_returned'; continue; }
        if (!empty($cal['errors'])) { $errors[$cid] = $cal['errors'][0]['reason'] ?? 'unknown'; continue; }
        $busy[$cid] = $cal['busy'] ?? [];
    }
    return ['busy' => $busy, 'errors' => $errors];
}

function kc_slot_overlaps_busy(DateTime $start, DateTime $end, array $busy) {
    foreach ($busy as $b) { if ($start < $b[1] && $end > $b[0]) return true; }
    return false;
}

/* ──────── E-mails ──────── */
function kc_send_html_email($to, $subject, $html) {
    $from = kc_opt('admin_email', 'contact@kayliclinn.fr');
    $name = kc_opt('company_name', 'Kayli Clinn');
    return wp_mail($to, $subject, $html, ['Content-Type: text/html; charset=UTF-8', 'From: ' . $name . ' <' . $from . '>']);
}

function kc_email_wrap($title, $inner) {
    $navy = '#081D3A'; $company = esc_html(kc_opt('company_name', 'Kayli Clinn'));
    $foot = $company;
    if (kc_opt('company_phone'))   $foot .= ' · ' . esc_html(kc_opt('company_phone'));
    if (kc_opt('company_address')) $foot .= '<br>' . esc_html(kc_opt('company_address'));
    return '<div style="background:#f4f6f8;padding:28px 12px;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">'
        . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(8,29,58,.08);">'
        . '<div style="background:' . $navy . ';padding:22px 28px;"><span style="color:#fff;font-size:18px;font-weight:700;letter-spacing:.4px;">' . $company . '</span></div>'
        . '<div style="padding:30px 28px 6px;"><h1 style="margin:0 0 14px;font-size:20px;color:' . $navy . ';">' . esc_html($title) . '</h1>'
        . '<div style="font-size:15px;line-height:1.6;color:#374151;">' . $inner . '</div></div>'
        . '<div style="padding:18px 28px 26px;color:#9ca3af;font-size:12px;line-height:1.5;border-top:1px solid #eef1f4;margin-top:18px;">' . $foot . '</div>'
        . '</div></div>';
}

function kc_email_button($label, $url) {
    return '<p style="margin:22px 0;"><a href="' . esc_url($url) . '" style="background:#0FA7A5;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;font-size:14px;display:inline-block;">' . esc_html($label) . '</a></p>';
}

function kc_booking_meta($b, $key, $default = null) {
    $m = $b->metadata_json ? (json_decode($b->metadata_json, true) ?: []) : [];
    return $m[$key] ?? $default;
}

function kc_booking_manage_url($b) {
    $base = kc_opt('page_url'); if (!$base) $base = home_url('/');
    return add_query_arg('resa', $b->cancellation_token, $base);
}

function kc_email_details_rows($b) {
    $rows = [['Prestation', esc_html($b->type_name) . ($b->variant_id ? ' — ' . esc_html(kc_booking_meta($b, 'variant_name')) : '')]];
    $rows[] = ['Date', esc_html(kc_fmt_dt($b->start_datetime))];
    $rows[] = ['Durée estimée', (int) ((strtotime($b->end_datetime) - strtotime($b->start_datetime)) / 60) . ' min'];
    if ($b->staff_name)      $rows[] = ['Intervenant', esc_html($b->staff_name)];
    if ($b->client_address)  $rows[] = ['Adresse', esc_html($b->client_address)];
    if ($b->total_amount_ttc   !== null) $rows[] = ['Montant', kc_money($b->total_amount_ttc) . ' TTC'];
    if ($b->deposit_amount_ttc !== null) $rows[] = ['Acompte', kc_money($b->deposit_amount_ttc)];
    $html = '<table style="width:100%;border-collapse:collapse;margin:6px 0 16px;">';
    foreach ($rows as $r) {
        $html .= '<tr><td style="padding:7px 0;color:#6b7280;font-size:13px;width:42%;vertical-align:top;">' . $r[0] . '</td>'
              .  '<td style="padding:7px 0;color:#111827;font-size:14px;font-weight:600;">' . $r[1] . '</td></tr>';
    }
    return $html . '</table>';
}

function kc_email_client_confirmed($b) {
    $inner = '<p>Bonjour ' . esc_html($b->client_firstname) . ',</p>'
           . '<p>Votre rendez-vous est confirmé. Voici le récapitulatif :</p>'
           . kc_email_details_rows($b)
           . '<p>Un imprévu ? Vous pouvez gérer ou annuler votre réservation depuis le lien ci-dessous.</p>'
           . kc_email_button('Gérer ma réservation', kc_booking_manage_url($b));
    kc_send_html_email($b->client_email, '[' . kc_opt('company_name', 'Kayli Clinn') . '] Réservation confirmée', kc_email_wrap('Votre réservation est confirmée', $inner));
}

function kc_email_client_pending($b, $payment_url = null) {
    if ($payment_url) {
        $inner = '<p>Bonjour ' . esc_html($b->client_firstname) . ',</p>'
               . '<p>Votre créneau est réservé. Pour le confirmer définitivement, il vous reste à régler l\'acompte :</p>'
               . kc_email_details_rows($b)
               . kc_email_button('Régler l\'acompte (' . kc_money($b->deposit_amount_ttc) . ')', $payment_url)
               . '<p style="font-size:13px;color:#6b7280;">Sans règlement, le créneau pourra être libéré.</p>';
        $subj = 'Finalisez votre réservation';
    } else {
        $inner = '<p>Bonjour ' . esc_html($b->client_firstname) . ',</p>'
               . '<p>Nous avons bien reçu votre demande. Nous revenons vers vous très vite pour la confirmer.</p>'
               . kc_email_details_rows($b);
        $subj = 'Demande de réservation reçue';
    }
    kc_send_html_email($b->client_email, '[' . kc_opt('company_name', 'Kayli Clinn') . '] ' . $subj, kc_email_wrap($subj, $inner));
}

function kc_email_client_cancelled($b) {
    $inner = '<p>Bonjour ' . esc_html($b->client_firstname) . ',</p>'
           . '<p>Votre réservation a bien été annulée :</p>'
           . kc_email_details_rows($b)
           . '<p>À très bientôt.</p>';
    kc_send_html_email($b->client_email, '[' . kc_opt('company_name', 'Kayli Clinn') . '] Réservation annulée', kc_email_wrap('Réservation annulée', $inner));
}

function kc_email_admin($b, $title, $extra = '') {
    $to = kc_opt('admin_email', 'contact@kayliclinn.fr'); if (!$to) return;
    $inner = $extra
        . kc_email_details_rows($b)
        . '<table style="width:100%;border-collapse:collapse;">'
        . '<tr><td style="padding:5px 0;color:#6b7280;font-size:13px;width:42%;">Client</td><td style="padding:5px 0;font-size:14px;font-weight:600;">' . esc_html($b->client_firstname . ' ' . $b->client_lastname) . '</td></tr>'
        . '<tr><td style="padding:5px 0;color:#6b7280;font-size:13px;">E-mail</td><td style="padding:5px 0;font-size:14px;"><a href="mailto:' . esc_attr($b->client_email) . '">' . esc_html($b->client_email) . '</a></td></tr>'
        . '<tr><td style="padding:5px 0;color:#6b7280;font-size:13px;">Téléphone</td><td style="padding:5px 0;font-size:14px;"><a href="tel:' . esc_attr($b->client_phone) . '">' . esc_html($b->client_phone) . '</a></td></tr>'
        . '</table>';
    if ($b->client_message) $inner .= '<p style="background:#f4f6f8;border-radius:8px;padding:12px;font-size:13px;"><strong>Message :</strong><br>' . nl2br(esc_html($b->client_message)) . '</p>';
    kc_send_html_email($to, '[Admin] ' . $title . ' — #' . (int) $b->id, kc_email_wrap($title, $inner));
}

/* ──────── Cycle de vie ──────── */
function kc_get_booking_enriched($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, t.name AS type_name, t.color AS type_color, t.cta_type, t.slug AS type_slug,
                s.name AS staff_name, s.email AS staff_email, s.google_calendar_id AS staff_gcal
         FROM {$wpdb->prefix}kc_bookings b
         LEFT JOIN {$wpdb->prefix}kc_booking_types t ON t.id = b.type_id
         LEFT JOIN {$wpdb->prefix}kc_booking_staff s ON s.id = b.staff_id
         WHERE b.id = %d", $id));
}

function kc_get_booking_by_token($token) {
    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}kc_bookings WHERE cancellation_token = %s", $token));
    return $id ? kc_get_booking_enriched((int) $id) : null;
}

function kc_confirm_booking($b, $send_email = true) {
    global $wpdb;
    if ($b->status === 'cancelled') return;
    if ($b->status !== 'confirmed') {
        $wpdb->update($wpdb->prefix . 'kc_bookings', ['status' => 'confirmed'], ['id' => (int) $b->id]);
        $b->status = 'confirmed';
    }
    if (empty($b->google_event_id) && kc_google_ready()) {
        $cid = $b->staff_gcal ?: $b->staff_email;
        if ($cid) {
            $event = [
                'summary'     => $b->type_name . ' — ' . $b->client_firstname . ' ' . $b->client_lastname,
                'description' => "Client : {$b->client_firstname} {$b->client_lastname}\nTél : {$b->client_phone}\nE-mail : {$b->client_email}\n"
                               . ($b->client_address ? "Adresse : {$b->client_address}\n" : '')
                               . ($b->client_message ? "Message : {$b->client_message}\n" : '')
                               . "\nRéservation #{$b->id} — " . kc_opt('company_name', 'Kayli Clinn'),
                'location'    => $b->client_address ?: '',
                'start'       => ['dateTime' => (new DateTime($b->start_datetime, kc_tz()))->format('c'), 'timeZone' => KC_BOOKING_TZ],
                'end'         => ['dateTime' => (new DateTime($b->end_datetime, kc_tz()))->format('c'),   'timeZone' => KC_BOOKING_TZ],
                'reminders'   => ['useDefault' => true],
            ];
            $created = kc_google_create_event($cid, $event, false);
            if (!is_wp_error($created) && !empty($created['id'])) {
                $wpdb->update($wpdb->prefix . 'kc_bookings', ['google_event_id' => $created['id']], ['id' => (int) $b->id]);
                $b->google_event_id = $created['id'];
            }
        }
    }
    if ($send_email) kc_email_client_confirmed($b);
}

function kc_mark_paid_and_confirm($b, $session_id = '') {
    global $wpdb;
    $m = $b->metadata_json ? (json_decode($b->metadata_json, true) ?: []) : [];
    $m['payment_status'] = 'paid'; if ($session_id) $m['stripe_session_id'] = $session_id;
    $wpdb->update($wpdb->prefix . 'kc_bookings', ['metadata_json' => wp_json_encode($m)], ['id' => (int) $b->id]);
    $b->metadata_json = wp_json_encode($m);
    kc_confirm_booking($b);
}

function kc_stripe_create_checkout($b, $amount) {
    $sk = kc_opt('stripe_sk'); if (!$sk) return new WP_Error('no_stripe', 'Stripe non configuré.');
    $base = kc_opt('page_url') ?: home_url('/');
    $resp = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Bearer ' . $sk],
        'body'    => [
            'mode'           => 'payment',
            'success_url'    => add_query_arg(['resa' => $b->cancellation_token, 'paid' => 1], $base),
            'cancel_url'     => add_query_arg(['resa' => $b->cancellation_token], $base),
            'customer_email' => $b->client_email,
            'metadata'       => ['kc_token' => $b->cancellation_token],
            'line_items'     => [[
                'quantity'   => 1,
                'price_data' => ['currency' => 'eur', 'unit_amount' => (int) round($amount * 100), 'product_data' => ['name' => $b->type_name . ' — acompte']],
            ]],
        ],
    ]);
    if (is_wp_error($resp)) return $resp;
    $j = json_decode(wp_remote_retrieve_body($resp), true);
    if (wp_remote_retrieve_response_code($resp) !== 200 || empty($j['url'])) return new WP_Error('stripe_failed', $j['error']['message'] ?? 'Erreur Stripe');
    return $j;
}

function kc_stripe_verify_sig($payload, $sig_header, $secret) {
    if (!$sig_header) return false;
    $t = null; $v1 = null;
    foreach (explode(',', $sig_header) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) continue;
        if ($kv[0] === 't')  $t  = $kv[1];
        if ($kv[0] === 'v1') $v1 = $kv[1];
    }
    if (!$t || !$v1) return false;
    if (!hash_equals(hash_hmac('sha256', $t . '.' . $payload, $secret), $v1)) return false;
    // Anti-rejeu : on refuse un horodatage hors tolérance (5 min, comme la
    // librairie officielle Stripe) pour empêcher la relecture d'un webhook capté.
    if (abs(time() - (int) $t) > 300) return false;
    return true;
}

/* ──────── Routes REST ──────── */
add_filter('rest_authentication_errors', 'kc_booking_allow_public_rest_routes', PHP_INT_MAX);
function kc_booking_allow_public_rest_routes($result) {
    return kc_booking_is_our_rest_route() ? null : $result;
}
function kc_booking_is_our_rest_route() {
    $uri = $_SERVER['REQUEST_URI'] ?? ''; $ns = KC_BOOKING_REST_NS;
    return (strpos($uri, '/wp-json/' . $ns) !== false
        || strpos($uri, 'rest_route=/' . $ns) !== false
        || strpos($uri, 'rest_route=%2F' . str_replace('/', '%2F', $ns)) !== false);
}

add_action('rest_api_init', 'kc_booking_register_rest_routes');
function kc_booking_register_rest_routes() {
    $tok = '(?P<token>[a-zA-Z0-9_-]{32,64})';
    register_rest_route(KC_BOOKING_REST_NS, '/types',         ['methods' => 'GET',  'callback' => 'kc_rest_get_types',        'permission_callback' => '__return_true']);
    register_rest_route(KC_BOOKING_REST_NS, '/availability',  ['methods' => 'GET',  'callback' => 'kc_rest_get_availability', 'permission_callback' => '__return_true',
        'args' => [
            'type_id'    => ['required' => true,  'sanitize_callback' => 'absint'],
            'variant_id' => ['required' => false, 'sanitize_callback' => 'absint'],
            'date_from'  => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
            'date_to'    => ['required' => true,  'sanitize_callback' => 'sanitize_text_field'],
            'staff_id'   => ['required' => false, 'sanitize_callback' => 'absint'],
        ]]);
    register_rest_route(KC_BOOKING_REST_NS, '/bookings',                      ['methods' => 'POST', 'callback' => 'kc_rest_create_booking',  'permission_callback' => '__return_true']);
    register_rest_route(KC_BOOKING_REST_NS, '/bookings/' . $tok,             ['methods' => 'GET',  'callback' => 'kc_rest_get_booking',     'permission_callback' => '__return_true']);
    register_rest_route(KC_BOOKING_REST_NS, '/bookings/' . $tok . '/confirm', ['methods' => 'POST', 'callback' => 'kc_rest_confirm_booking', 'permission_callback' => '__return_true']);
    register_rest_route(KC_BOOKING_REST_NS, '/bookings/' . $tok . '/cancel',  ['methods' => 'POST', 'callback' => 'kc_rest_cancel_booking',  'permission_callback' => '__return_true']);
    register_rest_route(KC_BOOKING_REST_NS, '/stripe/webhook',               ['methods' => 'POST', 'callback' => 'kc_rest_stripe_webhook',  'permission_callback' => '__return_true']);
}

function kc_rest_get_types(WP_REST_Request $request) {
    global $wpdb;
    $types = $wpdb->get_results("SELECT id, name, slug, description, icon, color, duration_minutes, buffer_minutes, min_advance_hours, max_advance_days, price, is_free, require_address, allow_team_selection, cta_type, external_url, position FROM {$wpdb->prefix}kc_booking_types WHERE is_active = 1 ORDER BY position ASC, id ASC");
    if ($types === null) return new WP_Error('kc_db_error', 'Erreur de lecture.', ['status' => 500]);
    $vrows = $wpdb->get_results("SELECT id, type_id, slug, name, duration_minutes, price_indicative, position FROM {$wpdb->prefix}kc_booking_type_variants WHERE is_active = 1 ORDER BY type_id ASC, position ASC, id ASC");
    $vbt = [];
    foreach ((array) $vrows as $v) {
        $vbt[(int) $v->type_id][] = ['id' => (int) $v->id, 'slug' => $v->slug, 'name' => $v->name,
            'duration_minutes' => (int) $v->duration_minutes,
            'price_indicative' => $v->price_indicative !== null ? (float) $v->price_indicative : null, 'position' => (int) $v->position];
    }
    $out = array_map(function ($t) use ($vbt) {
        $tid = (int) $t->id;
        return ['id' => $tid, 'name' => $t->name, 'slug' => $t->slug, 'description' => $t->description, 'icon' => $t->icon, 'color' => $t->color,
            'duration_minutes' => (int) $t->duration_minutes, 'buffer_minutes' => (int) $t->buffer_minutes,
            'min_advance_hours' => (int) $t->min_advance_hours, 'max_advance_days' => (int) $t->max_advance_days,
            'price' => $t->is_free ? null : (float) $t->price, 'is_free' => (bool) $t->is_free, 'cta_type' => $t->cta_type,
            'requires_payment' => ($t->cta_type === 'calendar_paid'), 'external_url' => $t->external_url,
            'require_address' => (bool) $t->require_address, 'allow_team_selection' => (bool) $t->allow_team_selection,
            'position' => (int) $t->position, 'variants' => $vbt[$tid] ?? []];
    }, $types);
    $r = rest_ensure_response($out); $r->header('Cache-Control', 'public, max-age=300'); return $r;
}

function kc_rest_get_availability(WP_REST_Request $request) {
    global $wpdb;
    $type_id = (int) $request['type_id']; $variant_id = $request['variant_id'] ? (int) $request['variant_id'] : 0;
    $staff_filter = $request['staff_id'] ? (int) $request['staff_id'] : 0;
    $df_raw = (string) $request['date_from']; $dt_raw = (string) $request['date_to'];
    $tz = kc_tz();

    $date_from = DateTime::createFromFormat('!Y-m-d', $df_raw, $tz);
    $date_to   = DateTime::createFromFormat('!Y-m-d', $dt_raw, $tz);
    if (!$date_from || !$date_to || $date_from->format('Y-m-d') !== $df_raw || $date_to->format('Y-m-d') !== $dt_raw)
        return new WP_Error('kc_bad_date', 'Dates invalides (AAAA-MM-JJ attendu).', ['status' => 400]);
    if ($date_to < $date_from) return new WP_Error('kc_bad_range', 'date_to doit être ≥ date_from.', ['status' => 400]);
    if ((int) $date_from->diff($date_to)->format('%a') > KC_BOOKING_MAX_RANGE)
        return new WP_Error('kc_range_too_wide', 'Amplitude max : ' . KC_BOOKING_MAX_RANGE . ' jours.', ['status' => 400]);

    $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_types WHERE id = %d AND is_active = 1", $type_id));
    if (!$type) return new WP_Error('kc_type_not_found', 'Type introuvable.', ['status' => 404]);
    $duration = (int) $type->duration_minutes; $buffer = (int) $type->buffer_minutes;

    $variant = null;
    if ($variant_id) {
        $variant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_type_variants WHERE id = %d AND type_id = %d AND is_active = 1", $variant_id, $type_id));
        if (!$variant) return new WP_Error('kc_variant_not_found', 'Variante introuvable.', ['status' => 404]);
        $duration = (int) $variant->duration_minutes;
    }

    $staff = kc_eligible_staff($type_id);
    if ($staff_filter) $staff = array_values(array_filter($staff, function ($s) use ($staff_filter) { return (int) $s->id === $staff_filter; }));

    $now = new DateTime('now', $tz);
    $earliest = (clone $now)->modify('+' . (int) $type->min_advance_hours . ' hours');
    $latest   = (clone $now)->modify('+' . (int) $type->max_advance_days . ' days');
    $win_start = clone $date_from; if ($earliest > $win_start) $win_start = clone $earliest;
    $win_end   = (clone $date_to)->setTime(23, 59, 59); if ($latest < $win_end) $win_end = clone $latest;

    $days_out = []; $cal_errors = []; $excluded = []; $google_used = false; $slot_step = (int) KC_BOOKING_SLOT_STEP;

    if (!empty($staff) && $win_start < $win_end) {
        $fb_start = (clone $win_start)->modify('-' . $buffer . ' minutes');
        $fb_end   = (clone $win_end)->modify('+' . ($duration + $buffer) . ' minutes');

        $busy_by_cid = [];
        if (kc_google_ready()) {
            $cids = [];
            foreach ($staff as $s) { $c = $s->google_calendar_id ?: $s->email; if ($c) $cids[] = $c; }
            if (!empty($cids)) {
                $fb = kc_google_get_busy_multi(array_unique($cids), $fb_start->format('c'), $fb_end->format('c'));
                if (is_wp_error($fb)) return new WP_Error('kc_google_unavailable', 'Disponibilités temporairement indisponibles : ' . $fb->get_error_message(), ['status' => 503]);
                $busy_by_cid = $fb['busy']; $cal_errors = $fb['errors']; $google_used = true;
            }
        }

        $sids = array_map(function ($s) { return (int) $s->id; }, $staff);
        $db_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT staff_id, start_datetime, end_datetime FROM {$wpdb->prefix}kc_bookings
             WHERE staff_id IN (" . implode(',', $sids) . ") AND status IN ('pending','confirmed')
             AND end_datetime > %s AND start_datetime < %s", $fb_start->format('Y-m-d H:i:s'), $fb_end->format('Y-m-d H:i:s')));

        $busy_by_staff = [];
        foreach ($staff as $s) {
            $sid = (int) $s->id; $cid = $s->google_calendar_id ?: $s->email;
            if ($google_used && $cid && isset($cal_errors[$cid])) { $excluded[$sid] = $cal_errors[$cid]; continue; }
            $busy_by_staff[$sid] = [];
            if ($google_used && $cid && !empty($busy_by_cid[$cid])) {
                foreach ($busy_by_cid[$cid] as $slot)
                    $busy_by_staff[$sid][] = [(new DateTime($slot['start']))->modify('-' . $buffer . ' minutes'), (new DateTime($slot['end']))->modify('+' . $buffer . ' minutes')];
            }
        }
        foreach ((array) $db_rows as $r) {
            $sid = (int) $r->staff_id; if (!isset($busy_by_staff[$sid])) continue;
            $busy_by_staff[$sid][] = [(new DateTime($r->start_datetime, $tz))->modify('-' . $buffer . ' minutes'), (new DateTime($r->end_datetime, $tz))->modify('+' . $buffer . ' minutes')];
        }

        $slot_step = max(5, (int) apply_filters('kc_booking_slot_step', KC_BOOKING_SLOT_STEP, $type, $variant));
        $slots_idx = [];
        foreach ($staff as $s) {
            $sid = (int) $s->id; if (!isset($busy_by_staff[$sid])) continue;
            $busy = $busy_by_staff[$sid];
            $cursor = clone $date_from;
            while ($cursor <= $date_to) {
                $w = kc_staff_working_window($s, $cursor);
                if ($w) {
                    $slot_start = clone $w[0];
                    while (true) {
                        $slot_end = (clone $slot_start)->modify('+' . $duration . ' minutes');
                        if ($slot_end > $w[1]) break;
                        if ($slot_start >= $earliest && $slot_start <= $latest && !kc_slot_overlaps_busy($slot_start, $slot_end, $busy)) {
                            $dk = $cursor->format('Y-m-d'); $tk = $slot_start->format('H:i');
                            if (!isset($slots_idx[$dk][$tk])) $slots_idx[$dk][$tk] = ['start' => $slot_start->format('c'), 'end' => $slot_end->format('c'), 'staff' => []];
                            $slots_idx[$dk][$tk]['staff'][] = $sid;
                        }
                        $slot_start = (clone $slot_start)->modify('+' . $slot_step . ' minutes');
                    }
                }
                $cursor->modify('+1 day');
            }
        }
        ksort($slots_idx);
        foreach ($slots_idx as $dk => $times) {
            ksort($times); $slots = [];
            foreach ($times as $tk => $info) { $st = array_values(array_unique($info['staff'])); sort($st);
                $slots[] = ['time' => $tk, 'start' => $info['start'], 'end' => $info['end'], 'staff_ids' => $st]; }
            $days_out[] = ['date' => $dk, 'weekday' => strtolower((new DateTime($dk, $tz))->format('D')), 'slots' => $slots];
        }
    }

    $payload = ['type_id' => $type_id, 'variant_id' => $variant_id ?: null, 'duration_minutes' => $duration, 'buffer_minutes' => $buffer,
        'timezone' => KC_BOOKING_TZ, 'slot_step' => $slot_step, 'range' => ['from' => $date_from->format('Y-m-d'), 'to' => $date_to->format('Y-m-d')],
        'source' => $google_used ? 'google+db' : 'db', 'days' => $days_out];
    if (!empty($cal_errors)) $payload['calendar_warnings'] = $cal_errors;
    if (!empty($excluded))   $payload['excluded_staff']    = $excluded;
    $r = rest_ensure_response($payload); $r->header('Cache-Control', 'public, max-age=30'); return $r;
}

/* ════════════════════════════════════════════════════════════════════════
   VERSION « DEVIS » de kc_rest_create_booking
   ────────────────────────────────────────────────────────────────────────
   OÙ : dans ton fichier PLUGIN (le gros fichier kc-booking).
   COMMENT :
     1. Recherche (Ctrl+F) la ligne :
            function kc_rest_create_booking(WP_REST_Request $req) {
     2. Sélectionne TOUT, depuis cette ligne jusqu'à juste AVANT la ligne :
            function kc_rest_get_booking(WP_REST_Request $req) {
        (c'est l'ancienne fonction, qui calcule le prix toute seule)
     3. Remplace-la par tout le bloc ci-dessous.
   ⚠️ Ne recopie PAS de balise <?php : tu es déjà dans le fichier.

   Ce qui change : la fonction accepte 4 champs envoyés par le devis :
     - amount_total : prix total TTC calculé par le formulaire
     - amount_now   : montant à encaisser maintenant (acompte OU total)
     - payment_mode : 'deposit' | 'full' | 'none'
     - quote        : détail/réponses du devis (stocké, visible en admin)
   Si ces champs sont absents, l'ancien comportement reste actif (sécurité).
   ════════════════════════════════════════════════════════════════════════ */

function kc_rest_create_booking(WP_REST_Request $req) {
    global $wpdb;
    if (!empty($req->get_param('company_url'))) return rest_ensure_response(['ok' => true, 'token' => bin2hex(random_bytes(32))]);

    $type_id    = (int) $req->get_param('type_id');
    $variant_id = (int) $req->get_param('variant_id');
    $staff_id   = (int) $req->get_param('staff_id');
    $start_raw  = sanitize_text_field((string) $req->get_param('start'));
    $first      = sanitize_text_field((string) $req->get_param('firstname'));
    $last       = sanitize_text_field((string) $req->get_param('lastname'));
    $email      = sanitize_email((string) $req->get_param('email'));
    $phone      = sanitize_text_field((string) $req->get_param('phone'));
    $address    = sanitize_text_field((string) $req->get_param('address'));
    $message    = sanitize_textarea_field((string) $req->get_param('message'));

    // ── Devis calculé côté formulaire (optionnel) ──
    $q_total = $req->get_param('amount_total');
    $q_now   = $req->get_param('amount_now');
    $q_mode  = sanitize_text_field((string) $req->get_param('payment_mode')); // deposit | full | none
    $q_blob  = $req->get_param('quote');
    $devis_driven = ($q_total !== null && $q_total !== '');

    if (!$type_id || $start_raw === '' || $first === '' || $last === '' || !is_email($email) || $phone === '')
        return new WP_Error('kc_missing', 'Champs obligatoires manquants ou invalides.', ['status' => 400]);

    $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_types WHERE id = %d AND is_active = 1", $type_id));
    if (!$type) return new WP_Error('kc_type_not_found', 'Prestation introuvable.', ['status' => 404]);
    $duration = (int) $type->duration_minutes; $buffer = (int) $type->buffer_minutes;

    $variant = null;
    if ($variant_id) {
        $variant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kc_booking_type_variants WHERE id = %d AND type_id = %d AND is_active = 1", $variant_id, $type_id));
        if (!$variant) return new WP_Error('kc_variant_not_found', 'Variante introuvable.', ['status' => 404]);
        $duration = (int) $variant->duration_minutes;
    }
    if ($type->require_address && $address === '') return new WP_Error('kc_address_required', 'Adresse obligatoire pour cette prestation.', ['status' => 400]);

    try { $start = new DateTime($start_raw, kc_tz()); } catch (Exception $e) { return new WP_Error('kc_bad_start', 'Date/heure invalide.', ['status' => 400]); }
    $start->setTimezone(kc_tz());
    $end = (clone $start)->modify('+' . $duration . ' minutes');

    $now = new DateTime('now', kc_tz());
    if ($start < (clone $now)->modify('+' . (int) $type->min_advance_hours . ' hours')) return new WP_Error('kc_too_soon', 'Créneau trop proche.', ['status' => 422]);
    if ($start > (clone $now)->modify('+' . (int) $type->max_advance_days . ' days')) return new WP_Error('kc_too_far', 'Créneau trop lointain.', ['status' => 422]);

    $eligible = kc_eligible_staff($type_id);
    if (empty($eligible)) return new WP_Error('kc_no_staff', 'Aucun intervenant disponible.', ['status' => 409]);
    if ($staff_id) $eligible = array_values(array_filter($eligible, function ($s) use ($staff_id) { return (int) $s->id === $staff_id; }));
    if (empty($eligible)) return new WP_Error('kc_staff_invalid', 'Intervenant non disponible.', ['status' => 422]);

    $chosen = null;
    foreach ($eligible as $s) {
        if (!kc_staff_works_at($s, $start, $end)) continue;
        $free = kc_slot_free_for_staff($s, $start, $end, $buffer);
        if (is_wp_error($free)) return new WP_Error('kc_google_unavailable', 'Vérification indisponible, réessayez.', ['status' => 503]);
        if ($free) { $chosen = $s; break; }
    }
    if (!$chosen) return new WP_Error('kc_slot_taken', 'Ce créneau vient d\'être pris. Choisissez-en un autre.', ['status' => 409]);

    // ── Tarification (RECALCULÉE CÔTÉ SERVEUR — ne jamais croire le client) ──
    // Sécurité : on ignore amount_total / amount_now envoyés par le navigateur.
    // Le prix d'un forfait vient de la grille officielle (KC_Pricing) à partir
    // du détail du devis (quote.booking) ; sinon on retombe sur le prix du type.
    if (is_string($q_blob)) { $dec = json_decode($q_blob, true); if (is_array($dec)) $q_blob = $dec; }

    $total = null; $deposit = null; $payment_mode = 'none'; $requires_payment = false;
    $server_priced = false;

    if (is_array($q_blob) && !empty($q_blob['booking']['forfait']) && class_exists('KC_Pricing')) {
        try {
            $devis        = KC_Pricing::compute_forfait($q_blob['booking']);
            $total        = $devis['total_cents'] / 100;
            $payment_mode = ($q_mode === 'full') ? 'full' : 'deposit';
            $deposit      = ($payment_mode === 'full') ? $total : $devis['acompte_cents'] / 100;
            $requires_payment = ($deposit > 0);
            $server_priced = true;
        } catch (InvalidArgumentException $e) {
            return new WP_Error('kc_bad_quote', 'Demande invalide : ' . $e->getMessage(), ['status' => 400]);
        }
    }

    if (!$server_priced) {
        $requires_payment = ($type->cta_type === 'calendar_paid');
        if ($requires_payment) {
            $price = ($variant && $variant->price_indicative !== null) ? (float) $variant->price_indicative : ($type->price !== null ? (float) $type->price : null);
            $total = $price; $dp = max(0, min(100, (int) kc_opt('deposit_percent', 30)));
            $deposit = $price !== null ? round($price * $dp / 100, 2) : null; $payment_mode = 'deposit';
        }
    }

    $token  = bin2hex(random_bytes(32));
    $status = (!$requires_payment && kc_opt('auto_confirm_free', 1)) ? 'confirmed' : 'pending';

    if (is_string($q_blob)) { $dec = json_decode($q_blob, true); if (is_array($dec)) $q_blob = $dec; }
    $meta = [
        'variant_name'   => $variant ? $variant->name : null,
        'consent'        => (bool) $req->get_param('consent'),
        'payment_status' => $requires_payment ? 'unpaid' : 'none',
        'quote'          => $q_blob ?: null,
    ];

    $wpdb->insert($wpdb->prefix . 'kc_bookings', [
        'type_id' => $type_id, 'variant_id' => $variant_id ?: null, 'staff_id' => (int) $chosen->id,
        'client_firstname' => $first, 'client_lastname' => $last, 'client_email' => $email, 'client_phone' => $phone,
        'client_address' => $address ?: null, 'client_message' => $message ?: null,
        'start_datetime' => $start->format('Y-m-d H:i:s'), 'end_datetime' => $end->format('Y-m-d H:i:s'), 'timezone' => KC_BOOKING_TZ,
        'total_amount_ttc' => $total, 'deposit_amount_ttc' => $deposit, 'payment_mode' => $payment_mode,
        'metadata_json' => wp_json_encode($meta), 'cancellation_token' => $token, 'status' => $status,
        'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
    $booking = kc_get_booking_enriched((int) $wpdb->insert_id);

    $payment_url = null;
    if ($requires_payment && $deposit && kc_opt('stripe_enabled') && kc_opt('stripe_sk')) {
        $sess = kc_stripe_create_checkout($booking, $deposit);
        if (!is_wp_error($sess)) {
            $payment_url = $sess['url'];
            $m = json_decode($booking->metadata_json, true) ?: []; $m['stripe_session_id'] = $sess['id'] ?? '';
            $wpdb->update($wpdb->prefix . 'kc_bookings', ['metadata_json' => wp_json_encode($m)], ['id' => (int) $booking->id]);
        }
    }

    if ($status === 'confirmed') kc_confirm_booking($booking);
    else                         kc_email_client_pending($booking, $payment_url);
    if (kc_opt('notify_admin', 1)) kc_email_admin($booking, ($status === 'confirmed' ? 'Nouvelle réservation confirmée' : 'Nouvelle demande de réservation'));

    return rest_ensure_response([
        'ok' => true, 'token' => $token, 'booking_id' => (int) $booking->id, 'status' => $status,
        'start' => $start->format('c'), 'end' => $end->format('c'),
        'staff' => ['id' => (int) $chosen->id, 'name' => $chosen->name],
        'requires_payment' => $requires_payment, 'total' => $total, 'deposit' => $deposit,
        'payment_mode' => $payment_mode, 'payment_url' => $payment_url,
        'manage_url' => kc_booking_manage_url($booking),
    ]);
}

function kc_rest_get_booking(WP_REST_Request $req) {
    $b = kc_get_booking_by_token($req['token']);
    if (!$b) return new WP_Error('kc_not_found', 'Réservation introuvable.', ['status' => 404]);
    $tz = kc_tz(); $start = new DateTime($b->start_datetime, $tz);
    $deadline = (clone $start)->modify('-' . (int) kc_opt('cancel_min_hours', 24) . ' hours');
    $can_cancel = in_array($b->status, ['pending', 'confirmed']) && (new DateTime('now', $tz)) <= $deadline;
    return rest_ensure_response([
        'token' => $b->cancellation_token, 'status' => $b->status,
        'type' => ['id' => (int) $b->type_id, 'name' => $b->type_name, 'color' => $b->type_color],
        'variant_name' => kc_booking_meta($b, 'variant_name'),
        'start' => $start->format('c'), 'end' => (new DateTime($b->end_datetime, $tz))->format('c'),
        'staff' => $b->staff_name ?: null,
        'client' => ['firstname' => $b->client_firstname, 'lastname' => $b->client_lastname, 'email' => $b->client_email, 'phone' => $b->client_phone],
        'address' => $b->client_address,
        'requires_payment' => ($b->cta_type === 'calendar_paid'),
        'total' => $b->total_amount_ttc !== null ? (float) $b->total_amount_ttc : null,
        'deposit' => $b->deposit_amount_ttc !== null ? (float) $b->deposit_amount_ttc : null,
        'payment_status' => kc_booking_meta($b, 'payment_status', 'none'),
        'can_cancel' => $can_cancel, 'cancel_deadline' => $deadline->format('c'),
    ]);
}

function kc_rest_confirm_booking(WP_REST_Request $req) {
    $b = kc_get_booking_by_token($req['token']);
    if (!$b) return new WP_Error('kc_not_found', 'Réservation introuvable.', ['status' => 404]);
    if ($b->status === 'cancelled') return new WP_Error('kc_cancelled', 'Réservation annulée.', ['status' => 409]);
    if ($b->status === 'confirmed') return rest_ensure_response(['ok' => true, 'status' => 'confirmed']);
    if ($b->cta_type === 'calendar_paid' && kc_opt('stripe_enabled') && kc_booking_meta($b, 'payment_status') !== 'paid')
        return new WP_Error('kc_payment_required', 'Paiement non finalisé.', ['status' => 402]);
    kc_confirm_booking($b);
    return rest_ensure_response(['ok' => true, 'status' => 'confirmed']);
}

function kc_rest_cancel_booking(WP_REST_Request $req) {
    global $wpdb;
    $b = kc_get_booking_by_token($req['token']);
    if (!$b) return new WP_Error('kc_not_found', 'Réservation introuvable.', ['status' => 404]);
    if ($b->status === 'cancelled') return rest_ensure_response(['ok' => true, 'status' => 'cancelled']);
    if (in_array($b->status, ['completed', 'no_show'])) return new WP_Error('kc_not_cancellable', 'Réservation non annulable.', ['status' => 409]);

    $tz = kc_tz(); $start = new DateTime($b->start_datetime, $tz);
    $minh = (int) kc_opt('cancel_min_hours', 24);
    if ((new DateTime('now', $tz)) > (clone $start)->modify('-' . $minh . ' hours'))
        return new WP_Error('kc_too_late', 'Annulation impossible : moins de ' . $minh . 'h avant le rendez-vous.', ['status' => 409]);

    $reason = sanitize_textarea_field((string) $req->get_param('reason'));
    $wpdb->update($wpdb->prefix . 'kc_bookings', ['status' => 'cancelled', 'cancelled_at' => current_time('mysql'), 'cancellation_reason' => $reason ?: null], ['id' => (int) $b->id]);
    if ($b->google_event_id && kc_google_ready()) {
        $cid = $b->staff_gcal ?: $b->staff_email;
        if ($cid) kc_google_delete_event($cid, $b->google_event_id);
    }
    $b->status = 'cancelled';
    kc_email_client_cancelled($b);
    if (kc_opt('notify_admin', 1)) kc_email_admin($b, 'Réservation annulée par le client', '<p>Le client a annulé la réservation suivante' . ($reason ? ' (motif : ' . esc_html($reason) . ')' : '') . ' :</p>');
    return rest_ensure_response(['ok' => true, 'status' => 'cancelled']);
}

function kc_rest_stripe_webhook(WP_REST_Request $req) {
    $payload = $req->get_body();
    $secret  = kc_opt('stripe_webhook_secret');
    if ($secret && !kc_stripe_verify_sig($payload, $req->get_header('Stripe-Signature'), $secret))
        return new WP_Error('kc_bad_sig', 'Signature invalide.', ['status' => 400]);
    $event = json_decode($payload, true);
    if (($event['type'] ?? '') === 'checkout.session.completed') {
        $obj = $event['data']['object'] ?? [];
        $token = $obj['metadata']['kc_token'] ?? '';
        if ($token) {
            $b = kc_get_booking_by_token($token);
            if ($b && $b->status === 'pending') kc_mark_paid_and_confirm($b, $obj['id'] ?? '');
        }
    }
    return rest_ensure_response(['received' => true]);
}

/* ──────── ADMIN : Configuration ──────── */
add_action('admin_menu', 'kc_booking_config_menu', 13);
function kc_booking_config_menu() {
    add_submenu_page('kc-booking-dashboard', 'Configuration', '⚙️ Configuration', 'manage_options', 'kc-booking-config', 'kc_booking_admin_config');
}

function kc_booking_admin_config() {
    if (isset($_POST['kc_cfg_save']) && check_admin_referer('kc_cfg')) {
        update_option('kc_booking_company_name',    sanitize_text_field($_POST['company_name']));
        update_option('kc_booking_company_phone',   sanitize_text_field($_POST['company_phone']));
        update_option('kc_booking_company_address', sanitize_text_field($_POST['company_address']));
        update_option('kc_booking_admin_email',     sanitize_email($_POST['admin_email']));
        update_option('kc_booking_page_url',        esc_url_raw($_POST['page_url']));
        update_option('kc_booking_auto_confirm_free', empty($_POST['auto_confirm_free']) ? 0 : 1);
        update_option('kc_booking_notify_admin',      empty($_POST['notify_admin']) ? 0 : 1);
        update_option('kc_booking_cancel_min_hours', max(0, (int) $_POST['cancel_min_hours']));
        update_option('kc_booking_deposit_percent',  max(0, min(100, (int) $_POST['deposit_percent'])));
        update_option('kc_booking_stripe_enabled',   empty($_POST['stripe_enabled']) ? 0 : 1);
        update_option('kc_booking_stripe_pk',             sanitize_text_field($_POST['stripe_pk']));
        update_option('kc_booking_stripe_sk',             sanitize_text_field($_POST['stripe_sk']));
        update_option('kc_booking_stripe_webhook_secret', sanitize_text_field($_POST['stripe_webhook_secret']));
        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuration enregistrée.</p></div>';
    }
    $webhook_url = rest_url(KC_BOOKING_REST_NS . '/stripe/webhook');
    ?>
    <div class="wrap">
        <h1>⚙️ Configuration</h1>
        <form method="post">
            <?php wp_nonce_field('kc_cfg'); ?>
            <input type="hidden" name="kc_cfg_save" value="1">

            <h2>🏢 Entreprise (affichée dans les e-mails)</h2>
            <table class="form-table">
                <tr><th><label>Nom</label></th><td><input type="text" name="company_name" value="<?php echo esc_attr(kc_opt('company_name', 'Kayli Clinn')); ?>" class="regular-text"></td></tr>
                <tr><th><label>Téléphone</label></th><td><input type="text" name="company_phone" value="<?php echo esc_attr(kc_opt('company_phone')); ?>" class="regular-text"></td></tr>
                <tr><th><label>Adresse</label></th><td><input type="text" name="company_address" value="<?php echo esc_attr(kc_opt('company_address')); ?>" class="regular-text"></td></tr>
                <tr><th><label>E-mail admin</label></th><td><input type="email" name="admin_email" value="<?php echo esc_attr(kc_opt('admin_email', 'contact@kayliclinn.fr')); ?>" class="regular-text"></td></tr>
                <tr><th><label>URL page réservation</label></th><td><input type="url" name="page_url" value="<?php echo esc_attr(kc_opt('page_url')); ?>" class="regular-text" placeholder="https://kayliclinn.fr/reservation/"></td></tr>
            </table>

            <h2>📅 Comportement des réservations</h2>
            <table class="form-table">
                <tr><th>Prestations gratuites</th><td><label><input type="checkbox" name="auto_confirm_free" value="1" <?php checked(kc_opt('auto_confirm_free', 1)); ?>> Confirmer automatiquement</label></td></tr>
                <tr><th>Notifications admin</th><td><label><input type="checkbox" name="notify_admin" value="1" <?php checked(kc_opt('notify_admin', 1)); ?>> M'envoyer un e-mail à chaque réservation / annulation</label></td></tr>
                <tr><th><label>Délai d'annulation</label></th><td><input type="number" name="cancel_min_hours" value="<?php echo (int) kc_opt('cancel_min_hours', 24); ?>" min="0" step="1"> heures avant le RDV</td></tr>
            </table>

            <h2>💳 Paiements (prestations payantes)</h2>
            <table class="form-table">
                <tr><th><label>Acompte demandé</label></th><td><input type="number" name="deposit_percent" value="<?php echo (int) kc_opt('deposit_percent', 30); ?>" min="0" max="100" step="1"> % du montant</td></tr>
                <tr><th>Activer Stripe</th><td><label><input type="checkbox" name="stripe_enabled" value="1" <?php checked(kc_opt('stripe_enabled')); ?>> Encaisser l'acompte en ligne via Stripe</label><p class="description">Si désactivé : la réservation payante reste « en attente » et tu es notifié pour gérer l'acompte manuellement.</p></td></tr>
                <tr><th><label>Clé publique (pk_…)</label></th><td><input type="text" name="stripe_pk" value="<?php echo esc_attr(kc_opt('stripe_pk')); ?>" class="large-text"></td></tr>
                <tr><th><label>Clé secrète (sk_…)</label></th><td><input type="password" name="stripe_sk" value="<?php echo esc_attr(kc_opt('stripe_sk')); ?>" class="large-text"></td></tr>
                <tr><th><label>Secret du webhook (whsec_…)</label></th><td><input type="password" name="stripe_webhook_secret" value="<?php echo esc_attr(kc_opt('stripe_webhook_secret')); ?>" class="large-text"></td></tr>
                <tr><th>URL du webhook Stripe</th><td><code><?php echo esc_html($webhook_url); ?></code><p class="description">À déclarer dans Stripe → Developers → Webhooks, événement <code>checkout.session.completed</code>.</p></td></tr>
            </table>

            <p><button type="submit" class="button button-primary button-large">💾 Enregistrer la configuration</button></p>
        </form>
    </div>
    <?php
}

/* ──────── ADMIN : Diagnostic REST ──────── */
add_action('admin_menu', 'kc_booking_rest_admin_menu', 12);
function kc_booking_rest_admin_menu() {
    add_submenu_page('kc-booking-dashboard', 'Diagnostic REST API', '🌐 Diagnostic REST', 'manage_options', 'kc-booking-rest-diag', 'kc_booking_admin_rest_diag');
}

function kc_booking_admin_rest_diag() {
    global $wpdb;
    $base = rest_url(KC_BOOKING_REST_NS);
    $first_type = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}kc_booking_types WHERE is_active = 1 ORDER BY position ASC, id ASC LIMIT 1");
    $df = (new DateTime('now', kc_tz()))->format('Y-m-d');
    $dt = (new DateTime('+14 days', kc_tz()))->format('Y-m-d');
    $rows = [
        ['GET',  '/types',                    'types'],
        ['GET',  '/availability',             'availability'],
        ['POST', '/bookings',                 null],
        ['GET',  '/bookings/{token}',         null],
        ['POST', '/bookings/{token}/confirm', null],
        ['POST', '/bookings/{token}/cancel',  null],
        ['POST', '/stripe/webhook',           null],
    ];
    ?>
    <div class="wrap">
        <h1>🌐 Diagnostic REST API — Phase 3 complète</h1>
        <p><strong>Base :</strong> <code><?php echo esc_html($base); ?></code></p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:60px;">Méth.</th><th>Route</th><th style="width:90px;">Statut</th><th style="width:160px;">Test</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><code><?php echo esc_html($r[0]); ?></code></td>
                    <td><code><?php echo esc_html($r[1]); ?></code></td>
                    <td><span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">✓ Livré</span></td>
                    <td><?php if ($r[2]): ?><button type="button" class="button" data-test="<?php echo esc_attr($r[2]); ?>">▶ Tester</button><?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description">POST /bookings, confirm et cancel se testent depuis le formulaire front (ils créent/modifient des données réelles).</p>
        <h2>Résultat</h2>
        <div id="kc-meta" style="font-family:monospace;font-size:12px;color:#6B7280;margin-bottom:8px;"></div>
        <pre id="kc-out" style="background:#0D2340;color:#E5E7EB;border-radius:8px;padding:16px;min-height:140px;font-family:monospace;font-size:12px;line-height:1.55;overflow:auto;max-height:560px;white-space:pre-wrap;word-break:break-word;">Cliquez sur « Tester ».</pre>
    </div>
    <script>
    (function(){
        const base = <?php echo wp_json_encode($base); ?>;
        const nonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
        const out = document.getElementById('kc-out'), meta = document.getElementById('kc-meta');
        const map = {
            'types': '/types',
            'availability': '/availability?type_id=<?php echo $first_type; ?>&date_from=<?php echo $df; ?>&date_to=<?php echo $dt; ?>'
        };
        document.querySelectorAll('[data-test]').forEach(b => b.addEventListener('click', function(){
            const url = base + map[this.dataset.test]; const t0 = performance.now();
            meta.textContent = ''; out.textContent = '⏳ GET ' + url;
            fetch(url, {credentials:'include', headers:{'X-WP-Nonce':nonce}})
              .then(r => r.text().then(t => ({s:r.status, st:r.statusText, b:t})))
              .then(({s,st,b}) => { let p; try{p=JSON.parse(b);}catch(e){p=b;}
                  meta.innerHTML = '<span>'+s+' '+st+'</span> · <span>'+Math.round(performance.now()-t0)+' ms</span>';
                  out.textContent = typeof p==='string'?p:JSON.stringify(p,null,2);
              }).catch(e => out.textContent = '❌ ' + e.message);
        }));
    })();
    </script>
    <?php
}