<?php
/**
 * TEC v2 — Default template override (TCLAS / Clausen theme)
 *
 * Adds the TCLAS ardoise page header above the TEC events view.
 * Original: the-events-calendar/src/views/v2/default-template.php
 * Override:  [theme]/tribe/events/v2/default-template.php
 *
 * @link https://evnt.is/1aiy
 * @version 5.0.0 (TCLAS override)
 * @package TCLAS
 */

use Tribe\Events\Views\V2\Template_Bootstrap;

get_header();
?>

<div class="tclas-page-header tclas-page-header--ardoise">
	<div class="container-tclas">
		<span class="tclas-eyebrow tclas-eyebrow--light"><?php esc_html_e( 'Community', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title"><?php esc_html_e( 'Events', 'tclas' ); ?></h1>
	</div>
</div>

<?php echo tribe( Template_Bootstrap::class )->get_view_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php
get_footer();
