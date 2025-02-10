<?php

namespace Leat\Utils;

/**
 * Common Helper class.
 */
class Common
{
	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $resource The resource to check.
	 * @return bool
	 */
	public static function is_not_empty_array($resource)
	{
		return (is_array($resource) && ! empty($resource));
	}

	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $product The product to check.
	 * @return bool
	 */
	public static function is_woocommerce_product($product)
	{
		return ($product instanceof \WC_Product);
	}


	/**
	 * Get the languages. Supports;
	 * - WPML
	 * - Polylang
	 */
	public static function get_languages()
	{
		$languages = array();

		if (defined('ICL_SITEPRESS_VERSION')) {
			// WPML.
			$wpml_languages = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');
			if (! empty($wpml_languages)) {
				foreach ($wpml_languages as $language) {
					$languages[] = $language['default_locale'];
				}
			}
		} elseif (function_exists('pll_languages_list')) {
			// Polylang.
			$pll_languages = pll_languages_list(array('fields' => 'locale'));
			if (! empty($pll_languages)) {
				$languages = $pll_languages;
			}
		} else {
			// Default to single language.
			$languages[] = get_locale();
		}

		return $languages;
	}

	/**
	 * Get the current language. Supports;
	 * - WPML
	 * - Polylang
	 */
	public static function get_current_language()
	{
		$current_language = '';

		if (defined('ICL_SITEPRESS_VERSION')) {
			// WPML.
			$current_language = apply_filters('wpml_current_language', null);
			if ($current_language) {
				$wpml_languages = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');
				if (! empty($wpml_languages) && isset($wpml_languages[$current_language])) {
					$current_language = $wpml_languages[$current_language]['default_locale'];
				}
			}
		} elseif (function_exists('pll_current_language')) {
			// Polylang.
			$current_language = pll_current_language('locale');
		}

		if (! $current_language) {
			// Default to single language.
			$current_language = get_locale();
		}

		return $current_language;
	}

	/**
	 * Glob wp options.
	 *
	 * @param string $pattern The pattern to glob.
	 *
	 * @return array
	 */
	public static function glob_wp_options($pattern)
	{
		if (is_multisite()) {
			return array_map('maybe_unserialize', get_site_option($pattern, array()));
		}
		return array_map('maybe_unserialize', get_option($pattern, array()));
	}

	/**
	 * Convert an array to an object recursively.
	 *
	 * @param array $array The array to convert.
	 * @return object
	 */
	public static function array_to_object($array)
	{
		return json_decode(json_encode($array));
	}

	/**
	 * Replace placeholders in text with actual values.
	 *
	 * @param string $text Text with placeholders.
	 * @param array $replacements Key-value pairs of replacements.
	 *
	 * @example
	 * $text = "Hello {{name}}";
	 * $replacements = array("name" => "Leat");
	 * $result = Common::replace_placeholders($text, $replacements);
	 * // $result = "Hello Leat";
	 *
	 * @return string
	 */
	public static function replace_placeholders($text, $replacements)
	{
		foreach ($replacements as $key => $value) {
			// Match any variation of the placeholder with optional spaces
			// Matches: {{key}}, {{ key }}, {{key }}, {{ key}}
			$pattern = '/{{\\s*' . preg_quote($key, '/') . '\\s*}}/';
			$text = preg_replace($pattern, $value ?? '', $text);
		}
		return $text;
	}
}
