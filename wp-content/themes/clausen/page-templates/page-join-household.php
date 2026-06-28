<?php
/**
 * Template Name: Join Household
 *
 * Acceptance page for a household invitation. Rendered for logged-out visitors
 * arriving at /member-hub/join-household/{token}/. Because this template is NOT
 * page-member-hub.php, tclas_hub_access_check() does not redirect non-members.
 *
 * On any token problem we show a single generic message — never reveal whether
 * an email, token, or owner exists (user-enumeration safety).
 *
 * @package TCLAS
 */

get_header();

$raw_token = (string) get_query_var( 'tclas_household_token' );
$invite    = $raw_token ? tclas_household_invite_by_token( $raw_token ) : null;

$error   = '';
$generic = __( 'This invitation link is invalid or has expired.', 'tclas' );

// Already logged in: don't try to provision a second account into this session.
$logged_in = is_user_logged_in();

// Handle the set-password submission.
if ( $invite && ! $logged_in && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	if (
		isset( $_POST['tclas_join_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tclas_join_nonce'] ) ), 'tclas_join_household' )
	) {
		// Re-fetch by token to guarantee it is still pending at submit time.
		$invite = tclas_household_invite_by_token( $raw_token );
		if ( ! $invite ) {
			$error = $generic;
		} else {
			$password = (string) ( $_POST['tclas_join_password'] ?? '' );
			$confirm  = (string) ( $_POST['tclas_join_password_confirm'] ?? '' );
			if ( $password !== $confirm ) {
				$error = __( 'The two passwords do not match.', 'tclas' );
			} else {
				$result = tclas_household_accept_invite( $invite, $password );
				if ( $result['ok'] ) {
					wp_safe_redirect( home_url( '/member-hub/edit-profile/?welcome=1' ) );
					exit;
				}
				$error = $result['error'];
			}
		}
	} else {
		$error = __( 'Security check failed. Please try again.', 'tclas' );
	}
}
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Join your household', 'tclas' ); ?></h1>
	</div>
</div>

<div class="container-tclas tclas-join-household" style="max-width:540px;margin:2rem auto;">

	<?php if ( $logged_in ) : ?>

		<div class="tclas-alert tclas-alert--info" role="alert">
			<?php esc_html_e( 'You are already logged in. To accept a household invitation, please log out first and open the link again.', 'tclas' ); ?>
		</div>

	<?php elseif ( ! $invite ) : ?>

		<div class="tclas-alert tclas-alert--error" role="alert">
			<?php echo esc_html( $generic ); ?>
		</div>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return to the homepage →', 'tclas' ); ?></a></p>

	<?php else : ?>

		<p>
			<?php
			printf(
				/* translators: %s: invitee name */
				esc_html__( 'Welcome, %s! Set a password to finish creating your member account.', 'tclas' ),
				esc_html( $invite->invitee_name )
			);
			?>
		</p>
		<p class="tclas-join-household__email">
			<?php esc_html_e( 'Your login email:', 'tclas' ); ?>
			<strong><?php echo esc_html( $invite->invitee_email ); ?></strong>
		</p>

		<?php if ( $error ) : ?>
			<div class="tclas-alert tclas-alert--error" role="alert"><?php echo esc_html( $error ); ?></div>
		<?php endif; ?>

		<form method="post" action="" class="tclas-join-household__form">
			<?php wp_nonce_field( 'tclas_join_household', 'tclas_join_nonce' ); ?>
			<p>
				<label for="tclas_join_password"><?php esc_html_e( 'Choose a password', 'tclas' ); ?></label>
				<input type="password" id="tclas_join_password" name="tclas_join_password" autocomplete="new-password" required>
			</p>
			<p>
				<label for="tclas_join_password_confirm"><?php esc_html_e( 'Confirm password', 'tclas' ); ?></label>
				<input type="password" id="tclas_join_password_confirm" name="tclas_join_password_confirm" autocomplete="new-password" required>
			</p>
			<p class="tclas-join-household__hint">
				<?php esc_html_e( 'Use at least 12 characters. A few unrelated words make a strong, memorable password.', 'tclas' ); ?>
			</p>
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Create my account', 'tclas' ); ?></button>
		</form>

	<?php endif; ?>

</div>

<?php
get_footer();
