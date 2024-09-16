<?php

namespace Piggy\Api\Models\Loyalty\Rewards;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\StaticMappers\Loyalty\Rewards\CollectableRewardMapper;
use Piggy\Api\StaticMappers\Loyalty\Rewards\CollectableRewardsMapper;

class CollectableReward
{
    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var Reward
     */
    protected $reward;

    /**
     * @var DateTime|null
     */
    protected $expiresAt;

    /**
     * @var bool
     */
    protected $hasBeenCollected;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/collectable-rewards';

    public function __construct(
        Contact $contact,
        DateTime $createdAt,
        string $uuid,
        string $title,
        Reward $reward,
        ?DateTime $expiresAt,
        bool $hasBeenCollected
    ) {
        $this->contact = $contact;
        $this->createdAt = $createdAt;
        $this->uuid = $uuid;
        $this->title = $title;
        $this->reward = $reward;
        $this->expiresAt = $expiresAt;
        $this->hasBeenCollected = $hasBeenCollected;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUuid(): string
    {
        return $this->uuid;
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

    public function hasBeenCollected(): bool
    {
        return $this->hasBeenCollected;
    }

    /**
     * @param  mixed[]  $params
     * @return CollectableReward[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     * @throws Exception
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return CollectableRewardsMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     * @throws Exception
     */
    public static function collect(string $loyaltyTransactionUuid, array $params = []): CollectableReward
    {
        $response = ApiClient::put(self::resourceUri."/collect/$loyaltyTransactionUuid", $params);

        return CollectableRewardMapper::map($response->getData());
    }
}
