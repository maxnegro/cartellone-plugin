<?php

namespace Cartellone\CLI;

if ( ! class_exists( '\WP_CLI' ) ) {
	return;
}

/**
 * Cartellone CLI commands.
 */
class CartelloneCommand {

	/**
	 * Test the stagione shortcode query.
	 *
	 * @subcommand test-stagione
	 *
	 * ## OPTIONS
	 *
	 * <anno>
	 * : Season year (e.g. 2018)
	 *
	 * <stagione>
	 * : Season slug (e.g. principale)
	 *
	 * [--format=<format>]
	 * : Render format. Choices: table, ids, json. Default: table.
	 * ---
	 * default: table
	 * ---
	 *
	 * [--debug]
	 * : Show query SQL and post types.
	 *
	 * [--render]
	 * : Render HTML output for each post using the shortcode partial.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cartellone test-stagione 2018 principale
	 *     wp cartellone test-stagione 2018 principale --format=ids
	 *     wp cartellone test-stagione 2018 principale --debug
	 *     wp cartellone test-stagione 2018 principale --render
	 */
	public function test_stagione( $args, $assoc_args ) {
		list( $anno, $stagione ) = $args;

		$settings = new \Cartellone\Settings();
		$year         = (int) $anno;
		$season_start = $settings->get_season_start_timestamp( $year );
		$season_end   = $settings->get_season_start_timestamp( $year + 1 );

		$query = new \WP_Query(
			array(
				'post_type'           => CARTELLONE_CPT,
				'post_status'         => 'publish',
				'nopaging'            => true,
				'suppress_filters'    => true,
				'tax_query'           => array(
					array(
						'taxonomy' => CARTELLONE_TAX_STAGIONE,
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $stagione ),
					),
				),
				'meta_key'            => CARTELLONE_META_SORT,
				'orderby'             => 'meta_value_num',
				'order'               => 'ASC',
				'meta_query'          => array(
					array(
						'key'     => CARTELLONE_META_SORT,
						'value'   => array( $season_start, $season_end ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'debug' ) ) {
			\WP_CLI::line( 'SQL: ' . $query->request );
			\WP_CLI::line( 'Found posts: ' . $query->found_posts );
		}

		if ( ! $query->have_posts() ) {
			\WP_CLI::line( 'No events found for this season.' );
			return;
		}

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'ids' === $format ) {
			\WP_CLI::line( implode( ' ', $query->posts ) );
			return;
		}

		if ( 'json' === $format ) {
			$posts = array();
			foreach ( $query->posts as $post ) {
				$posts[] = array(
					'id'     => $post->ID,
					'type'   => $post->post_type,
					'title'  => get_the_title( $post->ID ),
					'status' => $post->post_status,
				);
			}
			\WP_CLI::line( wp_json_encode( $posts, JSON_PRETTY_PRINT ) );
			return;
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'render' ) ) {
			$previous_post = $GLOBALS['post'] ?? null;

			foreach ( $query->posts as $post ) {
				$GLOBALS['post'] = $post;
				$GLOBALS['id'] = $post->ID;
				setup_postdata( $post );

				$evdata = new \Cartellone\Data( $post->ID );
				$event  = $evdata->get_data();
				$terms  = get_the_terms( $post->ID, CARTELLONE_TAX_TIPO );

				ob_start();
				require CARTELLONE_PATH . 'public/partials/cartellone-public-stagione-shortcode.php';
				$html = ob_get_clean();

				\WP_CLI::line( "--- Post {$post->ID} ---" );
				\WP_CLI::line( $html );

				wp_reset_postdata();
			}

			if ( $previous_post instanceof \WP_Post ) {
				$GLOBALS['post'] = $previous_post;
				$GLOBALS['id'] = $previous_post->ID;
				setup_postdata( $previous_post );
			}

			return;
		}

		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = array(
				'id'     => $post->ID,
				'type'   => $post->post_type,
				'title'  => get_the_title( $post->ID ),
				'status' => $post->post_status,
			);
		}

		\WP_CLI\Utils\format_items( $format, $items, array( 'id', 'type', 'title', 'status' ) );

		foreach ( $query->posts as $post ) {
			if ( $post->post_type !== CARTELLONE_CPT ) {
				\WP_CLI::warning( "Post {$post->ID} is type: {$post->post_type}" );
			}
		}
	}
}
