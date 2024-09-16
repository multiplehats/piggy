<?php

namespace Piggy\Api\Models\Contacts;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Contacts\SubscriptionMapper;
use Piggy\Api\StaticMappers\Contacts\SubscriptionsMapper;

class Subscription
{
    /**
     * @var SubscriptionType
     */
    protected $subscriptionType;

    /**
     * @var bool
     */
    protected $isSubscribed;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/contact-subscriptions';

    public function __construct(SubscriptionType $subscriptionType, bool $isSubscribed, string $status)
    {
        $this->subscriptionType = $subscriptionType;
        $this->isSubscribed = $isSubscribed;
        $this->status = $status;
    }

    public function getSubscriptionType(): SubscriptionType
    {
        return $this->subscriptionType;
    }

    public function setSubscriptionType(SubscriptionType $subscriptionType): void
    {
        $this->subscriptionType = $subscriptionType;
    }

    public function isSubscribed(): bool
    {
        return $this->isSubscribed;
    }

    public function setIsSubscribed(bool $isSubscribed): void
    {
        $this->isSubscribed = $isSubscribed;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @param  mixed[]  $params
     * @return Subscription[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(string $contactUuid, array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri."/$contactUuid", $params);

        return SubscriptionsMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function subscribe(string $contactUuid, array $params): Subscription
    {
        $response = ApiClient::put(self::resourceUri."/$contactUuid/subscribe", $params);

        return SubscriptionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function unsubscribe(string $contactUuid, array $params): Subscription
    {
        $response = ApiClient::put(self::resourceUri."/$contactUuid/unsubscribe", $params);

        return SubscriptionMapper::map($response->getData());
    }
}
