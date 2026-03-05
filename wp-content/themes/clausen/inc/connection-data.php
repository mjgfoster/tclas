<?php
/**
 * Connection data — canonical commune aliases and surname variant clusters.
 *
 * All alias strings are pre-normalized (lowercase, diacritics stripped) so
 * they can be compared directly against the normalization pipeline output in
 * connections.php.  Only 'label' values retain proper capitalisation — they
 * are used for display only.
 *
 * Sources:
 *   Communes: Administration communale du Luxembourg, post-2015 reform list.
 *   Surnames: TCLAS member research, Minnesota Historical Society immigration
 *             records, and known diaspora anglicisation patterns.
 *
 * To add a new commune or surname variant: append to the relevant array.
 * Any entry a member submits that cannot be resolved here is queued in the
 * admin "Unresolved genealogy entries" screen for manual review.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Communes ───────────────────────────────────────────────────────────────

/**
 * Return the canonical commune alias table.
 *
 * @return array<string, array{label: string, aliases: string[]}>
 */
function tclas_commune_aliases(): array {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}

	// Keys are canonical slugs (URL-safe, ASCII, lowercase, hyphens).
	// Alias strings are already lowercased and diacritic-stripped.
	$data = [

		// ── Major cities / canton seats ────────────────────────────────
		'luxembourg-city'    => [
			'label'   => 'Luxembourg City',
			'aliases' => [
				'luxembourg', 'luxembourg city', 'luxembourg-ville',
				'luxembourg ville', 'letzebuerg', 'luzembuerg',
				'city of luxembourg', 'lux', 'lux city',
			],
		],
		'esch-sur-alzette'   => [
			'label'   => 'Esch-sur-Alzette',
			'aliases' => [
				'esch', 'esch-sur-alzette', 'esch sur alzette',
				'esch/alzette', 'esch an der alzette', 'esch-alzette',
				'esch op der aalzecht', 'esch alzette',
			],
		],
		'differdange'        => [
			'label'   => 'Differdange',
			'aliases' => [ 'differdange', 'differdingen', 'differange' ],
		],
		'dudelange'          => [
			'label'   => 'Dudelange',
			'aliases' => [ 'dudelange', 'dudelingen', 'didelingen', 'dudeling' ],
		],
		'petange'            => [
			'label'   => 'Pétange',
			'aliases' => [ 'petange', 'petingen', 'peteng' ],
		],
		'schifflange'        => [
			'label'   => 'Schifflange',
			'aliases' => [ 'schifflange', 'scheffleng', 'shifflange' ],
		],
		'bettembourg'        => [
			'label'   => 'Bettembourg',
			'aliases' => [
				'bettembourg', 'beetebuerg', 'bettemburg',
				'bettenburg', 'bettemboug',
			],
		],
		'kayl'               => [
			'label'   => 'Kayl',
			'aliases' => [ 'kayl', 'keel', 'keyl' ],
		],
		'rumelange'          => [
			'label'   => 'Rumelange',
			'aliases' => [ 'rumelange', 'rumeleng' ],
		],
		'sanem'              => [
			'label'   => 'Sanem',
			'aliases' => [ 'sanem', 'senim' ],
		],

		// ── Diekirch canton ────────────────────────────────────────────
		'diekirch'           => [
			'label'   => 'Diekirch',
			'aliases' => [ 'diekirch', 'dikrech' ],
		],
		'ettelbruck'         => [
			'label'   => 'Ettelbruck',
			'aliases' => [
				'ettelbruck', 'ettelbrick', 'ettelbreck',
				'ettelbruk', 'ettelbruck',
			],
		],
		'bettendorf'         => [
			'label'   => 'Bettendorf',
			'aliases' => [ 'bettendorf', 'bettendurf' ],
		],
		'colmar-berg'        => [
			'label'   => 'Colmar-Berg',
			'aliases' => [ 'colmar-berg', 'colmar berg', 'colmar' ],
		],
		'ermsdorf'           => [
			'label'   => 'Ermsdorf',
			'aliases' => [ 'ermsdorf' ],
		],
		'feulen'             => [
			'label'   => 'Feulen',
			'aliases' => [ 'feulen', 'feelen' ],
		],
		'mertzig'            => [
			'label'   => 'Mertzig',
			'aliases' => [ 'mertzig', 'maerzig' ],
		],
		'reisdorf'           => [
			'label'   => 'Reisdorf',
			'aliases' => [ 'reisdorf', 'reesdorf' ],
		],
		'schieren'           => [
			'label'   => 'Schieren',
			'aliases' => [ 'schieren' ],
		],

		// ── Clervaux / Wiltz canton ────────────────────────────────────
		'clervaux'           => [
			'label'   => 'Clervaux',
			'aliases' => [ 'clervaux', 'clerf', 'klierf' ],
		],
		'wiltz'              => [
			'label'   => 'Wiltz',
			'aliases' => [ 'wiltz', 'wolz' ],
		],
		'bourscheid'         => [
			'label'   => 'Bourscheid',
			'aliases' => [ 'bourscheid', 'buurschent' ],
		],
		'consthum'           => [
			'label'   => 'Consthum',
			'aliases' => [ 'consthum' ],
		],
		'kiischpelt'         => [
			'label'   => 'Kiischpelt',
			'aliases' => [ 'kiischpelt', 'kischpelt' ],
		],
		'parc-hosingen'      => [
			'label'   => 'Parc Hosingen',
			'aliases' => [ 'parc hosingen', 'hosingen' ],
		],
		'putscheid'          => [
			'label'   => 'Putscheid',
			'aliases' => [ 'putscheid' ],
		],
		'tandel'             => [
			'label'   => 'Tandel',
			'aliases' => [ 'tandel' ],
		],
		'troisvierges'       => [
			'label'   => 'Troisvierges',
			'aliases' => [ 'troisvierges', 'trois-vierges', 'ulflingen', 'wolweleng' ],
		],
		'vianden'            => [
			'label'   => 'Vianden',
			'aliases' => [ 'vianden', 'veianen' ],
		],
		'weiswampach'        => [
			'label'   => 'Weiswampach',
			'aliases' => [ 'weiswampach', 'waiswampech' ],
		],
		'wincrange'          => [
			'label'   => 'Wincrange',
			'aliases' => [ 'wincrange', 'wenkrange' ],
		],
		'winseler'           => [
			'label'   => 'Winseler',
			'aliases' => [ 'winseler' ],
		],

		// ── Echternach / Grevenmacher canton ──────────────────────────
		'echternach'         => [
			'label'   => 'Echternach',
			'aliases' => [ 'echternach', 'iechternach', 'eechternach' ],
		],
		'bech'               => [
			'label'   => 'Bech',
			'aliases' => [ 'bech' ],
		],
		'berdorf'            => [
			'label'   => 'Berdorf',
			'aliases' => [ 'berdorf' ],
		],
		'consdorf'           => [
			'label'   => 'Consdorf',
			'aliases' => [ 'consdorf' ],
		],
		'flaxweiler'         => [
			'label'   => 'Flaxweiler',
			'aliases' => [ 'flaxweiler', 'flaxweler' ],
		],
		'grevenmacher'       => [
			'label'   => 'Grevenmacher',
			'aliases' => [ 'grevenmacher', 'greiwemaacher', 'grewenmacher' ],
		],
		'junglinster'        => [
			'label'   => 'Junglinster',
			'aliases' => [ 'junglinster' ],
		],
		'lenningen'          => [
			'label'   => 'Lenningen',
			'aliases' => [ 'lenningen' ],
		],
		'manternach'         => [
			'label'   => 'Manternach',
			'aliases' => [ 'manternach', 'mainternach' ],
		],
		'mertert'            => [
			'label'   => 'Mertert',
			'aliases' => [ 'mertert', 'maertert' ],
		],
		'rosport-mompach'    => [
			'label'   => 'Rosport-Mompach',
			'aliases' => [ 'rosport', 'mompach', 'rosport-mompach', 'rosport mompach' ],
		],
		'stadtbredimus'      => [
			'label'   => 'Stadtbredimus',
			'aliases' => [ 'stadtbredimus', 'stadtbredimes' ],
		],
		'waldbredimus'       => [
			'label'   => 'Waldbredimus',
			'aliases' => [ 'waldbredimus' ],
		],
		'wellenstein'        => [
			'label'   => 'Wellenstein',
			'aliases' => [ 'wellenstein', 'wellensteng' ],
		],
		'wormeldange'        => [
			'label'   => 'Wormeldange',
			'aliases' => [ 'wormeldange', 'wuermeleng', 'wurmeldange' ],
		],

		// ── Mersch canton ──────────────────────────────────────────────
		'mersch'             => [
			'label'   => 'Mersch',
			'aliases' => [ 'mersch', 'miersch' ],
		],
		'bissen'             => [
			'label'   => 'Bissen',
			'aliases' => [ 'bissen' ],
		],
		'fischbach'          => [
			'label'   => 'Fischbach',
			'aliases' => [ 'fischbach', 'feschbach' ],
		],
		'helperknapp'        => [
			'label'   => 'Helperknapp',
			'aliases' => [ 'helperknapp' ],
		],
		'lintgen'            => [
			'label'   => 'Lintgen',
			'aliases' => [ 'lintgen' ],
		],
		'lorentzweiler'      => [
			'label'   => 'Lorentzweiler',
			'aliases' => [ 'lorentzweiler', 'lorentzweler' ],
		],
		'nommern'            => [
			'label'   => 'Nommern',
			'aliases' => [ 'nommern' ],
		],
		'tuntange'           => [
			'label'   => 'Tuntange',
			'aliases' => [ 'tuntange', 'tunteng' ],
		],
		'vallée-de-lernz'   => [
			'label'   => "Vallée de l'Ernz",
			'aliases' => [ 'vallee de l\'ernz', 'ernz', 'medernach', 'nommern ernz' ],
		],

		// ── Luxembourg canton ──────────────────────────────────────────
		'bertrange'          => [
			'label'   => 'Bertrange',
			'aliases' => [ 'bertrange', 'beetebuerg-bertrange' ],
		],
		'garnich'            => [
			'label'   => 'Garnich',
			'aliases' => [ 'garnich' ],
		],
		'hesperange'         => [
			'label'   => 'Hesperange',
			'aliases' => [ 'hesperange', 'hesper' ],
		],
		'kehlen'             => [
			'label'   => 'Kehlen',
			'aliases' => [ 'kehlen' ],
		],
		'kopstal'            => [
			'label'   => 'Kopstal',
			'aliases' => [ 'kopstal' ],
		],
		'leudelange'         => [
			'label'   => 'Leudelange',
			'aliases' => [ 'leudelange', 'leideleng' ],
		],
		'mamer'              => [
			'label'   => 'Mamer',
			'aliases' => [ 'mamer', 'mamern' ],
		],
		'mondorf-les-bains'  => [
			'label'   => 'Mondorf-les-Bains',
			'aliases' => [
				'mondorf', 'mondorf-les-bains', 'mondorf les bains',
				'munneref', 'bad mondorf',
			],
		],
		'sandweiler'         => [
			'label'   => 'Sandweiler',
			'aliases' => [ 'sandweiler' ],
		],
		'schuttrange'        => [
			'label'   => 'Schuttrange',
			'aliases' => [ 'schuttrange', 'schuttringen' ],
		],
		'steinsel'           => [
			'label'   => 'Steinsel',
			'aliases' => [ 'steinsel' ],
		],
		'strassen'           => [
			'label'   => 'Strassen',
			'aliases' => [ 'strassen', 'stroossen' ],
		],
		'walferdange'        => [
			'label'   => 'Walferdange',
			'aliases' => [ 'walferdange', 'walferdeng' ],
		],

		// ── Capellen canton ────────────────────────────────────────────
		'beckerich'          => [
			'label'   => 'Beckerich',
			'aliases' => [ 'beckerich', 'beckrich' ],
		],
		'dippach'            => [
			'label'   => 'Dippach',
			'aliases' => [ 'dippach', 'dippech' ],
		],
		'ell'                => [
			'label'   => 'Ell',
			'aliases' => [ 'ell' ],
		],
		'hobscheid'          => [
			'label'   => 'Hobscheid',
			'aliases' => [ 'hobscheid', 'habschent' ],
		],
		'käerjeng'           => [
			'label'   => 'Käerjeng',
			'aliases' => [ 'kaerjeng', 'bascharage', 'kleinbettingen' ],
		],
		'steinfort'          => [
			'label'   => 'Steinfort',
			'aliases' => [ 'steinfort', 'steenfort' ],
		],

		// ── Redange canton ─────────────────────────────────────────────
		'boulaide'           => [
			'label'   => 'Boulaide',
			'aliases' => [ 'boulaide', 'bauschelt', 'buschelt' ],
		],
		'eschweiler'         => [
			'label'   => 'Eschweiler',
			'aliases' => [ 'eschweiler' ],
		],
		'grosbous'           => [
			'label'   => 'Grosbous',
			'aliases' => [ 'grosbous', 'groussbus' ],
		],
		'preizerdaul'        => [
			'label'   => 'Préizerdaul',
			'aliases' => [ 'preizerdaul' ],
		],
		'rambrouch'          => [
			'label'   => 'Rambrouch',
			'aliases' => [ 'rambrouch', 'rambrich' ],
		],
		'redange-sur-attert' => [
			'label'   => 'Redange-sur-Attert',
			'aliases' => [
				'redange', 'redange-sur-attert', 'redange sur attert',
				'redange-attert', 'redenig',
			],
		],
		'saeul'              => [
			'label'   => 'Saeul',
			'aliases' => [ 'saeul', 'saul', 'seel' ],
		],
		'vichten'            => [
			'label'   => 'Vichten',
			'aliases' => [ 'vichten' ],
		],
		'wahl'               => [
			'label'   => 'Wahl',
			'aliases' => [ 'wahl' ],
		],

		// ── Esch-sur-Sûre / Haute-Sûre ────────────────────────────────
		'esch-sur-sure'      => [
			'label'   => 'Esch-sur-Sûre',
			'aliases' => [
				'esch-sur-sure', 'esch sur sure',
				'esch an der sauer', 'esch/sure',
			],
		],
		'lac-de-la-haute-sure' => [
			'label'   => 'Lac de la Haute-Sûre',
			'aliases' => [
				'lac de la haute sure', 'lac de la haute-sure',
				'haute-sure', 'hautsauer',
			],
		],

		// ── Remich canton ──────────────────────────────────────────────
		'remich'             => [
			'label'   => 'Remich',
			'aliases' => [ 'remich', 'reimech' ],
		],
		'beaufort'           => [
			'label'   => 'Beaufort',
			'aliases' => [ 'beaufort', 'beefort' ],
		],
		'biwer'              => [
			'label'   => 'Biwer',
			'aliases' => [ 'biwer' ],
		],
		'frisange'           => [
			'label'   => 'Frisange',
			'aliases' => [ 'frisange', 'friesenge' ],
		],
		'larochette'         => [
			'label'   => 'Larochette',
			'aliases' => [ 'larochette', 'fels' ],  // "Fels" is the German name
		],
		'septfontaines'      => [
			'label'   => 'Septfontaines',
			'aliases' => [ 'septfontaines', 'simmern', 'sept fontaines' ],
		],
	];

	return $data;
}

