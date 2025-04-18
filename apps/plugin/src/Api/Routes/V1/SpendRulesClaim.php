<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;
use Leat\Api\Connection;
use Leat\Api\Exceptions\RouteException;

/**
 * SpendRuleSync class.
 *
 * @internal
 */
class SpendRulesClaim extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules-claim';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'spend-rules-claim';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/spend-rules-claim';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args()
	{
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => function ($request) {
					$user_id = $request->get_param('userId');
					return Middleware::is_valid_user(intval($user_id));
				},
				'args'                => [
					'id'      => [
						'type'     => 'integer',
						'required' => true,
					],
					'user_id' => [
						'type'     => 'integer',
						'required' => false,
					],
				],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Claims a spend rule.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 * @throws \RouteException If the spend rule is not found.
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$id                  = $request->get_param('id');
		$user_id             = $request->get_param('userId');

		if (! $id) {
			throw new RouteException('spend-rules-claim', 'Spend rule ID is required', 400);
		}

		if (! $user_id) {
			throw new RouteException('spend-rules-claim', 'User ID is required', 400);
		}

		$rule = $this->spend_rules_service->get_by_id($id);

		if (! $rule) {
			throw new RouteException('spend-rules-claim', 'Spend rule not found', 404);
		}

		if ('draft' === $rule['status']['value']) {
			throw new RouteException('spend-rules-claim', 'Spend rule is a draft', 400);
		}

		$contact = $this->connection->get_contact_by_wp_id($user_id);
		$uuid    = $contact['uuid'];

		if (! $uuid) {
			throw new RouteException('spend-rules-claim', 'User not found in Leat', 404);
		}

		$reward_uuid = $rule['leatRewardUuid']['value'];

		if (! $reward_uuid) {
			throw new RouteException('spend-rules-claim', 'Reward UUID not found for this spend rule', 404);
		}

		try {
			$reception = $this->connection->create_reward_reception($uuid, $reward_uuid);

			if (! $reception) {
				throw new RouteException('spend-rules-claim', 'Failed to create Reward Reception', 500);
			}

			$coupon = $this->spend_rules_service->create_coupon_for_spend_rule($rule, $user_id);

			if (! $coupon) {
				throw new RouteException('spend-rules-claim', 'Failed to create coupon', 500);
			}

			return [
				'coupon'           => $coupon,
				'reward_reception' => $reception,
			];
		} catch (\Throwable $th) {
			$message = $th->getMessage();

			if (strpos($th->getMessage(), 'You have insufficient credits') !== false) {
				throw new RouteException('spend-rules-claim', __('Insufficient credits', 'leat-crm'), 400);
			}

			throw new RouteException('spend-rules-claim', $message, 500);
		}
	}
}
