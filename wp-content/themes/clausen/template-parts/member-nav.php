<?php
/**
 * Member Navigation Bar
 *
 * Sticky sub-nav displayed on member-area pages for logged-in members.
 * Shows greeting + hub navigation links with active state highlighting.
 * Mobile: collapses to a dropdown toggle.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$user       = wp_get_current_user();
$first_name = ! empty( $user->user_firstname ) ? $user->user_firstname : __( 'Member', 'tclas' );
$profile_url = home_url( '/member-hub/profiles/' . rawurlencode( $user->user_nicename ) . '/' );

// Navigation items — mirrors the hub sidebar
$member_nav_links = [
	[ 'label' => __( 'Dashboard', 'tclas' ), 'url' => home_url( '/member-hub/' ),              'icon' => 'bi-house-door-fill' ],
	[ 'label' => __( 'Directory', 'tclas' ), 'url' => home_url( '/member-hub/profiles/' ),      'icon' => 'bi-people-fill' ],
	[ 'label' => __( 'Documents', 'tclas' ), 'url' => home_url( '/member-hub/documents/' ),     'icon' => 'bi-file-earmark-text' ],
	[ 'label' => __( 'My Profile', 'tclas' ), 'url' => $profile_url,                           'icon' => 'bi-person-circle' ],
	[ 'label' => __( 'Messages',  'tclas' ), 'url' => home_url( '/member-hub/messages/' ),      'icon' => 'bi-envelope-fill' ],
	[ 'label' => __( 'Forum',     'tclas' ), 'url' => home_url( '/member-hub/forums/' ),        'icon' => 'bi-chat-left-text-fill' ],
	[ 'label' => __( 'Map',       'tclas' ), 'url' => home_url( '/member-hub/ancestral-map/' ), 'icon' => 'bi-map-fill' ],
];

// Determine active link by comparing request URI to link path
$request_path = rtrim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );

// Find the active link label for the mobile toggle
$active_label = __( 'Dashboard', 'tclas' );
$active_icon  = 'bi-house-door-fill';
foreach ( $member_nav_links as $link ) {
	$link_path = rtrim( parse_url( $link['url'], PHP_URL_PATH ), '/' );
	$match     = ( $request_path === $link_path );
	if ( ! $match && $link['label'] !== 'Dashboard' && str_starts_with( $request_path, $link_path . '/' ) ) {
		$match = true;
	}
	if ( $match ) {
		$active_label = $link['label'];
		$active_icon  = $link['icon'];
		break;
	}
}
?>

<nav class="tclas-member-nav" aria-label="<?php esc_attr_e( 'Member navigation', 'tclas' ); ?>">
	<div class="tclas-member-nav__inner">

		<span class="tclas-member-nav__greeting">
			<?php echo esc_html( sprintf(
				/* translators: %s: member's first name */
				__( 'Moien, %s', 'tclas' ),
				$first_name
			) ); ?>
		</span>

		<!-- Desktop: inline links -->
		<ul class="tclas-member-nav__links" role="list">
			<?php foreach ( $member_nav_links as $link ) :
				$link_path = rtrim( parse_url( $link['url'], PHP_URL_PATH ), '/' );
				$is_active = ( $request_path === $link_path );
				if ( ! $is_active && $link['label'] !== 'Dashboard' && str_starts_with( $request_path, $link_path . '/' ) ) {
					$is_active = true;
				}
			?>
			<li>
				<a
					href="<?php echo esc_url( $link['url'] ); ?>"
					class="tclas-member-nav__link<?php echo $is_active ? ' tclas-member-nav__link--active' : ''; ?>"
					<?php echo $is_active ? 'aria-current="page"' : ''; ?>
				>
					<i class="bi <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></i>
					<span><?php echo esc_html( $link['label'] ); ?></span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>

		<!-- Logout link -->
		<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="tclas-member-nav__logout">
			<?php esc_html_e( 'Log out', 'tclas' ); ?>
		</a>

		<!-- Mobile: dropdown toggle -->
		<div class="tclas-member-nav__mobile">
			<button
				class="tclas-member-nav__toggle"
				aria-expanded="false"
				aria-controls="member-nav-dropdown"
			>
				<i class="bi <?php echo esc_attr( $active_icon ); ?>" aria-hidden="true"></i>
				<span><?php echo esc_html( $active_label ); ?></span>
				<i class="bi bi-chevron-down tclas-member-nav__chevron" aria-hidden="true"></i>
			</button>

			<ul class="tclas-member-nav__dropdown" id="member-nav-dropdown" role="list" hidden>
				<?php foreach ( $member_nav_links as $link ) :
					$link_path = rtrim( parse_url( $link['url'], PHP_URL_PATH ), '/' );
					$is_active = ( $request_path === $link_path );
					if ( ! $is_active && $link['label'] !== 'Dashboard' && str_starts_with( $request_path, $link_path . '/' ) ) {
						$is_active = true;
					}
				?>
				<li>
					<a
						href="<?php echo esc_url( $link['url'] ); ?>"
						class="tclas-member-nav__dropdown-link<?php echo $is_active ? ' tclas-member-nav__dropdown-link--active' : ''; ?>"
						<?php echo $is_active ? 'aria-current="page"' : ''; ?>
					>
						<i class="bi <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></i>
						<span><?php echo esc_html( $link['label'] ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
				<li>
					<a
						href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"
						class="tclas-member-nav__dropdown-link"
					>
						<i class="bi bi-box-arrow-right" aria-hidden="true"></i>
						<span><?php esc_html_e( 'Log out', 'tclas' ); ?></span>
					</a>
				</li>
			</ul>
		</div>

	</div>
</nav>
