<?php
/**
 * Template Name: MSP+LUX
 *
 * Sections:
 *  1. Page header  — ardoise (dark)
 *  2. By the Numbers — white  (two comparison tables: city-to-city, region-to-region)
 *  3. The Connections — ardoise (dark; 2×2 history grid)
 *  4. Parallel Lives  — or-pale (warm; 2×2 card grid)
 *  5. Resources       — white
 *  6. Join bar        — gold (non-members only)
 *
 * @package TCLAS
 */

get_header();
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="tclas-page-header tclas-msp-header">
	<div class="container-tclas">
		<?php tclas_breadcrumb(); ?>
		<span class="tclas-eyebrow"><?php esc_html_e( 'Minnesota · Luxembourg', 'tclas' ); ?></span>
		<h1 class="tclas-page-header__title tclas-msp-header__title"><?php the_title(); ?></h1>
		<?php $msp_tagline = function_exists( 'get_field' ) ? get_field( 'msp_tagline' ) : ''; ?>
		<p class="tclas-msp-header__tagline"><?php echo esc_html( $msp_tagline ?: 'Two (relatively) small places that somehow end up leading the pack.' ); ?></p>
	</div>
</div>

<!-- ── 1. By the Numbers ────────────────────────────────────────────────── -->
<section class="tclas-msp-numbers" aria-labelledby="msp-numbers-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'By the numbers', 'tclas' ); ?></span>
		<h2 id="msp-numbers-heading"><?php esc_html_e( 'Side by side', 'tclas' ); ?></h2>

		<!-- ── City vs City ── -->
		<?php
		$msp_city_mn_hdr  = function_exists( 'get_field' ) ? get_field( 'msp_city_mn_header' ) : '';
		$msp_city_lux_hdr = function_exists( 'get_field' ) ? get_field( 'msp_city_lux_header' ) : '';
		$msp_city_mn_hdr  = $msp_city_mn_hdr ?: 'Minneapolis';
		$msp_city_lux_hdr = $msp_city_lux_hdr ?: 'Luxembourg City';
		?>
		<div class="tclas-msp-compare">
			<p class="tclas-msp-compare__label"><?php esc_html_e( 'City to city', 'tclas' ); ?></p>

			<div class="tclas-msp-table" role="table" aria-label="<?php echo esc_attr( $msp_city_mn_hdr . ' vs. ' . $msp_city_lux_hdr ); ?>">

				<div class="tclas-msp-table__head" role="rowgroup">
					<div class="tclas-msp-table__row tclas-msp-table__row--head" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__place tclas-msp-table__place--mn" role="columnheader">
							<?php echo esc_html( $msp_city_mn_hdr ); ?>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__spacer" role="columnheader">
							<span class="screen-reader-text"><?php esc_html_e( 'Statistic', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__place tclas-msp-table__place--lux" role="columnheader">
							<?php echo esc_html( $msp_city_lux_hdr ); ?>
						</div>
					</div>
				</div>

				<div class="tclas-msp-table__body" role="rowgroup">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_city_stats' ) ) : ?>
					<?php while ( have_rows( 'msp_city_stats' ) ) : the_row(); ?>
					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="<?php echo esc_attr( get_sub_field( 'mn_value' ) ); ?>" data-format="<?php echo esc_attr( get_sub_field( 'mn_format' ) ); ?>"></span>
							<?php if ( $mn_suffix = get_sub_field( 'mn_suffix' ) ) : ?><span class="tclas-msp-stat__era"><?php echo esc_html( $mn_suffix ); ?></span><?php endif; ?>
							<?php if ( $mn_note = get_sub_field( 'mn_note' ) ) : ?><span class="tclas-msp-stat__note"><?php echo esc_html( $mn_note ); ?></span><?php endif; ?>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php echo esc_html( get_sub_field( 'stat_label' ) ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<?php if ( get_sub_field( 'lux_is_emoji' ) ) : ?>
							<span class="tclas-msp-stat__value" aria-hidden="true"><?php echo esc_html( get_sub_field( 'lux_value' ) ); ?></span>
							<?php else : ?>
							<span class="tclas-msp-stat__value" data-count="<?php echo esc_attr( get_sub_field( 'lux_value' ) ); ?>" data-format="<?php echo esc_attr( get_sub_field( 'lux_format' ) ); ?>"></span>
							<?php endif; ?>
							<?php if ( $lux_suffix = get_sub_field( 'lux_suffix' ) ) : ?><span class="tclas-msp-stat__era"><?php echo esc_html( $lux_suffix ); ?></span><?php endif; ?>
							<?php if ( $lux_note = get_sub_field( 'lux_note' ) ) : ?><span class="tclas-msp-stat__note"><?php echo esc_html( $lux_note ); ?></span><?php endif; ?>
						</div>
					</div>
					<?php endwhile; ?>
				<?php else : ?>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="428000" data-format="int">428,000</span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Population', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="137000" data-format="int">137,000</span>
						</div>
					</div>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="15" data-format="pct">15%</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'of city is parkland', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Green space', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="48" data-format="pct">48%</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'of city is green space', 'tclas' ); ?></span>
						</div>
					</div>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="1867" data-format="year">1867</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'incorporated', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Founded', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="963" data-format="year">963</span><span class="tclas-msp-stat__era"> AD</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'first recorded mention', 'tclas' ); ?></span>
						</div>
					</div>

				<?php endif; ?>
				</div><!-- /.tclas-msp-table__body -->
			</div><!-- /.tclas-msp-table -->
		</div><!-- /.tclas-msp-compare -->

		<!-- ── Metro vs Country ── -->
		<?php
		$msp_metro_mn_hdr  = function_exists( 'get_field' ) ? get_field( 'msp_metro_mn_header' ) : '';
		$msp_metro_lux_hdr = function_exists( 'get_field' ) ? get_field( 'msp_metro_lux_header' ) : '';
		$msp_metro_mn_hdr  = $msp_metro_mn_hdr ?: 'Twin Cities metro';
		$msp_metro_lux_hdr = $msp_metro_lux_hdr ?: 'Luxembourg';
		?>
		<div class="tclas-msp-compare">
			<p class="tclas-msp-compare__label"><?php esc_html_e( 'Region to region', 'tclas' ); ?></p>

			<div class="tclas-msp-table" role="table" aria-label="<?php echo esc_attr( $msp_metro_mn_hdr . ' vs. ' . $msp_metro_lux_hdr ); ?>">

				<div class="tclas-msp-table__head" role="rowgroup">
					<div class="tclas-msp-table__row tclas-msp-table__row--head" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__place tclas-msp-table__place--mn" role="columnheader">
							<?php echo esc_html( $msp_metro_mn_hdr ); ?>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__spacer" role="columnheader">
							<span class="screen-reader-text"><?php esc_html_e( 'Statistic', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__place tclas-msp-table__place--lux" role="columnheader">
							<?php echo esc_html( $msp_metro_lux_hdr ); ?>
						</div>
					</div>
				</div>

				<div class="tclas-msp-table__body" role="rowgroup">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_metro_stats' ) ) : ?>
					<?php while ( have_rows( 'msp_metro_stats' ) ) : the_row(); ?>
					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="<?php echo esc_attr( get_sub_field( 'mn_value' ) ); ?>" data-format="<?php echo esc_attr( get_sub_field( 'mn_format' ) ); ?>"></span>
							<?php if ( $mn_suffix = get_sub_field( 'mn_suffix' ) ) : ?><span class="tclas-msp-stat__era"><?php echo esc_html( $mn_suffix ); ?></span><?php endif; ?>
							<?php if ( $mn_note = get_sub_field( 'mn_note' ) ) : ?><span class="tclas-msp-stat__note"><?php echo esc_html( $mn_note ); ?></span><?php endif; ?>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php echo esc_html( get_sub_field( 'stat_label' ) ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<?php if ( get_sub_field( 'lux_is_emoji' ) ) : ?>
							<span class="tclas-msp-stat__value" aria-hidden="true"><?php echo esc_html( get_sub_field( 'lux_value' ) ); ?></span>
							<?php else : ?>
							<span class="tclas-msp-stat__value" data-count="<?php echo esc_attr( get_sub_field( 'lux_value' ) ); ?>" data-format="<?php echo esc_attr( get_sub_field( 'lux_format' ) ); ?>"></span>
							<?php endif; ?>
							<?php if ( $lux_suffix = get_sub_field( 'lux_suffix' ) ) : ?><span class="tclas-msp-stat__era"><?php echo esc_html( $lux_suffix ); ?></span><?php endif; ?>
							<?php if ( $lux_note = get_sub_field( 'lux_note' ) ) : ?><span class="tclas-msp-stat__note"><?php echo esc_html( $lux_note ); ?></span><?php endif; ?>
						</div>
					</div>
					<?php endwhile; ?>
				<?php else : ?>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="3760000" data-format="m2">3.76M</span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Population', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="672000" data-format="int">672,000</span>
						</div>
					</div>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="93000" data-format="usd-k">$93K</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'per capita', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'GDP per capita', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="143000" data-format="usd-k">$143K</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( "world's highest (PPP)", 'tclas' ); ?></span>
						</div>
					</div>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="37200000" data-format="m1">37.2M</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'passengers/yr at MSP (~9.9 per resident)', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Air traffic', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" data-count="5200000" data-format="m1">5.2M</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'passengers/yr at Findel (~7.7 per resident)', 'tclas' ); ?></span>
						</div>
					</div>

					<div class="tclas-msp-table__row" role="row">
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--mn" role="cell">
							<span class="tclas-msp-stat__value" data-count="2040" data-format="int">2,040</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'Luxembourg dual citizens — more than any other U.S. metro', 'tclas' ); ?></span>
						</div>
						<div class="tclas-msp-table__cell tclas-msp-table__cat" role="rowheader"><?php esc_html_e( 'Our connection', 'tclas' ); ?></div>
						<div class="tclas-msp-table__cell tclas-msp-table__stat tclas-msp-table__stat--lux" role="cell">
							<span class="tclas-msp-stat__value" aria-hidden="true">&#127968;</span>
							<span class="tclas-msp-stat__note"><?php esc_html_e( 'Minnesota calls them home', 'tclas' ); ?></span>
						</div>
					</div>

				<?php endif; ?>
				</div><!-- /.tclas-msp-table__body -->
			</div><!-- /.tclas-msp-table -->
		</div><!-- /.tclas-msp-compare -->

	</div><!-- /.container-tclas -->
</section>

<!-- ── 2. The Connections ────────────────────────────────────────────────── -->
<section class="tclas-msp-connections" aria-labelledby="msp-connections-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow tclas-eyebrow--accent"><?php esc_html_e( 'Shared history', 'tclas' ); ?></span>
		<h2 id="msp-connections-heading"><?php esc_html_e( 'The connections', 'tclas' ); ?></h2>

		<ol class="tclas-msp-timeline" role="list">
		<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_timeline' ) ) : ?>
			<?php while ( have_rows( 'msp_timeline' ) ) : the_row(); ?>
			<li class="tclas-msp-timeline__item">
				<div class="tclas-msp-timeline__year"><?php echo esc_html( get_sub_field( 'tl_year' ) ); ?></div>
				<div class="tclas-msp-timeline__content">
					<h3><?php echo esc_html( get_sub_field( 'tl_title' ) ); ?></h3>
					<?php echo wp_kses_post( get_sub_field( 'tl_body' ) ); ?>
				</div>
			</li>
			<?php endwhile; ?>
		<?php else : ?>

			<li class="tclas-msp-timeline__item">
				<div class="tclas-msp-timeline__year">1840s&ndash;1880s</div>
				<div class="tclas-msp-timeline__content">
					<h3><?php esc_html_e( 'Iron ore, two continents', 'tclas' ); ?></h3>
					<p>Minnesota&rsquo;s Iron Range and Luxembourg&rsquo;s Minett region both discovered iron ore in the same era. Luxembourg&rsquo;s national steelmaker ARBED&mdash;now ArcelorMittal&mdash;operated Hibbing Taconite and Minorca Mine in Minnesota for decades before selling to Cleveland-Cliffs in 2020.</p>
				</div>
			</li>

			<li class="tclas-msp-timeline__item">
				<div class="tclas-msp-timeline__year">1883</div>
				<div class="tclas-msp-timeline__content">
					<h3><?php esc_html_e( 'A Luxembourger founds American medicine', 'tclas' ); ?></h3>
					<p>Sister Alfred Moes, born in Remich, Luxembourg, had already built hospitals across Minnesota when a tornado devastated Rochester in 1883. She struck a deal with the Mayo brothers: she&rsquo;d fund the hospital if they&rsquo;d staff it. The result became Mayo Clinic.</p>
				</div>
			</li>

			<li class="tclas-msp-timeline__item">
				<div class="tclas-msp-timeline__year"><?php esc_html_e( 'Late 1800s', 'tclas' ); ?></div>
				<div class="tclas-msp-timeline__content">
					<h3><?php esc_html_e( 'A village that never forgot', 'tclas' ); ?></h3>
					<p>Rollingstone, Minn. (pop. ~600) remains the most intact Luxembourger-American community in the United States. Its Luxembourg Heritage Museum was listed on the National Register of Historic Places in 2021. The town has been a sister city with Bertrange, Luxembourg since 1980.</p>
				</div>
			</li>

			<li class="tclas-msp-timeline__item">
				<div class="tclas-msp-timeline__year"><?php esc_html_e( 'Today', 'tclas' ); ?></div>
				<div class="tclas-msp-timeline__content">
					<h3><?php esc_html_e( 'Dual citizens, dual loyalties', 'tclas' ); ?></h3>
					<p>The Twin Cities metro is home to roughly 2,040 people holding both American and Luxembourgish passports&mdash;more than any other U.S. metro area. TCLAS is where they find each other.</p>
				</div>
			</li>

		<?php endif; ?>
		</ol>

	</div>
</section>

<!-- ── 3. Parallel Lives ─────────────────────────────────────────────────── -->
<section class="tclas-msp-parallels" aria-labelledby="msp-parallels-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'Look familiar?', 'tclas' ); ?></span>
		<h2 id="msp-parallels-heading"><?php esc_html_e( 'Parallel lives', 'tclas' ); ?></h2>

		<div class="tclas-msp-parallels__grid">
		<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_parallels' ) ) : ?>
			<?php while ( have_rows( 'msp_parallels' ) ) : the_row(); ?>
			<div class="tclas-msp-parallel-card">
				<?php if ( $pl_icon = get_sub_field( 'pl_icon' ) ) : ?>
				<div class="tclas-msp-parallel-card__icon" aria-hidden="true"><?php echo esc_html( $pl_icon ); ?></div>
				<?php endif; ?>
				<div><?php echo wp_kses_post( get_sub_field( 'pl_body' ) ); ?></div>
			</div>
			<?php endwhile; ?>
		<?php else : ?>

			<div class="tclas-msp-parallel-card">
				<div class="tclas-msp-parallel-card__icon" aria-hidden="true">&#127970;</div>
				<p>Minneapolis hosts 17 Fortune 500 headquarters in a metro of 3.76 million. Luxembourg City is home to the EU Court of Justice, the European Investment Bank, and the Court of Auditors&mdash;in a country of 672,000. Both cities lead their regions by a lot.</p>
			</div>

			<div class="tclas-msp-parallel-card">
				<div class="tclas-msp-parallel-card__icon" aria-hidden="true">&#128176;</div>
				<p>Finance capitals, both. The Twin Cities anchors the Federal Reserve&rsquo;s 9th District. Luxembourg is the world&rsquo;s second-largest investment fund domicile, with over $6 trillion in assets under management.</p>
			</div>

			<div class="tclas-msp-parallel-card">
				<div class="tclas-msp-parallel-card__icon" aria-hidden="true">&#127795;</div>
				<p>Minneapolis is famously green&mdash;15% of the city is parkland. Luxembourg one-ups it: 48% of the capital is green space, and 52% of the entire country is protected land, the highest share in the EU.</p>
			</div>

			<div class="tclas-msp-parallel-card">
				<div class="tclas-msp-parallel-card__icon" aria-hidden="true">&#11088;</div>
				<p>Tim Walz, Minnesota&rsquo;s 41st governor, has Luxembourgish roots. He&rsquo;s in good company: Luxembourg-descended families have shaped Minnesota politics, medicine, and industry for 150 years.</p>
			</div>

		<?php endif; ?>
		</div>

	</div>
</section>

<!-- ── 3b. Dig Deeper subsections ────────────────────────────────────────── -->
<section class="tclas-section" aria-labelledby="msp-digdeeper-heading">
	<div class="container-tclas">

		<h2 id="msp-digdeeper-heading"><?php esc_html_e( 'Dig deeper into the connection', 'tclas' ); ?></h2>
		<p class="tclas-story-hint"><?php esc_html_e( '(Content coming soon)', 'tclas' ); ?></p>

		<div class="tclas-grid-3">
			<!-- Our History -->
			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/msp-lux/history/' ) ); ?>"><?php esc_html_e( 'Our History', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'How Luxembourgers came to Minnesota, and what they built.', 'tclas' ); ?>
					</p>
				</div>
			</article>

			<!-- The Language -->
			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/msp-lux/language/' ) ); ?>"><?php esc_html_e( 'The Language', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'Lëtzebuergesch: the world\'s only Grand Ducal language.', 'tclas' ); ?>
					</p>
				</div>
			</article>

			<!-- Culture & Life -->
			<article class="tclas-card tclas-card--accented">
				<div class="tclas-card__body">
					<h3 class="tclas-card__title">
						<a href="<?php echo esc_url( home_url( '/msp-lux/culture/' ) ); ?>"><?php esc_html_e( 'Culture & Life', 'tclas' ); ?></a>
					</h3>
					<p class="tclas-card__excerpt">
						<?php esc_html_e( 'Food, film, music, and modern Luxembourg.', 'tclas' ); ?>
					</p>
				</div>
			</article>
		</div>

	</div>
</section>

<!-- ── 4. Resources ──────────────────────────────────────────────────────── -->
<section class="tclas-msp-resources" aria-labelledby="msp-resources-heading">
	<div class="container-tclas">

		<span class="tclas-eyebrow"><?php esc_html_e( 'Dig deeper', 'tclas' ); ?></span>
		<h2 id="msp-resources-heading"><?php esc_html_e( 'Helpful resources', 'tclas' ); ?></h2>

		<div class="tclas-msp-resources__cols">

			<div class="tclas-msp-resources__col">
				<h3><?php esc_html_e( 'Luxembourg', 'tclas' ); ?></h3>
				<ul class="tclas-msp-resources__list">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_resources_lux' ) ) : ?>
					<?php while ( have_rows( 'msp_resources_lux' ) ) : the_row(); ?>
					<li><a href="<?php echo esc_url( get_sub_field( 'res_url' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_sub_field( 'res_label' ) ); ?></a></li>
					<?php endwhile; ?>
				<?php else : ?>
					<li><a href="https://www.luxembourgforfinance.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Luxembourg for Finance', 'tclas' ); ?></a></li>
					<li><a href="https://www.visitluxembourg.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit Luxembourg', 'tclas' ); ?></a></li>
					<li><a href="https://www.laccnyc.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Luxembourg American Chamber of Commerce', 'tclas' ); ?></a></li>
					<li><a href="https://guichet.public.lu/en.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Guichet.lu &mdash; citizenship &amp; residency', 'tclas' ); ?></a></li>
				<?php endif; ?>
				</ul>
			</div>

			<div class="tclas-msp-resources__col">
				<h3><?php esc_html_e( 'Minnesota', 'tclas' ); ?></h3>
				<ul class="tclas-msp-resources__list">
				<?php if ( function_exists( 'have_rows' ) && have_rows( 'msp_resources_mn' ) ) : ?>
					<?php while ( have_rows( 'msp_resources_mn' ) ) : the_row(); ?>
					<li><a href="<?php echo esc_url( get_sub_field( 'res_url' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_sub_field( 'res_label' ) ); ?></a></li>
					<?php endwhile; ?>
				<?php else : ?>
					<li><a href="https://www.exploreminnesota.com/profile/rollingstone-luxembourg-heritage-museum/2655" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Luxembourg Heritage Museum, Rollingstone', 'tclas' ); ?></a></li>
					<li><a href="https://www.mnhs.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Minnesota Historical Society &mdash; immigration records', 'tclas' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/ancestry/' ) ); ?>"><?php esc_html_e( 'TCLAS ancestry resources', 'tclas' ); ?></a></li>
				<?php endif; ?>
				</ul>
			</div>

		</div>

	</div>
</section>

<!-- ── 5. Join bar ───────────────────────────────────────────────────────── -->
<?php if ( ! tclas_is_member() ) : ?>
<section class="tclas-join-bar">
	<div class="container-tclas">
		<h2><?php esc_html_e( 'Join the community', 'tclas' ); ?></h2>
		<div class="tclas-join-bar__actions">
			<a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn btn-secondary btn-lg">
				<?php esc_html_e( 'Become a member', 'tclas' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn-outline-ardoise">
				<?php esc_html_e( 'Member log in', 'tclas' ); ?>
			</a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
