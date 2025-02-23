<?php

namespace Leat\Domain\Services\Order;


use Leat\Api\Connection;
use Leat\Domain\Services\EarnRules;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

/**
 * Handles order processing operations for the loyalty system.
 *
 * This class manages order-related operations including attribute synchronization,
 * credit processing, and checkout handling for both registered users and guest customers.
 *

 */
class OrderProcessor
{
    /**
     * API Connection instance.
     *

     * @var Connection
     */
    private $connection;

    /**
     * Earn Rules service instance.
     *

     * @var EarnRules
     */
    private $earn_rules;

    /**
     * Logger instance.
     *

     * @var Logger
     */
    private $logger;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *

     *
     * @param Connection $connection API connection instance.
     * @param EarnRules $earn_rules  Earn rules service instance.
     */
    public function __construct(Connection $connection, EarnRules $earn_rules, Settings $settings)
    {
        $this->connection = $connection;
        $this->earn_rules = $earn_rules;
        $this->settings = $settings;
        $this->logger = new Logger();
    }

    /**
     * Synchronizes attributes when an order is completed.
     *
     * Handles the synchronization of user/guest attributes and processes order credits
     * upon order completion.
     *

     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function sync_attributes_on_order_completed($order_id): void
    {
        try {
            $order = wc_get_order($order_id);
            $user_id = $order->get_user_id();
            $guest_checkout = empty($user_id);

            $uuid = $this->get_order_uuid($order, $guest_checkout);

            if (!$uuid) {
                throw new \Exception("No UUID found for order $order_id");
            }


            if ($this->are_credits_already_issued($order)) {
                return;
            }

            // Sync attributes
            if ($guest_checkout) {
                $this->connection->sync_guest_attributes($order, $uuid);
            } else {
                $this->connection->sync_user_attributes($user_id, $uuid);
            }

            $this->process_order_credits($order, $uuid, $user_id);
        } catch (\Throwable $th) {
            $this->logger->error('Error syncing attributes on order completed: ' . $th->getMessage());
            OrderNotes::add_error($order, 'Error processing loyalty credits: ' . $th->getMessage());
        }
    }

    /**
     * Processes an order during checkout.
     *
     * Creates or links loyalty accounts and syncs basic attributes during the checkout process.
     *

     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function handle_checkout_order_processed($order_id): void
    {
        try {
            $order = $this->get_order($order_id);
            $user_id = $order->get_user_id();
            $guest_checkout = empty($user_id);

            $uuid = $this->process_checkout_contact($order, $guest_checkout);

            if (!$uuid) {
                OrderNotes::add_error($order, 'Failed to create/find loyalty account');
                return;
            }

            OrderNotes::add_success($order, sprintf('Successfully linked to loyalty account %s', $uuid));
            $this->connection->sync_basic_attributes_from_order($order, $uuid, $guest_checkout);
        } catch (\Throwable $th) {
            $this->logger->error('Error processing checkout order: ' . $th->getMessage());
            OrderNotes::add_error($order, 'Error processing loyalty account: ' . $th->getMessage());
        }
    }


    /**
     * Handle checkout payment processing for WooCommerce Blocks
     *
     * @param PaymentContext $context Contains order and payment data
     * @param PaymentResult  $result  Payment result object
     */
    public function handle_blocks_checkout_order_processed($context, $result): void
    {
        try {
            $order = $context->order;
            $user_id = $order->get_user_id();
            $guest_checkout = empty($user_id);

            $uuid = $this->process_checkout_contact($order, $guest_checkout);

            if (!$uuid) {
                OrderNotes::add_error($order, 'Failed to create/find loyalty account');
                return;
            }

            OrderNotes::add_success($order, sprintf('Successfully linked to loyalty account %s', $uuid));
            $this->connection->sync_basic_attributes_from_order($order, $uuid, $guest_checkout);
        } catch (\Throwable $th) {
            $this->logger->error('Error processing checkout order: ' . $th->getMessage());
            OrderNotes::add_error($order, 'Error processing loyalty account: ' . $th->getMessage());
        }
    }

