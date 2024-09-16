<?php

namespace Piggy\Api\Models\Loyalty\Receptions;

use DateTime;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\Contacts\ContactIdentifier;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Shops\Shop;

class PhysicalRewardReception extends BaseReception
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $credits;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var ContactIdentifier|null
     */
    protected $contactIdentifier;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var DateTime|null
     */
    protected $expiresAt;

    /**
     * @var Reward
     */
    protected $reward;

    /**
     * @var bool
     */
    protected $hasBeenCollected;

    public function __construct(string $type, int $credits, string $uuid, Contact $contact, Shop $shop, string $channel, ?ContactIdentifier $contactIdentifier, DateTime $createdAt, string $title, Reward $reward, ?DateTime $expiresAt, bool $hasBeenCollected)
    {
        $this->type = $type;
        $this->credits = $credits;
        $this->uuid = $uuid;
        $this->contact = $contact;
        $this->shop = $shop;
        $this->channel = $channel;
        $this->contactIdentifier = $contactIdentifier;
        $this->createdAt = $createdAt;
        $this->title = $title;
        $this->reward = $reward;
        $this->expiresAt = $expiresAt;
        $this->hasBeenCollected = $hasBeenCollected;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getContactIdentifier(): ?ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function getHasBeenCollected(): bool
    {
        return $this->hasBeenCollected;
    }
}
