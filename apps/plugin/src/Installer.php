<?php
namespace PiggyWP;

use PiggyWP\Api\Connection;

/**
 * Installer class.
 * Handles installation of Piggy plugin dependencies.
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
		$db_schema_version = (int) get_option( 'piggy_db_schema_version', 0 );

		if ( $db_schema_version >= $schema_version && 0 !== $db_schema_version ) {
			return;
		}

		$show_errors = $wpdb->hide_errors();
		$collate     = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$tables = [
			"CREATE TABLE {$wpdb->prefix}piggy_reward_logs (
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
		update_option( 'piggy_db_schema_version', $schema_version );
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

		if ( in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ), 0 ), true ) ) {
			return true;
		}

		$wpdb->query( $create_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ), 0 ), true );
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
					esc_html__( 'Piggy %1$s table creation failed. Does the %2$s user have CREATE privileges on the %3$s database?', 'woo-gutenberg-products-block' ),
					'<code>' . esc_html( $table_name ) . '</code>',
					'<code>' . esc_html( DB_USER ) . '</code>',
					'<code>' . esc_html( DB_NAME ) . '</code>'
				);
				echo '</p></div>';
			}
		);
	}

	public function maybe_redirect_to_onboarding() {
		$api_key = get_option('piggy_api_key', null);

		if ( get_option( 'piggy_first_activation', false ) === false && $api_key !== null && $api_key !== '' ) {
			update_option( 'piggy_first_activation', true );
			wp_redirect( admin_url( 'admin.php?page=piggy#/onboarding' ) );
			exit;
		}
	}
}
