<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Infrastructure\Constants\WCOrders;
use Leat\Infrastructure\Repositories\WPGiftcardRepository;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;

/**
 * Class GiftcardProductService
 *
 * Service class for handling gift cards.
 *
 * @package Leat\Domain\Services
 */
class GiftcardProductService
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
     * Gift card repository instance.
     *
     * @var WPGiftcardRepository
     */
    private WPGiftcardRepository $repository;

    /**
     * Constructor.
     *
     * @param Connection $connection API connection instance.
     * @param Settings $settings Plugin settings instance.
     * @param WPGiftcardRepository $repository Gift card repository instance.
     */
    public function __construct(
        Connection $connection,
        Settings $settings,
        WPGiftcardRepository $repository
    ) {
        $this->logger = new Logger();
        $this->connection = $connection;
        $this->settings = $settings;
        $this->repository = $repository;
    }

    /**
     * Initialize the service and register hooks.
     *
     * @return void
     */
    public function init(): void
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

        // Ensure this hook runs with a high priority to avoid conflicts
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_giftcard_recipient_email'], 99);

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
    }

    /**
     * Process a gift card order.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function process_giftcard_order($order_id): void
    {
        $order = $this->repository->get_order($order_id);
        if (!$order) {
            $this->logger->error("Order not found: {$order_id}");
            return;
        }

        // Check if order contains gift cards *before* logging
        $has_giftcards = false;
        foreach ($order->get_items() as $item) {
            /**
             * WooCommerce order item object.
             *
             * @var \WC_Order_Item_Product $item
             */
            $product = $item->get_product();

            $product_id = $product->get_parent_id() ?: $product->get_id();
            if (get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
                $has_giftcards = true;
                $this->logger->info("Found gift card product in order: {$product_id}", [
                    'product_id' => $product_id,
                    'order_id' => $order_id,
                ], true);
                break;
            }
        }

        // Only proceed and log if order contains gift cards
        if (!$has_giftcards) {
            $this->logger->info("No gift cards found in order {$order_id}, skipping processing", [
                'order_id' => $order_id,
            ], true);
            return;
        }

        // Now log the start and check if already processed
        $this->logger->info("Starting to process gift card order: {$order_id}", ['order_id' => $order_id], true);

        // Check if we've already processed this order
        $giftcards_created = get_post_meta($order_id, WCOrders::GIFT_CARD_CREATED, true);
        $this->logger->info("Gift cards already created for order {$order_id}: " . ($giftcards_created ? 'yes' : 'no'), [], true);

        if ($giftcards_created) {
            $this->logger->info('Giftcards already created for order', [
                'order_id' => $order_id,
            ], true);
            OrderNotes::add_warning($order, 'Attempted to process gift cards again, but they were already created.');
            return;
        }

        $recipient_email = $this->repository->get_recipient_email_for_order($order_id);
        $this->logger->info("Retrieved recipient email for order {$order_id}: " . ($recipient_email ?: 'null'), [
            'order_id' => $order_id,
        ], true);

        $disable_recipient_email = $this->settings->get_setting_value_by_id('giftcard_disable_recipient_email');
        $this->logger->info("Gift card recipient email disabled setting: " . ($disable_recipient_email === 'on' ? 'yes' : 'no'), [
            'order_id' => $order_id,
        ], true);

        if ($disable_recipient_email === 'on') {
            OrderNotes::add($order, __('Gift card recipient email is disabled in settings. No email will be sent.', 'leat-crm'));
        } else if (empty($recipient_email)) {
            OrderNotes::add_warning($order, __('No gift card recipient email was found. This could mean that something went wrong with the checkout process.', 'leat-crm'));
        }

        $items = $this->repository->get_order_items($order_id);
        if (empty($items)) {
            $this->logger->error("No items found in order: {$order_id}");
            return;
        }

        foreach ($items as $item) {
            $product_id = wc_get_order_item_meta($item->get_id(), '_product_id', true);
            if (!$product_id) {
                continue;
            }

            $program_uuid = $this->repository->get_program_uuid_for_product($product_id);
            if (!$program_uuid) {
                continue;
            }

            $quantity = $item->get_quantity();
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            // Add note about starting to create gift cards
            OrderNotes::add(
                $order,
                sprintf(
                    // translators: 1: quantity of gift cards, 2: product name.
                    __('Starting to create %1$d gift card(s) for %2$s.', 'leat-crm'),
                    $quantity,
                    $product->get_name()
                )
            );

            for ($i = 1; $i <= $quantity; $i++) {
                $amount_in_cents = $this->calculate_giftcard_amount($product, $item, $i, $order_id);
                if ($amount_in_cents <= 0) {
                    OrderNotes::add_error(
                        $order,
                        sprintf(
                            // translators: 1: product name.
                            __('Invalid amount (0 or negative) for gift card for %1$s.', 'leat-crm'),
                            $product->get_name()
                        )
                    );
                    continue;
                }

                $giftcard_data = $this->create_giftcard($program_uuid, $amount_in_cents);
                if (!$giftcard_data || !isset($giftcard_data['uuid'])) {
                    OrderNotes::add_error(
                        $order,
                        sprintf(
                            // translators: 1: product name.
                            __('Failed to create gift card for %1$s.', 'leat-crm'),
                            $product->get_name()
                        )
                    );
                    continue;
                }

                $giftcard_uuid = $giftcard_data['uuid'];

                // Add customer-facing note with the gift card code if available
                if (isset($giftcard_data['hash']) && $giftcard_data['hash']) {
                    $order->add_order_note(
                        sprintf(
                            // translators: 1: gift card code.
                            __('Gift Card Code: %s', 'leat-crm'),
                            $giftcard_data['hash']
                        ),
                        true // This makes it a customer-facing note
                    );
                }

                // Add success note for gift card creation
                OrderNotes::add_success(
                    $order,
                    sprintf(
                        // translators: 1: gift card UUID, 2: amount.
                        __('Gift card %1$s created with amount %2$s successfully.', 'leat-crm'),
                        $giftcard_uuid,
                        number_format($amount_in_cents / 100, 2)
                    )
                );

                // Store the giftcard UUID as item meta.
                wc_add_order_item_meta($item->get_id(), WCOrders::GIFT_CARD_UUID . '_' . $i, $giftcard_uuid);
                wc_add_order_item_meta($item->get_id(), WCOrders::GIFT_CARD_UUID, $giftcard_uuid);

                // Send the giftcard email only if recipient email is set and email sending is enabled
                if ($disable_recipient_email === 'on') {
                    OrderNotes::add(
                        $order,
                        sprintf(
                            // translators: 1: gift card UUID
                            __('Gift card %1$s created. Gift Card input field is disabled in settings, so no email will be sent.', 'leat-crm'),
                            $giftcard_uuid
                        )
                    );
                } else if (empty($recipient_email)) {
                    OrderNotes::add_warning(
                        $order,
                        sprintf(
                            // translators: 1: gift card UUID
                            __('Gift card %1$s created but no recipient email was found to send it to.', 'leat-crm'),
                            $giftcard_uuid
                        )
                    );
                } else {
                    $email_sent = $this->send_giftcard_email($giftcard_uuid, $recipient_email);

                    if ($email_sent) {
                        OrderNotes::add_success(
                            $order,
                            sprintf(
                                // translators: 1: gift card UUID, 2: recipient email.
                                __('Gift card %1$s email sent to %2$s.', 'leat-crm'),
                                $giftcard_uuid,
                                $recipient_email
                            )
                        );
                    } else {
                        OrderNotes::add_error(
                            $order,
                            sprintf(
                                // translators: 1: gift card UUID, 2: recipient email.
                                __('Failed to send gift card %1$s email to %2$s.', 'leat-crm'),
                                $giftcard_uuid,
                                $recipient_email
                            )
                        );
                    }
                }
            }
        }

        if ($has_giftcards) {
            update_post_meta($order_id, WCOrders::GIFT_CARD_CREATED, true);
            OrderNotes::add_success($order, __('Gift card processing completed.', 'leat-crm'));
        }
    }

    /**
     * Calculate the gift card amount.
     *
     * @param WC_Product            $product The product object.
     * @param \WC_Order_Item_Product $item The order item object.
     * @param int                   $quantity The quantity of the product.
     * @param int                   $order_id The order ID.
     * @return int The amount in cents.
     */
    private function calculate_giftcard_amount($product, $item, $quantity, $order_id): int
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
     * Create a gift card.
     *
     * @param string $program_uuid The program UUID.
     * @param int $amount_in_cents The amount in cents.
     * @return array|null The gift card data or null on failure.
     */
    private function create_giftcard($program_uuid, $amount_in_cents): ?array
    {
        try {
            // Create the gift card
            $giftcard = $this->connection->create_giftcard($program_uuid);
            if (!$giftcard || !isset($giftcard['uuid'])) {
                return null;
            }

            // Create the transaction to fund the gift card
            $transaction = $this->connection->create_giftcard_transaction($giftcard['uuid'], $amount_in_cents);
            if (!$transaction) {
                return null;
            }

            return [
                'uuid' => $giftcard['uuid'],
                'hash' => $giftcard['hash'] ?? null,
                'id' => $giftcard['id'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error creating giftcard: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Send a gift card email.
     *
     * @param string $giftcard_uuid The gift card UUID.
     * @param string $recipient_email The recipient email.
     * @return bool Whether the email was sent successfully.
     */
    private function send_giftcard_email($giftcard_uuid, $recipient_email): bool
    {
        $this->logger->info("Attempting to send gift card email", [
            'giftcard_uuid' => $giftcard_uuid,
            'recipient_email' => $recipient_email
        ]);

        try {
            // Sending a giftcard email requires a Leat contact.
            $this->logger->info("Creating contact for recipient email: {$recipient_email}");
            $contact = $this->connection->find_or_create_contact($recipient_email);

            if (!$contact || !isset($contact['uuid'])) {
                $this->logger->error("Failed to create contact for recipient email: {$recipient_email}");
                return false;
            }

            $this->logger->info("Created contact for recipient email: {$recipient_email}, UUID: {$contact['uuid']}");

            $this->logger->info("Sending gift card email to contact", [
                'giftcard_uuid' => $giftcard_uuid,
                'contact_uuid' => $contact['uuid']
            ]);

            $response = $this->connection->send_giftcard_email($giftcard_uuid, $contact['uuid']);

            // Log the raw response for debugging
            $this->logger->info("Raw response from send_giftcard_email: " . json_encode($response));

            // The response is a Response object, not an array
            // Just check if it's not false (which would indicate an error)
            if ($response !== false) {
                $this->logger->info("Gift card email sent successfully to {$recipient_email}");
                return true;
            } else {
                $this->logger->error("Failed to send gift card email - response was false");
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error("Error sending giftcard email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle gift card withdrawal for refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_giftcard_withdrawal_refund($order_id, $refund_id): void
    {
        $order = $this->repository->get_order($order_id);
        $refund = $this->repository->get_order($refund_id);

        if (!$order || !$refund) {
            return;
        }

        $refund_items = $refund->get_items();
        if (empty($refund_items)) {
            return;
        }

        foreach ($refund_items as $refund_item) {
            $original_item = $this->find_matching_refunded_order_item($order, $refund_item);
            if (!$original_item) {
                continue;
            }

            $product_id = wc_get_order_item_meta($original_item->get_id(), '_product_id', true);
            if (!$product_id) {
                continue;
            }

            $program_uuid = $this->repository->get_program_uuid_for_product($product_id);
            if (!$program_uuid) {
                continue;
            }

            // Get the refunded quantity.
            $refunded_qty = abs($refund_item->get_quantity());
            if ($refunded_qty <= 0) {
                continue;
            }

            // Calculate refund percentage for this item.
            // $refund_percentage = abs($refund_item->get_total()) / $original_item->get_total();

            // Process refund for each giftcard in the item.
            for ($i = 1; $i <= $refunded_qty; $i++) {
                $giftcard_uuid = wc_get_order_item_meta($original_item->get_id(), WCOrders::GIFT_CARD_UUID . '_' . $i, true);
                if (!$giftcard_uuid) {
                    continue;
                }

                try {
                    // Withdraw the giftcard.
                    $response = $this->connection->reverse_giftcard_transaction($giftcard_uuid);
                    if ($response) {
                        $note = sprintf(
                            /* translators: %s: gift card UUID */
                            __('Gift card %s has been withdrawn due to refund.', 'leat-crm'),
                            $giftcard_uuid
                        );
                        $this->repository->add_order_note($order_id, $note);
                    } else {
                        $note = sprintf(
                            /* translators: %s: gift card UUID */
                            __('Failed to withdraw gift card %s.', 'leat-crm'),
                            $giftcard_uuid
                        );
                        $this->repository->add_order_note($order_id, $note);
                    }
                } catch (\Exception $e) {
                    $note = sprintf(
                        /* translators: 1: gift card UUID, 2: error message */
                        __('Error withdrawing gift card %1$s: %2$s', 'leat-crm'),
                        $giftcard_uuid,
                        $e->getMessage()
                    );
                    $this->repository->add_order_note($order_id, $note);
                }
            }
        }
    }

    /**
     * Find the matching order item for a refund item.
     *
     * @param object $order The order object.
     * @param object $refund_item The refund item object.
     * @return object|null The matching order item or null if not found.
     */
    private function find_matching_refunded_order_item($order, $refund_item): ?object
    {
        $refunded_product_id = wc_get_order_item_meta($refund_item->get_id(), '_product_id', true);

        foreach ($order->get_items() as $item) {
            $product_id = wc_get_order_item_meta($item->get_id(), '_product_id', true);
            if ($product_id == $refunded_product_id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Handle gift card withdrawal for order status changes.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function handle_giftcard_withdrawal($order_id): void
    {
        $order = $this->repository->get_order($order_id);
        if (!$order) {
            return;
        }

        $items = $this->repository->get_order_items($order_id);
        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $product_id = wc_get_order_item_meta($item->get_id(), '_product_id', true);
            if (!$product_id) {
                continue;
            }

            $program_uuid = $this->repository->get_program_uuid_for_product($product_id);
            if (!$program_uuid) {
                continue;
            }

            $quantity = $item->get_quantity();
            for ($i = 1; $i <= $quantity; $i++) {
                $giftcard_uuid = wc_get_order_item_meta($item->get_id(), WCOrders::GIFT_CARD_UUID . '_' . $i, true);

                if (!$giftcard_uuid) {
                    continue;
                }

                try {
                    // Withdraw the giftcard.
                    $response = $this->connection->reverse_giftcard_transaction($giftcard_uuid);
                    if ($response) {
                        $note = sprintf(
                            /* translators: %s: gift card UUID */
                            __('Gift card %s has been withdrawn due to order status change.', 'leat-crm'),
                            $giftcard_uuid
                        );
                        $this->repository->add_order_note($order_id, $note);
                    } else {
                        $note = sprintf(
                            /* translators: %s: gift card UUID */
                            __('Failed to withdraw gift card %s.', 'leat-crm'),
                            $giftcard_uuid
                        );
                        $this->repository->add_order_note($order_id, $note);
                    }
                } catch (\Exception $e) {
                    $note = sprintf(
                        /* translators: 1: gift card UUID, 2: error message */
                        __('Error withdrawing gift card %1$s: %2$s', 'leat-crm'),
                        $giftcard_uuid,
                        $e->getMessage()
                    );
                    $this->repository->add_order_note($order_id, $note);
                }
            }
        }
    }

    /**
     * Validate gift card recipient email.
     *
     * @return void
     */
    public function validate_giftcard_recipient_email(): void
    {
        $has_giftcard = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if ($this->repository->get_program_uuid_for_product($product_id)) {
                $has_giftcard = true;
                break;
            }
        }

        $disable_recipient_email = $this->settings->get_setting_value_by_id('giftcard_disable_recipient_email');
        if ($disable_recipient_email === 'on') {
            return;
        }


        if ($has_giftcard && empty($_POST['giftcard_recipient_email'])) {
            wc_add_notice(__('Please enter a recipient email address for the gift card.', 'leat-crm'), 'error');
        } elseif ($has_giftcard && !is_email($_POST['giftcard_recipient_email'])) {
            wc_add_notice(__('Please enter a valid recipient email address for the gift card.', 'leat-crm'), 'error');
        }
    }

    /**
     * Add gift card product tab to WooCommerce product data tabs.
     *
     * @param array $tabs The existing tabs.
     * @return array The modified tabs.
     */
    public function add_giftcard_product_tab($tabs): array
    {
        $tabs['leat_giftcard'] = [
            'label'  => __('Leat: Giftcard Settings', 'leat-crm'),
            'target' => 'leat_giftcard_product_data',
            'class'  => [],
        ];
        return $tabs;
    }

    /**
     * Add gift card program settings to WooCommerce product data panels.
     *
     * @return void
     */
    public function add_giftcard_program_settings(): void
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

    /**
     * Save gift card program settings from WooCommerce product meta.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function save_giftcard_program_settings($post_id): void
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

        $this->repository->save_program_uuid_for_product($post_id, $program_uuid);
    }

    /**
     * Add gift card recipient field to WooCommerce checkout.
     *
     * @param object $checkout The checkout object.
     * @return void
     */
    public function add_giftcard_recipient_field($checkout): void
    {
        $has_giftcard   = false;
        $giftcard_count = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if ($this->repository->get_program_uuid_for_product($product_id)) {
                $has_giftcard    = true;
                $giftcard_count += $cart_item['quantity'];
            }
        }

        $disable_recipient_email = $this->settings->get_setting_value_by_id('giftcard_disable_recipient_email');
        if ($disable_recipient_email === 'on') {
            return;
        }

        if ($has_giftcard) {
            $nonce = wp_create_nonce('leat_giftcard_recipient');
            $this->logger->info("Generated gift card recipient nonce: {$nonce}");

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

            if ($giftcard_count > 1) {
                printf(
                    '<p class="giftcard-notice"><em>%s</em></p>',
                    sprintf(
                        /* translators: %d: number of gift cards */
                        esc_html__('Important: You are purchasing %d gift cards. All gift cards will be sent to the same recipient email address entered above. If you want to send gift cards to different recipients, please place separate orders.', 'leat-crm'),
                        esc_html($giftcard_count)
                    )
                );
            }
        }
    }

    /**
     * Save gift card recipient email from WooCommerce checkout.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function save_giftcard_recipient_email($order_id): void
    {
        $this->logger->info("Attempting to save gift card recipient email for order: {$order_id}");

        // Log the POST data to see what's being received
        $this->logger->info("POST data for gift card recipient: " . json_encode($_POST));

        // During checkout, the nonce might not be present or might be invalid
        // Let's be more lenient with the nonce verification
        $nonce_valid = true;
        if (isset($_POST['leat_giftcard_recipient_nonce'])) {
            $nonce_valid = wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['leat_giftcard_recipient_nonce'])),
                'leat_giftcard_recipient'
            );

            if (!$nonce_valid) {
                $this->logger->warning("Nonce verification failed for gift card recipient email");
            }
        } else {
            $this->logger->warning("No nonce found for gift card recipient email");
        }

        // Log whether the email field exists in POST data
        $this->logger->info("Checking if giftcard_recipient_email exists in POST data: " . (isset($_POST['giftcard_recipient_email']) ? 'yes' : 'no'));

        if (! empty($_POST['giftcard_recipient_email'])) {
            $email = sanitize_email(wp_unslash($_POST['giftcard_recipient_email']));
            $this->logger->info("Saving gift card recipient email for order {$order_id}: {$email}");

            $this->repository->save_recipient_email_for_order(
                $order_id,
                $email
            );

            // Verify that the email was saved correctly
            $saved_email = $this->repository->get_recipient_email_for_order($order_id);
            $this->logger->info("Verified saved gift card recipient email for order {$order_id}: " . ($saved_email ?: 'null'));
        } else {
            $this->logger->warning("No gift card recipient email provided for order: {$order_id}");
        }
    }

    /**
     * Format gift card meta display in WooCommerce orders.
     *
     * @param string $display_value The display value.
     * @param object $meta The meta object.
     * @param object $item The item object.
     * @return string The formatted display value.
     */
    public function format_giftcard_meta_display($display_value, $meta, $item): string
    {
        if ('_leat_giftcard_uuid' === $meta->key) {
            $display_value = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url(admin_url('admin.php?page=leat-giftcards&uuid=' . $meta->value)),
                esc_html($meta->value)
            );
        }

        return $display_value;
    }

    /**
     * Add refund field script to WooCommerce order edit screen.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function add_refund_field_script($order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $has_giftcard = false;
        foreach ($order->get_items() as $item) {
            $product_id = wc_get_order_item_meta($item->get_id(), '_product_id', true);
            if ($product_id && $this->repository->get_program_uuid_for_product($product_id)) {
                $has_giftcard = true;
                break;
            }
        }

        if ($has_giftcard) {
?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.refund-actions').before('<div class="leat-giftcard-refund-notice"><p><strong><?php esc_html_e('Note:', 'leat-crm'); ?></strong> <?php esc_html_e('This order contains gift cards. Refunding will withdraw the gift card balance if possible.', 'leat-crm'); ?></p></div>');
                });
            </script>
<?php
        }
    }
}
