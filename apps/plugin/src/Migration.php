<?php
namespace Leat;

/**
 * Takes care of the migrations.
 *
 * @since 2.5.0
 */
class Migration {
	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * Please note that these functions are invoked when Leat is updated from a previous version,
	 * but NOT when Leat is newly installed.
	 *
	 * @var array
	 */
	private $db_upgrades = array(
		'0.2.0' => array(
			'leat_update_020_migrate_piggy_to_leat_prefix',
		),
	);

	/**
	 * Runs all the necessary migrations.
	 *
	 * @var array
	 */
	public function run_migrations() {
		$current_db_version = get_option( 'leat_version' );

		if ( empty( $current_db_version ) ) {
			return;
		}

		foreach ( $this->db_upgrades as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$this->{$update_callback}();
				}
			}
		}
	}

	/**
	 * Migrate options and database tables from piggy_ prefix to leat_ prefix.
	 */
	public static function leat_update_020_migrate_piggy_to_leat_prefix() {
		global $wpdb;

		// Migrate options
		$piggy_options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'piggy_%'");
		foreach ($piggy_options as $option) {
			$new_option_name = str_replace('piggy_', 'leat_', $option->option_name);
			update_option($new_option_name, $option->option_value);
			delete_option($option->option_name);
		}

		// Rename database tables
		$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}piggy_%'");
		foreach ($tables as $table) {
			$old_table_name = reset($table);
			$new_table_name = str_replace('piggy_', 'leat_', $old_table_name);
			$wpdb->query("RENAME TABLE {$old_table_name} TO {$new_table_name}");
		}
	}
}
