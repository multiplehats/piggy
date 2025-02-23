<?php

namespace Leat\Api\Routes\V1\Admin;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;
use Leat\Api\Services\WebhookManager;

/**
 * Shops class.
 *
 * @internal
 */
class Settings extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'settings';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'settings';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/settings';
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
						'description' => __('Settings', 'leat-crm'),
						'type'        => 'object',
					],
				],
			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [Middleware::class, 'is_authorized'],
				'args'                => [
					'id' => [
						'description' => __('Setting ID', 'leat-crm'),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Update settings
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$settings = $request->get_param('settings');

		if (! $settings) {
			return rest_ensure_response(null);
		}

		$old_api_key = $this->settings->get_setting_by_id('api_key');
		$old_shop_uuid = $this->settings->get_setting_by_id('shop_uuid');

		$old_api_key_value = is_array($old_api_key) ? ($old_api_key['value'] ?? '') : $old_api_key;
		$old_shop_uuid_value = is_array($old_shop_uuid) ? ($old_shop_uuid['value'] ?? '') : $old_shop_uuid;

		$new_api_key_value = is_array($settings['api_key']) ? ($settings['api_key']['value'] ?? '') : ($settings['api_key'] ?? '');
		$new_shop_uuid_value = is_array($settings['shop_uuid']) ? ($settings['shop_uuid']['value'] ?? '') : ($settings['shop_uuid'] ?? '');

		$result = $this->settings->update_settings($settings);

		/**
		 * If the API key changes, sync the rewards and promotions
		 * and sync the webhooks but only if the shop UUID is set.
		 */
		if (isset($new_api_key_value) && $new_api_key_value !== $old_api_key_value && !empty($new_shop_uuid_value)) {
			$this->sync_rewards->start_sync();
			$this->sync_promotions->start_sync();
			$this->webhook_manager->sync_webhooks();
		}

		/**
		 * If the shop UUID changes, sync the rewards and promotions
		 */
		if (isset($new_shop_uuid_value) && $new_shop_uuid_value !== $old_shop_uuid_value && !empty($new_api_key_value)) {
			$this->sync_rewards->start_sync();
			$this->sync_promotions->start_sync();
			$this->webhook_manager->sync_webhooks();
		}

		return rest_ensure_response($result);
	}

	/**
	 * Get a specific setting
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response(\WP_REST_Request $request)
	{
		$id = $request->get_param('id');

		if ($id) {
			$setting = $this->settings->get_setting_by_id($id);

			if (! $setting) {
				return rest_ensure_response(null);
			}

			// Mask API key if user doesn't have manage_options capability
			if ($id === 'api_key' && !current_user_can('manage_options')) {
				$setting = $this->mask_api_key($setting);
			}

			return rest_ensure_response($setting);
		}

		$include_api_key = current_user_can('manage_options');
		$all_settings    = $this->settings->get_all_settings_with_values($include_api_key);

		// Returns settings as an object rather than an array.
		// This makes it easier to work with in the front-end.
		$return = [];
		foreach ($all_settings as $item) {
			$data                  = $this->prepare_item_for_response($item['id'], $request);
			// Mask API key in the full settings response if user doesn't have manage_options
			if ($item['id'] === 'api_key' && !current_user_can('manage_options')) {
				$data = $this->mask_api_key($data);
			}
			$return[$item['id']] = $this->prepare_response_for_collection($data);
		}

		return rest_ensure_response($return);
	}

	/**
	 * Mask API key, showing only the last 4 characters
	 *
	 * @param string|array $setting The setting to mask
	 * @return string|array
	 */
	private function mask_api_key($setting)
	{
		if (is_array($setting)) {
			if (isset($setting['value'])) {
				$setting['value'] = $this->create_masked_value($setting['value']);
			}
			return $setting;
		}
		return $this->create_masked_value($setting);
	}

	/**
	 * Create a masked version of the API key
	 *
	 * @param string $value The value to mask
	 * @return string
	 */
	private function create_masked_value($value)
	{
		if (empty($value)) {
			return '';
		}
		$length = strlen($value);
		$visible_chars = 4;
		$masked_length = $length - $visible_chars;
		return str_repeat('•', $masked_length) . substr($value, -$visible_chars);
	}
}
