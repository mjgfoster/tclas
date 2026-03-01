	</main><!-- #main-content -->

	<footer class="tclas-footer" role="contentinfo">
		<div class="tclas-footer__upper">

			<!-- Motto watermark -->
			<div class="tclas-footer__motto-bg" aria-hidden="true">
				<span lang="lb">Mir wëlle bleiwe wat mir sinn</span>
			</div>

			<div class="container-tclas">
				<div class="tclas-footer__grid">

					<!-- Brand column -->
					<div class="tclas-footer__brand">
						<?php if ( has_custom_logo() ) : ?>
							<div class="tclas-footer__brand-logo">
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'medium', false, [ 'alt' => get_bloginfo( 'name' ) ] ); ?></a>
							</div>
						<?php endif; ?>
						<p class="tclas-footer__brand-name">Twin Cities Luxembourg American Society</p>
						<span class="tclas-footer__brand-tagline" lang="lb">Mir sinn hei.</span>
						<?php
						$desc = function_exists( 'get_field' ) ? get_field( 'org_address', 'option' ) : '';
						if ( $desc ) :
						?>
							<p class="tclas-footer__brand-desc"><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
					</div>

					<!-- Footer nav: main -->
					<div>
						<h2 class="tclas-footer__col-title"><?php esc_html_e( 'Explore', 'tclas' ); ?></h2>
						<?php
						wp_nav_menu( [
							'theme_location' => 'footer-main',
							'menu_class'     => 'tclas-footer__nav',
							'container'      => false,
							'walker'         => new TCLAS_Footer_Nav_Walker(),
							'fallback_cb'    => function() {
								echo '<ul class="tclas-footer__nav">';
								$links = [
									__( 'About',      'tclas' ) => home_url( '/about/' ),
									__( 'Events',     'tclas' ) => home_url( '/events/' ),
									__( 'News',       'tclas' ) => home_url( '/news/' ),
									__( 'Resources',  'tclas' ) => home_url( '/resources/' ),
									__( 'Membership', 'tclas' ) => home_url( '/join/' ),
								];
								foreach ( $links as $label => $url ) {
									echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
								}
								echo '</ul>';
							},
						] );
						?>
					</div>

					<!-- Footer nav: org -->
					<div>
						<h2 class="tclas-footer__col-title"><?php esc_html_e( 'Organisation', 'tclas' ); ?></h2>
						<?php
						wp_nav_menu( [
							'theme_location' => 'footer-org',
							'menu_class'     => 'tclas-footer__nav',
							'container'      => false,
							'walker'         => new TCLAS_Footer_Nav_Walker(),
							'fallback_cb'    => function() {
								echo '<ul class="tclas-footer__nav">';
								$links = [
									__( 'Board of directors', 'tclas' ) => home_url( '/about/board/' ),
									__( 'Contact',            'tclas' ) => home_url( '/contact/' ),
									__( 'Privacy policy',    'tclas' ) => get_privacy_policy_url(),
								];
								foreach ( $links as $label => $url ) {
									if ( $url ) echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
								}
								echo '</ul>';
							},
						] );
						?>
					</div>

					<!-- Newsletter -->
					<div class="tclas-footer__newsletter">
						<h2 class="tclas-footer__col-title"><?php esc_html_e( 'Stay in touch', 'tclas' ); ?></h2>
						<p><?php esc_html_e( 'Bimonthly news, events, and Luxembourg stories — in your inbox.', 'tclas' ); ?></p>
						<?php tclas_footer_newsletter_form(); ?>
					</div>

				</div><!-- .tclas-footer__grid -->
			</div><!-- .container-tclas -->
		</div><!-- .tclas-footer__upper -->

		<!-- Bottom bar -->
		<div class="tclas-footer__bottom">
			<div class="container-tclas" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;width:100%;">
				<span>
					&copy; <?php echo esc_html( date( 'Y' ) ); ?> Twin Cities Luxembourg American Society.
					<?php esc_html_e( 'All rights reserved.', 'tclas' ); ?>
				</span>
				<nav class="tclas-footer__legal" aria-label="<?php esc_attr_e( 'Legal links', 'tclas' ); ?>">
					<?php if ( get_privacy_policy_url() ) : ?>
						<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy policy', 'tclas' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'tclas' ); ?></a>
					<?php
					$fb_url = function_exists( 'get_field' ) ? get_field( 'facebook_group_url', 'option' ) : 'https://www.facebook.com/groups/tclas';
					if ( $fb_url ) :
					?>
						<a href="<?php echo esc_url( $fb_url ); ?>" target="_blank" rel="noopener noreferrer">Facebook<span class="sr-only"> (<?php esc_html_e( 'opens in new window', 'tclas' ); ?>)</span></a>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</footer>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
