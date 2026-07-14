<?php

namespace Cartellone;

/**
 * Settings manager.
 */
class Settings {

	/**
	 * Option name.
	 */
	private $option_name = 'cartellone_settings';

	/**
	 * Default settings.
	 */
	private $defaults = array(
		'season_start_day'   => '01',
		'season_start_month' => '09',
		'shortcode_cache_ttl' => HOUR_IN_SECONDS,
	);

	/**
	 * Settings.
	 */
	private $settings = array();

	/**
	 * Construct.
	 */
	public function __construct() {
		$saved = get_option( $this->option_name, array() );
		$this->settings = wp_parse_args( $saved, $this->defaults );
	}

	/**
	 * Run hooks.
	 */
	public function run() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'cartellone',
			$this->option_name,
			array(
				'type'              => 'array',
				'description'       => __( 'Cartellone plugin settings', 'cartellone' ),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults,
			)
		);

		add_settings_section(
			'cartellone_general',
			__( 'General', 'cartellone' ),
			'__return_false',
			'cartellone'
		);

		add_settings_field(
			'season_start_day',
			__( 'Season start day', 'cartellone' ),
			array( $this, 'render_day_field' ),
			'cartellone',
			'cartellone_general'
		);

		add_settings_field(
			'season_start_month',
			__( 'Season start month', 'cartellone' ),
			array( $this, 'render_month_field' ),
			'cartellone',
			'cartellone_general'
		);

		add_settings_field(
			'shortcode_cache_ttl',
			__( 'Shortcode cache TTL (seconds)', 'cartellone' ),
			array( $this, 'render_ttl_field' ),
			'cartellone',
			'cartellone_general'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$sanitized = array();

		if ( isset( $input['season_start_day'] ) ) {
			$sanitized['season_start_day'] = str_pad( (int) $input['season_start_day'], 2, '0', STR_PAD_LEFT );
		}

		if ( isset( $input['season_start_month'] ) ) {
			$sanitized['season_start_month'] = str_pad( (int) $input['season_start_month'], 2, '0', STR_PAD_LEFT );
		}

		if ( isset( $input['shortcode_cache_ttl'] ) ) {
			$sanitized['shortcode_cache_ttl'] = max( 0, (int) $input['shortcode_cache_ttl'] );
		}

		return $sanitized;
	}

	/**
	 * Render day field.
	 */
	public function render_day_field() {
		$value = $this->get( 'season_start_day' );
		?>
		<input type="number" name="cartellone_settings[season_start_day]" value="<?php echo esc_attr( $value ); ?>" min="1" max="31" class="small-text">
		<?php
	}

	/**
	 * Render month field.
	 */
	public function render_month_field() {
		$value = $this->get( 'season_start_month' );
		$months = array(
			'01' => __( 'January', 'cartellone' ),
			'02' => __( 'February', 'cartellone' ),
			'03' => __( 'March', 'cartellone' ),
			'04' => __( 'April', 'cartellone' ),
			'05' => __( 'May', 'cartellone' ),
			'06' => __( 'June', 'cartellone' ),
			'07' => __( 'July', 'cartellone' ),
			'08' => __( 'August', 'cartellone' ),
			'09' => __( 'September', 'cartellone' ),
			'10' => __( 'October', 'cartellone' ),
			'11' => __( 'November', 'cartellone' ),
			'12' => __( 'December', 'cartellone' ),
		);
		?>
		<select name="cartellone_settings[season_start_month]">
			<?php foreach ( $months as $num => $name ) : ?>
				<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $value, $num ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render TTL field.
	 */
	public function render_ttl_field() {
		$value = $this->get( 'shortcode_cache_ttl' );
		?>
		<input type="number" name="cartellone_settings[shortcode_cache_ttl]" value="<?php echo esc_attr( $value ); ?>" min="0" class="small-text">
		<p class="description"><?php esc_html_e( '0 disables caching.', 'cartellone' ); ?></p>
		<?php
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function all() {
		return $this->settings;
	}

	/**
	 * Get config hash.
	 *
	 * @return string
	 */
	public function get_config_hash() {
		return md5( $this->get( 'season_start_day' ) . $this->get( 'season_start_month' ) . $this->get( 'shortcode_cache_ttl' ) );
	}

	/**
	 * Get setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}

		return $default;
	}

	/**
	 * Get season start timestamp for a given year.
	 *
	 * @param int $year Year.
	 * @return int
	 */
	public function get_season_start_timestamp( $year ) {
		$day   = $this->get( 'season_start_day', '01' );
		$month = $this->get( 'season_start_month', '09' );

		return mktime( 0, 0, 0, (int) $month, (int) $day, (int) $year );
	}

	/**
	 * Get season year for a given timestamp.
	 *
	 * @param int $timestamp Timestamp.
	 * @return int
	 */
	public function get_season_year( $timestamp ) {
		$year = (int) date( 'Y', $timestamp );
		$season_start = $this->get_season_start_timestamp( $year );

		if ( $timestamp < $season_start ) {
			$year--;
		}

		return $year;
	}

	/**
	 * Get season label for a given year.
	 *
	 * @param int $year Year.
	 * @return string
	 */
	public function get_season_label( $year ) {
		return sprintf( '%04d-%04d', $year, $year + 1 );
	}
}
