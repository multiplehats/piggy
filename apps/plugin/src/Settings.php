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
			'default'  => null,
			'type'     => 'translatable_text',
			'label'    => __( 'Credits name', 'piggy' ),
			'description' => __( 'The name of the credits in your shop.', 'piggy' ),
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
			'description' => __( 'Select the Piggy subscription that will be used for marketing consent.', 'piggy' ),
			'default'  => 'functional',
			'options'  => array(
				'functional' => array('label' => __( 'Functional email', 'piggy' ), 'tooltip' => __( 'Functional emails are emails that are necessary for the functioning of the service. These include emails for password resets, order confirmations, and account creation.', 'piggy' )),
				'marketing' => array('label' => __( 'Marketing email', 'piggy' ), 'tooltip' => __( 'Marketing emails are emails that are used for marketing purposes. These include newsletters, promotions, and other marketing emails.', 'piggy' )),
			),
		);

		// Customer Dashboard related settings.
		$settings[] = array(
			'id'       => 'dashboard_title_logged_in',
			'default'  => array(
				'en_US' => __( 'You have {{ credits }} {{ credits__currency }}', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Title (Logged in)', 'piggy' ),
			/* translators: %s: a list of placeholders */
			'description' => sprintf( __( 'The title that will be displayed on the dashboard when the user is logged in. You can use the following placeholders: %s', 'piggy' ), '{{ credits }} {{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_title_logged_out',
			'default'  => array(
				'en_US' => __( 'Join our Loyalty Program and get rewarded when you shop with us. Get your first {{ credits }} {{ credits_currency }} when you sign up now', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Title (Logged out)', 'piggy' ),
			/* translators: %s: a list of placeholders */
			'description' => sprintf( __( 'The title that will be displayed on the dashboard when the user is logged out. You can use the following placeholders: %s', 'piggy' ), '{{ credits }} {{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_join_cta',
			'default'  => array(
				'en_US' => __( 'Join now', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Join program button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to join the loyalty program.', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_login_cta',
			'default'  => array(
				'en_US' => __( 'Log in', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Log in button', 'piggy' ),
			'description' => __( 'The text that will be displayed on the button that allows users to log in.', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_coupons',
			'default'  => array(
				'en_US' => __( 'Your coupons', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Coupons navigation item', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_earn',
			'default'  => array(
				'en_US' => __( 'Earn {{ credits__currency }}', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Earn navigation item', 'piggy' ),
			'description' => sprintf( __( 'The text that will be displayed on the navigation item that allows users to earn credits. You can use the following placeholders: %s', 'piggy' ), '{{ credits_currency }}' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_rewards',
			'default'  => array(
				'en_US' => __( 'Rewards', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Rewards navigation item', 'piggy' ),
		);

		$settings[] = array(
			'id'       => 'dashboard_nav_activity',
			'default'  => array(
				'en_US' => __( 'Your activity', 'piggy' ),
			),
			'type'     => 'translatable_text',
			'label'    => __( 'Activity navigation item', 'piggy' ),
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
}
