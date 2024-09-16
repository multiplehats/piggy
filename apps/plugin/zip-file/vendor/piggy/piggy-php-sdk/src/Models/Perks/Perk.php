<?php

namespace Piggy\Api\Models\Perks;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Http\Responses\Response;
use Piggy\Api\StaticMappers\Perks\PerkMapper;
use Piggy\Api\StaticMappers\Perks\PerksMapper;

class Perk
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $dataType;

    /**
     * @var mixed[]
     */
    protected $options = [];

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/perks';

    /**
     * @param mixed[] $options
     */
    public function __construct(
        string $uuid,
        string $label,
        string $name,
        string $dataType,
        array $options
    ) {
        $this->uuid = $uuid;
        $this->label = $label;
        $this->name = $name;
        $this->dataType = $dataType;
        $this->options = $options;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param  mixed[]  $params
     * @return Perk[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return PerksMapper::map((array) $response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): Perk
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return PerkMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $perkUuid, array $params = []): Perk
    {
        $response = ApiClient::get(self::resourceUri."/$perkUuid", $params);

        return PerkMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function update(string $perkUuid, array $body): Perk
    {
        $response = ApiClient::put(self::resourceUri."/$perkUuid", $body);

        return PerkMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function delete(string $perkUuid, array $params = []): Response
    {
        return ApiClient::delete(self::resourceUri."/$perkUuid", $params);
    }
}
