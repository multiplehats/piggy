<?php

namespace Piggy\Api\Models\WebhookSubscriptions;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\WebhookSubscriptions\WebhookSubscriptionMapper;
use Piggy\Api\StaticMappers\WebhookSubscriptions\WebhookSubscriptionsMapper;

class WebhookSubscription
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $event_type;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var mixed[]|null
     */
    protected $properties;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var DateTime
     */
    protected $created_at;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/webhook-subscriptions';

    /**
     * @param  mixed[]|null  $properties
     */
    public function __construct(
        string $uuid,
        string $name,
        string $eventType,
        string $url,
        ?array $properties,
        string $status,
        string $version,
        DateTime $createdAt
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->event_type = $eventType;
        $this->url = $url;
        $this->properties = $properties;
        $this->status = $status;
        $this->version = $version;
        $this->created_at = $createdAt;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return mixed[]|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function update(string $webhookUuid, array $params): WebhookSubscription
    {
        $response = ApiClient::put(self::resourceUri."/{$webhookUuid}", $params);

        return WebhookSubscriptionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     * @return WebhookSubscription[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return WebhookSubscriptionsMapper::map((array) $response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): WebhookSubscription
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return WebhookSubscriptionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $webhookUuid, array $params = []): WebhookSubscription
    {
        $response = ApiClient::get(self::resourceUri."/$webhookUuid", $params);

        return WebhookSubscriptionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     * @return mixed[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function delete(string $webhookUuid, array $params = []): array
    {
        $response = ApiClient::delete(self::resourceUri."/$webhookUuid", $params);

        return $response->getData();
    }
}
