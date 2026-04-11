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
 * Convert image URL to modern format equivalent if it exists.
 * Replaces the file extension (e.g., image.jpg → image.avif or image.webp).
 *
 * @param string $url    The original image URL.
 * @param string $format The target format: 'avif' or 'webp'.
 * @return string The converted URL, or empty string if conversion not applicable.
 */
function tclas_get_modern_image_url( string $url, string $format = 'avif' ): string {
	// Don't convert SVG or data URLs
	if ( strpos( $url, '.svg' ) !== false || strpos( $url, 'data:' ) === 0 ) {
		return '';
	}
	// Replace common image extensions with the target format
	$converted = preg_replace( '/\.(jpg|jpeg|png|webp|avif)$/i', '.' . $format, $url );
	return $converted !== $url ? $converted : '';
}

/**
 * Backward compatibility wrapper for AVIF conversion.
 * @deprecated Use tclas_get_modern_image_url() instead.
 */
function tclas_get_avif_url( string $url ): string {
	return tclas_get_modern_image_url( $url, 'avif' );
}

/**
 * Render a stack of hero photo slides for one side (minnesota or luxembourg).
 *
 * Used by the four-quadrant hero layout. Each photo cell contains all slides
 * for that side, stacked absolutely. JS cycles through them.
 *
 * @param string $side 'minnesota' or 'luxembourg'.
 */
