<?php
/**
 * Populate ACF fields with default content.
 *
 * Run once via WP-CLI to pre-fill every editable ACF field with the text
 * that was previously hardcoded in the templates. After running this,
 * every field will be visible and editable in the WP Admin page editor.
 *
 * Usage:
 *   wp eval-file wp-content/themes/clausen/bin/populate-acf-defaults.php
 *
 * Safe to re-run — only writes fields that are currently empty.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "Run via WP-CLI: wp eval-file wp-content/themes/clausen/bin/populate-acf-defaults.php\n";
	exit( 1 );
}

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF Pro must be active.' );
}

// ─── Page IDs ────────────────────────────────────────────────────────────
$page_ids = [
	'home'        => 17,
	'citizenship' => 96,
	'ancestry'    => 97,
	'msp_lux'     => 98,
	'join'        => 23,
];

$wrote = 0;

/**
 * Helper: set a field only when it's currently empty.
 */
function tclas_seed( $field, $value, $post_id ) {
	global $wrote;
	$current = get_field( $field, $post_id );
	if ( $current ) {
		return;
	}
	update_field( $field, $value, $post_id );
	$wrote++;
}

// ─────────────────────────────────────────────────────────────────────────
// 1. HOMEPAGE (ID 17)
// ─────────────────────────────────────────────────────────────────────────
$hp = $page_ids['home'];

tclas_seed( 'hp_mission_body', '<p>TCLAS&mdash;the Twin Cities Luxembourg American Society&mdash;is a group based in the Minneapolis&ndash;Saint Paul, Minnesota, metro that brings together Americans of Luxembourgish descent, dual citizens of Luxembourg and the United States, and expatriate Luxembourgers living in the Upper Midwest.</p>', $hp );

tclas_seed( 'hp_cta_heading', 'Think you might qualify?', $hp );

tclas_seed( 'hp_cta_body', '<p>Luxembourg recognizes citizenship through ancestry going back multiple generations. Our eligibility quiz walks you through the criteria for Articles 7, 23, and 7+23&mdash;in plain English.</p>', $hp );


// ─────────────────────────────────────────────────────────────────────────
// 2. CITIZENSHIP (ID 96)
// ─────────────────────────────────────────────────────────────────────────
$cit = $page_ids['citizenship'];

tclas_seed( 'cit_lede', '<p>Under the Law of June 8, 2017 on Luxembourgish nationality &mdash; amended and expanded in 2021 &mdash; descendants of Luxembourg citizens may be eligible to recover or obtain citizenship. The pathway is open to multiple generations of descendants, whether your ancestor emigrated in the 1880s or the 1950s.</p>', $cit );

tclas_seed( 'cit_lede_2', '<p>The quiz below walks through your family history generation by generation and gives you a personalized assessment of your eligibility. It is not a legal opinion, but it reflects the published criteria as they apply to most cases. After the quiz, you\'ll find guidance on what to do next.</p>', $cit );

tclas_seed( 'cit_next_steps', [
	[
		'step_title' => 'Gather your documentation',
		'step_body'  => '<p>You\'ll need birth, marriage, and death records for yourself and each Luxembourg ancestor in your lineage. Your local vital records office handles U.S. records; Luxembourg\'s National Archives (ANLux) holds records from the Grand Duchy.</p>',
	],
	[
		'step_title' => 'Research your ancestry',
		'step_body'  => '<p>TCLAS members can use the <a href="/ancestry/">ancestral commune map</a> to find others with roots in the same communes, and connect with experienced researchers in our community forums.</p>',
	],
	[
		'step_title' => 'Contact the Luxembourg consulate',
		'step_body'  => '<p>U.S. residents apply through a Luxembourg diplomatic post. Minnesota residents are typically served by the Consulate General in Chicago, or the embassy in Washington, D.C. for some cases.</p>',
	],
	[
		'step_title' => 'Submit your application',
		'step_body'  => '<p>Applications are processed by Luxembourg\'s SCAS (Service Central d\'Assistance Sociale). Processing times vary &mdash; plan for 12 to 24 months. The consulate will guide you through required forms and notarization.</p>',
	],
], $cit );

