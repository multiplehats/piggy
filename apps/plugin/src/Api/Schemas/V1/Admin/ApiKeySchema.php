<?php
namespace PiggyWP\Api\Schemas\V1\Admin;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

/**
 * ApiKey class.
 *
 * @internal
 */
class ApiKeySchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'api-key';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'api-key';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'api_key' => [
				'description' => __( 'The API key.', 'piggy' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
		];
	}

	/**
	 * Get the API key response.
	 *
	 * @param array $item Item to get response for.
	 *
	 * @return array
	 */
	public function get_item_response( $item ) {
		return [
			'api_key' => true,
		];
	}
}
