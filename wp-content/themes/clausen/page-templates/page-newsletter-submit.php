<?php
/**
 * Template Name: Newsletter Submission
 *
 * Members-only form for submitting story ideas, recipes, traditions, or
 * anything else for consideration in the TCLAS newsletter.
 *
 * Creates a tclas_nl_submit post on submission for admin review.
 *
 * @package TCLAS
 */

get_header();

// ── Handle form POST ────────────────────────────────────────────────────────
$submitted    = false;
$save_error   = '';

if ( tclas_is_member() && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if (
		isset( $_POST['tclas_nl_submit_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_nl_submit_nonce'] ) ), 'tclas_nl_submit_' . get_current_user_id() )
	) {
		$uid     = get_current_user_id();
		$name    = sanitize_text_field( $_POST['tclas_nl_name'] ?? '' );
		$email   = sanitize_email( $_POST['tclas_nl_email'] ?? '' );
		$phone   = sanitize_text_field( $_POST['tclas_nl_phone'] ?? '' );
		$message = sanitize_textarea_field( $_POST['tclas_nl_message'] ?? '' );

		if ( '' === $name || '' === $email || '' === $message ) {
			$save_error = __( 'Please fill in your name, email, and message.', 'tclas' );
		} else {
			$post_id = wp_insert_post( [
				'post_type'   => 'tclas_nl_submit',
				'post_title'  => sprintf(
					/* translators: %1$s: submitter name, %2$s: date */
					__( 'Submission from %1$s — %2$s', 'tclas' ),
					$name,
					wp_date( 'M j, Y' )
				),
				'post_status' => 'publish', // WP post status; workflow status is in meta.
				'post_author' => $uid,
			] );

			if ( is_wp_error( $post_id ) ) {
				$save_error = __( 'Something went wrong. Please try again.', 'tclas' );
			} else {
				update_post_meta( $post_id, '_tclas_submission_name',    $name );
				update_post_meta( $post_id, '_tclas_submission_email',   $email );
				update_post_meta( $post_id, '_tclas_submission_phone',   $phone );
				update_post_meta( $post_id, '_tclas_submission_message', $message );
				update_post_meta( $post_id, '_tclas_submission_status',  'draft' );
				update_post_meta( $post_id, '_tclas_submission_user_id', $uid );

				// Notify admin via email.
				$admin_email = get_option( 'admin_email' );
				if ( $admin_email ) {
					wp_mail(
						$admin_email,
						sprintf( __( '[TCLAS] New newsletter submission from %s', 'tclas' ), $name ),
						sprintf(
							__( "%s submitted a story idea for the newsletter.\n\nMessage:\n%s\n\nReview it in the admin:\n%s", 'tclas' ),
							$name,
							$message,
							admin_url( 'post.php?post=' . $post_id . '&action=edit' )
						)
					);
				}

				$submitted = true;
			}
		}
	} else {
		$save_error = __( 'Security check failed. Please try again.', 'tclas' );
	}
}

// ── Load user data for auto-fill ────────────────────────────────────────────
$user         = wp_get_current_user();
$default_name = $user->ID ? trim( $user->first_name . ' ' . $user->last_name ) : '';
if ( '' === $default_name && $user->ID ) {
	$default_name = $user->display_name;
}
$default_email = $user->ID ? $user->user_email : '';
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Submit a Story', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Submit a Story', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas container--medium">

		<?php if ( ! tclas_is_member() ) : ?>

			<div class="tclas-member-gate">
				<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
				<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
				<p class="tclas-member-gate__desc">
					<?php esc_html_e( 'Story submissions are open to TCLAS members. Join or log in to share your idea.', 'tclas' ); ?>
				</p>
				<div class="tclas-member-gate__actions">
					<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary">
						<?php esc_html_e( 'Join TCLAS', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( 'Log in', 'tclas' ); ?>
					</a>
				</div>
			</div>

		<?php elseif ( $submitted ) : ?>

			<div class="tclas-alert tclas-alert--success" role="alert" style="text-align:center;padding:2rem;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Thank you!', 'tclas' ); ?></h2>
				<p>
					<?php esc_html_e( "We've received your submission. Our team will review it and be in touch if we'd like to feature it in an upcoming newsletter.", 'tclas' ); ?>
				</p>
				<div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
					<a href="<?php the_permalink(); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( 'Submit another idea', 'tclas' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( '← Back to hub', 'tclas' ); ?>
					</a>
				</div>
			</div>

		<?php else : ?>

			<?php if ( $save_error ) : ?>
				<div class="tclas-alert tclas-alert--error" role="alert">
					<?php echo esc_html( $save_error ); ?>
				</div>
			<?php endif; ?>

			<p style="margin-bottom:1.5rem;">
				<?php esc_html_e( 'Share a story idea, ancestor story, recipe, tradition, or anything else you think would inspire our community. Our editorial team will review submissions and follow up with you.', 'tclas' ); ?>
			</p>

			<form
				id="tclas-nl-submit-form"
				class="tclas-my-story-form"
				method="post"
				action="<?php the_permalink(); ?>"
				novalidate
			>
				<?php wp_nonce_field( 'tclas_nl_submit_' . get_current_user_id(), 'tclas_nl_submit_nonce' ); ?>

				<fieldset class="tclas-story-fieldset">

					<!-- Name -->
					<label class="tclas-story-social-label" for="tclas-nl-name">
						<?php esc_html_e( 'Your name (required)', 'tclas' ); ?>
					</label>
					<input
						type="text"
						id="tclas-nl-name"
						name="tclas_nl_name"
						value="<?php echo esc_attr( $_POST['tclas_nl_name'] ?? $default_name ); ?>"
						class="tclas-story-input"
						required
					>

					<!-- Email -->
					<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-nl-email">
						<?php esc_html_e( 'Email address (required)', 'tclas' ); ?>
					</label>
					<input
						type="email"
						id="tclas-nl-email"
						name="tclas_nl_email"
						value="<?php echo esc_attr( $_POST['tclas_nl_email'] ?? $default_email ); ?>"
						class="tclas-story-input"
						required
					>

					<!-- Phone -->
					<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-nl-phone">
						<?php esc_html_e( 'Phone number (optional)', 'tclas' ); ?>
					</label>
					<input
						type="tel"
						id="tclas-nl-phone"
						name="tclas_nl_phone"
						value="<?php echo esc_attr( $_POST['tclas_nl_phone'] ?? '' ); ?>"
						class="tclas-story-input"
					>

					<!-- Message -->
					<label class="tclas-story-social-label tclas-story-social-label--mt" for="tclas-nl-message">
						<?php esc_html_e( 'Message / Story idea (required)', 'tclas' ); ?>
					</label>
					<textarea
						id="tclas-nl-message"
						name="tclas_nl_message"
						class="tclas-story-input"
						rows="8"
						required
						placeholder="<?php esc_attr_e( 'Share a story idea, ancestor story, recipe, tradition, or anything else you think would inspire our community…', 'tclas' ); ?>"
					><?php echo esc_textarea( $_POST['tclas_nl_message'] ?? '' ); ?></textarea>

				</fieldset>

				<div class="tclas-story-actions">
					<button type="submit" class="btn btn-primary">
						<?php esc_html_e( 'Submit', 'tclas' ); ?>
					</button>
					<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise">
						<?php esc_html_e( '← Back to hub', 'tclas' ); ?>
					</a>
				</div>

			</form>

		<?php endif; ?>

	</div><!-- .container-tclas -->
</section>

<?php get_footer(); ?>
