<?php
namespace PiggyWP;

use PiggyWP\Options;
use PiggyWP\Utils\AdminUtils;
use WC_Data_Store;

/**
 * AjaxController class.
 *
 * @since    5.0.0
 * @internal
 */
final class AjaxController {
	/**
	 * Contains options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Contains options.
	 *
	 * @var AdminUtils
	 */
	private $admin_utils;

	/**
	 * Constructor.
	 *
	 * @param Options $options   Options interface.
	 */
	public function __construct( Options $options, AdminUtils $admin_utils ) {
		$this->options     = $options;
		$this->admin_utils = $admin_utils;

		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		$actions = array(
			'search_products' => false,
			'get_products'    => false,
			'save_options'    => false,
			'reset_options'   => false,
		);

		// Register ajax actions. $with_nopriv fires non-authenticated Ajax actions for logged-out users.
		foreach ( $actions as $action => $with_nopriv ) {
			add_action( 'wp_ajax_piggy_' . $action, array( $this, $action ) );

			if ( $with_nopriv ) {
				add_action( 'wp_ajax_nopriv_piggy_' . $action, array( $this, $action ) );
			}
		}
	}

	/**
	 * Saves the options via ajax.
	 */
	public function save_options() {
		check_ajax_referer( 'piggy_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to do this.' );
		}

		$settings = isset( $_POST['data']['settings'] ) ? wp_unslash( $_POST['data']['settings'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $settings ) ) {
			wp_send_json_error( 'Invalid settings, must be an array.' );
		}

		$returned_options = array();

		try {
			$returned_options = $this->options->save_all_options( $settings );
			wp_send_json_success( $returned_options );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}

		wp_die();
	}

	/**
	 * Resets the options via ajax.
	 */
	public function reset_options() {
		check_ajax_referer( 'piggy_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to do this.' );
		}

		try {
			$this->options->reset_settings();
			wp_send_json_success();
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}

		wp_die();
	}

	/**
	 * Search for products and return json.
	 *
	 * @param string $term (default: '') Term to search.
	 * @param bool   $include_variations (default: false) Whether to include variations in results.
	 *
	 * @throws \Exception If the request is invalid.
	 */
	public function search_products( $term = '', $include_variations = false ) {
		check_ajax_referer( 'piggy_admin', 'nonce' );


		try {
			if ( empty( $term ) && isset( $_GET['term'] ) ) {
				$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
			}

			if ( empty( $term ) ) {
				throw new \Exception( __( 'No search term specified', 'piggy' ) );
			}

			if ( ! empty( $_GET['limit'] ) ) {
				$limit = absint( $_GET['limit'] );
			} else {
				/**
				 * Filter the number of products returned by the search.
				 *
				 * @since 1.0.0
				 */
				$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
			}

			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit );

			$product_objects = $this->admin_utils->filter_readable_products( $ids );
			$products        = array();

			$exclude_global_variable = isset( $_GET['exclude_global_variable'] ) ? sanitize_text_field( wp_unslash( $_GET['exclude_global_variable'] ) ) : 'no';

			foreach ( $product_objects as $product_object ) {
				if ( 'yes' === $exclude_global_variable && $product_object->is_type( 'variable' ) ) {
					continue;
				}

				$products[] = $this->admin_utils->get_admin_product_response( $product_object );
			}

			/**
			 * Filter the list of products returned by the search.
			 *
			 * @since 1.0.0
			 */
			wp_send_json_success( apply_filters( 'woocommerce_json_search_found_products', $products ) );
		} catch ( \Exception $ex ) {
			wp_send_json_error( $ex->getMessage() );

			return;
		}
	}

	/**
	 * Get products and return json.
	 *
	 * @param stirng $ids Comma separated IDs.
	 * @throws \Exception If the request is invalid.
	 */
	public function get_products( $ids ) {
		check_ajax_referer( 'piggy_admin', 'nonce' );

		try {
			if ( empty( $ids ) && isset( $_GET['ids'] ) ) {
				$ids = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
			}

			if ( empty( $ids ) ) {
				throw new \Exception( __( 'No product ids specified', 'piggy' ) );
			}

			$ids = explode( ',', $ids );

			$product_objects = $this->admin_utils->filter_readable_products( $ids );
			$products        = array();

			foreach ( $product_objects as $product_object ) {
				$products[] = $this->admin_utils->get_admin_product_response( $product_object );
			}

			/**
			 * Filter the list of products returned by the search.
			 *
			 * @since 1.0.0
			 */
			wp_send_json_success( apply_filters( 'woocommerce_json_search_found_products', $products ) );
		} catch ( \Exception $ex ) {
			wp_send_json_error( $ex->getMessage() );

			return;
		}
	}
}
