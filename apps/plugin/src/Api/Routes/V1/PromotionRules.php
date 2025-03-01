<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;

/**
 * Shops class.
 *
 * @internal
 */
class PromotionRules extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'promotion-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'promotion-rules';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/promotion-rules';
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
				'permission_callback' => [Middleware::class, 'is_authorized'],
				'args'                => [
					'settings' => [
						'description' => __('Promotion rules', 'leat-crm'),
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
						'description' => __('Promotion rule ID', 'leat-crm'),
						'type'        => 'string',
					],
					'status' => [
						'description' => __('Promotion rule status', 'leat-crm'),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Saves promotion rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$old_status = !empty($request->get_param('id')) ? get_post_status($request->get_param('id')) : null;
		$new_status = $request->get_param('status');

		$promotion = array(
			'uuid' => $request->get_param('uuid'),
			'title' => $request->get_param('title'),
			'selected_products' => $request->get_param('selectedProducts'),
			'status' => $new_status,
			'label' => $request->get_param('label'),
			'discount_value' => $request->get_param('discountValue'),
			'discount_type' => $request->get_param('discountType'),
			'minimum_purchase_amount' => $request->get_param('minimumPurchaseAmount'),
			'voucher_limit' => $request->get_param('voucherLimit'),
			'individual_use' => $request->get_param('individualUse'),
			'limit_per_contact' => $request->get_param('limitPerContact'),
			'expiration_duration' => $request->get_param('expirationDuration'),
			'redemptions_per_voucher' => $request->get_param('redemptionsPerVoucher'),
		);

		try {
			$this->promotion_rules_service->create_or_update($promotion, $request->get_param('id'));

			if ($old_status !== 'publish' && $new_status === 'publish') {
				$this->logger->info('Promotion rule published, syncing vouchers');
				$this->sync_vouchers->start_sync();
			}

			$response = $this->prepare_item_for_response(
				$this->promotion_rules_service->get_by_id($request->get_param('id')),
				$request
			);

			return rest_ensure_response($response);
		} catch (\Exception $e) {
			throw new RouteException('promotion-rules', 'Failed to save promotion rule', 500);
		}
	}

	/**
	 * Get promotion rules or a specific promotion rule
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
			: $this->promotion_rules_service->get_rules($status);

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
		$post = $this->promotion_rules_service->get_by_id($id);

		if (empty($post)) {
			throw new RouteException('promotion-rules', 'Promotion rule not found', 404);
		}

		return [$post];
	}
}
