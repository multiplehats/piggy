<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\Middleware;

/**
 * SyncWebhooks class.
 *
 * @internal
 */
class SyncWebhooks extends AbstractRoute
{
    /**
     * The route identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'sync-webhooks';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const SCHEMA_TYPE = 'sync-webhooks';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path()
    {
        return '/sync-webhooks';
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
                'permission_callback' => [Middleware::class, 'is_authorized'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'get_response'],
                'permission_callback' => [Middleware::class, 'is_authorized'],
            ],
            'schema'      => [$this->schema, 'get_public_item_schema'],
            'allow_batch' => ['v1' => true],
        ];
    }

    /**
     * Get the current webhooks status
     *
     * @param WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    protected function get_route_response(\WP_REST_Request $request)
    {
        $webhooks = $this->webhook_manager->get_webhooks();
        $required_webhooks = $this->webhook_manager->get_required_webhooks();

        return rest_ensure_response([
            'webhooks' => $webhooks,
            'required_webhooks' => $required_webhooks,
        ]);
    }

    /**
     * Sync webhooks with Leat
     *
     * @param  \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_post_response(\WP_REST_Request $request)
    {
        try {
            $this->webhook_manager->sync_webhooks();

            return rest_ensure_response([
                'success' => true,
                'webhooks' => $this->webhook_manager->get_webhooks(),
            ]);
        } catch (\Exception $e) {
            throw new RouteException('sync-webhooks', $e->getMessage(), 400);
        }
    }
}
