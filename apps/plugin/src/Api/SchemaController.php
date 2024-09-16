<?php
namespace PiggyWP\Api;

use PiggyWP\Api\Schemas\ExtendSchema;
use PiggyWP\Settings;

/**
 * SchemaController class.
 */
class SchemaController {

	/**
	 * Piggy schema class instances.
	 *
	 * @var Schemas\V1\AbstractSchema[]
	 */
	protected $schemas = [];

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	protected $settings;


	/**
	 * Piggy Rest Extending instance
	 *
	 * @var ExtendSchema
	 */
	private $extend;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema $extend Rest Extending instance.
	 */
	public function __construct( ExtendSchema $extend, Settings $settings ) {
		$this->extend  = $extend;
		$this->settings = $settings;

		$this->schemas = [
			'v1' => [
				Schemas\V1\EarnRewardSchema::IDENTIFIER => Schemas\V1\EarnRewardSchema::class,
				Schemas\V1\EarnRulesSchema::IDENTIFIER => Schemas\V1\EarnRulesSchema::class,
				Schemas\V1\SpendRulesSchema::IDENTIFIER => Schemas\V1\SpendRulesSchema::class,
				Schemas\V1\WCProductsSearchSchema::IDENTIFIER => Schemas\V1\WCProductsSearchSchema::class,
				Schemas\V1\SpendRulesSyncSchema::IDENTIFIER => Schemas\V1\SpendRulesSyncSchema::class,
				Schemas\V1\Admin\SettingsSchema::IDENTIFIER => Schemas\V1\Admin\SettingsSchema::class,
				Schemas\V1\Admin\ShopsSchema::IDENTIFIER => Schemas\V1\Admin\ShopsSchema::class,
				Schemas\V1\Admin\RewardsSchema::IDENTIFIER => Schemas\V1\Admin\RewardsSchema::class,
				Schemas\V1\SpendRulesClaimSchema::IDENTIFIER => Schemas\V1\SpendRulesClaimSchema::class,
				Schemas\V1\CouponsSchema::IDENTIFIER => Schemas\V1\CouponsSchema::class,
				Schemas\V1\JoinProgramSchema::IDENTIFIER => Schemas\V1\JoinProgramSchema::class,
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
	public function get( $name, $version = 1 ) {
		$schema = $this->schemas[ "v{$version}" ][ $name ] ?? false;

		if ( ! $schema ) {
			throw new \Exception( "{$name} v{$version} schema does not exist" );
		}

		return new $schema( $this->extend, $this, $this->settings );
	}
}
