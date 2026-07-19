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

	/**
	 * Remove cartellone meta from non-spettacoli posts.
	 *
	 * @subcommand cleanup-orphan-meta
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be deleted without actually deleting.
	 *
	 * [--force]
	 * : Actually delete the orphaned meta rows.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cartellone cleanup-orphan-meta --dry-run
	 *     wp cartellone cleanup-orphan-meta --force
	 */
	public function cleanup_orphan_meta( $args, $assoc_args ) {
		global $wpdb;

		$meta_keys = array(
			'cartellone_data',
			'cartellone_data_sort',
			'cartellone_data_data',
			'cartellone_ora',
			'cartellone_produzione',
			'cartellone_protagonisti',
			'cartellone_credits',
			'cartellone_vivaticket',
		);

		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

		$query = "
			SELECT pm.meta_id, pm.post_id, p.post_title, p.post_type, pm.meta_key, pm.meta_value
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key IN ( $placeholders )
			AND p.post_type != 'spettacoli'
			AND p.post_type != 'revision'
			ORDER BY pm.post_id, pm.meta_key
		";

		$prepared = $wpdb->prepare( $query, $meta_keys );
		$rows     = $wpdb->get_results( $prepared );

		if ( ! $rows ) {
			\WP_CLI::line( 'Nessun meta orfano trovato. Il database è già pulito.' );
			return;
		}

		$unique_posts = array();
		foreach ( $rows as $row ) {
			$unique_posts[ $row->post_id ] = (object) array(
				'ID'       => (int) $row->post_id,
				'title'    => $row->post_title,
				'post_type' => $row->post_type,
			);
		}

		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry_run', false );
		$force   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		\WP_CLI::line( '' );
		\WP_CLI::line( sprintf( 'Meta orfani trovati: %d righe su %d post unici.', count( $rows ), count( $unique_posts ) ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( 'Post interessati:' );

		$items = array();
		foreach ( $unique_posts as $post ) {
			$items[] = array(
				'id'       => $post->ID,
				'type'     => $post->post_type,
				'title'    => $post->title,
			);
		}

		\WP_CLI\Utils\format_items( 'table', $items, array( 'id', 'type', 'title' ) );

		if ( $dry_run ) {
			\WP_CLI::line( '' );
			\WP_CLI::line( 'Modalità dry-run: nessuna modifica al database.' );
			return;
		}

		if ( ! $force ) {
			\WP_CLI::line( '' );
			\WP_CLI::error( 'Usa --force per confermare la cancellazione.' );
		}

		$backup_file = '/tmp/cartellone-orphan-meta-backup-' . date( 'Ymd-His' ) . '.json';
		$backup      = array();

		foreach ( $rows as $row ) {
			$backup[] = array(
				'meta_id'    => (int) $row->meta_id,
				'post_id'    => (int) $row->post_id,
				'post_title' => $row->post_title,
				'post_type'  => $row->post_type,
				'meta_key'   => $row->meta_key,
				'meta_value' => $row->meta_value,
			);
		}

		file_put_contents( $backup_file, wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::line( sprintf( 'Backup salvato in: %s', $backup_file ) );

		\WP_CLI::line( 'Eliminazione in corso...' );

		$deleted = 0;
		foreach ( $rows as $row ) {
			$wpdb->delete(
				$wpdb->postmeta,
				array( 'meta_id' => $row->meta_id ),
				array( '%d' )
			);
			$deleted++;
		}

		\WP_CLI::line( sprintf( 'Eliminate %d righe di meta.', $deleted ) );
		\WP_CLI::line( 'Pulizia completata.' );
	}
}
