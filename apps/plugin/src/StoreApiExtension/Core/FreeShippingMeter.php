<?php
namespace PiggyWP\StoreApiExtension\Core;

use PiggyWP\StoreApiExtension\AbstractStoreApiExtensionType;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use WC_Shipping_Zones;

/**
 * Free Shipping Meter: StoreApi extension integration
 *
 * @since 3.0.0
 */
final class FreeShippingMeter extends AbstractStoreApiExtensionType {
	/**
	 * StoreApi extension name/id/slug
	 *
	 * @var string
	 */
	protected $name = 'free_shipping_meter';

	/**
	 * Schema identifiers
	 */
	protected $schema_ids = array( CartSchema::IDENTIFIER );

	/**
	 * Initializes the StoreApi extension type.
	 */
	public function initialize() {
		$this->settings = array(
			'enabled'             => $this->options->get( 'free_shipping_meter_enable' ),
			'type'                => $this->options->get( 'free_shipping_meter_type' ),
			'custom_amount'       => $this->options->get( 'free_shipping_meter_custom_global' ),
			'input_text'          => $this->options->get( 'free_shipping_meter_text_base' ),
			'input_text_achieved' => $this->options->get( 'free_shipping_meter_text_achieved' ),
		);
	}

	/**
	 * Returns if this StoreApi extension should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->get_setting( 'enabled', false ) === 'on';
	}

	/**
	 * Returns an array of scripts/handles to be registered for this Store Api extension.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_handles() {
		return array();
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the Store Api extension script.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data() {
		return array();
	}

	/**
	 * Returns the schema type.
	 */
	public function get_store_api_extension_schema_ids() {
		return $this->schema_ids;
	}

	/**
	 * Returns an Schema array for the StoreApi.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema() {
		return array(
			'description' => __( 'Free shipping meter data.', 'piggy' ),
			'type'        => array( 'array', 'null' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
	}

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_store_api_extension_callback( $param = null ): array {
		return $this->get_free_shipping_meter_data();
	}

	/**
	 * Get shipping data
	 *
	 * @return array
	 */
	public function get_free_shipping_meter_data() {
		$customer_location  = wc_get_customer_default_location();
		$free_shipping_data = self::get_free_shipping_settings( $customer_location );
		$country_code       = $customer_location['country'];
		$required_amount    = null;
		$requires           = null;
		$ignore_discounts   = null;

		if ( 'custom' === $this->get_setting( 'type' ) && $this->get_setting( 'custom_amount' ) > 0 ) {
			$required_amount = $this->get_setting( 'custom_amount' );
		} elseif ( $free_shipping_data && is_object( $free_shipping_data ) ) {
			$required_amount  = $free_shipping_data->minimum_amount;
			$requires         = $free_shipping_data->requires;
			$ignore_discounts = $free_shipping_data->ignore_discounts;
		}

		$data = array(
			'type'             => $this->get_setting( 'type' ),
			'text'             => $this->get_setting( 'input_text' ),
			'text_achieved'    => $this->get_setting( 'input_text_achieved' ),
			'country_code'     => $country_code,
			'flag'             => $this->get_country_emoji( $country_code ),
			'requires'         => $requires,
			'ignore_discounts' => $ignore_discounts === 'no' ? false : true,
			'required_amount'  => $this->extend->get_formatter( 'money' )->format(
				$required_amount,
				array(
					'rounding_mode' => PHP_ROUND_HALF_UP,
					'decimals'      => wc_get_price_decimals(),
				)
			),
		);

		return $data;
	}

