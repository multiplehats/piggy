<?php

namespace PiggyWP;

use PiggyWP\Utils\Common;

/**
 * Contains all the default options and options from the database.
 */
class Options {
	const PIGGY_VERSION = 'piggy_version';

	// Fields.
	const CHECKBOX    = 'checkbox';
	const CHECKBOXES  = 'checkboxes';
	const SWITCH	  = 'switch';
	const TEXT        = 'text';
	const SELECT      = 'select';
	const TEXTAREA    = 'textarea';
	const MULTISELECT = 'multiselect';
	const COLOR       = 'color';
	const NUMBER      = 'number';
	const OBJECT      = 'object';
	const API_KEY     = 'api_key';
	const TRANSLATABLE_TEXT = 'translatable_text';

	/**
	 * Prefix for each option
	 *
	 * @var string
	 */
	private static $option_prefix = 'piggy_';

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private static $default_settings;

	/**
	 * Settings and their values.
	 *
	 * @var array
	 */
	private static $all_option_values;

	/**
	 * Constructor
	 *
	 * @var Options
	 */
	private static $instance;

	/**
	 * Get instance.
	 */
	public static function get_self() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default settings.
	 */
	public static function default_settings() {
		// Cache the results.
		if ( null === self::$default_settings ) {
			self::$default_settings = array();

			self::$default_settings['quick_actions'] = array(
				'title'  => __( 'Quick actions', 'piggy' ),
				'fields' => array(
					array(
						'id'      => 'plugin_enable',
						'default' => 'on',
						'type'    => self::SWITCH,
						'label'   => __( 'Enable plugin', 'piggy' ),
						'tooltip' => __( 'If you disable this, the plugin will stop working on the front-end of your website. This is useful if you temporarily want to disable Piggy without deactivating the entire plugin.', 'piggy' ),
					),
					array(
						'id'      => 'plugin_reset',
						'default' => 'off',
						'type'    => self::SWITCH,
						'label'   => __( 'Delete plugin settings upon deactivation', 'piggy' ),
						'tooltip' => __( 'This wlll delete all plugins settings upon deactivation. Use with caution!', 'piggy' ),
					),
				),
			);

			self::$default_settings['connect_account'] = array(
				'title'  => __( 'API Key', 'piggy' ),
				'fields' => array(
					array(
						'id'      => 'api_key',
						'default' => '',
						'type'    => self::API_KEY,
						'label'   => __( 'API Key', 'piggy' ),
						'tooltip' => __( 'Enter your API key here.', 'piggy' ),
					),
					array(
						'id'      => 'shop_uuid',
						'default' => '',
						'type'    => self::TEXT,
						'label'   => __( 'Shop ID', 'piggy' ),
						'tooltip' => __( 'Select the shop you want to connect to.', 'piggy' ),
					),
				),
			);

			self::$default_settings['general_settings'] = array(
				'title'  => __( 'General Settings', 'piggy' ),
				'fields' => array(
					array(
						'id'      => 'credits_name',
						'default' => array(),
						'type'    => self::TRANSLATABLE_TEXT,
						'label'   => __( 'Credits name', 'piggy' ),
						'description' => __( 'The name of the credits in your shop.', 'piggy' ),
					),
					array(
						'id'      => 'include_guests',
						'default' => 'off',
						'type'    => self::SWITCH,
						'label'   => __( 'Include guests', 'piggy' ),
						'description' => __( 'Include customers without an account (guests) in your loyalty program.', 'piggy' ),
					),
					array(
						'id'      => 'reward_order_statuses',
						'type'    => self::CHECKBOXES,
						'label'   => __( 'Reward order statuses', 'piggy' ),
						'description' => __( 'Reward customers when the financial status of the order is one of the following', 'piggy' ),
						'default' => array(
							'paid' => 'on',
						),
						'options'     => array(
							'paid'      => array(
								'label' => __( 'Pending payment', 'piggy' ),
								'tooltip' => __( 'The order has been received, but no payment has been made. Pending payment orders are generally awaiting customer action.', 'piggy' )
							),
							'pending'      => array(
								'label' => __( 'On hold', 'cartpops' ),
								'tooltip' => __( 'The order is awaiting payment confirmation. Stock is reduced, but you need to confirm payment.', 'cartpops' )
							),
							'processing'      => array(
								'label' => __( 'Processing', 'cartpops' ),
								'tooltip' => __( 'Payment has been received (paid), and the stock has been reduced. The order is awaiting fulfillment.', 'cartpops' )
							),
							'completed'      => array(
								'label' => __( 'Completed', 'cartpops' ),
								'tooltip' => __( 'Order fulfilled and complete.', 'cartpops' )
							),
						),
					),
					array(
						'id'      => 'withdraw_order_statuses',
						'type'    => self::CHECKBOXES,
						'label'   => __( 'Withdraw points order statuses', 'piggy' ),
						'description' => __( 'Withdraw credits from customers when the order financial status is one of the following', 'piggy'),
						'default' => array(
							'paid' => 'on',
						),
						'options'     => array(
							'paid'      => array(
								'label' => __( 'Pending payment', 'piggy' ),
								'tooltip' => __( 'The order has been received, but no payment has been made. Pending payment orders are generally awaiting customer action.', 'piggy' )
							),
							'pending'      => array(
								'label' => __( 'On hold', 'cartpops' ),
								'tooltip' => __( 'The order is awaiting payment confirmation. Stock is reduced, but you need to confirm payment.', 'cartpops' )
							),
							'processing'      => array(
								'label' => __( 'Processing', 'cartpops' ),
								'tooltip' => __( 'Payment has been received (paid), and the stock has been reduced. The order is awaiting fulfillment.', 'cartpops' )
							),
							'completed'      => array(
								'label' => __( 'Completed', 'cartpops' ),
								'tooltip' => __( 'Order fulfilled and complete.', 'cartpops' )
							),
						),
					),
					array(
						'id'      => 'reward_order_parts',
						'type'    => self::CHECKBOXES,
						'label'   => __( 'Reward order parts', 'piggy' ),
						'description' => __( 'Reward customers for the following parts of an order', 'piggy' ),
						'default' => array(
							'subtotal' => 'on',
						),
						'options'     => array(
							'subtotal'      => array(
								'label' => __( 'Subtotal', 'piggy' ),
								'tooltip' => __( 'The total amount of the order before taxes and shipping.', 'piggy' )
							),
							'shipping'      => array(
								'label' => __( 'Shipping', 'piggy' ),
								'tooltip' => __( 'The cost of shipping the order.', 'piggy' )
							),
							'tax'      => array(
								'label' => __( 'Tax', 'piggy' ),
								'tooltip' => __( 'The amount of tax on the order.', 'piggy' )
							),
							'discount'      => array(
								'label' => __( 'Discount', 'piggy' ),
								'tooltip' => __( 'The amount of discount on the order.', 'piggy' )
							),
						),
					),
				),
			);
		}

		/**
		 * Filter the default settings.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'piggy_default_settings', self::$default_settings, self::get_self() );
	}

	/**
	 * The plugin's Settings page URL.
	 *
	 * @return string
	 */
	public function get_main_settings_page_url(): string {
		$url = 'options-general.php?page=' . $this->get_settings_page_slug();

		return admin_url( $url );
	}

	/**
	 * The plugin's Settings page slug.
	 *
	 * @return string
	 */
	public function get_settings_page_slug(): string {
		return 'piggy--settings';
	}

	/**
	 * Gets the option for the given name. Returns the default value if the value does not exist.
	 *
	 * @param string $id The option name.
	 * @param bool   $prefix If true, returns the default value for the option.
	 */
	public static function get($id, $prefix = true) {
		if ($prefix) {
			$id = self::$option_prefix . $id;
		}

		$current_language = Common::get_current_language();
		$translatable_id = $id . '_' . $current_language;
		$option_value = get_option($translatable_id, false);

		if ($option_value === false) {
			$option_value = self::get_default($id);
		}

		if (self::is_translatable_text_option($id) && is_string($option_value)) {
			$option_value = json_decode($option_value, true);
		}

		return $option_value;
	}

	/**
	 * Checks if the option exists or not.
	 *
	 * @param string $name Option name.
	 * @param bool   $prefix Whether to prefix the option name or not.
	 *
	 * @return bool
	 */
	public static function has( $name, $prefix = false ) {
		if ( $prefix ) {
			$name = self::$option_prefix . $name;
		}

		return ! empty( get_option( $name ) );
	}

	/**
	 * Saves the option for the given name.
	 *
	 * @param string $name The option name.
	 * @param mixed  $value The option value.
	 * @param bool   $prefix If true, returns the default value for the option.
	 */
	public static function save( $name, $value, $prefix = true ) {
		if ( $prefix ) {
			$name = self::$option_prefix . $name;
		}

		if (self::is_translatable_text_option($name) && is_array($value)) {
			$value = json_encode($value);
		}

		update_option( $name, $value );
	}

	/**
	 * Deletes the option for the given name.
	 *
	 * @param string $name The option name.
	 * @param bool   $prefix If true, returns the default value for the option.
	 */
	public static function delete( $name, $prefix = true ) {
		if ( $prefix ) {
			$name = self::$option_prefix . $name;
		}

		delete_option( $name );
	}

	/**
	 * Add to the default option.
	 *
	 * @param string $section The section name.
	 * @param string $name The name of the option.
	 * @param string $default_value The default value of the option.
	 * @param string $type The type of the option. Can be 'boolean', 'text', 'select', 'textarea', 'multiselect', 'color', 'number'.
	 * @param string $label The label of the option.
	 */
	public static function set_default( $section, $name, $default_value, $type, $label ) {
		self::$default_settings[ $section ]['fields'][] = array(
			'id'      => $name,
			'default' => $default_value,
			'type'    => $type,
			'label'   => $label,
		);
	}

	/**
	 * Get default option from memory.
	 *
	 * @param string $id The name of the option.
	 */
	public static function get_default( $id ) {
		$settings = self::default_settings();

		foreach ( $settings as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( $field['id'] === $id ) {
					return $field['default'];
				}
			}
		}

		return null;
	}

