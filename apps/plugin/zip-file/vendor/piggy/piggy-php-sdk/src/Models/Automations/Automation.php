<?php

namespace Piggy\Api\Models\Automations;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Automations\AutomationsMapper;

class Automation
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/automations';

    public function __construct(string $name, string $status, string $event, DateTime $createdAt, DateTime $updatedAt)
    {
        $this->name = $name;
        $this->status = $status;
        $this->event = $event;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param  mixed[]  $params
     * @return Automation[]
     *
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return AutomationsMapper::map($response);
    }

    /**
     * @param  mixed[]  $body
     * @return Automation[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): array
    {
        $response = ApiClient::post(self::resourceUri.'/'.'runs', $body);

        return AutomationsMapper::map($response);
    }
}
