<?php
/**
 * Template Name: Messages
 *
 * Member-to-member messaging interface.
 * - Inbox view: /member-hub/messages/
 * - Conversation view: /member-hub/messages/{username}/
 *
 * @package TCLAS
 */

get_header();

if ( ! tclas_is_member() ) :
?>
<div class="tclas-page-header">
	<div class="container-tclas">
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Messages', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas">
		<div class="tclas-member-gate">
			<?php tclas_illustration( 'member_gate_illustration', __( 'Member area', 'tclas' ), 'tclas-member-gate__illustration' ); ?>
			<h2 class="tclas-member-gate__title"><?php esc_html_e( 'Members only.', 'tclas' ); ?></h2>
			<p class="tclas-member-gate__desc">
				<?php esc_html_e( 'Messaging is available to TCLAS members. Join or log in to connect.', 'tclas' ); ?>
			</p>
			<div class="tclas-member-gate__actions">
				<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Join TCLAS', 'tclas' ); ?></a>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn-outline-ardoise"><?php esc_html_e( 'Log in', 'tclas' ); ?></a>
			</div>
		</div>
	</div>
</section>
<?php
	get_footer();
	return;
endif;

$user_id          = get_current_user_id();
$other_username   = get_query_var( 'tclas_message_username' );
$is_conversation  = ! empty( $other_username );

// ── Conversation view ───────────────────────────────────────────────────────
if ( $is_conversation ) :
	$other_user = get_user_by( 'slug', sanitize_title( $other_username ) );

	if ( ! $other_user || $other_user->ID === $user_id ) :
?>
<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Messages', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Conversation not found', 'tclas' ); ?></h1>
	</div>
</div>
<section class="tclas-section">
	<div class="container-tclas container--medium">
		<p><?php esc_html_e( 'That member could not be found.', 'tclas' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/member-hub/messages/' ) ); ?>" class="btn btn-outline-ardoise">
			← <?php esc_html_e( 'Back to messages', 'tclas' ); ?>
		</a>
	</div>
</section>
<?php
		get_footer();
		return;
	endif;

	// Mark this conversation as read.
	tclas_mark_conversation_read( $user_id, $other_user->ID );

	$messages = tclas_get_conversation( $user_id, $other_user->ID );

	$display_override = (string) ( get_user_meta( $other_user->ID, '_tclas_display_name_override', true ) ?: '' );
	$other_name       = '' !== $display_override ? $display_override : $other_user->display_name;
	$other_photo      = tclas_get_profile_photo_url( $other_user->ID, 'thumbnail' );
	$profile_url      = home_url( '/member-hub/profiles/' . rawurlencode( $other_user->user_nicename ) . '/' );

	// Check if we can send to this user.
	$can_contact = true;
	$contact_val = get_user_meta( $other_user->ID, '_tclas_privacy_allow_contact', true );
	if ( '' !== $contact_val && ! (bool) $contact_val ) {
		$legacy = get_user_meta( $other_user->ID, '_tclas_open_to_contact', true );
		$can_contact = ( '' === $legacy || (bool) $legacy );
	}
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php
		echo '<nav class="tclas-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'tclas' ) . '">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'tclas' ) . '</a>';
		echo '<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>';
		echo '<a href="' . esc_url( home_url( '/member-hub/' ) ) . '">' . esc_html__( 'Member hub', 'tclas' ) . '</a>';
		echo '<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>';
		echo '<a href="' . esc_url( home_url( '/member-hub/messages/' ) ) . '">' . esc_html__( 'Messages', 'tclas' ) . '</a>';
		echo '<span class="tclas-breadcrumb__sep" aria-hidden="true">›</span>';
		echo '<span class="tclas-breadcrumb__current" aria-current="page">' . esc_html( $other_name ) . '</span>';
		echo '</nav>';
		?>
		<h1 class="tclas-page-header__title"><?php echo esc_html( $other_name ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas container--medium">

		<!-- Conversation partner info -->
		<div class="tclas-conv-partner">
			<img src="<?php echo esc_url( $other_photo ); ?>" alt="" class="tclas-conv-partner__photo" width="48" height="48" loading="lazy">
			<div>
				<strong><?php echo esc_html( $other_name ); ?></strong>
				<a href="<?php echo esc_url( $profile_url ); ?>" class="tclas-conv-partner__link"><?php esc_html_e( 'View profile →', 'tclas' ); ?></a>
			</div>
		</div>

		<!-- Messages thread -->
		<div class="tclas-conv-thread" id="tclas-conv-thread" data-other-id="<?php echo (int) $other_user->ID; ?>">
			<?php if ( empty( $messages ) ) : ?>
				<p class="tclas-conv-empty"><?php esc_html_e( 'No messages yet. Start the conversation below.', 'tclas' ); ?></p>
			<?php else : ?>
				<?php foreach ( $messages as $msg ) :
					echo tclas_render_message_bubble( $msg['sender_id'], $msg['message'], $msg['created_at'] );
				endforeach; ?>
			<?php endif; ?>
		</div>

		<!-- Reply form -->
		<?php if ( $can_contact ) : ?>
			<form class="tclas-conv-reply" id="tclas-conv-reply-form" data-recipient="<?php echo (int) $other_user->ID; ?>">
				<label for="tclas-conv-message" class="sr-only"><?php esc_html_e( 'Your message', 'tclas' ); ?></label>
				<textarea
					id="tclas-conv-message"
					class="tclas-story-input tclas-conv-reply__input"
					rows="3"
					placeholder="<?php esc_attr_e( 'Write a message…', 'tclas' ); ?>"
					required
				></textarea>
				<button type="submit" class="btn btn-primary tclas-conv-reply__send">
					<?php esc_html_e( 'Send', 'tclas' ); ?>
				</button>
				<p class="tclas-conv-reply__status" id="tclas-conv-reply-status" aria-live="polite"></p>
			</form>
		<?php else : ?>
			<p class="tclas-conv-no-reply">
				<?php esc_html_e( 'This member prefers not to be contacted.', 'tclas' ); ?>
			</p>
		<?php endif; ?>

		<a href="<?php echo esc_url( home_url( '/member-hub/messages/' ) ); ?>" class="btn btn-outline-ardoise" style="margin-top:1rem;">
			← <?php esc_html_e( 'All messages', 'tclas' ); ?>
		</a>

	</div>
</section>

<script>
(function () {
	var form = document.getElementById('tclas-conv-reply-form');
	if (!form) return;

	var thread = document.getElementById('tclas-conv-thread');
	var status = document.getElementById('tclas-conv-reply-status');
	var textarea = document.getElementById('tclas-conv-message');

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var msg = textarea.value.trim();
		if (!msg) return;

		var btn = form.querySelector('.tclas-conv-reply__send');
		btn.disabled = true;
		status.textContent = '<?php echo esc_js( __( 'Sending…', 'tclas' ) ); ?>';

		var fd = new FormData();
		fd.append('action', 'tclas_send_message');
		fd.append('nonce', tclasData.nonce);
		fd.append('recipient_id', form.dataset.recipient);
		fd.append('message', msg);

		fetch(tclasData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				btn.disabled = false;
				if (data.success) {
					// Remove empty state if present.
					var empty = thread.querySelector('.tclas-conv-empty');
					if (empty) empty.remove();
					// Append bubble.
					thread.insertAdjacentHTML('beforeend', data.data.html);
					thread.scrollTop = thread.scrollHeight;
					textarea.value = '';
					status.textContent = '';
				} else {
					status.textContent = data.data.message || '<?php echo esc_js( __( 'Could not send message.', 'tclas' ) ); ?>';
				}
			})
			.catch(function () {
				btn.disabled = false;
				status.textContent = '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'tclas' ) ); ?>';
			});
	});

	// Scroll to bottom on load.
	if (thread) thread.scrollTop = thread.scrollHeight;

	// Mark as read on page load.
	var otherId = thread ? thread.dataset.otherId : '';
	if (otherId) {
		var fd = new FormData();
		fd.append('action', 'tclas_mark_messages_read');
		fd.append('nonce', tclasData.nonce);
		fd.append('other_id', otherId);
		fetch(tclasData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
	}
})();
</script>

