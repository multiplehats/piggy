<?php

namespace PiggyWP\Shortcodes;

class CartLauncherShortcode extends AbstractShortcode {
	/**
	 * Shortcode name within this namespace.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'cart_launcher';

	function get_assets() {
		return array();
	}

	public function init_hooks() {
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'fragments' ) );
	}

	/**
	 * Sets the shortcode default attributes.
	 *
	 * @return array Default attributes for the shortcode.
	 */
	public function get_shortcode_type_attributes(): array {
		return array(
			'icon'                 => 'piggy-icon-shopping-cart-line',
			'subtotal'             => 'true',
			'indicator'            => 'bubble',
			'indicator_hide_empty' => 'false',
			'wrapper'              => 'true',
			'shortcode'            => 'true',
			'menu_item'            => 'false',
			'classes'              => apply_filters(
				'piggy_cart_launcher_classes',
				array( 'piggy-cart__wrapper' )
			),
		);
	}

	/**
	 * Ajaxify short-code cart count.
	 *
	 * @param array $fragments Array of fragments.
	 *
	 * @return mixed
	 */
	public function fragments( array $fragments ): array {
		$has_cart = is_a( WC()->cart, 'WC_Cart' );

		if ( ! $has_cart ) {
			return $fragments;
		}

		$cart_count = WC()->cart->get_cart_contents_count();
		$sub_total  = WC()->cart->get_cart_subtotal();
		$cart_empty = 0 === $cart_count ? 'is-empty' : '';

		ob_start();
		echo '<span class="piggy-cart__container-counter ' . esc_attr( $cart_empty ) . '">' . sprintf( _n( '%d', '%d', $cart_count, 'piggy' ), $cart_count ) . '</span>'; // phpcs:ignore
		$cart_launcher_count = ob_get_clean();

		ob_start();
		echo '<span class="piggy-cart__container-text">' . wp_kses_post( $sub_total ) . '</span>';
		$cart_total = ob_get_clean();

		$fragments['.piggy-cart__container-counter'] = $cart_launcher_count;
		$fragments['.piggy-cart__container-text']    = $cart_total;

		return $fragments;
	}

	public function shortcode_output($attributes, $content = '') {
		if ( ! WC()->cart ) {
			return;
		}

		$args = wp_parse_args(
			$attributes,
			$this->get_shortcode_type_attributes()
		);

		$output = ob_start();

		$product_count = WC()->cart->get_cart_contents_count();
		$sub_total     = WC()->cart->get_cart_subtotal();

		if ( 'true' === $args['shortcode'] ) {
			$args['classes'][] = 'is-shortcode';
		}

		if ( 'true' === $args['menu_item'] ) {
			$args['classes'][] = 'is-menu';
		}

		if ( 'true' === $args['subtotal'] ) {
			$args['classes'][] = 'piggy-cart--show-subtotal-yes';
		}

		if ( $args['indicator'] ) {
			switch ( $args['indicator'] ) {
				case 'none':
					$args['classes'][] = 'piggy-cart--items-indicator-hide';
					break;
				case 'bubble':
					$args['classes'][] = 'piggy-cart--items-indicator-bubble';
					break;
				case 'plain':
					$args['classes'][] = 'piggy-cart--items-indicator-plain';
					break;
				default:
					$args['classes'][] = 'piggy-cart--items-indicator-bubble';
					break;
			}
		}

		if ( 'true' === $args['indicator_hide_empty'] && 0 === $product_count ) {
			$args['classes'][] = 'piggy-cart--empty-indicator-hide';
		}

		if ( 'true' === $args['wrapper'] ) {
			echo '<div class="' . esc_html( implode( ' ', array_filter( $args['classes'] ) ) ) . '">';
		}

		error_log(print_r($args, true));

		?>
		<div class="piggy-cart__toggle piggy-cart__container-wrapper" id="piggy-cart-launcher-<?php echo esc_attr( wp_unique_id() ); ?>">
			<div class="piggy-cart__container piggy-toggle-drawer">
				<span class="piggy-cart__container-icon">
					<i class="<?php echo esc_attr( $args['icon'] ); ?>" aria-hidden="true"></i>
					<span class="piggy-sr-only"><?php esc_html_e( 'Cart', 'piggy' ); ?></span>
					<span class="piggy-cart__container-counter">
						<?php echo esc_html( $product_count ); ?>
					</span>
				</span>
				<span class="piggy-cart__container-text"><?php echo wp_kses_post( $sub_total ); ?></span>
			</div>
		</div>
		<?php

		if ( $args['wrapper'] ) {
			echo '</div>';
		}

		$output = ob_get_clean();

		return $output;
	}
}
