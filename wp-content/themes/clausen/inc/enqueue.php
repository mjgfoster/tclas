<?php
/**
 * Enqueue scripts & styles
 *
 * Pre-compiled theme — no build step required.
 * Google Fonts (Source Sans 3 + Vollkorn) and Bootstrap loaded from CDN.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_enqueue_assets(): void {

	// Filesystem path used for filemtime() version strings.
	// filemtime() automatically busts the browser cache whenever a file changes,
	// so we never need to manually bump TCLAS_VERSION for asset updates.
	$dir = get_template_directory();

	// ── Google Fonts — Source Sans 3 (sans) + Ancizar Serif (serif) ─────
	// CSS vars: --font-sans (Source Sans 3), --font-serif (Ancizar Serif).
	// Weights: 400, 400i, 700, 700i for both fonts.
	wp_enqueue_style(
		'tclas-google-fonts',
		'https://fonts.googleapis.com/css2?family=Ancizar+Serif:ital,wght@0,400;0,700;1,400;1,700&family=Source+Sans+3:ital,wght@0,400;0,700;1,400;1,700&display=swap',
		[],
		null
	);

	// ── Bootstrap 5.3 CSS — CDN ───────────────────────────────────────────
	wp_enqueue_style(
		'bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
		[ 'tclas-google-fonts' ],
		null
	);

	// ── Compiled theme stylesheet ─────────────────────────────────────────
	wp_enqueue_style(
		'tclas-main',
		TCLAS_ASSETS . '/css/main.css',
		[ 'bootstrap' ],
		filemtime( $dir . '/assets/css/main.css' )
	);

	// Inject lion watermark SVG URL dynamically so path survives theme/domain moves.
	$lion_url = get_template_directory_uri() . '/assets/images/lion-watermark.svg';
	wp_add_inline_style(
		'tclas-main',
		"body::after { background-image: url('" . esc_url( $lion_url ) . "'); }"
	);

	// ── The Events Calendar overrides (TEC pages only) ────────────────────
	if ( class_exists( 'Tribe__Events__Main' ) &&
		( is_post_type_archive( Tribe__Events__Main::POSTTYPE ) || is_singular( Tribe__Events__Main::POSTTYPE ) || is_tax( Tribe__Events__Main::TAXONOMY ) )
	) {
		wp_enqueue_style(
			'tclas-tribe-events',
			TCLAS_ASSETS . '/css/tribe-events.css',
			[ 'tclas-main' ],
			filemtime( $dir . '/assets/css/tribe-events.css' )
		);
	}

	// ── Bootstrap 5.3 JS — CDN ────────────────────────────────────────────
	wp_enqueue_script(
		'bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
		[],
		null,
		true
	);

	// ── Theme JavaScript ──────────────────────────────────────────────────
	wp_enqueue_script(
		'tclas-main',
		TCLAS_ASSETS . '/js/main.js',
		[ 'bootstrap' ],
		filemtime( $dir . '/assets/js/main.js' ),
		true
	);

	wp_localize_script( 'tclas-main', 'tclasData', [
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'tclas_nonce' ),
		'isLoggedIn'  => is_user_logged_in(),
		'nationalDay' => tclas_get_national_day_data(),
		'themeUri'    => TCLAS_URI,
		'referralUrl' => tclas_get_referral_url(),
		'strings'     => [
			'openMenu'   => __( 'Open menu',   'tclas' ),
			'closeMenu'  => __( 'Close menu',  'tclas' ),
			'copied'     => __( 'Copied!',     'tclas' ),
			'copyUrl'    => __( 'Copy link',   'tclas' ),
			'formErrors' => __( 'Please correct the errors below.', 'tclas' ),
			// Connections feature
			'connDismissed'  => __( 'Connection dismissed.', 'tclas' ),
			'connSaving'     => __( 'Saving…', 'tclas' ),
			'connSaved'      => __( 'Saved!', 'tclas' ),
		],
	] );

	// ── Hero scripts (front page only) ───────────────────────────────────
	if ( is_front_page() ) {
		wp_enqueue_script(
			'tclas-hero',
			TCLAS_ASSETS . '/js/hero-slideshow.js',
			[],
			filemtime( $dir . '/assets/js/hero-slideshow.js' ),
			true
		);
		wp_enqueue_script(
			'tclas-hero-greeting',
			TCLAS_ASSETS . '/js/hero-greeting.js',
			[],
			filemtime( $dir . '/assets/js/hero-greeting.js' ),
			true
		);
	}

	// ── MSP+LUX counter animation (MSP+LUX page only) ────────────────────
	if ( is_page_template( 'page-templates/page-msp-lux.php' ) ) {
		wp_enqueue_script(
			'tclas-msp-lux',
			TCLAS_ASSETS . '/js/msp-lux-counters.js',
			[],
			filemtime( $dir . '/assets/js/msp-lux-counters.js' ),
			true
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'tclas_enqueue_assets' );

/**
 * Editor assets.
 */
function tclas_enqueue_editor_assets(): void {
	wp_enqueue_style(
		'tclas-editor',
		TCLAS_ASSETS . '/css/main.css',
		[],
		filemtime( get_template_directory() . '/assets/css/main.css' )
	);
}
add_action( 'enqueue_block_editor_assets', 'tclas_enqueue_editor_assets' );

/**
 * Resource hints — preconnect to CDNs.
 */
function tclas_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = [ 'href' => 'https://cdn.jsdelivr.net',   'crossorigin' => '' ];
		$urls[] = [ 'href' => 'https://fonts.googleapis.com', 'crossorigin' => '' ];
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'tclas_resource_hints', 10, 2 );

/**
 * SiteGround Speed Optimizer exclusions.
 */
function tclas_speed_optimizer_exclusions( array $excluded ): array {
	$excluded[] = 'cdn.jsdelivr.net';
	$excluded[] = 'fonts.googleapis.com';
	return $excluded;
}
add_filter( 'sgo_js_minify_exclude',  'tclas_speed_optimizer_exclusions' );
add_filter( 'sgo_css_minify_exclude', 'tclas_speed_optimizer_exclusions' );
