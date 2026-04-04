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
<?php get_template_part( 'template-parts/cookie', 'consent' ); ?>

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
					<img class="tclas-brand__lockup" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-header.svg' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="322" height="144">
				</a>

				<!-- Desktop navigation -->
				<nav class="tclas-header__desktop-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'tclas' ); ?>">
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

				<!-- Desktop utility bar: search + member action -->
				<div class="tclas-header__utility">
					<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" class="tclas-search-btn" aria-label="<?php esc_attr_e( 'Search', 'tclas' ); ?>">
						<svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					</a>
					<?php tclas_render_header_actions(); ?>
				</div>

				<!-- Mobile toggle -->
				<button
					class="tclas-hamburger"
					aria-expanded="false"
					aria-controls="tclas-nav-drawer"
					aria-label="<?php esc_attr_e( 'Toggle menu', 'tclas' ); ?>"
				>
					<svg class="tclas-hamburger__menu" aria-hidden="true" focusable="false" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
					<svg class="tclas-hamburger__close" aria-hidden="true" focusable="false" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>

			</div><!-- .tclas-header__inner -->
		</div><!-- .container-tclas -->

		<!-- Mobile navigation drawer -->
		<div class="tclas-nav-drawer" id="tclas-nav-drawer">
			<nav role="navigation" aria-label="<?php esc_attr_e( 'Mobile navigation', 'tclas' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'menu_class'     => 'tclas-nav-drawer__links',
					'container'      => false,
					'walker'         => new TCLAS_Nav_Walker(),
					'fallback_cb'    => '__return_false',
				] );
				?>
			</nav>
			<div class="tclas-nav-drawer__utility">
				<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" class="tclas-nav-drawer__search">
					<svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<span><?php esc_html_e( 'Search', 'tclas' ); ?></span>
				</a>
				<?php tclas_render_header_actions( true ); ?>
			</div>
		</div><!-- .tclas-nav-drawer -->

	</header>

	<?php
	// Member navigation bar — shown sitewide for active members
	if ( tclas_is_member() ) {
		get_template_part( 'template-parts/member', 'nav' );
	}
	?>

	<main id="main-content" tabindex="-1">
