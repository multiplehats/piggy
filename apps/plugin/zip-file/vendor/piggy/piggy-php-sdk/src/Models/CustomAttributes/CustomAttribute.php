<?php

namespace Piggy\Api\Models\CustomAttributes;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\CustomAttributes\CustomAttributeMapper;
use Piggy\Api\StaticMappers\CustomAttributes\CustomAttributesMapper;

class CustomAttribute
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $entity;

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
    protected $type;

    /**
     * @var mixed[]
     */
    protected $meta;

    /**
     * @var ?string
     */
    protected $groupName;

    /**
     * @var ?Group
     */
    protected $group;

    /**
     * @var bool|null
     */
    protected $isPiggyDefined;

    /**
     * @var bool|null
     */
    protected $isSoftReadOnly;

    /**
     * @var bool|null
     */
    protected $isHardReadOnly;

    /**
     * @var string|null
     */
    protected $fieldType;

    /**
     * @var bool
     */
    protected $hasUniqueValue;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var mixed[]
     */
    protected $options;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string
     */
    protected $createdAt;

    /**
     * @var bool
     */
    protected $canBeDeleted;

    /**
     * @var string|null
     */
    protected $lastUsedDate;

    /**
     * @var string|null
     */
    protected $createdByUser;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/custom-attributes';

    /**
     * @param  mixed[]  $options
     * @param  mixed[]  $meta
     */
    public function __construct(
        int $id,
        string $entity,
        string $name,
        string $label,
        string $type,
        bool $isPiggyDefined,
        bool $isSoftReadOnly,
        bool $isHardReadOnly,
        bool $hasUniqueValue,
        string $description,
        array $options,
        int $position,
        string $createdAt,
        bool $canBeDeleted,
        array $meta = [],
        ?string $groupName = null,
        ?Group $group = null,
        ?string $fieldType = null,
        ?string $lastUsedDate = null,
        ?string $createdByUser = null
    ) {
        $this->id = $id;
        $this->entity = $entity;
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->groupName = $groupName;
        $this->group = $group;
        $this->isPiggyDefined = $isPiggyDefined;
        $this->isSoftReadOnly = $isSoftReadOnly;
        $this->isHardReadOnly = $isHardReadOnly;
        $this->fieldType = $fieldType;
        $this->hasUniqueValue = $hasUniqueValue;
        $this->description = $description;
        $this->options = $options;
        $this->position = $position;
        $this->meta = $meta;
        $this->createdAt = $createdAt;
        $this->canBeDeleted = $canBeDeleted;
        $this->lastUsedDate = $lastUsedDate;
        $this->createdByUser = $createdByUser;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function getIsPiggyDefined(): ?bool
    {
        return $this->isPiggyDefined;
    }

    public function getIsSoftReadOnly(): ?bool
    {
        return $this->isSoftReadOnly;
    }

    public function getIsHardReadOnly(): ?bool
    {
        return $this->isHardReadOnly;
    }

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function getHasUniqueValue(): bool
    {
        return $this->hasUniqueValue;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCanBeDeleted(): bool
    {
        return $this->canBeDeleted;
    }

    public function getLastUsedDate(): ?string
    {
        return $this->lastUsedDate;
    }

    public function getCreatedByUser(): ?string
    {
        return $this->createdByUser;
    }

    /**
     * @param  mixed[]  $params
     * @return CustomAttribute[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return CustomAttributesMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): CustomAttribute
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return CustomAttributeMapper::map($response->getData());
    }
}
