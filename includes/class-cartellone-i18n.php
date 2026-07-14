<?php

namespace Cartellone;

/**
 * Internationalization.
 */
class i18n {

	/**
	 * Load plugin textdomain.
	 */
	public function run() {
		load_plugin_textdomain(
			'cartellone',
			false,
			dirname( plugin_basename( CARTELLONE_PATH . 'cartellone.php' ) ) . '/languages/'
		);
	}
}
