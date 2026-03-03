<?php
/**
 * Newsletter Secondary Navigation
 *
 * Sticky sub-nav displayed only on newsletter-related pages. Includes a
 * branded "The Loon & The Lion" logo link, Current Issue / Previous Issues
 * links, and four featured topic links with Luxembourgish tooltips.
 *
 * Mobile: centred branding + issue links row + topics dropdown.
 * Desktop (≥768px): single flex row with separator dividers.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Determine current section ────────────────────────────────────────────────
$nl_section      = '';
$nl_topic_slug   = '';

if ( is_page_template( 'page-templates/page-newsletter.php' ) ) {
	$nl_section = 'current';
} elseif ( is_page_template( 'page-templates/page-newsletter-archive.php' ) ) {
	$nl_section = 'archives';
} elseif ( get_query_var( 'tclas_newsletter_issue' ) ) {
	$nl_section = 'archives'; // single issue → highlight "Previous Issues"
} elseif ( is_tax( 'tclas_department' ) ) {
	$nl_section    = 'topic';
	$nl_topic_slug = get_queried_object()->slug;
}

// ── Find the newsletter archive page URL ─────────────────────────────────────
$_archive_ids = get_posts( [
	'post_type'      => 'page',
	'meta_key'       => '_wp_page_template',
	'meta_value'     => 'page-templates/page-newsletter-archive.php',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );
$nl_archive_url = $_archive_ids ? get_permalink( $_archive_ids[0] ) : '';

// ── Featured topics shown in navigation ─────────────────────────────────────
$nl_featured_topics = [
	[ 'slug' => 'communauteit',  'en' => 'Community',      'lux' => 'Communautéit'   ],
	[ 'slug' => 'an-der-kichen', 'en' => 'In the Kitchen', 'lux' => 'An der Kichen'  ],
	[ 'slug' => 'geschicht',     'en' => 'History',        'lux' => 'Geschicht'      ],
	[ 'slug' => 'zu-letzebuerg', 'en' => 'In Luxembourg',  'lux' => 'Zu Lëtzebuerg' ],
];

// ── Mobile dropdown label ────────────────────────────────────────────────────
$nl_dropdown_label = __( 'Browse Topics', 'tclas' );
if ( $nl_section === 'topic' ) {
	foreach ( $nl_featured_topics as $t ) {
		if ( $t['slug'] === $nl_topic_slug ) {
			$nl_dropdown_label = $t['en'];
			break;
		}
	}
}

// ── Base URL for newsletter home ─────────────────────────────────────────────
$nl_home_url = home_url( '/newsletter/' );
?>

<nav class="newsletter-nav" aria-label="<?php esc_attr_e( 'Newsletter navigation', 'tclas' ); ?>">
	<div class="newsletter-nav-inner">

		<!-- ── Desktop navigation (≥768px) ──────────────────────────────── -->
		<div class="newsletter-nav-desktop">

			<!-- Branding -->
			<a href="<?php echo esc_url( $nl_home_url ); ?>" class="newsletter-logo-link">
				<span class="newsletter-logo-link__loon"><?php esc_html_e( 'The Loon', 'tclas' ); ?></span>
				<span class="newsletter-logo-link__amp"> &amp; </span>
				<span class="newsletter-logo-link__lion"><?php esc_html_e( 'The Lion', 'tclas' ); ?></span>
			</a>

			<div class="nav-separator" aria-hidden="true"></div>

			<!-- Current Issue -->
			<a
				href="<?php echo esc_url( $nl_home_url ); ?>"
				class="newsletter-nav-link<?php echo $nl_section === 'current' ? ' active' : ''; ?>"
				<?php echo $nl_section === 'current' ? 'aria-current="page"' : ''; ?>
			><?php esc_html_e( 'Current Issue', 'tclas' ); ?></a>

			<!-- Previous Issues -->
			<?php if ( $nl_archive_url ) : ?>
			<a
				href="<?php echo esc_url( $nl_archive_url ); ?>"
				class="newsletter-nav-link<?php echo $nl_section === 'archives' ? ' active' : ''; ?>"
				<?php echo $nl_section === 'archives' ? 'aria-current="page"' : ''; ?>
			><?php esc_html_e( 'Previous Issues', 'tclas' ); ?></a>
			<?php endif; ?>

			<div class="nav-separator" aria-hidden="true"></div>

			<!-- Featured topics -->
			<?php foreach ( $nl_featured_topics as $topic ) :
				$topic_url    = get_term_link( $topic['slug'], 'tclas_department' );
				$topic_url    = is_wp_error( $topic_url ) ? '' : $topic_url;
				$topic_active = ( $nl_section === 'topic' && $nl_topic_slug === $topic['slug'] );
				if ( ! $topic_url ) { continue; }
			?>
			<a
				href="<?php echo esc_url( $topic_url ); ?>"
				class="newsletter-nav-link<?php echo $topic_active ? ' active' : ''; ?>"
				title="<?php echo esc_attr( $topic['lux'] . ' — ' . $topic['en'] ); ?>"
				<?php echo $topic_active ? 'aria-current="page"' : ''; ?>
			><?php echo esc_html( $topic['en'] ); ?></a>
			<?php endforeach; ?>

		</div><!-- .newsletter-nav-desktop -->

		<!-- ── Mobile navigation (<768px) ──────────────────────────────── -->
		<div class="newsletter-nav-mobile">

			<!-- Branding -->
			<a href="<?php echo esc_url( $nl_home_url ); ?>" class="newsletter-logo-mobile">
				<span class="newsletter-logo-link__loon"><?php esc_html_e( 'The Loon', 'tclas' ); ?></span>
				<span class="newsletter-logo-link__amp"> &amp; </span>
				<span class="newsletter-logo-link__lion"><?php esc_html_e( 'The Lion', 'tclas' ); ?></span>
			</a>

			<!-- Issue links -->
			<div class="mobile-issue-links">
				<a
					href="<?php echo esc_url( $nl_home_url ); ?>"
					class="mobile-issue-link<?php echo $nl_section === 'current' ? ' active' : ''; ?>"
				><?php esc_html_e( 'Current Issue', 'tclas' ); ?></a>

				<?php if ( $nl_archive_url ) : ?>
				<a
					href="<?php echo esc_url( $nl_archive_url ); ?>"
					class="mobile-issue-link<?php echo $nl_section === 'archives' ? ' active' : ''; ?>"
				><?php esc_html_e( 'Previous Issues', 'tclas' ); ?></a>
				<?php endif; ?>
			</div>

			<!-- Topics dropdown -->
			<div class="newsletter-topics-dropdown">
				<button
					class="topics-dropdown-button"
					aria-expanded="false"
					aria-controls="newsletter-topics-menu"
				>
					<span class="dropdown-label"><?php echo esc_html( $nl_dropdown_label ); ?></span>
					<svg class="chevron-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="6 9 12 15 18 9"></polyline>
					</svg>
				</button>

				<div class="topics-dropdown-menu" id="newsletter-topics-menu" hidden>
					<?php foreach ( $nl_featured_topics as $topic ) :
						$topic_url = get_term_link( $topic['slug'], 'tclas_department' );
						if ( is_wp_error( $topic_url ) || ! $topic_url ) { continue; }
						$topic_active = ( $nl_section === 'topic' && $nl_topic_slug === $topic['slug'] );
					?>
					<a
						href="<?php echo esc_url( $topic_url ); ?>"
						class="topics-dropdown-item<?php echo $topic_active ? ' active' : ''; ?>"
					><?php echo esc_html( $topic['en'] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>

		</div><!-- .newsletter-nav-mobile -->

	</div><!-- .newsletter-nav-inner -->
</nav>
