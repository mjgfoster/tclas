<?php
/**
 * Cookie Consent Banner + Preferences Modal
 *
 * Lightweight GDPR/CCPA-compliant consent UI.
 * Rendered once in header.php, controlled by cookie-consent.js.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$privacy_url = get_privacy_policy_url();
?>

<!-- Cookie Consent Banner -->
<div id="tclas-consent-banner" class="tclas-consent-banner" hidden role="dialog" aria-label="<?php esc_attr_e( 'Cookie consent', 'tclas' ); ?>">
	<div class="tclas-consent-banner__inner">
		<div class="tclas-consent-banner__text">
			<p>
				<?php
				printf(
					/* translators: %s: link to privacy policy */
					esc_html__( 'We use essential cookies to run this site and optional cookies for analytics. Read our %s for details.', 'tclas' ),
					$privacy_url
						? '<a href="' . esc_url( $privacy_url ) . '">' . esc_html__( 'privacy policy', 'tclas' ) . '</a>'
						: esc_html__( 'privacy policy', 'tclas' )
				);
				?>
			</p>
			<button type="button" class="tclas-consent-banner__manage tclas-consent-manage">
				<?php esc_html_e( 'Manage preferences', 'tclas' ); ?>
			</button>
		</div>
		<div class="tclas-consent-banner__actions">
			<button type="button" id="tclas-consent-accept" class="tclas-consent-btn tclas-consent-btn--accept">
				<?php esc_html_e( 'Accept all', 'tclas' ); ?>
			</button>
			<button type="button" id="tclas-consent-reject" class="tclas-consent-btn tclas-consent-btn--reject">
				<?php esc_html_e( 'Essential only', 'tclas' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Cookie Preferences Modal -->
<div id="tclas-consent-prefs" class="tclas-consent-prefs" hidden role="dialog" aria-label="<?php esc_attr_e( 'Cookie preferences', 'tclas' ); ?>" aria-modal="true">
	<div class="tclas-consent-prefs__panel" tabindex="-1">
		<div class="tclas-consent-prefs__header">
			<h2><?php esc_html_e( 'Cookie preferences', 'tclas' ); ?></h2>
			<button type="button" id="tclas-pref-close" class="tclas-consent-prefs__close" aria-label="<?php esc_attr_e( 'Close', 'tclas' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
			</button>
		</div>

		<p class="tclas-consent-prefs__desc">
			<?php esc_html_e( 'Choose which cookies you allow. Essential cookies are always active because they keep the site working. You can change your preferences anytime.', 'tclas' ); ?>
		</p>

		<!-- Essential (always on) -->
		<div class="tclas-consent-category">
			<div class="tclas-consent-category__row">
				<span class="tclas-consent-category__label"><?php esc_html_e( 'Essential', 'tclas' ); ?></span>
				<label class="tclas-toggle">
					<input type="checkbox" checked disabled>
					<span class="tclas-toggle__slider"></span>
				</label>
			</div>
			<p class="tclas-consent-category__desc">
				<?php esc_html_e( 'Required for login, navigation, and security. These cannot be turned off.', 'tclas' ); ?>
			</p>
		</div>

		<!-- Analytics -->
		<div class="tclas-consent-category">
			<div class="tclas-consent-category__row">
				<span class="tclas-consent-category__label"><?php esc_html_e( 'Analytics', 'tclas' ); ?></span>
				<label class="tclas-toggle">
					<input type="checkbox" id="tclas-pref-analytics">
					<span class="tclas-toggle__slider"></span>
				</label>
			</div>
			<p class="tclas-consent-category__desc">
				<?php esc_html_e( 'Help us understand how visitors use the site so we can improve it. Data is anonymized.', 'tclas' ); ?>
			</p>
		</div>

		<!-- Marketing -->
		<div class="tclas-consent-category">
			<div class="tclas-consent-category__row">
				<span class="tclas-consent-category__label"><?php esc_html_e( 'Marketing', 'tclas' ); ?></span>
				<label class="tclas-toggle">
					<input type="checkbox" id="tclas-pref-marketing">
					<span class="tclas-toggle__slider"></span>
				</label>
			</div>
			<p class="tclas-consent-category__desc">
				<?php esc_html_e( 'Track email engagement so we can send you more relevant content. Used by our newsletter platform (Brevo).', 'tclas' ); ?>
			</p>
		</div>

		<button type="button" id="tclas-pref-save" class="tclas-consent-prefs__save">
			<?php esc_html_e( 'Save preferences', 'tclas' ); ?>
		</button>
	</div>
</div>
