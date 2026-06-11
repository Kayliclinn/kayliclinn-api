<?php
/**
 * Phase 3.2 — GET /wp-json/kc-booking/v1/availability (implémentation de référence)
 *
 * Renvoie les créneaux disponibles dans le format EXACT consommé par la
 * page /reservation/ :
 *
 *   { "days": [ { "date": "2026-06-15", "weekday": "mon",
 *                 "slots": [ { "time": "09:00",
 *                              "start": "2026-06-15T09:00:00+02:00",
 *                              "end":   "2026-06-15T11:00:00+02:00",
 *                              "staff_ids": [1] } ] } ] }
 *
 * ⚠️ À INTÉGRER dans l'extension kc-booking existante : les trois fonctions
 * marquées « POINT D'INTÉGRATION » doivent être branchées sur vos tables
 * (personnel, horaires) et sur la synchro Google Agenda de la Phase 2.
 * En attendant, des valeurs par défaut raisonnables permettent de tester
 * le parcours de bout en bout (lun–sam, 08:30–17:30).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'kc-booking/v1', '/availability', array(
		'methods'             => 'GET',
		'callback'            => 'kc_booking_get_availability',
		'permission_callback' => '__return_true', // endpoint public (lecture seule)
		'args'                => array(
			'type_id'    => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			'variant_id' => array( 'required' => false, 'sanitize_callback' => 'absint' ),
			'date_from'  => array( 'required' => true ),
			'date_to'    => array( 'required' => true ),
		),
	) );
} );

function kc_booking_get_availability( WP_REST_Request $req ) {
	$type_id    = (int) $req['type_id'];
	$variant_id = (int) ( $req['variant_id'] ?? 0 );
	$tz         = new DateTimeZone( 'Europe/Paris' );

	// Bornes : aujourd'hui → 31 jours maximum
	$from = DateTime::createFromFormat( 'Y-m-d', (string) $req['date_from'], $tz );
	$to   = DateTime::createFromFormat( 'Y-m-d', (string) $req['date_to'], $tz );
	if ( ! $from || ! $to ) {
		return new WP_Error( 'kc_bad_dates', 'Dates invalides.', array( 'status' => 400 ) );
	}
	$today = new DateTime( 'today', $tz );
	if ( $from < $today ) {
		$from = clone $today;
	}
	$max = ( clone $today )->modify( '+31 days' );
	if ( $to > $max ) {
		$to = $max;
	}

	$duration = kc_booking_get_duration_minutes( $type_id, $variant_id );
	$lead     = ( new DateTime( 'now', $tz ) )->modify( '+24 hours' ); // délai mini de prévenance

	$weekdays = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' );
	$days     = array();

	for ( $d = clone $from; $d <= $to; $d->modify( '+1 day' ) ) {
		$date  = $d->format( 'Y-m-d' );
		$staff = kc_booking_get_working_staff( $type_id, $d ); // [ staff_id => [ ['08:30','12:00'], ['13:30','17:30'] ] ]
		if ( empty( $staff ) ) {
			continue;
		}

		$slots = array();
		foreach ( $staff as $staff_id => $plages ) {
			$busy = kc_booking_get_busy_intervals( $staff_id, $date ); // [ ['start'=>DateTime,'end'=>DateTime], … ]
			foreach ( $plages as $plage ) {
				$cursor = new DateTime( $date . ' ' . $plage[0], $tz );
				$fin    = new DateTime( $date . ' ' . $plage[1], $tz );
				while ( ( clone $cursor )->modify( '+' . $duration . ' minutes' ) <= $fin ) {
					$slot_end = ( clone $cursor )->modify( '+' . $duration . ' minutes' );
					if ( $cursor >= $lead && ! kc_booking_overlaps( $cursor, $slot_end, $busy ) ) {
						$key = $cursor->format( 'H:i' );
						if ( ! isset( $slots[ $key ] ) ) {
							$slots[ $key ] = array(
								'time'      => $key,
								'start'     => $cursor->format( 'c' ),
								'end'       => $slot_end->format( 'c' ),
								'staff_ids' => array(),
							);
						}
						$slots[ $key ]['staff_ids'][] = (int) $staff_id;
					}
					$cursor->modify( '+150 minutes' ); // pas entre débuts de créneaux (2 h 30)
				}
			}
		}

		if ( $slots ) {
			ksort( $slots );
			$days[] = array(
				'date'    => $date,
				'weekday' => $weekdays[ (int) $d->format( 'w' ) ],
				'slots'   => array_values( $slots ),
			);
		}
	}

	return rest_ensure_response( array( 'days' => $days ) );
}

/* ─────────────────────────────────────────────────────────────
   POINT D'INTÉGRATION 1 — durée d'un créneau selon type/variante.
   Brancher sur les tables kc-booking (duration_minutes).
   ───────────────────────────────────────────────────────────── */
function kc_booking_get_duration_minutes( $type_id, $variant_id ) {
	// TODO kc-booking : SELECT duration_minutes FROM variantes/types.
	return 120;
}

/* ─────────────────────────────────────────────────────────────
   POINT D'INTÉGRATION 2 — personnel travaillant ce jour-là pour
   cette prestation, avec ses plages horaires. Brancher sur la
   table du personnel + horaires de travail (vrais horaires à
   saisir avant la mise en ligne — point 5 de la checklist).
   ───────────────────────────────────────────────────────────── */
function kc_booking_get_working_staff( $type_id, DateTime $jour ) {
	$wd = (int) $jour->format( 'N' ); // 1 = lundi … 7 = dimanche
	if ( 7 === $wd ) {
		return array(); // dimanche : fermé par défaut (majoration gérée à part)
	}
	// TODO kc-booking : remplacer par les vrais horaires du personnel.
	return array( 1 => array( array( '08:30', '12:30' ), array( '13:30', '17:30' ) ) );
}

/* ─────────────────────────────────────────────────────────────
   POINT D'INTÉGRATION 3 — indisponibilités d'un membre du
   personnel pour une date : réservations kc-booking existantes
   + événements Google Agenda (synchro Phase 2).
   ───────────────────────────────────────────────────────────── */
function kc_booking_get_busy_intervals( $staff_id, $date ) {
	$busy = array();
	// TODO kc-booking : réservations confirmées/en attente du jour.
	// TODO kc-booking : événements Google Agenda du compte de service.
	return $busy;
}

/** Vrai si [start,end] chevauche un intervalle occupé. */
function kc_booking_overlaps( DateTime $start, DateTime $end, array $busy ) {
	foreach ( $busy as $b ) {
		if ( $start < $b['end'] && $end > $b['start'] ) {
			return true;
		}
	}
	return false;
}
