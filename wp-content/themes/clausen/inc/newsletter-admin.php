<?php
/**
 * Newsletter admin — Issues dashboard, Posts list column/filter, ACF helpers
 *
 * Provides:
 *  • "Newsletter" top-level admin menu with an Issues overview table
 *  • "Issue" column on the Posts list screen (click-to-filter)
 *  • Filter dropdown on Posts list to scope articles to one issue
 *  • Sortable column by issue date
 *  • Datalist suggestions on the tclas_issue_date ACF text field
 *  • ACF validation enforcing YYYY-MM date format
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


// ════════════════════════════════════════════════════════════════════════════
//  Admin menu
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_menu', 'tclas_nl_register_admin_menu' );

function tclas_nl_register_admin_menu(): void {
	add_menu_page(
		__( 'Newsletter', 'tclas' ),
		__( 'Newsletter', 'tclas' ),
		'edit_posts',
		'tclas-newsletter',
		'tclas_nl_issues_page',
		'dashicons-email-alt2',
		6 // just after Posts (5)
	);

	// Rename the auto-created first submenu to "Issues"
	add_submenu_page(
		'tclas-newsletter',
		__( 'Newsletter Issues', 'tclas' ),
		__( 'Issues', 'tclas' ),
		'edit_posts',
		'tclas-newsletter',
		'tclas_nl_issues_page'
	);
}


// ════════════════════════════════════════════════════════════════════════════
//  Issues dashboard page
// ════════════════════════════════════════════════════════════════════════════

function tclas_nl_issues_page(): void {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT
		     pm.meta_value                                                AS issue_date,
		     COUNT(DISTINCT p.ID)                                         AS total,
		     SUM(CASE WHEN p.post_status = 'publish' THEN 1 ELSE 0 END)  AS published,
		     SUM(CASE WHEN p.post_status = 'draft'   THEN 1 ELSE 0 END)  AS drafts
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key   = %s
		   AND pm.meta_value != ''
		   AND p.post_type   = 'post'
		 GROUP BY pm.meta_value
		 ORDER BY pm.meta_value DESC",
		'tclas_issue_date'
	) );
	// phpcs:enable
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Newsletter Issues', 'tclas' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add Article', 'tclas' ); ?>
		</a>
		<hr class="wp-header-end">

		<?php if ( empty( $rows ) ) : ?>

			<p><?php esc_html_e( 'No issues found. Create posts and set an Issue Date to get started.', 'tclas' ); ?></p>

		<?php else : ?>

		<table class="wp-list-table widefat fixed striped" style="margin-top:1rem;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Issue', 'tclas' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Articles', 'tclas' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Published', 'tclas' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Drafts', 'tclas' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Cover', 'tclas' ); ?></th>
					<th style="width:180px"><?php esc_html_e( 'Actions', 'tclas' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) :
					$dt         = DateTime::createFromFormat( 'Y-m', $row->issue_date );
					$label      = $dt ? $dt->format( 'F Y' ) : $row->issue_date;

					// Cover = a Main Story article that has a featured image
					$cover_ids  = get_posts( [
						'post_type'      => 'post',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'meta_query'     => [ [ 'key' => 'tclas_issue_date', 'value' => $row->issue_date ] ],
						'tax_query'      => [ [ 'taxonomy' => 'tclas_department', 'field' => 'slug', 'terms' => 'main-story' ] ],
					] );
					$has_cover  = ! empty( $cover_ids ) && has_post_thumbnail( $cover_ids[0] );

					$filter_url = admin_url( 'edit.php?post_type=post&tclas_issue_date=' . rawurlencode( $row->issue_date ) );
					$view_url   = home_url( '/newsletter/issue/' . rawurlencode( $row->issue_date ) . '/' );
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $label ); ?></strong><br>
						<code style="color:#888;font-size:.8em;"><?php echo esc_html( $row->issue_date ); ?></code>
					</td>
					<td><?php echo (int) $row->total; ?></td>
					<td><?php echo (int) $row->published; ?></td>
					<td>
						<?php if ( (int) $row->drafts > 0 ) : ?>
							<span style="color:#d63638;font-weight:600;"><?php echo (int) $row->drafts; ?></span>
						<?php else : ?>
							0
						<?php endif; ?>
					</td>
					<td style="font-size:1.2em;text-align:center;">
						<?php echo $has_cover
							? '<span style="color:#00a32a;" title="' . esc_attr__( 'Main Story with featured image found', 'tclas' ) . '">✓</span>'
							: '<span style="color:#d63638;" title="' . esc_attr__( 'No Main Story with featured image', 'tclas' ) . '">✗</span>'; ?>
					</td>
					<td>
						<a href="<?php echo esc_url( $filter_url ); ?>"><?php esc_html_e( 'Edit articles', 'tclas' ); ?></a>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View live', 'tclas' ); ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description" style="margin-top:1rem;">
			<?php esc_html_e( 'To start a new issue: create a post and enter the new date (YYYY-MM) in the Issue Date field.', 'tclas' ); ?>
		</p>

		<?php endif; ?>
	</div>
	<?php
}


// ════════════════════════════════════════════════════════════════════════════
//  Posts list — "Issue" column
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_posts_columns', 'tclas_nl_add_issue_column' );

function tclas_nl_add_issue_column( array $cols ): array {
	$out = [];
	foreach ( $cols as $key => $val ) {
		$out[ $key ] = $val;
		if ( $key === 'title' ) {
			$out['tclas_issue_date'] = __( 'Issue', 'tclas' );
		}
	}
	return $out;
}

add_action( 'manage_posts_custom_column', 'tclas_nl_render_issue_column', 10, 2 );

function tclas_nl_render_issue_column( string $col, int $post_id ): void {
	if ( $col !== 'tclas_issue_date' ) { return; }

	$date = get_post_meta( $post_id, 'tclas_issue_date', true );
	if ( ! $date ) {
		echo '<span style="color:#ddd;">—</span>';
		return;
	}

	$dt    = DateTime::createFromFormat( 'Y-m', $date );
	$label = $dt ? $dt->format( 'M Y' ) : $date;
	$url   = admin_url( 'edit.php?post_type=post&tclas_issue_date=' . rawurlencode( $date ) );

	echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

add_filter( 'manage_edit-post_sortable_columns', 'tclas_nl_sortable_issue_column' );

function tclas_nl_sortable_issue_column( array $cols ): array {
	$cols['tclas_issue_date'] = 'tclas_issue_date';
	return $cols;
}


// ════════════════════════════════════════════════════════════════════════════
//  Posts list — filter dropdown + query integration
// ════════════════════════════════════════════════════════════════════════════

add_action( 'restrict_manage_posts', 'tclas_nl_issue_filter_dropdown' );

function tclas_nl_issue_filter_dropdown( string $post_type ): void {
	if ( $post_type !== 'post' ) { return; }

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$dates = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = %s AND meta_value != ''
		 ORDER BY meta_value DESC",
		'tclas_issue_date'
	) );
	// phpcs:enable

	if ( empty( $dates ) ) { return; }

	$current = sanitize_text_field( wp_unslash( $_GET['tclas_issue_date'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification

	echo '<select name="tclas_issue_date" id="filter-by-issue-date">';
	echo '<option value="">' . esc_html__( 'All issues', 'tclas' ) . '</option>';
	foreach ( $dates as $d ) {
		$dt  = DateTime::createFromFormat( 'Y-m', $d );
		$lbl = $dt ? $dt->format( 'F Y' ) : $d;
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $d ),
			selected( $current, $d, false ),
			esc_html( $lbl )
		);
	}
	echo '</select>';
}

add_action( 'pre_get_posts', 'tclas_nl_filter_and_sort_posts' );

function tclas_nl_filter_and_sort_posts( WP_Query $q ): void {
	global $pagenow;
	if ( ! is_admin() || ! $q->is_main_query() || $pagenow !== 'edit.php' ) { return; }

	// Filter by issue date from dropdown
	$date = sanitize_text_field( wp_unslash( $_GET['tclas_issue_date'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( $date && preg_match( '/^\d{4}-\d{2}$/', $date ) ) {
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = [ 'key' => 'tclas_issue_date', 'value' => $date ];
		$q->set( 'meta_query', $meta );
	}

	// Sortable column
	if ( $q->get( 'orderby' ) === 'tclas_issue_date' ) {
		$q->set( 'meta_key', 'tclas_issue_date' );
		$q->set( 'orderby', 'meta_value' );
	}
}


// ════════════════════════════════════════════════════════════════════════════
//  ACF helpers
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inject a <datalist> for tclas_issue_date on the post edit screen so the
 * browser autocompletes existing issue dates while still allowing free input.
 */
