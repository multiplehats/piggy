<?php

namespace Leat\Domain\Services\Order;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;

/**
 * Handles credit-related operations for WooCommerce orders.
 *
 * This class manages the withdrawal and refunding of loyalty credits
 * associated with WooCommerce orders.
 *

 */
class OrderCreditHandler
{
    /**
     * API Connection instance.
     *

     * @var Connection
     */
    private $connection;

    /**
     * Logger instance.
     *

     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *

     * @param Connection $connection API connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->logger = new Logger();
    }

    /**
     * Handles the withdrawal of credits for a given order.
     *
     * Attempts to withdraw credits associated with an order based on its
     * transaction UUID. Logs the result and updates order meta data.
     *

     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function handle_order_credit_withdrawal($order_id): void
    {
        try {
            $order = wc_get_order($order_id);

            if (!$order || $this->are_credits_withdrawn($order)) {
                return;
            }

            $credit_data = $this->get_credit_transaction_data($order);
            if (!$credit_data) {
                return;
            }

            $result = $this->connection->refund_credits_full($credit_data['transaction_uuid']);
            $this->process_withdrawal_result($order, $result, $credit_data['credits'], $order->get_status());
        } catch (\Throwable $th) {
            $this->handle_error($order, $th, 'withdrawal');
        }
    }

    /**
     * Handles credit withdrawal for order refunds.
     *
     * Processes credit withdrawals when an order is refunded, supporting
     * both full and partial refunds.
     *

     * @param int $order_id  WooCommerce order ID.
     * @param int $refund_id WooCommerce refund ID.
     * @return void
     */
    public function handle_order_credit_withdrawal_refund($order_id, $refund_id): void
    {
        try {
            $order = wc_get_order($order_id);

            if (!$order || $this->are_credits_withdrawn($order)) {
                return;
            }

            $credit_data = $this->get_credit_transaction_data($order);
            if (!$credit_data) {
                return;
            }

            $refund_details = $this->get_refund_details($order, $refund_id);

            if ($refund_details['is_full_refund']) {
                $result = $this->connection->refund_credits_full($credit_data['transaction_uuid']);
                $this->process_withdrawal_result($order, $result, $credit_data['credits'], 'refund');
            } else {
                $this->handle_partial_refund($order);
            }
        } catch (\Throwable $th) {
            $this->handle_error($order, $th, 'refund');
        }
    }

    /**
     * Checks if credits have already been withdrawn for an order.
     *

     * @param \WC_Order $order WooCommerce order object.
     * @return bool True if credits are already withdrawn, false otherwise.
     */
    private function are_credits_withdrawn($order): bool
    {
        if ($order->get_meta('_leat_credits_withdrawn')) {
            $this->logger->info("Credits already withdrawn for order {$order->get_id()}");
            return true;
        }
        return false;
    }

    /**
     * Retrieves credit transaction data from an order.
     *

     * @param \WC_Order $order WooCommerce order object.
     * @return array|null Array containing transaction UUID and credits, or null if not found.
     */
    private function get_credit_transaction_data($order): ?array
    {
        $transaction_uuid = $order->get_meta('_leat_earn_rule_credit_transaction_uuid');
        $credits = $order->get_meta('_leat_earn_rule_credits_issued');

        if (!$transaction_uuid) {
            $this->logger->error("No Leat credit transaction UUID found for order {$order->get_id()}");
            return null;
        }

        return [
            'transaction_uuid' => $transaction_uuid,
            'credits' => $credits
        ];
    }

    /**
     * Gets refund details for an order.
     *

     * @param \WC_Order $order     WooCommerce order object.
     * @param int       $refund_id WooCommerce refund ID.
     * @return array {
     *     Refund details array.
     *     @type float $amount         The refund amount.
     *     @type bool  $is_full_refund Whether this is a full refund.
     * }
     */
    private function get_refund_details($order, $refund_id): array
    {
        $refund = new \WC_Order_Refund($refund_id);
        $refund_amount = $refund->get_amount();
        $original_amount = $order->get_total();
        $is_full_refund = $refund_amount >= $original_amount;

        return [
            'amount' => $refund_amount,
            'is_full_refund' => $is_full_refund
        ];
    }

    /**
     * Processes the result of a credit withdrawal attempt.
     *

     * @param \WC_Order $order  WooCommerce order object.
     * @param mixed     $result API response result.
     * @param int       $credits Number of credits withdrawn.
     * @param string    $reason  Reason for withdrawal.
     * @return void
     */
    private function process_withdrawal_result($order, $result, $credits, string $reason): void
    {
        if ($result) {
            $this->logger->info("Successfully processed credit withdrawal for order {$order->get_id()}");
            OrderNotes::add_success(
                $order,
                sprintf('Withdrew %d loyalty credits due to %s', $credits, $reason)
            );

            $order->update_meta_data('_leat_credits_withdrawn', $result->getUuid());
            $order->save();
        } else {
            OrderNotes::add_error($order, 'Failed to process loyalty credit withdrawal');
        }
    }

    /**
     * Handles partial refund scenarios.
     *
     * Currently logs a warning as partial refunds are not supported.
     *

     * @param \WC_Order $order WooCommerce order object.
     * @return void
     */
    private function handle_partial_refund($order): void
    {
        $this->logger->error('Partial refunds are not supported yet');
        OrderNotes::add_warning(
            $order,
            'Partial refunds of loyalty credits are not supported. You have to manually refund the credits in the Leat dashboard.'
        );
    }

    /**
     * Handles errors during credit operations.
     *

     * @param \WC_Order  $order WooCommerce order object.
     * @param \Throwable $th    Thrown exception.
     * @param string     $type  Type of operation that failed.
     * @return void
     */
    private function handle_error($order, \Throwable $th, string $type): void
    {
        $this->logger->error("Error handling order $type: " . $th->getMessage());
        OrderNotes::add_error($order, "Error processing loyalty credit $type: " . $th->getMessage());
    }
}
