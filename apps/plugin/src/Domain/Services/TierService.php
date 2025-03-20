<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\LeatTierRepositoryInterface;
use Leat\Infrastructure\Formatters\TierFormatter;

class TierService
{
    /**
     * LeatTierRepositoryInterface instance.
     *
     * @var LeatTierRepositoryInterface
     */
    protected $repository;

    /**
     * TierFormatter instance.
     *
     * @var TierFormatter
     */
    protected $formatter;

    public function __construct(LeatTierRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->formatter = new TierFormatter();
    }

    public function get_tiers()
    {
        $tiers = $this->repository->list();

        if (!$tiers) {
            return [];
        }

        return array_map([$this->formatter, 'format'], $tiers);
    }

    public function get_tier_by_contact_uuid($contact_uuid)
    {
        $tier = $this->repository->get_by_contact_uuid($contact_uuid);

        if (!$tier) {
            return null;
        }

        return $this->formatter->format($tier);
    }
}
