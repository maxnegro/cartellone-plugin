<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Cartellone
 * @subpackage Cartellone/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Cartellone
 * @subpackage Cartellone/public
 * @author     Your Name <email@example.com>
 */
class Cartellone_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('init', array($this, 'init'));

	}
	public function init() {

		add_filter('single_template', array($this, 'get_single_spettacoli_template'));
		add_shortcode('stagione', array($this, 'stagione_shortcode'));


	}

	public function get_single_spettacoli_template($single_template) {
		global $post;
		if ($post->post_type == 'spettacoli') {
			$single_template = dirname( __FILE__ ) . '/../templates/single-spettacoli.php';
		}
		return $single_template;
	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Cartellone_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Cartellone_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartellone-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Cartellone_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Cartellone_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartellone-public.js', array( 'jquery' ), $this->version, false );

	}

	/*
	 * [stagione anno="2016" stagione="main"]
	 */
	public function stagione_shortcode($atts) {
		$attribute = shortcode_atts( array(
			'anno' => '',
			'stagione' => '',
		), $atts );

		$the_query = new WP_Query(array(
			'post_type' => 'spettacoli',
			'post_status' => 'publish',
			'nopaging' => true,
			'tax_query' => array(
				array(
					'taxonomy' => 'stagione',
					'field' => 'slug',
					'terms' => $attribute['stagione']
				)
			),
			'meta_key' => 'cartellone_data_sort',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => 'cartellone_data_sort',
					'value' => array(mktime(0, 0, 0, 9, 1, $attribute['anno']), mktime(0, 0, 0, 9, 1, $attribute['anno']+1)),
					'compare' => 'BETWEEN',
					'type' => 'NUMERIC'
				)
			)
		));

		if ($the_query->have_posts()) {
			ob_start();
			$microdata=array();
			while ($the_query->have_posts()) {
				$the_query->the_post();
				$evdata = new Cartellone_Data(get_the_ID());
				$ev=$evdata->getData();
				array_push($microdata, $evdata->get_microdata_json_array());
				include(dirname( __FILE__ ) . '/partials/cartellone-public-stagione-shortcode.php');
			}
			echo '<script type="application/ld+json">';
			echo wp_json_encode($microdata, JSON_UNESCAPED_SLASHES);
			echo "</script>\n";

			wp_reset_postdata();
			return ob_get_clean();
		} else {
			return "<p>Non è stato ancora inserito alcuno spettacolo per questa stagione.</p>";
		}
	}

}