tclas_seed( 'cit_resources', [
	[
		'res_icon' => '📋',
		'res_name' => 'Law on Luxembourgish nationality (2017, amended 2021)',
		'res_desc' => 'Full text of the nationality law with 2021 amendments — the legal basis for citizenship by ancestry.',
		'res_url'  => 'https://legilux.public.lu/eli/etat/leg/loi/2017/03/08/a289/jo',
	],
	[
		'res_icon' => '🏛️',
		'res_name' => 'Luxembourg Consulate General, Chicago',
		'res_desc' => 'Handles applications from Minnesota and most Midwestern states.',
		'res_url'  => 'https://chicago.mae.lu/',
	],
	[
		'res_icon' => '🗂️',
		'res_name' => 'Archives nationales de Luxembourg (ANLux)',
		'res_desc' => 'Civil registration records from 1796 onward — births, marriages, deaths — searchable online.',
		'res_url'  => 'https://anlux.public.lu/',
	],
	[
		'res_icon' => '📖',
		'res_name' => 'Guichet.lu — Nationality application guide',
		'res_desc' => 'Official step-by-step guide from the Luxembourg government.',
		'res_url'  => 'https://guichet.public.lu/en/citoyens/citoyennete/nationalite-luxembourgeoise/acquisition-recouvrement/recouvrement-nationalite.html',
	],
], $cit );

tclas_seed( 'cit_community_heading', 'TCLAS members have been through it.', $cit );

tclas_seed( 'cit_community_body', '<p>Luxembourg citizenship through ancestry is a long but rewarding process. TCLAS members in the Twin Cities have completed every stage &mdash; from digging through parish records in Luxembourg to mailing apostilled documents to Chicago. They\'re happy to help.</p>', $cit );


// ─────────────────────────────────────────────────────────────────────────
// 3. ANCESTRY (ID 97)
// ─────────────────────────────────────────────────────────────────────────
$anc = $page_ids['ancestry'];

tclas_seed( 'anc_lede', '<p>Tens of thousands of Luxembourgers settled in Minnesota between the 1840s and early 1900s. If your family is among them, the records are out there &mdash; and more accessible than you might think.</p>', $anc );

tclas_seed( 'anc_steps', [
	[
		'step_title' => 'Start with what you know',
		'step_body'  => '<p>Gather family documents, photos, and stories before you search online. Names, approximate dates, and any mention of a Luxembourg town or region are the most valuable starting points. Ask older relatives &mdash; even vague details help narrow the search.</p>',
	],
	[
		'step_title' => 'Search U.S. records',
		'step_body'  => '<p>Immigration manifests, naturalization papers, and census records often name the exact Luxembourg commune your ancestors came from. FamilySearch (free) and Ancestry.com are the best places to start. The Minnesota Historical Society holds state-specific records including early territorial census data.</p>',
	],
	[
		'step_title' => 'Find the commune',
		'step_body'  => '<p>Knowing which Luxembourg commune your ancestors came from is the key that unlocks everything. Once you have it, you can search civil registration records, church registers, and military rolls. Common sources for the commune name: ship passenger lists, naturalization declarations, and obituaries.</p>',
	],
	[
		'step_title' => 'Explore Luxembourg archives',
		'step_body'  => '<p>Civil registration records from 1796 onward are digitized and searchable online through the Archives nationales de Luxembourg. Pre-1796 records &mdash; parish registers &mdash; are held at ANLux and partially digitized. Most records are freely accessible without an account.</p>',
	],
	[
		'step_title' => 'Connect with the community',
		'step_body'  => '<p>TCLAS members have traced hundreds of Luxembourg family lines across Minnesota. The ancestral commune map shows where our members\' roots lie &mdash; and often, people from the same commune find each other here. Your ancestors may have been neighbors.</p>',
	],
], $anc );

