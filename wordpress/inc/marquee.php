<?php
/**
 * Solutions marquee for the homepage.
 *
 * Infinite ticker of the curated solution-kit pages between the hero and
 * the collections strip. Data-driven: hides entirely when no solution
 * pages exist. Pure CSS motion (pauses on hover, off under
 * prefers-reduced-motion, horizontally scrollable without JS).
 *
 * @package apexvalue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Solution-kit pages (post_name starts with "solution-").
 *
 * @return WP_Post[]
 */
function apexvalue_solution_pages() {
	$pages = get_pages(
		array(
			'post_status' => 'publish',
			'sort_column' => 'post_name',
			'sort_order'  => 'ASC',
		)
	);

	return array_values(
		array_filter(
			$pages,
			function ( $page ) {
				return 0 === strpos( $page->post_name, 'solution-' );
			}
		)
	);
}

/**
 * Render one copy of the marquee item list.
 *
 * @param WP_Post[] $solutions Solution pages.
 * @param bool      $hidden    Whether this copy is aria-hidden (loop duplicate).
 */
function apexvalue_marquee_list( $solutions, $hidden ) {
	?>
	<ul class="apex-marquee__list"<?php echo $hidden ? ' aria-hidden="true"' : ''; ?>>
		<?php foreach ( $solutions as $solution ) : ?>
			<li class="apex-marquee__item">
				<a href="<?php echo esc_url( get_permalink( $solution ) ); ?>"><?php echo esc_html( $solution->post_title ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Render the marquee on the front page.
 */
function apexvalue_solutions_marquee() {
	if ( ! is_front_page() ) {
		return;
	}

	$solutions = apexvalue_solution_pages();
	if ( empty( $solutions ) ) {
		return;
	}

	/*
	 * A ticker is a signpost, not a directory: 240 titles produced a
	 * 196,000px track that had to race to stay readable. Show a rotating
	 * sample; the index page links every solution.
	 */
	$solutions = array_slice( $solutions, 0, 12 );
	?>
	<div class="apex-marquee" role="region" aria-label="<?php esc_attr_e( 'Curated solution kits', 'apexvalue' ); ?>">
		<div class="apex-marquee__track">
			<?php apexvalue_marquee_list( $solutions, false ); ?>
			<?php apexvalue_marquee_list( $solutions, true ); ?>
		</div>
	</div>
	<?php
}
add_action( 'storefront_before_content', 'apexvalue_solutions_marquee', 25 );