/**
 * Return a flat, sorted list of canonical commune labels for autocomplete.
 *
 * Merges the curated alias list with all 534 villages from the official
 * Luxembourg place-name index (commune-data.php).
 *
 * @return string[]
 */
function tclas_commune_labels(): array {
	$labels = array_column( tclas_commune_aliases(), 'label' );

	// Merge in every village from the full PDF-derived index.
	if ( function_exists( 'tclas_get_communes' ) ) {
		foreach ( tclas_get_communes() as $commune ) {
			$labels[] = $commune['name'];
		}
	}

	$labels = array_unique( $labels );
	sort( $labels );
	return $labels;
}

// ── Surname clusters ───────────────────────────────────────────────────────

/**
 * Return the surname variant cluster table.
 *
 * Each cluster groups all known variants (original LU spelling, German
 * transcription, Americanised forms) under a single canonical head.  A member
 * who enters "Smith" will match a member who entered "Schmitt" because both
 * normalise to the cluster head "schmitt".
 *
 * Variant strings are pre-lowercased and diacritic-stripped.
 *
 * @return array<string, array{label: string, variants: string[], notes?: string}>
 */
function tclas_surname_clusters(): array {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}

	$data = [

		// ── Classic LU → MN diaspora anglicisations ────────────────────
		'schmitt'    => [
			'label'    => 'Schmitt',
			'variants' => [ 'schmitt', 'schmidt', 'schmid', 'smith', 'smyth', 'schmit' ],
			'notes'    => 'Most common LU→MN anglicisation',
		],
		'muller'     => [
			'label'    => 'Müller',
			'variants' => [ 'muller', 'mueller', 'miller' ],
			'notes'    => 'Umlaut drop (ü→u→miller)',
		],
		'kieffer'    => [
			'label'    => 'Kieffer',
			'variants' => [ 'kieffer', 'kiefer', 'keifer', 'keefer', 'kiffer' ],
		],
		'wagner'     => [
			'label'    => 'Wagner',
			'variants' => [ 'wagner', 'wagener', 'waggoner', 'wagonner' ],
		],
		'klein'      => [
			'label'    => 'Klein',
			'variants' => [ 'klein', 'kline', 'cline', 'klyne' ],
		],
		'becker'     => [
			'label'    => 'Becker',
			'variants' => [ 'becker', 'baker' ],
			'notes'    => 'Bäcker → Baker common trade-name anglicisation',
		],
		'schneider'  => [
			'label'    => 'Schneider',
			'variants' => [ 'schneider', 'snyder', 'snider', 'schnyder', 'snider' ],
		],
		'hoffmann'   => [
			'label'    => 'Hoffmann',
			'variants' => [ 'hoffmann', 'hoffman', 'hofmann' ],
		],
		'weber'      => [
			'label'    => 'Weber',
			'variants' => [ 'weber', 'weaver' ],
		],
		'braun'      => [
			'label'    => 'Braun',
			'variants' => [ 'braun', 'brown' ],
		],
		'kremer'     => [
			'label'    => 'Kremer',
			'variants' => [ 'kremer', 'kreamer', 'creamer', 'cramer', 'kraemer' ],
		],
		'engel'      => [
			'label'    => 'Engel',
			'variants' => [ 'engel', 'angle', 'angel', 'engle' ],
		],
		'lux'        => [
			'label'    => 'Lux',
			'variants' => [ 'lux' ],
			'notes'    => 'Distinctly Luxembourgish; rarely anglicised',
		],
		'lutz'       => [
			'label'    => 'Lutz',
			'variants' => [ 'lutz', 'luts' ],
		],
		'meyers'     => [
			'label'    => 'Meyers',
			'variants' => [ 'meyers', 'meyer', 'maier', 'mayer', 'myers', 'meier' ],
		],
		'hansen'     => [
			'label'    => 'Hansen',
			'variants' => [ 'hansen', 'hanson', 'hans' ],
		],
		'schroeder'  => [
			'label'    => 'Schroeder',
			'variants' => [ 'schroeder', 'schroder', 'schrader', 'shroder' ],
		],
		'pauly'      => [
			'label'    => 'Pauly',
			'variants' => [ 'pauly', 'pauli', 'pawley' ],
		],
		'welter'     => [
			'label'    => 'Welter',
			'variants' => [ 'welter', 'waltert' ],
		],
		'gonner'     => [
			'label'    => 'Gonner',
			'variants' => [ 'gonner', 'goener', 'guner' ],
		],
		'schiltz'    => [
			'label'    => 'Schiltz',
			'variants' => [ 'schiltz', 'schilz', 'shiltz' ],
		],
		'simon'      => [
			'label'    => 'Simon',
			'variants' => [ 'simon', 'symon', 'simons' ],
		],
		'bissen'     => [
			'label'    => 'Bissen',
			'variants' => [ 'bissen', 'bison' ],
		],
		'frank'      => [
			'label'    => 'Frank',
			'variants' => [ 'frank', 'franck', 'franke' ],
		],
		'faber'      => [
			'label'    => 'Faber',
			'variants' => [ 'faber', 'favor', 'faver' ],
		],
		'haas'       => [
			'label'    => 'Haas',
			'variants' => [ 'haas', 'hase', 'hays', 'hays' ],
		],
		'jacoby'     => [
			'label'    => 'Jacoby',
			'variants' => [ 'jacoby', 'jacobi', 'jacobs' ],
		],
		'jung'       => [
			'label'    => 'Jung',
			'variants' => [ 'jung', 'young' ],
			'notes'    => 'Jung → Young common anglicisation',
		],
		'konig'      => [
			'label'    => 'König',
			'variants' => [ 'konig', 'koenig', 'king' ],
		],
		'lange'      => [
			'label'    => 'Lange',
			'variants' => [ 'lange', 'lang', 'long' ],
		],
		'martin'     => [
			'label'    => 'Martin',
			'variants' => [ 'martin', 'martens', 'martins' ],
		],
		'reding'     => [
			'label'    => 'Reding',
			'variants' => [ 'reding', 'reading', 'reeding' ],
		],
		'stentz'     => [
			'label'    => 'Stentz',
			'variants' => [ 'stentz', 'stenz', 'stens' ],
		],
		'theisen'    => [
			'label'    => 'Theisen',
			'variants' => [ 'theisen', 'thiessen', 'theissen', 'theyson' ],
		],
		'thies'      => [
			'label'    => 'Thies',
			'variants' => [ 'thies', 'theiss', 'this', 'tice' ],
		],
		'thomas'     => [
			'label'    => 'Thomas',
			'variants' => [ 'thomas', 'tomas' ],
		],
		'thill'      => [
			'label'    => 'Thill',
			'variants' => [ 'thill', 'thil', 'till' ],
		],
		'weiss'      => [
			'label'    => 'Weiss',
			'variants' => [ 'weiss', 'weis', 'wise', 'wyss' ],
		],
		'wolff'      => [
			'label'    => 'Wolff',
			'variants' => [ 'wolff', 'wolf', 'wulf' ],
		],
		'zimmer'     => [
			'label'    => 'Zimmer',
			'variants' => [ 'zimmer', 'zimmerman', 'zimmermann', 'zimerman' ],
		],
	];

	return $data;
}

