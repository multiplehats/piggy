<?php

namespace Leat\Domain\Interfaces;

use Piggy\Api\Models\Tiers\Tier;

/**
 * Interface LeatTierRepositoryInterface
 *
 * Interface for Leat tier repository implementations.
 *
 * @package Leat\Domain\Interfaces
 */
interface LeatTierRepositoryInterface
{
    /**
     * Get all tiers.
     *
     * @return array<Tier>|null
     */
    public function list(): ?array;

    /**
     * Get a tier by contact UUID.
     * @param string $contact_uuid The contact UUID.
     * @return Tier|null The tier object or null if not found.
     */
    public function get_by_contact_uuid(string $contact_uuid): ?Tier;
}
