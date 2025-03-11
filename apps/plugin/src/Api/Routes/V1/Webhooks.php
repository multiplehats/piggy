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
		$signature = $request->get_header('signature');

		if (! $signature) {
			$this->logger->error('No webhook signature provided');

			return false;
		}

		$payload = $request->get_body();
		$secret  = get_option('leat_webhook_secret');

		if (! $secret) {
			$this->logger->error('Webhook secret not found');

			return false;
		}

		$calculated_signature = hash_hmac('sha256', $payload, $secret);
		$is_valid = hash_equals($signature, $calculated_signature);

		if (!$is_valid) {
			$this->logger->error('Webhook signature verification failed', [], true);
		}

		return $is_valid;
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
