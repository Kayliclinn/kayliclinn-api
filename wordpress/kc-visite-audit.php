<?php
/**
 * Plugin Name: Kayli Clinn — Type « Visite d'audit »
 * Description: Crée (une fois) un type de réservation gratuit « Visite d'audit gratuite » dans kc-booking, utilisé comme calendrier commun à toutes les prestations sur devis (sinistre, textile, vitres en hauteur, événement…). Aucun réglage à faire.
 * Version: 1.0.0
 * Author: Kayli Clinn
 *
 * INSTALLATION : Extensions → Téléverser → activer. Le type apparaît dans
 * kc-booking → Prestations, avec le slug « visite-audit ». Désactiver ce
 * plugin ne supprime pas le type (vos réservations restent intactes).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'kc_visite_audit_seed' );
// Filet de sécurité si kc-booking est activé après ce plugin :
add_action( 'admin_init', 'kc_visite_audit_seed' );

function kc_visite_audit_seed() {
	global $wpdb;
	$table = $wpdb->prefix . 'kc_booking_types';

	// La table de kc-booking doit exister (plugin de réservation actif).
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return;
	}

	// Idempotent : ne rien faire si le type existe déjà.
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", 'visite-audit' ) );
	if ( $exists ) {
		// On s'assure juste qu'il reste actif et gratuit.
		$wpdb->update( $table, array( 'is_active' => 1, 'cta_type' => 'calendar_free' ), array( 'id' => (int) $exists ) );
		return;
	}

	// Colonnes alignées sur le seed officiel de kc-booking (sûres).
	$wpdb->insert( $table, array(
		'name'             => "Visite d'audit gratuite",
		'slug'             => 'visite-audit',
		'icon'             => 'clipboard',
		'color'            => '#0B8483',
		'duration_minutes' => 45,
		'cta_type'         => 'calendar_free',
		'require_address'  => 1,
		'description'      => "Visite gratuite et sans engagement pour établir un devis ferme (prestations sur mesure : sinistre, textile, vitres en hauteur, événement, etc.).",
		'position'         => 5,
		'is_active'        => 1,
	) );
}
