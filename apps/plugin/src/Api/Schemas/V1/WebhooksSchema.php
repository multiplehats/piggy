<?php
namespace PiggyWP\Api\Schemas\V1;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

/**
 * Settings class.
 *
 * @internal
 */
class WebhooksSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'webhooks';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'webhooks';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [];
	}

	/**
	 * Convert a Earn Rule post into an object suitable for the response.
	 *
	 * @param \WP_Post $post Earn Rule post object.
	 * @return array
	 */
	public function get_item_response($post) {
		return true;
	}
}
