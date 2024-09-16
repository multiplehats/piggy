<?php

namespace Piggy\Api\Models\Loyalty;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Loyalty\LoyaltyProgramMapper;

class LoyaltyProgram
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    private $customCreditName;

    /**
     * @var int|null
     */
    private $maxAmount;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/loyalty-program';

    public function __construct(int $id, string $name, ?int $maxAmount = null, string $customCreditName = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->customCreditName = $customCreditName;
        $this->maxAmount = $maxAmount;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCustomCreditName(): string
    {
        return $this->customCreditName;
    }

    public function getMaxAmount(): ?int
    {
        return $this->maxAmount;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(array $params = []): LoyaltyProgram
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return LoyaltyProgramMapper::map($response->getData());
    }
}
