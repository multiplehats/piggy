<?php

namespace Piggy\Api\Models\Forms;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Forms\FormsMapper;

class Form
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
    protected $url;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    public $type;

    const resourceUri = '/api/v3/oauth/clients/forms';

    public function __construct(string $name, string $status, string $url, string $uuid, string $type)
    {
        $this->name = $name;
        $this->status = $status;
        $this->url = $url;
        $this->uuid = $uuid;
        $this->type = $type;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param  mixed[]  $params
     * @return Form[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return FormsMapper::map((array) $response->getData());
    }
}
