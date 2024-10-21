<?php
namespace Leat\Utils;

/**
 * Common Helper class.
 */
class Common {
	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $resource The resource to check.
	 * @return bool
	 */
	public static function is_not_empty_array( $resource ) {
		return ( is_array( $resource ) && ! empty( $resource ) );
	}

	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $product The product to check.
	 * @return bool
	 */
	public static function is_woocommerce_product ( $product ) {
		return ( $product instanceof \WC_Product );
	}


	/**
	 * Get the languages. Supports;
	 * - WPML
	 * - Polylang
	 */
	public static function get_languages() {
		$languages = array();

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			// WPML
			$wpml_languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
			if ( ! empty( $wpml_languages ) ) {
				foreach ( $wpml_languages as $language ) {
					$languages[] = $language['default_locale'];
				}
			}
		} elseif ( function_exists('pll_languages_list') ) {
			// Polylang
			$pll_languages = pll_languages_list( array( 'fields' => 'locale' ) );
			if ( ! empty( $pll_languages ) ) {
				$languages = $pll_languages;
			}
		} else {
			// Default to single language
			$languages[] = get_locale();
		}

		return $languages;
	}

	/**
	 * Get the current language. Supports;
	 * - WPML
	 * - Polylang
	 */
	public static function get_current_language() {
		$current_language = '';

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			// WPML
			$current_language = apply_filters('wpml_current_language', NULL);
			if ($current_language) {
				$wpml_languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
				if (!empty($wpml_languages) && isset($wpml_languages[$current_language])) {
					$current_language = $wpml_languages[$current_language]['default_locale'];
				}
			}
		} elseif ( function_exists('pll_current_language') ) {
			// Polylang
			$current_language = pll_current_language('locale');
		}

		if (!$current_language) {
			// Default to single language
			$current_language = get_locale();
		}

		return $current_language;
	}

}
