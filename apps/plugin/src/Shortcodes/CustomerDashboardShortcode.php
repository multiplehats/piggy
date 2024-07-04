<?php

namespace PiggyWP\Shortcodes;

use PiggyWP\Shortcodes\AbstractShortcode;

class CustomerDashboardShortcode extends AbstractShortcode {
	/**
	 * Shortcode name within this namespace.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'dashboard';

	function get_assets() {
		return array();
	}

	public function init_hooks() {
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'fragments' ) );
	}

	/**
	 * Sets the shortcode default attributes.
	 *
	 * @return array Default attributes for the shortcode.
	 */
	public function get_shortcode_type_attributes(): array {
		return array();
	}

	public function shortcode_output($attributes, $content = '') {
		$output = ob_start();

		?><div class="piggy-customer-dashboard"></div><?php

		$output = ob_get_clean();

		return $output;
	}
}