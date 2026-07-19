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
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		add_filter( 'post_type_link', array( $this, 'change_link' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'parse_query', array( $this, 'post_sort' ) );
		add_filter( 'et_builder_loop_order_by_options_' . CARTELLONE_CPT, array( $this, 'extend_divi_loop_order_by_options' ) );
		add_filter( 'et_builder_loop_order_by_options_post', array( $this, 'extend_divi_loop_order_by_options_post_fallback' ) );
		add_filter( 'et_builder_loop_order_by_options_multiple_post_types', array( $this, 'extend_divi_loop_order_by_options_multiple_post_types' ), 10, 2 );
		add_filter( 'divi_loop_data_after_execution', array( $this, 'filter_divi_loop_data_after_execution' ), 10, 3 );
		add_filter( 'divi_module_options_loop_post_type_results_query_args', array( $this, 'filter_divi_loop_results_query_args' ), 10, 2 );
		\Cartellone\Divi\LoopHide::register();

		add_filter( 'get_post_metadata', array( $this, 'inject_placeholder_thumbnail' ), 10, 4 );
		add_action( 'save_post', array( $this, 'prevent_placeholder_as_thumbnail' ), 10, 3 );
		add_filter( 'update_post_metadata', array( $this, 'block_placeholder_metadata' ), 10, 4 );
		add_filter( 'add_post_metadata', array( $this, 'block_placeholder_metadata' ), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'filter_cartellone_srcset' ), 20, 5 );

		add_filter( 'cartellone_placeholder_image_url', array( $this->settings(), 'get_placeholder_image_url' ) );

		add_action( 'cli_init', array( $this, 'register_cli_commands' ) );
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

		$guard = $this->is_divi_future_guard_enabled( $params, $context );

		if ( 'future' === $guard || 'past' === $guard ) {
			$today_start = strtotime( 'today', current_time( 'timestamp' ) );

			$clause = array(
				'key'     => CARTELLONE_META_SORT,
				'value'   => $today_start,
				'compare' => 'future' === $guard ? '>=' : '<',
				'type'    => 'NUMERIC',
			);

			if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array( $clause );
			} else {
				$query_args['meta_query'] = array(
					'relation' => 'AND',
					$query_args['meta_query'],
					$clause,
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

		$data_loop = isset( $context['data_loop'] ) ? strtolower( trim( (string) $context['data_loop'] ) ) : '';

		if ( 'future' === $data_loop ) {
			return 'future';
		}

		if ( 'past' === $data_loop ) {
			return 'past';
		}

		$enabled = false;

		/**
		 * Final override for enabling/disabling Divi future-date guard.
		 *
		 * @param bool  $enabled Current computed state.
		 * @param array $context Module context data.
		 * @param array $params  Loop params.
		 */
		$enabled = (bool) apply_filters( 'cartellone_divi_future_guard_enabled', $enabled, $context, $params );

		return $enabled ? 'future' : false;
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
			'data_loop'   => '',
		);

		if ( is_array( $block_attrs ) ) {
			$main_custom_attributes = $this->get_divi_module_main_custom_attributes( $block_attrs );
			if ( isset( $main_custom_attributes['data-loop'] ) ) {
				$context['data_loop'] = (string) $main_custom_attributes['data-loop'];
			} elseif ( isset( $block_attrs['module']['advanced']['htmlAttributes']['desktop']['value']['data-loop'] ) ) {
				$context['data_loop'] = (string) $block_attrs['module']['advanced']['htmlAttributes']['desktop']['value']['data-loop'];
			}
		}

		return $context;
	}

	/**
	 * Extract main-target custom attributes from Divi module attrs.
	 *
	 * @param array $block_attrs Block attrs.
	 * @return array
	 */
	private function get_divi_module_main_custom_attributes( $block_attrs ) {
		$attributes = array();

		if ( ! is_array( $block_attrs ) ) {
			return $attributes;
		}

		$attributes_list = $block_attrs['module']['decoration']['attributes']['desktop']['value']['attributes'] ?? array();

		if ( ! is_array( $attributes_list ) ) {
			return $attributes;
		}

		foreach ( $attributes_list as $attribute ) {
			if ( is_object( $attribute ) ) {
				$attribute = (array) $attribute;
			}

			if ( ! is_array( $attribute ) ) {
				continue;
			}

			$name           = isset( $attribute['name'] ) ? (string) $attribute['name'] : '';
			$value          = isset( $attribute['value'] ) ? (string) $attribute['value'] : '';
			$target_element = isset( $attribute['targetElement'] ) ? (string) $attribute['targetElement'] : '';

			if ( '' === $name || '' === $value ) {
				continue;
			}

			if ( '' !== $target_element && 'main' !== $target_element ) {
				continue;
			}

			$attributes[ $name ] = $value;
		}

		return $attributes;
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
	}

	/**
	 * Save (flush) the permalink structure when the config hash changed.
	 *
	 * Safe to call from init or settings save: rewrite rules are only
	 * rebuilt when the relevant settings actually changed.
	 */
	public function flush_permalink_structure() {
		$config_hash = $this->settings->get_config_hash();
		$stored_hash = get_option( 'cartellone_config_hash' );

		if ( $config_hash === $stored_hash ) {
			return;
		}

		update_option( 'cartellone_config_hash', $config_hash, 'no' );

		flush_rewrite_rules();
	}

	/**
	 * Flush the permalink structure at plugin activation.
	 *
	 * Deferred to init so the plugin's CPT and rewrite rules are already
	 * registered before the rules are rebuilt.
	 */
	public function flush_permalink_structure_on_activation() {
		add_action( 'init', array( $this, 'flush_permalink_structure' ), 99 );
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
	 * Register custom image sizes.
	 */
	public function register_image_sizes() {
		add_image_size( 'cartellone-thumbnail', 1280, 720, true );
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
	 * Inject placeholder image as thumbnail for posts without one.
	 */
	public function inject_placeholder_thumbnail( $value, $post_id, $meta_key, $single ) {
		if ( '_thumbnail_id' !== $meta_key || ! $single || get_post_type( $post_id ) !== CARTELLONE_CPT ) {
			return $value;
		}

		if ( '1' !== $this->settings->get( 'placeholder_enabled', '1' ) ) {
			return $value;
		}

		if ( $this->is_admin_context() ) {
			return $value;
		}

		static $in_filter = false;

		if ( $in_filter ) {
			return $value;
		}

		$in_filter = true;

		$real_thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

		$in_filter = false;

		if ( ! $real_thumbnail_id ) {
			$placeholder_id = $this->settings->get( 'placeholder_image_id' );

			if ( $placeholder_id ) {
				$attachment = get_post( $placeholder_id );
				$file       = get_attached_file( $placeholder_id );

				if ( $attachment && 'attachment' === $attachment->post_type && ! empty( $file ) && file_exists( $file ) ) {
					return $placeholder_id;
				}
			}
		}

		return $value;
	}

	/**
	 * Whether the current request is an admin/editing context where the
	 * placeholder should NOT be injected (to avoid persisting it as the
	 * post's real featured image).
	 *
	 * @return bool
	 */
	private function is_admin_context() {
		if ( is_admin() || wp_doing_ajax() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// The block editor reads/writes posts and media through the core
			// wp/v2 REST namespace. The placeholder must not be injected there
			// (otherwise it leaks into the editor after saving). The Divi loop
			// uses the separate divi/v1 namespace and is left untouched.
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

			if ( false !== strpos( $request_uri, '/wp-json/wp/v2/' ) ) {
				return true;
			}

			$context = isset( $_GET['context'] ) ? sanitize_key( $_GET['context'] ) : '';

			if ( 'edit' === $context ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Block the placeholder image from being written as a spettacolo's real
	 * featured image metadata.
	 *
	 * The placeholder is only injected on public views; persisting it as the
	 * post's actual thumbnail would make it permanent and break the
	 * "no thumbnail" state. Returning a non-null value short-circuits the
	 * metadata write so the placeholder id is never stored.
	 *
	 * @param null|bool $check Whether to short-circuit.
	 * @param int       $object_id Object ID.
	 * @param string    $meta_key Meta key.
	 * @param mixed     $meta_value Meta value.
	 * @return null|bool
	 */
	public function block_placeholder_metadata( $check, $object_id, $meta_key, $meta_value ) {
		if ( '_thumbnail_id' !== $meta_key ) {
			return $check;
		}

		if ( CARTELLONE_CPT !== get_post_type( $object_id ) ) {
			return $check;
		}

		$placeholder_id = $this->settings->get( 'placeholder_image_id' );

		if ( ! $placeholder_id ) {
			return $check;
		}

		if ( (int) $meta_value === (int) $placeholder_id ) {
			return false;
		}

		return $check;
	}

	/**
	 * Prevent the placeholder image from being persisted as a spettacolo's
	 * real featured image on save (fallback for paths that write the meta
	 * directly instead of through the metadata API).
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param bool    $update Whether this is an update.
	 */
	public function prevent_placeholder_as_thumbnail( $post_id, $post, $update ) {
		if ( CARTELLONE_CPT !== $post->post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$placeholder_id = $this->settings->get( 'placeholder_image_id' );

		if ( ! $placeholder_id ) {
			return;
		}

		$stored = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( (int) $stored === (int) $placeholder_id ) {
			delete_post_meta( $post_id, '_thumbnail_id' );
		}
	}

	/**
	 * Filter srcset for cartellone-thumbnail to keep only 16:9 sizes.
	 *
	 * @param array  $sources       Array of image sources.
	 * @param int[]  $size_array    Requested width and height.
	 * @param string $image_src     The src of the image.
	 * @param array  $image_meta    The image meta data.
	 * @param int    $attachment_id The attachment ID.
	 * @return array
	 */
	public function filter_cartellone_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$cartellone_src = wp_get_attachment_image_url( $attachment_id, 'cartellone-thumbnail' );

		if ( ! $cartellone_src || $cartellone_src !== $image_src ) {
			return $sources;
		}

		$filtered = array();

		if ( ! empty( $sources ) && is_array( $sources ) ) {
			foreach ( $sources as $source ) {
				if ( ! is_array( $source ) || empty( $source['url'] ) ) {
					continue;
				}

				$entry_url = $source['url'];
				$matched_ratio = null;
				$matched_name = '';

				if ( ! empty( $image_meta['sizes'] ) && is_array( $image_meta['sizes'] ) ) {
					$entry_basename = basename( $entry_url );

					foreach ( $image_meta['sizes'] as $size_name => $size_data ) {
						if ( ! is_array( $size_data ) ) {
							continue;
						}

						$size_file = isset( $size_data['file'] ) ? (string) $size_data['file'] : '';
						if ( ! $size_file ) {
							continue;
						}

						if ( basename( $size_file ) !== $entry_basename ) {
							continue;
						}

						$width  = isset( $size_data['width'] ) ? (int) $size_data['width'] : 0;
						$height = isset( $size_data['height'] ) ? (int) $size_data['height'] : 0;

						if ( $width > 0 && $height > 0 ) {
							$matched_ratio = $width / $height;
							$matched_name = $size_name;
						}

						break;
					}
				}

				if ( null === $matched_ratio ) {
					$upload_dir = wp_upload_dir();
					$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $entry_url );

					if ( $file_path !== $entry_url && file_exists( $file_path ) ) {
						$image_size = getimagesize( $file_path );

						if ( $image_size && isset( $image_size[0], $image_size[1] ) && $image_size[0] > 0 && $image_size[1] > 0 ) {
							$matched_ratio = $image_size[0] / $image_size[1];
						}
					}
				}

				if ( null === $matched_ratio ) {
					$filtered[] = $source;
					continue;
				}

				if ( 'cartellone-thumbnail' === $matched_name || $this->is_sixteen_by_nine( $matched_ratio ) ) {
					$filtered[] = $source;
				}
			}
		}

		$has_cartellone = false;
		foreach ( $filtered as $source ) {
			if ( isset( $source['url'] ) && $source['url'] === $cartellone_src ) {
				$has_cartellone = true;
				break;
			}
		}

		if ( ! $has_cartellone ) {
			$cartellone_src_data = wp_get_attachment_image_src( $attachment_id, 'cartellone-thumbnail' );

			if ( $cartellone_src_data && isset( $cartellone_src_data[0], $cartellone_src_data[1] ) ) {
				$filtered[] = array(
					'url'        => $cartellone_src_data[0],
					'descriptor' => 'w',
					'value'      => $cartellone_src_data[1],
				);
			}
		}

		if ( empty( $filtered ) ) {
			return $sources;
		}

		return $filtered;
	}

	/**
	 * Check if an aspect ratio is approximately 16:9.
	 *
	 * @param float $ratio Aspect ratio.
	 * @param float $tolerance Tolerance.
	 * @return bool
	 */
	private function is_sixteen_by_nine( $ratio, $tolerance = 0.15 ) {
		$target = 16 / 9;

		return abs( $ratio - $target ) < $tolerance;
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

	/**
	 * Register WP-CLI commands.
	 */
	public function register_cli_commands() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command( 'cartellone', \Cartellone\CLI\CartelloneCommand::class );
	}
}
