<?php

namespace Leat\Api;

use Leat\Registry\Container;
use Leat\Api\Formatters;
use Leat\Api\RoutesController;
use Leat\Api\SchemaController;
use Leat\Api\Schemas\ExtendSchema;
use Leat\Domain\Syncing\SyncPromotions;
use Leat\Domain\Syncing\SyncVouchers;
use Leat\Domain\Services\PromotionRulesService;
use Leat\Domain\Services\SpendRulesService;
use Leat\Domain\Services\WebhookManager;
use Leat\Domain\Syncing\SyncRewards;
use Leat\Settings;
use Leat\Utils\Logger;

/**
 * Api Main Class.
 */
final class Api
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var PromotionRulesService
	 */
	private $promotion_rules_service;

	/**
	 * @var SpendRulesService
	 */
	private $spend_rules_service;

	/**
	 * @var SyncVouchers
	 */
	private $sync_vouchers;

	/**
	 * @var SyncPromotions
	 */
	private $sync_promotions;

	/**
	 * @var SyncRewards
	 */
	private $sync_rewards;

	/**
	 * @var WebhookManager
	 */
	private $webhook_manager;

	/**
	 * Constructor.
	 *
	 * @param Connection     $connection      The Connection instance.
	 * @param Settings      $settings       The Settings instance.
	 * @param PromotionRulesService $promotion_rules_service The PromotionRulesService instance.
	 * @param SpendRulesService $spend_rules_service The SpendRulesService instance.
	 * @param SpendRules $spend_rules The SpendRules instance.
	 * @param SyncVouchers   $sync_vouchers   The SyncVouchers instance.
	 * @param SyncPromotions $sync_promotions The SyncPromotions instance.
	 * @param SyncRewards $sync_rewards The SyncRewards instance.
	 * @param WebhookManager $webhook_manager The WebhookManager instance.
	 */
	public function __construct(
		Connection $connection,
		Settings $settings,
		PromotionRulesService $promotion_rules_service,
		SpendRulesService $spend_rules_service,
		SyncVouchers $sync_vouchers,
		SyncPromotions $sync_promotions,
		SyncRewards $sync_rewards,
		WebhookManager $webhook_manager
	) {
		$this->connection = $connection;
		$this->settings = $settings;
		$this->promotion_rules_service = $promotion_rules_service;
		$this->spend_rules_service = $spend_rules_service;
		$this->sync_vouchers = $sync_vouchers;
		$this->sync_promotions = $sync_promotions;
		$this->sync_rewards = $sync_rewards;
		$this->webhook_manager = $webhook_manager;
	}

	/**
	 * Init and hook in Leat API functionality.
	 */
	public function init()
	{
		add_action(
			'rest_api_init',
			function () {
				self::container(
					$this->connection,
					$this->settings,
					$this->promotion_rules_service,
					$this->spend_rules_service,
					$this->sync_vouchers,
					$this->sync_promotions,
					$this->sync_rewards,
					$this->webhook_manager
				)
					->get(RoutesController::class)
					->register_all_routes();
			}
		);
	}

	/**
	 * Loads the DI container for Leat API.
	 *
	 * @param Connection     $connection      The Connection instance.
	 * @param Settings      $settings       The Settings instance.
	 * @param PromotionRulesService $promotion_rules_service The PromotionRulesService instance.
	 * @param SpendRulesService $spend_rules_service The SpendRulesService instance.
	 * @param SyncVouchers   $sync_vouchers   The SyncVouchers instance.
	 * @param SyncPromotions $sync_promotions The SyncPromotions instance.
	 * @param SyncRewards $sync_rewards The SyncRewards instance.
	 * @param WebhookManager $webhook_manager The WebhookManager instance.
	 * @param boolean       $reset          Used to reset the container to a fresh instance.
	 * @return Container
	 */
	public static function container(
		Connection $connection = null,
		Settings $settings = null,
		PromotionRulesService $promotion_rules_service = null,
		SpendRulesService $spend_rules_service = null,
		SyncVouchers $sync_vouchers = null,
		SyncPromotions $sync_promotions = null,
		SyncRewards $sync_rewards = null,
		WebhookManager $webhook_manager = null,
		$reset = false
	) {
		static $container;

		if ($reset) {
			$container = null;
		}

		if ($container) {
			return $container;
		}

		$container = new Container();

		$container->register(Logger::class, function () {
			return new Logger('api');
		});

		// Register existing instances if provided
		if ($settings) {
			$container->register(Settings::class, function () use ($settings) {
				return $settings;
			});
		}

		if ($connection) {
			$container->register(Connection::class, function () use ($connection) {
				return $connection;
			});
		}

		if ($webhook_manager) {
			$container->register(WebhookManager::class, function () use ($webhook_manager) {
				return $webhook_manager;
			});
		}

		if ($sync_vouchers) {
			$container->register(SyncVouchers::class, function () use ($sync_vouchers) {
				return $sync_vouchers;
			});
		}

		if ($sync_promotions) {
			$container->register(SyncPromotions::class, function () use ($sync_promotions) {
				return $sync_promotions;
			});
		}

		if ($sync_rewards) {
			$container->register(SyncRewards::class, function () use ($sync_rewards) {
				return $sync_rewards;
			});
		}

		if ($promotion_rules_service) {
			$container->register(PromotionRulesService::class, function () use ($promotion_rules_service) {
				return $promotion_rules_service;
			});
		}

		if ($spend_rules_service) {
			$container->register(SpendRulesService::class, function () use ($spend_rules_service) {
				return $spend_rules_service;
			});
		}

		// Register remaining dependencies
		$container->register(
			RoutesController::class,
			function ($container) {
				return new RoutesController(
					$container->get(SchemaController::class),
					$container->get(Logger::class),
					$container->get(Connection::class),
					$container->get(Settings::class),
					$container->get(SyncVouchers::class),
					$container->get(SyncPromotions::class),
					$container->get(SyncRewards::class),
					$container->get(WebhookManager::class),
					$container->get(PromotionRulesService::class),
					$container->get(SpendRulesService::class)
				);
			}
		);

		$container->register(
			SchemaController::class,
			function ($container) {
				return new SchemaController(
					$container->get(ExtendSchema::class),
					$container->get(Logger::class),
					$container->get(Settings::class),
					$container->get(PromotionRulesService::class),
					$container->get(SpendRulesService::class)
				);
			}
		);

		$container->register(
			ExtendSchema::class,
			function ($container) {
				return new ExtendSchema(
					$container->get(Formatters::class)
				);
			}
		);

		$container->register(
			Formatters::class,
			function () {
				return new Formatters();
			}
		);

		return $container;
	}
}
