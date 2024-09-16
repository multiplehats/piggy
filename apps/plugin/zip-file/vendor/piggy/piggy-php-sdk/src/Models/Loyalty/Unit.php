<?php

namespace Piggy\Api\Models\Loyalty;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Units\UnitMapper;
use Piggy\Api\StaticMappers\Units\UnitsMapper;

class Unit
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $label;

    /** @var bool|null */
    protected $isDefault;

    /**
     * @var string|null
     */
    protected $prefix;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/units';

    public function __construct(string $name, ?string $label, ?bool $isDefault, ?string $prefix)
    {
        $this->name = $name;
        $this->label = $label;
        $this->isDefault = $isDefault;
        $this->prefix = $prefix;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param  mixed[]  $params
     * @return Unit[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return UnitsMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): Unit
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return UnitMapper::map($response->getData());
    }
}
