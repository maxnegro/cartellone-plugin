<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Cartellone
 * @subpackage Cartellone/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Cartellone
 * @subpackage Cartellone/includes
 * @author     Your Name <email@example.com>
 */
class Cartellone {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Cartellone_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'plugin-name';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_init_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Cartellone_Loader. Orchestrates the hooks of the plugin.
	 * - Cartellone_i18n. Defines internationalization functionality.
	 * - Cartellone_Admin. Defines all hooks for the admin area.
	 * - Cartellone_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartellone-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartellone-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cartellone-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-cartellone-public.php';

		$this->loader = new Cartellone_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Cartellone_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Cartellone_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Cartellone_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box');

		$this->loader->add_filter( 'manage_spettacoli_posts_columns', $plugin_admin, 'manage_posts_columns');
		$this->loader->add_filter( 'manage_spettacoli_posts_custom_column', $plugin_admin, 'manage_posts_custom_column', 10, 2);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Cartellone_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Cartellone_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function register_custompost_type() {
		$labels = array(
			"name" => __( 'Spettacoli', 'cartellone' ),
			"singular_name" => __( 'Spettacolo', 'cartellone' ),
		);

		$args = array(
			"label" => __( 'Spettacoli', 'cartellone' ),
			"labels" => $labels,
			"description" => "",
			"public" => true,
			"publicly_queryable" => true,
			"show_ui" => true,
			"show_in_rest" => false,
			"rest_base" => "",
			"has_archive" => true,
			"show_in_menu" => true,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			// "rewrite" => false, // custom rewrite elsewhere in code
			"rewrite" => array( "slug" => "spettacoli", "with_front" => true ),
			"query_var" => true,
			"supports" => array( "title", "slug", "editor", "thumbnail" ),
		);
		register_post_type( "spettacoli", $args );

		/**
		 * Add default sort order for events
		 */
		add_filter('parse_query', array($this, 'post_sort'));

	}

	public function register_custompost_taxonomy() {
		$labels = array(
			"name" => __( 'Stagioni', 'cartellone' ),
			"singular_name" => __( 'Stagione', 'cartellone' ),
			);

		$args = array(
			"label" => __( 'Stagione', 'cartellone' ),
			"labels" => $labels,
			"public" => true,
			"hierarchical" => true,
			"label" => "Stagione",
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"query_var" => true,
			"rewrite" => array( 'slug' => 'stagione', 'with_front' => true, ),
			"show_admin_column" => false,
			"show_in_rest" => false,
			"rest_base" => "",
			"show_in_quick_edit" => true,
		);
		register_taxonomy( "stagione", array( "spettacoli" ), $args );

		$labels = array(
			"name" => __( 'Tipi', 'cartellone' ),
			"singular_name" => __( 'Tipo', 'cartellone' ),
			);

		$args = array(
			"label" => __( 'Tipo', 'cartellone' ),
			"labels" => $labels,
			"public" => true,
			"hierarchical" => true,
			"label" => "Tipo",
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"query_var" => true,
			"rewrite" => array( 'slug' => 'tipo', 'with_front' => true, ),
			"show_admin_column" => false,
			"show_in_rest" => false,
			"rest_base" => "",
			"show_in_quick_edit" => true,
		);
		register_taxonomy( "tipo", array( "spettacoli" ), $args );


	}

	public function post_sort($query) {
			if (!is_admin() && isset($query->query_vars) && array_key_exists('post_type', $query->query_vars) && $query->query_vars['post_type'] == 'spettacoli') {
					$query->query_vars['orderby'] = 'meta_value';
					$query->query_vars['meta_key'] = 'cartellone_data_sort';
					$query->query_vars['order'] = 'ASC';
			}
	}


	public function define_init_hooks() {
		add_action('init', array($this, 'register_custompost_type'));
		add_action('init', array($this, 'register_custompost_taxonomy'));

		add_action('pre_get_posts', array($this, 'cartellone_pre_get_posts'));
		add_action('init', array($this, 'cartellone_custom_rewrite_rule'));

		add_filter('post_type_link', array($this, 'cartellone_change_link'), 10, 2);

	}

	// Helper function for custom rewrite rule
	public function cartellone_pre_get_posts( $query ) {
		if (isset($_GET['cartellone_ssn'])) {
			$meta_query = array(
				array(
					'key' => 'cartellone_data_sort',
					'value' => array(mktime(0, 0, 0, 6, 1, substr($_GET['cartellone_ssn'], 0, 4)), mktime(0, 0, 0, 6, 1, substr($_GET['cartellone_ssn']+1, 0, 4))),
					'compare' => 'BETWEEN',
					'type' => 'NUMERIC'
				)
			);
			$query->set('meta_query', $meta_query);
		}
	}

	// Custom rewrite rule
	public function cartellone_custom_rewrite_rule() {
		add_rewrite_rule(
			'^spettacoli/([0-9]+-[0-9]+)/([^/]+)$',
			'index.php?post_type=spettacoli&cartellone_ssn=$matches[1]&name=$matches[2]',
			'top'
		);
		flush_rewrite_rules();
	}

	public function cartellone_change_link($permalink, $post) {
		if ('spettacoli' == get_post_type($post)) {
			$evDate = get_post_meta($post->ID, "cartellone_data_sort");
			if (is_array($evDate) && array_key_exists(0, $evDate)) {
				$evDate = $evDate[0];
			} else {
				$evDate = 0;
			}
			$evYear = date("Y", $evDate);
			// Theatrical season starts on September 1st
			if (($evDate < mktime(0,0,0,6,1,$evYear))) {
				$evYear -= 1;
			}

			$slug="";
			if (false !== strpos($permalink, '%spettacoli')) {
				$slug = '%spettacoli%';
			} elseif ( $post->post_name ) {
				$slug = $post->post_name;
			} else {
				$slug = sanitize_title($post->post_title);
			}

			$permalink = sprintf("%s/spettacoli/%04d-%04d/%s", get_home_url(), $evYear, $evYear+1, $slug);
		}

		return $permalink;
	}
}
