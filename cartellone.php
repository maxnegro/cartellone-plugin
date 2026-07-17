<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/maxnegro/cartellone-plugin
 * @license           GPL-2.0-or-later
 * @package           cartellone
 *
 * @cartellone
 * Plugin Name:       Cartellone Teatro Bibiena
 * Plugin URI:        https://github.com/maxnegro/cartellone-plugin
 * Description:       Supporto per l'inserimento spettacoli in cartellone
 * Version:           2.0.0
 * Author:            Massimiliano Masserelli
 * Author URI:        http://photomarketing.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cartellone
 * Domain Path:       /languages
 * GitHub Plugin URI: maxnegro/cartellone-plugin
 * Primary Branch:    master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Plugin constants.
if ( ! defined( 'CARTELLONE_VERSION' ) ) {
	define( 'CARTELLONE_VERSION', '2.0.0' );
}

if ( ! defined( 'CARTELLONE_CPT' ) ) {
	define( 'CARTELLONE_CPT', 'spettacoli' );
}

if ( ! defined( 'CARTELLONE_TAX_STAGIONE' ) ) {
	define( 'CARTELLONE_TAX_STAGIONE', 'stagione' );
}

if ( ! defined( 'CARTELLONE_TAX_TIPO' ) ) {
	define( 'CARTELLONE_TAX_TIPO', 'tipo' );
}

if ( ! defined( 'CARTELLONE_META_DATA' ) ) {
	define( 'CARTELLONE_META_DATA', 'cartellone_data' );
}

if ( ! defined( 'CARTELLONE_META_SORT' ) ) {
	define( 'CARTELLONE_META_SORT', 'cartellone_data_sort' );
}

if ( ! defined( 'CARTELLONE_META_DATE' ) ) {
	define( 'CARTELLONE_META_DATE', 'cartellone_data_data' );
}

if ( ! defined( 'CARTELLONE_META_ORA' ) ) {
	define( 'CARTELLONE_META_ORA', 'cartellone_ora' );
}

if ( ! defined( 'CARTELLONE_META_PRODUZIONE' ) ) {
	define( 'CARTELLONE_META_PRODUZIONE', 'cartellone_produzione' );
}

if ( ! defined( 'CARTELLONE_META_PROTAGONISTI' ) ) {
	define( 'CARTELLONE_META_PROTAGONISTI', 'cartellone_protagonisti' );
}

if ( ! defined( 'CARTELLONE_META_CREDITS' ) ) {
	define( 'CARTELLONE_META_CREDITS', 'cartellone_credits' );
}

if ( ! defined( 'CARTELLONE_META_VIVATICKET' ) ) {
	define( 'CARTELLONE_META_VIVATICKET', 'cartellone_vivaticket' );
}

if ( ! defined( 'CARTELLONE_DB_VERSION' ) ) {
	define( 'CARTELLONE_DB_VERSION', '2.0.0' );
}

if ( ! defined( 'CARTELLONE_PATH' ) ) {
	define( 'CARTELLONE_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CARTELLONE_URL' ) ) {
	define( 'CARTELLONE_URL', plugin_dir_url( __FILE__ ) );
}

// Composer autoloader.
$autoload = CARTELLONE_PATH . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	require_once CARTELLONE_PATH . 'includes/class-cartellone.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-data.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-settings.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-i18n.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-activator.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-deactivator.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-divi-loop-hide.php';
	require_once CARTELLONE_PATH . 'includes/class-cartellone-cli.php';
	require_once CARTELLONE_PATH . 'admin/class-cartellone-admin.php';
	require_once CARTELLONE_PATH . 'public/class-cartellone-public.php';
}

/**
 * The code that runs during plugin activation.
 */
function activate_cartellone() {
	$activator = new \Cartellone\Activator();
	$activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_cartellone() {
	$deactivator = new \Cartellone\Deactivator();
	$deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_cartellone' );
register_deactivation_hook( __FILE__, 'deactivate_cartellone' );

// Load plugin.
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( '\Cartellone\Cartellone' ) ) {
			return;
		}

		$plugin = new \Cartellone\Cartellone();
		$plugin->run();
	}
);
