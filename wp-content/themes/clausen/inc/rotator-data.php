<?php
/**
 * Homepage culture-corner datasets — words + annual traditions.
 *
 * Both are fixed, evergreen datasets: the word list rotates weekly by
 * ISO week number, and the traditions calendar computes each entry's next
 * occurrence (fixed dates, Easter offsets, or nth-weekday rules). No admin,
 * no DB, no staleness. To grow either: append to the array.
 *
 * @package TCLAS
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Wuert vun der Woch — Luxembourgish words with English cultural notes.
 * Notes are English-only on purpose (no lb grammar to get wrong).
 *
 * @return array<int, array{word: string, en: string, note: string}>
 */
function tclas_wuert_list(): array {
	return [
		[ 'word' => 'Moien',             'en' => 'hello',                'note' => 'The universal Luxembourgish greeting — any person, any hour of the day.' ],
		[ 'word' => 'Äddi',              'en' => 'goodbye',              'note' => 'The everyday farewell. For "see you soon," try "Bis geschwënn."' ],
		[ 'word' => 'Villmols merci',    'en' => 'thank you very much',  'note' => 'Literally "many times thanks" — gratitude, Luxembourg-style.' ],
		[ 'word' => 'Wann ech gelift',   'en' => 'please',               'note' => 'Literally "if it pleases me" — the polite oil in every Luxembourgish exchange.' ],
		[ 'word' => 'Wëllkomm',          'en' => 'welcome',              'note' => 'What you\'ll hear crossing a Luxembourgish threshold — and what we say to every new member.' ],
		[ 'word' => 'Gudden Appetit',    'en' => 'enjoy your meal',      'note' => 'Never start eating before someone says it.' ],
		[ 'word' => 'Gesondheet',        'en' => 'cheers / bless you',   'note' => 'Raise a glass or answer a sneeze — one word covers both.' ],
		[ 'word' => 'Kachkéis',          'en' => 'cooked cheese',        'note' => 'The famous soft cheese spread — beloved, pungent, non-negotiable on a Schmier.' ],
		[ 'word' => 'Gromperekichelcher','en' => 'potato fritters',      'note' => 'Crispy grated-potato cakes with onion and parsley — the smell of every Luxembourgish fair.' ],
		[ 'word' => 'Quetschentaart',    'en' => 'plum tart',            'note' => 'Late-summer damson plum tart — the taste of a Luxembourgish September.' ],
		[ 'word' => 'Bouneschlupp',      'en' => 'green bean soup',      'note' => 'Hearty green-bean soup with potatoes and bacon — grandmother-approved.' ],
		[ 'word' => 'Träipen',           'en' => 'blood sausage',        'note' => 'Traditionally eaten around Christmas and New Year, with applesauce and mashed potatoes.' ],
		[ 'word' => 'Gebeess',           'en' => 'jam',                  'note' => 'Often homemade from Quetschen (damson plums) — spread thick on a Schmier.' ],
		[ 'word' => 'Schmier',           'en' => 'a slice of buttered bread', 'note' => 'The humble open-faced foundation of Luxembourgish snacking.' ],
		[ 'word' => 'Heemecht',          'en' => 'homeland',             'note' => 'Also the name of the national anthem, "Ons Heemecht" — Our Homeland.' ],
		[ 'word' => 'Léift',             'en' => 'love',                 'note' => 'As in the anthem\'s plea: peace and love for the homeland.' ],
		[ 'word' => 'Frëndschaft',       'en' => 'friendship',           'note' => 'What a society like ours runs on.' ],
		[ 'word' => 'Famill',            'en' => 'family',               'note' => 'The reason most of us are here — and the thread the ancestral map follows.' ],
		[ 'word' => 'Bomi',              'en' => 'grandma',              'note' => 'Paired forever with Bopi (grandpa). Many members first heard Luxembourgish from theirs.' ],
		[ 'word' => 'Kanner',            'en' => 'children',             'note' => 'The Kleeschen brings them gifts on December 6 — a bigger deal than Christmas morning.' ],
		[ 'word' => 'Gebuertsdag',       'en' => 'birthday',             'note' => '"Vill Gléck fir de Gebuertsdag" — much luck for your birthday.' ],
		[ 'word' => 'Schéin',            'en' => 'beautiful',            'note' => 'Also doubles in farewells: "Schéinen Dag nach" — have a nice day.' ],
		[ 'word' => 'Sprooch',           'en' => 'language',             'note' => 'Lëtzebuergesch became the national language in 1984 — and it\'s still growing.' ],
		[ 'word' => 'Schwätzen',         'en' => 'to speak',             'note' => '"Schwätzt Dir Lëtzebuergesch?" — Do you speak Luxembourgish? Even a little counts.' ],
		[ 'word' => 'Léieren',           'en' => 'to learn',             'note' => 'What you\'re doing right now, one word a week.' ],
		[ 'word' => 'Päiperlek',         'en' => 'butterfly',            'note' => 'Routinely voted the most beloved word in the language. Say it out loud — you\'ll see.' ],
		[ 'word' => 'Duerf',             'en' => 'village',              'note' => 'Most Luxembourgish emigrants left a Duerf — and founded new ones in the Midwest.' ],
		[ 'word' => 'Stad',              'en' => 'city',                 'note' => 'Say "d\'Stad" and every Luxembourger knows you mean the capital.' ],
		[ 'word' => 'Musel',             'en' => 'the Moselle',          'note' => 'The river of Luxembourg\'s wine country, shared with Germany across the water.' ],
		[ 'word' => 'Éislek',            'en' => 'the Oesling',          'note' => 'Luxembourg\'s rugged Ardennes north — castles, forests, and hardy ancestors.' ],
		[ 'word' => 'Wäin',              'en' => 'wine',                 'note' => 'Crémant and Riesling from the Musel valley — Luxembourg\'s quiet claim to fame.' ],
		[ 'word' => 'Roude Léiw',        'en' => 'Red Lion',             'note' => 'The heraldic red lion of Luxembourg — on the ensign, the jerseys, and many a diaspora tattoo.' ],
		[ 'word' => 'Gëlle Fra',         'en' => 'Golden Lady',          'note' => 'The gilded memorial in the capital, unveiled 1923 — Luxembourg\'s symbol of remembrance and freedom.' ],
		[ 'word' => 'Kiermes',           'en' => 'parish fair',          'note' => 'Every village has its Kiermes; the capital\'s grew into the mighty Schueberfouer.' ],
		[ 'word' => 'Vakanz',            'en' => 'vacation',             'note' => 'What Luxembourgers take seriously — and what brings many members "home" to the Grand Duchy.' ],
		[ 'word' => 'Bis geschwënn',     'en' => 'see you soon',         'note' => 'The warmest way to end a conversation — or a homepage visit.' ],
	];
}

