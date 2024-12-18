<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Domain\Services\WebhookManager;
use Leat\Api\Exceptions\RouteException;

/**
 * Webhooks class.
 *
 * @internal
 */
class Webhooks extends AbstractRoute {
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
     * @var WebhookManager
     */
    private $webhook_manager;

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path() {
        return '/webhooks';
    }

    /**
     * Get method arguments for this REST route.
     *
     * @return array An array of endpoints.
     */
    public function get_args() {
        return [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'get_response' ],
                'permission_callback' => [ $this, 'verify_webhook_signature' ],
                'args'                => [
                    'event_type' => [
                        'required' => false,
                        'type'     => 'string',
                    ],
                ],
            ],
            'schema'      => [ $this->schema, 'get_public_item_schema' ],
            'allow_batch' => [ 'v1' => true ],
        ];
    }

    /**
     * Verify webhook signature from Piggy
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool
     */
    public function verify_webhook_signature(\WP_REST_Request $request) {
        $webhook_manager = new WebhookManager($this->connection);

        $signature = $request->get_header('X-Piggy-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->get_body();
        $secret = get_option('leat_webhook_secret');

        if (!$secret) {
            // If no secret is set, we'll accept the webhook for now
            // This should be changed in production
            return true;
        }

        $calculated_signature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($signature, $calculated_signature);
    }

    /**
     * Handle incoming webhook
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    protected function get_route_post_response(\WP_REST_Request $request) {
        // $webhook_manager = new WebhookManager($this->connection);

        // $event_type = $request->get_header('X-Piggy-Event');

        // if (!$event_type) {
        //     throw new RouteException('invalid_webhook', 'Missing event type header', 400);
        // }

        // $this->webhook_manager->handle_webhook($event_type, $request->get_json_params());

        return rest_ensure_response(new \WP_REST_Response(['status' => 'success'], 200));
    }
}