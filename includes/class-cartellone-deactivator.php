<?php

namespace Cartellone;

/**
 * Deactivator.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}
