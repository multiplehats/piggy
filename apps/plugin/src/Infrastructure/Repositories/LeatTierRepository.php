<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Services\ApiService;
use Leat\Domain\Interfaces\LeatTierRepositoryInterface;
use Piggy\Api\Models\Tiers\Tier;

/**
 * Class LeatGiftcardRepository
 *
 * Leat-specific implementation for gift card data access.
 *
 * @package Leat\Infrastructure\Repositories
 */
class LeatTierRepository implements LeatTierRepositoryInterface
{
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
    }

    public function list(): ?array
    {
        try {
            $client = $this->apiService->init_client();

            if (!$client) {

                return null;
            }

            $tiers = Tier::list();

            return $tiers;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error finding gift card by hash');

            return null;
        }
    }

    public function get_by_contact_uuid(string $contact_uuid): ?Tier
    {
        try {
            $client = $this->apiService->init_client();

            if (!$client) {
                return null;
            }

            $tier = Tier::findBy($contact_uuid);
            return $tier;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error finding tier by contact UUID');

            return null;
        }
    }
}