/**
 * Build a lookup map: normalized_variant → canonical_cluster_head.
 *
 * Pre-computed once per request so matching is O(1).
 *
 * @return array<string, string>
 */
function tclas_surname_variant_map(): array {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}

	$map = [];
	foreach ( tclas_surname_clusters() as $head => $cluster ) {
		foreach ( $cluster['variants'] as $variant ) {
			$map[ $variant ] = $head;
		}
	}
	return $map;
}

/**
 * Build a lookup map: normalized_alias → canonical_commune_slug.
 *
 * @return array<string, string>
 */
function tclas_commune_alias_map(): array {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}

	$map = [];

	// 1. Curated aliases take priority (hand-crafted, highest confidence).
	foreach ( tclas_commune_aliases() as $slug => $entry ) {
		foreach ( $entry['aliases'] as $alias ) {
			$map[ $alias ] = $slug;
		}
	}

	// 2. Full village list from PDF index — adds 500+ communes the curated
	//    table doesn't cover.  Only fills gaps (doesn't overwrite curated entries).
	if ( function_exists( 'tclas_get_communes' ) && function_exists( 'tclas_normalize_string' ) ) {
		foreach ( tclas_get_communes() as $slug => $commune ) {
			$french_norm = tclas_normalize_string( $commune['name'] );
			$lux_norm    = tclas_normalize_string( $commune['lux'] );

			if ( ! isset( $map[ $french_norm ] ) ) $map[ $french_norm ] = $slug;
			if ( ! isset( $map[ $lux_norm    ] ) ) $map[ $lux_norm    ] = $slug;
			// Bare slug itself (e.g. "echternach" → "echternach")
			if ( ! isset( $map[ $slug ] ) )         $map[ $slug ]        = $slug;
		}
	}

	return $map;
}
