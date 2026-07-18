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
		$this->create_default_placeholder();

		flush_rewrite_rules();
	}

	/**
	 * Create default placeholder image if not already present.
	 */
	private function create_default_placeholder() {
		$settings = new Settings();
		$placeholder_id = $settings->get( 'placeholder_image_id' );

		if ( $placeholder_id ) {
			return;
		}

		$default_path = CARTELLONE_PATH . 'public/img/cartellone-plugin-placeholder.png';

		if ( ! file_exists( $default_path ) ) {
			return;
		}

		$upload = wp_upload_bits( 'cartellone-plugin-placeholder.png', null, file_get_contents( $default_path ) );

		if ( ! $upload['error'] ) {
			$attachment = array(
				'post_mime_type' => $upload['type'],
				'post_title'     => 'Teatro Bibiena Placeholder',
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $upload['file'] );

			if ( ! is_wp_error( $attach_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';

				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				$options = get_option( 'cartellone_settings', array() );
				$options['placeholder_image_id'] = $attach_id;
				update_option( 'cartellone_settings', $options );
			}
		}
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
