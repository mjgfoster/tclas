<?php
/**
 * TCLAS homepage culture corner — Wuert vun der Woch + Elo zu Lëtzebuerg
 *
 * Two self-rotating homepage cards with zero ongoing admin:
 *
 *  - Wuert vun der Woch: deterministic weekly rotation through the fixed
 *    word list in rotator-data.php (ISO week number — same word all week,
 *    cache-friendly). LOD.lu pronunciation audio when available.
 *
 *  - Elo zu Lëtzebuerg: what's happening (or next up) on Luxembourg's
 *    annual traditions calendar. Fixed dates, Easter offsets, and
 *    nth-weekday rules computed per year — the box is always about the
 *    present or near future, never stale, and never empty.
 *
 * No DB, no CPTs, no archive pages: these are homepage widgets, full stop.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Assets ────────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'tclas_register_rotator_assets' );
function tclas_register_rotator_assets(): void {
	wp_register_style(
		'tclas-rotators',
		get_template_directory_uri() . '/assets/css/tclas-rotators.css',
		[],
		filemtime( get_template_directory() . '/assets/css/tclas-rotators.css' )
	);
	wp_register_script(
		'tclas-rotators',
		get_template_directory_uri() . '/assets/js/tclas-rotators.js',
		[],
		filemtime( get_template_directory() . '/assets/js/tclas-rotators.js' ),
		true
	);
}

// ── Pickers ───────────────────────────────────────────────────────────────────

/**
 * This week's word: ISO year-week keyed rotation, stable Monday→Sunday.
 *
 * @return array{word: string, en: string, note: string}
 */
function tclas_get_wuert_of_week(): array {
	$words = tclas_wuert_list();
	$now   = current_time( 'timestamp' );
	$index = ( (int) date( 'o', $now ) * 53 + (int) date( 'W', $now ) ) % count( $words );
	return $words[ $index ];
}

/**
 * Easter Sunday timestamp for a year (local noon, avoids TZ edge cases).
 * Uses ext/calendar when present, Anonymous Gregorian algorithm otherwise.
 */
function tclas_easter_ts( int $year ): int {
	if ( function_exists( 'easter_days' ) ) {
		return strtotime( sprintf( '%d-03-21 12:00 +%d days', $year, easter_days( $year ) ) );
	}
	// Anonymous Gregorian computus fallback.
	$a = $year % 19; $b = intdiv( $year, 100 ); $c = $year % 100;
	$d = intdiv( $b, 4 ); $e = $b % 4; $f = intdiv( $b + 8, 25 );
	$g = intdiv( $b - $f + 1, 3 ); $h = ( 19 * $a + $b - $d - $g + 15 ) % 30;
	$i = intdiv( $c, 4 ); $k = $c % 4; $l = ( 32 + 2 * $e + 2 * $i - $h - $k ) % 7;
	$m = intdiv( $a + 11 * $h + 22 * $l, 451 );
	$month = intdiv( $h + $l - 7 * $m + 114, 31 );
	$day   = ( ( $h + $l - 7 * $m + 114 ) % 31 ) + 1;
	return strtotime( sprintf( '%d-%02d-%02d 12:00', $year, $month, $day ) );
}

/**
 * Resolve a tradition's start timestamp for a given year.
 */
function tclas_tradition_start( array $rule, int $year ): int {
	switch ( $rule[0] ) {
		case 'fixed':
			return strtotime( sprintf( '%d-%02d-%02d 12:00', $year, $rule[1], $rule[2] ) );
		case 'easter':
			return tclas_easter_ts( $year ) + ( $rule[1] * DAY_IN_SECONDS );
		case 'nth':
			return strtotime( $rule[1] . ' ' . $year . ' 12:00' );
	}
	return 0;
}

/**
 * The tradition happening now, or the next one coming up.
 *
 * @return array{lb: string, en: string, blurb: string, start: int, status: string}
 */