tclas_seed( 'anc_resources_lux', [
	[
		'res_label' => 'Archives nationales de Luxembourg (ANLux)',
		'res_url'   => 'https://anlux.public.lu/fr/rechercher/genealogie.html',
	],
	[
		'res_label' => 'eLuxemburgensia — digitized Luxembourg newspapers and periodicals',
		'res_url'   => 'https://eluxemburgensia.lu/',
	],
	[
		'res_label' => 'Matricula — parish records online',
		'res_url'   => 'https://data.matricula-online.eu/en/LU/luxemburg/',
	],
	[
		'res_label' => 'Luxembourg.public.lu — government genealogy guide',
		'res_url'   => 'https://luxembourg.public.lu/en/society-and-culture/population/genealogy.html',
	],
	[
		'res_label' => 'LuxRoots — genealogy community',
		'res_url'   => 'https://www.luxroots.org/',
	],
], $anc );

tclas_seed( 'anc_resources_us', [
	[
		'res_label' => 'FamilySearch — free records database',
		'res_url'   => 'https://www.familysearch.org/',
	],
	[
		'res_label' => 'Ancestry.com — immigration & census records',
		'res_url'   => 'https://www.ancestry.com/',
	],
	[
		'res_label' => 'Minnesota Historical Society — state records',
		'res_url'   => 'https://www.mnhs.org/',
	],
	[
		'res_label' => 'Luxembourg Heritage Museum, Rollingstone',
		'res_url'   => 'https://www.exploreminnesota.com/profile/rollingstone-luxembourg-heritage-museum/2655',
	],
], $anc );


// ─────────────────────────────────────────────────────────────────────────
// 4. JOIN (ID 23)
// ─────────────────────────────────────────────────────────────────────────
$join = $page_ids['join'];

tclas_seed( 'join_lede', '<p>Whether your Luxembourg story goes back five generations or five months &mdash; or you simply married into one &mdash; you belong here. Membership connects you to a warm, curious community that spans the Atlantic.</p>', $join );

tclas_seed( 'join_tiers', [
	[
		'tier_invite' => 'Just you — and everyone here.',
		'tier_note'   => '',
	],
	[
		'tier_invite' => 'Bring the people you love.',
		'tier_note'   => 'Covers up to four household members.',
	],
	[
		'tier_invite' => 'Same community, adjusted rate.',
		'tier_note'   => 'For full-time students and seniors.',
	],
], $join );

tclas_seed( 'join_perks', [
	[
		'perk_title' => 'The member hub',
		'perk_desc'  => 'A private corner of the site just for members. Connect with others through the member directory, explore the ancestral commune map, and share your story.',
	],
	[
		'perk_title' => 'Member events',
		'perk_desc'  => 'Receptions, celebrations, and informal gatherings — when and where they happen. We\'re a young organization, and the calendar is growing.',
	],
	[
		'perk_title' => 'Citizenship resources',
		'perk_desc'  => 'Guides, links, and a community of people who have navigated the citizenship process — and are happy to share what they learned.',
	],
	[
		'perk_title' => 'Annual member gift',
		'perk_desc'  => 'A limited-edition design each year, for members who like a tangible reminder of where they come from.',
	],
	[
		'perk_title' => 'Partner discounts',
		'perk_desc'  => 'We\'re building relationships with language and cultural organizations in the Twin Cities. Member perks are coming.',
	],
	[
		'perk_title' => 'Community',
		'perk_desc'  => 'The main thing. People who understand why this matters to you — because it matters to them, too.',
	],
], $join );

tclas_seed( 'join_volunteer_body', '', $join ); // Intentionally empty — placeholder text is fine
tclas_seed( 'join_volunteer_email', 'info@tclas.org', $join );
tclas_seed( 'join_bottom_cta', 'Membership is open to anyone with a Luxembourg connection — and to anyone curious enough to find one.', $join );


