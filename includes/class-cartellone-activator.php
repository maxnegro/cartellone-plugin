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

		$this->flush_permalink_rules();
	}

	/**
	 * Save the permalink structure at activation.
	 *
	 * Deferred to init (via the Cartellone loader) so rewrite rules are
	 * rebuilt after the CPT and rewrite rules are registered.
	 */
	private function flush_permalink_rules() {
		if ( ! class_exists( '\Cartellone\Cartellone' ) ) {
			return;
		}

		$plugin = new \Cartellone\Cartellone();
		$plugin->flush_permalink_structure_on_activation();
	}

	/**
	 * Create default placeholder image if not already present.
	 */
	private function create_default_placeholder() {
		$settings = new Settings();
		$placeholder_id = $settings->get( 'placeholder_image_id' );

		if ( $placeholder_id && $this->is_valid_attachment( (int) $placeholder_id ) ) {
			return;
		}

		// From here the configured id is either empty or invalid: do not
		// overwrite a valid id, only (re)create or reuse a placeholder below.

		$default_path = CARTELLONE_PATH . 'public/img/cartellone-plugin-placeholder.png';

		if ( ! file_exists( $default_path ) ) {
			return;
		}

		$existing = $this->find_existing_placeholder();

		if ( $existing ) {
			$options = get_option( 'cartellone_settings', array() );
			$options['placeholder_image_id'] = $existing;
			update_option( 'cartellone_settings', $options );
			return;
		}

		$upload = wp_upload_bits( 'cartellone-plugin-placeholder.png', null, file_get_contents( $default_path ) );

		if ( ! $upload['error'] ) {
			$attachment = array(
				'post_mime_type' => $upload['type'],
				'post_title'     => 'Cartellone Plugin Placeholder',
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
	 * Find an existing placeholder attachment.
	 *
	 * Matches by the placeholder post title or by file name so a previously
	 * chosen custom placeholder is reused instead of uploading a duplicate.
	 *
	 * @return int|null Attachment ID if found, null otherwise.
	 */
	private function find_existing_placeholder() {
		global $wpdb;

		$attach_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = 'attachment'
				AND p.post_title = %s
				AND pm.meta_key = '_wp_attached_file'
				AND pm.meta_value LIKE %s
				ORDER BY p.ID DESC LIMIT 1",
				'Cartellone Plugin Placeholder',
				'%cartellone-plugin-placeholder.png'
			)
		);

		if ( $attach_id && $this->is_valid_attachment( (int) $attach_id ) ) {
			return (int) $attach_id;
		}

		return null;
	}

	/**
	 * Check that an attachment exists and its file is present on disk.
	 *
	 * @param int $attach_id Attachment ID.
	 * @return bool
	 */
	private function is_valid_attachment( $attach_id ) {
		$post = get_post( $attach_id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return false;
		}

		$file = get_attached_file( $attach_id );

		return ! empty( $file ) && file_exists( $file );
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
