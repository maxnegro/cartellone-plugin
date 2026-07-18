<?php

namespace Cartellone\Divi;

/**
 * Hide Divi modules based on the state of their associated loop.
 *
 * Modules are identified by attributes that reference a data-loop-id on the
 * loop:
 * - data-hide-when-loop-empty: hides the module when the loop returns no results.
 * - data-hide-when-loop-paginates: hides the module when the loop has more
 *   items than those displayed on the page (i.e. it would paginate).
 * - data-hide-when-loop-does-not-paginate: hides the module when the loop
 *   fits entirely on the page (i.e. it would NOT paginate).
 */
class LoopHide {

	/**
	 * Per-loop state keyed by loop ID.
	 *
	 * @var array<string, array{has_results: bool, paginates: bool}>
	 */
	private static $loop_results = array();

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_filter( 'divi_loop_data_after_execution', array( __CLASS__, 'capture_loop_results' ), 10, 3 );
		add_action( 'wp_footer', array( __CLASS__, 'output_hide_script' ) );
	}

	/**
	 * Capture whether a Divi loop has results and whether it paginates.
	 */
	public static function capture_loop_results( $loop_data, $block_attrs, $block ) {
		$loop_id = self::get_loop_id( $block_attrs );
		if ( empty( $loop_id ) ) {
			return $loop_data;
		}

		$query_args = $loop_data['query_args'] ?? array();
		$query_type = $loop_data['query_type'] ?? 'post_types';

		$has_results = false;
		$paginates   = false;

		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			if ( 'post_types' === $query_type ) {
				$post_type = $query_args['post_type'] ?? null;

				if ( is_string( $post_type ) ) {
					$post_type = array_map( 'trim', explode( ',', (string) $post_type ) );
				} elseif ( ! is_array( $post_type ) ) {
					$post_type = array();
				}

				if ( ! empty( array_intersect( array( 'any' ), $post_type ) ) || ! empty( $post_type ) ) {
					$query       = new \WP_Query( $query_args );
					$has_results = ! empty( $query->posts );

					$per_page = (int) ( $query_args['posts_per_page'] ?? 0 );
					if ( $per_page <= 0 ) {
						$per_page = (int) get_option( 'posts_per_page', 10 );
					}

					if ( $query->found_posts > $per_page ) {
						$paginates = true;
					}
				}
			}
		}

		self::$loop_results[ $loop_id ] = array(
			'has_results' => $has_results,
			'paginates'   => $paginates,
		);

		return $loop_data;
	}

	/**
	 * Output scripts to hide modules based on their loop state.
	 */
	public static function output_hide_script(): void {
		if ( empty( self::$loop_results ) ) {
			return;
		}

		$empty_loops     = array();
		$paginating_loops = array();
		$not_paginating_loops = array();

		foreach ( self::$loop_results as $loop_id => $state ) {
			if ( empty( $state['has_results'] ) ) {
				$empty_loops[] = $loop_id;
			}

			if ( ! empty( $state['paginates'] ) ) {
				$paginating_loops[] = $loop_id;
			} else {
				$not_paginating_loops[] = $loop_id;
			}
		}

		if ( empty( $empty_loops ) && empty( $paginating_loops ) && empty( $not_paginating_loops ) ) {
			return;
		}

		$empty_loops_json      = wp_json_encode( $empty_loops );
		$paginating_loops_json = wp_json_encode( $paginating_loops );
		$not_paginating_loops_json = wp_json_encode( $not_paginating_loops );
		?>
		<script type="text/javascript">
		jQuery(function($){
			var emptyLoops = <?php echo $empty_loops_json; ?>;
			emptyLoops.forEach(function(loopId){
				$('[data-hide-when-loop-empty="' + loopId + '"]').hide();
			});

			var paginatingLoops = <?php echo $paginating_loops_json; ?>;
			paginatingLoops.forEach(function(loopId){
				$('[data-hide-when-loop-paginates="' + loopId + '"]').hide();
			});

			var notPaginatingLoops = <?php echo $not_paginating_loops_json; ?>;
			notPaginatingLoops.forEach(function(loopId){
				$('[data-hide-when-loop-does-not-paginate="' + loopId + '"]').hide();
			});
		});
		</script>
		<?php
	}

	/**
	 * Extract data-loop-id from loop block attributes.
	 */
	private static function get_loop_id( $block_attrs ): string {
		if ( ! is_array( $block_attrs ) ) {
			return '';
		}

		$module = $block_attrs['module'] ?? array();

		$attributes = $module['decoration']['attributes']['desktop']['value']['attributes'] ?? array();

		if ( empty( $attributes ) ) {
			$loop_config = $module['advanced']['loop']['desktop']['value'] ?? array();
			$attributes = $loop_config['attributes']['desktop']['value']['attributes'] ?? array();
		}

		foreach ( $attributes as $attr ) {
			if ( is_object( $attr ) ) {
				$attr = (array) $attr;
			}

			if ( ! is_array( $attr ) ) {
				continue;
			}

			if ( 'data-loop-id' === $attr['name'] ) {
				return trim( (string) $attr['value'] );
			}
		}

		return '';
	}
}

