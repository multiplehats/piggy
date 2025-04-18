<?php
namespace Leat\Api\Formatters;

/**
 * Default Formatter.
 */
class DefaultFormatter implements FormatterInterface {
	/**
	 * Format a given value and return the result.
	 *
	 * @param mixed $value Value to format.
	 * @param array $options Options that influence the formatting.
	 * @return mixed
	 */
	public function format( $value, array $options = [] ) {
		return $value;
	}
}
