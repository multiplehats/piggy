<?php

namespace Leat\Domain\Interfaces;

use Piggy\Api\Models\Giftcards\Giftcard;

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
}
