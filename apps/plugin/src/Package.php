<?php

namespace Leat;

use Leat\Domain\Package as NewPackage;
use Leat\Domain\Bootstrap;
use Leat\Registry\Container;
use Leat\Domain\Services\FeatureGating;

/**
 * Main package class.
 */
class Package
{

	/**
	 * Get the package instance.
	 *
	 * @return Package  The Package instance class
	 */
	protected static function get_package()
	{
		return self::container()->get(NewPackage::class);
	}

	/**
	 * Init the package
	 */
	public static function init()
	{
		self::container()->get(Bootstrap::class);
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version()
	{
		return self::get_package()->get_version();
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path()
	{
		return self::get_package()->get_path();
	}

	/**
	 * Returns an instance of the the FeatureGating class.
	 *
	 * @return FeatureGating
	 */
	public static function feature()
	{
		return self::get_package()->feature();
	}

	/**
	 * Loads the dependency injection container for Leat.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance.
	 *                       Note: this means all dependencies will be
	 *                       reconstructed.
	 */
	public static function container($reset = false)
	{
		static $container;
		if (! $container instanceof Container || $reset) {
			$container = new Container();
			// register Package.
			$container->register(
				NewPackage::class,
				function ($container) {
					// TODO: Need to construct this from the plugin file.
					$version = LEAT_VERSION;
					return new NewPackage(
						$version,
						dirname(__DIR__)
					);
				}
			);
			// register Bootstrap.
			$container->register(
				Bootstrap::class,
				function ($container) {
					return new Bootstrap(
						$container
					);
				}
			);
			// register Migration.
			$container->register(
				Migration::class,
				function () {
					return new Migration();
				}
			);
		}
		return $container;
	}
}
