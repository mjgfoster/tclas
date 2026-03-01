<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page">

	<?php
	// Renew banner for expiring / expired members
	$status = tclas_membership_status();
	if ( in_array( $status, [ 'expiring', 'expired' ], true ) ) :
		$days      = tclas_days_to_expiry();
		$renew_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'checkout' ) : '#';
		$message   = $status === 'expired'
			? __( 'Your membership has expired.', 'tclas' )
			: sprintf( _n( 'Your membership expires in %d day.', 'Your membership expires in %d days.', $days, 'tclas' ), $days );
	?>
	<div class="tclas-renew-banner" role="alert">
		<span>
			<?php echo esc_html( $message ); ?>
			<a href="<?php echo esc_url( $renew_url ); ?>"><?php esc_html_e( 'Renew my membership →', 'tclas' ); ?></a>
		</span>
		<button class="tclas-renew-banner__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'tclas' ); ?>">×</button>
	</div>
	<?php endif; ?>

	<header class="tclas-header" role="banner">
		<div class="tclas-flag-stripe" aria-hidden="true"></div>
		<div class="container-tclas">
			<div class="tclas-header__inner">

				<!-- Brand -->
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tclas-brand" rel="home">
					<?php if ( has_custom_logo() ) : ?>
						<div class="tclas-brand__logo">
							<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'medium', false, [ 'alt' => get_bloginfo( 'name' ) ] ); ?>
						</div>
					<?php else : ?>
						<div class="tclas-brand__logo" aria-hidden="true" style="display:flex;align-items:center;justify-content:center;color:#C0001A;font-size:1.4rem;font-weight:700;">🦁</div>
					<?php endif; ?>
					<div class="tclas-brand__text">
						<span class="tclas-brand__name">Twin Cities Luxembourg</span>
						<span class="tclas-brand__tagline">American Society</span>
					</div>
				</a>

				<!-- Primary navigation -->
				<nav aria-label="<?php esc_attr_e( 'Primary navigation', 'tclas' ); ?>">
					<?php
					wp_nav_menu( [
						'theme_location' => 'primary',
						'menu_class'     => 'tclas-nav',
						'container'      => false,
						'walker'         => new TCLAS_Nav_Walker(),
						'fallback_cb'    => '__return_false',
					] );
					?>
				</nav>

				<!-- Header actions -->
				<div class="tclas-header__actions">
					<?php tclas_render_header_actions(); ?>
					<button
						class="tclas-hamburger"
						aria-expanded="false"
						aria-controls="tclas-nav"
						aria-label="<?php esc_attr_e( 'Open menu', 'tclas' ); ?>"
					>☰</button>
				</div>

			</div><!-- .tclas-header__inner -->
		</div><!-- .container-tclas -->
	</header>

	<main id="main-content" tabindex="-1">
