<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Webhooks Schema class.
 *
 * @internal
 */
class WebhooksSchema extends AbstractSchema
{
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'webhooks';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'webhooks';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties()
	{
		return [
			'event_type' => [
				'description' => __('Type of webhook event', 'leat-crm'),
				'type'        => 'string',
				'enum'        => [
					'voucher_created',
					'voucher_updated',
					'voucher_redeemed',
					'promotion_updated',
				],
			],
			'data'       => [
				'description' => __('Webhook payload data', 'leat-crm'),
				'type'        => 'object',
				'properties'  => [
					'uuid'           => [
						'description' => __('UUID of the affected resource', 'leat-crm'),
						'type'        => 'string',
					],
					'contact_uuid'   => [
						'description' => __('UUID of the related contact', 'leat-crm'),
						'type'        => 'string',
					],
					'promotion_uuid' => [
						'description' => __('UUID of the related promotion', 'leat-crm'),
						'type'        => 'string',
					],
					'status'         => [
						'description' => __('Status of the resource', 'leat-crm'),
						'type'        => 'string',
					],
					'created_at'     => [
						'description' => __('Creation timestamp', 'leat-crm'),
						'type'        => 'string',
						'format'      => 'date-time',
					],
					'updated_at'     => [
						'description' => __('Last update timestamp', 'leat-crm'),
						'type'        => 'string',
						'format'      => 'date-time',
					],
				],
			],
			'signature'  => [
				'description' => __('Webhook signature for verification', 'leat-crm'),
				'type'        => 'string',
			],
		];
	}
}
