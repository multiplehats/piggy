<?php

namespace Leat\Api;

use Leat\Registry\Container;
use Leat\Api\Formatters;
use Leat\Api\RoutesController;
use Leat\Api\SchemaController;
use Leat\Api\Schemas\ExtendSchema;
use Leat\Domain\Services\SyncPromotions;
use Leat\Domain\Services\SyncVouchers;
use Leat\Domain\Services\PromotionRules;
use Leat\Domain\Services\WebhookManager;
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
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var PromotionRules
	 */
	private $promotion_rules;

	/**
	 * @var SyncVouchers
	 */
	private $sync_vouchers;

	/**
	 * @var SyncPromotions
	 */
	private $sync_promotions;

	/**
	 * @var WebhookManager
	 */
	private $webhook_manager;

	/**
	 * Constructor.
	 *
	 * @param Connection     $connection      The Connection instance.
	 * @param Settings      $settings       The Settings instance.
	 * @param PromotionRules $promotion_rules The PromotionRules instance.
	 * @param SyncVouchers   $sync_vouchers   The SyncVouchers instance.
	 * @param SyncPromotions $sync_promotions The SyncPromotions instance.
	 * @param WebhookManager $webhook_manager The WebhookManager instance.
	 */
	public function __construct(
		Connection $connection,
		Settings $settings,
		PromotionRules $promotion_rules,
		SyncVouchers $sync_vouchers,
		SyncPromotions $sync_promotions,
		WebhookManager $webhook_manager
	) {
		$this->connection = $connection;
		$this->settings = $settings;
		$this->promotion_rules = $promotion_rules;
		$this->sync_vouchers = $sync_vouchers;
		$this->sync_promotions = $sync_promotions;
		$this->webhook_manager = $webhook_manager;
		$this->logger = new Logger('leat-api');
	}

	/**
	 * Init and hook in Leat API functionality.
	 */
	public function init()
	{
		add_action(
			'rest_api_init',
			function () {
				self::container($this->connection, $this->settings, $this->promotion_rules, $this->sync_vouchers, $this->sync_promotions, $this->webhook_manager)
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
	 * @param PromotionRules $promotion_rules The PromotionRules instance.
	 * @param SyncVouchers   $sync_vouchers   The SyncVouchers instance.
	 * @param SyncPromotions $sync_promotions The SyncPromotions instance.
	 * @param WebhookManager $webhook_manager The WebhookManager instance.
	 * @param boolean       $reset          Used to reset the container to a fresh instance.
	 * @return Container
	 */
	public static function container(
		Connection $connection = null,
		Settings $settings = null,
		PromotionRules $promotion_rules = null,
		SyncVouchers $sync_vouchers = null,
		SyncPromotions $sync_promotions = null,
		WebhookManager $webhook_manager = null,
		Logger $logger = null,
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

		if ($logger) {
			$container->register(Logger::class, function () use ($logger) {
				return $logger;
			});
		}

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

		if ($promotion_rules) {
			$container->register(PromotionRules::class, function () use ($promotion_rules) {
				return $promotion_rules;
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
					$container->get(WebhookManager::class),
					$container->get(PromotionRules::class),
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
					$container->get(PromotionRules::class)
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
