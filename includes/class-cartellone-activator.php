<?php

namespace Cartellone;

/**
 * Activator.
 */
class Activator {

	/**
	 * Activate the plugin.
	 */
	public function activate() {
		$this->migrate_meta();

		flush_rewrite_rules();
	}

	/**
	 * Migrate legacy serialized meta to individual fields.
	 */
	private function migrate_meta() {
		$db_version = get_option( 'cartellone_db_version' );

		if ( version_compare( $db_version, CARTELLONE_DB_VERSION, '>=' ) ) {
			return;
		}

		$query = new \WP_Query(
			array(
				'post_type'      => CARTELLONE_CPT,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $query->posts ) ) {
			update_option( 'cartellone_db_version', CARTELLONE_DB_VERSION );
			return;
		}

		foreach ( $query->posts as $post_id ) {
			Data::migrate_meta( $post_id );
		}

		update_option( 'cartellone_db_version', CARTELLONE_DB_VERSION );
	}
}
