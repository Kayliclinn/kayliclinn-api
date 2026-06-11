<?php
/**
 * KC_Pricing — Grille tarifaire officielle Kayli Clinn (source : Excel tarification).
 *
 * SÉCURITÉ : cette classe est LA référence des prix côté serveur.
 * L'extension kc-booking DOIT recalculer chaque montant avec elle au moment
 * de créer la session Stripe — ne jamais faire confiance aux montants
 * envoyés par le navigateur (amount_total / amount_now sont indicatifs).
 *
 * Intégration : require_once dans kc-booking (et kc-devis), puis voir
 * INTEGRATION-kc-booking.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KC_Pricing {

	/** Forfaits logement TTC — réservables en ligne (acompte 30 %). */
	const FORFAITS = array(
		'airbnb'        => array(
			'label' => 'Nettoyage Airbnb / location courte durée',
			'prix'  => array( 'studio' => 45, 'p2' => 60, 'p3' => 75, 'p4' => 95 ),
		),
		'demenagement'  => array(
			'label' => 'Nettoyage après déménagement / fin de bail / état des lieux',
			'prix'  => array( 'studio' => 79, 'p2' => 99, 'p3' => 119, 'p4' => 149 ),
		),
		'standard'      => array(
			'label' => 'Nettoyage appartement standard (ponctuel complet)',
			'prix'  => array( 'studio' => 79, 'p2' => 99, 'p3' => 119, 'p4' => 149 ),
		),
		'logement-vide' => array(
			'label' => 'Nettoyage logement vide',
			'prix'  => array( 'studio' => 79, 'p2' => 99, 'p3' => 119, 'p4' => 149 ),
		),
	);

	const TAILLES = array(
		'studio' => 'Studio ≤ 25 m²',
		'p2'     => '2 pièces',
		'p3'     => '3 pièces',
		'p4'     => '4 pièces',
	);

	/** Options à montant fixe (une seule option « linge », un seul « balcon »). */
	const OPTIONS = array(
		'linge-petit'  => array( 'label' => 'Gestion du linge — petit volume', 'prix' => 15, 'groupe' => 'linge' ),
		'linge-moyen'  => array( 'label' => 'Gestion du linge — volume moyen', 'prix' => 25, 'groupe' => 'linge' ),
		'linge-gros'   => array( 'label' => 'Gestion du linge — gros volume', 'prix' => 40, 'groupe' => 'linge' ),
		'frigo'        => array( 'label' => 'Nettoyage intérieur frigo', 'prix' => 10, 'groupe' => '' ),
		'four'         => array( 'label' => 'Nettoyage intérieur four', 'prix' => 15, 'groupe' => '' ),
		'petit-balcon' => array( 'label' => 'Petit balcon', 'prix' => 10, 'groupe' => 'balcon' ),
		'terrasse'     => array( 'label' => 'Terrasse ou grand balcon', 'prix' => 30, 'groupe' => 'balcon' ),
	);

	/** Majorations cumulables, en % de (base + options). */
	const MAJORATIONS = array(
		'urgence'        => array( 'label' => 'Intervention urgente (< 48 h)', 'taux' => 0.20 ),
		'dimanche-ferie' => array( 'label' => 'Dimanche ou jour férié', 'taux' => 0.25 ),
	);

	const ACOMPTE_PCT = 30;

	/**
	 * Calcule un forfait logement à partir de la demande du client.
	 * Tous les montants sont en CENTIMES (entiers) pour éviter les flottants.
	 *
	 * @param array $booking ['forfait' => slug, 'taille' => slug,
	 *                        'options' => [slugs], 'majorations' => [slugs]]
	 * @return array{label:string,taille:string,total_cents:int,acompte_cents:int,solde_cents:int,lignes:array}
	 * @throws InvalidArgumentException si la combinaison n'existe pas dans la grille.
	 */
	public static function compute_forfait( array $booking ) {
		$forfait = isset( $booking['forfait'] ) ? (string) $booking['forfait'] : '';
		$taille  = isset( $booking['taille'] ) ? (string) $booking['taille'] : '';

		if ( ! isset( self::FORFAITS[ $forfait ] ) ) {
			throw new InvalidArgumentException( 'Forfait inconnu : ' . $forfait );
		}
		$grille = self::FORFAITS[ $forfait ];
		if ( ! isset( $grille['prix'][ $taille ] ) ) {
			throw new InvalidArgumentException( 'Taille inconnue : ' . $taille );
		}

		$base_cents = (int) round( $grille['prix'][ $taille ] * 100 );
		$lignes     = array(
			array(
				'label'   => $grille['label'] . ' — ' . self::TAILLES[ $taille ],
				'montant' => $base_cents / 100,
			),
		);

		$options       = isset( $booking['options'] ) && is_array( $booking['options'] ) ? $booking['options'] : array();
		$groupes_vus   = array();
		$options_cents = 0;
		foreach ( $options as $key ) {
			$key = (string) $key;
			if ( ! isset( self::OPTIONS[ $key ] ) ) {
				throw new InvalidArgumentException( 'Option inconnue : ' . $key );
			}
			$opt    = self::OPTIONS[ $key ];
			$groupe = $opt['groupe'];
			if ( $groupe && isset( $groupes_vus[ $groupe ] ) ) {
				throw new InvalidArgumentException( 'Options incompatibles (groupe ' . $groupe . ')' );
			}
			if ( $groupe ) {
				$groupes_vus[ $groupe ] = true;
			}
			$options_cents += (int) round( $opt['prix'] * 100 );
			$lignes[]       = array( 'label' => $opt['label'], 'montant' => $opt['prix'] );
		}

		$sous_total_cents = $base_cents + $options_cents;

		$majorations = isset( $booking['majorations'] ) && is_array( $booking['majorations'] ) ? $booking['majorations'] : array();
		$taux_total  = 0.0;
		foreach ( array_unique( $majorations ) as $key ) {
			$key = (string) $key;
			if ( ! isset( self::MAJORATIONS[ $key ] ) ) {
				throw new InvalidArgumentException( 'Majoration inconnue : ' . $key );
			}
			$maj         = self::MAJORATIONS[ $key ];
			$taux_total += $maj['taux'];
			$lignes[]    = array(
				'label'   => $maj['label'] . ' (+' . round( $maj['taux'] * 100 ) . ' %)',
				'montant' => round( $sous_total_cents * $maj['taux'] ) / 100,
			);
		}

		$total_cents   = (int) round( $sous_total_cents * ( 1 + $taux_total ) );
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

	/**
	 * Montant à encaisser maintenant, en centimes, selon le mode choisi.
	 *
	 * @param array  $booking Demande client (voir compute_forfait).
	 * @param string $mode    'deposit' (acompte 30 %) ou 'full' (totalité).
	 */
	public static function amount_now_cents( array $booking, $mode ) {
		$devis = self::compute_forfait( $booking );
		return ( 'full' === $mode ) ? $devis['total_cents'] : $devis['acompte_cents'];
	}
}
