<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;

/**
 * Spend Rules class.
 *
 * @internal
 */
class SpendRules extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'spend-rules';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/spend-rules';
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
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => function ($request) {
					$user_id = $request->get_param('userId');
					return Middleware::is_valid_user(intval($user_id));
				},
				'args'                => [
					'id'     => [
						'description' => __('Spend rule ID', 'leat-crm'),
						'type'        => 'string',
					],
					'status' => [
						'description' => __('Spend rule status', 'leat-crm'),
						'type'        => 'string',
					],
					'user_id' => [
						'description' => __('User ID to get applicable spend rules for', 'leat-crm'),
						'type'        => 'integer',
					],
				],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Get spend rules or a specific spend rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response(\WP_REST_Request $request)
	{
		$user_id             = $request->get_param('userId');

		if (! $user_id) {
			throw new RouteException('spend-rules-claim', 'User ID is required', 400);
		}

		$contact = $this->connection->get_contact_by_wp_id($user_id);
		$uuid    = $contact['uuid'];

		if (! $uuid) {
			throw new RouteException('spend-rules-claim', 'User not found in Leat', 404);
		}

		$posts = $this->spend_rules_service->get_rules_for_contact($uuid, $this->connection);

		$response_objects = array_map(function ($post) use ($request) {
			return $this->prepare_response_for_collection(
				$this->prepare_item_for_response($post, $request)
			);
		}, $posts);

		return rest_ensure_response($response_objects);
	}

	/**
	 * Helper method to get and validate a single post
	 *
	 * @param string $id Post ID
	 * @return array Single post in an array
	 * @throws \WP_Error If post not found
	 */
	private function get_single_post($id)
	{
		$post = $this->spend_rules_service->get_by_id($id);

		if (empty($post)) {
			throw new RouteException('spend-rules', 'Spend rule not found', 404);
		}

		return [$post];
	}
}
