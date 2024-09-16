<?php

namespace Piggy\Api\Resources\OAuth\Automations;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Http\BaseClient;
use Piggy\Api\Mappers\Automations\AutomationsMapper;
use Piggy\Api\Models\Automations\Automation;
use Piggy\Api\Resources\BaseResource;

class AutomationsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/automations';

    public function __construct(BaseClient $client)
    {
        parent::__construct($client);

    }

    /**
     * @param  mixed[]  $params
     * @return Automation[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new AutomationsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]|null  $data
     * @return array<null>
     *
     * @throws PiggyRequestException
     */
    public function create(string $contactUuid, string $automationUuid, ?array $data = null): array
    {
        $response = $this->client->post("$this->resourceUri/runs", [
            'contact_uuid' => $contactUuid,
            'automation_uuid' => $automationUuid,
            'data' => $data,
        ]);

        return $response->getData();
    }
}
