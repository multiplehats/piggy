<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;


/**
 * Webhooks class.
 *
 * @internal
 */
class Webhooks extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'webhooks';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'webhooks';


	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/webhooks';
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
				'permission_callback' => [$this, 'verify_webhook_signature'],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Verify webhook signature from Piggy
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function verify_webhook_signature(\WP_REST_Request $request)
	{
		// error_log('request: ' . json_encode($request->get_json_params()));

		// $signature = $request->get_header('X-Piggy-Signature');

		// // Log all headers
		// // error_log('headers: ' . json_encode($request->get_headers()));

		// if (! $signature) {
		// 	return true;
		// }

		// $payload = $request->get_body();
		// $secret  = get_option('leat_webhook_secret');

		// if (! $secret) {
		// 	throw new RouteException('webhook-signature', 'Webhook secret not found', 400);
		// }

		// $calculated_signature = hash_hmac('sha256', $payload, $secret);

		// return hash_equals($signature, $calculated_signature);

		return true;
	}

	/**
	 * Handle incoming webhook
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$payload = $request->get_json_params();;

		// Extract event type from payload
		$event_type = $payload['type'] ?? null;

		if (!$event_type) {
			throw new RouteException('webhook-event', 'Missing event type in webhook payload', 400);
		}

		// Pass the event type and data to the webhook handler
		$this->webhook_manager->handle_webhook($event_type, $payload['data'] ?? []);

		return rest_ensure_response(new \WP_REST_Response(['status' => 'success'], 200));
	}
}
