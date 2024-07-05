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
		$subscriptions = $contact->getSubscriptions();
		$subscription_list = array();

		if( $subscriptions ) {
			foreach( $subscriptions as $subscription ) {
				$type = $subscription->getSubscriptionType();

				$subscription_list[] = [
					'is_subscribed' => $subscription->isSubscribed(),
					'status' => $subscription->getStatus(),
					'type' => [
						'uuid' => $type->getUuid(),
						'name' =>  $type->getName(),
						'description' => $type->getDescription(),
						'active' => $type->isActive(),
						'strategy' => $type->getStrategy(),
					]
				];
			}
		}

		return [
			'uuid' => $contact->getUuid(),
			'email' => $contact->getEmail(),
			'subscriptions' => isset( $subscription_list ) ? $subscription_list : [],
			'attributes' =>  $contact->getCurrentValues(),
			'balance' => [
				'prepaid' => $contact->getPrepaidBalance()->getBalanceInCents(),
				'credit'  => $contact->getCreditBalance()->getBalance(),
			]
		];
	}
}