	/**
	 * Get all of the saved options from the database.
	 *
	 * @return array
	 */
	public function get_all_options( $bust_cache = false ): array {
		// Cache the results.
		if ( null === self::$all_option_values || $bust_cache ) {
			global $wpdb;

			$option_name = self::$option_prefix . '%';

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",
					$option_name
				),
				ARRAY_A
			);

			$options = array();

			foreach ( $results as $result ) {
				$options[ $result['option_name'] ] = maybe_unserialize( $result['option_value'] );
			}

			$default_options = self::default_settings();
			$languages = Common::get_languages();

			foreach ( $default_options as $section ) {
				foreach ( $section['fields'] as $field ) {
					$name = self::$option_prefix . $field['id'];

					// Handle translatable text fields
					if ( $field['type'] === self::TRANSLATABLE_TEXT ) {
						if ( isset( $options[ $name ] ) && is_string( $options[ $name ] ) ) {
							$options[ $name ] = json_decode( $options[ $name ], true );
						} else {
							$options[ $name ] = $field['default'];
						}
					} else {
						// If we're missing any options, fall back to the default.
						if ( ! isset( $options[ $name ] ) ) {
							$options[ $name ] = $field['default'];
						}

						// If type is a number, convert to int.
						if ( self::NUMBER === $field['type'] ) {
							$options[ $name ] = (int) $options[ $name ];
						}
					}
				}
			}

