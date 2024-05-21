<?php
namespace PiggyWP\StoreApiExtension;

use PiggyWP\Integrations\IntegrationInterface;

interface StoreApiExtensionTypeInterface extends IntegrationInterface {
	/**
	 * Returns if this StoreApi extension should be active. If false, the endpoint data will be omitted and any scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active();

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_store_api_extension_callback( $param = null ): array;

	/**
	 * An array of schema types.
	 * client side.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema_ids();

	/**
	 * An Schema array made available to StoreApi extensions
	 * client side.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema();

	/**
	 * Returns an array of script handles to enqueue for this StoreApi extension in
	 * the frontend context
	 *
	 * @return string[]
	 */
	public function get_store_api_extension_script_handles();

	/**
	 * An array of key, value pairs of data made available to StoreApi extensions
	 * client side.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data();
}
