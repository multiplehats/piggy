<?php
namespace Piggy;

/**
 * Installer class.
 * Handles installation of Blocks plugin dependencies.
 *
 * @internal
 */
class Installer {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Installation tasks ran on admin_init callback.
	 */
	public function install() {
		$this->maybe_create_tables();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	public function maybe_create_tables() {}

	/**
	 * Create database table, if it doesn't already exist.
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
					esc_html__( 'Piggy %1$s table creation failed. Does the %2$s user have CREATE privileges on the %3$s database?', 'piggy' ),
					'<code>' . esc_html( $table_name ) . '</code>',
					'<code>' . esc_html( DB_USER ) . '</code>',
					'<code>' . esc_html( DB_NAME ) . '</code>'
				);
				echo '</p></div>';
			}
		);
	}
}
