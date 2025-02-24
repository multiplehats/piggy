<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * SyncWebhooks Schema class.
 *
 * @internal
 */
class SyncWebhooksSchema extends AbstractSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'sync-webhooks';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'sync-webhooks';

    /**
     * Schema properties.
     *
     * @return array
     */
    public function get_properties()
    {
        return [
            'success' => [
                'type' => 'boolean',
                'description' => 'Whether the sync request was successful',
                'readonly' => true,
            ],
            'webhooks' => [
                'type' => 'array',
                'description' => 'List of currently synced webhooks',
                'readonly' => true,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                            'description' => 'Webhook ID',
                        ],
                        'name' => [
                            'type' => 'string',
                            'description' => 'Webhook name',
                        ],
                        'event_type' => [
                            'type' => 'string',
                            'description' => 'Webhook event type',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'Webhook delivery URL',
                        ],
                        'status' => [
                            'type' => 'string',
                            'description' => 'Webhook status',
                        ],
                    ],
                ],
            ],
            'required_webhooks' => [
                'type' => 'array',
                'description' => 'List of required webhooks',
                'readonly' => true,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Webhook name',
                        ],
                        'event_type' => [
                            'type' => 'string',
                            'description' => 'Webhook event type',
                        ],
                        'attributes' => [
                            'type' => 'array',
                            'description' => 'Optional webhook attributes',
                            'items' => [
                                'type' => 'string'
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
