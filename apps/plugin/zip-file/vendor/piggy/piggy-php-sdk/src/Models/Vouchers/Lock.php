<?php

namespace Piggy\Api\Models\Vouchers;

use DateTime;

class Lock
{
    /**
     * @var string
     */
    protected $release_key;

    /**
     * @var DateTime
     */
    protected $locked_at;

    /**
     * @var DateTime|null
     */
    protected $unlocked_at;

    /**
     * @var DateTime|null
     */
    protected $system_release_at;

    public function __construct(
        string $release_key,
        DateTime $locked_at,
        ?DateTime $unlocked_at = null,
        ?DateTime $system_release_at = null
    ) {
        $this->release_key = $release_key;
        $this->locked_at = $locked_at;
        $this->unlocked_at = $unlocked_at;
        $this->system_release_at = $system_release_at;
    }

    public function getReleaseKey(): string
    {
        return $this->release_key;
    }

    public function getLockedAt(): DateTime
    {
        return $this->locked_at;
    }

    public function getUnlockedAt(): ?DateTime
    {
        return $this->unlocked_at;
    }

    public function getSystemReleaseAt(): ?DateTime
    {
        return $this->system_release_at;
    }
}