/**
 * Annual traditions calendar. Rules:
 *   fixed:  [ 'fixed', month, day ]
 *   easter: [ 'easter', offset_days ]   (relative to Easter Sunday)
 *   nth:    [ 'nth', 'second sunday of october' ]  (strtotime phrase)
 *
 * 'days' = how long the event runs (window for "happening now").
 *
 * @return array<int, array{lb: string, en: string, rule: array, days: int, blurb: string}>
 */
function tclas_traditions_list(): array {
	return [
		[
			'lb'    => 'Dräikinneksdag',
			'en'    => 'Epiphany',
			'rule'  => [ 'fixed', 1, 6 ],
			'days'  => 1,
			'blurb' => 'Kings\' Day — whoever finds the bean in the Dräikinnekskuch (kings\' cake) wears the paper crown.',
		],
		[
			'lb'    => 'Liichtmëssdag',
			'en'    => 'Candlemas',
			'rule'  => [ 'fixed', 2, 2 ],
			'days'  => 1,
			'blurb' => 'Children go door to door with paper lanterns singing "Léiwer Härgottsblieschen," trading song for sweets.',
		],
		[
			'lb'    => 'Buergbrennen',
			'en'    => 'bonfire day',
			'rule'  => [ 'easter', -42 ],
			'days'  => 1,
			'blurb' => 'On the first Sunday of Lent, villages burn a great cross-topped bonfire to chase winter away.',
		],
		[
			'lb'    => 'Éimaischen',
			'en'    => 'Easter Monday market',
			'rule'  => [ 'easter', 1 ],
			'days'  => 1,
			'blurb' => 'The Easter Monday pottery market in the capital and Nospelt — home of the Péckvillercher, little clay bird whistles.',
		],
		[
			'lb'    => 'Muttergottesoktav',
			'en'    => 'the Octave pilgrimage',
			'rule'  => [ 'easter', 21 ],
			'days'  => 15,
			'blurb' => 'Luxembourg\'s great pilgrimage to Our Lady, Comforter of the Afflicted — the same devotion that traveled to Carey, Ohio in 1875.',
		],
		[
			'lb'    => 'Sprangprëssessioun',
			'en'    => 'the hopping procession',
			'rule'  => [ 'easter', 52 ],
			'days'  => 1,
			'blurb' => 'On Whit Tuesday, thousands hop-step through Echternach in Europe\'s last dancing procession — UNESCO-listed and gloriously odd.',
		],
		[
			'lb'    => 'Nationalfeierdag',
			'en'    => 'National Day',
			'rule'  => [ 'fixed', 6, 22 ],
			'days'  => 2,
			'blurb' => 'Torchlight parade and fireworks on the eve, ceremony on June 23 — the Grand Duke\'s official birthday and Luxembourg\'s biggest party.',
		],
		[
			'lb'    => 'Schueberfouer',
			'en'    => 'the great funfair',
			'rule'  => [ 'fixed', 8, 21 ],
			'days'  => 20,
			'blurb' => 'The capital\'s enormous funfair, founded by John the Blind in 1340 — Gromperekichelcher, fried fish, and rides into September.',
		],
		[
			'lb'    => 'Nëssmoort',
			'en'    => 'the Vianden nut market',
			'rule'  => [ 'nth', 'second sunday of october' ],
			'days'  => 1,
			'blurb' => 'Vianden celebrates the walnut harvest — nut tarts, nut liqueur, nut everything, beneath the castle.',
		],
		[
			'lb'    => 'Kleeschen',
			'en'    => 'St. Nicholas Day',
			'rule'  => [ 'fixed', 12, 6 ],
			'days'  => 1,
			'blurb' => 'De Kleeschen brings Luxembourgish children their presents on December 6 — a bigger morning than Christmas itself.',
		],
		[
			'lb'    => 'Chrëschtmaart',
			'en'    => 'Christmas market season',
			'rule'  => [ 'fixed', 12, 1 ],
			'days'  => 24,
			'blurb' => 'Gluhwäin steam and Gromperekichelcher sizzle across the capital\'s squares all through Advent.',
		],
	];
}
