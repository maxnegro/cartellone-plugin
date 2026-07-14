<?php

namespace Cartellone;

/**
 * Public-facing functionality.
 */
class Frontend {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Data instance.
	 *
	 * @var Data
	 */
	private $data;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_name = 'cartellone';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings instance.
	 * @param Data     $data Data instance.
	 */
	public function __construct( Settings $settings, Data $data ) {
		$this->settings = $settings;
		$this->data     = $data;

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'single_template', array( $this, 'get_single_spettacoli_template' ) );
		add_shortcode( 'stagione', array( $this, 'render_stagione_shortcode' ) );

		add_filter( 'get_next_post_join', array( $this, 'post_join' ) );
		add_filter( 'get_previous_post_join', array( $this, 'post_join' ) );
		add_filter( 'get_next_post_sort', array( $this, 'post_sort_next' ) );
		add_filter( 'get_previous_post_sort', array( $this, 'post_sort_prev' ) );
		add_filter( 'get_next_post_where', array( $this, 'post_where_next' ) );
		add_filter( 'get_previous_post_where', array( $this, 'post_where_prev' ) );

		add_action( 'template_redirect', array( $this, 'maybe_redirect_old_season_url' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Run hooks.
	 */
	public function run() {
		// Hooks registered in init().
	}

	/**
	 * Redirect old season URLs to current permalink.
	 */
	public function maybe_redirect_old_season_url() {
		if ( ! is_404() ) {
			return;
		}

		$post_type = get_query_var( 'post_type' );

		if ( CARTELLONE_CPT !== $post_type ) {
			return;
		}

		$name = get_query_var( 'name' );

		if ( ! $name ) {
			return;
		}

		$post = get_page_by_path( $name, OBJECT, CARTELLONE_CPT );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		wp_redirect( get_permalink( $post->ID ), 301 );
		exit;
	}

	/**
	 * Get single template.
	 *
	 * @param string $single_template Template path.
	 * @return string
	 */
	public function get_single_spettacoli_template( $single_template ) {
		global $post;

		if ( $post instanceof \WP_Post && CARTELLONE_CPT === $post->post_type ) {
			return CARTELLONE_PATH . 'templates/single-spettacoli.php';
		}

		return $single_template;
	}

	/**
	 * Post join for navigation.
	 *
	 * @param string $join Join SQL.
	 * @return string
	 */
	public function post_join( $join ) {
		global $wpdb;

		$join .= " INNER JOIN {$wpdb->postmeta} AS m ON p.ID = m.post_id";

		return $join;
	}

	/**
	 * Post sort for next navigation.
	 *
	 * @param string $orderby Orderby SQL.
	 * @return string
	 */
	public function post_sort_next( $orderby ) {
		return 'ORDER BY CAST(m.meta_value AS UNSIGNED) ASC, p.post_title ASC, p.ID ASC LIMIT 1';
	}

	/**
	 * Post sort for previous navigation.
	 *
	 * @param string $orderby Orderby SQL.
	 * @return string
	 */
	public function post_sort_prev( $orderby ) {
		return 'ORDER BY CAST(m.meta_value AS UNSIGNED) DESC, p.post_title DESC, p.ID DESC LIMIT 1';
	}

	/**
	 * Post where for next navigation.
	 *
	 * @param string $original Original where SQL.
	 * @return string
	 */
	public function post_where_next( $original ) {
		global $post, $wpdb;

		if ( ! $post instanceof \WP_Post ) {
			return $original;
		}

		$ev_date = get_post_meta( $post->ID, CARTELLONE_META_SORT, true );
		$ev_date = $ev_date ? (int) $ev_date : 0;
		$title   = $post->post_title;

		$season_year  = $this->settings->get_season_year( $ev_date );
		$season_end   = $this->settings->get_season_start_timestamp( $season_year + 1 );

		$sql = $wpdb->prepare(
			"WHERE p.post_type = %s AND (p.post_status = 'publish' OR p.post_status = 'private') AND p.ID <> %d AND m.meta_key = %s AND CAST(m.meta_value AS UNSIGNED) < %d AND (CAST(m.meta_value AS UNSIGNED) > %d OR (CAST(m.meta_value AS UNSIGNED) = %d AND (p.post_title > %s OR (p.post_title = %s AND p.ID > %d))))",
			CARTELLONE_CPT,
			$post->ID,
			CARTELLONE_META_SORT,
			$season_end,
			$ev_date,
			$ev_date,
			$title,
			$title,
			$post->ID
		);

		return $sql;
	}

	/**
	 * Post where for previous navigation.
	 *
	 * @param string $original Original where SQL.
	 * @return string
	 */
	public function post_where_prev( $original ) {
		global $post, $wpdb;

		if ( ! $post instanceof \WP_Post ) {
			return $original;
		}

		$ev_date = get_post_meta( $post->ID, CARTELLONE_META_SORT, true );
		$ev_date = $ev_date ? (int) $ev_date : 0;
		$title   = $post->post_title;

		$season_year  = $this->settings->get_season_year( $ev_date );
		$season_start = $this->settings->get_season_start_timestamp( $season_year );

		$sql = $wpdb->prepare(
			"WHERE p.post_type = %s AND (p.post_status = 'publish' OR p.post_status = 'private') AND p.ID <> %d AND m.meta_key = %s AND CAST(m.meta_value AS UNSIGNED) >= %d AND (CAST(m.meta_value AS UNSIGNED) < %d OR (CAST(m.meta_value AS UNSIGNED) = %d AND (p.post_title < %s OR (p.post_title = %s AND p.ID < %d))))",
			CARTELLONE_CPT,
			$post->ID,
			CARTELLONE_META_SORT,
			$season_start,
			$ev_date,
			$ev_date,
			$title,
			$title,
			$post->ID
		);

		return $sql;
	}

	/**
	 * Render stagione shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_stagione_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'anno'     => '',
				'stagione' => '',
			),
			$atts,
			'stagione'
		);

		if ( empty( $atts['anno'] ) || empty( $atts['stagione'] ) ) {
			return '<p>' . esc_html__( 'Missing year or season attributes.', 'cartellone' ) . '</p>';
		}

		$cache_key = 'cartellone_stagione_' . md5( serialize( $atts ) );
		$ttl       = $this->settings->get( 'shortcode_cache_ttl', 0 );

		if ( $ttl > 0 ) {
			$cached = get_transient( $cache_key );

			if ( false !== $cached ) {
				return $cached;
			}
		}

		$year         = (int) $atts['anno'];
		$season_start = $this->settings->get_season_start_timestamp( $year );
		$season_end   = $this->settings->get_season_start_timestamp( $year + 1 );

		$query = new \WP_Query(
			array(
				'post_type'      => CARTELLONE_CPT,
				'post_status'    => 'publish',
				'nopaging'       => true,
				'tax_query'      => array(
					array(
						'taxonomy' => CARTELLONE_TAX_STAGIONE,
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $atts['stagione'] ),
					),
				),
				'meta_key'       => CARTELLONE_META_SORT,
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => CARTELLONE_META_SORT,
						'value'   => array( $season_start, $season_end ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No events for this season yet.', 'cartellone' ) . '</p>';
		}

		ob_start();

		$microdata = array();

		while ( $query->have_posts() ) {
			$query->the_post();

			$evdata = new Data( get_the_ID() );
			$ev     = $evdata->get_data();

			$microdata[] = $evdata->get_microdata_json_array();

			require CARTELLONE_PATH . 'public/partials/cartellone-public-stagione-shortcode.php';
		}

		wp_reset_postdata();

		if ( ! empty( $microdata ) ) {
			echo '<script type="application/ld+json">';
			echo wp_json_encode( $microdata, JSON_UNESCAPED_SLASHES );
			echo "</script>\n";
		}

		$output = ob_get_clean();

		if ( $ttl > 0 ) {
			set_transient( $cache_key, $output, $ttl );
		}

		return $output;
	}

	/**
	 * Enqueue public styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			CARTELLONE_URL . 'public/css/cartellone-public.css',
			array(),
			CARTELLONE_VERSION,
			'all'
		);
	}

	/**
	 * Enqueue public scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			CARTELLONE_URL . 'public/js/cartellone-public.js',
			array( 'jquery' ),
			CARTELLONE_VERSION,
			false
		);
	}
}
