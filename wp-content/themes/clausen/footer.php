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
						<div class="tclas-footer__brand-logo">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
								<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-footer.svg' ) ); ?>"
								     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
								     width="507" height="144">
							</a>
						</div>
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
						<h2 class="tclas-footer__col-title"><?php esc_html_e( 'About', 'tclas' ); ?></h2>
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

						<!-- Social icons -->
						<?php
						$fb_url  = function_exists( 'get_field' ) ? get_field( 'facebook_group_url', 'option' ) : '';
						$ig_url  = function_exists( 'get_field' ) ? get_field( 'instagram_url', 'option' ) : '';
						$li_url  = function_exists( 'get_field' ) ? get_field( 'linkedin_url', 'option' ) : '';
						if ( $fb_url || $ig_url || $li_url ) :
						?>
						<nav class="tclas-footer__social tclas-footer__social--newsletter" aria-label="<?php esc_attr_e( 'Social media', 'tclas' ); ?>">
							<?php if ( $fb_url ) : ?>
								<a href="<?php echo esc_url( $fb_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Facebook (opens in new window)', 'tclas' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
								</a>
							<?php endif; ?>
							<?php if ( $ig_url ) : ?>
								<a href="<?php echo esc_url( $ig_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Instagram (opens in new window)', 'tclas' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
								</a>
							<?php endif; ?>
							<?php if ( $li_url ) : ?>
								<a href="<?php echo esc_url( $li_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'LinkedIn (opens in new window)', 'tclas' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
								</a>
							<?php endif; ?>
						</nav>
						<?php endif; ?>
					</div>

				</div><!-- .tclas-footer__grid -->
			</div><!-- .container-tclas -->
		</div><!-- .tclas-footer__upper -->

		<!-- Bottom bar -->
		<div class="tclas-footer__bottom">
			<div class="container-tclas">
				<span>
					&copy; <?php echo esc_html( date( 'Y' ) ); ?> Twin Cities Luxembourg American Society.
					<?php esc_html_e( 'All rights reserved.', 'tclas' ); ?>
				</span>
				<div class="tclas-footer__bottom-right">

					<!-- Legal links -->
					<nav class="tclas-footer__legal" aria-label="<?php esc_attr_e( 'Legal links', 'tclas' ); ?>">
						<?php if ( get_privacy_policy_url() ) : ?>
							<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy policy', 'tclas' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( home_url( '/about/terms/' ) ); ?>"><?php esc_html_e( 'Terms of use', 'tclas' ); ?></a>
						<button type="button" class="tclas-footer__cookie-link tclas-consent-manage"><?php esc_html_e( 'Manage cookies', 'tclas' ); ?></button>
						<a href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>"><?php esc_html_e( 'Accessibility statement', 'tclas' ); ?></a>
					</nav>

				</div><!-- .tclas-footer__bottom-right -->
			</div>
		</div>
	</footer>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
