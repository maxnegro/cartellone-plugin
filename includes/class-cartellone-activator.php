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

		global $wpdb;

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				CARTELLONE_CPT
			)
		);

		if ( empty( $post_ids ) ) {
			update_option( 'cartellone_db_version', CARTELLONE_DB_VERSION );
			return;
		}

		foreach ( $post_ids as $post_id ) {
			Data::migrate_meta( $post_id );
		}

		update_option( 'cartellone_db_version', CARTELLONE_DB_VERSION );
	}
}
