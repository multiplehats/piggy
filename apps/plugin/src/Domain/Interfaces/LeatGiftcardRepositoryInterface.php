<?php

namespace Leat\Domain\Interfaces;

use Piggy\Api\Models\Giftcards\Giftcard;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;

/**
 * Interface LeatGiftcardRepositoryInterface
 *
 * Interface for Leat gift card repository implementations.
 *
 * @package Leat\Domain\Interfaces
 */
interface LeatGiftcardRepositoryInterface
{
    /**
     * Find a gift card by its hash.
     *
     * @param string $hash The gift card hash.
     * @return Giftcard|null The gift card object or null if not found.
     */
    public function find_by_hash(string $hash): ?Giftcard;

    /**
     * Get the transactions for a gift card.
     *
     * @param string $uuid The gift card UUID.
     * @return array|null The transactions or null if not found.
     */
    public function get_transactions(string $uuid): ?array;

    /**
     * Create giftcard transaction.
     *
     * @param string $uuid The gift card UUID.
     * @param int $amount The amount to create the transaction for.
     * @return GiftcardTransaction The created transaction or null on failure.
     */
    public function create_transaction(string $uuid, int $amount): ?GiftcardTransaction;

    /**
     * Reverse a gift card transaction.
     *
     * @param string $tx_uuid The gift card transaction UUID.
     * @return GiftcardTransaction The reversed transaction or null on failure.
     */
    public function reverse_transaction(string $tx_uuid): ?GiftcardTransaction;
}