	/**
	 * Returns an object containing the free shipping options from the WooCommerce settings based on location.
	 *
	 * @param string $location The locale to get the free shipping settings for.
	 */
	private function get_free_shipping_settings( $location ) {
		$result     = null;
		$found_zone = null;
		$zones      = WC_Shipping_Zones::get_zones();

		foreach ( $zones as $z ) {
			$zone_location = ! empty( $z['zone_locations'] ) ? $z['zone_locations'][0] : null;

			if ( ( $zone_location && $location['country'] === $zone_location->code ) || ( empty( $z['zone_locations'] ) && ! $found_zone ) ) {
				$found_zone = $z;
			}
		}

		if ( $found_zone ) {
			$shipping_methods     = $found_zone['shipping_methods'];
			$free_shipping_method = null;

			foreach ( $shipping_methods as $method ) {
				if ( 'free_shipping' === $method->id ) {
					$free_shipping_method = $method;
					break;
				}
			}

			if ( $free_shipping_method && 'yes' === $free_shipping_method->enabled ) {
				$result = (object) array(
					'ignore_discounts' => $free_shipping_method->ignore_discounts,
					'minimum_amount'   => $free_shipping_method->min_amount,
					'requires'         => $free_shipping_method->requires,
				);
			}
		}

		return $result;
	}

