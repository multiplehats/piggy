<?php

namespace Piggy\Api\Models\Loyalty\RewardAttributes;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Loyalty\RewardAttributes\RewardAttributeMapper;
use Piggy\Api\StaticMappers\Loyalty\RewardAttributes\RewardAttributesMapper;

class RewardAttribute
{
    /** @var string */
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
    protected $dataType;

    /**
     * @var string|null
     */
    protected $fieldType;

    /**
     * @var bool|null
     */
    protected $isSoftReadOnly;

    /**
     * @var bool|null
     */
    protected $isHardReadOnly;

    /**
     * @var bool|null
     */
    protected $isPiggyDefined;

    /**
     * @var mixed[]|null
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $placeholder;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/reward-attributes';

    /**
     * @param  mixed[]|null  $options
     */
    public function __construct(string $name, string $label, string $description, string $dataType, ?string $fieldType, ?bool $isSoftReadOnly = null, ?bool $isHardReadOnly = null, ?bool $isPiggyDefined = null, ?array $options = null, ?string $placeholder = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->dataType = $dataType;
        $this->fieldType = $fieldType;
        $this->isSoftReadOnly = $isSoftReadOnly;
        $this->isHardReadOnly = $isHardReadOnly;
        $this->isPiggyDefined = $isPiggyDefined;
        $this->options = $options;
        $this->placeholder = $placeholder;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->dataType;
    }

    public function setType(string $dataType): void
    {
        $this->dataType = $dataType;
    }

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): void
    {
        $this->fieldType = $fieldType;
    }

    public function getIsSoftReadOnly(): ?bool
    {
        return $this->isSoftReadOnly;
    }

    public function setIsSoftReadOnly(bool $isSoftReadOnly): void
    {
        $this->isSoftReadOnly = $isSoftReadOnly;
    }

    public function getIsHardReadOnly(): ?bool
    {
        return $this->isHardReadOnly;
    }

    public function setIsHardReadOnly(bool $isHardReadOnly): void
    {
        $this->isHardReadOnly = $isHardReadOnly;
    }

    public function getIsPiggyDefined(): ?bool
    {
        return $this->isPiggyDefined;
    }

    public function setIsPiggyDefined(bool $isPiggyDefined): void
    {
        $this->isPiggyDefined = $isPiggyDefined;
    }

    /**
     * @return mixed[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param  mixed[]|null  $options
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @param  mixed[]  $params
     * @return RewardAttribute[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return RewardAttributesMapper::map((array) $response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): RewardAttribute
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return RewardAttributeMapper::map($response->getData());
    }
}
