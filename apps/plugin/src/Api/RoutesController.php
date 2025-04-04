<?php

namespace Leat\Api;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRulesService;
use Leat\Domain\Services\SpendRulesService;
use Leat\Domain\Syncing\SyncPromotions;
use Leat\WebhookManager;
use Leat\Domain\Syncing\SyncRewards;
use Leat\Domain\Syncing\SyncVouchers;
use Leat\Domain\Services\TierService;
use Leat\Infrastructure\Repositories\WPGiftcardCouponRepository;
use Leat\Settings;
use Leat\Utils\Logger;

/**
 * RoutesController class.
 */
class RoutesController
{
	/**
	 * Leat schema_controller.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Leat connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Sync vouchers.
	 *
	 * @var SyncVouchers
	 */
	protected $sync_vouchers;

	/**
	 * Sync promotions.
	 *
	 * @var SyncPromotions
	 */
	protected $sync_promotions;

	/**
	 * Sync rewards.
	 *
	 * @var SyncRewards
	 */
	protected $sync_rewards;

	/**
	 * Webhook manager.
	 *
	 * @var WebhookManager
	 */
	protected $webhook_manager;

	/**
	 * Promotion rules.
	 *
	 * @var PromotionRulesService
	 */
	protected $promotion_rules_service;

	/**
	 * Spend rules.
	 *
	 * @var SpendRulesService
	 */
	protected $spend_rules_service;

	/**
	 * WPGiftcardCouponRepository.
	 *
	 * @var WPGiftcardCouponRepository
	 */
	protected $wp_giftcard_coupon_repository;

	/**
	 * Tier service.
	 *
	 * @var TierService
	 */
	protected $tier_service;

	/**
	 * Leat routes.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema controller class passed to each route.
	 */
	public function __construct(
		SchemaController $schema_controller,
		Logger $logger,
		Connection $connection,
		Settings $settings,
		SyncVouchers $sync_vouchers,
		SyncPromotions $sync_promotions,
		SyncRewards $sync_rewards,
		WebhookManager $webhook_manager,
		PromotionRulesService $promotion_rules_service,
		SpendRulesService $spend_rules_service,
		WPGiftcardCouponRepository $wp_giftcard_coupon_repository,
		TierService $tier_service
	) {
		$this->schema_controller = $schema_controller;
		$this->logger            = $logger;
		$this->connection        = $connection;
		$this->settings          = $settings;
		$this->sync_vouchers     = $sync_vouchers;
		$this->sync_promotions   = $sync_promotions;
		$this->sync_rewards      = $sync_rewards;
		$this->webhook_manager   = $webhook_manager;
		$this->promotion_rules_service   = $promotion_rules_service;
		$this->spend_rules_service   = $spend_rules_service;
		$this->wp_giftcard_coupon_repository   = $wp_giftcard_coupon_repository;
		$this->tier_service   = $tier_service;

		$this->routes = [
			'v1'      => [
				Routes\V1\EarnReward::IDENTIFIER         => Routes\V1\EarnReward::class,
				Routes\V1\EarnRules::IDENTIFIER          => Routes\V1\EarnRules::class,
				Routes\V1\SpendRules::IDENTIFIER         => Routes\V1\SpendRules::class,
				Routes\V1\PromotionRules::IDENTIFIER     => Routes\V1\PromotionRules::class,
				Routes\V1\SpendRulesClaim::IDENTIFIER    => Routes\V1\SpendRulesClaim::class,
				Routes\V1\Coupons::IDENTIFIER            => Routes\V1\Coupons::class,
				Routes\V1\JoinProgram::IDENTIFIER        => Routes\V1\JoinProgram::class,
				Routes\V1\Contact::IDENTIFIER            => Routes\V1\Contact::class,
				Routes\V1\Tiers::IDENTIFIER             => Routes\V1\Tiers::class,
				Routes\V1\WCCategoriesSearch::IDENTIFIER => Routes\V1\WCCategoriesSearch::class,
				Routes\V1\Giftcards::IDENTIFIER         => Routes\V1\Giftcards::class,
			],
			'private' => [
				Routes\V1\Webhooks::IDENTIFIER => Routes\V1\Webhooks::class,
				Routes\V1\SyncPromotions::IDENTIFIER => Routes\V1\SyncPromotions::class,
				Routes\V1\SyncRewards::IDENTIFIER     => Routes\V1\SyncRewards::class,
				Routes\V1\SyncVouchers::IDENTIFIER       => Routes\V1\SyncVouchers::class,
				Routes\V1\Admin\Settings::IDENTIFIER     => Routes\V1\Admin\Settings::class,
				Routes\V1\Admin\Shops::IDENTIFIER        => Routes\V1\Admin\Shops::class,
				Routes\V1\Admin\SpendRules::IDENTIFIER      => Routes\V1\Admin\SpendRules::class,
				Routes\V1\WCProductsSearch::IDENTIFIER   => Routes\V1\WCProductsSearch::class,
				Routes\V1\WCCategoriesSearch::IDENTIFIER => Routes\V1\WCCategoriesSearch::class,
				Routes\V1\SyncWebhooks::IDENTIFIER => Routes\V1\SyncWebhooks::class,
			],
		];
	}

	/**
	 * Register all Leat API routes. This includes routes under specific version namespaces.
	 */
	public function register_all_routes()
	{
		$this->register_routes('v1', 'leat/v1');
		$this->register_routes('private', 'leat/private');
	}

	/**
	 * Get a route class instance.
	 *
	 * Each route class is instantized with the SchemaController instance, and its main Schema Type.
	 *
	 * @throws \Exception If the schema does not exist.
	 * @param string $name Name of schema.
	 * @param string $version API Version being requested.
	 * @return AbstractRoute
	 */
	public function get($name, $version = 'v1')
	{
		$route = $this->routes[$version][$name] ?? false;

		if (! $route) {
			throw new \Exception(esc_html(sprintf('%s %s route does not exist', $name, $version)));
		}

		return new $route(
			$this->schema_controller,
			$this->logger,
			$this->schema_controller->get($route::SCHEMA_TYPE, $route::SCHEMA_VERSION),
			$this->connection,
			$this->settings,
			$this->sync_vouchers,
			$this->sync_promotions,
			$this->sync_rewards,
			$this->webhook_manager,
			$this->promotion_rules_service,
			$this->spend_rules_service,
			$this->wp_giftcard_coupon_repository,
			$this->tier_service
		);
	}

	/**
	 * Register defined list of routes with WordPress.
	 *
	 * @param string $version API Version being registered..
	 * @param string $namespace Overrides the default route namespace.
	 * @throws \Exception If the route does not exist.
	 */
	protected function register_routes($version = 'v1', $namespace = 'leat/v1')
	{
		if (! isset($this->routes[$version])) {
			return;
		}
		$route_identifiers = array_keys($this->routes[$version]);
		foreach ($route_identifiers as $route) {
			$route_instance = $this->get($route, $version);
			$route_instance->set_namespace($namespace);

			$args = $route_instance->get_args();

			foreach ($args as $key => $arg) {
				if (in_array($key, ['schema', 'allow_batch'], true)) {
					continue;
				}

				if (! isset($arg['permission_callback'])) {
					throw new \Exception(
						sprintf(
							'Route %s must implement a permission_callback. Use "__return_true" for intentionally public endpoints.',
							$route
						)
					);
				}
			}

			register_rest_route(
				$route_instance->get_namespace(),
				$route_instance->get_path(),
				$args
			);
		}
	}
}
