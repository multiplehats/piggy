<?php

namespace Leat\StoreApiExtension\Core;

use Leat\StoreApiExtension\AbstractStoreApiExtensionType;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Leat\Utils\PotentialCreditDisplay;

/**
 * Common: StoreApi extension integration
 *
 * @since 3.0.0
 */
final class Common extends AbstractStoreApiExtensionType
{
	/**
	 * StoreApi extension name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = 'common';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = [CartSchema::IDENTIFIER];

	/**
	 * @var PotentialCreditDisplay
	 */
	private $potential_credit_display;

	/**
	 * Initializes the StoreApi extension type.
	 */
	public function initialize()
	{
		$this->potential_credit_display = new PotentialCreditDisplay($this->connection, $this->settings);
	}

	/**
	 * Returns if this StoreApi extension should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return true;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this Store Api extension.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_handles()
	{
		return [];
	}

	/**
	 * Returns the schema type.
	 */
	public function get_store_api_extension_schema_ids()
	{
		return $this->schema_ids;
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the Store Api extension script.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data()
	{
		return [];
	}

	/**
	 * Returns an Schema array for the StoreApi.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema()
	{
		return [
			'products' => [
				'description' => __('Products to recommend.', 'leat-crm'),
				'type'        => array('array', 'null'),
				'context'     => array('view', 'edit'),
				'readonly'    => true,
			],
		];
	}

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_store_api_extension_callback($param = null): array
	{
		return $this->get_cart_total_data();
	}

	/**
	 * Get shipping data
	 *
	 * @return array
	 */
	public function get_cart_total_data(): array
	{
		$this->cart_controller->load_cart();
		$cart = $this->cart_controller->get_cart_instance();
		$data = [];
		$estimated_credits  = null;

		// If it's the WC Cart, set the estimated credits to the cart total.
		if ($cart instanceof \WC_Cart) {
			$estimated_credits = $this->potential_credit_display->get_potential_credits($cart->get_total('edit'));
		}

		$data = array(
			'estimatedCredits'    => $estimated_credits,
		);

		return $data;
	}
}
