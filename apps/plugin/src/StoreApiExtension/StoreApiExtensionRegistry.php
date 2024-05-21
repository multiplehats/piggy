<?php
namespace Piggy\StoreApiExtension;

use Piggy\Integrations\IntegrationRegistry;

/**
 * Class used for interacting with StoreApi Extension types.
 *
 * @since 2.6.0
 */
final class StoreApiExtensionRegistry extends IntegrationRegistry {
	/**
	 * Integration identifier is used to construct hook names and is given when the integration registry is initialized.
	 *
	 * @var string
	 */
	protected $registry_identifier = 'store_api_extension';

	/**
	 * Retrieves all registered StoreApi extensions that are also active.
	 *
	 * @return StoreApiTypeInterface[]
	 */
	public function get_all_active_registered() {
		return array_filter(
			$this->get_all_registered(),
			function( $store_api_extension ) {
				return $store_api_extension->is_active();
			}
		);
	}

	/**
	 * Gets an array of all registered StoreApi extensions data, but only for active StoreApi extensions.
	 *
	 * @return array
	 */
	public function get_all_schema_ids() {
		$schema_ids           = [];
		$store_api_extensions = $this->get_all_active_registered();

		foreach ( $store_api_extensions as $api_extension ) {
			$schema_ids[ $api_extension->get_name() ] = $api_extension->get_store_api_extension_schema_ids();
		}

		return $schema_ids;
	}

	/**
	 * Gets an array of all registered StoreApi extensions schemas, but only for active StoreApi extensions.
	 *
	 * @return array
	 */
	public function get_all_registered_api_schemas() {
		$api_schema           = [];
		$store_api_extensions = $this->get_all_active_registered();

		foreach ( $store_api_extensions as $api_extension ) {
			$api_schema[ $api_extension->get_name() ] = $api_extension->get_store_api_extension_schema();
		}

		return $api_schema;
	}

	/**
	 * Gets an array of all registered StoreApi extensions controllers, but only for active StoreApi extensions.
	 */
	public function get_all_registered_endpoint_extensions() {
		$api                 = [];
		$store_api_extension = $this->get_all_active_registered();

		foreach ( $store_api_extension as $api_extension ) {
			$endpoints = $api_extension->get_store_api_extension_schema_ids();

			if ( ! empty( $endpoints ) && is_array( $endpoints ) ) {
				foreach ( $endpoints as $endpoint ) {
					$api[ $endpoint ][ $api_extension->get_name() ] = array(
						'name'   => $api_extension->get_name(),
						'schema' => $api_extension->get_store_api_extension_schema(),
						'callback' => array( $api_extension, 'get_store_api_extension_callback' ),
					);
				}
			}
		}

		return $api;
	}

	/**
	 * Gets an array of all registered StoreApi extensions script handles, but only for active StoreApi extensions.
	 *
	 * @return string[]
	 */
	public function get_all_active_store_api_extension_script_dependencies() {
		$script_handles = [];
		$store_api_ext  = $this->get_all_active_registered();

		foreach ( $store_api_ext as $store_api_ext ) {
			$script_handles = array_merge(
				$script_handles,
				is_admin() ? $store_api_ext->get_store_api_extension_script_handles_for_admin() : $store_api_ext->get_store_api_extension_script_handles()
			);
		}

		return array_unique( array_filter( $script_handles ) );
	}

	/**
	 * Gets an array of all registered StoreApi extensions script data, but only for active StoreApi extensions.
	 *
	 * @return array
	 */
	public function get_all_registered_script_data() {
		$script_data          = [];
		$store_api_extensions = $this->get_all_active_registered();

		foreach ( $store_api_extensions as $api_extension ) {
			$script_data[ $api_extension->get_name() . '_data' ] = $api_extension->get_store_api_extension_script_data();
		}

		return array_filter( $script_data );
	}
}