function tclas_render_hero_photo_stack( string $side ): void {
	$pairs = function_exists( 'get_field' ) ? get_field( 'hero_pairs', 'option' ) : null;
	if ( ! is_array( $pairs ) || empty( $pairs ) ) {
		// Fallback: show illustration
		$url = tclas_hero_url( 'desktop' );
		echo '<div class="tclas-hero__photo-stack">';
		echo '<div class="tclas-hero__image-slide" data-index="0" data-side="' . esc_attr( $side ) . '" data-active="true">';
		echo '<img src="' . esc_url( $url ) . '" alt="" aria-hidden="true" loading="eager">';
		echo '</div>';
		echo '</div>';
		return;
	}

	$field_photo = $side === 'minnesota' ? 'mn_photo' : 'lux_photo';
	$field_city  = $side === 'minnesota' ? 'mn_municipality' : 'lux_municipality';
	$field_credit = $side === 'minnesota' ? 'mn_credit' : 'lux_credit';
	$label_pos   = $side === 'minnesota' ? 'left' : 'right';

	echo '<div class="tclas-hero__photo-stack" data-side="' . esc_attr( $side ) . '">';
	foreach ( $pairs as $index => $pair ) {
		$img    = ! empty( $pair[ $field_photo ] ) ? $pair[ $field_photo ] : null;
		$city   = isset( $pair[ $field_city ] )  ? trim( $pair[ $field_city ] )  : '';
		$credit = isset( $pair[ $field_credit ] ) ? trim( $pair[ $field_credit ] ) : '';
		$active = $index === 0 ? ' data-active="true"' : '';

		echo '<div class="tclas-hero__image-slide" data-index="' . esc_attr( $index ) . '" data-side="' . esc_attr( $side ) . '"' . $active . '>';
		if ( $img && ! empty( $img['url'] ) ) {
			$src    = esc_url( ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $img['url'] );
			$srcset = ! empty( $img['sizes']['large'] )
				? ' srcset="' . esc_url( $img['sizes']['large'] ) . ' 1024w, ' . esc_url( $img['url'] ) . ' ' . (int) $img['width'] . 'w"'
				: '';
			echo '<img src="' . $src . '"' . $srcset . ' sizes="(min-width: 768px) 62vw, 100vw" alt="" aria-hidden="true" loading="' . ( $index === 0 ? 'eager' : 'lazy' ) . '">';
		}
		if ( $city ) {
			echo '<div class="tclas-hero__label tclas-hero__label--' . esc_attr( $label_pos ) . '">';
			echo '<p class="tclas-hero__label-city">' . esc_html( $city ) . '</p>';
			if ( $credit ) {
				echo '<p class="tclas-hero__label-credit">' . esc_html( $credit ) . '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
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

// ── Custom SVG sprite helper ──────────────────────────────────────────────

/**
 * Output an SVG icon from the custom sprite.
 *
 * @param string $name  Symbol ID without the "icon-" prefix (e.g. 'flag-lux', 'roude-leiw', 'flag-mn').
 * @param string $class Extra CSS classes (e.g. 'tclas-icon--lg tclas-icon--flag').
 * @param string $label Accessible label. If empty, icon is aria-hidden.
 */
function tclas_icon( string $name, string $class = '', string $label = '' ): void {
	$href  = get_theme_file_uri( 'assets/images/icons.svg' ) . '#icon-' . $name;
	$attrs = $label
		? 'role="img" aria-label="' . esc_attr( $label ) . '"'
		: 'aria-hidden="true"';
	printf(
		'<svg class="tclas-icon %s" %s><use href="%s"></use></svg>',
		esc_attr( $class ),
		$attrs,
		esc_url( $href )
	);
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

/**
 * Output a "Members only" badge (lock icon + label).
 *
 * Call with a post ID; outputs nothing if the post isn't members-only.
 *
 * @param int $post_id  Post ID to check.
 */
function tclas_members_only_badge( int $post_id ): void {
	if ( ! get_post_meta( $post_id, '_tclas_members_only', true ) ) {
		return;
	}
	$icon = '<svg aria-hidden="true" focusable="false" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	echo '<span class="tclas-members-badge">';
	echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<span>' . esc_html__( 'Members', 'tclas' ) . '</span>';
	echo '</span>';
}

/**
 * Is the current page part of the members-only area?
 *
 * Used to conditionally display the member navigation bar.
 */
function tclas_is_member_page(): bool {
	if ( is_page_template( 'page-templates/page-member-hub.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-member-profiles.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-my-story.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-edit-profile.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-ancestral-map-edit.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-privacy-settings.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-messages.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-map.php' ) ) {
		return true;
	}
	if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
		return true;
	}
	if ( is_tax( 'tclas_commune' ) ) {
		return true;
	}
	// Check for /member-hub/* URLs
	if ( strpos( $_SERVER['REQUEST_URI'], '/member-hub/' ) !== false ) {
		return true;
	}
	return false;
}

// ── Body class for member pages ───────────────────────────────────────────

add_filter( 'body_class', function ( array $classes ): array {
	if ( tclas_is_member_page() ) {
		$classes[] = 'is-member-page';
	}
	return $classes;
} );

// ── Newsletter page detection ─────────────────────────────────────────────

/**
 * Is the current page part of the newsletter section?
 *
 * Used to conditionally display the newsletter sub-navigation bar.
 */
function tclas_is_newsletter_page(): bool {
	if ( is_page_template( 'page-templates/page-newsletter.php' ) ) {
		return true;
	}
	if ( is_page_template( 'page-templates/page-newsletter-archive.php' ) ) {
		return true;
	}
	if ( get_query_var( 'tclas_newsletter_issue' ) ) {
		return true;
	}
	if ( is_tax( 'tclas_department' ) ) {
		return true;
	}
	// Individual newsletter articles (posts with an issue date).
	if ( is_singular( 'post' ) && get_post_meta( get_the_ID(), 'tclas_issue_date', true ) ) {
		return true;
	}
	return false;
}

// ── Member navigation links ──────────────────────────────────────────────

/**
 * Return the member hub navigation links array.
 *
 * Shared between the desktop header dropdown and the mobile nav drawer.
 *
 * @return array<int, array{label: string, url: string, icon: string}>
 */
function tclas_get_member_nav_links(): array {
	$user        = wp_get_current_user();
	$profile_url = home_url( '/member-hub/profiles/' . rawurlencode( $user->user_nicename ) . '/' );

	return [
		[ 'label' => __( 'Member Hub', 'tclas' ),       'url' => home_url( '/member-hub/' ),              'icon' => 'bi-house-door-fill' ],
		[ 'label' => __( 'My Member Profile', 'tclas' ),'url' => $profile_url,                           'icon' => 'bi-person-circle' ],
		[ 'label' => __( 'Member Directory', 'tclas' ), 'url' => home_url( '/member-hub/profiles/' ),      'icon' => 'bi-people-fill' ],
		[ 'label' => __( 'Ancestral Map', 'tclas' ),    'url' => home_url( '/member-hub/ancestral-map/' ), 'icon' => 'bi-map-fill' ],
		[ 'label' => __( 'Documents', 'tclas' ),        'url' => home_url( '/member-hub/documents/' ),     'icon' => 'bi-file-earmark-text' ],
		[ 'label' => __( 'Forum',     'tclas' ),        'url' => home_url( '/member-hub/forums/' ),        'icon' => 'bi-chat-left-text-fill' ],
		[ 'label' => __( 'Map',       'tclas' ), 'url' => home_url( '/member-hub/ancestral-map/' ), 'icon' => 'bi-map-fill' ],
	];
}

// ── Navigation CTA ────────────────────────────────────────────────────────

/**
 * Render the header utility action.
 *
 * - Anonymous users: login link.
 * - Members (desktop): "Moien, Name ▾" dropdown with hub links.
 * - Members (mobile): hub links inline in the nav drawer.
 *
 * @param bool $mobile  True when rendering inside the mobile drawer.
 */
function tclas_render_header_actions( bool $mobile = false ): void {
	if ( ! is_user_logged_in() ) {
		$login_url = wp_login_url( get_permalink() );

		if ( $mobile ) {
			echo '<a href="' . esc_url( $login_url ) . '" class="tclas-nav-drawer__login-link">' . esc_html__( 'Member Log In', 'tclas' ) . '</a>';
		} else {
			echo '<a href="' . esc_url( $login_url ) . '" class="tclas-header__login">' . esc_html__( 'Member Log In', 'tclas' ) . '</a>';
		}
		return;
	}

	// ── Logged-in member ─────────────────────────────────────────────────
	$user       = wp_get_current_user();
	$first_name = ! empty( $user->user_firstname ) ? $user->user_firstname : __( 'Member', 'tclas' );
	$links      = tclas_get_member_nav_links();
	$logout_url = wp_logout_url( home_url() );
	$chevron    = '<svg class="tclas-member-dropdown__chevron" aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';

	if ( $mobile ) {
		// ── Mobile: links in nav drawer ──────────────────────────────────
		echo '<div class="tclas-nav-drawer__member">';
		echo '<span class="tclas-nav-drawer__greeting">' . esc_html( sprintf( __( 'Moien, %s', 'tclas' ), $first_name ) ) . '</span>';

		foreach ( $links as $link ) {
			echo '<a href="' . esc_url( $link['url'] ) . '" class="tclas-nav-drawer__member-link">';
			echo '<i class="bi ' . esc_attr( $link['icon'] ) . '" aria-hidden="true"></i>';
			echo '<span>' . esc_html( $link['label'] ) . '</span>';
			echo '</a>';
		}

		echo '<a href="' . esc_url( $logout_url ) . '" class="tclas-nav-drawer__member-link tclas-nav-drawer__member-logout">';
		echo '<i class="bi bi-box-arrow-right" aria-hidden="true"></i>';
		echo '<span>' . esc_html__( 'Log out', 'tclas' ) . '</span>';
		echo '</a>';
		echo '</div>';
	} else {
		// ── Desktop: dropdown in header utility area ─────────────────────
		echo '<div class="tclas-member-dropdown">';

		echo '<button class="tclas-member-dropdown__trigger" aria-expanded="false" aria-controls="tclas-member-dropdown-menu" aria-haspopup="true">';
		echo '<span>' . esc_html( sprintf( __( 'Moien, %s', 'tclas' ), $first_name ) ) . '</span>';
		echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</button>';

		echo '<ul class="tclas-member-dropdown__menu" id="tclas-member-dropdown-menu" role="menu">';
		foreach ( $links as $link ) {
			echo '<li role="none">';
			echo '<a role="menuitem" href="' . esc_url( $link['url'] ) . '" class="tclas-member-dropdown__link">';
			echo '<i class="bi ' . esc_attr( $link['icon'] ) . '" aria-hidden="true"></i>';
			echo '<span>' . esc_html( $link['label'] ) . '</span>';
			echo '</a>';
			echo '</li>';
		}
		echo '<li role="none" class="tclas-member-dropdown__separator"></li>';
		echo '<li role="none">';
		echo '<a role="menuitem" href="' . esc_url( $logout_url ) . '" class="tclas-member-dropdown__link tclas-member-dropdown__logout">';
		echo '<i class="bi bi-box-arrow-right" aria-hidden="true"></i>';
		echo '<span>' . esc_html__( 'Log out', 'tclas' ) . '</span>';
		echo '</a>';
		echo '</li>';
		echo '</ul>';

		echo '</div>';
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

/**
 * Shortcode wrapper for tclas_ltz().
 *
 * Usage (phrase as content, translation as attribute):
 *   [ltz t="Hello"]Moien[/ltz]
 *   [ltz t="We are here"]Mir sinn hei[/ltz]
 *
 * The full attribute name "translation" is also accepted:
 *   [ltz translation="Hello"]Moien[/ltz]
 */
function tclas_ltz_shortcode( array $atts, ?string $content = null ): string {
	$atts = shortcode_atts(
		[
			't'           => '',
			'translation' => '',
		],
		$atts,
		'ltz'
	);

	$phrase      = trim( $content ?? '' );
	$translation = trim( $atts['t'] ?: $atts['translation'] );

	if ( ! $phrase || ! $translation ) {
		return $phrase; // Render plain text if either part is missing.
	}

	return tclas_ltz( $phrase, $translation, false );
}
add_shortcode( 'ltz', 'tclas_ltz_shortcode' );

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
 *
 * TODO: Consult with board/stakeholders about:
 * - telephone: Organization phone number
 * - email: Organization email address
 * - address: Full postal address (street, city, postal code, country)
 * - founder: Names of founders (if applicable)
 * - member: Board members and their roles (using Person schema)
 * - areaServed: Geographic regions served (US and Luxembourg)
 * See: https://schema.org/Organization
 */
function tclas_json_ld(): void {
	$logo_url = '';
	if ( get_custom_logo() ) {
		$logo_url = wp_get_attachment_url( get_theme_mod( 'custom_logo' ) );
	}

	$data = [
		'@context'       => 'https://schema.org',
		'@type'          => 'Organization',
		'name'           => 'Twin Cities Luxembourg American Society',
		'alternateName'  => 'TCLAS',
		'url'            => home_url(),
		'logo'           => $logo_url ?: null,
		'sameAs'         => [
			'https://www.facebook.com/groups/tclas',
		],
		// TODO: Uncomment and fill in after consulting with stakeholders
		// 'telephone'      => '+1-XXX-XXX-XXXX',
		// 'email'          => 'contact@example.com',
		// 'address'        => [
		// 	'@type'        => 'PostalAddress',
		// 	'streetAddress' => '',
		// 	'addressLocality' => '',
		// 	'addressRegion'  => '',
		// 	'postalCode'     => '',
		// 	'addressCountry' => 'US',
		// ],
		// 'areaServed'     => ['US', 'LU'],
		// 'member'         => [ /* array of Person objects for board members */ ],
	];

	// Remove null values for cleaner output
	$data = array_filter( $data, function( $value ) {
		return $value !== null && $value !== '';
	} );

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