add_action( 'admin_footer-post.php',     'tclas_nl_acf_datalist' );
add_action( 'admin_footer-post-new.php', 'tclas_nl_acf_datalist' );

function tclas_nl_acf_datalist(): void {
	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$dates = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = %s AND meta_value != ''
		 ORDER BY meta_value DESC
		 LIMIT 20",
		'tclas_issue_date'
	) );
	// phpcs:enable

	if ( empty( $dates ) ) { return; }
	?>
	<datalist id="tclas-issue-datalist">
		<?php foreach ( $dates as $d ) : ?>
		<option value="<?php echo esc_attr( $d ); ?>">
		<?php endforeach; ?>
	</datalist>
	<script>
	(function () {
		function attach() {
			var input = document.querySelector(
				'input[data-key="field_tclas_issue_date"], input[name="acf[field_tclas_issue_date]"]'
			);
			if ( ! input || input.list ) { return; }
			input.setAttribute( 'list', 'tclas-issue-datalist' );
			input.setAttribute( 'placeholder', 'YYYY-MM' );
		}
		// ACF renders fields asynchronously; give it a moment.
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', function () { setTimeout( attach, 400 ); } );
		} else {
			setTimeout( attach, 400 );
		}
	})();
	</script>
	<?php
}

/**
 * Validate YYYY-MM format for the issue date field.
 */
add_filter( 'acf/validate_value/key=field_tclas_issue_date', 'tclas_nl_validate_issue_date', 10, 2 );

function tclas_nl_validate_issue_date( $valid, $value ) {
	if ( $value && ! preg_match( '/^\d{4}-\d{2}$/', (string) $value ) ) {
		return __( 'Issue date must be in YYYY-MM format (e.g. 2025-03).', 'tclas' );
	}
	return $valid;
}
