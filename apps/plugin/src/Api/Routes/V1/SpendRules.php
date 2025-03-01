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
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [MIddleware::class, 'is_authorized'],
				'args'                => [
					'settings' => [
						'description' => __('Spend rules', 'leat-crm'),
						'type'        => 'object',
					],
				],
			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [Middleware::class, 'is_public'],
				'args'                => [
					'id'     => [
						'description' => __('Spend rule ID', 'leat-crm'),
						'type'        => 'string',
					],
					'status' => [
						'description' => __('Spend rule status', 'leat-crm'),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Saves spend rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$data = array(
			'label'                 => $request->get_param('label'),
			'type'                  => $request->get_param('type'),
			'status'                => $request->get_param('status'),
			'title'                 => $request->get_param('title'),
			'starts_at'             => $request->get_param('starts_at'),
			'expires_at'             => $request->get_param('expires_at'),
			'completed'             => $request->get_param('completed'),
			'description'           => $request->get_param('description'),
			'instructions'          => $request->get_param('instructions'),
			'fulfillment'           => $request->get_param('fulfillment'),
			'discount_value'         => $request->get_param('discountValue'),
			'discount_type'          => $request->get_param('discountType'),
			'limit_usage_to_x_items'    => $request->get_param('limitUsageToXItems'),
			'minimum_purchase_amount' => $request->get_param('minimumPurchaseAmount'),
			'selected_products'      => $request->get_param('selectedProducts'),
			'selected_categories'    => $request->get_param('selectedCategories'),
		);

		try {
			$this->spend_rules_service->create_or_update($data, $request->get_param('id'));

			$response = $this->prepare_item_for_response(
				$this->spend_rules_service->get_by_id($request->get_param('id')),
				$request
			);

			return rest_ensure_response($response);
		} catch (\Exception $e) {
			$this->logger->error('Failed to save spend rule', ['error' => $e->getMessage()]);

			throw new RouteException('spend-rules', 'Failed to save spend rule', 500);
		}
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
		$id = $request->get_param('id');
		$status = $request->get_param('status') ? explode(',', $request->get_param('status')) : ['publish'];

		$posts = $id
			? $this->get_single_post($id)
			: $this->spend_rules_service->get_rules($status);

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
