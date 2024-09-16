<?php

namespace Piggy\Api\Models\Giftcards;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Giftcards\GiftcardProgramsMapper;

class GiftcardProgram
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
     * @var bool
     */
    protected $active;

    const resourceUri = '/api/v3/oauth/clients/giftcard-programs';

    /**
     * GiftcardProgram constructor.
     */
    public function __construct(string $uuid, string $name, bool $active)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->active = $active;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return GiftcardProgram[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(): array
    {
        $response = ApiClient::get(self::resourceUri);

        return GiftcardProgramsMapper::map($response->getData());
    }
}
