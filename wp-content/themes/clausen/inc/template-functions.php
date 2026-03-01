<?php
/**
 * Template helper functions
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Hero illustration ──────────────────────────────────────────────────────

/**
 * Return the hero image URL. Checks ACF Theme Options first, falls back to bundled SVG.
 */
function tclas_hero_url( string $size = 'desktop' ): string {
	if ( function_exists( 'get_field' ) ) {
		$field  = $size === 'mobile' ? 'hero_illustration_mobile' : 'hero_illustration';
		$img    = get_field( $field, 'option' );
		if ( $img && ! empty( $img['url'] ) ) {
			return esc_url( $img['url'] );
		}
	}
	$file = 'hero.svg'; // Single SVG serves both sizes until a mobile version is uploaded via ACF
	return TCLAS_ASSETS . '/images/' . $file;
}

/**
 * Render hero section background.
 *
 * When hero_pairs ACF data is present, renders the Figma split-screen hero:
 *   .tclas-hero__background-desktop — two clip-path sides, each with all slides
 *   .tclas-hero__background-mobile  — single-image crossfade slides
 *
 * Falls back to the illustration / SVG placeholder when no pairs are set.
 */
function tclas_render_hero_bg(): void {
	if ( function_exists( 'get_field' ) ) {
		$pairs = get_field( 'hero_pairs', 'option' );
		if ( is_array( $pairs ) && ! empty( $pairs ) ) {

			// ── Desktop: two clip-path sides (MN left, LUX right) ────────────
			echo '<div class="tclas-hero__background-desktop">';

			// Left side — Minnesota, slides DOWN.
			echo '<div class="tclas-hero__side tclas-hero__side--left">';
			foreach ( $pairs as $index => $pair ) {
				$img    = ! empty( $pair['mn_photo'] ) ? $pair['mn_photo'] : null;
				$city   = isset( $pair['mn_municipality'] ) ? trim( $pair['mn_municipality'] ) : '';
				$credit = isset( $pair['mn_credit'] )       ? trim( $pair['mn_credit'] )       : '';
				$active = $index === 0 ? ' data-active="true"' : '';
				echo '<div class="tclas-hero__image-slide" data-index="' . esc_attr( $index ) . '" data-side="minnesota"' . $active . '>';
				if ( $img && ! empty( $img['url'] ) ) {
					$src    = esc_url( ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $img['url'] );
					$srcset = ! empty( $img['sizes']['large'] )
						? ' srcset="' . esc_url( $img['sizes']['large'] ) . ' 1024w, ' . esc_url( $img['url'] ) . ' ' . (int) $img['width'] . 'w"'
						: '';
					echo '<img src="' . $src . '"' . $srcset . ' sizes="(min-width: 768px) 50vw, 100vw" alt="" aria-hidden="true" loading="' . ( $index === 0 ? 'eager' : 'lazy' ) . '">';
				}
				if ( $city ) {
					echo '<div class="tclas-hero__label tclas-hero__label--left">';
					echo '<p class="tclas-hero__label-city">' . esc_html( $city ) . ', MN</p>';
					if ( $credit ) {
						echo '<p class="tclas-hero__label-credit">' . esc_html( $credit ) . '</p>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
			echo '</div>'; // side--left

			// Right side — Luxembourg, slides UP.
			echo '<div class="tclas-hero__side tclas-hero__side--right">';
			foreach ( $pairs as $index => $pair ) {
				$img    = ! empty( $pair['lux_photo'] ) ? $pair['lux_photo'] : null;
				$city   = isset( $pair['lux_municipality'] ) ? trim( $pair['lux_municipality'] ) : '';
				$credit = isset( $pair['lux_credit'] )       ? trim( $pair['lux_credit'] )       : '';
				$active = $index === 0 ? ' data-active="true"' : '';
				echo '<div class="tclas-hero__image-slide" data-index="' . esc_attr( $index ) . '" data-side="luxembourg"' . $active . '>';
				if ( $img && ! empty( $img['url'] ) ) {
					$src    = esc_url( ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $img['url'] );
					$srcset = ! empty( $img['sizes']['large'] )
						? ' srcset="' . esc_url( $img['sizes']['large'] ) . ' 1024w, ' . esc_url( $img['url'] ) . ' ' . (int) $img['width'] . 'w"'
						: '';
					echo '<img src="' . $src . '"' . $srcset . ' sizes="(min-width: 768px) 50vw, 100vw" alt="" aria-hidden="true" loading="' . ( $index === 0 ? 'eager' : 'lazy' ) . '">';
				}
				if ( $city ) {
					echo '<div class="tclas-hero__label tclas-hero__label--right">';
					echo '<p class="tclas-hero__label-city">' . esc_html( $city ) . '</p>';
					if ( $credit ) {
						echo '<p class="tclas-hero__label-credit">' . esc_html( $credit ) . '</p>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
			echo '</div>'; // side--right

			echo '</div>'; // background-desktop

			// ── Mobile: single crossfade image ───────────────────────────────
			echo '<div class="tclas-hero__background-mobile">';
			foreach ( $pairs as $index => $pair ) {
				// Prefer dedicated mobile_image; fall back to lux_photo.
				$img = ! empty( $pair['mobile_image'] )
					? $pair['mobile_image']
					: ( ! empty( $pair['lux_photo'] ) ? $pair['lux_photo'] : null );
				$active = $index === 0 ? ' data-active="true"' : '';
				echo '<div class="tclas-hero__image-slide" data-index="' . esc_attr( $index ) . '"' . $active . '>';
				if ( $img && ! empty( $img['url'] ) ) {
					$src = esc_url( ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $img['url'] );
					echo '<img src="' . $src . '" sizes="100vw" alt="" aria-hidden="true" loading="' . ( $index === 0 ? 'eager' : 'lazy' ) . '">';
					echo '<div class="tclas-hero__mobile-gradient" aria-hidden="true"></div>';
				}
				echo '</div>';
			}
			echo '</div>'; // background-mobile

			return;
		}
	}

	// Fallback: illustration / SVG placeholder.
	$desktop_url = tclas_hero_url( 'desktop' );
	$mobile_url  = tclas_hero_url( 'mobile' );
	echo '<picture class="tclas-hero__bg">';
	echo '<source media="(max-width: 600px)" srcset="' . esc_url( $mobile_url ) . '">';
	echo '<img src="' . esc_url( $desktop_url ) . '" alt="" aria-hidden="true" loading="eager">';
	echo '</picture>';
}

// ── Illustration placeholders ──────────────────────────────────────────────

/**
 * Render an illustration slot. Shows ACF image if set, otherwise placeholder.
 *
 * @param string $acf_field  ACF field name in Theme Options.
 * @param string $alt        Image alt text.
 * @param string $class      Additional CSS class.
 */
function tclas_illustration( string $acf_field, string $alt = '', string $class = '' ): void {
	$img = null;
	if ( function_exists( 'get_field' ) ) {
		$img = get_field( $acf_field, 'option' );
	}

	$wrapper_class = 'tclas-illustration-placeholder ' . $class;

	if ( $img && ! empty( $img['url'] ) ) {
		echo '<div class="' . esc_attr( trim( $wrapper_class ) ) . '">';
		echo '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
		echo '</div>';
	} else {
		echo '<div class="' . esc_attr( trim( $wrapper_class ) ) . '" aria-hidden="true">';
		echo '<span class="sr-only">' . esc_html( $alt ) . '</span>';
		echo '</div>';
	}
}

// ── Membership / auth state ────────────────────────────────────────────────

/**
 * Return membership status for current user.
 * Values: 'none', 'active', 'expiring', 'expired'.
 */
function tclas_membership_status(): string {
	if ( ! is_user_logged_in() ) {
		return 'none';
	}
	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return 'active'; // If PMPro not active, treat as member
	}

	$user_id = get_current_user_id();

	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$level = pmpro_getMembershipLevelForUser( $user_id );
		if ( $level && ! empty( $level->enddate ) ) {
			$end = (int) $level->enddate;
			$now = time();
			if ( $end < $now ) {
				return 'expired';
			}
			if ( $end - $now < 30 * DAY_IN_SECONDS ) {
				return 'expiring';
			}
		}
	}

	return pmpro_hasMembershipLevel( null, $user_id ) ? 'active' : 'none';
}

/**
 * Return days until membership expiry, or 0 if not applicable.
 */
function tclas_days_to_expiry(): int {
	if ( ! is_user_logged_in() || ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return 0;
	}
	$level = pmpro_getMembershipLevelForUser( get_current_user_id() );
	if ( ! $level || empty( $level->enddate ) ) {
		return 0;
	}
	return max( 0, (int) ceil( ( (int) $level->enddate - time() ) / DAY_IN_SECONDS ) );
}

/**
 * Is the current user a TCLAS member?
 */
function tclas_is_member(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	// Admins can always access member-only content (for previewing/QA).
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return true;
	}
	return (bool) pmpro_hasMembershipLevel();
}

// ── Navigation CTA ────────────────────────────────────────────────────────

/**
 * Render the header action area (join/login/account/renew).
 */
function tclas_render_header_actions(): void {
	$status = tclas_membership_status();

	switch ( $status ) {
		case 'active':
			$account_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : get_permalink( get_option( 'pmpro_account_page_id' ) );
			$name        = wp_get_current_user()->display_name;
			echo '<a href="' . esc_url( $account_url ) . '" class="tclas-header__account">';
			echo get_avatar( get_current_user_id(), 30, '', '', [ 'class' => 'tclas-header__avatar' ] );
			echo '<span>' . esc_html__( 'My account', 'tclas' ) . '</span>';
			echo '</a>';
			break;

		case 'expiring':
		case 'expired':
			$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#join';
			echo '<a href="' . esc_url( $renew_url ) . '" class="btn btn-primary btn-sm tclas-header__join">';
			echo esc_html__( 'Renew my membership', 'tclas' );
			echo '</a>';
			break;

		default: // 'none'
			$join_url  = home_url( '/join/' );
			$login_url = wp_login_url( get_permalink() );
			echo '<a href="' . esc_url( $join_url ) . '" class="btn btn-primary btn-sm tclas-header__join">' . esc_html__( 'Join', 'tclas' ) . '</a>';
			echo '<a href="' . esc_url( $login_url ) . '" class="tclas-header__login">' . esc_html__( 'Log in', 'tclas' ) . '</a>';
			break;
	}
}

// ── Lëtzebuergesch helpers ─────────────────────────────────────────────────

/**
 * Return a time-appropriate Luxembourgish greeting as an HTML string.
 *
 * Hours evaluated in the Europe/Luxembourg timezone:
 *   00–10  Gudde Moien  (Good morning)
 *   11–16  Moien        (Good day)
 *   17–20  Gudden Owend (Good evening)
 *   21–23  Gudde Nuecht (Good night)
 *
 * Wrapped in <span lang="lb"><abbr title="…"> for semantics + accessibility.
 */
function tclas_lux_greeting(): string {
	try {
		$tz   = new DateTimeZone( 'Europe/Luxembourg' );
		$now  = new DateTime( 'now', $tz );
		$hour = (int) $now->format( 'G' );
	} catch ( \Exception $e ) {
		$hour = 12; // Safe fallback → "Moien"
	}

	if ( $hour < 11 ) {
		$phrase      = 'Gudde Moien';
		$translation = 'Good morning';
	} elseif ( $hour < 17 ) {
		$phrase      = 'Moien';
		$translation = 'Good day';
	} elseif ( $hour < 21 ) {
		$phrase      = 'Gudden Owend';
		$translation = 'Good evening';
	} else {
		$phrase      = 'Gudde Nuecht';
		$translation = 'Good night';
	}

	return '<span lang="lb"><abbr class="ltz" title="' . esc_attr( $translation ) . '">' . esc_html( $phrase ) . '</abbr></span>';
}

/**
 * Wrap a Lëtzebuergesch phrase with translation tooltip.
 */
function tclas_ltz( string $phrase, string $translation, bool $echo = true ): string {
	$html = '<span lang="lb"><abbr class="ltz" tabindex="0" title="' . esc_attr( $translation ) . '">' . esc_html( $phrase ) . '</abbr></span>';
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	return $html;
}

// ── Card helpers ──────────────────────────────────────────────────────────

/**
 * Return a post category icon emoji based on category slug.
 */
function tclas_category_icon( string $slug = '' ): string {
	$icons = [
		'food-drink'    => '🍷',
		'travel'        => '✈️',
		'language'      => '💬',
		'citizenship'   => '📄',
		'events'        => '📅',
		'member-story'  => '👤',
		'news'          => '📰',
		'genealogy'     => '🌳',
		'culture'       => '🎭',
		'default'       => '',
	];
	return $icons[ $slug ] ?? $icons['default'];
}

/**
 * Return post reading time in minutes.
 */
function tclas_reading_time( int $post_id = 0 ): int {
	$content    = get_post_field( 'post_content', $post_id ?: get_the_ID() );
	$word_count = str_word_count( wp_strip_all_tags( $content ) );
	return max( 1, (int) ceil( $word_count / 200 ) );
}

// ── Structured data ────────────────────────────────────────────────────────

/**
 * Output organisation JSON-LD.
 */
function tclas_json_ld(): void {
	$data = [
		'@context'  => 'https://schema.org',
		'@type'     => 'Organization',
		'name'      => 'Twin Cities Luxembourg American Society',
		'alternateName' => 'TCLAS',
		'url'       => home_url(),
		'logo'      => get_custom_logo() ? wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ) : '',
		'sameAs'    => [
			'https://www.facebook.com/groups/tclas',
		],
	];
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'tclas_json_ld' );

// ── Transient cache busting ────────────────────────────────────────────────

/**
 * Clear cached query transients when a post is published or updated.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function tclas_bust_post_transients( int $post_id, WP_Post $post ): void {
	if ( 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	delete_transient( 'tclas_homepage_posts' );
	delete_transient( 'tclas_related_' . $post_id );
}
add_action( 'save_post', 'tclas_bust_post_transients', 10, 2 );