<?php

// ── Inbox view ──────────────────────────────────────────────────────────────
else :
	$conversations = tclas_get_conversations( $user_id );
?>

<div class="tclas-page-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb( __( 'Messages', 'tclas' ) ); ?>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Messages', 'tclas' ); ?></h1>
	</div>
</div>

<section class="tclas-section">
	<div class="container-tclas container--medium">

		<?php if ( empty( $conversations ) ) : ?>
			<div class="tclas-conv-empty-inbox">
				<p><?php esc_html_e( 'No messages yet. Visit a member\'s profile to start a conversation.', 'tclas' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/member-hub/profiles/' ) ); ?>" class="btn btn-outline-ardoise">
					<?php esc_html_e( 'Browse members →', 'tclas' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="tclas-inbox">
				<?php foreach ( $conversations as $conv ) :
					$conv_url = home_url( '/member-hub/messages/' . rawurlencode( $conv['other_username'] ) . '/' );
					$time_ago = human_time_diff( strtotime( $conv['last_date'] ) );
				?>
					<a href="<?php echo esc_url( $conv_url ); ?>" class="tclas-inbox-row<?php echo $conv['unread_count'] > 0 ? ' tclas-inbox-row--unread' : ''; ?>">
						<img src="<?php echo esc_url( $conv['other_photo'] ); ?>" alt="" class="tclas-inbox-row__photo" width="44" height="44" loading="lazy">
						<div class="tclas-inbox-row__body">
							<div class="tclas-inbox-row__header">
								<strong class="tclas-inbox-row__name"><?php echo esc_html( $conv['other_name'] ); ?></strong>
								<span class="tclas-inbox-row__time"><?php echo esc_html( $time_ago ); ?> <?php esc_html_e( 'ago', 'tclas' ); ?></span>
							</div>
							<p class="tclas-inbox-row__preview">
								<?php echo esc_html( $conv['last_message'] ); ?>
							</p>
						</div>
						<?php if ( $conv['unread_count'] > 0 ) : ?>
							<span class="tclas-inbox-row__badge"><?php echo (int) $conv['unread_count']; ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<a href="<?php echo esc_url( home_url( '/member-hub/' ) ); ?>" class="btn btn-outline-ardoise" style="margin-top:1.5rem;">
			← <?php esc_html_e( 'Back to hub', 'tclas' ); ?>
		</a>

	</div>
</section>

<?php
endif;
get_footer();
?>
