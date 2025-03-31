<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;

/**
 * Giftcards class.
 */
class Giftcards extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'giftcards';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'giftcards';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/giftcards';
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
				'permission_callback' => [Middleware::class, 'is_public'],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [Middleware::class, 'is_public'],
				'args'                => [
					'coupon_code' => [
						'description' => __('Coupon code', 'leat-crm'),
						'type'        => 'string',
					],
				],
			],
			'schema' => [$this->schema, 'get_public_item_schema'],
		];
	}

	/**
	 * Checks the Giftcard balance
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$coupon_code = $request->get_param('couponCode');

		if (!$coupon_code) {
			throw new RouteException('missing_coupon_code', 'Missing coupon code', 400);
		}

		try {
			$coupon = $this->wp_giftcard_coupon_repository->find_by_hash($coupon_code);

			if (!$coupon) {
				throw new RouteException('earn-rule-not-found', 'Earn rule not found', 404);
			}

			if (!$this->wp_giftcard_coupon_repository->is_giftcard($coupon)) {
				throw new RouteException('invalid_coupon_code', 'Invalid coupon code', 400);
			}

			$data     = $this->prepare_item_for_response($coupon, $request);
			$response = $this->prepare_response_for_collection($data);

			return $response;
		} catch (RouteException $e) {
			throw $e;
		} catch (\Throwable $th) {
			error_log('Error checking gift card balance: ' . $th->getMessage());
			throw new RouteException("error_checking_giftcard_balance", "Error checking gift card balance", 500);
		}
	}
}
