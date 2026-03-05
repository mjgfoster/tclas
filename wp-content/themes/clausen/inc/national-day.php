<?php
/**
 * Lëtzebuerger Nationalfeierdag (June 23) detection
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Return national day data for JS localisation.
 */
function tclas_get_national_day_data(): array {
	$is_season = tclas_is_national_day_season();
	return [
		'isNationalDaySeason' => $is_season,
		'date'                => 'June 23',
	];
}

/**
 * Is it currently National Day season?
 * Within 7 days before or 1 day after June 23.
 * Also honours the ACF manual override.
 */
function tclas_is_national_day_season(): bool {
	if ( function_exists( 'get_field' ) && get_field( 'national_day_mode', 'option' ) ) {
		return true;
	}
	$now   = current_datetime();
	$month = (int) $now->format( 'n' );
	$day   = (int) $now->format( 'j' );
	if ( $month !== 6 ) {
		return false;
	}
	return $day >= 16 && $day <= 24;
}
