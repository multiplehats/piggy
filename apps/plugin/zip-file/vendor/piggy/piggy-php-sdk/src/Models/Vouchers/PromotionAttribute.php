<?php

namespace Piggy\Api\Models\Vouchers;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Vouchers\PromotionAttributeMapper;
use Piggy\Api\StaticMappers\Vouchers\PromotionAttributesMapper;

class PromotionAttribute
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed[]
     */
    protected $options;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var ?string
     */
    protected $placeholder;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/promotion-attributes';

    /**
     * @param  mixed[]  $options
     */
    public function __construct(
        string $name,
        string $description,
        string $label,
        string $type,
        array $options,
        ?int $id = null,
        ?string $placeholder = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->label = $label;
        $this->type = $type;
        $this->options = $options;
        $this->id = $id;
        $this->placeholder = $placeholder;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
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
     * @return PromotionAttribute[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return PromotionAttributesMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $promotionAttributeId, array $params = []): PromotionAttribute
    {
        $response = ApiClient::get(self::resourceUri."/$promotionAttributeId", $params);

        return PromotionAttributeMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function create(array $body): PromotionAttribute
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return PromotionAttributeMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function update(string $promotionAttributeId, array $params): PromotionAttribute
    {
        $response = ApiClient::put(self::resourceUri."/$promotionAttributeId", $params);

        return PromotionAttributeMapper::map($response->getData());
    }
}
