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

		$tables = [
			'leat_reward_logs',
		];

		foreach ( $tables as $table_name ) {
			$exists = $this->maybe_create_table( $table_name );

			if ( ! $exists ) {
				$this->add_create_table_notice( $table_name );
			}
		}

		if ( $show_errors ) {
			$wpdb->show_errors();
		}

		update_option( 'leat_db_schema_version', $schema_version );
	}

	/**
	 * Create database table, if it doesn't already exist.
	 *
	 * Based on admin/install-helper.php maybe_create_table function.
	 *
	 * @param string $table_name Database table name.
	 * @return bool False on error, true if already exists or success.
	 */
	protected function maybe_create_table( $table_name ) {
		global $wpdb;

		$cache_key    = 'leat_table_exists_' . $table_name;
		$table_exists = wp_cache_get( $cache_key );

		if ( false === $table_exists ) {
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$wpdb->prefix . $table_name
				)
			);
			wp_cache_set( $cache_key, (bool) $table_exists );
		}

		if ( $table_exists ) {
			return true;
		}

		// Execute the create table query using prepare
		$result = $wpdb->query(
			$wpdb->prepare(
				// Note: %1$s is for the table name, %2$s for collate
				'CREATE TABLE IF NOT EXISTS `%1$s` (
					`id` mediumint(9) NOT NULL AUTO_INCREMENT,
					`wp_user_id` bigint(20) NOT NULL,
					`earn_rule_id` bigint(20) NOT NULL,
					`credits` bigint(20) NOT NULL,
					`timestamp` datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
					PRIMARY KEY (id)
				) %2$s',
				$wpdb->prefix . $table_name,
				$wpdb->get_charset_collate()
			)
		);

		// Clear and refresh cache after table creation
		wp_cache_delete( $cache_key );
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->prefix . $table_name
			)
		);
		wp_cache_set( $cache_key, (bool) $table_exists );

		return (bool) $table_exists;
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
		$api_key = get_option( 'leat_api_key', null );

		if ( get_option( 'leat_first_activation', false ) === false && $api_key !== null && $api_key !== '' ) {
			update_option( 'leat_first_activation', true );
			wp_redirect( admin_url( 'admin.php?page=leat#/onboarding' ) );
			exit;
		}
	}
}
