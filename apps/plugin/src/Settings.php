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
			'id'          => 'api_key',
			'default'     => '',
			'type'        => 'text',
			'label'       => __('API Key', 'leat-crm'),
			'tooltip'     => __('Enter your API key here.', 'leat-crm'),
			'description' => sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
				__('You can generate an API key in your %1$sLeat Business Dashboard%2$s.', 'leat-crm'),
				'<a href="' . esc_url('https://business.leat.com/apps/integrations/personal-access-tokens') . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
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
			'label'       => __('Reward order status', 'leat-crm'),
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
			'id'          => 'dashboard_show_join_program_cta',
			'default'     => 'on',
			'type'        => 'switch',
			'label'       => __('Show join program CTA', 'leat-crm'),
			'description' => sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
				esc_html__('Show the join program CTA on the dashboard. You must enable customer registration in %1$sWooCommerce Account Settings%2$s to use this feature.', 'leat-crm'),
				'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=account')) . '">',
				'</a>'
			),
		);
		$settings[] = array(
			'id'          => 'dashboard_show_tiers',
			'default'     => 'on',
			'type'        => 'switch',
			'label'       => __('Show tiers', 'leat-crm'),
			'description' => __('Show the tiers on the dashboard.', 'leat-crm'),
		);
		$settings[] = array(
			'id'          => 'dashboard_myaccount_title',
			'default'     => array(
				'default' => __('Loyalty Program', 'leat-crm'),
			),
			'type'        => 'translatable_text',
			'label'       => __('Loyalty Program title', 'leat-crm'),
			'description' => __('The title for the WooCommerce My Account page that displays the loyalty program.', 'leat-crm'),
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
			'id'      => 'dashboard_nav_tiers',
			'default' => array(
				'default' => __('Tiers', 'leat-crm'),
			),
			'type'    => 'translatable_text',
			'label'   => __('Tiers navigation item', 'leat-crm'),
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
			'label'       => __('Cancel gift cards on these order statuses', 'leat-crm'),
			'description' => __('Select which order statuses will invalidate previously issued gift cards. When an order containing a gift card purchase changes to any of these statuses, the gift card will be canceled. This happens only once, when the order first reaches any selected status.', 'leat-crm'),
			'default'     => array('refunded' => 'on'),
			'options'     => $this->woocommerce_order_statuses_options(),
		);

		$settings[] = array(
			'id'          => 'giftcard_coupon_balance_update_order_statuses',
			'type'        => 'checkboxes',
			'label'       => __('Update gift card balance on these order statuses', 'leat-crm'),
			'description' => __('Select which order statuses will update a purchased gift card\'s balance in the system. For example, if both "Processing" and "Completed" are selected, the gift card balance will be updated when the order reaches either status. The system prevents duplicate processing.', 'leat-crm'),
			'default'     => array('processing' => 'on', 'completed' => 'on'),
			'options'     => $this->woocommerce_order_statuses_options(),
		);

		$settings[] = array(
			'id'          => 'giftcard_coupon_allow_acceptance',
			'type'        => 'switch',
			'label'       => __('Accept Gift Cards', 'leat-crm'),
			'description' => __('If enabled, customers will be able to redeem Leat gift cards.', 'leat-crm'),
			'default'     => 'on',
		);

		$settings[] = array(
			'id'          => 'giftcard_disable_recipient_email',
			'type'        => 'switch',
			'label'       => __('Disable Gift Card Recipient Email', 'leat-crm'),
			'description' => __('If enabled, customers will not be asked for a recipient email during checkout when purchasing gift cards. The gift card will be sent to the customer\'s own email address.', 'leat-crm'),
			'default'     => 'off',
		);

		$settings[] = array(
			'id'          => 'giftcard_checking_balance_text',
			'type'        => 'translatable_text',
			'label'       => __('Checking Balance Text', 'leat-crm'),
			'description' => __('The text shown while checking a gift card balance.', 'leat-crm'),
			'default'     => array(
				'default' => __('Checking gift card balance...', 'leat-crm'),
			),
		);

		$settings[] = array(
			'id'          => 'giftcard_applied_success_message',
			'type'        => 'translatable_text',
			'label'       => __('Gift Card Applied Success Message', 'leat-crm'),
			/* translators: %s: a list of placeholders */
			'description' => sprintf(__('The success message shown when a gift card is applied. Use placeholders: %s', 'leat-crm'), '<code>{{ code }}</code>, <code>{{ balance }}</code>'),
			'default'     => array(
				/* translators: %1$s: gift card code, %2$s: gift card balance */
				'default' => __('Gift card {{ code }} applied. Balance: {{ balance }}', 'leat-crm'),
			),
		);

		$settings[] = array(
			'id'          => 'only_reward_known_contacts',
			'type'        => 'switch',
			'label'       => __('Only Reward Known Contacts', 'leat-crm'),
			'description' => __('If enabled, credits will only be attributed to contacts that already exist in Leat. New contacts will not be created automatically.', 'leat-crm'),
			'default'     => 'off',
		);

		// Prepaid Settings
		$settings[] = array(
			'id'          => 'prepaid_order_status',
			'type'        => 'select',
			'label'       => __('Prepaid Top-up Order Status', 'leat-crm'),
			'description' => __('Select the order status that triggers adding value to the customer\'s prepaid balance.', 'leat-crm'),
			'default'     => 'completed',
			'options'     => $this->woocommerce_order_statuses_options(),
		);
		$settings[] = array(
			'id'          => 'prepaid_withdraw_order_statuses',
			'type'        => 'checkboxes',
			'label'       => __('Reverse Prepaid Top-up Statuses', 'leat-crm'),
			'description' => __('Select order statuses that should reverse a previously completed prepaid top-up transaction.', 'leat-crm'),
			'default'     => array('refunded' => 'on', 'cancelled' => 'on'), // Default to refunded and cancelled
			'options'     => $this->woocommerce_order_statuses_options(),
		);

		/**
		 * Filter the default settings.
		 *

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
		$order_statuses = wc_get_order_statuses();
		$formatted_statuses = array();

		foreach ($order_statuses as $status_key => $status_label) {
			$clean_key = str_replace('wc-', '', $status_key);
			$formatted_statuses[$clean_key] = array(
				'label' => $status_label,
			);
		}

		/**
		 * Filter the available order statuses for the plugin settings
		 *
		 * @param array $formatted_statuses Array of order statuses
		 * @return array
		 */
		return apply_filters('leat_order_statuses_options', $formatted_statuses);
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
					// If the $id somehow includes the leat_ prefix, remove it.

					$setting_id = str_replace('leat_', '', $setting['id']);

					return $setting_id === $id;
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

		do_action('leat_settings_updated');

		return true;
	}
}
