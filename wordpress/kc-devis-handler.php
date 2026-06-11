<?php
/**
 * Gestionnaire durci de l'action admin-ajax « kc_devis » (extension kc-devis).
 *
 * Reçoit le devis du tunnel /devis/ et envoie deux emails : notification
 * à l'admin + récapitulatif au client. Version de référence à intégrer
 * dans l'extension kc-devis existante (remplace le handler actuel).
 *
 * Sécurité incluse : nonce, honeypot, limitation de débit (transients),
 * validation des champs, échappement HTML de TOUTE donnée client,
 * prix recalculés via KC_Pricing pour les forfaits.
 *
 * Le plugin doit aussi injecter sur la page /devis/ :
 *   wp_localize_script( $handle, 'kcDevis', array(
 *     'ajaxUrl' => admin_url( 'admin-ajax.php' ),
 *     'nonce'   => wp_create_nonce( 'kc_devis' ),
 *   ) );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/kc-pricing.php';

add_action( 'wp_ajax_kc_devis', 'kc_devis_handle' );
add_action( 'wp_ajax_nopriv_kc_devis', 'kc_devis_handle' );

function kc_devis_handle() {
	// 1) Nonce
	if ( ! check_ajax_referer( 'kc_devis', '_wpnonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Session expirée, rechargez la page.' ), 403 );
	}

	// 2) Limitation de débit : 5 envois / 10 min / IP
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'inconnu';
	$key = 'kc_devis_rl_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		wp_send_json_error( array( 'message' => 'Trop de demandes, réessayez dans quelques minutes.' ), 429 );
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

	// 3) Charge utile
	$raw     = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	$payload = json_decode( $raw, true );
	if ( ! is_array( $payload ) ) {
		wp_send_json_error( array( 'message' => 'Demande illisible.' ), 400 );
	}

	// Honeypot éventuel transmis par le formulaire
	if ( ! empty( $payload['website'] ) ) {
		wp_send_json_success(); // robot : on simule un succès
	}

	// 4) Validation du contact
	$c         = isset( $payload['contact'] ) && is_array( $payload['contact'] ) ? $payload['contact'] : array();
	$firstname = kc_devis_clean_name( $c['firstname'] ?? '' );
	$lastname  = kc_devis_clean_name( $c['lastname'] ?? '' );
	$email     = sanitize_email( $c['email'] ?? '' );
	$phone     = preg_replace( '/[^0-9+\s.\-]/', '', (string) ( $c['phone'] ?? '' ) );
	$address   = sanitize_text_field( mb_substr( (string) ( $c['address'] ?? '' ), 0, 200 ) );
	$message   = sanitize_textarea_field( mb_substr( (string) ( $c['message'] ?? '' ), 0, 1000 ) );

	if ( '' === $firstname || '' === $lastname || ! is_email( $email ) || strlen( preg_replace( '/\D/', '', $phone ) ) < 9 ) {
		wp_send_json_error( array( 'message' => 'Coordonnées incomplètes ou invalides.' ), 400 );
	}

	$kind       = in_array( $payload['kind'] ?? '', array( 'forfait', 'estimation', 'audit' ), true ) ? $payload['kind'] : 'audit';
	$prestation = sanitize_text_field( mb_substr( (string) ( $payload['prestation'] ?? 'Votre demande' ), 0, 120 ) );

	// 5) Bloc tarif — recalculé serveur pour les forfaits, indicatif sinon
	$prix_html = '';
	if ( 'forfait' === $kind && ! empty( $payload['booking'] ) && is_array( $payload['booking'] ) ) {
		try {
			$devis = KC_Pricing::compute_forfait( $payload['booking'] );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( array( 'message' => 'Demande invalide : ' . esc_html( $e->getMessage() ) ), 400 );
		}
		$lignes = '';
		foreach ( $devis['lignes'] as $l ) {
			$lignes .= '<li>' . esc_html( $l['label'] ) . ' : ' . esc_html( number_format_i18n( $l['montant'], 2 ) ) . ' €</li>';
		}
		$prix_html = '<h3>Votre tarif (grille officielle)</h3><ul>' . $lignes . '</ul>'
			. '<p><strong>Total : ' . esc_html( number_format_i18n( $devis['total_cents'] / 100, 2 ) ) . ' € TTC</strong><br>'
			. 'Acompte à la réservation (30 %) : ' . esc_html( number_format_i18n( $devis['acompte_cents'] / 100, 2 ) ) . ' €<br>'
			. 'Solde après la prestation : ' . esc_html( number_format_i18n( $devis['solde_cents'] / 100, 2 ) ) . ' €</p>'
			. '<p>Ce tarif est ferme pour un logement en état normal : vous pouvez réserver votre créneau sur '
			. '<a href="' . esc_url( home_url( '/reservation/' ) ) . '">kayliclinn.fr/reservation</a>.</p>';
	} elseif ( 'estimation' === $kind && ! empty( $payload['estimation'] ) && is_array( $payload['estimation'] ) ) {
		$est = $payload['estimation'];
		$min = max( 0, min( (float) ( $est['min'] ?? 0 ), 1000000 ) );
		$max = max( $min, min( (float) ( $est['max'] ?? 0 ), 1000000 ) );
		$prix_html = '<h3>Votre estimation indicative</h3>'
			. '<p><strong>' . esc_html( number_format_i18n( $min ) ) . ' – ' . esc_html( number_format_i18n( $max ) ) . ' '
			. esc_html( sanitize_text_field( $est['unite'] ?? '€ HT' ) ) . '</strong></p>'
			. ( ! empty( $est['detail'] ) ? '<p>' . esc_html( sanitize_text_field( $est['detail'] ) ) . '</p>' : '' )
			. '<p>Le devis ferme vous est remis après une visite d\'audit gratuite et sans engagement.</p>';
	} else {
		$prix_html = '<p>Votre demande nécessite une visite d\'audit gratuite pour établir un devis ferme. '
			. 'Nous vous recontactons sous 24 h ouvrées.</p>';
	}

	// 6) Récapitulatif (tout est échappé)
	$recap = '<h3>Récapitulatif</h3><ul>'
		. '<li>Prestation : ' . esc_html( $prestation ) . '</li>'
		. '<li>Nom : ' . esc_html( $firstname . ' ' . $lastname ) . '</li>'
		. '<li>Email : ' . esc_html( $email ) . '</li>'
		. '<li>Téléphone : ' . esc_html( $phone ) . '</li>'
		. ( $address ? '<li>Adresse : ' . esc_html( $address ) . '</li>' : '' )
		. ( $message ? '<li>Précisions : ' . esc_html( $message ) . '</li>' : '' )
		. '</ul>';

	$admin_email = get_option( 'kc_devis_notification_email', get_option( 'admin_email' ) );
	$headers     = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $firstname . ' ' . $lastname . ' <' . $email . '>',
	);

	// 7) Envois
	$ok_admin = wp_mail(
		$admin_email,
		'Demande de devis — ' . $firstname . ' ' . $lastname . ' — ' . $prestation,
		'<h2>Nouvelle demande de devis depuis le site</h2>' . $prix_html . $recap,
		$headers
	);
	$ok_client = wp_mail(
		$email,
		'Votre estimation Kayli Clinn — ' . $prestation,
		'<h2>Merci pour votre demande !</h2><p>Bonjour ' . esc_html( $firstname ) . ',</p>' . $prix_html . $recap
			. '<p>Une question ? Répondez à cet email ou appelez-nous au <a href="tel:+33670012061">06 70 01 20 61</a>.</p>'
			. '<p>À très bientôt,<br>L\'équipe Kayli Clinn</p>',
		array( 'Content-Type: text/html; charset=UTF-8' )
	);

	if ( ! $ok_admin && ! $ok_client ) {
		wp_send_json_error( array( 'message' => 'L\'envoi a échoué, réessayez ou appelez-nous.' ), 500 );
	}
	wp_send_json_success();
}

/** Nettoie un prénom/nom : lettres, espaces, tirets, apostrophes — 40 caractères max. */
function kc_devis_clean_name( $value ) {
	$value = sanitize_text_field( mb_substr( (string) $value, 0, 40 ) );
	return preg_match( '/^[\p{L}][\p{L}\s\'\.\-]{1,39}$/u', $value ) ? $value : '';
}
