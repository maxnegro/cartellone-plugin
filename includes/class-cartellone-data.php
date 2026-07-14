<?php

namespace Cartellone;

/**
 * Event data handler.
 */
class Data {

	/**
	 * Post ID.
	 *
	 * @var int|null
	 */
	private $post_id = null;

	/**
	 * Cached data.
	 *
	 * @var array
	 */
	private $event = array();

	/**
	 * Constructor.
	 *
	 * @param int|null $post_id Post ID.
	 */
	public function __construct( $post_id = null ) {
		$this->event = array(
			'data'         => null,
			'ora'          => null,
			'produzione'   => null,
			'protagonisti' => null,
			'credits'      => null,
			'vivaticket'   => null,
		);

		if ( ! empty( $post_id ) ) {
			$this->post_id = (int) $post_id;
			$this->load_data();
		}
	}

	/**
	 * Load data from individual meta fields.
	 *
	 * @return bool
	 */
	public function load_data() {
		if ( empty( $this->post_id ) ) {
			return false;
		}

		$legacy = get_post_meta( $this->post_id, CARTELLONE_META_DATA, true );

		if ( ! empty( $legacy ) && is_array( $legacy ) ) {
			$this->event = $legacy;
		}

		$individual = get_post_meta( $this->post_id, CARTELLONE_META_DATE, true );

		if ( ! empty( $individual ) ) {
			$this->event['data']         = (int) get_post_meta( $this->post_id, CARTELLONE_META_DATE, true );
			$this->event['ora']          = get_post_meta( $this->post_id, CARTELLONE_META_ORA, true );
			$this->event['produzione']   = get_post_meta( $this->post_id, CARTELLONE_META_PRODUZIONE, true );
			$this->event['protagonisti'] = get_post_meta( $this->post_id, CARTELLONE_META_PROTAGONISTI, true );
			$this->event['credits']      = get_post_meta( $this->post_id, CARTELLONE_META_CREDITS, true );
			$this->event['vivaticket']   = get_post_meta( $this->post_id, CARTELLONE_META_VIVATICKET, true );
		}

		return true;
	}

	/**
	 * Save data to individual meta fields.
	 *
	 * @return bool
	 */
	public function save_data() {
		if ( empty( $this->post_id ) ) {
			return false;
		}

		update_post_meta( $this->post_id, CARTELLONE_META_DATE, $this->event['data'] );
		update_post_meta( $this->post_id, CARTELLONE_META_SORT, $this->event['data'] );
		update_post_meta( $this->post_id, CARTELLONE_META_ORA, $this->event['ora'] );
		update_post_meta( $this->post_id, CARTELLONE_META_PRODUZIONE, $this->event['produzione'] );
		update_post_meta( $this->post_id, CARTELLONE_META_PROTAGONISTI, $this->event['protagonisti'] );
		update_post_meta( $this->post_id, CARTELLONE_META_CREDITS, $this->event['credits'] );
		update_post_meta( $this->post_id, CARTELLONE_META_VIVATICKET, $this->event['vivaticket'] );

		return true;
	}

