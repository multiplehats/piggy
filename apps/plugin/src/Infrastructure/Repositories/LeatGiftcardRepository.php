<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Api\ApiService;
use Leat\Domain\Interfaces\LeatGiftcardRepositoryInterface;
use Leat\Utils\Logger;
use Piggy\Api\Models\Giftcards\Giftcard;

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
     * @var Connection
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
                $this->logger->error('Failed to initialize API client');
                return null;
            }

            $giftcard = Giftcard::findOneBy(['hash' => $hash]);
            return $giftcard;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error finding gift card by hash');

            return null;
        }
    }
}
