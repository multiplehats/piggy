<?php
namespace PiggyWP\Api\Schemas\V1\Admin;

use PiggyWP\Api\Schemas\V1\AbstractSchema;
use Piggy\Api\Models\Contacts\Contact;

/**
 * Contacts class.
 *
 * @internal
 */
class ContactsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'contacts';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'contacts';

	/**
	 * Contacts schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'uuid'          => [
				'description' => __( 'The contacts\'s unique id.', 'piggy' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
		];
	}

	/**
	 * Get the Contacts response.
	 *
	 * @param Contact $contact The contact object.
	 *
	 * @return array
	 */
	public function get_item_response( $contact ) {
		return [
			'uuid' => $contact->getUuid(),
		];
	}
}
