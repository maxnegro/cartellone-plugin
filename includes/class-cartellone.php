<?php

namespace Cartellone;

/**
 * The core plugin class.
 */
class Cartellone {

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
	 * Admin instance.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Frontend instance.
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * i18n instance.
	 *
	 * @var i18n
	 */
	private $i18n;

	/**
	 * Construct the plugin.
	 */
	public function __construct() {
		$this->settings = new Settings();
		$this->data      = new Data();
		$this->i18n      = new i18n();
		$this->admin     = new Admin( $this->settings, $this->data );
		$this->frontend  = new Frontend( $this->settings, $this->data );
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->settings->run();
		$this->i18n->run();
		$this->admin->run();
		$this->frontend->run();
		add_filter( 'wp_import_post_meta', array( $this, 'normalize_imported_legacy_meta' ), 10, 3 );
		add_action( 'import_post_meta', array( $this, 'migrate_imported_legacy_meta' ), 10, 3 );

		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'custom_rewrite_rule' ) );
		add_filter( 'post_type_link', array( $this, 'change_link' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'parse_query', array( $this, 'post_sort' ) );
		add_filter( 'et_builder_loop_order_by_options_' . CARTELLONE_CPT, array( $this, 'extend_divi_loop_order_by_options' ) );
		add_filter( 'et_builder_loop_order_by_options_post', array( $this, 'extend_divi_loop_order_by_options_post_fallback' ) );
		add_filter( 'et_builder_loop_order_by_options_multiple_post_types', array( $this, 'extend_divi_loop_order_by_options_multiple_post_types' ), 10, 2 );
		add_filter( 'divi_loop_data_after_execution', array( $this, 'filter_divi_loop_data_after_execution' ), 10, 3 );
		add_filter( 'divi_module_options_loop_post_type_results_query_args', array( $this, 'filter_divi_loop_results_query_args' ), 10, 2 );
	}

	/**
	 * Default sort order for spettacoli.
	 *
	 * @param \WP_Query $query Query object.
	 */
	public function post_sort( $query ) {
		if ( ! is_admin() && $query->is_main_query() && isset( $query->query_vars['post_type'] ) && CARTELLONE_CPT === $query->query_vars['post_type'] ) {
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', CARTELLONE_META_SORT );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Add custom order by option to Divi Loop for spettacoli.
	 *
	 * @param array $order_by_options Existing options.
	 * @return array
	 */
	public function extend_divi_loop_order_by_options( $order_by_options ) {
		if ( ! is_array( $order_by_options ) ) {
			return $order_by_options;
		}

		return $this->append_cartellone_order_by_option( $order_by_options );
	}

	/**
	 * Fallback for Divi order-by endpoint when it incorrectly targets "post"
	 * while request params target the spettacoli post type.
	 *
	 * @param array $order_by_options Existing options.
	 * @return array
	 */
	public function extend_divi_loop_order_by_options_post_fallback( $order_by_options ) {
		if ( ! is_array( $order_by_options ) ) {
			return $order_by_options;
		}

		if ( ! $this->is_divi_order_by_request_for_spettacoli() ) {
			return $order_by_options;
		}

		return $this->append_cartellone_order_by_option( $order_by_options );
	}

	/**
	 * Add custom order by option to Divi Loop when multiple post types are selected.
	 *
	 * @param array $order_by_options Existing options.
	 * @param array $post_types Selected post types.
	 * @return array
	 */
	public function extend_divi_loop_order_by_options_multiple_post_types( $order_by_options, $post_types ) {
		if ( ! is_array( $order_by_options ) ) {
			return $order_by_options;
		}

		if ( is_string( $post_types ) ) {
			$post_types = array_map( 'trim', explode( ',', $post_types ) );
		}

		if ( ! is_array( $post_types ) || ! in_array( CARTELLONE_CPT, $post_types, true ) ) {
			return $order_by_options;
		}

		return $this->append_cartellone_order_by_option( $order_by_options );
	}

	/**
	 * Append custom cartellone order by option if missing.
	 *
	 * @param array $order_by_options Existing options.
	 * @return array
	 */
	private function append_cartellone_order_by_option( $order_by_options ) {

		foreach ( $order_by_options as $option ) {
			if ( isset( $option['value'] ) && CARTELLONE_META_SORT === $option['value'] ) {
				return $order_by_options;
			}
		}

		$order_by_options[] = array(
			'value' => CARTELLONE_META_SORT,
			'label' => __( 'Data spettacolo (cartellone)', 'cartellone' ),
		);

		return $order_by_options;
	}

	/**
	 * Check whether current request is Divi order-by endpoint for spettacoli.
	 *
	 * @return bool
	 */
	private function is_divi_order_by_request_for_spettacoli() {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( false === strpos( $request_uri, '/divi/v1/loop/query-order-by' ) ) {
			return false;
		}

		$post_types_param = '';
		if ( isset( $_GET['post_types'] ) ) {
			$post_types_param = (string) wp_unslash( $_GET['post_types'] );
		} elseif ( isset( $_GET['post_type'] ) ) {
			$post_types_param = (string) wp_unslash( $_GET['post_type'] );
		}

		if ( '' === $post_types_param ) {
			return false;
		}

		$post_types = array_map( 'trim', explode( ',', $post_types_param ) );

		return in_array( CARTELLONE_CPT, $post_types, true );
	}

	/**
	 * Customize Divi Loop data before final query execution.
	 *
	 * @param array $loop_data Loop data.
	 * @param array $block_attrs Block attributes.
	 * @param array $block Parsed block.
	 * @return array
	 */
	public function filter_divi_loop_data_after_execution( $loop_data, $block_attrs, $block ) {
		if ( empty( $loop_data['query_args'] ) || ! is_array( $loop_data['query_args'] ) ) {
			return $loop_data;
		}

		$query_args = $loop_data['query_args'];
		$post_type  = $query_args['post_type'] ?? null;

		if ( is_string( $post_type ) ) {
			$post_type = array_map( 'trim', explode( ',', $post_type ) );
		} elseif ( ! is_array( $post_type ) ) {
			$post_type = array();
		}

		if ( ! in_array( CARTELLONE_CPT, $post_type, true ) ) {
			return $loop_data;
		}

		$loop_values = array();
		if ( isset( $block_attrs['module']['advanced']['loop']['desktop']['value'] ) && is_array( $block_attrs['module']['advanced']['loop']['desktop']['value'] ) ) {
			$loop_values = $block_attrs['module']['advanced']['loop']['desktop']['value'];
		}

		$context = $this->build_divi_module_context( $loop_values, $block_attrs, $block );

		$loop_data['query_args'] = $this->apply_spettacoli_divi_query_rules( $query_args, $loop_values, $context );

		return $loop_data;
	}

	/**
	 * Customize Divi Visual Builder loop query args.
	 *
	 * @param array $query_args Query args.
	 * @param array $params Request params.
	 * @return array
	 */
	public function filter_divi_loop_results_query_args( $query_args, $params ) {
		if ( ! is_array( $query_args ) ) {
			return $query_args;
		}

		$post_type = $query_args['post_type'] ?? null;
		if ( is_string( $post_type ) ) {
			$post_type = array_map( 'trim', explode( ',', $post_type ) );
		} elseif ( ! is_array( $post_type ) ) {
			$post_type = array();
		}

		if ( ! in_array( CARTELLONE_CPT, $post_type, true ) ) {
			return $query_args;
		}

		$context = $this->build_divi_module_context( array(), array(), array() );

		return $this->apply_spettacoli_divi_query_rules( $query_args, $params, $context );
	}

	/**
	 * Apply shared Divi loop rules for spettacoli.
	 *
	 * @param array $query_args Query args.
	 * @param array $params Optional loop params.
	 * @param array $context Optional module context.
	 * @return array
	 */
	private function apply_spettacoli_divi_query_rules( $query_args, $params = array(), $context = array() ) {
		if ( ! is_array( $query_args ) ) {
			return $query_args;
		}

		if ( $this->is_divi_future_guard_enabled( $params, $context ) ) {
			$today_start = strtotime( 'today', current_time( 'timestamp' ) );

			$future_clause = array(
				'key'     => CARTELLONE_META_SORT,
				'value'   => $today_start,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);

			if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array( $future_clause );
			} else {
				$query_args['meta_query'] = array(
					'relation' => 'AND',
					$query_args['meta_query'],
					$future_clause,
				);
			}
		}

		$requested_orderby = '';
		if ( isset( $params['order_by'] ) && is_string( $params['order_by'] ) ) {
			$requested_orderby = sanitize_key( $params['order_by'] );
		} elseif ( isset( $query_args['orderby'] ) && is_string( $query_args['orderby'] ) ) {
			$requested_orderby = sanitize_key( $query_args['orderby'] );
		}

		$requested_order = '';
		if ( isset( $params['order'] ) && is_string( $params['order'] ) ) {
			$requested_order = sanitize_key( $params['order'] );
		} elseif ( isset( $query_args['order'] ) && is_string( $query_args['order'] ) ) {
			$requested_order = sanitize_key( $query_args['order'] );
		}

		if ( in_array( $requested_order, array( 'descending', 'desc' ), true ) ) {
			$requested_order = 'DESC';
		} elseif ( in_array( $requested_order, array( 'ascending', 'asc' ), true ) ) {
			$requested_order = 'ASC';
		} else {
			$requested_order = 'ASC';
		}

		if ( CARTELLONE_META_SORT === $requested_orderby ) {
			$query_args['meta_key'] = CARTELLONE_META_SORT;
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = $requested_order;
		}

		return $query_args;
	}

	/**
	 * Determine whether the Divi future-date guard is enabled.
	 *
	 * @param array $params Loop params from Visual Builder or saved attrs.
	 * @param array $context Module context.
	 * @return bool
	 */
	private function is_divi_future_guard_enabled( $params, $context = array() ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( isset( $params['cartellone_only_future'] ) ) {
			return 'off' !== sanitize_key( (string) $params['cartellone_only_future'] );
		}

		if ( isset( $params['cartelloneOnlyFuture'] ) ) {
			return 'off' !== sanitize_key( (string) $params['cartelloneOnlyFuture'] );
		}

		$admin_label = isset( $context['admin_label'] ) ? (string) $context['admin_label'] : '';
		$loop_id     = isset( $context['loop_id'] ) ? (string) $context['loop_id'] : '';

		$allow_admin_labels = apply_filters( 'cartellone_divi_future_guard_allow_admin_labels', array() );
		$allow_loop_ids     = apply_filters( 'cartellone_divi_future_guard_allow_loop_ids', array() );
		$deny_admin_labels  = apply_filters( 'cartellone_divi_future_guard_deny_admin_labels', array() );
		$deny_loop_ids      = apply_filters( 'cartellone_divi_future_guard_deny_loop_ids', array() );

		$allow_admin_labels = is_array( $allow_admin_labels ) ? array_map( 'strval', $allow_admin_labels ) : array();
		$allow_loop_ids     = is_array( $allow_loop_ids ) ? array_map( 'strval', $allow_loop_ids ) : array();
		$deny_admin_labels  = is_array( $deny_admin_labels ) ? array_map( 'strval', $deny_admin_labels ) : array();
		$deny_loop_ids      = is_array( $deny_loop_ids ) ? array_map( 'strval', $deny_loop_ids ) : array();

		$has_allowlist = ! empty( $allow_admin_labels ) || ! empty( $allow_loop_ids );
		$enabled       = false;

		if ( $has_allowlist ) {
			$enabled = ( $admin_label && in_array( $admin_label, $allow_admin_labels, true ) )
				|| ( $loop_id && in_array( $loop_id, $allow_loop_ids, true ) );
		}

		if ( $admin_label && in_array( $admin_label, $deny_admin_labels, true ) ) {
			$enabled = false;
		}

		if ( $loop_id && in_array( $loop_id, $deny_loop_ids, true ) ) {
			$enabled = false;
		}

		/**
		 * Final override for enabling/disabling Divi future-date guard.
		 *
		 * @param bool  $enabled Current computed state.
		 * @param array $context Module context data.
		 * @param array $params  Loop params.
		 */
		$enabled = (bool) apply_filters( 'cartellone_divi_future_guard_enabled', $enabled, $context, $params );

		return $enabled;
	}

	/**
	 * Build normalized context for per-module rules.
	 *
	 * @param array $loop_values Loop values.
	 * @param array $block_attrs Block attrs.
	 * @param array $block Full block.
	 * @return array
	 */
	private function build_divi_module_context( $loop_values = array(), $block_attrs = array(), $block = array() ) {
		$context = array(
			'admin_label' => '',
			'loop_id'     => '',
			'block_name'  => '',
		);

		if ( is_array( $loop_values ) && isset( $loop_values['loopId'] ) ) {
			$context['loop_id'] = (string) $loop_values['loopId'];
		}

		if ( is_array( $block_attrs ) ) {
			if ( isset( $block_attrs['module']['meta']['adminLabel']['desktop']['value'] ) ) {
				$context['admin_label'] = (string) $block_attrs['module']['meta']['adminLabel']['desktop']['value'];
			}

			if ( '' === $context['loop_id'] && isset( $block_attrs['module']['advanced']['loop']['desktop']['value']['loopId'] ) ) {
				$context['loop_id'] = (string) $block_attrs['module']['advanced']['loop']['desktop']['value']['loopId'];
			}
		}

		if ( is_array( $block ) && isset( $block['blockName'] ) ) {
			$context['block_name'] = (string) $block['blockName'];
		}

		return $context;
	}

	/**
	 * Migrate legacy serialized meta during WordPress imports.
	 *
	 * @param int    $post_id Imported post ID.
	 * @param string  $key Meta key.
	 * @param mixed   $value Meta value.
	 */
	public function migrate_imported_legacy_meta( $post_id, $key, $value ) {
		if ( CARTELLONE_META_DATA !== $key || CARTELLONE_CPT !== get_post_type( $post_id ) ) {
			return;
		}

		Data::migrate_meta( $post_id );
	}

	/**
	 * Normalize imported legacy meta before the importer stores it.
	 *
	 * @param array $post_metas Imported meta entries.
	 * @param int   $post_id Imported post ID.
	 * @param array $post Raw imported post data.
	 * @return array
	 */
	public function normalize_imported_legacy_meta( $post_metas, $post_id, $post ) {
		if ( CARTELLONE_CPT !== ( $post['post_type'] ?? '' ) || empty( $post_metas ) ) {
			return $post_metas;
		}

		$normalized_metas = array();
		$field_map        = array(
			'ora'          => CARTELLONE_META_ORA,
			'produzione'   => CARTELLONE_META_PRODUZIONE,
			'protagonisti' => CARTELLONE_META_PROTAGONISTI,
			'credits'      => CARTELLONE_META_CREDITS,
			'vivaticket'   => CARTELLONE_META_VIVATICKET,
		);

		foreach ( $post_metas as $meta ) {
			if ( CARTELLONE_META_DATA !== ( $meta['key'] ?? '' ) ) {
				$normalized_metas[] = $meta;
				continue;
			}

			$legacy = Data::normalize_legacy_meta( $meta['value'] ?? '' );

			if ( ! empty( $legacy ) ) {
				if ( isset( $legacy['data'] ) ) {
					$normalized_metas[] = array(
						'key'   => CARTELLONE_META_DATE,
						'value' => (int) $legacy['data'],
					);
					$normalized_metas[] = array(
						'key'   => CARTELLONE_META_SORT,
						'value' => (int) $legacy['data'],
					);
				}

				foreach ( $field_map as $field => $meta_key ) {
					if ( ! isset( $legacy[ $field ] ) ) {
						continue;
					}

					$normalized_metas[] = array(
						'key'   => $meta_key,
						'value' => $legacy[ $field ],
					);
				}
			}

			$normalized_metas[] = $meta;
		}

		return $normalized_metas;
	}

	/**
	 * Custom rewrite rule.
	 */
	public function custom_rewrite_rule() {
		add_rewrite_rule(
			'^spettacoli/([0-9]+-[0-9]+)/([^/]+)$',
			'index.php?post_type=spettacoli&cartellone_ssn=$matches[1]&name=$matches[2]',
			'top'
		);

		$config_hash = $this->settings->get_config_hash();
		$stored_hash = get_option( 'cartellone_config_hash' );

		if ( empty( $stored_hash ) || $config_hash !== $stored_hash ) {
			flush_rewrite_rules();
			update_option( 'cartellone_config_hash', $config_hash, 'no' );
		}
	}

	/**
	 * Change permalink for spettacoli.
	 *
	 * @param string   $permalink Permalink.
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function change_link( $permalink, $post ) {
		if ( CARTELLONE_CPT !== $post->post_type ) {
			return $permalink;
		}

		$ev_date = get_post_meta( $post->ID, CARTELLONE_META_SORT, true );
		$ev_date = $ev_date ? (int) $ev_date : 0;

		if ( ! $ev_date ) {
			return $permalink;
		}

		$ev_year = (int) date( 'Y', $ev_date );
		$season_start = $this->settings->get_season_start_timestamp( $ev_year );

		if ( $ev_date < $season_start ) {
			$ev_year--;
		}

		$slug = '';
		if ( false !== strpos( $permalink, '%spettacoli%' ) ) {
			$slug = '%spettacoli%';
		} elseif ( $post->post_name ) {
			$slug = $post->post_name;
		} else {
			$slug = sanitize_title( $post->post_title );
		}

		return sprintf( '%s/spettacoli/%04d-%04d/%s', get_home_url(), $ev_year, $ev_year + 1, $slug );
	}

	/**
	 * Pre get posts filter.
	 *
	 * @param \WP_Query $query Query object.
	 */
	public function pre_get_posts( $query ) {
		global $pagenow;

		if ( is_admin() && $query->is_main_query() && 'edit.php' === $pagenow && CARTELLONE_CPT === $query->get( 'post_type' ) ) {
			$query->set( 'meta_key', CARTELLONE_META_SORT );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
		}

		if ( ! is_admin() && isset( $_GET['cartellone_ssn'] ) ) {
			$season_year = sanitize_text_field( $_GET['cartellone_ssn'] );
			$year        = (int) substr( $season_year, 0, 4 );
			$season_start = $this->settings->get_season_start_timestamp( $year );
			$season_end   = $this->settings->get_season_start_timestamp( $year + 1 );

			$meta_query = array(
				array(
					'key'     => CARTELLONE_META_SORT,
					'value'   => array( $season_start, $season_end ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			);
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Register CPT.
	 */
	public function register_cpt() {
		$labels = array(
			'name'               => _x( 'Spettacoli', 'post type general name', 'cartellone' ),
			'singular_name'      => _x( 'Spettacolo', 'post type singular name', 'cartellone' ),
			'menu_name'          => _x( 'Spettacoli', 'admin menu', 'cartellone' ),
			'name_admin_bar'     => _x( 'Spettacolo', 'add new on admin bar', 'cartellone' ),
			'add_new'            => _x( 'Add New', 'spettacolo', 'cartellone' ),
			'add_new_item'       => __( 'Add New Spettacolo', 'cartellone' ),
			'new_item'           => __( 'New Spettacolo', 'cartellone' ),
			'edit_item'          => __( 'Edit Spettacolo', 'cartellone' ),
			'view_item'          => __( 'View Spettacolo', 'cartellone' ),
			'all_items'          => __( 'All Spettacoli', 'cartellone' ),
			'search_items'       => __( 'Search Spettacoli', 'cartellone' ),
			'parent_item_colon'  => __( 'Parent Spettacoli:', 'cartellone' ),
			'not_found'          => __( 'No spettacoli found.', 'cartellone' ),
			'not_found_in_trash' => __( 'No spettacoli found in Trash.', 'cartellone' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => '',
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-tickets',
			'show_in_rest'       => true,
			'rest_base'          => 'spettacoli',
			'has_archive'        => true,
			'exclude_from_search'=> false,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'hierarchical'       => false,
			'rewrite'            => array( 'slug' => 'spettacoli', 'with_front' => true ),
			'query_var'          => true,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		);

		register_post_type( CARTELLONE_CPT, $args );
	}

	/**
	 * Register taxonomies.
	 */
	public function register_taxonomy() {
		$stagione_labels = array(
			'name'              => _x( 'Stagioni', 'taxonomy general name', 'cartellone' ),
			'singular_name'     => _x( 'Stagione', 'taxonomy singular name', 'cartellone' ),
			'search_items'      => __( 'Search Stagioni', 'cartellone' ),
			'all_items'         => __( 'All Stagioni', 'cartellone' ),
			'parent_item'       => __( 'Parent Stagione', 'cartellone' ),
			'parent_item_colon' => __( 'Parent Stagione:', 'cartellone' ),
			'edit_item'         => __( 'Edit Stagione', 'cartellone' ),
			'update_item'       => __( 'Update Stagione', 'cartellone' ),
			'add_new_item'      => __( 'Add New Stagione', 'cartellone' ),
			'new_item_name'     => __( 'New Stagione Name', 'cartellone' ),
			'menu_name'         => __( 'Stagioni', 'cartellone' ),
		);

		$stagione_args = array(
			'labels'            => $stagione_labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'stagioni',
			'rewrite'           => array( 'slug' => 'stagione', 'with_front' => true ),
			'query_var'         => true,
		);

		register_taxonomy( CARTELLONE_TAX_STAGIONE, array( CARTELLONE_CPT ), $stagione_args );

		$tipo_labels = array(
			'name'              => _x( 'Tipi', 'taxonomy general name', 'cartellone' ),
			'singular_name'     => _x( 'Tipo', 'taxonomy singular name', 'cartellone' ),
			'search_items'      => __( 'Search Tipi', 'cartellone' ),
			'all_items'         => __( 'All Tipi', 'cartellone' ),
			'parent_item'       => __( 'Parent Tipo', 'cartellone' ),
			'parent_item_colon' => __( 'Parent Tipo:', 'cartellone' ),
			'edit_item'         => __( 'Edit Tipo', 'cartellone' ),
			'update_item'       => __( 'Update Tipo', 'cartellone' ),
			'add_new_item'      => __( 'Add New Tipo', 'cartellone' ),
			'new_item_name'     => __( 'New Tipo Name', 'cartellone' ),
			'menu_name'         => __( 'Tipi', 'cartellone' ),
		);

		$tipo_args = array(
			'labels'            => $tipo_labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'tipi',
			'rewrite'           => array( 'slug' => 'tipo', 'with_front' => true ),
			'query_var'         => true,
		);

		register_taxonomy( CARTELLONE_TAX_TIPO, array( CARTELLONE_CPT ), $tipo_args );
	}

	/**
	 * Register meta fields for REST API.
	 */
	public function register_meta() {
		$meta_args = array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		);

		register_meta( 'post', CARTELLONE_META_SORT, array_merge( $meta_args, array( 'type' => 'integer' ) ) );
		register_meta( 'post', CARTELLONE_META_DATE, array_merge( $meta_args, array( 'type' => 'integer' ) ) );
		register_meta( 'post', CARTELLONE_META_ORA, $meta_args );
		register_meta( 'post', CARTELLONE_META_PRODUZIONE, $meta_args );
		register_meta( 'post', CARTELLONE_META_PROTAGONISTI, $meta_args );
		register_meta( 'post', CARTELLONE_META_CREDITS, $meta_args );
		register_meta( 'post', CARTELLONE_META_VIVATICKET, $meta_args );
	}

	/**
	 * Get settings instance.
	 */
	public function settings() {
		return $this->settings;
	}

	/**
	 * Get data instance.
	 */
	public function data() {
		return $this->data;
	}
}
