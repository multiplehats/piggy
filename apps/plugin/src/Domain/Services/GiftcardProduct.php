<?php

/**
 * Giftcard Product Service
 *
 * Handles all functionality related to gift card products in WooCommerce, including:
 * - Product settings and metadata
 * - Order processing and gift card creation
 * - Email notifications
 * - Refund and withdrawal handling
 *
 * @package Leat

 */

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;

/**
 * Class GiftcardProduct
 *
 * Main service class for handling gift card product functionality.
 *
 */
class GiftcardProduct
{
	/**
	 * API connection instance.
	 *

	 * @var Connection
	 */
	private Connection $connection;

	/**
	 * Plugin settings instance.
	 *

	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Logger instance.
	 *

	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *

	 *
	 * @param Connection $connection API connection instance.
	 * @param Settings   $settings   Plugin settings instance.
	 */
	public function __construct(Connection $connection, Settings $settings)
	{
		$this->logger = new Logger();

		$this->connection = $connection;
		$this->settings   = $settings;
	}


	/**
	 * Initialize the gift card product functionality.
	 */
	public function init()
	{
		// Add giftcard product settings.
		add_filter('woocommerce_product_data_tabs', [$this, 'add_giftcard_product_tab']);
		add_action('woocommerce_product_data_panels', [$this, 'add_giftcard_program_settings']);
		add_action('woocommerce_process_product_meta', [$this, 'save_giftcard_program_settings']);
		add_filter('woocommerce_order_item_display_meta_value', [$this, 'format_giftcard_meta_display'], 10, 3);

		// Process giftcards after order status changes.
		$trigger_status = $this->settings->get_setting_value_by_id('giftcard_order_status');
		add_action("woocommerce_order_status_{$trigger_status}", [$this, 'process_giftcard_order'], 10, 1);

		// Recipient email.
		add_action('woocommerce_before_order_notes', [$this, 'add_giftcard_recipient_field']);
		add_action('woocommerce_checkout_update_order_meta', [$this, 'save_giftcard_recipient_email']);
		add_action('woocommerce_checkout_process', [$this, 'validate_giftcard_recipient_email']);

		// Handle refunds for giftcard orders.
		add_action('woocommerce_order_item_add_action_buttons', [$this, 'add_refund_field_script']);

		$withdraw_statuses = $this->settings->get_setting_value_by_id('giftcard_withdraw_order_statuses');

		foreach ($withdraw_statuses as $status => $enabled) {
			if ('on' === $enabled) {
				if ('refunded' === $status) {
					add_action('woocommerce_order_refunded', [$this, 'handle_giftcard_withdrawal_refund'], 10, 2);
				} else {
					add_action('woocommerce_order_status_' . $status, [$this, 'handle_giftcard_withdrawal'], 10, 1);
				}
			}
		}

		add_action('woocommerce_blocks_loaded', [$this, 'register_giftcard_recipient_field_for_blocks']);
	}

	/**
	 * Check if there is a giftcard in the cart.
	 *
	 * @return int|false The quantity of giftcards in the cart, or false if there are no giftcards.
	 */
	private function has_giftcard_in_cart()
	{
		// Check if WooCommerce is loaded and cart is available
		if (!function_exists('WC') || !WC()->cart || !did_action('wp_loaded')) {
			return false;
		}

		foreach (WC()->cart->get_cart() as $cart_item) {
			if (get_post_meta($cart_item['product_id'], '_leat_giftcard_program_uuid', true)) {
				return $cart_item['quantity'];
			}
		}

		return false;
	}

	public function add_giftcard_product_tab($tabs)
	{
		$tabs['leat_giftcard'] = [
			'label'  => __('Giftcard Settings', 'leat-crm'),
			'target' => 'leat_giftcard_product_data',
			'class'  => [],
		];
		return $tabs;
	}

