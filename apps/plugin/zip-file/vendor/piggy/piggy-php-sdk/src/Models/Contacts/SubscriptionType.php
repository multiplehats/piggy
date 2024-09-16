<?php

namespace Piggy\Api\Models\Contacts;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Contacts\SubscriptionTypesMapper;

class SubscriptionType
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
    protected $description;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string|null
     */
    protected $strategy;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/subscription-types';

    public function __construct(string $uuid, string $name, string $description, bool $active, string $strategy)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->active = $active;
        $this->strategy = $strategy;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getStrategy(): ?string
    {
        return $this->strategy;
    }

    public function setStrategy(?string $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @param  mixed[]  $params
     * @return SubscriptionType[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return SubscriptionTypesMapper::map($response->getData());
    }
}
