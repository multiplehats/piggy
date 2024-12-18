<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use WC_Product_Simple;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;


class GiftcardProduct {
    private Connection $connection;
    private Logger $logger;

    public function __construct(Connection $connection) {
        $this->logger = new Logger();

        $this->connection = $connection;
    }

    public function init() {
        // Add giftcard product settings.
        add_filter('woocommerce_product_data_tabs', [$this, 'add_giftcard_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_giftcard_program_settings']);
        add_action('woocommerce_process_product_meta', [$this, 'save_giftcard_program_settings']);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'format_giftcard_meta_display'], 10, 3);

        // Process giftcards after order is completed.
        add_action('woocommerce_order_status_completed', [$this, 'process_giftcard_order'], 10, 1);

        // Recipient email.
        add_action('woocommerce_before_order_notes', [$this, 'add_giftcard_recipient_field']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_giftcard_recipient_email']);
        add_action('woocommerce_checkout_process', [$this, 'validate_giftcard_recipient_email']);
    }

    public function add_giftcard_product_tab($tabs) {
        $tabs['leat_giftcard'] = [
            'label' => __('Giftcard Settings', 'leat-crm'),
            'target' => 'leat_giftcard_product_data',
            'class' => [], // Remove show_if_leat_giftcard class
        ];
        return $tabs;
    }

    public function add_giftcard_program_settings() {
        global $post;

        echo '<div id="leat_giftcard_product_data" class="panel woocommerce_options_panel">';

        // Get all giftcard programs
        $programs = $this->connection->list_giftcard_programs();
        $options = ['' => __('Select a program', 'leat-crm')];
        if ($programs) {
            foreach ($programs as $program) {
                $options[$program['uuid']] = $program['name'];
            }
        }

        woocommerce_wp_select([
            'id' => '_leat_giftcard_program_uuid',
            'label' => __('Giftcard Program', 'leat-crm'),
            'description' => __('Select the giftcard program this product is connected to', 'leat-crm'),
            'desc_tip' => true,
            'options' => $options,
            'value' => get_post_meta($post->ID, '_leat_giftcard_program_uuid', true),
        ]);

        echo '</div>';
    }

    public function save_giftcard_program_settings($post_id) {
        $program_uuid = isset($_POST['_leat_giftcard_program_uuid'])
            ? sanitize_text_field($_POST['_leat_giftcard_program_uuid'])
            : '';
        update_post_meta($post_id, '_leat_giftcard_program_uuid', $program_uuid);
    }

    public function add_giftcard_recipient_field($checkout) {
        // Check if cart has giftcard
        $has_giftcard = false;
        $giftcard_count = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if (get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
                $has_giftcard = true;
                $giftcard_count += $cart_item['quantity'];
            }
        }

        if ($has_giftcard) {
            woocommerce_form_field('giftcard_recipient_email', [
                'type' => 'email',
                'class' => ['form-row-wide'],
                'label' => __('Gift Card Recipient Email', 'leat-crm'),
                'placeholder' => __('Enter recipient email address', 'leat-crm'),
                'required' => true,
            ], $checkout->get_value('giftcard_recipient_email'));

            // Add notice if multiple gift cards
            if ($giftcard_count > 1) {
                echo '<p class="giftcard-notice"><em>' .
                    sprintf(
                        /* translators: %d: number of gift cards */
                        __('Important: You are purchasing %d gift cards. All gift cards will be sent to the same recipient email address entered above. If you want to send gift cards to different recipients, please place separate orders.', 'leat-crm'),
                        $giftcard_count
                    ) .
                    '</em></p>';
            }
        }
    }

    public function save_giftcard_recipient_email($order_id) {
        if (!empty($_POST['giftcard_recipient_email'])) {
            update_post_meta($order_id, '_giftcard_recipient_email', sanitize_email($_POST['giftcard_recipient_email']));
        }
    }

    private function send_giftcard_email($giftcard_uuid, $recipient_email) {
        try {
            // Sending a giftcard email requires a Leat contact.
            $contact = $this->connection->create_contact($recipient_email);

            $response = $this->connection->send_giftcard_email($giftcard_uuid, $contact['uuid']);

            $this->logger->info('Sent giftcard email', [
                'giftcard_uuid' => $giftcard_uuid,
                'recipient_email' => $recipient_email,
                'response' => $response
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send giftcard email', [
                'giftcard_uuid' => $giftcard_uuid,
                'recipient_email' => $recipient_email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function process_giftcard_order($order_id) {
        $order = wc_get_order($order_id);

        // Check if we've already processed this order
        if (get_post_meta($order_id, '_leat_giftcards_created', true)) {
            $this->logger->info('Giftcards already created for order', ['order_id' => $order_id]);
            OrderNotes::addWarning($order, 'Attempted to process gift cards again, but they were already created.');
            return;
        }

        $recipient_email = get_post_meta($order_id, '_giftcard_recipient_email', true);

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $product_id = $product->get_parent_id() ?: $product->get_id();
            $program_uuid = get_post_meta($product_id, '_leat_giftcard_program_uuid', true);

            if ($program_uuid) {
                $quantity = $item->get_quantity();
                $amount_in_cents = $product->get_price() * 100;

                OrderNotes::add($order,
                    sprintf(
                        __('Starting to create %d gift card(s) for %s.', 'leat-crm'),
                        $quantity,
                        $product->get_name()
                    )
                );

                for ($i = 0; $i < $quantity; $i++) {
                    try {
                        $data = $this->create_giftcard($program_uuid, $amount_in_cents);

                        if ($data) {
                            // Store giftcard data in order item meta
                            $item->add_meta_data('_leat_giftcard_tx_uuid_' . $i + 1, $data['tx']['uuid']);
                            $item->add_meta_data('_leat_giftcard_hash_' . $i + 1, $data['giftcard']['hash']);
                            $item->add_meta_data('_leat_giftcard_uuid_' . $i + 1, $data['giftcard']['uuid']);
                            $item->add_meta_data('_leat_giftcard_id_' . $i + 1, $data['giftcard']['id']);
                            $item->add_meta_data('_leat_giftcard_tx_id_' . $i + 1, $data['tx']['id']);

                            $giftcard_uuid = $data['giftcard']['uuid'];
                            $giftcard_id = $data['giftcard']['id'];
                            $giftcard_hash = $data['giftcard']['hash'];

                            if (!$giftcard_uuid) {
                                $error_message = 'Failed to create gift card - UUID not found';
                                OrderNotes::addError($order, $error_message);
                                $this->logger->error($error_message, [
                                    'order_id' => $order_id,
                                    'program_uuid' => $program_uuid
                                ]);
                                continue;
                            }

                            // Add customer-facing note with the giftcard hash
                            $order->add_order_note(
                                sprintf(
                                    __('Gift Card Code: %s', 'leat-crm'),
                                    $giftcard_hash
                                ),
                                true
                            );

                            OrderNotes::addSuccess($order,
                                sprintf(
                                    __('Gift card #%s created successfully.', 'leat-crm'),
                                    $giftcard_id
                                )
                            );

                            // Send email if recipient email exists
                            if ($recipient_email && $giftcard_uuid) {
                                $email_sent = $this->send_giftcard_email($giftcard_uuid, $recipient_email);

                                if ($email_sent) {
                                    OrderNotes::addSuccess($order,
                                        sprintf(
                                            __('Gift card #%s email sent to %s.', 'leat-crm'),
                                            $giftcard_id,
                                            $recipient_email
                                        )
                                    );
                                } else {
                                    OrderNotes::addError($order,
                                        sprintf(
                                            __('Failed to send gift card #%s email to %s.', 'leat-crm'),
                                            $giftcard_id,
                                            $recipient_email
                                        )
                                    );
                                }
                            }

                            $item->save();

                            $this->logger->info('Created giftcard', [
                                'order_id' => $order_id,
                                'program_uuid' => $program_uuid,
                                'giftcard_uuid' => $data['giftcard']['uuid'],
                                'giftcard_tx_uuid' => $data['tx']['uuid'],
                                'amount_in_cents' => $amount_in_cents
                            ]);
                        }
                    } catch (\Exception $e) {
                        OrderNotes::addError($order,
                            sprintf(
                                __('Error creating gift card: %s', 'leat-crm'),
                                $e->getMessage()
                            )
                        );
                        $this->logger->error($error_message, [
                            'order_id' => $order_id,
                            'program_uuid' => $program_uuid
                        ]);
                    }
                }
            }
        }

        // Mark order as processed for giftcards
        update_post_meta($order_id, '_leat_giftcards_created', true);
        OrderNotes::addSuccess($order, __('Gift card processing completed.', 'leat-crm'));
    }

    private function create_giftcard($program_uuid, $amount_in_cents) {
        try {
            $giftcard = $this->connection->create_giftcard($program_uuid);

            $transaction = $this->connection->create_giftcard_transaction($giftcard['uuid'], $amount_in_cents);

            return [
                'tx' => $transaction,
                'giftcard' => $giftcard,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error creating giftcard', [
                'program_uuid' => $program_uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function format_giftcard_meta_display($display_value, $meta, $item) {
        // If gift card id desn't exist yet, don't show anything
        $meta_key = $meta->key;
        if (!str_starts_with($meta_key, '_leat_giftcard_id_')) {
            return $display_value;
        }

        // Only modify display for giftcard ID fields
        if (!str_starts_with($meta->key, '_leat_giftcard_id_')) {
            return $display_value;
        }

        $giftcard_id = $meta->value;
        return sprintf(
            '<a href="%s" target="_blank">View Giftcard #%s</a>',
            esc_url('https://business.leat.com/store/giftcards/program/cards?card_id=' . $giftcard_id),
            esc_html($giftcard_id)
        );
    }

    public function validate_giftcard_recipient_email() {
        // Check if cart has giftcard
        $has_giftcard = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if (get_post_meta($product_id, '_leat_giftcard_program_uuid', true)) {
                $has_giftcard = true;
                break;
            }
        }

        // Validate recipient email if cart has giftcard
        if ($has_giftcard && empty($_POST['giftcard_recipient_email'])) {
            wc_add_notice(__('Gift Card Recipient Email is required.', 'leat-crm'), 'error');
        }
    }
}