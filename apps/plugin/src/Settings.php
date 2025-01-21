<?php

namespace Leat;

/**
 * Contains all the default options and options from the database.
 */
class Settings
{
	/**
	 * Default settings.
	 */
	public function get_all_settings()
	{
		$settings = [];

		$settings[] = array(
			'title'   => __('Quick actions', 'leat-crm'),
			'id'      => 'plugin_enable',
			'default' => 'on',
			'type'    => 'switch',
			'label'   => __('Enable plugin', 'leat-crm'),
			'tooltip' => __('If you disable this, the plugin will stop working on the front-end of your website. This is useful if you temporarily want to disable plugin functionality without deactivating the entire plugin.', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'plugin_reset',
			'default' => 'off',
			'type'    => 'switch',
			'label'   => __('Delete plugin settings upon deactivation', 'leat-crm'),
			'tooltip' => __('This will delete all plugins settings upon deactivation. Use with caution!', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'api_key',
			'default' => '',
			'type'    => 'text',
			'label'   => __('API Key', 'leat-crm'),
			'tooltip' => __('Enter your API key here.', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'shop_uuid',
			'default' => '',
			'type'    => 'text',
			'label'   => __('Shop ID', 'leat-crm'),
			'tooltip' => __('Select the shop you want to connect to.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'credits_name',
			'default'     => array(
				'default' => __('Credits', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Credits name', 'leat-crm'),
			'description' => __('The name of the credits in your shop.', 'leat-crm'),
		);

		$settings[] = array(
			'id'          => 'credits_spend_rule_progress',
			'default'     => array(
				'default' => __('You have {{ credits }} {{ credits_currency }} out of {{ credits_required }}', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Credits balance', 'leat-crm'),
			/* translators: %s: a list of placeholders */
			'description' => sprintf(__('The format of the credits balance message that will be displayed to the user. The following placeholders can be used: %s', 'leat-crm'), '{{ credits }}, {{ credits_currency }} and {{ credits_required }}'),
		);
		$settings[] = array(
			'id'          => 'include_guests',
			'default'     => 'off',
			'type'        => 'switch',
			'label'       => __('Include guests', 'leat-crm'),
			'description' => __('Include customers without an account (guests) in your loyalty program.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'reward_order_statuses',
			'type'        => 'select',
			'label'       => __('Reward order statuses', 'leat-crm'),
			'description' => __('Select which order status will trigger a credit reward for customers', 'leat-crm'),
			'default'     => 'completed',
			'options'     => $this->woocommerce_order_statuses_options(),
		);
		$settings[] = array(
			'id'          => 'withdraw_order_statuses',
			'type'        => 'checkboxes',
			'label'       => __('Withdraw credits order statuses', 'leat-crm'),
			'description' => __('Select which order statuses will trigger a credit refund to the customer. Credits will be refunded only once, when the order first reaches any of the selected statuses. For example, if both "Refunded" and "Cancelled" are selected, credits will be returned to the customer when the order is either refunded or cancelled, whichever happens first. Note that partial refunds are only supported for refunded orders.', 'leat-crm'),
			'default'     => array('refunded' => 'on'),
			'options'     => $this->woocommerce_order_statuses_options(),
		);
		$settings[] = array(
			'id'          => 'reward_order_parts',
			'type'        => 'checkboxes',
			'label'       => __('Reward order parts', 'leat-crm'),
			'description' => __('Reward customers for the following parts of an order', 'leat-crm'),
			'default'     => array('subtotal' => 'on'),
			'options'     => array(
				'subtotal' => array(
					'label'   => __('Subtotal', 'leat-crm'),
					'tooltip' => __('The total amount of the order before taxes and shipping.', 'leat-crm'),
				),
				'shipping' => array(
					'label'   => __('Shipping', 'leat-crm'),
					'tooltip' => __('The cost of shipping the order.', 'leat-crm'),
				),
				'tax'      => array(
					'label'   => __('Tax', 'leat-crm'),
					'tooltip' => __('The amount of tax on the order.', 'leat-crm'),
				),
				'discount' => array(
					'label'   => __('Discount', 'leat-crm'),
					'tooltip' => __('The amount of discount on the order.', 'leat-crm'),
				),
			),
		);

		$settings[] = array(
			'id'          => 'marketing_consent_subscription',
			'type'        => 'select',
			'label'       => __('Marketing consent subscription', 'leat-crm'),
			'description' => __('Opt-in users to receive marketing emails.', 'leat-crm'),
			'default'     => 'off',
		);
		$settings[] = array(
			'id'          => 'dashboard_title_logged_in',
			'default'     => array(
				'default' => __('You have {{ credits }} {{ credits_currency }}', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Title (Logged in)', 'leat-crm'),
			/* translators: %s: a list of placeholders */
			'description' => sprintf(__('The title that will be displayed on the dashboard when the user is logged in. You can use the following placeholders: %s', 'leat-crm'), '{{ credits }} {{ credits_currency }}'),
		);
		$settings[] = array(
			'id'          => 'dashboard_title_logged_out',
			'default'     => array(
				'default' => __('Join our Loyalty Program and get rewarded when you shop with us. Get your first {{ credits }} {{ credits_currency }} when you sign up now', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Title (Logged out)', 'leat-crm'),
			/* translators: %s: a list of placeholders */
			'description' => sprintf(__('The title that will be displayed on the dashboard when the user is logged out. You can use the following placeholders: %s', 'leat-crm'), '{{ credits }} {{ credits_currency }}'),
		);
		$settings[] = array(
			'id'          => 'dashboard_join_cta',
			'default'     => array(
				'default' => __('Join now', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Join program button', 'leat-crm'),
			'description' => __('The text that will be displayed on the button that allows users to join the loyalty program.', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'dashboard_title_join_program',
			'default' => array(
				'default' => __('Join our Loyalty Program and get rewarded when you shop with us.', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Title (Join program)', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_join_program_cta',
			'default'     => array(
				'default' => __('Join program', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Join program button', 'leat-crm'),
			'description' => __('The text that will be displayed on the button that allows users to join the loyalty program.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_login_cta',
			'default'     => array(
				'default' => __('Log in', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Log in button', 'leat-crm'),
			'description' => __('The text that will be displayed on the button that allows users to log in.', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'dashboard_nav_coupons',
			'default' => array(
				'default' => __('Your coupons', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Coupons navigation item', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'dashboard_nav_coupons_empty_state',
			'default' => array(
				'default' => __('You don\'t have any coupons yet.', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Coupons empty state', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'dashboard_coupons_loading_state',
			'default' => array(
				'default' => __('Loading your coupons...', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Coupons loading state', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_nav_earn',
			'default'     => array(
				'default' => __('Earn {{ credits_currency }}', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Earn navigation item', 'leat-crm'),
			/* translators: %s: a list of placeholders */
			'description' => sprintf(__('The text that will be displayed on the navigation item that allows users to earn credits. You can use the following placeholders: %s', 'leat-crm'), '{{ credits_currency }}'),
		);
		$settings[] = array(
			'id'      => 'dashboard_nav_rewards',
			'default' => array(
				'default' => __('Rewards', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Rewards navigation item', 'leat-crm'),
		);
		$settings[] = array(
			'id'      => 'dashboard_nav_activity',
			'default' => array(
				'default' => __('Your activity', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Activity navigation item', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_earn_cta',
			'default'     => array(
				'default' => __('Claim reward', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Claim reward button', 'leat-crm'),
			'description' => __('The text that will be displayed on the button that allows users to claim their reward.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_spend_cta',
			'default'     => array(
				'default' => __('Unlock', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Spend credits button', 'leat-crm'),
			'description' => __('The text that will be displayed on the button that allows users to spend their credits.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'giftcard_order_status',
			'type'        => 'select',
			'label'       => __('Gift Card Order Status', 'leat-crm'),
			'description' => __('When should gift cards be created and sent to customers?', 'leat-crm'),
			'default'     => 'completed',
			'options'     => $this->woocommerce_order_statuses_options(),
		);
		$settings[] = array(
			'id'          => 'giftcard_withdraw_order_statuses',
			'type'        => 'checkboxes',
			'label'       => __('Gift Card Withdraw Order Statuses', 'leat-crm'),
			'description' => __('Select which order statuses will trigger a gift card withdrawal to the customer. Gift cards will be withdrawn only once, when the order first reaches any of the selected statuses. For example, if both "Refunded" and "Cancelled" are selected, gift cards will be returned to the customer when the order is either refunded or cancelled, whichever happens first.', 'leat-crm'),
			'default'     => array('refunded' => 'on'),
			'options'     => $this->woocommerce_order_statuses_options(),
		);

		/**
		 * Filter the default settings.
		 *
		 * @since 1.0.0
		 */
		return apply_filters('leat_default_settings', $settings, $this);
	}

	/**
	 * Taken directly from WooCommerce (This class has no access to the wc_get_order_statuses function.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/5c800958781a6e699d17f28932c296c51e074a14/plugins/woocommerce/includes/wc-order-functions.php#L100
	 */
	private function woocommerce_order_statuses_options()
	{
		$order_statuses = [
			'wc-pending'        => __('Pending payment', 'leat-crm'),
			'wc-processing'     => __('Processing', 'leat-crm'),
			'wc-on-hold'        => __('On hold', 'leat-crm'),
			'wc-completed'      => __('Completed', 'leat-crm'),
			'wc-cancelled'      => __('Cancelled', 'leat-crm'),
			'wc-refunded'       => __('Refunded', 'leat-crm'),
			'wc-failed'         => __('Failed', 'leat-crm'),
			'wc-checkout-draft' => __('Draft', 'leat-crm'),
		];

		$filtered_statuses = array_filter(
			$order_statuses,
			function ($status_key) {
				return strpos($status_key, 'wc-') === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		$formatted_statuses = array();

		foreach ($filtered_statuses as $status_key => $status_label) {
			$clean_key                        = str_replace('wc-', '', $status_key);
			$formatted_statuses[$clean_key] = array(
				'label' => $status_label,
			);
		}

		return $formatted_statuses;
	}

	/**
	 * The plugin's Settings page URL.
	 *
	 * @return string
	 */
	public function get_main_settings_page_url(): string
	{
		$url = 'options-general.php?page=' . $this->get_settings_page_slug();

		return admin_url($url);
	}

	/**
	 * The plugin's Settings page slug.
	 *
	 * @return string
	 */
	public function get_settings_page_slug(): string
	{
		return 'leat--settings';
	}

	/**
	 * Get all settings with their current values.
	 *
	 * @param bool $include_api_key Whether to include the API key in the settings.
	 * @return array
	 */
	public function get_all_settings_with_values($include_api_key = true)
	{
		$all_settings = $this->get_all_settings();

		if (! $include_api_key) {
			$all_settings = array_filter(
				$all_settings,
				function ($setting) {
					return 'api_key' !== $setting['id'];
				}
			);
		}

		return array_map([$this, 'get_setting_with_value'], $all_settings);
	}

	/**
	 * Get a specific setting with its current value.
	 *
	 * @param string $id The setting ID.
	 * @return array|null
	 */
	public function get_setting_by_id($id)
	{
		$all_settings = $this->get_all_settings();
		$setting      = current(
			array_filter(
				$all_settings,
				function ($setting) use ($id) {
					return $setting['id'] === $id;
				}
			)
		);

		return $setting ? $this->get_setting_with_value($setting) : null;
	}

	public function get_setting_value_by_id($id)
	{
		$setting = $this->get_setting_by_id($id);

		return $setting ? $setting['value'] : null;
	}

	/**
	 * Get a setting with its current value.
	 *
	 * @param array $setting The setting array.
	 * @return array
	 */
	private function get_setting_with_value($setting)
	{
		$default = isset($setting['default']) ? $setting['default'] : null;
		$id      = $setting['id'];

		$setting['value'] = get_option('leat_' . $id, $default);

		if ('translatable_text' === $setting['type'] && is_string($setting['value'])) {
			$setting['value'] = json_decode($setting['value'], true);
		}

		if ('checkboxes' === $setting['type'] && is_string($setting['value'])) {
			$setting['value'] = json_decode($setting['value'], true);
		}

		return $setting;
	}

	/**
	 * Get route response for all settings.
	 *
	 * @param bool $include_api_key Whether to include the API key in the response.
	 * @return array
	 */
	public function get_route_response($include_api_key = true)
	{
		return $this->get_all_settings_with_values($include_api_key);
	}

	/**
	 * Get item response for a specific setting.
	 *
	 * @param string $id The setting ID.
	 * @return array|null
	 */
	public function get_item_response($id)
	{
		return $this->get_setting_by_id($id);
	}

	/**
	 * Update multiple settings.
	 *
	 * @param array $settings An array of settings to update.
	 * @return bool
	 */
	public function update_settings($settings)
	{
		foreach ($settings as $setting) {
			$value = $setting['value'];

			if ('translatable_text' === $setting['type'] && is_array($value)) {
				$value = wp_json_encode($value);
			}

			if ('checkboxes' === $setting['type'] && is_array($value)) {
				$value = wp_json_encode($value);
			}

			update_option('leat_' . $setting['id'], $value);
		}

		return true;
	}
}