function tclas_get_current_tradition(): array {
	$today = strtotime( date( 'Y-m-d 12:00', current_time( 'timestamp' ) ) );
	$year  = (int) date( 'Y', $today );

	$best = null;
	foreach ( tclas_traditions_list() as $t ) {
		// Check this year and next (handles December → January rollover).
		foreach ( [ $year, $year + 1 ] as $y ) {
			$start = tclas_tradition_start( $t['rule'], $y );
			$end   = $start + ( max( 1, $t['days'] ) - 1 ) * DAY_IN_SECONDS;

			if ( $today >= $start && $today <= $end ) {
				// Happening right now — instant winner.
				return $t + [ 'start' => $start, 'status' => 'now' ];
			}
			if ( $start > $today && ( null === $best || $start < $best['start'] ) ) {
				$best = $t + [ 'start' => $start, 'status' => 'upcoming' ];
			}
		}
	}

	return $best; // dataset spans the year — never null
}

// ── Render ────────────────────────────────────────────────────────────────────

/**
 * The homepage culture corner: two cards, no links to nowhere, no archive.
 * Called from front-page.php.
 */
function tclas_render_culture_corner(): void {
	$wuert     = tclas_get_wuert_of_week();
	$tradition = tclas_get_current_tradition();

	wp_enqueue_style( 'tclas-rotators' );

	// LOD.lu pronunciation (cached 7 days by lod-audio.php); single words only.
	$audio_url = null;
	if ( function_exists( 'tclas_get_commune_audio' ) && ! str_contains( $wuert['word'], ' ' ) ) {
		$audio_url = tclas_get_commune_audio( $wuert['word'], 'wuert-' . sanitize_title( $wuert['word'] ) );
	}
	if ( $audio_url ) {
		wp_enqueue_script( 'tclas-rotators' );
	}
	?>
	<section class="tclas-culture" aria-label="<?php esc_attr_e( 'Luxembourgish culture corner', 'tclas' ); ?>">
		<div class="container-tclas">
			<div class="tclas-culture__grid">

				<!-- Wuert vun der Woch -->
				<div class="tclas-culture__card">
					<span class="tclas-eyebrow"><?php esc_html_e( 'Wuert vun der Woch', 'tclas' ); ?></span>
					<p class="tclas-culture__word">
						<span lang="lb"><?php echo esc_html( $wuert['word'] ); ?></span>
						<?php if ( $audio_url ) : ?>
							<button type="button" class="tclas-culture__play" data-audio-src="<?php echo esc_url( $audio_url ); ?>"
							        aria-label="<?php echo esc_attr( sprintf( __( 'Listen to pronunciation of %s', 'tclas' ), $wuert['word'] ) ); ?>">
								<svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
							</button>
						<?php endif; ?>
					</p>
					<p class="tclas-culture__translation"><?php echo esc_html( $wuert['en'] ); ?></p>
					<p class="tclas-culture__note"><?php echo esc_html( $wuert['note'] ); ?></p>
					<?php if ( $audio_url ) : ?>
						<p class="tclas-culture__credit">
							<?php printf(
								/* translators: %s: link to LOD.lu */
								esc_html__( 'Audio from %s', 'tclas' ),
								'<a href="https://lod.lu" target="_blank" rel="noopener noreferrer">LOD.lu</a>'
							); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Elo zu Lëtzebuerg -->
				<div class="tclas-culture__card">
					<span class="tclas-eyebrow"><?php esc_html_e( 'Elo zu Lëtzebuerg', 'tclas' ); ?></span>
					<p class="tclas-culture__when">
						<?php if ( 'now' === $tradition['status'] ) : ?>
							<span class="tclas-culture__badge-now"><?php esc_html_e( 'Happening now', 'tclas' ); ?></span>
						<?php else : ?>
							<?php echo esc_html( sprintf(
								/* translators: %s: date */
								__( 'Coming up · %s', 'tclas' ),
								date_i18n( 'F j', $tradition['start'] )
							) ); ?>
						<?php endif; ?>
					</p>
					<p class="tclas-culture__word"><span lang="lb"><?php echo esc_html( $tradition['lb'] ); ?></span></p>
					<p class="tclas-culture__translation"><?php echo esc_html( $tradition['en'] ); ?></p>
					<p class="tclas-culture__note"><?php echo esc_html( $tradition['blurb'] ); ?></p>
				</div>

			</div>
		</div>
	</section>
	<?php
}
