<?php

namespace Leat\Domain\Interfaces;

use Piggy\Api\Models\Prepaid\PrepaidTransaction;

/**
 * Interface LeatPrepaidRepositoryInterface
 *
 * Interface for Leat prepaid repository implementations.
 *
 * @package Leat\Domain\Interfaces
 */
interface LeatPrepaidRepositoryInterface
{
    /**
     * Create prepaid transaction for a contact.
     *
     * @param string $contact_uuid The contact's UUID.
     * @param int $amount_in_cents The amount in cents.
     * @param string $shop_uuid The shop UUID.
     * @return PrepaidTransaction|null The created transaction or null on failure.
     */
    public function create_transaction(string $contact_uuid, int $amount_in_cents, string $shop_uuid): ?PrepaidTransaction;

    /**
     * Reverse a prepaid transaction.
     *
     * @param string $transaction_uuid The UUID of the transaction to reverse.
     * @return PrepaidTransaction|null The new reversal transaction or null on failure.
     */
    public function reverse_transaction(string $transaction_uuid): ?PrepaidTransaction;
}