	/**
	 * Returns the emoji flag for the given country code.
	 *
	 * @param string $country_code The country code.
	 */
	private function get_country_emoji( $country_code ) {
		$value       = null;
		$emoji_flags = array();

		$emoji_flags['AD'] = "\u{1F1E6}\u{1F1E9}";
		$emoji_flags['AE'] = "\u{1F1E6}\u{1F1EA}";
		$emoji_flags['AF'] = "\u{1F1E6}\u{1F1EB}";
		$emoji_flags['AG'] = "\u{1F1E6}\u{1F1EC}";
		$emoji_flags['AI'] = "\u{1F1E6}\u{1F1EE}";
		$emoji_flags['AL'] = "\u{1F1E6}\u{1F1F1}";
		$emoji_flags['AM'] = "\u{1F1E6}\u{1F1F2}";
		$emoji_flags['AO'] = "\u{1F1E6}\u{1F1F4}";
		$emoji_flags['AQ'] = "\u{1F1E6}\u{1F1F6}";
		$emoji_flags['AR'] = "\u{1F1E6}\u{1F1F7}";
		$emoji_flags['AS'] = "\u{1F1E6}\u{1F1F8}";
		$emoji_flags['AT'] = "\u{1F1E6}\u{1F1F9}";
		$emoji_flags['AU'] = "\u{1F1E6}\u{1F1FA}";
		$emoji_flags['AW'] = "\u{1F1E6}\u{1F1FC}";
		$emoji_flags['AX'] = "\u{1F1E6}\u{1F1FD}";
		$emoji_flags['AZ'] = "\u{1F1E6}\u{1F1FF}";
		$emoji_flags['BA'] = "\u{1F1E7}\u{1F1E6}";
		$emoji_flags['BB'] = "\u{1F1E7}\u{1F1E7}";
		$emoji_flags['BD'] = "\u{1F1E7}\u{1F1E9}";
		$emoji_flags['BE'] = "\u{1F1E7}\u{1F1EA}";
		$emoji_flags['BF'] = "\u{1F1E7}\u{1F1EB}";
		$emoji_flags['BG'] = "\u{1F1E7}\u{1F1EC}";
		$emoji_flags['BH'] = "\u{1F1E7}\u{1F1ED}";
		$emoji_flags['BI'] = "\u{1F1E7}\u{1F1EE}";
		$emoji_flags['BJ'] = "\u{1F1E7}\u{1F1EF}";
		$emoji_flags['BL'] = "\u{1F1E7}\u{1F1F1}";
		$emoji_flags['BM'] = "\u{1F1E7}\u{1F1F2}";
		$emoji_flags['BN'] = "\u{1F1E7}\u{1F1F3}";
		$emoji_flags['BO'] = "\u{1F1E7}\u{1F1F4}";
		$emoji_flags['BQ'] = "\u{1F1E7}\u{1F1F6}";
		$emoji_flags['BR'] = "\u{1F1E7}\u{1F1F7}";
		$emoji_flags['BS'] = "\u{1F1E7}\u{1F1F8}";
		$emoji_flags['BT'] = "\u{1F1E7}\u{1F1F9}";
		$emoji_flags['BV'] = "\u{1F1E7}\u{1F1FB}";
		$emoji_flags['BW'] = "\u{1F1E7}\u{1F1FC}";
		$emoji_flags['BY'] = "\u{1F1E7}\u{1F1FE}";
		$emoji_flags['BZ'] = "\u{1F1E7}\u{1F1FF}";
		$emoji_flags['CA'] = "\u{1F1E8}\u{1F1E6}";
		$emoji_flags['CC'] = "\u{1F1E8}\u{1F1E8}";
		$emoji_flags['CD'] = "\u{1F1E8}\u{1F1E9}";
		$emoji_flags['CF'] = "\u{1F1E8}\u{1F1EB}";
		$emoji_flags['CG'] = "\u{1F1E8}\u{1F1EC}";
		$emoji_flags['CH'] = "\u{1F1E8}\u{1F1ED}";
		$emoji_flags['CI'] = "\u{1F1E8}\u{1F1EE}";
		$emoji_flags['CK'] = "\u{1F1E8}\u{1F1F0}";
		$emoji_flags['CL'] = "\u{1F1E8}\u{1F1F1}";
		$emoji_flags['CM'] = "\u{1F1E8}\u{1F1F2}";
		$emoji_flags['CN'] = "\u{1F1E8}\u{1F1F3}";
		$emoji_flags['CO'] = "\u{1F1E8}\u{1F1F4}";
		$emoji_flags['CR'] = "\u{1F1E8}\u{1F1F7}";
		$emoji_flags['CU'] = "\u{1F1E8}\u{1F1FA}";
		$emoji_flags['CV'] = "\u{1F1E8}\u{1F1FB}";
		$emoji_flags['CW'] = "\u{1F1E8}\u{1F1FC}";
		$emoji_flags['CX'] = "\u{1F1E8}\u{1F1FD}";
		$emoji_flags['CY'] = "\u{1F1E8}\u{1F1FE}";
		$emoji_flags['CZ'] = "\u{1F1E8}\u{1F1FF}";
		$emoji_flags['DE'] = "\u{1F1E9}\u{1F1EA}";
		$emoji_flags['DG'] = "\u{1F1E9}\u{1F1EC}";
		$emoji_flags['DJ'] = "\u{1F1E9}\u{1F1EF}";
		$emoji_flags['DK'] = "\u{1F1E9}\u{1F1F0}";
		$emoji_flags['DM'] = "\u{1F1E9}\u{1F1F2}";
		$emoji_flags['DO'] = "\u{1F1E9}\u{1F1F4}";
		$emoji_flags['DZ'] = "\u{1F1E9}\u{1F1FF}";
		$emoji_flags['EC'] = "\u{1F1EA}\u{1F1E8}";
		$emoji_flags['EE'] = "\u{1F1EA}\u{1F1EA}";
		$emoji_flags['EG'] = "\u{1F1EA}\u{1F1EC}";
		$emoji_flags['EH'] = "\u{1F1EA}\u{1F1ED}";
		$emoji_flags['ER'] = "\u{1F1EA}\u{1F1F7}";
		$emoji_flags['ES'] = "\u{1F1EA}\u{1F1F8}";
		$emoji_flags['ET'] = "\u{1F1EA}\u{1F1F9}";
		$emoji_flags['FI'] = "\u{1F1EB}\u{1F1EE}";
		$emoji_flags['FJ'] = "\u{1F1EB}\u{1F1EF}";
		$emoji_flags['FK'] = "\u{1F1EB}\u{1F1F0}";
		$emoji_flags['FM'] = "\u{1F1EB}\u{1F1F2}";
		$emoji_flags['FO'] = "\u{1F1EB}\u{1F1F4}";
		$emoji_flags['FR'] = "\u{1F1EB}\u{1F1F7}";
		$emoji_flags['GA'] = "\u{1F1EC}\u{1F1E6}";
		$emoji_flags['GB'] = "\u{1F1EC}\u{1F1E7}";
		$emoji_flags['GD'] = "\u{1F1EC}\u{1F1E9}";
		$emoji_flags['GE'] = "\u{1F1EC}\u{1F1EA}";
		$emoji_flags['GF'] = "\u{1F1EC}\u{1F1EB}";
		$emoji_flags['GG'] = "\u{1F1EC}\u{1F1EC}";
		$emoji_flags['GH'] = "\u{1F1EC}\u{1F1ED}";
		$emoji_flags['GI'] = "\u{1F1EC}\u{1F1EE}";
		$emoji_flags['GL'] = "\u{1F1EC}\u{1F1F1}";
		$emoji_flags['GM'] = "\u{1F1EC}\u{1F1F2}";
		$emoji_flags['GN'] = "\u{1F1EC}\u{1F1F3}";
		$emoji_flags['GP'] = "\u{1F1EC}\u{1F1F5}";
		$emoji_flags['GQ'] = "\u{1F1EC}\u{1F1F6}";
		$emoji_flags['GR'] = "\u{1F1EC}\u{1F1F7}";
		$emoji_flags['GS'] = "\u{1F1EC}\u{1F1F8}";
		$emoji_flags['GT'] = "\u{1F1EC}\u{1F1F9}";
		$emoji_flags['GU'] = "\u{1F1EC}\u{1F1FA}";
		$emoji_flags['GW'] = "\u{1F1EC}\u{1F1FC}";
		$emoji_flags['GY'] = "\u{1F1EC}\u{1F1FE}";
		$emoji_flags['HK'] = "\u{1F1ED}\u{1F1F0}";
		$emoji_flags['HM'] = "\u{1F1ED}\u{1F1F2}";
		$emoji_flags['HN'] = "\u{1F1ED}\u{1F1F3}";
		$emoji_flags['HR'] = "\u{1F1ED}\u{1F1F7}";
		$emoji_flags['HT'] = "\u{1F1ED}\u{1F1F9}";
		$emoji_flags['HU'] = "\u{1F1ED}\u{1F1FA}";
		$emoji_flags['ID'] = "\u{1F1EE}\u{1F1E9}";
		$emoji_flags['IE'] = "\u{1F1EE}\u{1F1EA}";
		$emoji_flags['IL'] = "\u{1F1EE}\u{1F1F1}";
		$emoji_flags['IM'] = "\u{1F1EE}\u{1F1F2}";
		$emoji_flags['IN'] = "\u{1F1EE}\u{1F1F3}";
		$emoji_flags['IO'] = "\u{1F1EE}\u{1F1F4}";
		$emoji_flags['IQ'] = "\u{1F1EE}\u{1F1F6}";
		$emoji_flags['IR'] = "\u{1F1EE}\u{1F1F7}";
		$emoji_flags['IS'] = "\u{1F1EE}\u{1F1F8}";
		$emoji_flags['IT'] = "\u{1F1EE}\u{1F1F9}";
		$emoji_flags['JE'] = "\u{1F1EF}\u{1F1EA}";
		$emoji_flags['JM'] = "\u{1F1EF}\u{1F1F2}";
		$emoji_flags['JO'] = "\u{1F1EF}\u{1F1F4}";
		$emoji_flags['JP'] = "\u{1F1EF}\u{1F1F5}";
		$emoji_flags['KE'] = "\u{1F1F0}\u{1F1EA}";
		$emoji_flags['KG'] = "\u{1F1F0}\u{1F1EC}";
		$emoji_flags['KH'] = "\u{1F1F0}\u{1F1ED}";
		$emoji_flags['KI'] = "\u{1F1F0}\u{1F1EE}";
		$emoji_flags['KM'] = "\u{1F1F0}\u{1F1F2}";
		$emoji_flags['KN'] = "\u{1F1F0}\u{1F1F3}";
		$emoji_flags['KP'] = "\u{1F1F0}\u{1F1F5}";
		$emoji_flags['KR'] = "\u{1F1F0}\u{1F1F7}";
		$emoji_flags['KW'] = "\u{1F1F0}\u{1F1FC}";
		$emoji_flags['KY'] = "\u{1F1F0}\u{1F1FE}";
		$emoji_flags['KZ'] = "\u{1F1F0}\u{1F1FF}";
		$emoji_flags['LA'] = "\u{1F1F1}\u{1F1E6}";
		$emoji_flags['LB'] = "\u{1F1F1}\u{1F1E7}";
		$emoji_flags['LC'] = "\u{1F1F1}\u{1F1E8}";
		$emoji_flags['LI'] = "\u{1F1F1}\u{1F1EE}";
		$emoji_flags['LK'] = "\u{1F1F1}\u{1F1F0}";
		$emoji_flags['LR'] = "\u{1F1F1}\u{1F1F7}";
		$emoji_flags['LS'] = "\u{1F1F1}\u{1F1F8}";
		$emoji_flags['LT'] = "\u{1F1F1}\u{1F1F9}";
		$emoji_flags['LU'] = "\u{1F1F1}\u{1F1FA}";
		$emoji_flags['LV'] = "\u{1F1F1}\u{1F1FB}";
		$emoji_flags['LY'] = "\u{1F1F1}\u{1F1FE}";
		$emoji_flags['MA'] = "\u{1F1F2}\u{1F1E6}";
		$emoji_flags['MC'] = "\u{1F1F2}\u{1F1E8}";
		$emoji_flags['MD'] = "\u{1F1F2}\u{1F1E9}";
		$emoji_flags['ME'] = "\u{1F1F2}\u{1F1EA}";
		$emoji_flags['MF'] = "\u{1F1F2}\u{1F1EB}";
		$emoji_flags['MG'] = "\u{1F1F2}\u{1F1EC}";
		$emoji_flags['MH'] = "\u{1F1F2}\u{1F1ED}";
		$emoji_flags['MK'] = "\u{1F1F2}\u{1F1F0}";
		$emoji_flags['ML'] = "\u{1F1F2}\u{1F1F1}";
		$emoji_flags['MM'] = "\u{1F1F2}\u{1F1F2}";
		$emoji_flags['MN'] = "\u{1F1F2}\u{1F1F3}";
		$emoji_flags['MO'] = "\u{1F1F2}\u{1F1F4}";
		$emoji_flags['MP'] = "\u{1F1F2}\u{1F1F5}";
		$emoji_flags['MQ'] = "\u{1F1F2}\u{1F1F6}";
		$emoji_flags['MR'] = "\u{1F1F2}\u{1F1F7}";
		$emoji_flags['MS'] = "\u{1F1F2}\u{1F1F8}";
		$emoji_flags['MT'] = "\u{1F1F2}\u{1F1F9}";
		$emoji_flags['MU'] = "\u{1F1F2}\u{1F1FA}";
		$emoji_flags['MV'] = "\u{1F1F2}\u{1F1FB}";
		$emoji_flags['MW'] = "\u{1F1F2}\u{1F1FC}";
		$emoji_flags['MX'] = "\u{1F1F2}\u{1F1FD}";
		$emoji_flags['MY'] = "\u{1F1F2}\u{1F1FE}";
		$emoji_flags['MZ'] = "\u{1F1F2}\u{1F1FF}";
		$emoji_flags['NA'] = "\u{1F1F3}\u{1F1E6}";
		$emoji_flags['NC'] = "\u{1F1F3}\u{1F1E8}";
		$emoji_flags['NE'] = "\u{1F1F3}\u{1F1EA}";
		$emoji_flags['NF'] = "\u{1F1F3}\u{1F1EB}";
		$emoji_flags['NG'] = "\u{1F1F3}\u{1F1EC}";
		$emoji_flags['NI'] = "\u{1F1F3}\u{1F1EE}";
		$emoji_flags['NL'] = "\u{1F1F3}\u{1F1F1}";
		$emoji_flags['NO'] = "\u{1F1F3}\u{1F1F4}";
		$emoji_flags['NP'] = "\u{1F1F3}\u{1F1F5}";
		$emoji_flags['NR'] = "\u{1F1F3}\u{1F1F7}";
		$emoji_flags['NU'] = "\u{1F1F3}\u{1F1FA}";
		$emoji_flags['NZ'] = "\u{1F1F3}\u{1F1FF}";
		$emoji_flags['OM'] = "\u{1F1F4}\u{1F1F2}";
		$emoji_flags['PA'] = "\u{1F1F5}\u{1F1E6}";
		$emoji_flags['PE'] = "\u{1F1F5}\u{1F1EA}";
		$emoji_flags['PF'] = "\u{1F1F5}\u{1F1EB}";
		$emoji_flags['PG'] = "\u{1F1F5}\u{1F1EC}";
		$emoji_flags['PH'] = "\u{1F1F5}\u{1F1ED}";
		$emoji_flags['PK'] = "\u{1F1F5}\u{1F1F0}";
		$emoji_flags['PL'] = "\u{1F1F5}\u{1F1F1}";
		$emoji_flags['PM'] = "\u{1F1F5}\u{1F1F2}";
		$emoji_flags['PN'] = "\u{1F1F5}\u{1F1F3}";
		$emoji_flags['PR'] = "\u{1F1F5}\u{1F1F7}";
		$emoji_flags['PS'] = "\u{1F1F5}\u{1F1F8}";
		$emoji_flags['PT'] = "\u{1F1F5}\u{1F1F9}";
		$emoji_flags['PW'] = "\u{1F1F5}\u{1F1FC}";
		$emoji_flags['PY'] = "\u{1F1F5}\u{1F1FE}";
		$emoji_flags['QA'] = "\u{1F1F6}\u{1F1E6}";
		$emoji_flags['RE'] = "\u{1F1F7}\u{1F1EA}";
		$emoji_flags['RO'] = "\u{1F1F7}\u{1F1F4}";
		$emoji_flags['RS'] = "\u{1F1F7}\u{1F1F8}";
		$emoji_flags['RU'] = "\u{1F1F7}\u{1F1FA}";
		$emoji_flags['RW'] = "\u{1F1F7}\u{1F1FC}";
		$emoji_flags['SA'] = "\u{1F1F8}\u{1F1E6}";
		$emoji_flags['SB'] = "\u{1F1F8}\u{1F1E7}";
		$emoji_flags['SC'] = "\u{1F1F8}\u{1F1E8}";
		$emoji_flags['SD'] = "\u{1F1F8}\u{1F1E9}";
		$emoji_flags['SE'] = "\u{1F1F8}\u{1F1EA}";
		$emoji_flags['SG'] = "\u{1F1F8}\u{1F1EC}";
		$emoji_flags['SH'] = "\u{1F1F8}\u{1F1ED}";
		$emoji_flags['SI'] = "\u{1F1F8}\u{1F1EE}";
		$emoji_flags['SJ'] = "\u{1F1F8}\u{1F1EF}";
		$emoji_flags['SK'] = "\u{1F1F8}\u{1F1F0}";
		$emoji_flags['SL'] = "\u{1F1F8}\u{1F1F1}";
		$emoji_flags['SM'] = "\u{1F1F8}\u{1F1F2}";
		$emoji_flags['SN'] = "\u{1F1F8}\u{1F1F3}";
		$emoji_flags['SO'] = "\u{1F1F8}\u{1F1F4}";
		$emoji_flags['SR'] = "\u{1F1F8}\u{1F1F7}";
		$emoji_flags['SS'] = "\u{1F1F8}\u{1F1F8}";
		$emoji_flags['ST'] = "\u{1F1F8}\u{1F1F9}";
		$emoji_flags['SV'] = "\u{1F1F8}\u{1F1FB}";
		$emoji_flags['SX'] = "\u{1F1F8}\u{1F1FD}";
		$emoji_flags['SY'] = "\u{1F1F8}\u{1F1FE}";
		$emoji_flags['SZ'] = "\u{1F1F8}\u{1F1FF}";
		$emoji_flags['TC'] = "\u{1F1F9}\u{1F1E8}";
		$emoji_flags['TD'] = "\u{1F1F9}\u{1F1E9}";
		$emoji_flags['TF'] = "\u{1F1F9}\u{1F1EB}";
		$emoji_flags['TG'] = "\u{1F1F9}\u{1F1EC}";
		$emoji_flags['TH'] = "\u{1F1F9}\u{1F1ED}";
		$emoji_flags['TJ'] = "\u{1F1F9}\u{1F1EF}";
		$emoji_flags['TK'] = "\u{1F1F9}\u{1F1F0}";
		$emoji_flags['TL'] = "\u{1F1F9}\u{1F1F1}";
		$emoji_flags['TM'] = "\u{1F1F9}\u{1F1F2}";
		$emoji_flags['TN'] = "\u{1F1F9}\u{1F1F3}";
		$emoji_flags['TO'] = "\u{1F1F9}\u{1F1F4}";
		$emoji_flags['TR'] = "\u{1F1F9}\u{1F1F7}";
		$emoji_flags['TT'] = "\u{1F1F9}\u{1F1F9}";
		$emoji_flags['TV'] = "\u{1F1F9}\u{1F1FB}";
		$emoji_flags['TW'] = "\u{1F1F9}\u{1F1FC}";
		$emoji_flags['TZ'] = "\u{1F1F9}\u{1F1FF}";
		$emoji_flags['UA'] = "\u{1F1FA}\u{1F1E6}";
		$emoji_flags['UG'] = "\u{1F1FA}\u{1F1EC}";
		$emoji_flags['UM'] = "\u{1F1FA}\u{1F1F2}";
		$emoji_flags['US'] = "\u{1F1FA}\u{1F1F8}";
		$emoji_flags['UY'] = "\u{1F1FA}\u{1F1FE}";
		$emoji_flags['UZ'] = "\u{1F1FA}\u{1F1FF}";
		$emoji_flags['VA'] = "\u{1F1FB}\u{1F1E6}";
		$emoji_flags['VC'] = "\u{1F1FB}\u{1F1E8}";
		$emoji_flags['VE'] = "\u{1F1FB}\u{1F1EA}";
		$emoji_flags['VG'] = "\u{1F1FB}\u{1F1EC}";
		$emoji_flags['VI'] = "\u{1F1FB}\u{1F1EE}";
		$emoji_flags['VN'] = "\u{1F1FB}\u{1F1F3}";
		$emoji_flags['VU'] = "\u{1F1FB}\u{1F1FA}";
		$emoji_flags['WF'] = "\u{1F1FC}\u{1F1EB}";
		$emoji_flags['WS'] = "\u{1F1FC}\u{1F1F8}";
		$emoji_flags['XK'] = "\u{1F1FD}\u{1F1F0}";
		$emoji_flags['YE'] = "\u{1F1FE}\u{1F1EA}";
		$emoji_flags['YT'] = "\u{1F1FE}\u{1F1F9}";
		$emoji_flags['ZA'] = "\u{1F1FF}\u{1F1E6}";
		$emoji_flags['ZM'] = "\u{1F1FF}\u{1F1F2}";
		$emoji_flags['ZW'] = "\u{1F1FF}\u{1F1FC}";

		foreach ( $emoji_flags as $key => $v ) {
			if ( $country_code === $key ) {
				$value = $v;
			}
		}

		return $value;
	}
}