	public function add_giftcard_program_settings()
	{
		global $post;

		// Verify user permissions.
		if (! current_user_can('edit_products')) {
			return;
		}

		wp_nonce_field('leat_giftcard_program_settings', 'leat_giftcard_program_nonce');

		echo '<div id="' . esc_attr('leat_giftcard_product_data') . '" class="panel woocommerce_options_panel">';

		$programs = $this->connection->list_giftcard_programs();
		$options  = ['' => esc_html__('Select a program', 'leat-crm')];
		if ($programs) {
			foreach ($programs as $program) {
				$options[esc_attr($program['uuid'])] = esc_html($program['name']);
			}
		}

		woocommerce_wp_select(
			[
				'id'          => '_leat_giftcard_program_uuid',
				'label'       => esc_html__('Giftcard Program', 'leat-crm'),
				'description' => esc_html__('Select the giftcard program this product is connected to', 'leat-crm'),
				'desc_tip'    => true,
				'options'     => $options,
				'value'       => esc_attr(get_post_meta($post->ID, '_leat_giftcard_program_uuid', true)),
			]
		);

		echo '</div>';
	}

	public function save_giftcard_program_settings($post_id)
	{
		if (
			! isset($_POST['leat_giftcard_program_nonce']) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['leat_giftcard_program_nonce'])), 'leat_giftcard_program_settings')
		) {
			return;
		}

		if (! current_user_can('edit_products')) {
			return;
		}

		$program_uuid = isset($_POST['_leat_giftcard_program_uuid'])
			? sanitize_text_field(wp_unslash($_POST['_leat_giftcard_program_uuid']))
			: '';
		update_post_meta($post_id, '_leat_giftcard_program_uuid', $program_uuid);
	}

	/**
	 * Register the giftcard recipient field for block checkout.
	 *
	 * @return void
	 */
	public function register_giftcard_recipient_field_for_blocks()
	{
		$giftcard_quantity = $this->has_giftcard_in_cart();

		if ($giftcard_quantity) {
			woocommerce_register_additional_checkout_field([
				'id' => 'leat/giftcard-recipient-email',
				'label' => __('Gift card recipient email', 'leat-crm'),
				'location' => 'contact',
				'type' => 'text',
				'required' => true,
				'attributes' => [
					'type' => 'email',
					'autocomplete' => 'email',
				],
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => function ($field_value) {
					if (!is_email($field_value)) {
						return new \WP_Error(
							'invalid_giftcard_recipient_email',
							__('Please enter a valid email address for the gift card recipient.', 'leat-crm')
						);
					}
					return true;
				}
			]);

			if ($giftcard_quantity > 1) {
				// Add notice through WooCommerce notice system
				wc_add_notice(
					sprintf(
						/* translators: %d: number of gift cards */
						__('You are purchasing %d gift cards. All gift cards will be sent to the same gift card recipient email address. If you want to send gift cards to different recipients, please place separate orders.', 'leat-crm'),
						$giftcard_quantity
					),
					'notice'
				);
			}
		}

		add_action('woocommerce_set_additional_field_value', function ($key, $value, $group, $wc_object) {
			if ('leat/giftcard-recipient-email' !== $key) {
				return;
			}

			$this->logger->info('Processing gift card recipient field save', [
				'key' => $key,
				'value' => $value,
				'group' => $group,
				'object_id' => $wc_object->get_id()
			]);

			if ($wc_object instanceof \WC_Order) {
				$wc_object->update_meta_data('_giftcard_recipient_email', sanitize_email($value));
				$wc_object->save();

				$this->logger->info('Successfully saved gift card recipient email to order', [
					'order_id' => $wc_object->get_id(),
					'email' => $value
				]);
			}
		});
	}

	/**
	 * For legacy checkout.
	 */
	public function add_giftcard_recipient_field($checkout)
	{
		$giftcard_quantity = $this->has_giftcard_in_cart();

		if ($giftcard_quantity) {
			wp_nonce_field('leat_giftcard_recipient', 'leat_giftcard_recipient_nonce');

			woocommerce_form_field(
				'giftcard_recipient_email',
				[
					'type'        => 'email',
					'class'       => ['form-row-wide'],
					'label'       => __('Gift Card Recipient Email', 'leat-crm'),
					'placeholder' => __('Enter recipient email address', 'leat-crm'),
					'required'    => true,
				],
				esc_attr($checkout->get_value('giftcard_recipient_email'))
			);

			if ($giftcard_quantity > 1) {
				printf(
					'<p class="giftcard-notice"><em>%s</em></p>',
					sprintf(
						/* translators: %d: number of gift cards */
						esc_html__('Important: You are purchasing %d gift cards. All gift cards will be sent to the same recipient email address entered above. If you want to send gift cards to different recipients, please place separate orders.', 'leat-crm'),
						esc_html($giftcard_quantity)
					)
				);
			}
		}
	}

	/**
	 * For legacy checkout.
	 */
	public function save_giftcard_recipient_email($order_id)
	{
		if (
			! isset($_POST['leat_giftcard_recipient_nonce']) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['leat_giftcard_recipient_nonce'])), 'leat_giftcard_recipient')
		) {
			return;
		}

		if (! current_user_can('edit_shop_orders')) {
			return;
		}

		if (! empty($_POST['giftcard_recipient_email'])) {
			update_post_meta(
				$order_id,
				'_giftcard_recipient_email',
				sanitize_email(wp_unslash($_POST['giftcard_recipient_email']))
			);
		}
	}

	private function send_giftcard_email($giftcard_uuid, $recipient_email)
	{
		try {
			// Sending a giftcard email requires a Leat contact.
			$contact = $this->connection->create_contact($recipient_email);

			$response = $this->connection->send_giftcard_email($giftcard_uuid, $contact['uuid']);

			$this->logger->info(
				'Sent giftcard email',
				[
					'giftcard_uuid'   => $giftcard_uuid,
					'recipient_email' => $recipient_email,
					'response'        => $response,
				]
			);

			return true;
		} catch (\Exception $e) {
			$this->logger->error(
				'Failed to send giftcard email',
				[
					'giftcard_uuid'   => $giftcard_uuid,
					'recipient_email' => $recipient_email,
					'error'           => $e->getMessage(),
				]
			);
			return false;
		}
	}

	/**
	 * Calculate the giftcard amount based on the product price or WPCleverWoonp integration.
	 *
	 * @param WC_Product            $product The product object.
	 * @param WC_Order_Item_Product $item The order item object.
	 * @param int                   $quantity The quantity of the product.
	 * @param int                   $order_id The order ID.
	 * @return int The amount in cents.
	 */
	private function calculate_giftcard_amount($product, $item, $quantity, $order_id)
	{
		/**
		 * WPC Name Your Price for WooCommerce integration.
		 *
		 * @see https://wordpress.org/plugins/wpc-name-your-price/
		 */
		if (class_exists('WPCleverWoonp') && $product->is_type('simple')) {
			$amount_in_cents = ($item->get_total() / $quantity) * 100;

			return $amount_in_cents;
		}

		// Default WooCommerce pricing (simple, and variable products).
		return $product->get_price() * 100;
	}

	/**
	 * Process giftcard orders.
	 *
	 * @param int $order_id The order ID.
	 */
	public function process_giftcard_order($order_id)
	{
		$order = wc_get_order($order_id);

		// Check if we've already processed this order.
		if (get_post_meta($order_id, '_leat_giftcards_created', true)) {
			$this->logger->info('Giftcards already created for order', ['order_id' => $order_id]);
			OrderNotes::add_warning($order, 'Attempted to process gift cards again, but they were already created.');
			return;
		}

		$recipient_email = get_post_meta($order_id, '_giftcard_recipient_email', true);

		$has_giftcards = false;

		foreach ($order->get_items() as $item) {
			/**
			 * Process each order item to create gift cards.
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product      = $item->get_product();
			$product_id   = $product->get_parent_id() ?: $product->get_id();
			$program_uuid = get_post_meta($product_id, '_leat_giftcard_program_uuid', true);

			if ($program_uuid) {
				$has_giftcards = true;
				$quantity       = $item->get_quantity();
				$amount_in_cents = $this->calculate_giftcard_amount($product, $item, $quantity, $order_id);

				OrderNotes::add(
					$order,
					sprintf(
						// translators: 1: quantity of gift cards, 2: amount in cents, 3: product name.
						__('Starting to create %1$d gift card(s) with amount %2$s for %3$s.', 'leat-crm'),
						$quantity,
						$amount_in_cents / 100,
						$product->get_name()
					)
				);

				for ($i = 0; $i < $quantity; $i++) {
					try {
						$data = $this->create_giftcard($program_uuid, $amount_in_cents);

						if ($data) {
							// Store giftcard data in order item meta.
							$item->add_meta_data('_leat_giftcard_tx_uuid_' . ($i + 1), $data['tx']['uuid']);
							$item->add_meta_data('_leat_giftcard_hash_' . ($i + 1), $data['giftcard']['hash']);
							$item->add_meta_data('_leat_giftcard_uuid_' . ($i + 1), $data['giftcard']['uuid']);
							$item->add_meta_data('_leat_giftcard_id_' . ($i + 1), $data['giftcard']['id']);
							$item->add_meta_data('_leat_giftcard_tx_id_' . ($i + 1), $data['tx']['id']);

							$giftcard_uuid = $data['giftcard']['uuid'];
							$giftcard_id   = $data['giftcard']['id'];
							$giftcard_hash = $data['giftcard']['hash'];

							if (! $giftcard_uuid) {
								$error_message = 'Failed to create gift card - UUID not found';
								OrderNotes::add_error($order, $error_message);
								$this->logger->error(
									$error_message,
									[
										'order_id'     => $order_id,
										'program_uuid' => $program_uuid,
									]
								);
								continue;
							}

							// Add customer-facing note with the giftcard hash.
							$order->add_order_note(
								sprintf(
									// translators: 1: giftcard hash.
									__('Gift Card Code: %s', 'leat-crm'),
									$giftcard_hash
								),
								true
							);

							OrderNotes::add_success(
								$order,
								sprintf(
									// translators: 1: giftcard id, 2: amount in cents.
									__('Gift card #%1$s created with amount %2$s successfully.', 'leat-crm'),
									$giftcard_id,
									$amount_in_cents / 100
								)
							);

							// Send email if recipient email exists.
							if ($recipient_email && $giftcard_uuid) {
								$email_sent = $this->send_giftcard_email($giftcard_uuid, $recipient_email);

								if ($email_sent) {
									OrderNotes::add_success(
										$order,
										sprintf(
											// translators: 1: giftcard id, 2: recipient email.
											__('Gift card #%1$s email sent to %2$s.', 'leat-crm'),
											$giftcard_id,
											$recipient_email
										)
									);
								} else {
									OrderNotes::add_error(
										$order,
										sprintf(
											// translators: 1: giftcard id, 2: recipient email.
											__('Failed to send gift card #%1$s email to %2$s.', 'leat-crm'),
											$giftcard_id,
											$recipient_email
										)
									);
								}
							} else {
								OrderNotes::add_warning(
									$order,
									__('No recipient email found for gift card.', 'leat-crm')
								);
							}

							$item->save();

							$this->logger->info(
								'Created giftcard',
								[
									'order_id'         => $order_id,
									'program_uuid'     => $program_uuid,
									'giftcard_uuid'    => $data['giftcard']['uuid'],
									'giftcard_tx_uuid' => $data['tx']['uuid'],
									'amount_in_cents'  => $amount_in_cents,
								]
							);
						}
					} catch (\Exception $e) {
						OrderNotes::add_error(
							$order,
							sprintf(
								// translators: 1: error message.
								__('Error creating gift card: %1$s', 'leat-crm'),
								$e->getMessage()
							)
						);
						$this->logger->error(
							$error_message,
							[
								'order_id'     => $order_id,
								'program_uuid' => $program_uuid,
							]
						);
					}
				}
			}
		}

		if (isset($has_giftcards) && $has_giftcards) {
			update_post_meta($order_id, '_leat_giftcards_created', true);
			OrderNotes::add_success($order, __('Gift card processing completed.', 'leat-crm'));
		}
	}

	private function create_giftcard($program_uuid, $amount_in_cents)
	{
		try {
			$giftcard = $this->connection->create_giftcard($program_uuid);

			$transaction = $this->connection->create_giftcard_transaction($giftcard['uuid'], $amount_in_cents);

			return [
				'tx'       => $transaction,
				'giftcard' => $giftcard,
			];
		} catch (\Exception $e) {
			$this->logger->error(
				'Error creating giftcard',
				[
					'program_uuid' => $program_uuid,
					'error'        => $e->getMessage(),
				]
			);
			throw $e;
		}
	}

	public function format_giftcard_meta_display($display_value, $meta, $item)
	{
		$meta_key = $meta->key;
		if (! str_starts_with($meta_key, '_leat_giftcard_id_')) {
			return $display_value;
		}

		if (! str_starts_with($meta->key, '_leat_giftcard_id_')) {
			return $display_value;
		}

		$giftcard_id = $meta->value;
		return sprintf(
			// translators: 1: giftcard id, 2: giftcard id.
			__('<a href="%1$s" target="_blank">View Giftcard #%2$s</a>', 'leat-crm'),
			esc_url('https://business.leat.com/store/giftcards/program/cards?card_id=' . $giftcard_id),
			esc_html($giftcard_id)
		);
	}

	public function validate_giftcard_recipient_email()
	{
		$has_giftcard = false;
		foreach (WC()->cart->get_cart() as $cart_item) {
			$product_id = $cart_item['product_id'];
			if (get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
				$has_giftcard = true;
				break;
			}
		}

		// Validate recipient email if cart has giftcard.
		if ($has_giftcard) {
			if (
				! isset($_POST['leat_giftcard_recipient_nonce']) ||
				! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['leat_giftcard_recipient_nonce'])), 'leat_giftcard_recipient')
			) {
				return;
			}

			if (empty($_POST['giftcard_recipient_email'])) {
				wc_add_notice(__('Gift Card Recipient Email is required.', 'leat-crm'), 'error');
			}
		}
	}

	/**
	 * Handle refunds for giftcard orders.
	 *
	 * @param int $order_id The order ID.
	 * @param int $refund_id The refund ID.
	 */
	public function handle_giftcard_withdrawal_refund($order_id, $refund_id)
	{
		$order  = wc_get_order($order_id);
		$refund = wc_get_order($refund_id);

		if (! $order || ! $refund) {
			return;
		}

		// Check if this refund has already been processed.
		if (get_post_meta($refund_id, '_leat_giftcards_reversed', true)) {
			OrderNotes::add_warning($order, 'Gift cards for this refund have already been processed.');
			return;
		}

		foreach ($refund->get_items() as $refund_item) {
			$refunded_qty = abs($refund_item->get_quantity());
			if ($refunded_qty <= 0) {
				continue;
			}

			// Get the original order item.
			$original_item = $this->find_matching_refunded_order_item($order, $refund_item);
			if (! $original_item) {
				continue;
			}

			/**
			 * WooCommerce product object.
			 *
			 * @var \WC_Product $product
			 */
			$product = $original_item->get_product();

			$product_id = $product->get_parent_id() ?: $product->get_id();

			if (! get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
				continue;
			}

			// Calculate refund percentage for this item.
			$refund_percentage = abs($refund_item->get_total()) / $original_item->get_total();

			// Process refund for each giftcard in the item.
			for ($i = 1; $i <= $refunded_qty; $i++) {
				$tx_uuid       = $original_item->get_meta('_leat_giftcard_tx_uuid_' . $i);
				$giftcard_uuid = $original_item->get_meta('_leat_giftcard_uuid_' . $i);
				$giftcard_id   = $original_item->get_meta('_leat_giftcard_id_' . $i);

				// Check if this specific gift card has already been reversed.
				if ($original_item->get_meta('_leat_giftcard_reversed_' . $i)) {
					OrderNotes::add_warning(
						$order,
						sprintf(
							// translators: 1: giftcard id.
							__('Gift card #%1$s has already been reversed.', 'leat-crm'),
							$giftcard_id ?: $i
						)
					);
					continue;
				}

				if (! $tx_uuid || ! $giftcard_uuid) {
					OrderNotes::add_error(
						$order,
						sprintf(
							// translators: 1: giftcard id.
							__('Could not process refund for gift card #%1$s - missing transaction data.', 'leat-crm'),
							$giftcard_id ?: $i
						)
					);
					continue;
				}

				try {
					if ($refund_percentage >= 1) {
						// Full refund for this giftcard.
						$result = $this->connection->reverse_giftcard_transaction($tx_uuid);
					} else {
						// Partial refund.
						$original_amount = $original_item->get_total() * 100; // Convert to cents.
						$refund_amount   = round($original_amount * $refund_percentage);
						$result          = $this->connection->create_giftcard_refund_transaction($giftcard_uuid, $refund_amount);
					}

					if ($result) {
						// Store refund transaction data.
						$refund_item->add_meta_data(
							'_leat_giftcard_refund_tx_uuid_' . $i,
							$result['uuid']
						);
						$refund_item->save();

						OrderNotes::add_success(
							$order,
							sprintf(
								// translators: 1: refund percentage, 2: giftcard id.
								__('Deducted %1$s%% from gift card #%2$s.', 'leat-crm'),
								round($refund_percentage * 100),
								$giftcard_id
							)
						);

						$this->logger->info(
							'Processed giftcard refund',
							[
								'order_id'          => $order_id,
								'refund_id'         => $refund_id,
								'giftcard_id'       => $giftcard_id,
								'giftcard_uuid'     => $giftcard_uuid,
								'refund_percentage' => $refund_percentage,
								'refund_tx_uuid'    => $result['uuid'],
							]
						);

						// Mark this specific gift card as reversed.
						$original_item->add_meta_data('_leat_giftcard_reversed_' . $i, true);
						$original_item->save();
					} else {
						OrderNotes::add_error(
							$order,
							sprintf(
								// translators: 1: giftcard id.
								__('Failed to process refund for gift card #%1$s.', 'leat-crm'),
								$giftcard_id
							)
						);
					}
				} catch (\Exception $e) {
					OrderNotes::add_error(
						$order,
						sprintf(
							// translators: 1: giftcard id, 2: error message.
							__('Error processing refund for gift card #%1$s: %2$s', 'leat-crm'),
							$giftcard_id,
							$e->getMessage()
						)
					);
					$this->logger->error(
						'Error processing giftcard refund',
						[
							'order_id'    => $order_id,
							'refund_id'   => $refund_id,
							'giftcard_id' => $giftcard_id,
							'error'       => $e->getMessage(),
						]
					);
				}
			}
		}

		// Mark this refund as processed.
		update_post_meta($refund_id, '_leat_giftcards_reversed', true);
	}

	public function handle_giftcard_withdrawal($order_id)
	{
		$order = wc_get_order($order_id);

		// Check if withdrawals have already been processed for this order.
		if (get_post_meta($order_id, '_leat_giftcards_reversed', true)) {
			OrderNotes::add_warning($order, 'Gift cards for this order have already been reversed.');
			return;
		}

		foreach ($order->get_items() as $item) {
			/**
			 * Process each order item.
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product      = $item->get_product();
			$product_id   = $product->get_parent_id() ?: $product->get_id();
			$program_uuid = get_post_meta($product_id, '_leat_giftcard_program_uuid', true);

			if (! $program_uuid) {
				continue;
			}

			$quantity = $item->get_quantity();

			for ($i = 1; $i <= $quantity; $i++) {
				$tx_uuid       = $item->get_meta('_leat_giftcard_tx_uuid_' . $i);
				$giftcard_uuid = $item->get_meta('_leat_giftcard_uuid_' . $i);
				$giftcard_id   = $item->get_meta('_leat_giftcard_id_' . $i);

				// Check if this specific gift card has already been reversed.
				if ($item->get_meta('_leat_giftcard_reversed_' . $i)) {
					OrderNotes::add_warning(
						$order,
						sprintf(
							// translators: 1: giftcard id.
							__('Gift card #%1$s has already been reversed.', 'leat-crm'),
							$giftcard_id ?: $i
						)
					);
					continue;
				}

				if (! $tx_uuid || ! $giftcard_uuid) {
					OrderNotes::add_error(
						$order,
						sprintf(
							// translators: 1: giftcard id.
							__('Could not process withdrawal for gift card #%1$s - missing transaction data.', 'leat-crm'),
							$giftcard_id ?: $i
						)
					);
					continue;
				}

				try {
					$result = $this->connection->reverse_giftcard_transaction($tx_uuid);

					if ($result) {
						$item->add_meta_data(
							'_leat_giftcard_withdrawal_tx_uuid_' . $i,
							$result['uuid']
						);
						$item->save();

						OrderNotes::add_success(
							$order,
							sprintf(
								// translators: 1: giftcard id.
								__('Gift card #%1$s withdrawn successfully.', 'leat-crm'),
								$giftcard_id
							)
						);

						$this->logger->info(
							'Processed giftcard withdrawal',
							[
								'order_id'      => $order_id,
								'giftcard_id'   => $giftcard_id,
								'giftcard_uuid' => $giftcard_uuid,
								'tx_uuid'       => $result['uuid'],
							]
						);

						// Mark this specific gift card as reversed.
						$item->add_meta_data('_leat_giftcard_reversed_' . $i, true);
						$item->save();
					} else {
						OrderNotes::add_error(
							$order,
							sprintf(
								// translators: 1: giftcard id.
								__('Failed to process withdrawal for gift card #%1$s.', 'leat-crm'),
								$giftcard_id
							)
						);
					}
				} catch (\Exception $e) {
					OrderNotes::add_error(
						$order,
						sprintf(
							// translators: 1: giftcard id, 2: error message.
							__('Error processing withdrawal for gift card #%1$s: %2$s', 'leat-crm'),
							$giftcard_id,
							$e->getMessage()
						)
					);
					$this->logger->error(
						'Error processing giftcard withdrawal',
						[
							'order_id'    => $order_id,
							'giftcard_id' => $giftcard_id,
							'error'       => $e->getMessage(),
						]
					);
				}
			}
		}

		// Mark the entire order as processed for withdrawals.
		update_post_meta($order_id, '_leat_giftcards_reversed', true);
	}

	/**
	 * Find the matching original order item for a refund item
	 *
	 * @param WC_Order              $order The original order.
	 * @param WC_Order_Item_Product $refund_item The refund item.
	 * @return WC_Order_Item_Product|null
	 */
	private function find_matching_refunded_order_item($order, $refund_item)
	{
		foreach ($order->get_items() as $order_item) {
			if ($order_item->get_product_id() === $refund_item->get_product_id()) {
				return $order_item;
			}
		}
		return null;
	}

	/**
	 * Ensure the refund amount field is disabled for gift card orders.
	 * Otherwise the shop admin can accidentally refund the entire order or partially refund an order, and we can't handle that.
	 */
	public function add_refund_field_script($order_id)
	{
		$order        = wc_get_order($order_id);
		$has_giftcard = false;

		foreach ($order->get_items() as $item) {
			/**
			 * Process each order item to check if it has a gift card.
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product    = $item->get_product();
			$product_id = $product->get_parent_id() ?: $product->get_id();

			if (get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
				$has_giftcard = true;
				break;
			}
		}

		if ($has_giftcard) {
			wp_enqueue_script('jquery');

			$script = sprintf(
				'jQuery(document).ready(function($) {
					const refundAmount = $("#refund_amount");
					refundAmount.prop("readonly", true);
					refundAmount.after("<p class=\"description\">%s</p>");
				});',
				esc_js(__('This order contains a gift card. Gift card orders must be refunded at the line item level. Please use the quantity and amount fields above to process refunds for individual gift cards.', 'leat-crm'))
			);

			wp_add_inline_script('jquery', $script);
		}
	}
}
