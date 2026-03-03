<?php
/**
 * Newsletter single-issue rewrite
 *
 * Registers /newsletter/issue/{YYYY-MM}/ as a virtual URL that renders
 * a single-issue page template without requiring a WordPress page object.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


// ── Rewrite rule ─────────────────────────────────────────────────────────────

add_action( 'init', 'tclas_nl_issue_rewrite_rule' );

function tclas_nl_issue_rewrite_rule(): void {
	add_rewrite_rule(
		'^newsletter/issue/([^/]+)/?$',
		'index.php?tclas_newsletter_issue=$matches[1]',
		'top'
	);
}


// ── Query var ─────────────────────────────────────────────────────────────────

add_filter( 'query_vars', 'tclas_nl_issue_query_var' );

function tclas_nl_issue_query_var( array $vars ): array {
	$vars[] = 'tclas_newsletter_issue';
	return $vars;
}


// ── Template redirect ─────────────────────────────────────────────────────────

add_action( 'template_redirect', 'tclas_nl_issue_template_redirect' );

function tclas_nl_issue_template_redirect(): void {
	$issue_date = get_query_var( 'tclas_newsletter_issue' );
	if ( ! $issue_date || ! preg_match( '/^\d{4}-\d{2}$/', $issue_date ) ) {
		return;
	}

	$template = get_theme_file_path( 'page-templates/page-newsletter-issue.php' );
	if ( file_exists( $template ) ) {
		include $template;
		exit;
	}
}
