<?php
namespace PiggyWP\Api;

use PiggyWP\Api\Routes\V1\EarnReward;
use PiggyWP\Api\Schemas\ExtendSchema;

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
	public function __construct( ExtendSchema $extend ) {
		$this->extend  = $extend;
		$this->schemas = [
			'v1' => [
				Schemas\V1\EarnRewardSchema::IDENTIFIER => Schemas\V1\EarnRewardSchema::class,
				Schemas\V1\EarnRulesSchema::IDENTIFIER => Schemas\V1\EarnRulesSchema::class,
				Schemas\V1\SpendRulesSchema::IDENTIFIER => Schemas\V1\SpendRulesSchema::class,
				Schemas\V1\Admin\SettingsSchema::IDENTIFIER => Schemas\V1\Admin\SettingsSchema::class,
				Schemas\V1\Admin\ShopsSchema::IDENTIFIER => Schemas\V1\Admin\ShopsSchema::class,
				Schemas\V1\Admin\RewardsSchema::IDENTIFIER => Schemas\V1\Admin\RewardsSchema::class,
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

		return new $schema( $this->extend, $this );
	}
}
