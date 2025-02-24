<?php

namespace Leat\Api;

use Leat\Api\Schemas\ExtendSchema;
use Leat\Domain\Services\PromotionRulesService;
use Leat\Settings;
use Leat\Utils\Logger;

/**
 * SchemaController class.
 */
class SchemaController
{

	/**
	 * Leat schema class instances.
	 *
	 * @var Schemas\V1\AbstractSchema[]
	 */
	protected $schemas = [];

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Promotion rules service instance
	 *
	 * @var PromotionRulesService
	 */
	protected $promotion_rules_service;

	/**
	 * Leat Rest Extending instance
	 *
	 * @var ExtendSchema
	 */
	private $extend;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema $extend Rest Extending instance.
	 */
	public function __construct(ExtendSchema $extend, Logger $logger, Settings $settings, PromotionRulesService $promotion_rules_service)
	{
		$this->extend                  = $extend;
		$this->logger                  = $logger;
		$this->settings                = $settings;
		$this->promotion_rules_service = $promotion_rules_service;

		$this->schemas = [
			'v1' => [
				Schemas\V1\WebhooksSchema::IDENTIFIER => Schemas\V1\WebhooksSchema::class,
				Schemas\V1\EarnRewardSchema::IDENTIFIER    => Schemas\V1\EarnRewardSchema::class,
				Schemas\V1\EarnRulesSchema::IDENTIFIER     => Schemas\V1\EarnRulesSchema::class,
				Schemas\V1\SpendRulesSchema::IDENTIFIER    => Schemas\V1\SpendRulesSchema::class,
				Schemas\V1\PromotionRulesSchema::IDENTIFIER => Schemas\V1\PromotionRulesSchema::class,
				Schemas\V1\WCProductsSearchSchema::IDENTIFIER => Schemas\V1\WCProductsSearchSchema::class,
				Schemas\V1\SyncPromotionsSchema::IDENTIFIER => Schemas\V1\SyncPromotionsSchema::class,
				Schemas\V1\SyncRewardsSchema::IDENTIFIER => Schemas\V1\SyncRewardsSchema::class,
				Schemas\V1\SyncVouchersSchema::IDENTIFIER  => Schemas\V1\SyncVouchersSchema::class,
				Schemas\V1\Admin\SettingsSchema::IDENTIFIER => Schemas\V1\Admin\SettingsSchema::class,
				Schemas\V1\Admin\ShopsSchema::IDENTIFIER   => Schemas\V1\Admin\ShopsSchema::class,
				Schemas\V1\Admin\RewardsSchema::IDENTIFIER => Schemas\V1\Admin\RewardsSchema::class,
				Schemas\V1\SpendRulesClaimSchema::IDENTIFIER => Schemas\V1\SpendRulesClaimSchema::class,
				Schemas\V1\CouponsSchema::IDENTIFIER       => Schemas\V1\CouponsSchema::class,
				Schemas\V1\JoinProgramSchema::IDENTIFIER   => Schemas\V1\JoinProgramSchema::class,
				Schemas\V1\ContactSchema::IDENTIFIER       => Schemas\V1\ContactSchema::class,
				Schemas\V1\WCCategoriesSearchSchema::IDENTIFIER => Schemas\V1\WCCategoriesSearchSchema::class,
				Schemas\V1\SyncWebhooksSchema::IDENTIFIER => Schemas\V1\SyncWebhooksSchema::class,
			],
		];
	}

	/**
	 * Get a schema class instance.
	 *
	 * @throws \Exception If the schema does not exist.
	 *
	 * @param string $name Name of schema.
	 * @param int    $version API Version being requested.
	 * @return Schemas\V1\AbstractSchema A new instance of the requested schema.
	 */
	public function get($name, $version = 1)
	{
		$schema = $this->schemas["v{$version}"][$name] ?? false;

		if (! $schema) {
			throw new \Exception(esc_html(sprintf('%s v%d schema does not exist', $name, $version)));
		}

		return new $schema($this->extend, $this->logger, $this, $this->settings, $this->promotion_rules_service);
	}
}