	/**
	 * Hydrate from request.
	 */
	public function hydrate_from_request() {
		if ( empty( $this->post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['cartellone_nonce'] ) || ! wp_verify_nonce( $_POST['cartellone_nonce'], '_cartellone_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $this->post_id ) ) {
			return;
		}

		if ( isset( $_POST['cartellone_data'] ) ) {
			$datetimestamp = \DateTime::createFromFormat( '!d/m/Y', $_POST['cartellone_data'] );
			if ( $datetimestamp instanceof \DateTime ) {
				$this->event['data'] = $datetimestamp->getTimestamp();
			}
		}

		if ( isset( $_POST['cartellone_ora'] ) ) {
			$this->event['ora'] = sanitize_text_field( $_POST['cartellone_ora'] );
		}

		if ( isset( $_POST['cartellone_produzione'] ) ) {
			$this->event['produzione'] = sanitize_text_field( $_POST['cartellone_produzione'] );
		}

		if ( isset( $_POST['cartellone_protagonisti'] ) ) {
			$this->event['protagonisti'] = sanitize_text_field( $_POST['cartellone_protagonisti'] );
		}

		if ( isset( $_POST['cartellone_credits'] ) ) {
			$this->event['credits'] = sanitize_textarea_field( $_POST['cartellone_credits'] );
		}

		if ( isset( $_POST['cartellone_vivaticket'] ) ) {
			$this->event['vivaticket'] = esc_url_raw( $_POST['cartellone_vivaticket'] );
		}
	}

	/**
	 * Get raw data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->event;
	}

	/**
	 * Get single field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		return array_key_exists( $key, $this->event ) ? $this->event[ $key ] : $default;
	}

	/**
	 * Check if tickets are purchasable.
	 *
	 * @return bool
	 */
	public function season_open() {
		return time() > mktime( 0, 0, 0, 11, 2, 2022 );
	}

	/**
	 * Generate schema.org microdata array.
	 *
	 * @return array
	 */
	public function get_microdata_json_array() {
		$payload = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Event',
			'location' => array(
				'@type' => 'Place',
				'name'  => 'Teatro Comunale Bibiena',
				'url'   => 'https://www.teatrobibiena.it/',
				'address' => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => 'Via 2 Agosto 1980 n.114',
					'addressLocality' => 'S.Agata Bolognese',
					'postalCode'      => '40019',
					'addressRegion'   => 'BO',
					'addressCountry'  => 'IT',
				),
			),
			'startDate' => date( 'Y-m-d\T', $this->event['data'] ) . $this->event['ora'] . ':00',
			'performer' => array(
				'@type' => 'PerformingGroup',
				'name'  => $this->event['protagonisti'],
			),
		);

		$ev_post = get_post( $this->post_id );

		if ( $ev_post instanceof \WP_Post ) {
			if ( ! empty( $ev_post->post_title ) ) {
				$payload['name'] = $ev_post->post_title . ' - ' . $this->event['protagonisti'];
			}
			if ( ! empty( $ev_post->post_content ) ) {
				$payload['description'] = apply_filters( 'the_content', $ev_post->post_content );
			}
		}

		$permalink = get_permalink( $this->post_id );
		if ( $permalink ) {
			$payload['url'] = $permalink;
		}

		if ( has_post_thumbnail( $this->post_id ) ) {
			$payload['image'] = get_the_post_thumbnail_url( $this->post_id, 'full' );
		}

		if ( ! empty( $this->event['vivaticket'] ) ) {
			$payload['offers'] = array(
				'@type'     => 'Offer',
				'url'       => $this->event['vivaticket'],
				'validFrom' => date( 'c', $this->season_open() ),
			);
		}

		return $payload;
	}

	/**
	 * Get microdata JSON.
	 *
	 * @return string|false
	 */
	public function get_microdata_json() {
		return wp_json_encode( $this->get_microdata_json_array(), JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Migrate legacy serialized meta to individual fields.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function migrate_meta( $post_id ) {
		$legacy = get_post_meta( $post_id, CARTELLONE_META_DATA, true );

		if ( empty( $legacy ) || ! is_array( $legacy ) ) {
			return;
		}

		if ( ! empty( $legacy['data'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_DATE, (int) $legacy['data'] );
			update_post_meta( $post_id, CARTELLONE_META_SORT, (int) $legacy['data'] );
		}

		if ( isset( $legacy['ora'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_ORA, sanitize_text_field( $legacy['ora'] ) );
		}

		if ( isset( $legacy['produzione'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_PRODUZIONE, sanitize_text_field( $legacy['produzione'] ) );
		}

		if ( isset( $legacy['protagonisti'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_PROTAGONISTI, sanitize_text_field( $legacy['protagonisti'] ) );
		}

		if ( isset( $legacy['credits'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_CREDITS, sanitize_textarea_field( $legacy['credits'] ) );
		}

		if ( isset( $legacy['vivaticket'] ) ) {
			update_post_meta( $post_id, CARTELLONE_META_VIVATICKET, esc_url_raw( $legacy['vivaticket'] ) );
		}
	}
}
