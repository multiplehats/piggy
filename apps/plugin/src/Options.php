<?php

namespace Piggy;

/**
 * Contains all the default options and options from the database.
 */
class Options {
	const PIGGY_VERSION = 'piggy_version';

	// Fields.
	const CHECKBOX    = 'checkbox';
	const TEXT        = 'text';
	const SELECT      = 'select';
	const TEXTAREA    = 'textarea';
	const MULTISELECT = 'multiselect';
	const COLOR       = 'color';
	const NUMBER      = 'number';
	const OBJECT      = 'object';
	const API_KEY     = 'api_key';

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
						'type'    => self::CHECKBOX,
						'label'   => __( 'Enable plugin', 'piggy' ),
						'tooltip' => __( 'If you disable this, the plugin will stop working on the front-end of your website. This is useful if you temporarily want to disable Piggy without deactivating the entire plugin.', 'piggy' ),
					),
					array(
						'id'      => 'plugin_reset',
						'default' => 'off',
						'type'    => self::CHECKBOX,
						'label'   => __( 'Delete plugin settings upon deactivation', 'piggy' ),
						'tooltip' => __( 'This wlll delete all plugins settings upon deactivation. Use with caution!', 'piggy' ),
					),
				),
			);

			self::$default_settings['api_key'] = array(
				'title'  => __( 'API Key', 'piggy' ),
				'fields' => array(
					array(
						'id'      => 'api_key',
						'default' => '',
						'type'    => self::API_KEY,
						'label'   => __( 'API Key', 'piggy' ),
						'tooltip' => __( 'Enter your API key here.', 'piggy' ),
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
	public static function get( $id, $prefix = true ) {
		if ( ! self::has( $id, true ) ) {
			return self::get_default( $id );
		}

		if ( $prefix ) {
			$id = self::$option_prefix . $id;
		}

		return get_option( $id );
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
				$options[ $result['option_name'] ] = $result['option_value'];
			}

			$default_options = self::default_settings();

			foreach ( $default_options as $section ) {
				foreach ( $section['fields'] as $field ) {
					$name = self::$option_prefix . $field['id'];

					// If we're missing any options, fall back to the default.
					if ( ! isset( $options[ $name ] ) ) {
						$options[ $name ] = $field['default'];
					}

					// If the type is an API key, we need to only return the first 5 characters.
					if ( self::API_KEY === $field['type'] ) {
						// Get the total length of the API key, divide by two, and hide the second half with asterisks.
						$options[ $name ] = substr( $options[ $name ], 0, strlen( $options[ $name ] ) / 2 ) . str_repeat( '*', strlen( $options[ $name ] ) / 2 );
					}

					// If type is a number, convert to int.
					if ( self::NUMBER === $field['type'] ) {
						$options[ $name ] = (int) $options[ $name ];
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
	public function save_all_options( array $options, bool $prefix = true ) {
		foreach ( $options as $name => $value ) {
			if ( $prefix ) {
				$name = self::$option_prefix . $name;
			}

			update_option( $name, $value );
		}

		return $this->get_options_for_client( 'admin', true );
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
}
