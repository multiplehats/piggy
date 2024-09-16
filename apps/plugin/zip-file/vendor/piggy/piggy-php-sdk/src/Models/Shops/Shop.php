<?php

namespace Piggy\Api\Models\Shops;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Shops\ShopMapper;
use Piggy\Api\StaticMappers\Shops\ShopsMapper;

class Shop
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
     * @var null|int
     */
    protected $id;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/shops';

    public function __construct(string $uuid, string $name, ?int $id)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->id = $id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param  mixed[]  $params
     * @return Shop[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return ShopsMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $shopUuid, array $params = []): Shop
    {
        $response = ApiClient::get(self::resourceUri."/$shopUuid", $params);

        return ShopMapper::map($response->getData());
    }
}
