<?php

namespace Cartellone;

/**
 * Admin functionality.
 */
class Admin {

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
	}

	/**
	 * Run hooks.
	 */
	public function run() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_spettacoli', array( $this, 'save_meta_box' ) );
		add_filter( 'manage_spettacoli_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_spettacoli_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		if ( 'spettacoli' === $screen->post_type ) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				CARTELLONE_URL . 'admin/css/cartellone-admin.css',
				array(),
				CARTELLONE_VERSION,
				'all'
			);

			wp_enqueue_style(
				'jquery-ui',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css',
				array(),
				null
			);

			wp_enqueue_style(
				$this->plugin_name . '-timepicker',
				CARTELLONE_URL . 'admin/css/jquery.ui.timepicker.css',
				array( 'jquery-ui' ),
				CARTELLONE_VERSION,
				'all'
			);
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		if ( 'spettacoli' === $screen->post_type ) {
			wp_enqueue_script(
				'jquery-ui-timepicker',
				CARTELLONE_URL . 'admin/js/jquery.ui.timepicker.js',
				array( 'jquery-ui-datepicker' ),
				CARTELLONE_VERSION,
				false
			);

			wp_enqueue_script(
				$this->plugin_name . '-admin',
				CARTELLONE_URL . 'admin/js/cartellone-admin.js',
				array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-timepicker' ),
				CARTELLONE_VERSION,
				false
			);
		}
	}

	/**
	 * Add settings page under Spettacoli menu.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . CARTELLONE_CPT,
			__( 'Cartellone Settings', 'cartellone' ),
			__( 'Settings', 'cartellone' ),
			'manage_options',
			'cartellone',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'cartellone' );
				do_settings_sections( 'cartellone' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( '_cartellone_nonce', 'cartellone_nonce' );

		$evdata = new Data( $post->ID );
		$ev     = $evdata->get_data();

		require CARTELLONE_PATH . 'admin/partials/cartellone-admin-render-meta-box.php';
	}

	/**
	 * Add meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			$this->plugin_name,
			__( 'Event details', 'cartellone' ),
			array( $this, 'render_meta_box' ),
			'spettacoli',
			'normal',
			'core'
		);
	}

	/**
	 * Save meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['cartellone_nonce'] ) || ! wp_verify_nonce( $_POST['cartellone_nonce'], '_cartellone_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$mbdata = new Data( $post_id );
		$mbdata->hydrate_from_request();
		$mbdata->save_data();
	}

	/**
	 * Manage posts columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function manage_posts_columns( $columns ) {
		return array(
			'cb'          => $columns['cb'],
			'title'       => __( 'Title', 'cartellone' ),
			'protagonisti' => __( 'Cast', 'cartellone' ),
			'data'        => __( 'Date', 'cartellone' ),
			'vivaticket'  => '<span class="dashicons dashicons-tickets-alt"></span>',
			'date'        => __( 'Status', 'cartellone' ),
		);
	}

	/**
	 * Manage posts custom column.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function manage_posts_custom_column( $column, $post_id ) {
		$cdata = new Data( $post_id );
		$event = $cdata->get_data();

		switch ( $column ) {
			case 'protagonisti':
				echo esc_html( $event['protagonisti'] ?? '' );
				break;
			case 'data':
				if ( ! empty( $event['data'] ) ) {
					echo esc_html( date_i18n( 'D d/m/Y', (int) $event['data'] ) );
				}
				break;
			case 'vivaticket':
				if ( ! empty( $event['vivaticket'] ) ) {
					printf(
						'<a href="%s" target="_blank"><span class="dashicons dashicons-tickets-alt"></span></a>',
						esc_url( $event['vivaticket'] )
					);
				}
				break;
		}
	}
}
