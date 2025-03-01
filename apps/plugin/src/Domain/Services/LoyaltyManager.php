<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\Customer\{
	CustomerAttributeSync,
	CustomerCreationHandler,
	CustomerProfileDisplay
};
use Leat\Domain\Services\Order\{
	OrderProcessor,
	OrderCreditHandler
};
use Leat\Domain\Services\Cart\CartManager;
use Leat\Domain\Services\EarnRules;
use Leat\Domain\Services\SpendRulesService;
use Leat\Settings;
use Leat\Utils\Logger;

/**
 * Class LoyaltyManager
 */
class LoyaltyManager
{

	/**
	 * Connection instance.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Earn Rules instance.
	 *
	 * @var EarnRules
	 */
	private $earn_rules;

	/**
	 * Spend Rules instance.
	 *
	 * @var SpendRules
	 */
	private $spend_rules_service;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * CustomerAttributeSync instance.
	 *
	 * @var CustomerAttributeSync
	 */
	private $attribute_sync;

	/**
	 * CustomerCreationHandler instance.
	 *
	 * @var CustomerCreationHandler
	 */
	private $customer_creation;

	/**
	 * CustomerProfileDisplay instance.
	 *
	 * @var CustomerProfileDisplay
	 */
	private $profile_display;

	/**
	 * OrderProcessor instance.
	 *
	 * @var OrderProcessor
	 */
	private $order_processor;

	/**
	 * OrderCreditHandler instance.
	 *
	 * @var OrderCreditHandler
	 */
	private $order_credit_handler;

	/**
	 * CartManager instance.
	 *
	 * @var CartManager
	 */
	private $cart_manager;

	/**
	 * LoyaltyManager constructor.
	 *
	 * @param Connection $connection
	 * @param EarnRules $earn_rules
	 * @param SpendRulesService $spend_rules_service
	 * @param Settings $settings
	 * @param CustomerAttributeSync $attribute_sync
	 * @param CustomerCreationHandler $customer_creation
	 * @param CustomerProfileDisplay $profile_display
	 * @param OrderProcessor $order_processor
	 * @param OrderCreditHandler $order_credit_handler
	 * @param CartManager $cart_manager
	 */
	public function __construct(
		Logger $logger,
		Connection $connection,
		EarnRules $earn_rules,
		SpendRulesService $spend_rules_service,
		Settings $settings,
		CustomerAttributeSync $attribute_sync,
		CustomerCreationHandler $customer_creation,
		CustomerProfileDisplay $profile_display,
		OrderProcessor $order_processor,
		OrderCreditHandler $order_credit_handler,
		CartManager $cart_manager
	) {
		$this->logger = $logger;
		$this->connection = $connection;
		$this->earn_rules = $earn_rules;
		$this->spend_rules_service = $spend_rules_service;
		$this->settings = $settings;
		$this->attribute_sync = $attribute_sync;
		$this->customer_creation = $customer_creation;
		$this->profile_display = $profile_display;
		$this->order_processor = $order_processor;
		$this->order_credit_handler = $order_credit_handler;
		$this->cart_manager = $cart_manager;

		$this->register_hooks();
	}

	private function register_hooks(): void
	{
		add_action('woocommerce_created_customer', [$this->customer_creation, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this->profile_display, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this->profile_display, 'show_uuid_on_profile']);
		add_action('show_user_profile', [$this->profile_display, 'show_claimed_rewards_on_profile']);
		add_action('edit_user_profile', [$this->profile_display, 'show_claimed_rewards_on_profile']);
		add_action('wp_login', [$this->attribute_sync, 'sync_attributes_on_login'], 10, 2);
		add_action('wp_logout', [$this->attribute_sync, 'sync_attributes_on_logout']);

		add_action('woocommerce_applied_coupon', [$this->cart_manager, 'handle_applied_coupon'], 10, 1);
		add_action('woocommerce_removed_coupon', [$this->cart_manager, 'handle_removed_coupon'], 10, 1);
		add_action('woocommerce_before_calculate_totals', [$this->cart_manager, 'adjust_cart_item_prices'], 10, 1);
		add_filter('woocommerce_product_get_sale_price', [$this->cart_manager, 'remove_sale_price_for_discounted_products'], 10, 2);
		add_filter('woocommerce_product_get_price', [$this->cart_manager, 'adjust_price_for_discounted_products'], 10, 2);

		$reward_status = $this->settings->get_setting_value_by_id('reward_order_statuses');
		add_action('woocommerce_order_status_' . $reward_status, [$this->order_processor, 'sync_attributes_on_order_completed'], 10, 1);

		add_action('woocommerce_checkout_order_processed', [$this->order_processor, 'handle_checkout_order_processed'], 10, 1);
		add_action('woocommerce_rest_checkout_process_payment_with_context', [$this->order_processor, 'handle_blocks_checkout_order_processed'], 10, 2);

		$withdraw_statuses = $this->settings->get_setting_value_by_id('withdraw_order_statuses') ?? ['refunded' => 'on'];
		foreach ($withdraw_statuses as $status => $enabled) {
			if ('on' === $enabled) {
				if ('refunded' === $status) {
					add_action('woocommerce_order_refunded', [$this->order_credit_handler, 'handle_order_credit_withdrawal_refund'], 10, 2);
				} else {
					add_action('woocommerce_order_status_' . $status, [$this->order_credit_handler, 'handle_order_credit_withdrawal'], 10, 1);
				}
			}
		}
	}
}
