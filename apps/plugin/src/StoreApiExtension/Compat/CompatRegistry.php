<?php

namespace Leat\StoreApiExtension\Compat;

use Leat\StoreApiExtension\AbstractStoreApiExtensionType;

/**
 * Compatibility
 * @see https://wordpress.org/plugins/woo-product-bundle/
 *
 * @since 2.0.0
 */
final class CompatRegistry extends AbstractStoreApiExtensionType
{
	/**
	 * StoreApi extension name/id/slug
	 *
	 * @var string
	 */
	protected $name = 'compatibility';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = array();

	/**
	 * Supported plugins
	 *
	 * @var array
	 */
	private $supported_plugins = [];

	protected $integrations = [];

	/**
	 * Initializes the available integrations.
	 */
	public function initialize()
	{
		foreach ($this->supported_plugins as $integrationClass) {
			$integrationInstance = new $integrationClass(
				$this->cart_controller
			);

			if ($integrationInstance->is_active()) {
				$this->integrations[$integrationInstance->integration_id()] = $integrationInstance;
			}
		}
	}

	/**
	 * Returns if this StoreApi extension should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return !empty($this->integrations);
	}

	/**
	 * Returns an array of scripts/handles to be registered for this Store Api extension.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_handles()
	{
		return array();
	}

	/**
	 * Returns the schema type.
	 */
	public function get_store_api_extension_schema_ids()
	{
		$schema_ids = [];

		foreach ($this->integrations as $integration) {
			$schema_ids = array_merge($schema_ids, $integration->get_schema_ids());
		}

		return $schema_ids;
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the Store Api extension script.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data()
	{
		return array();
	}

	/**
	 * Returns an Schema array for the StoreApi.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema()
	{
		$schema = [];

		foreach ($this->integrations as $integration) {
			$integration_schema = $integration->get_schema();
			$schema[$integration->integration_id()] = $integration_schema;
		}

		return $schema;
	}

	/**
	 * The callback for the StoreApi extension.
	 */
	public function get_store_api_extension_callback($param = null): array
	{
		$callback = array();

		// Gets all the data for each integration.
		foreach ($this->integrations as $integration) {
			// Only add the callback if it's an integration cart item.
			if (!$integration->is_integration_cart_item($param)) {
				continue;
			}

			$integration_callback[$integration->integration_id()] = $integration->get_callback($param);

			$callback = array_merge($callback, $integration_callback);
		}

		return $callback;
	}
}
