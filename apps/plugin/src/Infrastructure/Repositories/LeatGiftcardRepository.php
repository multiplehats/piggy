<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Services\ApiService;
use Leat\Domain\Interfaces\LeatGiftcardRepositoryInterface;
use Leat\Utils\Logger;
use Piggy\Api\Models\Giftcards\Giftcard;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;

/**
 * Class LeatGiftcardRepository
 *
 * Leat-specific implementation for gift card data access.
 *
 * @package Leat\Infrastructure\Repositories
 */
class LeatGiftcardRepository implements LeatGiftcardRepositoryInterface
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Leat connection instance.
     *
     * @var ApiService
     */
    private ApiService $apiService;

    /**
     * Constructor.
     *
     * @param ApiService $apiService The Leat API service instance.
     */
    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->logger = new Logger();
    }

    /**
     * Find a gift card by its hash.
     *
     * @param string $hash The gift card hash.
     * @return Giftcard|null The gift card object or null if not found.
     */
    public function find_by_hash(string $hash): ?Giftcard
    {
        try {
            $client = $this->apiService->init_client();

            if (!$client) {

                return null;
            }

            $giftcard = Giftcard::findOneBy(['hash' => strtoupper($hash)]);
            return $giftcard;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error finding gift card by hash');

            return null;
        }
    }

    /**
     * Get the transactions for a gift card.
     *
     * @param string $uuid The gift card UUID.
     * @return array|null The transactions or null if not found.
     */
    public function get_transactions(string $uuid): ?array
    {
        try {
            $client = $this->apiService->init_client();

            if (!$client) {

                return null;
            }

            $transactions = GiftcardTransaction::list([
                'giftcard_uuid' => $uuid,
            ]);

            return $transactions;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error getting gift card transactions');

            return null;
        }
    }

    /**
     * Create giftcard transaction.
     *
     * @param string $uuid The gift card UUID.
     * @param int $amount The amount to create the transaction for.
     * @return GiftcardTransaction The created transaction or null on failure.
     */
    public function create_transaction(string $uuid, int $amount): ?GiftcardTransaction
    {
        try {
            $client = $this->apiService->init_client();

            if (! $client) {
                return null;
            }

            $shop_uuid = $this->apiService->get_shop_uuid();

            if (! $shop_uuid) {
                return null;
            }

            $giftcard_transaction = GiftcardTransaction::create(
                [
                    'shop_uuid'       => $shop_uuid,
                    'giftcard_uuid'   => $uuid,
                    'amount_in_cents' => $amount,
                    'type'            => 1,
                ]
            );

            return $giftcard_transaction;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error creating gift card transaction');

            return null;
        }
    }

    /**
     * Reverse a gift card transaction.
     *
     * @param string $tx_uuid The gift card transaction UUID.
     * @return GiftcardTransaction The reversed transaction or null on failure.
     */
    public function reverse_transaction(string $tx_uuid): ?GiftcardTransaction
    {
        try {
            $client = $this->apiService->init_client();

            if (! $client) {
                return null;
            }

            $giftcard_transaction = GiftcardTransaction::reverse($tx_uuid);

            return $giftcard_transaction;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error reversing gift card transaction');

            return null;
        }
    }
}
