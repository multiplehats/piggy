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
		// '1.0.0' => array(
		// 'leat_update_example_callback',
		// ),
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

		if ( empty( $this->db_upgrades ) ) {
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

	// /**
	// * Example callback.
	// */
	// public static function leat_update_example_callback() {
	// global $wpdb;

	// Do work.
	// }
}
