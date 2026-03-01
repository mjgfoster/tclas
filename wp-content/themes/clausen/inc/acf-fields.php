<?php
/**
 * ACF Theme Options & custom fields
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tclas_register_acf_fields(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page( [
		'page_title' => 'TCLAS Theme options',
		'menu_title' => 'Theme options',
		'menu_slug'  => 'tclas-theme-options',
		'capability' => 'manage_options',
		'redirect'   => false,
		'icon_url'   => 'dashicons-flag',
	] );

	acf_add_options_sub_page( [
		'page_title'  => 'Illustrations',
		'menu_title'  => 'Illustrations',
		'parent_slug' => 'tclas-theme-options',
	] );

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// ── Theme options field group ─────────────────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_options',
		'title'    => 'TCLAS Theme options',
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'tclas-theme-options' ] ] ],
		'fields'   => [
			[
				'key'   => 'field_adobe_fonts_kit_id',
				'label' => 'Adobe Fonts kit ID',
				'name'  => 'adobe_fonts_kit_id',
				'type'  => 'text',
				'default_value' => 'pck6hdf',
				'instructions' => 'Get from Adobe Fonts → Web Projects. Default: pck6hdf (Skolar Sans).',
			],
			[
				'key'   => 'field_footer_mc4wp_form_id',
				'label' => 'Footer newsletter form ID',
				'name'  => 'footer_mc4wp_form_id',
				'type'  => 'number',
				'default_value' => 0,
				'instructions' => 'MC4WP form ID for the footer signup. Set after creating a form in Mailchimp for WP.',
			],
			[
				'key'   => 'field_referral_base_url',
				'label' => 'Referral landing page URL',
				'name'  => 'referral_base_url',
				'type'  => 'url',
				'instructions' => 'URL of the referral welcome page, e.g. https://twincities.lu/welcome/. Leave blank to auto-detect.',
			],
			[
				'key'   => 'field_mailchimp_members_list_id',
				'label' => 'Mailchimp members list ID',
				'name'  => 'mailchimp_members_list_id',
				'type'  => 'text',
				'instructions' => 'Used for auto-subscribe on PMPro activation.',
			],
			[
				'key'   => 'field_facebook_group_url',
				'label' => 'Facebook group URL',
				'name'  => 'facebook_group_url',
				'type'  => 'url',
				'default_value' => 'https://www.facebook.com/groups/tclas',
			],
			[
				'key'          => 'field_instagram_url',
				'label'        => 'Instagram URL',
				'name'         => 'instagram_url',
				'type'         => 'url',
				'instructions' => 'Full URL to the TCLAS Instagram profile. Shown in footer.',
			],
			[
				'key'          => 'field_linkedin_url',
				'label'        => 'LinkedIn URL',
				'name'         => 'linkedin_url',
				'type'         => 'url',
				'instructions' => 'Full URL to the TCLAS LinkedIn page. Shown in footer.',
			],
			[
				'key'   => 'field_national_day_mode',
				'label' => 'National Day mode',
				'name'  => 'national_day_mode',
				'type'  => 'true_false',
				'default_value' => 0,
				'instructions' => 'Enable flag stripe and special hero during Lëtzebuerger Nationalfeierdag season (June). Auto-detected within 7 days of June 23.',
				'ui'    => 1,
			],
			[
				'key'   => 'field_org_address',
				'label' => 'Organisation address',
				'name'  => 'org_address',
				'type'  => 'textarea',
				'rows'  => 3,
				'instructions' => 'Displayed in footer. Can be a mailing address or service area description.',
			],
			[
				'key'   => 'field_org_email',
				'label' => 'Contact email',
				'name'  => 'org_email',
				'type'  => 'email',
			],
			[
				'key'          => 'field_price_individual',
				'label'        => 'Individual membership price ($)',
				'name'         => 'price_individual',
				'type'         => 'number',
				'default_value' => 30,
				'min'          => 0,
				'instructions' => 'Annual price for Individual membership. Shown on the homepage membership section.',
			],
			[
				'key'          => 'field_price_family',
				'label'        => 'Family membership price ($)',
				'name'         => 'price_family',
				'type'         => 'number',
				'default_value' => 45,
				'min'          => 0,
				'instructions' => 'Annual price for Family membership.',
			],
			[
				'key'          => 'field_price_student',
				'label'        => 'Student membership price ($)',
				'name'         => 'price_student',
				'type'         => 'number',
				'default_value' => 15,
				'min'          => 0,
				'instructions' => 'Annual price for Student membership.',
			],
			// ── Homepage hero photo pairs ────────────────────────────────────
			[
				'key'          => 'field_hero_pairs',
				'label'        => 'Hero photo pairs',
				'name'         => 'hero_pairs',
				'type'         => 'repeater',
				'instructions' => 'Up to six Minnesota / Luxembourg photo pairs for the split hero. Municipality is required; photo credit is optional.',
				'layout'       => 'block',
				'max'          => 6,
				'button_label' => 'Add pair',
				'sub_fields'   => [
					[
						'key'           => 'field_hero_pairs_mn_photo',
						'label'         => 'Minnesota photo',
						'name'          => 'mn_photo',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'instructions'  => 'Recommended: 1200×800px landscape.',
						'wrapper'       => [ 'width' => '50' ],
					],
					[
						'key'           => 'field_hero_pairs_lux_photo',
						'label'         => 'Luxembourg photo',
						'name'          => 'lux_photo',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'instructions'  => 'Recommended: 1200×800px landscape.',
						'wrapper'       => [ 'width' => '50' ],
					],
					[
						'key'         => 'field_hero_pairs_mn_municipality',
						'label'       => 'MN municipality',
						'name'        => 'mn_municipality',
						'type'        => 'text',
						'required'    => 1,
						'placeholder' => 'e.g. Minneapolis',
						'wrapper'     => [ 'width' => '25' ],
					],
					[
						'key'          => 'field_hero_pairs_mn_credit',
						'label'        => 'MN photo credit',
						'name'         => 'mn_credit',
						'type'         => 'text',
						'placeholder'  => 'e.g. Photo: Jane Smith',
						'instructions' => 'Optional.',
						'wrapper'      => [ 'width' => '25' ],
					],
					[
						'key'         => 'field_hero_pairs_lux_municipality',
						'label'       => 'LUX municipality',
						'name'        => 'lux_municipality',
						'type'        => 'text',
						'required'    => 1,
						'placeholder' => 'e.g. Luxembourg City',
						'wrapper'     => [ 'width' => '25' ],
					],
					[
						'key'          => 'field_hero_pairs_lux_credit',
						'label'        => 'LUX photo credit',
						'name'         => 'lux_credit',
						'type'         => 'text',
						'placeholder'  => 'e.g. Photo: Visit Luxembourg',
						'instructions' => 'Optional.',
						'wrapper'      => [ 'width' => '25' ],
					],
				],
			],
			// ── Homepage stats ────────────────────────────────────────────────
			[
				'key'          => 'field_homepage_stats',
				'label'        => 'Homepage stats',
				'name'         => 'homepage_stats',
				'type'         => 'repeater',
				'layout'       => 'table',
				'min'          => 0,
				'max'          => 6,
				'button_label' => 'Add stat',
				'instructions' => 'The 4-cell strip beneath testimonials. Example: 300+ Members · 12+ Years · 40 Events per year · 150+ Citizen journeys supported.',
				'sub_fields'   => [
					[
						'key'         => 'field_homepage_stats_value',
						'label'       => 'Value',
						'name'        => 'stat_value',
						'type'        => 'text',
						'placeholder' => 'e.g. 300+',
						'wrapper'     => [ 'width' => '30' ],
					],
					[
						'key'         => 'field_homepage_stats_label',
						'label'       => 'Label',
						'name'        => 'stat_label',
						'type'        => 'text',
						'placeholder' => 'e.g. Members',
						'wrapper'     => [ 'width' => '70' ],
					],
				],
			],
			// ── Testimonials (shared: homepage + ancestry page) ───────────────
			[
				'key'          => 'field_testimonials',
				'label'        => 'Testimonials',
				'name'         => 'testimonials',
				'type'         => 'repeater',
				'layout'       => 'block',
				'min'          => 0,
				'button_label' => 'Add testimonial',
				'instructions' => 'Shared by the homepage (first 3) and the Ancestry page. Add at least 3 rows before the board preview.',
				'sub_fields'   => [
					[
						'key'   => 'field_testimonials_name',
						'label' => 'Name',
						'name'  => 'name',
						'type'  => 'text',
					],
					[
						'key'          => 'field_testimonials_member_since',
						'label'        => 'Member since',
						'name'         => 'member_since',
						'type'         => 'text',
						'placeholder'  => 'e.g. 2019',
						'instructions' => 'Year only.',
						'wrapper'      => [ 'width' => '50' ],
					],
					[
						'key'         => 'field_testimonials_location',
						'label'       => 'Location',
						'name'        => 'location',
						'type'        => 'text',
						'placeholder' => 'e.g. Minneapolis, MN',
						'wrapper'     => [ 'width' => '50' ],
					],
					[
						'key'           => 'field_testimonials_photo',
						'label'         => 'Photo',
						'name'          => 'photo',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'thumbnail',
						'instructions'  => 'Square headshot, 200×200px or larger.',
					],
					[
						'key'          => 'field_testimonials_quote',
						'label'        => 'Quote',
						'name'         => 'quote',
						'type'         => 'textarea',
						'rows'         => 3,
						'instructions' => 'Do not include quotation marks — they will be added by the template.',
					],
				],
			],
		],
		[
			'key'          => 'field_ltz_terms',
			'label'        => 'Luxembourgish vocabulary',
			'name'         => 'ltz_terms',
			'type'         => 'repeater',
			'instructions' => 'Words and phrases to wrap in <span lang="lb">. List longer phrases before shorter words — multi-word phrases must come first. Leave empty to use the built-in fallback list. Changes apply within 12 hours (or after saving).',
			'layout'       => 'table',
			'button_label' => 'Add term',
			'sub_fields'   => [
				[
					'key'     => 'field_ltz_terms_ltz',
					'label'   => 'Luxembourgish term',
					'name'    => 'ltz_term',
					'type'    => 'text',
					'wrapper' => [ 'width' => '50' ],
				],
				[
					'key'          => 'field_ltz_terms_en',
					'label'        => 'English gloss',
					'name'         => 'en_translation',
					'type'         => 'text',
					'wrapper'      => [ 'width' => '50' ],
					'instructions' => 'Optional — for editorial reference only, not displayed on the site.',
				],
			],
		],
	] );

	// ── Illustrations field group ─────────────────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_illustrations',
		'title'    => 'Illustrations',
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'tclas-theme-options-illustrations' ] ] ],
		'fields'   => [
			[
				'key'   => 'field_hero_illustration',
				'label' => 'Hero illustration (desktop)',
				'name'  => 'hero_illustration',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'instructions'  => 'Recommended: 1440×640px PNG or SVG. Falls back to bundled hero.svg.',
			],
			[
				'key'   => 'field_hero_illustration_mobile',
				'label' => 'Hero illustration (mobile)',
				'name'  => 'hero_illustration_mobile',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'instructions'  => 'Recommended: 640×640px portrait crop. Falls back to desktop version.',
			],
			[
				'key'   => 'field_welcome_illustration',
				'label' => 'Welcome section illustration',
				'name'  => 'welcome_illustration',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'instructions'  => 'Recommended: ~480×480px. Interior gathering scene.',
			],
			[
				'key'   => 'field_membership_illustration',
				'label' => 'Membership section illustration',
				'name'  => 'membership_illustration',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'instructions'  => 'Recommended: 1200×360px wide panoramic. Outdoor summer gathering.',
			],
			[
				'key'   => 'field_member_gate_illustration',
				'label' => 'Member gate illustration',
				'name'  => 'member_gate_illustration',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'thumbnail',
				'instructions'  => 'Recommended: 320×320px. Friendly lion at a doorway.',
			],
			[
				'key'   => 'field_referral_lion_illustration',
				'label' => 'Referral card illustration',
				'name'  => 'referral_lion_illustration',
				'type'  => 'image',
				'return_format' => 'array',
				'preview_size'  => 'thumbnail',
				'instructions'  => 'Recommended: 160×160px. Lion holding an envelope.',
			],
		],
	] );

	// ── Luxembourg Story fields ───────────────────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_story',
		'title'    => 'Story details',
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'tclas_story' ] ] ],
		'fields'   => [
			[
				'key'   => 'field_story_member_name',
				'label' => 'Member name',
				'name'  => 'story_member_name',
				'type'  => 'text',
				'instructions' => 'As you would like it to appear on the site.',
			],
			[
				'key'   => 'field_story_connection_type',
				'label' => 'Luxembourg connection',
				'name'  => 'story_connection_type',
				'type'  => 'checkbox',
				'choices' => [
					'ancestry'    => 'Family ancestry',
					'citizenship' => 'Citizenship / passport',
					'marriage'    => 'Married into a Luxembourg family',
					'work'        => 'Work / career connection',
					'travel'      => 'Frequent traveller / enthusiast',
					'culture'     => 'Food, language, culture',
				],
				'layout' => 'vertical',
			],
			[
				'key'   => 'field_story_immigration_generation',
				'label' => 'Immigration generation',
				'name'  => 'story_immigration_generation',
				'type'  => 'select',
				'choices' => [
					'1st'     => '1st generation (born in Luxembourg)',
					'2nd'     => '2nd generation (parents born in Luxembourg)',
					'3rd'     => '3rd generation',
					'4th'     => '4th generation',
					'further' => 'Further back',
					'na'      => 'Not applicable',
				],
				'allow_null' => 1,
			],
			[
				'key'   => 'field_story_citizenship_status',
				'label' => 'Citizenship / passport status',
				'name'  => 'story_citizenship_status',
				'type'  => 'select',
				'choices' => [
					'citizen'     => 'Luxembourg citizen',
					'in_progress' => 'Application in progress',
					'eligible'    => 'Eligible, not applied',
					'researching' => 'Researching eligibility',
					'na'          => 'Not applicable',
				],
				'allow_null' => 1,
			],
		],
	] );

	// ── Board member fields ───────────────────────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_board',
		'title'    => 'Board member details',
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'tclas_board' ] ] ],
		'fields'   => [
			[
				'key'   => 'field_board_role',
				'label' => 'Role / title',
				'name'  => 'board_role',
				'type'  => 'text',
			],
			[
				'key'   => 'field_board_bio',
				'label' => 'Bio',
				'name'  => 'board_bio',
				'type'  => 'textarea',
				'rows'  => 4,
			],
			[
				'key'   => 'field_board_email',
				'label' => 'Contact email',
				'name'  => 'board_email',
				'type'  => 'email',
			],
		],
	] );

	// ── Commune profile fields (term meta) ────────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_commune',
		'title'    => 'Commune Profile',
		'location' => [ [ [ 'param' => 'taxonomy', 'operator' => '==', 'value' => 'tclas_commune' ] ] ],
		'fields'   => [
			[
				'key'          => 'field_commune_wikipedia_url',
				'label'        => 'Wikipedia URL',
				'name'         => 'tclas_commune_wikipedia_url',
				'type'         => 'url',
				'instructions' => 'Link to the English or Luxembourgish Wikipedia article for this commune.',
			],
			[
				'key'          => 'field_commune_lux_website',
				'label'        => '.lu Official Website',
				'name'         => 'tclas_commune_lux_website_url',
				'type'         => 'url',
				'instructions' => 'Link to the official commune or municipal website on .lu domain.',
			],
		],
	] );

	// ── Department icons (term meta — Newsletter Browse by Topic, Phase 4) ──
	acf_add_local_field_group( [
		'key'      => 'group_tclas_department',
		'title'    => 'Department',
		'location' => [ [ [ 'param' => 'taxonomy', 'operator' => '==', 'value' => 'tclas_department' ] ] ],
		'fields'   => [
			[
				'key'           => 'field_department_icon',
				'label'         => 'Icon',
				'name'          => 'department_icon',
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'thumbnail',
				'instructions'  => 'SVG, PNG or WebP, 64×64px. Shown on the Newsletter Browse by Topic grid. Leave blank to render the card without an icon image.',
			],
		],
	] );

	// ── Newsletter / Loon & Lion — post fields ────────────────────────────
	acf_add_local_field_group( [
		'key'      => 'group_tclas_newsletter',
		'title'    => 'Loon & Lion Issue',
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ] ] ],
		'fields'   => [
			[
				'key'          => 'field_tclas_issue_date',
				'label'        => 'Issue date',
				'name'         => 'tclas_issue_date',
				'type'         => 'text',
				'placeholder'  => 'YYYY-MM',
				'instructions' => 'Format: YYYY-MM (e.g. 2027-01). Groups this article with others in the same issue.',
			],
			[
				'key'           => 'field_tclas_issue_order',
				'label'         => 'Order within issue',
				'name'          => 'tclas_issue_order',
				'type'          => 'number',
				'default_value' => 99,
				'min'           => 1,
				'instructions'  => 'Lower numbers appear first in the table of contents. Main Story should be 1.',
			],
		],
	] );
}
add_action( 'acf/init', 'tclas_register_acf_fields' );
