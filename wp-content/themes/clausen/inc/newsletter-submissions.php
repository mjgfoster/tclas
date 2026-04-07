<?php
/**
 * Newsletter Submissions
 *
 * Admin meta boxes and status workflow for the tclas_nl_submit post type.
 * Members submit story ideas via the frontend form (page-newsletter-submit.php);
 * admins review and manage submissions in the WordPress admin.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — Admin meta boxes
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', 'tclas_nl_submission_meta_boxes' );
function tclas_nl_submission_meta_boxes(): void {
	add_meta_box(
		'tclas_nl_submission_details',
		__( 'Submission Details', 'tclas' ),
		'tclas_nl_submission_details_box',
		'tclas_nl_submit',
		'normal',
		'high'
	);

	add_meta_box(
		'tclas_nl_submission_admin',
		__( 'Admin Review', 'tclas' ),
		'tclas_nl_submission_admin_box',
		'tclas_nl_submit',
		'side',
		'high'
	);
}

/**
 * Render the submission details meta box (submitter info + message).
 */
function tclas_nl_submission_details_box( WP_Post $post ): void {
	$name    = get_post_meta( $post->ID, '_tclas_submission_name',    true );
	$email   = get_post_meta( $post->ID, '_tclas_submission_email',   true );
	$phone   = get_post_meta( $post->ID, '_tclas_submission_phone',   true );
	$message = get_post_meta( $post->ID, '_tclas_submission_message', true );
	$user_id = (int) get_post_meta( $post->ID, '_tclas_submission_user_id', true );

	$profile_link = '';
	if ( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			$profile_link = home_url( '/member-hub/profiles/' . rawurlencode( $user->user_nicename ) . '/' );
		}
	}
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Submitter', 'tclas' ); ?></th>
			<td>
				<strong><?php echo esc_html( $name ); ?></strong>
				<?php if ( $profile_link ) : ?>
					— <a href="<?php echo esc_url( $profile_link ); ?>" target="_blank"><?php esc_html_e( 'View profile', 'tclas' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Email', 'tclas' ); ?></th>
			<td>
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			</td>
		</tr>
		<?php if ( $phone ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Phone', 'tclas' ); ?></th>
			<td><?php echo esc_html( $phone ); ?></td>
		</tr>
		<?php endif; ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Message / Story Idea', 'tclas' ); ?></th>
			<td>
				<div style="background:#f9f9f9;padding:1em;border:1px solid #ddd;border-radius:4px;white-space:pre-wrap;max-height:400px;overflow-y:auto;">
					<?php echo nl2br( esc_html( $message ) ); ?>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Submitted', 'tclas' ); ?></th>
			<td><?php echo esc_html( get_the_date( 'F j, Y \a\t g:i a', $post ) ); ?></td>
		</tr>
	</table>
	<?php
}

/**
 * Render the admin review meta box (status + notes).
 */
function tclas_nl_submission_admin_box( WP_Post $post ): void {
	$status = get_post_meta( $post->ID, '_tclas_submission_status', true ) ?: 'draft';
	$notes  = get_post_meta( $post->ID, '_tclas_submission_admin_notes', true );
	$email  = get_post_meta( $post->ID, '_tclas_submission_email', true );
	$name   = get_post_meta( $post->ID, '_tclas_submission_name', true );

	wp_nonce_field( 'tclas_nl_submission_save_' . $post->ID, 'tclas_nl_submission_nonce' );

	$statuses = [
		'draft'          => __( 'Draft', 'tclas' ),
		'pending_review' => __( 'Pending Review', 'tclas' ),
		'accepted'       => __( 'Accepted', 'tclas' ),
		'declined'       => __( 'Declined', 'tclas' ),
		'published'      => __( 'Published', 'tclas' ),
	];
	?>
	<p>
		<label for="tclas-submission-status"><strong><?php esc_html_e( 'Status', 'tclas' ); ?></strong></label><br>
		<select name="tclas_submission_status" id="tclas-submission-status" style="width:100%;">
			<?php foreach ( $statuses as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label for="tclas-submission-notes"><strong><?php esc_html_e( 'Admin Notes', 'tclas' ); ?></strong></label><br>
		<textarea
			name="tclas_submission_admin_notes"
			id="tclas-submission-notes"
			rows="5"
			style="width:100%;"
			placeholder="<?php esc_attr_e( 'Internal notes about this submission…', 'tclas' ); ?>"
		><?php echo esc_textarea( $notes ); ?></textarea>
	</p>

	<?php if ( $email ) : ?>
	<p>
		<a
			href="mailto:<?php echo esc_attr( $email ); ?>?subject=<?php echo esc_attr( sprintf( __( 'Re: Your TCLAS newsletter submission', 'tclas' ) ) ); ?>"
			class="button"
			style="width:100%;text-align:center;"
		>
			<?php printf( esc_html__( 'Email %s', 'tclas' ), esc_html( $name ?: $email ) ); ?>
		</a>
	</p>
	<?php endif; ?>
	<?php
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — Save meta box data
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'save_post_tclas_nl_submit', 'tclas_nl_submission_save_meta', 10, 2 );
function tclas_nl_submission_save_meta( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['tclas_nl_submission_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['tclas_nl_submission_nonce'] ) ),
			'tclas_nl_submission_save_' . $post_id
		)
	) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$allowed_statuses = [ 'draft', 'pending_review', 'accepted', 'declined', 'published' ];
	$status = sanitize_key( $_POST['tclas_submission_status'] ?? 'draft' );
	if ( in_array( $status, $allowed_statuses, true ) ) {
		update_post_meta( $post_id, '_tclas_submission_status', $status );
	}

	update_post_meta(
		$post_id,
		'_tclas_submission_admin_notes',
		sanitize_textarea_field( $_POST['tclas_submission_admin_notes'] ?? '' )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — Admin list columns
// ═══════════════════════════════════════════════════════════════════════════

add_filter( 'manage_tclas_nl_submit_posts_columns', 'tclas_nl_submission_columns' );
function tclas_nl_submission_columns( array $columns ): array {
	$new = [];
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['tclas_submitter'] = __( 'Submitter', 'tclas' );
			$new['tclas_email']    = __( 'Email', 'tclas' );
			$new['tclas_status']   = __( 'Status', 'tclas' );
		}
	}
	return $new;
}

add_action( 'manage_tclas_nl_submit_posts_custom_column', 'tclas_nl_submission_column_content', 10, 2 );
function tclas_nl_submission_column_content( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'tclas_submitter':
			echo esc_html( get_post_meta( $post_id, '_tclas_submission_name', true ) );
			break;
		case 'tclas_email':
			$email = get_post_meta( $post_id, '_tclas_submission_email', true );
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			break;
		case 'tclas_status':
			$status = get_post_meta( $post_id, '_tclas_submission_status', true ) ?: 'draft';
			$labels = [
				'draft'          => __( 'Draft', 'tclas' ),
				'pending_review' => __( 'Pending Review', 'tclas' ),
				'accepted'       => __( 'Accepted', 'tclas' ),
				'declined'       => __( 'Declined', 'tclas' ),
				'published'      => __( 'Published', 'tclas' ),
			];
			$colors = [
				'draft'          => '#999',
				'pending_review' => '#d48806',
				'accepted'       => '#389e0d',
				'declined'       => '#cf1322',
				'published'      => '#1890ff',
			];
			$label = $labels[ $status ] ?? ucfirst( $status );
			$color = $colors[ $status ] ?? '#999';
			echo '<span style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( $label ) . '</span>';
			break;
	}
}

// Make status column sortable.
add_filter( 'manage_edit-tclas_nl_submit_sortable_columns', 'tclas_nl_submission_sortable_columns' );
function tclas_nl_submission_sortable_columns( array $columns ): array {
	$columns['tclas_status'] = 'tclas_status';
	return $columns;
}