// ─────────────────────────────────────────────────────────────────────────
// 5. MSP+LUX (ID 98)
// ─────────────────────────────────────────────────────────────────────────
$msp = $page_ids['msp_lux'];

tclas_seed( 'msp_tagline', 'Two (relatively) small places that somehow end up leading the pack.', $msp );

tclas_seed( 'msp_city_mn_header', 'Minneapolis', $msp );
tclas_seed( 'msp_city_lux_header', 'Luxembourg City', $msp );
tclas_seed( 'msp_metro_mn_header', 'Twin Cities metro', $msp );
tclas_seed( 'msp_metro_lux_header', 'Luxembourg', $msp );

tclas_seed( 'msp_city_stats', [
	[
		'stat_label'  => 'Population',
		'mn_value'    => 428000,
		'mn_format'   => 'int',
		'mn_note'     => '',
		'mn_suffix'   => '',
		'lux_value'   => '137000',
		'lux_format'  => 'int',
		'lux_note'    => '',
		'lux_suffix'  => '',
		'lux_is_emoji' => false,
	],
	[
		'stat_label'  => 'Green space',
		'mn_value'    => 15,
		'mn_format'   => 'pct',
		'mn_note'     => 'of city is parkland',
		'mn_suffix'   => '',
		'lux_value'   => '48',
		'lux_format'  => 'pct',
		'lux_note'    => 'of city is green space',
		'lux_suffix'  => '',
		'lux_is_emoji' => false,
	],
	[
		'stat_label'  => 'Founded',
		'mn_value'    => 1867,
		'mn_format'   => 'year',
		'mn_note'     => 'incorporated',
		'mn_suffix'   => '',
		'lux_value'   => '963',
		'lux_format'  => 'year',
		'lux_note'    => 'first recorded mention',
		'lux_suffix'  => ' AD',
		'lux_is_emoji' => false,
	],
], $msp );

tclas_seed( 'msp_metro_stats', [
	[
		'stat_label'  => 'Population',
		'mn_value'    => 3760000,
		'mn_format'   => 'm2',
		'mn_note'     => '',
		'mn_suffix'   => '',
		'lux_value'   => '672000',
		'lux_format'  => 'int',
		'lux_note'    => '',
		'lux_suffix'  => '',
		'lux_is_emoji' => false,
	],
	[
		'stat_label'  => 'GDP per capita',
		'mn_value'    => 93000,
		'mn_format'   => 'usd-k',
		'mn_note'     => 'per capita',
		'mn_suffix'   => '',
		'lux_value'   => '143000',
		'lux_format'  => 'usd-k',
		'lux_note'    => "world's highest (PPP)",
		'lux_suffix'  => '',
		'lux_is_emoji' => false,
	],
	[
		'stat_label'  => 'Air traffic',
		'mn_value'    => 37200000,
		'mn_format'   => 'm1',
		'mn_note'     => 'passengers/yr at MSP (~9.9 per resident)',
		'mn_suffix'   => '',
		'lux_value'   => '5200000',
		'lux_format'  => 'm1',
		'lux_note'    => 'passengers/yr at Findel (~7.7 per resident)',
		'lux_suffix'  => '',
		'lux_is_emoji' => false,
	],
	[
		'stat_label'  => 'Our connection',
		'mn_value'    => 2040,
		'mn_format'   => 'int',
		'mn_note'     => 'Luxembourg dual citizens — more than any other U.S. metro',
		'mn_suffix'   => '',
		'lux_value'   => '🏠',
		'lux_format'  => '',
		'lux_note'    => 'Minnesota calls them home',
		'lux_suffix'  => '',
		'lux_is_emoji' => true,
	],
], $msp );

