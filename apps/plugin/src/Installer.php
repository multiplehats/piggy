<?php
namespace Leat;

use Leat\Api\Connection;

/**
 * Installer class.
 * Handles installation of Leat plugin dependencies.
 *
 * @internal
 */
class Installer {
	/**
	 * Initialize class features.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_create_tables' ) );
		add_action( 'admin_init', array( $this, 'maybe_migrate_piggy_to_leat' ) );
		// add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	public function maybe_create_tables() {
		global $wpdb;

		$schema_version    = 1;
		$db_schema_version = (int) get_option( 'leat_db_schema_version', 0 );

		if ( $db_schema_version >= $schema_version && 0 !== $db_schema_version ) {
			return;
		}

		$show_errors = $wpdb->hide_errors();
		$collate     = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$tables = [
			"CREATE TABLE {$wpdb->prefix}leat_reward_logs (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`wp_user_id` bigint(20) NOT NULL,
				`earn_rule_id` bigint(20) NOT NULL,
				`credits` bigint(20) NOT NULL,
				`timestamp` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $collate;"
		];

		foreach ( $tables as $create_table_sql ) {
			$table_name = $wpdb->prefix . $this->get_table_name_from_sql( $create_table_sql );
			$exists = $this->maybe_create_table( $table_name, $create_table_sql );

			if ( ! $exists ) {
				$this->add_create_table_notice( $table_name );
			}
		}

		if ( $show_errors ) {
			$wpdb->show_errors();
		}

		// Update succeeded. This is only updated when successful and validated.
		// $schema_version should be incremented when changes to schema are made within this method.
		update_option( 'leat_db_schema_version', $schema_version );
	}

	/**
	 * Extracts the table name from a CREATE TABLE SQL statement.
	 *
	 * @param string $create_table_sql Create table SQL statement.
	 * @return string Table name.
	 */
	private function get_table_name_from_sql( $create_table_sql ) {
		global $wpdb;

		if ( preg_match( '/CREATE TABLE ([^ ]+) \(/', $create_table_sql, $matches ) ) {
			return str_replace( $wpdb->prefix, '', $matches[1] );
		}
		return '';
	}

	/**
	 * Create database table, if it doesn't already exist.
	 *
	 * Based on admin/install-helper.php maybe_create_table function.
	 *
	 * @param string $table_name Database table name.
	 * @param string $create_sql Create database table SQL.
	 * @return bool False on error, true if already exists or success.
	 */
	protected function maybe_create_table( $table_name, $create_sql ) {
		global $wpdb;

		$cache_key = 'leat_table_exists_' . $table_name;
		$table_exists = wp_cache_get( $cache_key );

		if ( false === $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_exists = in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $table_name ), 0 ), true );
			wp_cache_set( $cache_key, $table_exists );
		}

		if ( $table_exists ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Table creation SQL is safe and requires direct query
		$wpdb->query( $create_sql );

		// Clear and refresh cache after table creation
		wp_cache_delete( $cache_key );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $table_name ), 0 ), true );
		wp_cache_set( $cache_key, $table_exists );

		return $table_exists;
	}

	/**
	 * Add a notice if table creation fails.
	 *
	 * @param string $table_name Name of the missing table.
	 */
	protected function add_create_table_notice( $table_name ) {
		add_action(
			'admin_notices',
			function() use ( $table_name ) {
				echo '<div class="error"><p>';
				printf(
					/* translators: %1$s table name, %2$s database user, %3$s database name. */
					esc_html__( 'Leat %1$s table creation failed. Does the %2$s user have CREATE privileges on the %3$s database?', 'leat-crm' ),
					'<code>' . esc_html( $table_name ) . '</code>',
					'<code>' . esc_html( DB_USER ) . '</code>',
					'<code>' . esc_html( DB_NAME ) . '</code>'
				);
				echo '</p></div>';
			}
		);
	}

	public function maybe_redirect_to_onboarding() {
		$api_key = get_option('leat_api_key', null);

		if ( get_option( 'leat_first_activation', false ) === false && $api_key !== null && $api_key !== '' ) {
			update_option( 'leat_first_activation', true );
			wp_redirect( admin_url( 'admin.php?page=leat#/onboarding' ) );
			exit;
		}
	}

	/**
     * Migrate data from piggy_ prefix to leat_ prefix during installation.
     */
    public function maybe_migrate_piggy_to_leat() {
        if ( get_option( 'leat_migration_complete', false ) ) {
            return;
        }

        global $wpdb;

        // Add caching for options query
        $cache_key = 'leat_piggy_options';
        $piggy_options = wp_cache_get( $cache_key );

        if ( false === $piggy_options ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $piggy_options = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                    'piggy_%'
                )
            );
            wp_cache_set( $cache_key, $piggy_options );
        }

        // Migrate options
        foreach ($piggy_options as $option) {
            $new_option_name = str_replace('piggy_', 'leat_', $option->option_name);
            update_option($new_option_name, $option->option_value);
            delete_option($option->option_name);
        }

        // Rename database tables
        $tables = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . 'piggy_%'
            )
        );

        foreach ($tables as $table) {
            $old_table_name = reset($table);
            $new_table_name = str_replace('piggy_', 'leat_', $old_table_name);

            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $new_table_name)) != $new_table_name) {
                $wpdb->query(
                    $wpdb->prepare(
                        "RENAME TABLE %s TO %s",
                        $old_table_name,
                        $new_table_name
                    )
                );

                if ($wpdb->last_error) {
                    // Use proper WordPress error logging
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        error_log( sprintf( 'Leat Migration: Failed to rename table %s to %s: %s', $old_table_name, $new_table_name, $wpdb->last_error ) );
                    }
                }
            } else {
                // Use proper WordPress error logging
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( sprintf( 'Leat Migration: Table %s already exists. Skipping rename operation.', $new_table_name ) );
                }
            }
        }

        // Migrate custom post types
        $this->migrate_custom_post_type('piggy_earn_rule', 'leat_earn_rule');
        $this->migrate_custom_post_type('piggy_spend_rule', 'leat_spend_rule');

        // Set flag to indicate migration is complete
        update_option('leat_migration_complete', true);

		// maybe deactivate piggy plugin
		deactivate_plugins('piggy/piggy.php');
    }

    private function migrate_custom_post_type($old_post_type, $new_post_type) {
        $posts = get_posts(array(
            'post_type' => $old_post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        foreach ($posts as $post) {
            // Update post type
            $post->post_type = $new_post_type;
            wp_update_post($post);

            // Update post meta
            $post_meta = get_post_meta($post->ID);
            foreach ($post_meta as $meta_key => $meta_values) {
                if (strpos($meta_key, '_piggy_') === 0) {
                    $new_meta_key = str_replace('_piggy_', '_leat_', $meta_key);
                    foreach ($meta_values as $meta_value) {
                        add_post_meta($post->ID, $new_meta_key, $meta_value);
                    }
                    delete_post_meta($post->ID, $meta_key);
                }
            }
        }
    }
}
