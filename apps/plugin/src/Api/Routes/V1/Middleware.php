<?php

namespace Leat\Api\Routes\V1;

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
				throw new RouteException( 'leat_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'leat-crm' ), 403 );
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
	 * Publicly accessible middleware.
	 *
	 * @return boolean
	 */
	public static function is_public() {
		return true;
	}

	/**
	 * Ensure that the user is logged in
	 *
	 * @throws RouteException If the user is not logged in.
	 * @return boolean
	 */
	public static function is_logged_in() {
		try {
			if ( ! is_user_logged_in() ) {
				throw new RouteException( 'leat_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'leat-crm' ), 403 );
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
	 * Validate if the user is logged in, and that the incoming user id matches the current user id.
	 *
	 * @param int $user_id The user id to validate.
	 * @throws RouteException If the user is not logged in, or the user id does not match the current user id.
	 * @return boolean
	 */
	public static function is_valid_user( $user_id ) {
		try {
			if ( ! self::is_logged_in() || get_current_user_id() !== $user_id ) {
				throw new RouteException( 'leat_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'leat-crm' ), 403 );
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