    /**
     * Retrieves the UUID associated with an order.
     *

     *
     * @param \WC_Order $order         WooCommerce order object.
     * @param bool      $guest_checkout Whether this is a guest checkout.
     * @return string|null UUID if found, null otherwise.
     */
    private function get_order_uuid($order, bool $guest_checkout): ?string
    {
        if ($guest_checkout) {
            return $order->get_meta('_leat_contact_uuid');
        }

        $contact = $this->connection->get_contact_by_wp_id($order->get_user_id());
        return $contact['uuid'] ?? null;
    }

    /**
     * Checks if credits have already been issued for an order.
     *

     *
     * @param \WC_Order $order WooCommerce order object.
     * @return bool True if credits were already issued, false otherwise.
     */
    private function are_credits_already_issued($order): bool
    {
        return (bool) $order->get_meta('_leat_earn_rule_credit_transaction_uuid');
    }

    /**
     * Processes and applies credits for an order.
     *

     *
     * @param \WC_Order  $order   WooCommerce order object.
     * @param string     $uuid    Contact UUID.
     * @param int|null   $user_id WordPress user ID.
     * @return void
     */
    private function process_order_credits($order, string $uuid, ?int $user_id): void
    {
        $order_total = $order->get_total();
        $applicable_rule = $this->earn_rules->get_applicable_place_order_rule($order_total);

        if (!$applicable_rule) {
            return;
        }

        $result = $this->connection->apply_credits($uuid, null, $order_total, 'purchase_amount');

        if (!$result) {
            $this->logger->error("Failed to apply credits for order {$order->get_id()}");
            OrderNotes::add_error($order, 'Failed to apply loyalty credits for this order.');
            return;
        }

        $this->save_credit_transaction($order, $result, $applicable_rule, $user_id);
    }

    /**
     * Retrieves a WooCommerce order by ID.
     *
     * @param int $order_id WooCommerce order ID.
     * @return \WC_Order
     * @throws \Exception When order cannot be found.
     */
    private function get_order($order_id): \WC_Order
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new \Exception('Could not find order with ID: ' . $order_id);
        }
        return $order;
    }

    /**
     * Processes contact information during checkout.
     *
     * Creates a new Leat contact for guest users or retrieves existing contact for registered users.
     *
     * @param \WC_Order $order         WooCommerce order object.
     * @param bool      $guest_checkout Whether this is a guest checkout.
     * @return string|null Contact UUID if successful, null otherwise.
     */
    private function process_checkout_contact($order, bool $guest_checkout): ?string
    {
        if ($guest_checkout) {
            $email = $order->get_billing_email();
            if (!$email) {
                $this->logger->error('No email provided for guest order: ' . $order->get_id());
                return null;
            }

            $contact = $this->connection->create_contact($email);
            if (!$contact) {
                $this->logger->error('Failed to create contact for guest order: ' . $order->get_id());
                return null;
            }

            $uuid = $contact['uuid'];
            $order->update_meta_data('_leat_contact_uuid', $uuid);
            $order->save();
            return $uuid;
        }

        $contact = $this->connection->get_contact_by_wp_id($order->get_user_id());
        return $contact['uuid'] ?? null;
    }

    /**
     * Saves credit transaction details to the order.
     *

     *
     * @param \WC_Order    $order           WooCommerce order object.
     * @param object       $result          Credit transaction result.
     * @param array        $applicable_rule Applicable earn rule details.
     * @param int|null     $user_id        WordPress user ID.
     * @return void
     */
    private function save_credit_transaction($order, $result, array $applicable_rule, ?int $user_id): void
    {
        $credits = $result->getCredits();
        $result_uuid = $result->getUuid();

        $order->update_meta_data('_leat_earn_rule_credit_transaction_uuid', $result_uuid);
        $order->update_meta_data('_leat_earn_rule_credits_issued', $credits);
        OrderNotes::add_success($order, sprintf('Added %d loyalty credits (Transaction ID: %s)', $credits, $result_uuid));
        $order->save();

        if ($user_id) {
            $this->logger->info("Adding $credits credits to user $user_id for order {$order->get_id()}");
            $this->connection->add_reward_log($user_id, $applicable_rule['id'], $credits);
        }
    }
}
