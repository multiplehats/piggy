<?php

namespace Leat\Api\Routes\V1\Admin;

use Leat\Api\Exceptions\RouteException;

/**
 * Middleware class.
 *
 * @internal
 */
class Middleware {
	/**
	 * Ensure that the user is allowed to make this request.
	 *
	 * @throws RouteException If the user is not allowed to make this request.
	 * @return boolean
	 */
	public static function is_authorized() {
		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new RouteException( 'leat_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'leat' ), 403 );
			}
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		return true;
	}

	/**
	 * Ensure that the user is logged in
	 *
	 * @throws RouteException If the user is not logged in
	 * @return boolean
	 */
	public static function is_logged_in() {
		try {
			if ( ! is_user_logged_in() ) {
				throw new RouteException( 'leat_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'leat' ), 403 );
			}
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		return true;
	}
}