tclas_seed( 'msp_timeline', [
	[
		'tl_year'  => '1840s–1880s',
		'tl_title' => 'Iron ore, two continents',
		'tl_body'  => '<p>Minnesota\'s Iron Range and Luxembourg\'s Minett region both discovered iron ore in the same era. Luxembourg\'s national steelmaker ARBED&mdash;now ArcelorMittal&mdash;operated Hibbing Taconite and Minorca Mine in Minnesota for decades before selling to Cleveland-Cliffs in 2020.</p>',
	],
	[
		'tl_year'  => '1883',
		'tl_title' => 'A Luxembourger founds American medicine',
		'tl_body'  => '<p>Sister Alfred Moes, born in Remich, Luxembourg, had already built hospitals across Minnesota when a tornado devastated Rochester in 1883. She struck a deal with the Mayo brothers: she\'d fund the hospital if they\'d staff it. The result became Mayo Clinic.</p>',
	],
	[
		'tl_year'  => 'Late 1800s',
		'tl_title' => 'A village that never forgot',
		'tl_body'  => '<p>Rollingstone, Minn. (pop. ~600) remains the most intact Luxembourger-American community in the United States. Its Luxembourg Heritage Museum was listed on the National Register of Historic Places in 2021. The town has been a sister city with Bertrange, Luxembourg since 1980.</p>',
	],
	[
		'tl_year'  => 'Today',
		'tl_title' => 'Dual citizens, dual loyalties',
		'tl_body'  => '<p>The Twin Cities metro is home to roughly 2,040 people holding both American and Luxembourgish passports&mdash;more than any other U.S. metro area. TCLAS is where they find each other.</p>',
	],
], $msp );

tclas_seed( 'msp_parallels', [
	[
		'pl_icon' => '🏢',
		'pl_body' => '<p>Minneapolis hosts 17 Fortune 500 headquarters in a metro of 3.76 million. Luxembourg City is home to the EU Court of Justice, the European Investment Bank, and the Court of Auditors&mdash;in a country of 672,000. Both cities lead their regions by a lot.</p>',
	],
	[
		'pl_icon' => '💰',
		'pl_body' => '<p>Finance capitals, both. The Twin Cities anchors the Federal Reserve\'s 9th District. Luxembourg is the world\'s second-largest investment fund domicile, with over $6 trillion in assets under management.</p>',
	],
	[
		'pl_icon' => '🌳',
		'pl_body' => '<p>Minneapolis is famously green&mdash;15% of the city is parkland. Luxembourg one-ups it: 48% of the capital is green space, and 52% of the entire country is protected land, the highest share in the EU.</p>',
	],
	[
		'pl_icon' => '⭐',
		'pl_body' => '<p>Tim Walz, Minnesota\'s 41st governor, has Luxembourgish roots. He\'s in good company: Luxembourg-descended families have shaped Minnesota politics, medicine, and industry for 150 years.</p>',
	],
], $msp );

tclas_seed( 'msp_resources_lux', [
	[
		'res_label' => 'Luxembourg for Finance',
		'res_url'   => 'https://www.luxembourgforfinance.com/',
	],
	[
		'res_label' => 'Visit Luxembourg',
		'res_url'   => 'https://www.visitluxembourg.com/',
	],
	[
		'res_label' => 'Luxembourg American Chamber of Commerce',
		'res_url'   => 'https://www.laccnyc.org/',
	],
	[
		'res_label' => 'Guichet.lu — citizenship & residency',
		'res_url'   => 'https://guichet.public.lu/en.html',
	],
], $msp );

tclas_seed( 'msp_resources_mn', [
	[
		'res_label' => 'Luxembourg Heritage Museum, Rollingstone',
		'res_url'   => 'https://www.exploreminnesota.com/profile/rollingstone-luxembourg-heritage-museum/2655',
	],
	[
		'res_label' => 'Minnesota Historical Society — immigration records',
		'res_url'   => 'https://www.mnhs.org/',
	],
	[
		'res_label' => 'TCLAS ancestry resources',
		'res_url'   => '/ancestry/',
	],
], $msp );


// ─── Summary ─────────────────────────────────────────────────────────────
WP_CLI::success( "Populated {$wrote} ACF field(s) across 5 pages." );
