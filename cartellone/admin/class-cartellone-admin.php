<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Cartellone
 * @subpackage Cartellone/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Cartellone
 * @subpackage Cartellone/admin
 * @author     Your Name <email@example.com>
 */
class Cartellone_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_dependencies(	$this->plugin_name, $this->version );


	}

	/**
	* Loads class dependencies
	*
	* @since    1.0.0
	*
	*/
	private function load_dependencies($plugin_name, $version)	{
		require_once ( plugin_dir_path(__FILE__) . "/../includes/class-cartellone-data.php" );
		// require_once ( plugin_dir_path(__FILE__) . "/../includes/class-cartellone-admin-list.php" );
		// require_once ( plugin_dir_path(__FILE__) . "/../includes/class-iomn-eventi-admin-prenotazioni-list.php" );
		// require_once ( plugin_dir_path(__FILE__) . "/../includes/class-iomn-eventi-admin-options.php" );
	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartellone-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_style('jquery-timepicker-style', plugin_dir_url(__FILE__).'css/jquery.ui.timepicker.css', array('jquery-ui'), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartellone-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script('jquery-ui-spinner');
		wp_enqueue_script('jquery-ui-timepicker', plugin_dir_url(__FILE__).'js/jquery.ui.timepicker.js', array('jquery-ui-core'), $this->version, false);
		wp_enqueue_script('jquery-ui-datepicker');

	}

	/**
	* Renders meta box html.
	*
	* @since    1.0.0
	*/
	public function render_meta_box($post)	{
		$evdata = new Cartellone_Data($post->ID);
		require plugin_dir_path(__FILE__).'partials/cartellone-admin-render-meta-box.php';
	}

	/**
	* Provides meta box for editing data.
	*
	* @since    1.0.0
	*/
	public function add_meta_box()
	{
		add_meta_box($this->plugin_name, 'Dettagli spettacolo', array($this, 'render_meta_box'), 'spettacoli', 'normal', 'core');
	}

	public function save_meta_box($post_id) {
		$mbdata = new Cartellone_Data( $post_id );
		$mbdata->load_form_fields();
		$mbdata->save_data();
	}

	public function manage_posts_columns ($columns) {
		$columns = [
			'cb' => $columns['cb'],
			'title' => "Titolo",
			'protagonisti' => "Protagonisti",
			'data' => "Data",
			'vivaticket' => '<span class="dashicons dashicons-tickets-alt"></span>',
			'date' => "Stato"
		];
		return $columns;
	}

	public function manage_posts_custom_column ($column, $post_id) {
		$cData = new Cartellone_Data($post_id);
		$datiEvento = $cData->getData();
		switch ($column) {
			case 'protagonisti':
				echo $datiEvento[$column];
				break;
			case 'data':
				echo date('D d/m/Y', $datiEvento['data']);
				break;
			case 'vivaticket':
				if (!empty($datiEvento['vivaticket'])) {
					printf('<a href="%s" target="_new"><span class="dashicons dashicons-tickets-alt"></span></a>', $datiEvento['vivaticket']);
				}
				break;
			default:
			  break;
		}

	}
}
