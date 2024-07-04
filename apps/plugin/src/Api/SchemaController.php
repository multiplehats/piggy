<?php
namespace PiggyWP\Api;

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
				Schemas\V1\Admin\EarnRulesSchema::IDENTIFIER => Schemas\V1\Admin\EarnRulesSchema::class,
				Schemas\V1\Admin\SpendRulesSchema::IDENTIFIER => Schemas\V1\Admin\SpendRulesSchema::class,
				Schemas\V1\Admin\SettingsSchema::IDENTIFIER => Schemas\V1\Admin\SettingsSchema::class,
				Schemas\V1\Admin\ShopsSchema::IDENTIFIER => Schemas\V1\Admin\ShopsSchema::class,
				Schemas\V1\Admin\ContactsSchema::IDENTIFIER => Schemas\V1\Admin\ContactsSchema::class,
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
