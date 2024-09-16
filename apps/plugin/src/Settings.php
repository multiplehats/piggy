<?php

namespace PiggyWP;

/**
 * Contains all the default options and options from the database.
 */
class Settings {
	/**
	 * Default settings.
	 */
	public function get_all_settings() {
		$settings = [];

		$settings[] = array(
			'title'    => __( 'Quick actions', 'piggy' ),
			'id'       => 'plugin_enable',
			'default'  => 'on',
			'type'     => 'switch',
			'label'    => __( 'Enable plugin', 'piggy' ),
			'tooltip'  => __( 'If you disable this, the plugin will stop working on the front-end of your website. This is useful if you temporarily want to disable Piggy without deactivating the entire plugin.', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'plugin_reset',
			'default'  => 'off',
			'type'     => 'switch',
			'label'    => __( 'Delete plugin settings upon deactivation', 'piggy' ),
			'tooltip'  => __( 'This will delete all plugins settings upon deactivation. Use with caution!', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'api_key',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'API Key', 'piggy' ),
			'tooltip'  => __( 'Enter your API key here.', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'shop_uuid',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'Shop ID', 'piggy' ),
			'tooltip'  => __( 'Select the shop you want to connect to.', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'credits_name',
			'default'  => array(
				'default' => __( 'Credits', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Credits name', 'piggy' ),
			'description' => __( 'The name of the credits in your shop.', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'credits_spend_rule_progress',
			'default'  => array(
				'default' => __( 'You have {{ credits }} {{ credits_currency }} out of {{ credits_required }}', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Credits balance', 'piggy' ),
			'description' =>
			sprintf( __( 'The format of the credits balance message that will be displayed to the user. The following placeholders can be used: %s', 'piggy' ), '{{ credits }}, {{ credits_currency }} and {{ credits_required }}' ),
		);
		$settings[] = array(
			'id'       => 'include_guests',
			'default'  => 'off',
			'type'     => 'switch',
			'label'    => __( 'Include guests', 'piggy' ),
			'description' => __( 'Include customers without an account (guests) in your loyalty program.', 'piggy' ),
		);
		$settings[] = array(
			'id'       => 'reward_order_statuses',
			'type'     => 'checkboxes',
			'label'    => __( 'Reward order statuses', 'piggy' ),
			'description' => __( 'Reward customers when the financial status of the order is one of the following', 'piggy' ),
			'default'  => array('paid' => 'on'),
			'options'  => array(
				'paid'      => array('label' => __( 'Pending payment', 'piggy' ), 'tooltip' => __( 'The order has been received, but no payment has been made. Pending payment orders are generally awaiting customer action.', 'piggy' )),
				'pending'   => array('label' => __( 'On hold', 'cartpops' ), 'tooltip' => __( 'The order is awaiting payment confirmation. Stock is reduced, but you need to confirm payment.', 'cartpops' )),
				'processing'=> array('label' => __( 'Processing', 'cartpops' ), 'tooltip' => __( 'Payment has been received (paid), and the stock has been reduced. The order is awaiting fulfillment.', 'cartpops' )),
				'completed' => array('label' => __( 'Completed', 'cartpops' ), 'tooltip' => __( 'Order fulfilled and complete.', 'cartpops' )),
			),
		);
		$settings[] = array(
			'id'       => 'withdraw_order_statuses',
			'type'     => 'checkboxes',
			'label'    => __( 'Withdraw credits order statuses', 'piggy' ),
			'description' => __( 'Withdraw credits from customers when the order financial status is one of the following', 'piggy'),
			'default'  => array('paid' => 'on'),
			'options'  => array(
				'paid'      => array('label' => __( 'Pending payment', 'piggy' ), 'tooltip' => __( 'The order has been received, but no payment has been made. Pending payment orders are generally awaiting customer action.', 'piggy' )),
				'pending'   => array('label' => __( 'On hold', 'cartpops' ), 'tooltip' => __( 'The order is awaiting payment confirmation. Stock is reduced, but you need to confirm payment.', 'cartpops' )),
				'processing'=> array('label' => __( 'Processing', 'cartpops' ), 'tooltip' => __( 'Payment has been received (paid), and the stock has been reduced. The order is awaiting fulfillment.', 'cartpops' )),
				'completed' => array('label' => __( 'Completed', 'cartpops' ), 'tooltip' => __( 'Order fulfilled and complete.', 'cartpops' )),
			),
		);
		$settings[] = array(
			'id'       => 'reward_order_parts',
			'type'     => 'checkboxes',
			'label'    => __( 'Reward order parts', 'piggy' ),
			'description' => __( 'Reward customers for the following parts of an order', 'piggy' ),
			'default'  => array('subtotal' => 'on'),
			'options'  => array(
				'subtotal' => array('label' => __( 'Subtotal', 'piggy' ), 'tooltip' => __( 'The total amount of the order before taxes and shipping.', 'piggy' )),
				'shipping' => array('label' => __( 'Shipping', 'piggy' ), 'tooltip' => __( 'The cost of shipping the order.', 'piggy' )),
				'tax'      => array('label' => __( 'Tax', 'piggy' ), 'tooltip' => __( 'The amount of tax on the order.', 'piggy' )),
				'discount' => array('label' => __( 'Discount', 'piggy' ), 'tooltip' => __( 'The amount of discount on the order.', 'piggy' )),
			),
		);

		$settings[] = array(
			'id'       => 'marketing_consent_subscription',
			'type'     => 'select',
			'label'    => __( 'Marketing consent subscription', 'piggy' ),
			'description' => __( 'Opt-in users to receive marketing emails.', 'piggy' ),
			'default'  => 'off'
		);


		$settings[] = array(
			'id'       => 'dashboard_title_logged_in',
			'default'  => array(
				'default' => __( 'You have {{ credits }} {{ credits_currency }}', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Title (Logged in)', 'piggy' ),
			/* translators: %s: a list of placeholders */
			'description' => sprintf( __( 'The title that will be displayed on the dashboard when the user is logged in. You can use the following placeholders: %s', 'piggy' ), '{{ credits }} {{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_title_logged_out',
			'default'  => array(
				'default' => __( 'Join our Loyalty Program and get rewarded when you shop with us. Get your first {{ credits }} {{ credits_currency }} when you sign up now', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Title (Logged out)', 'piggy' ),
			/* translators: %s: a list of placeholders */
			'description' => sprintf( __( 'The title that will be displayed on the dashboard when the user is logged out. You can use the following placeholders: %s', 'piggy' ), '{{ credits }} {{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_join_cta',
			'default'  => array(
				'default' => __( 'Join now', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Join program button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to join the loyalty program.', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_login_cta',
			'default'  => array(
				'default' => __( 'Log in', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Log in button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to log in.', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_coupons',
			'default'  => array(
				'default' => __( 'Your coupons', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Coupons navigation item', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_earn',
			'default'  => array(
				'default' => __( 'Earn {{ credits_currency }}', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Earn navigation item', 'piggy' ),
			'description' => sprintf( __( 'The text that will be displayed on the navigation item that allows users to earn credits. You can use the following placeholders: %s', 'piggy' ), '{{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_rewards',
			'default'  => array(
				'default' => __( 'Rewards', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Rewards navigation item', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_activity',
			'default'  => array(
				'default' => __( 'Your activity', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Activity navigation item', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_earn_cta',
			'default'  => array(
				'default' => __( 'Claim reward', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Claim reward button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to claim their reward.', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_spend_cta',
			'default'  => array(
				'default' => __( 'Unlock', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Spend credits button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to spend their credits.', 'piggy' ),
		);

		/**
		 * Filter the default settings.
		 *
		 * @since 1.0.0
		 */
		return apply_filters('piggy_default_settings', $settings, $this);
	}

	/**
	 * The plugin's Settings page URL.
	 *
	 * @return string
	 */
	public function get_main_settings_page_url(): string {
		$url = 'options-general.php?page=' . $this->get_settings_page_slug();

		return admin_url( $url );
	}

	/**
	 * The plugin's Settings page slug.
	 *
	 * @return string
	 */
	public function get_settings_page_slug(): string {
		return 'piggy--settings';
	}

	/**
	 * Get all settings with their current values.
	 *
	 * @param bool $include_api_key Whether to include the API key in the settings.
	 * @return array
	 */
	public function get_all_settings_with_values($include_api_key = true) {
		$all_settings = $this->get_all_settings();

		if (!$include_api_key) {
			$all_settings = array_filter($all_settings, function($setting) {
				return $setting['id'] !== 'api_key';
			});
		}

		return array_map([$this, 'get_setting_with_value'], $all_settings);
	}

	/**
	 * Get a specific setting with its current value.
	 *
	 * @param string $id The setting ID.
	 * @return array|null
	 */
	public function get_setting_by_id($id) {
		$all_settings = $this->get_all_settings();
		$setting = current(array_filter($all_settings, function($setting) use ($id) {
			return $setting['id'] === $id;
		}));

		return $setting ? $this->get_setting_with_value($setting) : null;
	}

	/**
	 * Get a setting with its current value.
	 *
	 * @param array $setting The setting array.
	 * @return array
	 */
	private function get_setting_with_value($setting) {
		$default = isset($setting['default']) ? $setting['default'] : null;
		$id = $setting['id'];

		$setting['value'] = get_option('piggy_' . $id, $default);

		if ($setting['type'] === 'translatable_text' && is_string($setting['value'])) {
			$setting['value'] = json_decode($setting['value'], true);
		}

		if ($setting['type'] === 'checkboxes' && is_string($setting['value'])) {
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
	public function get_route_response($include_api_key = true) {
		return $this->get_all_settings_with_values($include_api_key);
	}

	/**
	 * Get item response for a specific setting.
	 *
	 * @param string $id The setting ID.
	 * @return array|null
	 */
	public function get_item_response($id) {
		return $this->get_setting_by_id($id);
	}

	/**
	 * Update multiple settings.
	 *
	 * @param array $settings An array of settings to update.
	 * @return bool
	 */
	public function update_settings($settings) {
		foreach ($settings as $setting) {
			$value = $setting['value'];

			if ($setting['type'] === 'translatable_text' && is_array($value)) {
				$value = json_encode($value);
			}

			if ($setting['type'] === 'checkboxes' && is_array($value)) {
				$value = json_encode($value);
			}

			update_option('piggy_' . $setting['id'], $value);
		}

		return true;
	}
}
