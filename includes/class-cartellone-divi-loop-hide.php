<?php

namespace Cartellone\Divi;

/**
 * Hide Divi modules when their associated loop produces no results.
 *
 * Modules are identified by a data-hide-when-loop-empty attribute that
 * references a data-loop-id on the loop. When a loop with a given
 * data-loop-id returns no results, any module/sezione that carries the
 * matching data-hide-when-loop-empty attribute is hidden via jQuery.
 */
class LoopHide {

	/**
	 * Loop results cache keyed by loop ID.
	 *
	 * @var array<string, bool>
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
	 * Capture whether a Divi loop has results and cache it by data-loop-id.
	 */
	public static function capture_loop_results( $loop_data, $block_attrs, $block ) {
		$loop_id = self::get_loop_id( $block_attrs );
		if ( empty( $loop_id ) ) {
			return $loop_data;
		}

		$query_args = $loop_data['query_args'] ?? array();
		$query_type = $loop_data['query_type'] ?? 'post_types';

		$has_results = false;

		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			if ( 'post_types' === $query_type ) {
				$post_type = $query_args['post_type'] ?? null;

				if ( is_string( $post_type ) ) {
					$post_type = array_map( 'trim', explode( ',', (string) $post_type ) );
				} elseif ( ! is_array( $post_type ) ) {
					$post_type = array();
				}

				if ( ! empty( array_intersect( array( 'any' ), $post_type ) ) || ! empty( $post_type ) ) {
					$query = new \WP_Query( $query_args );
					$has_results = ! empty( $query->posts );
				}
			}
		}

		self::$loop_results[ $loop_id ] = $has_results;

		return $loop_data;
	}

	/**
	 * Output script to hide modules whose loop is empty.
	 */
	public static function output_hide_script(): void {
		$empty_loops = array_keys( array_filter( self::$loop_results, function ( $has_results ) {
			return ! $has_results;
		} ) );

		if ( empty( $empty_loops ) ) {
			return;
		}

		$empty_loops_json = wp_json_encode( $empty_loops );
		?>
		<script type="text/javascript">
		jQuery(function($){
			var emptyLoops = <?php echo $empty_loops_json; ?>;
			emptyLoops.forEach(function(loopId){
				$('[data-hide-when-loop-empty="' + loopId + '"]').hide();
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