			self::$all_option_values = $options;
		}

		return self::$all_option_values;
	}


	/**
	 * Save all of the options to the database.
	 *
	 * @param array $options The options to save.
	 * @param bool  $prefix If true, the options will be saved with the plugin's prefix.
	 */
	public function save_options(array $options, bool $prefix = true) {
		$languages = Common::get_languages();

		foreach ($options as $name => $value) {
			if ($prefix) {
				$name = self::$option_prefix . $name;
			}

			// Handle translatable text fields
			if (is_array($value) && isset($value['type']) && $value['type'] === self::TRANSLATABLE_TEXT) {
				$translatable_values = $value['value'];
				$json_value = json_encode($translatable_values);
				update_option($name, $json_value);
			} else {
				update_option($name, $value['value']);
			}
		}

		return $this->get_options_for_client('admin', true);
	}

	/**
	 * Retrieves options to be used in the WP admin settings and the WP frontend.
	 *
	 * @param string $type The option name.
	 *
	 * @return array
	 */
	public function get_options_for_client( string $type = 'frontend', $bust_cache = false ): array {
		$options = $this->get_all_options( $bust_cache );

		// Remove Piggy prefix.
		$options = array_combine(
			array_map(
				function ( $key ) {
					return str_replace( self::$option_prefix, '', $key );
				},
				array_keys( $options )
			),
			$options
		);

		// Unserialize the options.
		foreach ( $options as $option_name => $option_value ) {
			$options[ $option_name ] = maybe_unserialize( $option_value );
		}

		$excluded_options = array();

		// Remove options that are irrelevant to the frontend.
		if ( 'frontend' === $type ) {
			$excluded_options = array(
				'api_key',
				'plugin_reset',
			);
		}

		if ( 'admin' === $type ) {
			$excluded_options = array(
				'version',
			);
		}

		/**
		 * Filters the excluded options for the frontend or admin.
		 *
		 * @param array $excluded_options The excluded options.
		 * @param string $type The type of options to get.
		 * @since 1.0.0
		 */
		apply_filters( 'piggy_excluded_options', $excluded_options, $type );

		foreach ( $excluded_options as $option ) {
			unset( $options[ $option ] );
		}

		/**
		 * Filters the options for the frontend or admin right before they are outputted to the client.
		 *
		 * @param array $options The options.
		 * @param string $type The type of options to get.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'piggy_options', $options, $type );

		return $options;
	}

	/**
	 * Gets the options for the frontend.
	 */
	public function get_frontend_options_payload(): array {
		return $this->get_options_for_client( 'frontend' );
	}

	/**
	 * Gets the options for the admin.
	 */
	public function get_admin_options_payload( $bust_cache = false ): array {
		$options  = $this->get_options_for_client( 'admin', $bust_cache );
		$settings = self::default_settings();

		// Options are key value pairs by default, but we need them to be grouped by section.
		foreach ( $settings as $section_key => $section ) {
			foreach ( $section['fields'] as $field_key => $field ) {
				if ( isset( $options[ $field['id'] ] ) ) {
					$settings[ $section_key ]['fields'][ $field_key ]['value'] = $options[ $field['id'] ];
				} else {
					$settings[ $section_key ]['fields'][ $field_key ]['value'] = $field['default'];
				}
			}
		}

		$all_fields = array();

		// Omit sections from the payload..
		foreach ( $settings as $section ) {
			foreach ( $section['fields'] as $field ) {
				$all_fields[ $field['id'] ] = $field;
			}
		}

		/**
		 * Filters the options payload for the admin.
		 * This is used to pass the options to the frontend.
		 * The options are grouped by section.
		 *
		 * @param array $all_fields The options.
		 * @since 1.0.0
		 */
		apply_filters( 'piggy_admin_options_payload', $all_fields );

		return $all_fields;
	}

	/**
	 * Reset all options to their default values.
	 */
	public static function reset_settings() {
		$settings = self::default_settings();

		foreach ( $settings as $section ) {
			foreach ( $section['fields'] as $field ) {
				delete_option( self::$option_prefix . $field['id'] );
			}
		}
	}

	/**
	 * Checks if the option is a translatable text option.
	 *
	 * @param string $name The option name.
	 *
	 * @return bool
	 */
	private static function is_translatable_text_option($name) {
		$default_settings = self::default_settings();

		foreach ($default_settings as $section) {
			foreach ($section['fields'] as $field) {
				if (self::$option_prefix . $field['id'] === $name && $field['type'] === self::TRANSLATABLE_TEXT) {
					return true;
				}
			}
		}

		return false;
	}
}
